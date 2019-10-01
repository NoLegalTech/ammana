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

use AppBundle\Entity\AdviserPack;
use AppBundle\Entity\Company;
use AppBundle\Entity\ContactMessage;
use AppBundle\Entity\NewsletterSubscriber;
use AppBundle\Entity\Pack;
use AppBundle\Entity\Protocol;
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

            $user = $permissions->getCurrentUser();
            $user->setCredits($user->getCredits() - 1);
            $em->persist($user);
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

    /**
     * Show orders of adviser.
     */
    public function ordersAction($id, Request $request, PermissionsService $permissions)
    {
        if (!$permissions->currentRolesInclude("admin")) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        $adviser = $this->getDoctrine()
            ->getRepository(User::class)
            ->findById($id)[0];

        $protocols = $this->getDoctrine()
            ->getRepository(Protocol::class)
            ->findByUser($id);

        $names = [];
        $protocols_specs = $this->container->getParameter('protocols');
        foreach ($protocols_specs as $id) {
            $protocol_spec = $this->container->getParameter('protocol.'.$id);
            $names[$id] = $protocol_spec['name'];
        }

        return $this->render('adviser/orders.html.twig', array(
            'title' => $this->getI18n()['orders_page']['title'],
            'protocols' => $protocols,
            'names' => $names,
            'adviser' => $adviser,
            'google_analytics' => $this->getAnalyticsCode(),
            'newsletter_form' => $this->getNewsletterForm($request)->createView()
        ));
    }

    /**
     * Buys a pack.
     *
     */
    public function buyPackAction(Request $request, PermissionsService $permissions, HashGenerator $hasher)
    {
        if (!$permissions->currentRolesInclude("adviser")) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        $adviser = $permissions->getCurrentUser();

        $profile_completed = $this->adviserHasCompletedProfile($adviser);

        $adviserPack = new AdviserPack();

        $form = $this->createForm('AppBundle\Form\AdviserPackType', $adviserPack, array(
            'i18n' => $this->getI18n()
        ));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $purchasedPack = new Pack();
            $purchasedPack->setEnabled(false);
            $purchasedPack->setUser($adviser->getId());
            $purchasedPack->setOrderHash($hasher->generate(8, false));
            $purchasedPack->setOrderDate(new \DateTime(date('Y-m-d')));
            $purchasedPack->setPrice($adviserPack->getPrice());
            $purchasedPack->setAmount($adviserPack->getAmount());

            $em = $this->getDoctrine()->getManager();
            $em->persist($purchasedPack);
            $em->flush();

            return $this->redirectToRoute('adviser_pay_pack', array(
                'id' => $purchasedPack->getId()
            ));
        }

        return $this->render('adviser/buy_pack.html.twig', array(
            'title' => $this->getI18n()['buy_pack_page']['title'],
            'profile_completed' => $profile_completed,
            'form' => $form->createView(),
            'google_analytics' => $this->getAnalyticsCode(),
            'newsletter_form' => $this->getNewsletterForm($request)->createView()
        ));
    }

    /**
     * Pays a pack.
     *
     */
    public function payPackAction(Pack $pack, Request $request, Quaderno $quaderno, \Swift_Mailer $mailer, PermissionsService $permissions, OrderNumberFormatter $formatter, Invoices $invoices, AlertsService $alerts)
    {
        if (!$permissions->currentRolesInclude("adviser")) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        $adviser = $permissions->getCurrentUser();
        if ($pack->getUser() != $adviser->getId()) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        if ($pack->getEnabled()) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['already_paid_pack']['user']
            ));
        }

        if ($request->query->get('quaderno_error_message') != null) {
            $alerts->error(
                $this->getI18n()['errors']['quaderno_paypal_error']['log'],
                $request->query->get('quaderno_error_message'),
                $pack->__toString()
            );
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['quaderno_paypal_error']['user']
            ));
        }

        if ($request->isMethod('POST')) {
            $postData = $request->request;
            $payer_status = $postData->get('payer_status');
            $item_number = $postData->get('item_number');

            if ($payer_status != 'VERIFIED' || $formatter->format($pack->getId()) != $item_number) {
                $alerts->error(
                    $this->getI18n()['errors']['wrong_paypal_callback']['log'],
                    $request->query->get('quaderno_error_message'),
                    $pack->__toString()
                );
                return $this->redirectToRoute('error', array(
                    'message' => $this->getI18n()['errors']['wrong_paypal_callback']['user']
                ));
            }
            $pack->setEnabled(true);
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('adviser_pack_paid');
        }

        $amount = $pack->getPrice();
        $token = array(
            "iat" => time(),
            "amount" => $amount,
            "currency" => "EUR",
            "description" => "Pack de " . $pack->getAmount() . " protocolos",
            "item_number" => $formatter->format($pack->getId()),
            "quantity" => 1
        );
        $jwt = JWT::encode($token, $this->container->getParameter('quaderno_api_key'));

        $vatnumber = $adviser->getCif();
        if (!$quaderno->isValidVAT($vatnumber)) {
            $vatnumber = "";
        }

        return $this->render('adviser/pack.payment.html.twig', array(
            'title' => $this->getI18n()['pack_payment_page']['title'],
            'adviser' => $adviser,
            'vatnumber' => $vatnumber,
            'amount' => $amount,
            'charge' => $jwt,
            'quaderno_public_api_key' => $this->container->getParameter('quaderno_api_public_key'),
            'payment_data' => array(
                'order_hash' => $pack->getOrderHash(),
                'bank_account' => $this->container->getParameter('account_number'),
                'amount' => $this->formatEuro($amount)
            ),
            'google_analytics' => $this->getAnalyticsCode(),
            'newsletter_form' => $this->getNewsletterForm($request)->createView()
        ));
    }

    private function formatEuro($amount) {
        $amount = "$amount";
        return substr($amount, 0, strlen($amount) - 2) . '.' . substr($amount, -2);
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

    private function adviserHasCompletedProfile($adviser) {
        return ( $adviser->getEmail() != null )
            && ( $adviser->getPassword() != null )
            && ( $adviser->getCompanyName() != null )
            && ( $adviser->getCif() != null )
            && ( $adviser->getAddress() != null );
    }

}
