<?php

namespace AppBundle\Controller;

use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use AppBundle\Entity\User;

class UserController extends Controller {

    /**
     * @Route("/user/create")
     */
    public function createAction(LoggerInterface $logger) {
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

        $logger->info('Created user: '.$user->getEmail());
        return new Response('Created new user with id '.$user->getId());
    }

    /**
     * @Route("/user/show/{id}")
     */
    public function showAction($id) {
        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($id);

        if (!$user) {
            throw $this->createNotFoundException(
                'No user found for id '.$id
            );
        }

        return $this->render('user/show.html.twig', array(
            'user' => $user,
        ));
    }

}
