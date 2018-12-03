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
 * Protocol controller.
 *
 */
class ProtocolController extends Controller
{

    /**
     * Lists all protocol entities of the current user.
     *
     */
    public function indexAction(Request $request, PermissionsService $permissions, Invoices $invoices)
    {
        if (!$permissions->currentRolesInclude("customer")) {
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
        $to_buy = [];
        $protocols_specs = $this->container->getParameter('protocols');
        foreach ($protocols_specs as $id) {
            $protocol_spec = $this->container->getParameter('protocol.'.$id);
            $names[$id] = $protocol_spec['name'];
            if (!in_array($id, $already_ordered_ids)) {
                $to_buy []= array(
                    'id' => $id,
                    'name' => $protocol_spec['name']
                );
            }
        }

        return $this->render('protocol/index.html.twig', array(
            'title' => $this->getI18n()['protocols_page']['title'],
            'protocols' => $protocols,
            'invoices' => $invoices->getInvoicesForProtocols($protocols),
            'names' => $names,
            'to_buy' => $to_buy,
            'google_analytics' => $this->getAnalyticsCode(),
            'newsletter_form' => $this->getNewsletterForm($request)->createView()
        ));
    }

    public function ordersAction(Request $request, PermissionsService $permissions)
    {
        if (!$permissions->currentRolesInclude("admin")) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        $protocols = $this->getDoctrine()
            ->getRepository(Protocol::class)
            ->findByEnabled(false);

        $names = [];
        $protocols_specs = $this->container->getParameter('protocols');
        foreach ($protocols_specs as $id) {
            $protocol_spec = $this->container->getParameter('protocol.'.$id);
            $names[$id] = $protocol_spec['name'];
        }

        $users = [];
        foreach ($protocols as $protocol) {
            $users[$protocol->getUser()] = $this->getDoctrine()
                ->getRepository(User::class)
                ->find($protocol->getUser());
        }

        return $this->render('protocol/full_list.html.twig', array(
            'title' => $this->getI18n()['orders_page']['title'],
            'protocols' => $protocols,
            'names' => $names,
            'users' => $users,
            'google_analytics' => $this->getAnalyticsCode(),
            'newsletter_form' => $this->getNewsletterForm($request)->createView()
        ));
    }

    public function indexAdminAction(Request $request, PermissionsService $permissions, Invoices $invoices)
    {
        if (!$permissions->currentRolesInclude("admin")) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        $user = $permissions->getCurrentUser();

        $protocols = $this->getDoctrine()
            ->getRepository(Protocol::class)
            ->findByCreatedBy($user->getId());

        $company_names = [];
        foreach($protocols as $protocol) {
            $company_names[$protocol->getId()] = $this->getDoctrine()
                ->getRepository(Company::class)
                ->find($protocol->getUser())
                ->getCompanyName();
        }

        $names = [];
        $to_buy = [];
        $protocols_specs = $this->container->getParameter('protocols');
        foreach ($protocols_specs as $id) {
            $protocol_spec = $this->container->getParameter('protocol.'.$id);
            $names[$id] = $protocol_spec['name'];
            $to_buy []= array(
                'id' => $id,
                'name' => $protocol_spec['name']
            );
        }

        return $this->render('protocol/index.admin.html.twig', array(
            'title' => $this->getI18n()['protocols_page']['title'],
            'protocols' => $protocols,
            'invoices' => $invoices->getInvoicesForProtocols($protocols),
            'names' => $names,
            'company_names' => $company_names,
            'to_buy' => $to_buy,
            'google_analytics' => $this->getAnalyticsCode(),
            'newsletter_form' => $this->getNewsletterForm($request)->createView()
        ));
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
    public function buyAction($id, Request $request, PermissionsService $permissions, HashGenerator $hasher)
    {
        if (!$permissions->currentRolesInclude("customer")) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        $user = $permissions->getCurrentUser();

        $protocol = $this->container->getParameter('protocol.'.$id);
        if ($protocol == null) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['missing_protocol_definition']['user']
            ));
        }
        $protocol['id'] = $id;

