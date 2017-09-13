<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\User;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
        ]);
    }

    public function createUserAction() {
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

    public function showUserAction($userId) {
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
