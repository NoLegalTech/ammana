<?php

namespace AppBundle\Controller;

use Psr\Log\LoggerInterface;

use Symfony\Component\Filesystem\Filesystem;

use AppBundle\Entity\Protocol;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class PDF extends \FPDF {

    function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '') {
        $k = $this->k;
        if ($this->y+$h > $this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak()) {
            $x = $this->x;
            $ws = $this->ws;
            if ($ws > 0) {
                $this->ws = 0;
                $this->_out('0 Tw');
            }
            $this->AddPage($this->CurOrientation);
            $this->x = $x;
            if ($ws > 0) {
                $this->ws = $ws;
                $this->_out(sprintf('%.3F Tw', $ws*$k));
            }
        }
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        $s = '';
        if ($fill || $border==1) {
            if ($fill) {
                $op = ($border==1) ? 'B' : 'f';
            } else {
                $op = 'S';
            }
            $s = sprintf('%.2F %.2F %.2F %.2F re %s ', $this->x * $k, ($this->h - $this->y) * $k, $w * $k, -$h * $k, $op);
        }
        if (is_string($border)) {
            $x = $this->x;
            $y = $this->y;
            if (is_int(strpos($border, 'L'))) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x * $k, ($this->h - $y) * $k, $x * $k, ($this->h - ($y + $h)) * $k);
            }
            if (is_int(strpos($border, 'T'))) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x * $k, ($this->h - $y) * $k, ($x + $w) * $k, ($this->h - $y) * $k);
            }
            if (is_int(strpos($border, 'R'))) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', ($x + $w) * $k, ($this->h - $y) * $k, ($x + $w) * $k, ($this->h - ($y + $h)) * $k);
            }
            if (is_int(strpos($border, 'B'))) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x * $k, ($this->h - ($y + $h)) * $k, ($x + $w) * $k, ($this->h - ($y + $h)) * $k);
            }
        }
        if ($txt != '') {
            if ($align == 'R') {
                $dx = $w - $this->cMargin - $this->GetStringWidth($txt);
            } elseif ($align == 'C') {
                $dx = ($w - $this->GetStringWidth($txt)) / 2;
            } elseif ($align == 'FJ') {
                $wmax = ($w - 2 * $this->cMargin);
                $this->ws = ($wmax - $this->GetStringWidth($txt)) / substr_count($txt, ' ');
                $this->_out(sprintf('%.3F Tw', $this->ws * $this->k));
                $dx = $this->cMargin;
            } else {
                $dx = $this->cMargin;
            }
            $txt = str_replace(')', '\\)', str_replace('(', '\\(', str_replace('\\', '\\\\', $txt)));
            if ($this->ColorFlag) {
                $s .= 'q ' . $this->TextColor . ' ';
            }
            $s .= sprintf('BT %.2F %.2F Td (%s) Tj ET', ($this->x + $dx) * $k, ($this->h - ($this->y + .5 * $h + .3 * $this->FontSize)) * $k, $txt);
            if ($this->underline) {
                $s .= ' ' . $this->_dounderline($this->x + $dx, $this->y + .5 * $h + .3 * $this->FontSize, $txt);
            }
            if ($this->ColorFlag) {
                $s .= ' Q';
            }
            if ($link) {
                if ($align == 'FJ') {
                    $wlink = $wmax;
                } else {
                    $wlink = $this->GetStringWidth($txt);
                }
                $this->Link($this->x + $dx, $this->y + .5 * $h - .5 * $this->FontSize, $wlink, $this->FontSize, $link);
            }
        }
        if ($s) {
            $this->_out($s);
        }
        if ($align == 'FJ') {
            $this->_out('0 Tw');
            $this->ws = 0;
        }
        $this->lasth = $h;
        if ($ln > 0) {
            $this->y += $h;
            if ($ln == 1) {
                $this->x = $this->lMargin;
            }
        } else {
            $this->x += $w;
        }
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Calibri','',11);
        $this->Cell(0,10,$this->PageNo(),0,0,'C');
    }

}

/**
 * Protocol controller.
 *
 */
class ProtocolController extends Controller
{