        $profile_completed = $this->userHasCompletedProfile($user);

        $questionsForm = $this->getForm(
            $protocol['questions'],
            $request->isMethod('POST')
        );

        $questionsForm->handleRequest($request);

        if ($questionsForm->isSubmitted() && $questionsForm->isValid()) {
            if ($request->get('confirmed') == null) {
                return $this->render('protocol/confirmation.html.twig', array(
                    'title' => $this->getI18n()['order_confirmation_page']['title'],
                    'profile_completed' => $profile_completed,
                    'form' => $questionsForm->createView(),
                    'protocol' => $protocol,
                    'google_analytics' => $this->getAnalyticsCode(),
                    'newsletter_form' => $this->getNewsletterForm($request)->createView()
                ));
            }

            $purchasedProtocol = new Protocol();
            $purchasedProtocol->setIdentifier($id);
            $purchasedProtocol->setUser($user->getId());
            $purchasedProtocol->setCreatedBy($user->getId());
            $purchasedProtocol->setEnabled(false);
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

            return $this->redirectToRoute('protocol_pay', array(
                'id' => $purchasedProtocol->getId()
            ));
        }

        return $this->render('protocol/questions.html.twig', array(
            'title' => $this->getI18n()['questions_page']['title'],
            'profile_completed' => $profile_completed,
            'form' => $questionsForm->createView(),
            'protocol' => $protocol,
            'google_analytics' => $this->getAnalyticsCode(),
            'newsletter_form' => $this->getNewsletterForm($request)->createView()
        ));
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

    /**
     * Creates a protocol (admin).
     *
     */
    public function createAction($id, Request $request, PermissionsService $permissions, HashGenerator $hasher)
    {
        if (!$permissions->currentRolesInclude("admin")) {
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

            return $this->redirectToRoute('protocol_config', array('id' => $id, 'company_id' => $company->getId()));
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
     * Creates a protocol (admin).
     *
     */
    public function configAction($id, $company_id, Request $request, PermissionsService $permissions, HashGenerator $hasher)
    {
        if (!$permissions->currentRolesInclude("admin")) {
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
                return $this->render('protocol/confirmation.admin.html.twig', array(
                    'title' => $this->getI18n()['order_confirmation_admin_page']['title'],
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

            return $this->redirectToRoute('protocol_admin_index');
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
     * Downloads a protocol.
     *
     */
    public function downloadAction(Protocol $protocol, PDFPrinter $printer, Request $request, PermissionsService $permissions)
    {
        if (!$permissions->currentRolesInclude("customer")) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        $user = $permissions->getCurrentUser();

        if ($protocol->getUser() != $user->getId()) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        $protocol_spec = $this->container->getParameter('protocol.'.$protocol->getIdentifier());
        if ($protocol_spec == null) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['missing_protocol_definition']['user']
            ));
        }

        if (!isset($protocol_spec['document'])) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['wrong_protocol_definition']['user']
            ));
        }

        $document = $protocol_spec['document'];
        $printer->setFileName($protocol_spec['name'].'.pdf');

        if ($user->getLogo() != null) {
            $printer->setLogo($this->get('kernel')->getRootDir() . '/../web/uploads/' . $user->getLogo());
        }

        $variables = [];
        $asignments = explode(',', $protocol->getAnswers());
        foreach ($asignments as $asignment) {
            list($var, $val) = explode('=', $asignment);
            $variables[$var] = $val;
        }
        $variables['company_name'] = $user->getCompanyName();
        $printer->setVariables($variables);
        $printer->setQuestions($protocol_spec['questions']);

        $printer->setStyles($document['styles']);
        $printer->setContent($document['content']);

        return new Response($printer->print(), 200, array( 'Content-Type' => 'application/pdf'));
    }

