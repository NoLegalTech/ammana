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

        $user = $permissions->getUserFromSession($session);

        $invoices = $this->getDoctrine()
            ->getRepository(Invoice::class)
            ->findByUser($user->getId());

        if (!$invoices || count($invoices) == 0) {
            $logger->info('Invoices is an empty set => creating 3 fake ones');
            $this->addThreeFakeInvoices($user);
            $invoices = $this->getDoctrine()
                ->getRepository(Invoice::class)
                ->findByUser($user->getId());
        }

        return $this->render('invoice/index.html.twig', array(
            'invoices' => $invoices,
        ));
    }

    private function addThreeFakeInvoices($user) {
        $invoice_1 = new Invoice();
        $invoice_1->setUser($user->getId());
        $invoice_1->setNumber('F00001');
        $invoice_1->setEmittedAt(new \DateTime('2017-09-14'));
        $invoice_1->setPath('F00001.pdf');

        $invoice_2 = new Invoice();
        $invoice_2->setUser($user->getId());
        $invoice_2->setNumber('F00002');
        $invoice_2->setEmittedAt(new \DateTime('2017-09-17'));
        $invoice_2->setPath('F00002.pdf');

        $invoice_3 = new Invoice();
        $invoice_3->setUser($user->getId());
        $invoice_3->setNumber('F00003');
        $invoice_3->setEmittedAt(new \DateTime('2017-09-24'));
        $invoice_3->setPath('F00003.pdf');

        $em = $this->getDoctrine()->getManager();
        $em->persist($invoice_1);
        $em->persist($invoice_2);
        $em->persist($invoice_3);
        $em->flush();
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

        $user = $permissions->getUserFromSession($session);

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
