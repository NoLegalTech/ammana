<?php

namespace AppBundle\Controller;

use Psr\Log\LoggerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use AppBundle\Entity\User;
use AppBundle\Service\PermissionsService;

class ProfileController extends Controller {

    public function indexAction(Request $request, SessionInterface $session, PermissionsService $permissions)
    {
        if (!$permissions->currentRolesInclude("customer") && !$permissions->currentRolesInclude("admin")) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        $user = $permissions->getCurrentUser($session);

        $editForm = $this->createForm('AppBundle\Form\UserType', $user, array(
            'i18n' => $this->getI18n()
        ));
        $editForm->add('previous_logo', HiddenType::class, array(
            'data' => $user->getLogo(),
            'mapped' => false
        ));
        $editForm->add('delete_logo', CheckboxType::class, array(
            'label' => 'Marque para borrar logo',
            'required' => false,
            'mapped' => false
        ));
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $previous_logo = $editForm->get('previous_logo')->getData();
            $delete_logo = $editForm->get('delete_logo')->getData();

            if ($delete_logo) {
                $user->setLogo(null);
            } else {
                $file = $user->getLogo();
                if ($file != null) {
                    $fileName = md5(uniqid()).'.'.$file->guessExtension();
                    $file->move($this->get('kernel')->getRootDir(). '/../web/uploads', $fileName);
                    $user->setLogo($fileName);
                } else {
                    $user->setLogo($previous_logo);
                }
            }
            
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('profile_homepage');
        }

        return $this->render('user/profile.html.twig', array(
            'user' => $user,
            'edit_form' => $editForm->createView()
        ));
    }

    private function getI18n() {
        return $this->container->get('twig')->getGlobals()['i18n']['es'];
    }

}