    /**
     * Downloads a protocol's instructions.
     *
     */
    public function downloadInstructionsAction(Protocol $protocol, Request $request, PermissionsService $permissions)
    {
        if (!$permissions->currentRolesInclude("customer")) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        $user = $permissions->getCurrentUser();

        if ($protocol->getUser() != $user->getId()) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        $protocol_spec = $this->container->getParameter('protocol.'.$protocol->getIdentifier());
        if ($protocol_spec == null) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['missing_protocol_definition']['user']
            ));
        }

        if (!isset($protocol_spec['document'])) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['wrong_protocol_definition']['user']
            ));
        }

        $path_to_document = $this->get('kernel')->getRootDir() . '/../app/Resources/downloads/instrucciones_' . $protocol->getIdentifier() . '.pdf';
        $fileName = $this->getI18n()['protocols']['instructions'][$protocol->getIdentifier()];

        return new Response(
            file_get_contents($path_to_document),
            200,
            array(
                'Content-Type' => 'mime/type',
                'Content-Disposition' => 'attachment;filename="'.$fileName.".pdf"
            )
        );
    }

    /**
     * Downloads a protocol's recibi.
     *
     */
    public function downloadRecibiAction(Protocol $protocol, PDFPrinter $printer, Request $request, PermissionsService $permissions)
    {
        if (!$permissions->currentRolesInclude("customer")) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        $user = $permissions->getCurrentUser();

        if ($protocol->getUser() != $user->getId()) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        $protocol_spec = $this->container->getParameter('protocol.'.$protocol->getIdentifier());
        if ($protocol_spec == null) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['missing_protocol_definition']['user']
            ));
        }

        if (!isset($protocol_spec['document'])) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['wrong_protocol_definition']['user']
            ));
        }

        $path_to_document = $this->get('kernel')->getRootDir() . '/../app/Resources/downloads/recibi_' . $protocol->getIdentifier() . '.docx';
        $fileName = $this->getI18n()['protocols']['recibi'][$protocol->getIdentifier()];

        return new Response(
            file_get_contents($path_to_document),
            200,
            array(
                'Content-Type' => 'mime/type',
                'Content-Disposition' => 'attachment;filename="'.$fileName.".docx"
            )
        );
    }

    /**
     * Downloads a protocol.
     *
     */
    public function downloadAdminAction(Protocol $protocol, PDFPrinter $printer, Request $request, PermissionsService $permissions)
    {
        if (!$permissions->currentRolesInclude("admin")) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        $em = $this->getDoctrine()->getManager();

        $company = $em->getRepository('AppBundle:Company')->find($protocol->getUser());

        $protocol_spec = $this->container->getParameter('protocol.'.$protocol->getIdentifier());
        if ($protocol_spec == null) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['missing_protocol_definition']['user']
            ));
        }

        if (!isset($protocol_spec['document'])) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['wrong_protocol_definition']['user']
            ));
        }

        $document = $protocol_spec['document'];
        $printer->setFileName($protocol_spec['name'].'.pdf');

        if ($company->getLogo() != null) {
            $printer->setLogo($this->get('kernel')->getRootDir() . '/../web/uploads/' . $company->getLogo());
        }

        $variables = [];
        $asignments = explode(',', $protocol->getAnswers());
        foreach ($asignments as $asignment) {
            list($var, $val) = explode('=', $asignment);
            $variables[$var] = $val;
        }
        $variables['company_name'] = $company->getCompanyName();
        $printer->setVariables($variables);
        $printer->setQuestions($protocol_spec['questions']);

        $printer->setStyles($document['styles']);
        $printer->setContent($document['content']);

        return new Response($printer->print(), 200, array( 'Content-Type' => 'application/pdf'));
    }

    /**
     * Downloads a protocol's instructions.
     *
     */
    public function downloadInstructionsAdminAction(Protocol $protocol, Request $request, PermissionsService $permissions)
    {
        if (!$permissions->currentRolesInclude("admin")) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        $protocol_spec = $this->container->getParameter('protocol.'.$protocol->getIdentifier());
        if ($protocol_spec == null) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['missing_protocol_definition']['user']
            ));
        }

        if (!isset($protocol_spec['document'])) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['wrong_protocol_definition']['user']
            ));
        }

        $path_to_document = $this->get('kernel')->getRootDir() . '/../app/Resources/downloads/instrucciones_' . $protocol->getIdentifier() . '.pdf';
        $fileName = $this->getI18n()['protocols']['instructions'][$protocol->getIdentifier()];

        return new Response(
            file_get_contents($path_to_document),
            200,
            array(
                'Content-Type' => 'mime/type',
                'Content-Disposition' => 'attachment;filename="'.$fileName.".pdf"
            )
        );
    }

    /**
     * Downloads a protocol's recibi.
     *
     */
    public function downloadRecibiAdminAction(Protocol $protocol, PDFPrinter $printer, Request $request, PermissionsService $permissions)
    {
        if (!$permissions->currentRolesInclude("admin")) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        $protocol_spec = $this->container->getParameter('protocol.'.$protocol->getIdentifier());
        if ($protocol_spec == null) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['missing_protocol_definition']['user']
            ));
        }

        if (!isset($protocol_spec['document'])) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['wrong_protocol_definition']['user']
            ));
        }

        $path_to_document = $this->get('kernel')->getRootDir() . '/../app/Resources/downloads/recibi_' . $protocol->getIdentifier() . '.docx';
        $fileName = $this->getI18n()['protocols']['recibi'][$protocol->getIdentifier()];

        return new Response(
            file_get_contents($path_to_document),
            200,
            array(
                'Content-Type' => 'mime/type',
                'Content-Disposition' => 'attachment;filename="'.$fileName.".docx"
            )
        );
    }

    /**
     * Shows a protocol in HTML.
     *
     */
    public function htmlAdminAction(Protocol $protocol, Request $request, PermissionsService $permissions)
    {
        if (!$permissions->currentRolesInclude("admin")) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        $em = $this->getDoctrine()->getManager();

        $company = $em->getRepository('AppBundle:Company')->find($protocol->getUser());

        $protocol_spec = $this->container->getParameter('protocol.'.$protocol->getIdentifier());
        if ($protocol_spec == null) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['missing_protocol_definition']['user']
            ));
        }

        if (!isset($protocol_spec['document'])) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['wrong_protocol_definition']['user']
            ));
        }

        $document = $protocol_spec['document'];

        $with_logo = false;
        $logo_url = '';
        if ($company->getLogo() != null) {
            $with_logo = true;
            $logo_url = '/web/uploads/' . $company->getLogo();
        }

        $variables = [];
        $asignments = explode(',', $protocol->getAnswers());
        foreach ($asignments as $asignment) {
            list($var, $val) = explode('=', $asignment);
            $variables[$var] = $val;
        }
        $variables['company_name'] = $company->getCompanyName();

        return $this->render('protocol/show.admin.html.twig', array(
            'title' => $protocol_spec['short_name'] ." " . $company->getCompanyName(),
            'protocol' => $protocol,
            'variables' => $variables,
            'questions' => $protocol_spec['questions'],
            'styles' => $document['styles'],
            'content' => $document['content'],
            'with_logo' => $with_logo,
            'logo_url' => $logo_url,
            'google_analytics' => $this->getAnalyticsCode(),
            'newsletter_form' => $this->getNewsletterForm($request)->createView()
        ));
    }

    /**
     * Deletes a protocol.
     *
     */
    public function deleteAdminAction(Protocol $protocol, Request $request, PermissionsService $permissions)
    {
        if (!$permissions->currentRolesInclude("admin")) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        $em = $this->getDoctrine()->getManager();

        $em->remove($protocol);
        $em->flush();

        return $this->redirectToRoute('protocol_admin_index');
    }

    /**
     * Pays a protocol.
     *
     */
    public function payAction(Protocol $protocol, Request $request, Quaderno $quaderno, \Swift_Mailer $mailer, PermissionsService $permissions, OrderNumberFormatter $formatter, Invoices $invoices, AlertsService $alerts)
    {
        if (!$permissions->currentRolesInclude("customer")) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        $user = $permissions->getCurrentUser();
        if ($protocol->getUser() != $user->getId()) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        if ($protocol->getEnabled()) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['already_paid_protocol']['user']
            ));
        }

        if ($request->query->get('quaderno_error_message') != null) {
            $alerts->error(
                $this->getI18n()['errors']['quaderno_paypal_error']['log'],
                $request->query->get('quaderno_error_message'),
                $protocol->__toString()
            );
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['quaderno_paypal_error']['user']
            ));
        }

        $protocol_spec = $this->container->getParameter('protocol.'.$protocol->getIdentifier());
        if ($protocol_spec == null) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['missing_protocol_definition']['user']
            ));
        }

        if ($request->isMethod('POST')) {
            $postData = $request->request;
            $payer_status = $postData->get('payer_status');
            $item_number = $postData->get('item_number');

            if ($payer_status != 'VERIFIED' || $formatter->format($protocol->getId()) != $item_number) {
                $alerts->error(
                    $this->getI18n()['errors']['wrong_paypal_callback']['log'],
                    $request->query->get('quaderno_error_message'),
                    $protocol->__toString()
                );
                return $this->redirectToRoute('error', array(
                    'message' => $this->getI18n()['errors']['wrong_paypal_callback']['user']
                ));
            }
            $protocol->setEnabled(true);
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('protocol_paid');
        }

        $amount = $protocol->getPrice();
        $token = array(
            "iat" => time(),
            "amount" => $amount,
            "currency" => "EUR",
            "description" => $protocol_spec['name'],
            "item_number" => $formatter->format($protocol->getId()),
            "quantity" => 1
        );
        $jwt = JWT::encode($token, $this->container->getParameter('quaderno_api_key'));

        $vatnumber = $user->getCif();
        if (!$quaderno->isValidVAT($vatnumber)) {
            $vatnumber = "";
        }

        return $this->render('protocol/payment.html.twig', array(
            'title' => $this->getI18n()['payment_page']['title'],
            'user' => $user,
            'vatnumber' => $vatnumber,
            'amount' => $amount,
            'protocol_spec' => $protocol_spec,
            'charge' => $jwt,
            'quaderno_public_api_key' => $this->container->getParameter('quaderno_api_public_key'),
            'payment_data' => array(
                'order_hash' => $protocol->getOrderHash(),
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

    /**
     * Shows a payment completion status page.
     *
     */
    public function paymentCompleteAction(Request $request)
    {
        return $this->render('protocol/payment_complete.html.twig', array(
            'title' => $this->getI18n()['payment_complete_page']['title'],
            'google_analytics' => $this->getAnalyticsCode(),
            'newsletter_form' => $this->getNewsletterForm($request)->createView()
        ));
    }

    /**
     * Marks a protocol as paid by transfer.
     *
     */
    public function payTransferAction(Protocol $protocol, PermissionsService $permissions, Quaderno $quaderno, Protocols $protocols)
    {
        if (!$permissions->currentRolesInclude("admin")) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        if ($protocol->getEnabled()) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['already_paid_protocol']['user']
            ));
        }

        $theUser = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($protocol->getUser());

        $theInvoice = $quaderno->createInvoice($theUser, $protocol);

        if ($theInvoice == null) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['quaderno_invoice_not_created']['user'],
                'technical_info' => $quaderno->popErrors()
            ));
        }

        $this->getDoctrine()->getManager()->persist($theInvoice);
        $protocol->setEnabled(true);
        $protocol->setInvoice($theInvoice->getId());
        $this->getDoctrine()->getManager()->flush();

        $quaderno->sendToClient($theInvoice);

        return $this->redirectToRoute('protocol_index');
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
