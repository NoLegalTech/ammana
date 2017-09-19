<?php

namespace AppBundle\Controller;

use Psr\Log\LoggerInterface;

use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

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
     * Creates a new user entity.
     *
     */
    public function newAction(Request $request)
    {
        $user = new User();
        $form = $this->createForm('AppBundle\Form\UserType', $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('user_show', array('id' => $user->getId()));
        }

        return $this->render('user/new.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a user entity.
     *
     */
    public function showAction(User $user)
    {
        $deleteForm = $this->createDeleteForm($user);

        return $this->render('user/show.html.twig', array(
            'user' => $user,
            'delete_form' => $deleteForm->createView(),
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
        $form = $this->createForm('AppBundle\Form\Register', $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $user->setEnabled(false);
                $user->setActivationHash($this->generateActivationHash());
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                $message = (new \Swift_Message('Bienvenido'))
                    ->setFrom('ammana_pre@ammana.es')
                    ->setTo($user->getEmail())
                    ->setBody(
                        $this->renderView(
                            // app/Resources/views/emails/activation.html.twig
                            'email/activation.html.twig',
                            array('hash' => $user->getActivationHash())
                        ),
                        'text/html'
                    )
                    ->addPart(
                        $this->renderView(
                            'email/activation.txt.twig',
                            array('hash' => $user->getActivationHash())
                        ),
                        'text/plain'
                    );

                $mailer->send($message);

                return $this->redirectToRoute('thanks_for_registering');
            } catch(\Exception $e){
                throw $e; // TODO don't rethrow
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
            '.', '-', '_', '$', '#', '!'
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

}
