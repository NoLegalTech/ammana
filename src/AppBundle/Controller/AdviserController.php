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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use \Firebase\JWT\JWT;

use AppBundle\Entity\Company;
use AppBundle\Entity\Protocol;
use AppBundle\Entity\ContactMessage;
use AppBundle\Entity\NewsletterSubscriber;
use AppBundle\Entity\User;
use AppBundle\Service\HashGenerator;
use AppBundle\Service\Invoices;
use AppBundle\Service\PDFPrinter;
use AppBundle\Service\PermissionsService;
use AppBundle\Service\OrderNumberFormatter;
use AppBundle\Service\Quaderno;
use AppBundle\Service\Protocols;
use AppBundle\Service\AlertsService;

/**
 * Adviser controller.
 *
 */
class AdviserController extends Controller
{

    /**
     * Lists all protocol entities of the current adviser.
     *
     */
    public function protocolsAction(Request $request, PermissionsService $permissions, Invoices $invoices)
    {
        if (!$permissions->currentRolesInclude("adviser")) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        $user = $permissions->getCurrentUser();

        $protocols = $this->getDoctrine()
            ->getRepository(Protocol::class)
            ->findByUser($user->getId());

        $already_ordered_ids = [];
        foreach ($protocols as $protocol) {
            $already_ordered_ids []= $protocol->getIdentifier();
        }

        $names = [];
        $to_generate = [];
        $protocols_specs = $this->container->getParameter('protocols');
        foreach ($protocols_specs as $id) {
            $protocol_spec = $this->container->getParameter('protocol.'.$id);
            $names[$id] = $protocol_spec['name'];
            if (!in_array($id, $already_ordered_ids)) {
                $to_generate []= array(
                    'id' => $id,
                    'name' => $protocol_spec['name']
                );
            }
        }

        return $this->render('adviser/protocols.html.twig', array(
            'title' => $this->getI18n()['protocols_page']['title'],
            'protocols' => $protocols,
            'invoices' => $invoices->getInvoicesForProtocols($protocols),
            'names' => $names,
            'to_generate' => $to_generate,
            'credits' => $user->getCredits(),
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