    /**
     * Lists all protocol entities of the current user.
     *
     */
    public function indexAction(LoggerInterface $logger, SessionInterface $session)
    {
        $user = $this->getUserFromSession($session);
        if ($user == null) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        $protocols = $this->getDoctrine()
            ->getRepository(Protocol::class)
            ->findByUser($user->getId());

        $names = [];
        foreach ($this->container->getParameter('protocols') as $id => $protocol_spec) {
            $names[$id] = $protocol_spec['name'];
        }

        return $this->render('protocol/index.html.twig', array(
            'protocols' => $protocols,
            'names' => $names
        ));
    }

    private function getUserFromSession($session) {
        if (!$session->get('user')) {
            return null;
        }

        $found = $this->getDoctrine()
            ->getRepository(User::class)
            ->findByEmail($session->get('user'));

        if (!$found) {
            return null;
        }

        return $found[0];
    }

    private function userHasCompletedProfile($user) {
        return ( $user->getEmail() != null )
            && ( $user->getPassword() != null )
            && ( $user->getCompanyName() != null )
            && ( $user->getCif() != null )
            && ( $user->getAddress() != null )
            && ( $user->getContactPerson() != null )
            && ( $user->getNumberEmployees() != null )
            && ( $user->getSector() != null );
    }

    /**
     * Buys a protocol.
     *
     */
    public function buyAction($id, Request $request, LoggerInterface $logger, SessionInterface $session)
    {
        $user = $this->getUserFromSession($session);
        if ($user == null) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        $protocols = $this->container->getParameter('protocols');
        if (!isset($protocols[$id])) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        $protocol = $protocols[$id];
        $protocol['id'] = $id;

        $profile_completed = $this->userHasCompletedProfile($user);

        $questionsForm = $this->getForm(
            $protocol['questions'],
            $request->isMethod('POST')
        );

        $questionsForm->handleRequest($request);

        if ($questionsForm->isSubmitted() && $questionsForm->isValid()) {
            if ($request->get('confirmed') == null) {
                return $this->render('protocol/confirmation.html.twig', array(
                    'profile_completed' => $profile_completed,
                    'form' => $questionsForm->createView(),
                    'protocol' => $protocol
                ));
            }

            $purchasedProtocol = new Protocol();
            $purchasedProtocol->setIdentifier($id);
            $purchasedProtocol->setUser($user->getId());
            $purchasedProtocol->setEnabled(false);
            $purchasedProtocol->setExpiresAt(new \DateTime(date('Y-m-d', strtotime('+1 year'))));
            $answers = [];
            foreach ($questionsForm->getData() as $key => $value) {
                $answers []= $key . '=' . $value;
            }
            $purchasedProtocol->setAnswers(implode(',', $answers));
            $em = $this->getDoctrine()->getManager();
            $em->persist($purchasedProtocol);
            $em->flush();

            return $this->render('protocol/payment.html.twig', array(
                'profile_completed' => $profile_completed,
                'form' => $questionsForm->createView(),
                'protocol' => $protocol
            ));
        }

        return $this->render('protocol/questions.html.twig', array(
            'profile_completed' => $profile_completed,
            'form' => $questionsForm->createView(),
            'protocol' => $protocol
        ));
    }

    private function getForm($questions, $isConfirmation) {
        $formBuilder = $this->createFormBuilder();
        foreach ($questions as $question) {
            $choices = array();
            $count = 0;
            foreach ($question['answers'] as $answer) {
                $choices[$answer] = $count++;
            }
            $formBuilder->add($question['id'], ChoiceType::class, array(
                'label' => $question['question'],
                'choices' => $choices,
                'expanded' => !$isConfirmation,
                'multiple' => false
            ));
        }

        return $formBuilder ->getForm();
    }

    /**
     * Downloads a protocol.
     *
     */
    public function downloadAction(Protocol $protocol, Request $request, LoggerInterface $logger, SessionInterface $session)
    {
        $user = $this->getUserFromSession($session);
        if ($user == null) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }


        if ($protocol->getUser() != $user->getId()) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        $protocols = $this->container->getParameter('protocols');
        if (!isset($protocols[$protocol->getIdentifier()])) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        $protocol_spec = $protocols[$protocol->getIdentifier()];

        $pdf = new PDF();
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->AddFont('Cambria','B','cambria-bold-59d2276a6a486.php');
        $pdf->AddFont('Cambria','', 'cambria-59d2585e5b777.php');
        $pdf->AddFont('Calibri','', 'Calibri.php');

        if (!isset($protocol_spec['document'])) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        $document = $protocol_spec['document'];

