<?php

namespace AppBundle\Controller;

use Psr\Log\LoggerInterface;

use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $users = $em->getRepository('AppBundle:User')->findAll();

        return $this->render('user/index.html.twig', array(
            'users' => $users,
        ));
    }

    /**
     * Displays a form to edit an existing user entity.
     *
     */
    public function editAction(Request $request, User $user)
    {
        $deleteForm = $this->createDeleteForm($user);
        $editForm = $this->createForm('AppBundle\Form\UserType', $user);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_edit', array('id' => $user->getId()));
        }

        return $this->render('user/edit.html.twig', array(
            'user' => $user,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a user entity.
     *
     */
    public function deleteAction(Request $request, User $user)
    {
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
    public function registerAction(Request $request, LoggerInterface $logger, \Swift_Mailer $mailer)
    {
        $user = new User();
        $form = $this->createForm('AppBundle\Form\CredentialsType', $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $user->setEnabled(false);
                $user->setActivationHash($this->generateActivationHash());
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                $plain_text = $this->renderView(
                    'email/activation.txt.twig',
                    array('activationHash' => $user->getActivationHash())
                );

                $logger->info('Sending welcome mail to '.$user->getEmail().' with content:');
                $logger->info($plain_text);

                $message = (new \Swift_Message('Bienvenido a ammana.es'))
                    ->setFrom(array('ammana_pre@ammana.es' => 'Ammana'))
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
                $logger->error($e);
                return $this->redirectToRoute('error', array(
                    'message' => 'Error registrando el usuario '.$user->getEmail()
                ));
            }
        }

        return $this->render('user/register.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
        ));
    }

    public function generateActivationHash() {
        $result = "";
        $allowed_chars = array(
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
            'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
            'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
            '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0',
            '.', '-', '_', '$', '!'
        );
        for ($i = 0; $i < 100; $i++) {
            $random_index = rand(0, count($allowed_chars) - 1);
            $result .= $allowed_chars[$random_index];
        }
        return $result;
    }

    /**
     * Welcome page to new user.
     */
    public function welcomeAction(Request $request)
    {
        return $this->render('user/welcome.html.twig');
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
            'user' => $user
        ));
    }

    /**
     * Shown when activation does not succeed.
     */
    public function activateErrorAction()
    {
        return $this->render('user/activation_error.html.twig');
    }

    /**
     * Login.
     */
    public function loginAction(Request $request, LoggerInterface $logger, SessionInterface $session)
    {
        $user = new User();
        $form = $this->createForm('AppBundle\Form\CredentialsType', $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $found = $this->getDoctrine()
                ->getRepository(User::class)
                ->findByEmail($user->getEmail());
            if (!$found || count($found) != 1 || $found[0]->getEnabled() == false || $found[0]->getPassword() != $user->getPassword()) {
                return $this->redirectToRoute('login_error');
            }

            $session->set('user', $found[0]->getEmail());

            return $this->redirectToRoute('profile_homepage');
        }

        return $this->render('user/login.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
        ));
    }

    /**
     * Shown when login does not succeed.
     */
    public function loginErrorAction()
    {
        return $this->render('user/login_error.html.twig');
    }

    /**
     * Logout.
     */
    public function logoutAction(Request $request, LoggerInterface $logger, SessionInterface $session)
    {
        $session->invalidate();
        return $this->redirectToRoute('app');
    }

    /**
     * Page to recover password.
     */
    public function forgotPasswordAction(Request $request, LoggerInterface $logger, \Swift_Mailer $mailer)
    {
        $user = new User();
        $form = $this->createForm('AppBundle\Form\OnlyEmailType', $user);
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
            $found_user->setActivationHash($this->generateActivationHash());
            $this->getDoctrine()->getManager()->flush();

            $plain_text = $this->renderView(
                'email/set_password.txt.twig',
                array('activationHash' => $found_user->getActivationHash())
            );

            $logger->info('Sending "reset password" mail to '.$user->getEmail().' with content:');
            $logger->info($plain_text);

            $message = (new \Swift_Message('Establecer contraseña'))
                ->setFrom(array('ammana_pre@ammana.es' => 'Ammana'))
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
            'user' => $user,
            'form' => $form->createView(),
        ));
    }

    /**
     * Password to set password was sent.
     */
    public function sentPasswordEmailAction(Request $request)
    {
        return $this->render('user/resetting_password.html.twig');
    }

    /**
     * Page to set new password.
     */
    public function newPasswordAction(Request $request, LoggerInterface $logger, User $user)
    {
        $form = $this->createForm('AppBundle\Form\OnlyPasswordType', $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setActivationHash($this->generateActivationHash());
            $this->getDoctrine()->getManager()->flush();
            return $this->render('user/password_set.html.twig');
        }

        return $this->render('user/new_password.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
        ));
    }

}
