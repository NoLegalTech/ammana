<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Invoice;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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
    public function indexAction(SessionInterface $session, PermissionsService $permissions)
    {
        if ($permissions->currentRolesInclude("customer")) {
            $user = $permissions->getCurrentUser($session);
            return $this->showUserInvoices($user);
        }

        if ($permissions->currentRolesInclude("admin")) {
            return $this->showAllInvoices();
        }

        return $this->redirectToRoute('error', array(
            'message' => $this->getI18n()['errors']['restricted_access']['user']
        ));
    }

    private function showUserInvoices($user) {
        $invoices = $this->getDoctrine()
            ->getRepository(Invoice::class)
            ->findByUser($user->getId());

        return $this->render('invoice/index.html.twig', array(
            'title' => $this->getI18n()['invoices_page']['title'],
            'invoices' => $invoices,
            'google_analytics' => $this->container->getParameter('google_analytics')
        ));
    }

    private function showAllInvoices() {
        $invoices = $this->getDoctrine()
            ->getRepository(Invoice::class)
            ->findAll();

        $users = [];
        foreach ($invoices as $invoice) {
            $users[$invoice->getUser()] = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($invoice->getUser());
        }

        return $this->render('invoice/full_list.html.twig', array(
            'title' => $this->getI18n()['invoices_page']['title'],
            'invoices' => $invoices,
            'users' => $users,
            'google_analytics' => $this->container->getParameter('google_analytics')
        ));
    }

    private function getI18n() {
        return $this->container->get('twig')->getGlobals()['i18n']['es'];
    }

}
