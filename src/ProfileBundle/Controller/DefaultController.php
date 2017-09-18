<?php

namespace ProfileBundle\Controller;

use Psr\Log\LoggerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Entity\User;

class DefaultController extends Controller {

    public function indexAction(Request $request) {
        $email= 'ammana@sample.com'; // TODO get from session when there's login

        $found = $this->getDoctrine()
            ->getRepository(User::class)
            ->findByEmail($email);

        if (!$found) {
            throw $this->createNotFoundException(
                'No user found for email '.$email
            );
        }

        $user = $found[0];

        $editForm = $this->createForm('AppBundle\Form\UserType', $user);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('profile_homepage');
        }

        return $this->render('user/profile.html.twig', array(
            'user' => $user,
            'edit_form' => $editForm->createView()
        ));
    }

}
