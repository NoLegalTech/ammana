<?php

/*!
 * ammana.es - job protocols generator
 * https://github.com/NoLegalTech/ammana
 * Copyright (C) 2018 Zeres Abogados y Consultores Laborales SLP <zeres@zeres.es>
 * https://github.com/NoLegalTech/ammana/blob/master/LICENSE
 */

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
            'google_analytics' => $this->getAnalyticsCode()
        ]);
    }

    public function maintenanceAction(Request $request)
    {
        return $this->render('default/maintenance.html.twig', [
            'google_analytics' => $this->getAnalyticsCode()
        ]);
    }

    public function errorAction(Request $request)
    {
        return $this->render('default/error.html.twig', [
            'title' => $this->getI18n()['error_page']['error_label'],
            'message' => $request->query->get('message'),
            'technical_info' => $request->query->get('technical_info', null),
            'google_analytics' => $this->getAnalyticsCode()
        ]);
    }

    public function legalAction(Request $request)
    {
        return $this->render('default/legal.html.twig', [
            'title' => $this->getI18n()['legal_page']['title'],
            'google_analytics' => $this->getAnalyticsCode()
        ]);
    }

    public function privacyAction(Request $request)
    {
        return $this->render('default/privacy.html.twig', [
            'title' => $this->getI18n()['privacy_page']['title'],
            'google_analytics' => $this->getAnalyticsCode()
        ]);
    }

    public function cookiesAction(Request $request)
    {
        return $this->render('default/cookies.html.twig', [
            'title' => $this->getI18n()['cookies_page']['title'],
            'google_analytics' => $this->getAnalyticsCode()
        ]);
    }

    public function redesAction(Request $request)
    {
        $tax = 0.21;
        return $this->render('default/redes.html.twig', [
            'title' => $this->getI18n()['redes_page']['title'],
            'google_analytics' => $this->getAnalyticsCode(),
            'price' => $this->container->getParameter('protocol_price') / (100 * (1 + $tax))
        ]);
    }

    public function mensajeriasAction(Request $request)
    {
        $tax = 0.21;
        return $this->render('default/mensajerias.html.twig', [
            'title' => $this->getI18n()['mensajerias_page']['title'],
            'google_analytics' => $this->getAnalyticsCode(),
            'price' => $this->container->getParameter('protocol_price') / (100 * (1 + $tax))
        ]);
    }

    public function equiposAction(Request $request)
    {
        $tax = 0.21;
        return $this->render('default/equipos.html.twig', [
            'title' => $this->getI18n()['equipos_page']['title'],
            'google_analytics' => $this->getAnalyticsCode(),
            'price' => $this->container->getParameter('protocol_price') / (100 * (1 + $tax))
        ]);
    }

    public function iconsAction(Request $request)
    {
        return $this->render('default/icons.html.twig', array(
            'title' => 'Iconos',
            'google_analytics' => $this->getAnalyticsCode()
        ));
    }

    private function getI18n() {
        return $this->container->get('twig')->getGlobals()['i18n']['es'];
    }

    private function getAnalyticsCode() {
        return $this->container->hasParameter('google_analytics')
            ? $this->container->getParameter('google_analytics')
            : null;
    }

}
