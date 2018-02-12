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
            'google_analytics' => $this->container->getParameter('google_analytics')
        ]);
    }

    public function maintenanceAction(Request $request)
    {
        return $this->render('default/maintenance.html.twig', [
            'google_analytics' => $this->container->getParameter('google_analytics')
        ]);
    }

    public function errorAction(Request $request)
    {
        return $this->render('default/error.html.twig', [
            'title' => $this->getI18n()['error_page']['error_label'],
            'message' => $request->query->get('message'),
            'technical_info' => $request->query->get('technical_info', null),
            'google_analytics' => $this->container->getParameter('google_analytics')
        ]);
    }

    public function legalAction(Request $request)
    {
        return $this->render('default/legal.html.twig', [
            'title' => $this->getI18n()['legal_page']['title'],
            'google_analytics' => $this->container->getParameter('google_analytics')
        ]);
    }

    public function privacyAction(Request $request)
    {
        return $this->render('default/privacy.html.twig', [
            'title' => $this->getI18n()['privacy_page']['title'],
            'google_analytics' => $this->container->getParameter('google_analytics')
        ]);
    }

    public function cookiesAction(Request $request)
    {
        return $this->render('default/cookies.html.twig', [
            'title' => $this->getI18n()['cookies_page']['title'],
            'google_analytics' => $this->container->getParameter('google_analytics')
        ]);
    }

    public function iconsAction(Request $request)
    {
        return $this->render('default/icons.html.twig', array(
            'title' => 'Iconos',
            'google_analytics' => $this->container->getParameter('google_analytics')
        ));
    }

    private function getI18n() {
        return $this->container->get('twig')->getGlobals()['i18n']['es'];
    }

}
