<?php

namespace AppBundle\Controller;

use Psr\Log\LoggerInterface;

use AppBundle\Entity\Invoice;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Invoice controller.
 *
 */
class InvoiceController extends Controller
{
    /**
     * Lists all invoice entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $invoices = $em->getRepository('AppBundle:Invoice')->findAll();

        return $this->render('invoice/index.html.twig', array(
            'invoices' => $invoices,
        ));
    }

}
