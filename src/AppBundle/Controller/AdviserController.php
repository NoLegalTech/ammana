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
            ->findByCreatedBy($user->getId());

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
            $to_generate []= array(
                'id' => $id,
                'name' => $protocol_spec['name']
            );
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

    /**
     * Generates a protocol
     *
     */
    public function generateProtocolAction($id, Request $request, PermissionsService $permissions, HashGenerator $hasher)
    {
        if (!$permissions->currentRolesInclude("adviser")) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        $company = new Company();

        $companyNameForm = $this->createForm('AppBundle\Form\CompanyNameType', $company, array(
            'i18n' => $this->getI18n()
        ));

        $companyNameForm->handleRequest($request);

        if ($companyNameForm->isSubmitted() && $companyNameForm->isValid()) {
            $file = $company->getLogo();
            if ($file != null) {
                $fileName = md5(uniqid()).'.'.$file->guessExtension();
                $file->move($this->get('kernel')->getRootDir(). '/../web/uploads', $fileName);
                $company->setLogo($fileName);
            }

            $this->getDoctrine()->getManager()->persist($company);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('adviser_protocol_config', array('id' => $id, 'company_id' => $company->getId()));
        }

        return $this->render('user/company_name.admin.html.twig', array(
            'title' => $this->getI18n()['company_name_admin_page']['title'],
            'company' => $company,
            'edit_form' => $companyNameForm->createView(),
            'google_analytics' => $this->getAnalyticsCode(),
            'newsletter_form' => $this->getNewsletterForm($request)->createView()
        ));
    }

    /**
     * Generates a protocol
     *
     */
    public function configProtocolAction($id, $company_id, Request $request, PermissionsService $permissions, HashGenerator $hasher)
    {
        if (!$permissions->currentRolesInclude("adviser")) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        $em = $this->getDoctrine()->getManager();

        $company = $em->getRepository('AppBundle:Company')->find($company_id);

        $protocol = $this->container->getParameter('protocol.'.$id);
        if ($protocol == null) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['missing_protocol_definition']['user']
            ));
        }
        $protocol['id'] = $id;

        $questionsForm = $this->getForm(
            $protocol['questions'],
            $request->isMethod('POST')
        );

        $questionsForm->handleRequest($request);

        if ($questionsForm->isSubmitted() && $questionsForm->isValid()) {
            if ($request->get('confirmed') == null) {
                return $this->render('adviser/protocol.confirmation.html.twig', array(
                    'title' => $this->getI18n()['adviser_protocol_confirmation_page']['title'],
                    'form' => $questionsForm->createView(),
                    'protocol' => $protocol,
                    'company' => $company,
                    'google_analytics' => $this->getAnalyticsCode(),
                    'newsletter_form' => $this->getNewsletterForm($request)->createView()
                ));
            }

            $purchasedProtocol = new Protocol();
            $purchasedProtocol->setIdentifier($id);
            $purchasedProtocol->setUser($company->getId());
            $purchasedProtocol->setCreatedBy($permissions->getCurrentUser()->getId());
            $purchasedProtocol->setEnabled(true);
            $purchasedProtocol->setOrderHash($hasher->generate(8, false));
            $purchasedProtocol->setOrderDate(new \DateTime(date('Y-m-d')));
            $purchasedProtocol->setPrice($this->container->getParameter('protocol_price'));
            $answers = [];
            foreach ($questionsForm->getData() as $key => $value) {
                $answers []= $key . '=' . $value;
            }
            $purchasedProtocol->setAnswers(implode(',', $answers));
            $em = $this->getDoctrine()->getManager();
            $em->persist($purchasedProtocol);
            $em->flush();

            return $this->redirectToRoute('adviser_protocol_index');
        }

        return $this->render('protocol/questions.admin.html.twig', array(
            'title' => $this->getI18n()['questions_admin_page']['title'],
            'form' => $questionsForm->createView(),
            'protocol' => $protocol,
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

    private function getForm($questions, $isConfirmation) {
        $formBuilder = $this->createFormBuilder();
        $counter_questions = 1;
        $subcounter_questions = 1;
        foreach ($questions as $question) {
            $choices = array();
            $counter_answers = 0;
            foreach ($question['answers'] as $answer) {
                $choices[$answer] = $counter_answers++;
            }
            $properties = array(
                'label' => $counter_questions .') ' . $question['question'],
                'choices' => $choices,
                'expanded' => !$isConfirmation,
                'multiple' => false
            );
            if (isset($question['condition'])) {
                $properties['label'] = ($counter_questions - 1) . '.' . $subcounter_questions .') ' . $question['question'];
                $properties['attr'] = array(
                    'class' => 'has-condition',
                    'data-condition' => $question['condition']
                );
                $properties['label_attr'] = array(
                    'class' => 'has-condition',
                    'data-condition' => $question['condition']
                );
                $subcounter_questions++;
            } else {
                $counter_questions++;
                $subcounter_questions = 1;
            }
            $formBuilder->add($question['id'], ChoiceType::class, $properties);
        }

        return $formBuilder ->getForm();
    }

}
