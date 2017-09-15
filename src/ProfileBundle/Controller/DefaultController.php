<?php

namespace ProfileBundle\Controller;

use Psr\Log\LoggerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use AppBundle\Entity\User;

class DefaultController extends Controller
{
    public function indexAction() {
        $id = 1; // TODO get from session when there's login

        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($id);

        if (!$user) {
            throw $this->createNotFoundException(
                'No user found for id '.$id
            );
        }

        return $this->render('user/profile.html.twig', array(
            'user' => $user,
        ));
    }
}
