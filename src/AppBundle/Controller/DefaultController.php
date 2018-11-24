<?php

/*!
 * ammana.es - job protocols generator
 * https://github.com/NoLegalTech/ammana
 * Copyright (C) 2018 Zeres Abogados y Consultores Laborales SLP <zeres@zeres.es>
 * https://github.com/NoLegalTech/ammana/blob/master/LICENSE
 */

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

use AppBundle\Entity\ContactMessage;
use AppBundle\Entity\NewsletterSubscriber;

class DefaultController extends Controller
{
    public function indexAction(Request $request, \Swift_Mailer $mailer)
    {
        $contactForm = $this->getContactForm($request, $mailer);

        $newsletterForm = $this->getNewsletterForm($request);

        return $this->render('default/index.html.twig', [
            'title' => $this->getI18n()['home_page']['claim']['title'],
            'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
            'google_analytics' => $this->getAnalyticsCode(),
            'contact_form' => $contactForm->createView(),
            'newsletter_form' => $newsletterForm->createView()
        ]);
    }

    public function maintenanceAction(Request $request)
    {
        return $this->render('default/maintenance.html.twig', [
            'google_analytics' => $this->getAnalyticsCode()
        ]);
    }

    public function errorAction(Request $request)
    {
        return $this->render('default/error.html.twig', [
            'title' => $this->getI18n()['error_page']['error_label'],
            'message' => $request->query->get('message'),
            'technical_info' => $request->query->get('technical_info', null),
            'google_analytics' => $this->getAnalyticsCode()
        ]);
    }

    public function legalAction(Request $request)
    {
        return $this->render('default/legal.html.twig', [
            'title' => $this->getI18n()['legal_page']['title'],
            'google_analytics' => $this->getAnalyticsCode()
        ]);
    }

    public function privacyAction(Request $request)
    {
        return $this->render('default/privacy.html.twig', [
            'title' => $this->getI18n()['privacy_page']['title'],
            'google_analytics' => $this->getAnalyticsCode()
        ]);
    }

    public function cookiesAction(Request $request)
    {
        return $this->render('default/cookies.html.twig', [
            'title' => $this->getI18n()['cookies_page']['title'],
            'google_analytics' => $this->getAnalyticsCode()
        ]);
    }

    public function redesAction(Request $request)
    {
        $tax = 0.21;
        return $this->render('default/redes.html.twig', [
            'title' => $this->getI18n()['redes_page']['title'],
            'google_analytics' => $this->getAnalyticsCode(),
            'price' => $this->container->getParameter('protocol_price') / (100 * (1 + $tax))
        ]);
    }

    public function mensajeriasAction(Request $request)
    {
        $tax = 0.21;
        return $this->render('default/mensajerias.html.twig', [
            'title' => $this->getI18n()['mensajerias_page']['title'],
            'google_analytics' => $this->getAnalyticsCode(),
            'price' => $this->container->getParameter('protocol_price') / (100 * (1 + $tax))
        ]);
    }

    public function equiposAction(Request $request)
    {
        $tax = 0.21;
        return $this->render('default/equipos.html.twig', [
            'title' => $this->getI18n()['equipos_page']['title'],
            'google_analytics' => $this->getAnalyticsCode(),
            'price' => $this->container->getParameter('protocol_price') / (100 * (1 + $tax))
        ]);
    }

    public function iconsAction(Request $request)
    {
        return $this->render('default/icons.html.twig', array(
            'title' => 'Iconos',
            'google_analytics' => $this->getAnalyticsCode()
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

    private function getContactForm(Request $request, \Swift_Mailer $mailer)
    {
        $contactMessage = new ContactMessage();

        $form = $this->createForm('AppBundle\Form\ContactType', $contactMessage, array(
            'i18n' => $this->getI18n()
        ));
        $form->handleRequest($request);

        $sender_email = $this->container->getParameter('emails_sender_email');
        $sender_name = $this->container->getParameter('emails_sender_name');
        $contactEmail = $this->container->getParameter('contact_email');

        if ($form->isSubmitted() && $form->isValid()) {
            $message = (new \Swift_Message($this->getI18n()['emails']['contact']['title']))
                ->setFrom(array($sender_email => $sender_name))
                ->setTo($contactEmail)
                ->setBody(
                    $this->renderView(
                        'email/contact.html.twig',
                        array('data' => $contactMessage)
                    ),
                    'text/html'
                )
                ->addPart(
                    $this->renderView(
                        'email/contact.txt.twig',
                        array('data' => $contactMessage)
                    ),
                    'text/plain'
                );

            $mailer->send($message);

            unset($contactMessage);
            unset($form);
            $contactMessage = new ContactMessage();
            $form = $this->createForm('AppBundle\Form\ContactType', $contactMessage, array(
                'i18n' => $this->getI18n()
            ));
        }


        return $form;
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
