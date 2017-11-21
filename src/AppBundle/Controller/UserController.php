<?php

namespace AppBundle\Controller;

use Psr\Log\LoggerInterface;

use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use AppBundle\Service\PermissionsService;
use AppBundle\Service\HashGenerator;

/**
 * User controller.
 *
 */
class UserController extends Controller
{
    /**
     * Lists all user entities.
     *
     */
    public function indexAction(PermissionsService $permissions)
    {
        if (!$permissions->currentRolesInclude("admin")) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        $em = $this->getDoctrine()->getManager();

        $users = $em->getRepository('AppBundle:User')->findAll();
        $customers = [];
        foreach ($users as $user) {
            if (in_array('customer', explode(',', $user->getRoles()))) {
                $customers[]= $user;
            }
        }

        return $this->render('user/index.html.twig', array(
            'title' => $this->getI18n()['user_list_page']['title'],
            'users' => $customers,
        ));
    }

    /**
     * Displays a form to edit an existing user entity.
     *
     */
    public function editAction(Request $request, User $user, PermissionsService $permissions)
    {
        if (!$permissions->currentRolesInclude("admin")) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        $deleteForm = $this->createDeleteForm($user);
        $editForm = $this->createForm('AppBundle\Form\UserType', $user, array(
            'i18n' => $this->getI18n()
        ));
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_edit', array('id' => $user->getId()));
        }

        return $this->render('user/edit.html.twig', array(
            'title' => $this->getI18n()['edit_user_page']['title'],
            'user' => $user,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a user entity.
     *
     */
    public function deleteAction(Request $request, User $user, PermissionsService $permissions)
    {
        if (!$permissions->currentRolesInclude("admin")) {
            return $this->redirectToRoute('error', array(
                'message' => $this->getI18n()['errors']['restricted_access']['user']
            ));
        }

        $form = $this->createDeleteForm($user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();
        }

        return $this->redirectToRoute('user_index');
    }

    /**
     * Creates a form to delete a user entity.
     *
     * @param User $user The user entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(User $user)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('user_delete', array('id' => $user->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * Registers a new user.
     */
    public function registerAction(Request $request, LoggerInterface $logger, \Swift_Mailer $mailer, HashGenerator $hasher)
    {
        $user = new User();
        $form = $this->createForm('AppBundle\Form\CredentialsType', $user, array(
            'i18n' => $this->getI18n()
        ));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $user->setEnabled(false);
                $user->setRoles('customer');
                $user->setActivationHash($hasher->generate());
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                $plain_text = $this->renderView(
                    'email/activation.txt.twig',
                    array('activationHash' => $user->getActivationHash())
                );

                $logger->info('Sending welcome mail to '.$user->getEmail().' with content:');
                $logger->info($plain_text);

                $sender_email = $this->container->getParameter('emails_sender_email');
                $sender_name = $this->container->getParameter('emails_sender_name');

                $message = (new \Swift_Message($this->getI18n()['emails']['welcome']['title']))
                    ->setFrom(array($sender_email => $sender_name))
                    ->setTo($user->getEmail())
                    ->setBody(
                        $this->renderView(
                            'email/activation.html.twig',
                            array('activationHash' => $user->getActivationHash())
                        ),
                        'text/html'
                    )
                    ->addPart($plain_text, 'text/plain');

                $mailer->send($message);

                return $this->redirectToRoute('thanks_for_registering');
            } catch(\Exception $e){
                $logger->error($this->getI18n()['errors']['cannot_register_user']['log'] . ' ' . $user->getEmail());
                $logger->error($e);
                return $this->redirectToRoute('error', array(
                    'message' => $this->getI18n()['errors']['cannot_register_user']['user']
                ));
            }
        }

        return $this->render('user/register.html.twig', array(
            'title' => $this->getI18n()['register_page']['title'],
            'user' => $user,
            'form' => $form->createView(),
        ));
    }

    /**
     * Welcome page to new user.
     */
    public function welcomeAction(Request $request)
    {
        return $this->render('user/welcome.html.twig', array(
            'title' => $this->getI18n()['welcome_page']['title']
        ));
    }

    /**
     * Activates a user.
     */
    public function activateAction(User $user)
    {
        if ($user->getEnabled()) {
            return $this->redirectToRoute('activation_error');
        }
        $user->setEnabled(true);
        $this->getDoctrine()->getManager()->flush();
        return $this->render('user/activated.html.twig', array(
            'title' => $this->getI18n()['activation_page']['title'],
            'user' => $user
        ));
    }

    /**
     * Shown when activation does not succeed.
     */
    public function activateErrorAction()
    {
        return $this->render('user/activation_error.html.twig', array(
            'title' => $this->getI18n()['activation_error_page']['title']
        ));
    }

    /**
     * Login.
     */
    public function loginAction(Request $request, PermissionsService $permissions, SessionInterface $session)
    {
        $user = new User();
        $form = $this->createForm('AppBundle\Form\CredentialsType', $user, array(
            'i18n' => $this->getI18n()
        ));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $found = $this->getDoctrine()
                ->getRepository(User::class)
                ->findByEmail($user->getEmail());
            if (!$found || count($found) != 1 || $found[0]->getEnabled() == false || $found[0]->getPassword() != $user->getPassword()) {
                return $this->redirectToRoute('login_error');
            }

            $session->set('user', $found[0]->getEmail());
            $session->set('menu', $this->getMenuForRoles($found[0]->getRoles()));

            if ($permissions->currentRolesInclude("admin")) {
                return $this->redirectToRoute('user_index');
            }
            return $this->redirectToRoute('protocol_index');
        }

