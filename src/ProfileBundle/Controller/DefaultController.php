<?php

namespace ProfileBundle\Controller;

use Psr\Log\LoggerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use AppBundle\Entity\User;

class DefaultController extends Controller
{
    public function indexAction() {
        $email= 'ammana@sample.com'; // TODO get from session when there's login

        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->findByEmail($email);

        if (!$user) {
            throw $this->createNotFoundException(
                'No user found for email '.$email
            );
        }

        return $this->render('user/profile.html.twig', array(
            'user' => $user[0],
        ));
    }
}
