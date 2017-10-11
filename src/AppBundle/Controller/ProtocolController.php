<?php

namespace AppBundle\Controller;

use Psr\Log\LoggerInterface;

use Symfony\Component\Filesystem\Filesystem;

use AppBundle\Entity\Protocol;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use AppBundle\Service\PDFPrinter;
use AppBundle\Service\PermissionsService;

use \Firebase\JWT\JWT;

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
    public function indexAction(LoggerInterface $logger, SessionInterface $session, PermissionsService $permissions)
    {
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
            'names' => $names
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
    public function buyAction($id, Request $request, LoggerInterface $logger, SessionInterface $session, PermissionsService $permissions)
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
            $answers = [];
            foreach ($questionsForm->getData() as $key => $value) {
                $answers []= $key . '=' . $value;
            }
            $purchasedProtocol->setAnswers(implode(',', $answers));
            $em = $this->getDoctrine()->getManager();
            $em->persist($purchasedProtocol);
            $em->flush();

            return $this->redirectToRoute('protocol_pay', array(
                'id' => $protocol['id']
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
    public function payAction(Protocol $protocol, Request $request, LoggerInterface $logger, SessionInterface $session, PermissionsService $permissions)
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
            $payer_email = $postData->get('payer_email'); // => pepellou-buyer@gmail.com 
            $payer_id = $postData->get('payer_id'); // => 3LTGLV3ZK2MYA 
            $payer_status = $postData->get('payer_status'); // => VERIFIED 
            $first_name = $postData->get('first_name'); // => test 
            $last_name = $postData->get('last_name'); // => buyer 
            $txn_id = $postData->get('txn_id'); // => 58021743YV823525V 
            $mc_currency = $postData->get('mc_currency'); // => EUR 
            $mc_fee = $postData->get('mc_fee'); // => 0.55 
            $mc_gross = $postData->get('mc_gross'); // => 5.90 
            $protection_eligibility = $postData->get('protection_eligibility'); // => INELIGIBLE 
            $payment_fee = $postData->get('payment_fee'); // => 0.55 
            $payment_gross = $postData->get('payment_gross'); // => 5.90 
            $payment_status = $postData->get('payment_status'); // => Completed 
            $payment_type = $postData->get('payment_type'); // => instant 
            $item_name = $postData->get('item_name'); // => Modelo Autocobertura Redes Sociales 
            $item_number = $postData->get('item_number'); // => 12 
            $quantity = $postData->get('quantity'); // => 1 
            $txn_type = $postData->get('txn_type'); // => web_accept 
            $payment_date = $postData->get('payment_date'); // => 2017-10-11T08:45:12Z 
            $business = $postData->get('business'); // => pepellou-facilitator@gmail.com 
            $receiver_id = $postData->get('receiver_id'); // => GAT2BUMPBCM3G 
            $notify_version = $postData->get('notify_version'); // => UNVERSIONED 
            $custom = $postData->get('custom'); // => {"ip_address":"77.27.142.122","quaderno_id":346579,"application":"quaderno","tax":{"name":"IVA","rate":21.0,"country":"ES"}} 
            $verify_sign = $postData->get('verify_sign'); // => AFcWxV21C7fd0v3bYYYRCpSSRl31AjWh4qtr.48ClpfjVHt.TjLMdf9a

            if ($payer_status == 'VERIFIED') {
                $protocol->setEnabled(true);
                $this->getDoctrine()->getManager()->flush();
                // TODO create invoice
            }
            //TODO remote payment_complete template
            return $this->redirectToRoute('protocol_index');
        }

        $amount = 590;
        $key = "sk_test_VfHLuScwssnCCBo7Jw65";
        $token = array(
            "iat" => time(),
            "amount" => $amount,
            "currency" => "EUR",
            "description" => $protocol_spec['name'],
            "item_number" => $protocol->getId(),
            "quantity" => 1
        );
        $jwt = JWT::encode($token, $key);

        return $this->render('protocol/payment.html.twig', array(
            'user' => $user,
            'protocol_spec' => $protocol_spec,
            'charge' => $jwt,
            'payment_data' => array(
                'order_hash' => 'sample_hash',
                'bank_account' => $this->container->getParameter('account_number'),
                'amount' => $this->formatEuro($amount)
            )
        ));
    }

    private function formatEuro($amount) {
        $amount = "$amount";
        return substr($amount, 0, strlen($amount) - 2) . '.' . substr($amount, -2);
    }

}
