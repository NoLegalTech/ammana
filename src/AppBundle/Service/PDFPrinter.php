<?php

namespace AppBundle\Service;

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

class PDFPrinter {

    private $content = null;
    private $styles = null;
    private $variables = null;
    private $logo = null;
    private $fileName = null;

    public function __construct() {
    }

    public function setContent($content) {
        $this->content = $content;
    }

    public function setStyles($styles) {
        $this->styles = $styles;
    }

    public function setVariables($variables) {
        $this->variables = $variables;
    }

    public function setLogo($logo) {
        $this->logo = $logo;
    }

    public function setFileName($fileName) {
        $this->fileName = $fileName;
    }

    public function print() {
        $pdf = new PDF();
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->AddFont('Cambria','B','cambria-bold-59d2276a6a486.php');
        $pdf->AddFont('Cambria','', 'cambria-59d2585e5b777.php');
        $pdf->AddFont('Calibri','', 'Calibri.php');
        $pdf->SetTextColor(0,0,0);
        $pdf->SetMargins(30, 30);
        if ($this->logo != null) {
            $pdf->Image($this->logo, 130, 20, 50);
        }
        $pdf->SetXY(30,50);
        if ($this->content != null && $this->styles != null) {
            foreach ($this->content as $line) {
                list($currentStyle, $condition, $currentLine) = $this->parse($line);
                $style = $this->applyStyles($this->styles, $currentStyle);
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
                    $pdf->Cell(0, $style['margin-top'], '', 0, 1, 'L');
                }
                if ($condition != null) {
                    list($variable, $value) = explode('=', $condition);
                    if ($value != $this->getVariable($variable)) {
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
                        $this->expandVariables($l);
                        $this->printLine($pdf, $style, $l, $alignment);
                    }
                    $this->printLine($pdf, $style, '', 'L');
                } else {
                    $this->expandVariables($currentLine);
                    $this->printLine($pdf, $style, $currentLine, $alignment);
                }
                if (isset($style['margin-bottom'])) {
                    $pdf->Cell(0, $style['margin-bottom'], '', 0, 1, 'L');
                }
            }
        }
        //return $pdf->Output('D', $protocol_spec['name'] . '.pdf');
        return $pdf->Output('S', $this->fileName);
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

    private function getVariable($name) {
        if ($this->variables == null) {
            return null;
        }
        return $this->variables[$name];
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

    private function printLine($pdf, $style, $line, $alignment) {
        if (strpos($line, ' ') === false) {
            $alignment = 'L';
        }
        $pdf->Cell(0, $style['line-height'], $this->fixSpecialCharacters($line), 0, 1, $alignment);
    }

    private function fixSpecialCharacters($text) {
        return iconv('UTF-8', 'windows-1252', $text);
        // if iconv extension  not loaded we can do instead:
        // return utf8_decode($text);
    }

    private function expandVariables(&$line) {
        if ($this->variables == null) {
            return;
        }
        foreach ($this->variables as $name => $value) {
            $line = str_replace('[' . $name . ']', $value, $line);
        }
    }

}

