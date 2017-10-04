<?php

namespace AppBundle\Controller;

use Psr\Log\LoggerInterface;

use Symfony\Component\Filesystem\Filesystem;

use AppBundle\Entity\Protocol;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use AppBundle\Service\PDFPrinter;

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
    public function indexAction(LoggerInterface $logger, SessionInterface $session)
    {
        $user = $this->getUserFromSession($session);
        if ($user == null) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

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

    private function getUserFromSession($session) {
        if (!$session->get('user')) {
            return null;
        }

        $found = $this->getDoctrine()
            ->getRepository(User::class)
            ->findByEmail($session->get('user'));

        if (!$found) {
            return null;
        }

        return $found[0];
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
    public function buyAction($id, Request $request, LoggerInterface $logger, SessionInterface $session)
    {
        $user = $this->getUserFromSession($session);
        if ($user == null) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

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

            return $this->render('protocol/payment.html.twig', array(
                'profile_completed' => $profile_completed,
                'form' => $questionsForm->createView(),
                'protocol' => $protocol
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
            $formBuilder->add($question['id'], ChoiceType::class, array(
                'label' => $question['question'],
                'choices' => $choices,
                'expanded' => !$isConfirmation,
                'multiple' => false
            ));
        }

        return $formBuilder ->getForm();
    }

    /**
     * Downloads a protocol.
     *
     */
    public function downloadAction(Protocol $protocol, PDFPrinter $printer, Request $request, LoggerInterface $logger, SessionInterface $session)
    {
        $user = $this->getUserFromSession($session);
        if ($user == null) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }


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

        if ($request->query->get('logo') == 'yes') {
            $printer->setLogo($this->get('kernel')->getRootDir() . '/../src/AppBundle/Resources/public/img/logo_agilaz.png');
        }

        $variables = [];
        $asignments = explode(',', $protocol->getAnswers());
        foreach ($asignments as $asignment) {
            list($var, $val) = explode('=', $asignment);
            $variables[$var] = $val;
        }
        $variables['company_name'] = $user->getCompanyName();
        $printer->setVariables($variables);

        $printer->setStyles($document['styles']);
        $printer->setContent($document['content']);

        //return new Response($printer->print(), 200);
        return new Response($printer->print(), 200, array( 'Content-Type' => 'application/pdf'));
    }

    /**
     * Pays a protocol.
     *
     */
    public function payAction($id, $type, Request $request, LoggerInterface $logger, SessionInterface $session)
    {
        $user = $this->getUserFromSession($session);
        if ($user == null) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        $protocol = $this->container->getParameter('protocol.'.$id);
        if ($protocol == null) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }
        $protocol['id'] = $id;

        $found = $this->getDoctrine()
            ->getRepository(Protocol::class)
            ->findOneBy(array(
                'user' => $user->getId(),
                'identifier' => $id
            ));

        if ($type == 'paypal') {
            $found->setEnabled(true);
            $this->getDoctrine()->getManager()->flush();
        }

        return $this->redirectToRoute('protocol_index');
    }

}