        return $this->render('user/login.html.twig', array(
            'title' => $this->getI18n()['login_page']['title'],
            'user' => $user,
            'form' => $form->createView(),
        ));
    }

    private function getMenuForRoles($roles)
    {
        $menu = [];
        foreach (explode(',', $roles) as $role) {
            $menu = array_merge($menu, $this->getMenuOptionsForRole($role));
        }
        return $menu;
    }

    private function getMenuOptionsForRole($role) {
        $options = array(
            'customer' => array(
                array(
                    'icon' => 'fa-user',
                    'path' => 'profile_homepage',
                    'text' => $this->getI18n()['menus']['registered_user']['profile']
                ),
                array(
                    'icon' => 'fa-files-o',
                    'path' => 'protocol_index',
                    'text' => $this->getI18n()['menus']['registered_user']['protocols']
                ),
                array(
                    'icon' => 'fa-eur',
                    'path' => 'invoice_index',
                    'text' => $this->getI18n()['menus']['registered_user']['invoices']
                ),
                array(
                    'icon' => 'fa-sign-out',
                    'path' => 'user_logout',
                    'text' => $this->getI18n()['menus']['registered_user']['logout']
                )
            ),
            "admin" => array(
                array(
                    'icon' => 'fa-user',
                    'path' => 'profile_homepage',
                    'text' => $this->getI18n()['menus']['registered_user']['profile']
                ),
                array(
                    'icon' => 'fa-user',
                    'path' => 'user_index',
                    'text' => $this->getI18n()['menus']['registered_user']['customers']
                ),
                array(
                    'icon' => 'fa-eur',
                    'path' => 'invoice_index',
                    'text' => $this->getI18n()['menus']['registered_user']['invoices']
                ),
                array(
                    'icon' => 'fa-files-o',
                    'path' => 'protocol_index',
                    'text' => $this->getI18n()['menus']['registered_user']['orders']
                ),
                array(
                    'icon' => 'fa-sign-out',
                    'path' => 'user_logout',
                    'text' => $this->getI18n()['menus']['registered_user']['logout']
                )
            )
        );
        return isset($options[$role])
            ? $options[$role]
            : [];
    }

    /**
     * Shown when login does not succeed.
     */
    public function loginErrorAction()
    {
        return $this->render('user/login_error.html.twig', array(
            'title' => $this->getI18n()['login_error_page']['title']
        ));
    }

    /**
     * Logout.
     */
    public function logoutAction(Request $request, SessionInterface $session)
    {
        $session->invalidate();
        return $this->redirectToRoute('app');
    }

    /**
     * Page to recover password.
     */
    public function forgotPasswordAction(Request $request, LoggerInterface $logger, \Swift_Mailer $mailer, HashGenerator $hasher)
    {
        $user = new User();
        $form = $this->createForm('AppBundle\Form\OnlyEmailType', $user, array(
            'i18n' => $this->getI18n()
        ));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $found = $this->getDoctrine()
                ->getRepository(User::class)
                ->findByEmail($user->getEmail());
            if (!$found || count($found) != 1 || $found[0]->getEnabled() == false) {
                $logger->error("Error trying to reset password for user ".$user->getEmail());
                $logger->error("Found user: ".print_r($found, true));
                return $this->redirectToRoute('sent_password_email');
            }
            $found_user = $found[0];
            $found_user->setActivationHash($hasher->generate());
            $this->getDoctrine()->getManager()->flush();

            $plain_text = $this->renderView(
                'email/set_password.txt.twig',
                array('activationHash' => $found_user->getActivationHash())
            );

            $logger->info('Sending "reset password" mail to '.$user->getEmail().' with content:');
            $logger->info($plain_text);

            $sender_email = $this->container->getParameter('emails_sender_email');
            $sender_name = $this->container->getParameter('emails_sender_name');

            $message = (new \Swift_Message('Establecer contraseña'))
                ->setFrom(array($sender_email => $sender_name))
                ->setTo($found_user->getEmail())
                ->setBody(
                    $this->renderView(
                        'email/set_password.html.twig',
                        array('activationHash' => $found_user->getActivationHash())
                    ),
                    'text/html'
                )
                ->addPart($plain_text, 'text/plain');

            $mailer->send($message);

            return $this->redirectToRoute('sent_password_email');
        }

        return $this->render('user/forgot_password.html.twig', array(
            'title' => $this->getI18n()['forgot_password_page']['title'],
            'user' => $user,
            'form' => $form->createView(),
        ));
    }

    /**
     * Password to set password was sent.
     */
    public function sentPasswordEmailAction(Request $request)
    {
        return $this->render('user/resetting_password.html.twig', array(
            'title' => $this->getI18n()['new_password_requested_page']['title']
        ));
    }

    /**
     * Page to set new password.
     */
    public function newPasswordAction(Request $request, User $user, HashGenerator $hasher)
    {
        $form = $this->createForm('AppBundle\Form\OnlyPasswordType', $user, array(
            'i18n' => $this->getI18n()
        ));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setActivationHash($hasher->generate());
            $this->getDoctrine()->getManager()->flush();
            return $this->render('user/password_set.html.twig', array(
                'title' => $this->getI18n()['new_password_set_page']['title']
            ));
        }

        return $this->render('user/new_password.html.twig', array(
            'title' => $this->getI18n()['new_password_page']['title'],
            'user' => $user,
            'form' => $form->createView(),
        ));
    }

    private function getI18n() {
        return $this->container->get('twig')->getGlobals()['i18n']['es'];
    }

}
