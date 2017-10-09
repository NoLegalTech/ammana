<?php

namespace ProfileBundle\Controller;

use Psr\Log\LoggerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use AppBundle\Entity\User;
use AppBundle\Service\PermissionsService;

class DefaultController extends Controller {

    public function indexAction(Request $request, SessionInterface $session, PermissionsService $permissions)
    {
        if (!$permissions->currentRolesInclude("customer")) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        $user = $permissions->getUserFromSession($session);

        $editForm = $this->createForm('AppBundle\Form\UserType', $user);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $file = $user->getLogo();
            $fileName = md5(uniqid()).'.'.$file->guessExtension();
            $file->move($this->get('kernel')->getRootDir(). '/../web/uploads', $fileName);
            $user->setLogo($fileName);
            
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('profile_homepage');
        }

        return $this->render('user/profile.html.twig', array(
            'user' => $user,
            'edit_form' => $editForm->createView()
        ));
    }

}
