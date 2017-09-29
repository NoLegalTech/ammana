<?php

namespace AppBundle\Controller;

use Psr\Log\LoggerInterface;

use Symfony\Component\Filesystem\Filesystem;

use AppBundle\Entity\Protocol;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

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

        return $this->render('protocol/index.html.twig', array(
            'protocols' => $protocols,
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
    public function buyAction($id, LoggerInterface $logger, SessionInterface $session)
    {
        $user = $this->getUserFromSession($session);
        if ($user == null) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        $profile_completed = $this->userHasCompletedProfile($user);

        $protocol_name = "Modelo Autocobertura Redes Sociales";
        $path = "M_redes.pdf";
        switch($id) {
            case 1:
                $protocol_name = "Modelo Autocobertura Redes Sociales";
                $path = "M_redes.pdf";
                break;
            case 2:
                $protocol_name = "Modelo Autocobertura Telemática";
                $path = "M_telematica.pdf";
                break;
            case 3:
                $protocol_name = "Modelo Autocobertura Mensajerías";
                $path = "M_mensajerias.pdf";
                break;
        }

        return $this->render('protocol/questions.html.twig', array(
            'user' => $user,
            'profile_completed' => $profile_completed,
            'disabled' => $profile_completed ? "" : "disabled",
            'protocol_name' => $protocol_name,
            'protocols' => $this->container->getParameter('protocols')
        ));
    }

    /**
     * Downloads a protocol.
     *
     */
    public function downloadAction(Protocol $protocol, LoggerInterface $logger, SessionInterface $session)
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

        $pdf = new \FPDF();

        $pdf->AddPage();
        $pdf->SetFont('Helvetica','',16);
        $pdf->SetTextColor(86,89,100);
        $pdf->SetFillColor(241,244,255);
        $pdf->SetXY(20,31);
        $pdf->Cell(0,0,'Protocolo fake "'.$protocol->getPath().'"',0,0,'',false);

        return new Response($pdf->Output('D', $protocol->getPath()), 200);
    }

}
