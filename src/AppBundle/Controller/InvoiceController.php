<?php

namespace AppBundle\Controller;

use Psr\Log\LoggerInterface;

use AppBundle\Entity\Invoice;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use AppBundle\Service\PermissionsService;

/**
 * Invoice controller.
 *
 */
class InvoiceController extends Controller
{
    /**
     * Lists all invoice entities of the current user.
     *
     */
    public function indexAction(LoggerInterface $logger, SessionInterface $session, PermissionsService $permissions)
    {
        if (!$permissions->currentRolesInclude("customer")) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        $user = $permissions->getCurrentUser($session);

        $invoices = $this->getDoctrine()
            ->getRepository(Invoice::class)
            ->findByUser($user->getId());

        return $this->render('invoice/index.html.twig', array(
            'invoices' => $invoices,
        ));
    }

    /**
     * Downloads an invoice.
     *
     */
    public function downloadAction(Invoice $invoice, LoggerInterface $logger, SessionInterface $session, PermissionsService $permissions)
    {
        if (!$permissions->currentRolesInclude("customer")) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        $user = $permissions->getCurrentUser($session);

        if ($invoice->getUser() != $user->getId()) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        $basePath = $this->get('kernel')->getRootDir(). "/../web/downloads/";
        $path = $basePath.$invoice->getPath();
        if (!file_exists($path) || !is_file($path)) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }
        $content = file_get_contents($path);

        $response = new Response();
        $response->headers->set('Content-Type', 'mime/type');
        $response->headers->set('Content-Disposition', 'attachment;filename="'.$invoice->getNumber().".pdf");

        $response->setContent($content);
        return $response;
    }
}
