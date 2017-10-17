<?php

namespace AppBundle\Controller;

use Psr\Log\LoggerInterface;

use Symfony\Component\Filesystem\Filesystem;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use \Firebase\JWT\JWT;

use AppBundle\Entity\Protocol;
use AppBundle\Entity\User;
use AppBundle\Service\HashGenerator;
use AppBundle\Service\Invoices;
use AppBundle\Service\PDFPrinter;
use AppBundle\Service\PermissionsService;
use AppBundle\Service\OrderNumberFormatter;
use AppBundle\Service\Quaderno;
use AppBundle\Service\Protocols;

/**
 * Protocol controller.
 *
 */
class ProtocolController extends Controller
{

    /**
     * Lists all protocol entities of the current user.
     *
     */
    public function indexAction(LoggerInterface $logger, SessionInterface $session, PermissionsService $permissions, Invoices $invoices)
    {
        if ($permissions->currentRolesInclude("admin")) {
            return $this->showAllOrders();
        }

        if (!$permissions->currentRolesInclude("customer")) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        $user = $permissions->getCurrentUser();

        $protocols = $this->getDoctrine()
            ->getRepository(Protocol::class)
            ->findByUser($user->getId());

        $names = [];
        $protocols_specs = $this->container->getParameter('protocols');
        foreach ($protocols_specs as $id) {
            $protocol_spec = $this->container->getParameter('protocol.'.$id);
            $names[$id] = $protocol_spec['name'];
        }

        return $this->render('protocol/index.html.twig', array(
            'protocols' => $protocols,
            'invoices' => $invoices->getInvoicesForProtocols($protocols),
            'names' => $names
        ));
    }

    private function showAllOrders() {
        $protocols = $this->getDoctrine()
            ->getRepository(Protocol::class)
            ->findByEnabled(false);

        $names = [];
        $protocols_specs = $this->container->getParameter('protocols');
        foreach ($protocols_specs as $id) {
            $protocol_spec = $this->container->getParameter('protocol.'.$id);
            $names[$id] = $protocol_spec['name'];
        }

        $users = [];
        foreach ($protocols as $protocol) {
            $users[$protocol->getUser()] = $this->getDoctrine()
                ->getRepository(User::class)
                ->find($protocol->getUser());
        }

        return $this->render('protocol/full_list.html.twig', array(
            'protocols' => $protocols,
            'names' => $names,
            'users' => $users
        ));
    }

    private function userHasCompletedProfile($user) {
        return ( $user->getEmail() != null )
            && ( $user->getPassword() != null )
            && ( $user->getCompanyName() != null )
            && ( $user->getCif() != null )
            && ( $user->getAddress() != null )
            && ( $user->getContactPerson() != null )
            && ( $user->getNumberEmployees() != null )
            && ( $user->getSector() != null );
    }

    /**
     * Buys a protocol.
     *
     */
    public function buyAction($id, Request $request, LoggerInterface $logger, SessionInterface $session, PermissionsService $permissions, HashGenerator $hasher)
    {
        if (!$permissions->currentRolesInclude("customer")) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        $user = $permissions->getCurrentUser();

        $protocol = $this->container->getParameter('protocol.'.$id);
        if ($protocol == null) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }
        $protocol['id'] = $id;

        $profile_completed = $this->userHasCompletedProfile($user);

        $questionsForm = $this->getForm(
            $protocol['questions'],
            $request->isMethod('POST')
        );

        $questionsForm->handleRequest($request);

        if ($questionsForm->isSubmitted() && $questionsForm->isValid()) {
            if ($request->get('confirmed') == null) {
                return $this->render('protocol/confirmation.html.twig', array(
                    'profile_completed' => $profile_completed,
                    'form' => $questionsForm->createView(),
                    'protocol' => $protocol
                ));
            }

            $purchasedProtocol = new Protocol();
            $purchasedProtocol->setIdentifier($id);
            $purchasedProtocol->setUser($user->getId());
            $purchasedProtocol->setEnabled(false);
            $purchasedProtocol->setOrderHash($hasher->generate(8, false));
            $purchasedProtocol->setOrderDate(new \DateTime(date('Y-m-d')));
            $purchasedProtocol->setPrice($this->container->getParameter('protocol_price'));
            $answers = [];
            foreach ($questionsForm->getData() as $key => $value) {
                $answers []= $key . '=' . $value;
            }
            $purchasedProtocol->setAnswers(implode(',', $answers));
            $em = $this->getDoctrine()->getManager();
            $em->persist($purchasedProtocol);
            $em->flush();

            return $this->redirectToRoute('protocol_pay', array(
                'id' => $purchasedProtocol->getId()
            ));
        }

