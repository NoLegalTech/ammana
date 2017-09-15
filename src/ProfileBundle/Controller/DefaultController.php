<?php

namespace ProfileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function indexAction() {
        return $this->render('user/profile.html.twig');
    }
}
