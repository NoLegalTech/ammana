<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {
        return $this->render('default/index.html.twig', [
            'title' => $this->getI18n()['home_page']['claim']['title'],
            'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
        ]);
    }

    public function errorAction(Request $request)
    {
        return $this->render('default/error.html.twig', [
            'title' => $this->getI18n()['error_page']['error_label'],
            'message' => $request->query->get('message')
        ]);
    }

    public function iconsAction(Request $request)
    {
        return $this->render('default/icons.html.twig', array(
            'title' => 'Iconos'
        ));
    }

    private function getI18n() {
        return $this->container->get('twig')->getGlobals()['i18n']['es'];
    }

}
