<?php

namespace AppBundle\Controller;

use Psr\Log\LoggerInterface;

use AppBundle\Entity\Invoice;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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
    public function indexAction(LoggerInterface $logger, SessionInterface $session)
    {
        $user = $this->getUserFromSession($session);
        if ($user == null) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        $invoices = $this->getDoctrine()
            ->getRepository(Invoice::class)
            ->findByUser($user->getId());

        $logger->info('INVOICES ARE: '.print_r($invoices, true));

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
        $invoice_1->setPath('');

        $invoice_2 = new Invoice();
        $invoice_2->setUser($user->getId());
        $invoice_2->setNumber('F00002');
        $invoice_2->setEmittedAt(new \DateTime('2017-09-17'));
        $invoice_2->setPath('');

        $invoice_3 = new Invoice();
        $invoice_3->setUser($user->getId());
        $invoice_3->setNumber('F00003');
        $invoice_3->setEmittedAt(new \DateTime('2017-09-24'));
        $invoice_3->setPath('');

        $em = $this->getDoctrine()->getManager();
        $em->persist($invoice_1);
        $em->persist($invoice_2);
        $em->persist($invoice_3);
        $em->flush();
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

    /**
     * Downloads an invoice.
     *
     */
    public function downloadAction(Invoice $invoice, LoggerInterface $logger, SessionInterface $session)
    {
        $user = $this->getUserFromSession($session);
        if ($user == null) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }


        if ($invoice->getUser() != $user->getId()) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        return $this->redirectToRoute('error', array(
            'message' => 'Todavía no implementada la descarga, pero hasta aquí correcto.'
        ));
    }
}