        return $this->render('protocol/questions.html.twig', array(
            'profile_completed' => $profile_completed,
            'form' => $questionsForm->createView(),
            'protocol' => $protocol
        ));
    }

    private function getForm($questions, $isConfirmation) {
        $formBuilder = $this->createFormBuilder();
        foreach ($questions as $question) {
            $choices = array();
            $count = 0;
            foreach ($question['answers'] as $answer) {
                $choices[$answer] = $count++;
            }
            $properties = array(
                'label' => $question['question'],
                'choices' => $choices,
                'expanded' => !$isConfirmation,
                'multiple' => false
            );
            if (isset($question['condition'])) {
                $properties['attr'] = array(
                    'class' => 'has-condition',
                    'data-condition' => $question['condition']
                );
                $properties['label_attr'] = array(
                    'class' => 'has-condition',
                    'data-condition' => $question['condition']
                );
            }
            $formBuilder->add($question['id'], ChoiceType::class, $properties);
        }

        return $formBuilder ->getForm();
    }

    /**
     * Downloads a protocol.
     *
     */
    public function downloadAction(Protocol $protocol, PDFPrinter $printer, Request $request, LoggerInterface $logger, SessionInterface $session, PermissionsService $permissions)
    {
        if (!$permissions->currentRolesInclude("customer")) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        $user = $permissions->getCurrentUser();

        if ($protocol->getUser() != $user->getId()) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        $protocol_spec = $this->container->getParameter('protocol.'.$protocol->getIdentifier());
        if ($protocol_spec == null) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        if (!isset($protocol_spec['document'])) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        $document = $protocol_spec['document'];
        $printer->setFileName($protocol_spec['name'].'.pdf');

        if ($user->getLogo() != null) {
            $printer->setLogo($this->get('kernel')->getRootDir() . '/../web/uploads/' . $user->getLogo());
        }

        $variables = [];
        $asignments = explode(',', $protocol->getAnswers());
        foreach ($asignments as $asignment) {
            list($var, $val) = explode('=', $asignment);
            $variables[$var] = $val;
        }
        $variables['company_name'] = $user->getCompanyName();
        $printer->setVariables($variables);
        $printer->setQuestions($protocol_spec['questions']);

        $printer->setStyles($document['styles']);
        $printer->setContent($document['content']);

        return new Response($printer->print(), 200, array( 'Content-Type' => 'application/pdf'));
    }

    /**
     * Pays a protocol.
     *
     */
    public function payAction(Protocol $protocol, Request $request, LoggerInterface $logger, SessionInterface $session, PermissionsService $permissions, OrderNumberFormatter $formatter, Invoices $invoices)
    {
        if (!$permissions->currentRolesInclude("customer")) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        $user = $permissions->getCurrentUser();
        if ($protocol->getUser() != $user->getId()) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        $protocol_spec = $this->container->getParameter('protocol.'.$protocol->getIdentifier());
        if ($protocol_spec == null) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        if ($request->isMethod('POST')) {
            $postData = $request->request;
            $payer_status = $postData->get('payer_status');
            $item_number = $postData->get('item_number');

            if ($payer_status != 'VERIFIED' || $formatter->format($protocol->getId()) != $item_number) {
                $logger->error("Error returning from Paypal payment of protocol #".$protocol->getId());
                $logger->error("    payer_status = ".$payer_status);
                $logger->error("    item_number  = ".$item_number);
                return $this->redirectToRoute('error', array(
                    'message' => 'Ha ocurrido un error inesperado.'
                ));
            }
            $protocol->setEnabled(true);
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('protocol_paid');
        }

        $amount = $protocol->getPrice();
        $token = array(
            "iat" => time(),
            "amount" => $amount,
            "currency" => "EUR",
            "description" => $protocol_spec['name'],
            "item_number" => $formatter->format($protocol->getId()),
            "quantity" => 1
        );
        $jwt = JWT::encode($token, $this->container->getParameter('quaderno_api_key'));

        return $this->render('protocol/payment.html.twig', array(
            'user' => $user,
            'amount' => $amount,
            'protocol_spec' => $protocol_spec,
            'charge' => $jwt,
            'payment_data' => array(
                'order_hash' => $protocol->getOrderHash(),
                'bank_account' => $this->container->getParameter('account_number'),
                'amount' => $this->formatEuro($amount)
            )
        ));
    }

    private function formatEuro($amount) {
        $amount = "$amount";
        return substr($amount, 0, strlen($amount) - 2) . '.' . substr($amount, -2);
    }

    /**
     * Shows a payment completion status page.
     *
     */
    public function paymentCompleteAction()
    {
        return $this->render('protocol/payment_complete.html.twig');
    }

    /**
     * Marks a protocol as paid by transfer.
     *
     */
    public function payTransferAction(Protocol $protocol, LoggerInterface $logger, PermissionsService $permissions, Quaderno $quaderno, Protocols $protocols)
    {
        if (!$permissions->currentRolesInclude("admin")) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        $theUser = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($protocol->getUser());

        $theInvoice = $quaderno->createInvoice($theUser, $protocol);

        if ($theInvoice == null) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        $this->getDoctrine()->getManager()->persist($theInvoice);
        $protocol->setEnabled(true);
        $protocol->setInvoice($theInvoice->getId());
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('protocol_index');
    }

}
