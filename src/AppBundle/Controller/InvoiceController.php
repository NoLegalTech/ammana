<?php

/*!
 * ammana.es - job protocols generator
 * https://github.com/NoLegalTech/ammana
 * Copyright (C) 2018 Zeres Abogados y Consultores Laborales SLP <zeres@zeres.es>
 * https://github.com/NoLegalTech/ammana/blob/master/LICENSE
 */

namespace AppBundle\Controller;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use AppBundle\Entity\Invoice;
use AppBundle\Entity\NewsletterSubscriber;
use AppBundle\Entity\User;

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
    public function indexAction(Request $request, SessionInterface $session, PermissionsService $permissions)
    {
        if ($permissions->currentRolesInclude("customer")) {
            $user = $permissions->getCurrentUser($session);
            return $this->showUserInvoices($request, $user);
        }

        if ($permissions->currentRolesInclude("admin")) {
            return $this->showAllInvoices($request);
        }

        return $this->redirectToRoute('error', array(
            'message' => $this->getI18n()['errors']['restricted_access']['user']
        ));
    }

    private function showUserInvoices(Request $request, $user) {
        $invoices = $this->getDoctrine()
            ->getRepository(Invoice::class)
            ->findByUser($user->getId());

        return $this->render('invoice/index.html.twig', array(
            'title' => $this->getI18n()['invoices_page']['title'],
            'invoices' => $invoices,
            'google_analytics' => $this->getAnalyticsCode(),
            'newsletter_form' => $this->getNewsletterForm($request)->createView()
        ));
    }

    private function showAllInvoices(Request $request) {
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
            'google_analytics' => $this->getAnalyticsCode(),
            'newsletter_form' => $this->getNewsletterForm($request)->createView()
        ));
    }

    private function getI18n() {
        return $this->container->get('twig')->getGlobals()['i18n']['es'];
    }

    private function getAnalyticsCode() {
        return $this->container->hasParameter('google_analytics')
            ? $this->container->getParameter('google_analytics')
            : null;
    }

    private function getNewsletterForm(Request $request)
    {
        $subscriber = new NewsletterSubscriber();

        $form = $this->createForm('AppBundle\Form\NewsletterType', $subscriber, array(
            'i18n' => $this->getI18n()
        ));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($subscriber);

            try {
                $em->flush();
            } catch (UniqueConstraintViolationException $e) {
            }

            unset($subscriber);
            unset($form);
            $subscriber = new NewsletterSubscriber();
            $form = $this->createForm('AppBundle\Form\NewsletterType', $subscriber, array(
                'i18n' => $this->getI18n()
            ));
        }


        return $form;
    }

}
