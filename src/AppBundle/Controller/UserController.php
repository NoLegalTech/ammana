<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Entity\User;

class UserController extends Controller {

    /**
     * @Route("/user/create")
     */
    public function createAction() {
        $em = $this->getDoctrine()->getManager();

        $user = new User();
        $user->setEmail('pepellou@gmail.com');
        $user->setPassword('aPassword');
        $user->setCompanyName('Agil AZ S.L.');
        $user->setCif('B70240296');
        $user->setAddress('c/ Hedras, 6, 1D');
        $user->setContactPerson('Pepe Doval');

        $em->persist($user);

        $em->flush();

        return new Response('Created new user with id '.$user->getId());
    }

    /**
     * @Route("/user/show")
     */
    public function showAction($userId) {
        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($userId);

        if (!$user) {
            throw $this->createNotFoundException(
                'No user found for id '.$userId
            );
        }

        return new Response('Found user with id '.$user->getId().' and email '.$user->getEmail());
    }

}