        $pdf->SetTextColor(0,0,0);
        $pdf->SetMargins(30, 30);

        if ($request->query->get('logo') == 'yes') {
            $pdf->Image(
                $this->get('kernel')->getRootDir() . '/../src/AppBundle/Resources/public/img/logo_agilaz.png',
                130,
                20,
                50
            );
        }
        $pdf->SetXY(30,50);

        //$pdf->Cell(0, 6, utf8_decode('Prueba del copón'), 0, 1, 'L');
        //$pdf->Cell(0, 6, iconv('UTF-8', 'windows-1252', 'Otra también'), 0, 1, 'L');
        foreach ($document['lines'] as $line) {
            list($currentStyle, $condition, $currentLine) = $this->parse($line);
            $style = $this->applyStyles($document['styles'], $currentStyle);
            $fontStyle = '';
            if ($style['font-weight'] == 'bold') {
                $fontStyle .= 'B';
            }
            if ($style['text-style'] == 'underline') {
                $fontStyle .= 'U';
            }
            $alignment = 'L';
            if ($style['text-align'] == 'center') {
                $alignment = 'C';
            }
            if ($style['text-align'] == 'justify') {
                $alignment = 'FJ';
            }
            $pdf->SetFont('Cambria', $fontStyle, 13);
            if (isset($style['margin-top'])) {
                $pdf->Cell(0, $style['margin-top'], '', 0, 1, $alignment);
            }
            if ($condition != null) {
                list($variable, $value) = explode('=', $condition);
                if ($value != $this->getAnswer($protocol, $variable)) {
                    continue;
                }
            }
            if (is_array($currentLine)) {
                $total = count($currentLine);
                $current = 0;
                foreach ($currentLine as $l) {
                    $current++;
                    if ($current == $total && $alignment == 'FJ') {
                        $alignment = 'L';
                    }
                    $pdf->Cell(0, $style['line-height'], iconv('UTF-8', 'windows-1252', $l), 0, 1, $alignment);
                }
                $pdf->Cell(0, $style['line-height'], '', 0, 1, $alignment);
            } else {
                $pdf->Cell(0, $style['line-height'], iconv('UTF-8', 'windows-1252', $currentLine), 0, 1, $alignment);
            }
            if (isset($style['margin-bottom'])) {
                $pdf->Cell(0, $style['margin-bottom'], '', 0, 1, $alignment);
            }
        }

        return new Response($pdf->Output('D', $protocol_spec['name']), 200);
        //return new Response($pdf->Output('S', $protocol_spec['name'].'.pdf'), 200, array( 'Content-Type' => 'application/pdf'));
    }

    private function getAnswer($protocol, $variable) {
        $asignments = explode(',', $protocol->getAnswers());
        foreach ($asignments as $asignment) {
            list($var, $val) = explode('=', $asignment);
            if ($var == $variable) {
                return $val;
            }
        }
        return null;
    }

    private function parse($line) {
        $condition = null;
        $style = null;
        foreach ($line as $key => $value) {
            if ($key == "condition") {
                $condition = $value;
            } else {
                $style = $key;
                $line = $value;
            }
        }
        return [$style, $condition, $line];
    }

    private function applyStyles($styles, $selected) {
        if ($selected == 'default' || !isset($styles[$selected])) {
            return $styles['default'];
        }
        $style = $styles['default'];
        foreach ($styles[$selected] as $key => $value) {
            $style[$key] = $value;
        }
        return $style;
    }

    /**
     * Pays a protocol.
     *
     */
    public function payAction($id, $type, Request $request, LoggerInterface $logger, SessionInterface $session)
    {
        $user = $this->getUserFromSession($session);
        if ($user == null) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        $protocols = $this->container->getParameter('protocols');
        if (!isset($protocols[$id])) {
            return $this->redirectToRoute('error', array(
                'message' => 'Ha ocurrido un error inesperado.'
            ));
        }

        $protocol = $protocols[$id];
        $protocol['id'] = $id;

        $found = $this->getDoctrine()
            ->getRepository(Protocol::class)
            ->findOneBy(array(
                'user' => $user->getId(),
                'identifier' => $id
            ));

        if ($type == 'paypal') {
            $found->setEnabled(true);
            $this->getDoctrine()->getManager()->flush();
        }

        return $this->redirectToRoute('protocol_index');
    }

}
