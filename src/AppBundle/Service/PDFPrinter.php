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

class Condition {

    private $variableName, $expectedValue;
    private $dependsOn = null;

    public function __construct($variableName, $expectedValue, $dependsOn = null) {
        $this->variableName = $variableName;
        $this->expectedValue = $expectedValue;
        $this->dependsOn = $dependsOn;
    }

    public function matches($variables) {
        return ($variables != null)
            && $variables[$this->variableName] == $this->expectedValue
            && ($this->dependsOn == null || $this->dependsOn->matches($variables));
    }

    public function __toString() {
        return '(' . $this->variableName . ' == ' . $this->expectedValue . ')';
    }

}

class TrueCondition extends Condition {

    public function __construct() {
    }

    public function matches($variables) {
        return true;
    }

}

class PDFElement {

    private $element, $type;
    private $pdf, $style, $alignment;
    private $variables;
    private $condition, $properties;

    private static $list_counter = 1;
    private static $list_style = '.';
    private static $h2_counter = 1;

    public function __construct($element, $type, $pdf, $style, $variables, $condition, $properties) {
        $this->element = $element;
        $this->type = $type;
        $this->pdf = $pdf;
        $this->style = $style;
        $this->variables = $variables;
        $this->condition = $condition;
        $this->properties = $properties;
        $this->alignment = 'L';
    }

    public function getCondition() {
        return $this->condition;
    }

    public function getProperty($property) {
        return ($this->properties != null && isset($this->properties[$property]))
            ? $this->properties[$property]
            : null;
    }

    public function print() {
        $this->applyStyleBefore();
        $this->printElement();
        $this->applyStyleAfter();
    }

    public function log($text) {
        $this->pdf->Cell(0, $this->style['line-height'], $text, 0, 1, 'L');
    }

    private function printElement() {
        if ($this->type == 'li') {
            if ($this->getProperty('start_at') != null) {
                self::$list_counter = $this->getProperty('start_at');
            }
            if ($this->getProperty('list_style') != null) {
                self::$list_style = $this->getProperty('list_style');
            }
            $this->pdf->Cell($this->style['list-margin-left'], $this->style['line-height'], self::$list_counter . self::$list_style, 0, 0, 'L');
            self::$list_counter++;
        }
        if ($this->type == 'h2') {
            if (is_array($this->element)) {
                $this->element[0] = self::$h2_counter . '. ' . $this->element[0];
            } else {
                $this->element = self::$h2_counter . '. ' . $this->element;
            }
            self::$h2_counter++;
        }
        if (is_array($this->element)) {
            $total = count($this->element);
            $current = 0;
            $isFirst = true;
            foreach ($this->element as $l) {
                $current++;
                if ($current == $total && $this->alignment == 'FJ') {
                    $this->alignment = 'L';
                }
                if ($isFirst) {
                    $this->printLine($l);
                } else {
                    if ($this->type == 'li') {
                        $this->pdf->Cell($this->style['list-margin-left'], $this->style['line-height'], ' ', 0, 0, 'L');
                    }
                    $this->printLine($l);
                }
                $isFirst = false;
            }
            $this->alignment = 'L';
            $this->printLine('');
        } else {
            $this->printLine($this->element);
        }
    }

    private function printLine($line) {
        if (strpos($line, ' ') === false) {
            $this->alignment = 'L';
        }
        $line = $this->expandVariables($line);
        $line = $this->fixSpecialCharacters($line);
        $this->pdf->Cell(0, $this->style['line-height'], $line, 0, 1, $this->alignment);
    }

    private function fixSpecialCharacters($text) {
        return iconv('UTF-8', 'windows-1252', $text);
        // if iconv extension  not loaded we can do instead:
        // return utf8_decode($text);
    }

    private function expandVariables($line) {
        if ($this->variables == null) {
            return $line;
        }
        foreach ($this->variables as $name => $value) {
            $line = str_replace('[' . $name . ']', $value, $line);
        }
        return $line;
    }

    private function applyStyleBefore() {
        $this->applyFont();
        $this->applyAlignment();
        $this->applyMarginTop();
    }

    private function applyFont() {
        $fontStyle = '';
        if ($this->style['font-weight'] == 'bold') {
            $fontStyle .= 'B';
        }
        if ($this->style['text-style'] == 'underline') {
            $fontStyle .= 'U';
        }
        $this->pdf->SetFont($this->style['font-family'], $fontStyle, $this->style['font-size']);
    }

    private function applyAlignment() {
        $this->alignment = 'L';
        if ($this->style['text-align'] == 'center') {
            $this->alignment = 'C';
        }
        if ($this->style['text-align'] == 'justify') {
            $this->alignment = 'FJ';
        }
    }

    private function applyMarginTop() {
        if (isset($this->style['margin-top'])) {
            $this->pdf->Cell(0, $this->style['margin-top'], '', 0, 1, 'L');
        }
    }

    private function applyStyleAfter() {
        if (isset($this->style['margin-bottom'])) {
            $this->pdf->Cell(0, $this->style['margin-bottom'], '', 0, 1, 'L');
        }
    }

}

class PDFPrinter {

    private $content = null;
    private $styles = null;
    private $style = null;
    private $variables = null;
    private $logo = null;
    private $fileName = null;
    private $pdf;
    private $allowed_tags;
    private $questions;

    public function __construct() {
        $this->pdf = new PDF();
        $this->allowed_tags = [ 'p', 'h1', 'h2', 'li' ];
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

    public function setQuestions($questions) {
        $this->questions = $questions;
    }

    public function setLogo($logo) {
        $this->logo = $logo;
    }

    public function setFileName($fileName) {
        $this->fileName = $fileName;
    }

    public function print() {
        $this->pdf->AliasNbPages();
        $this->pdf->AddPage();
        $this->pdf->AddFont('Cambria','B','cambria-bold-59d2276a6a486.php');
        $this->pdf->AddFont('Cambria','', 'cambria-59d2585e5b777.php');
        $this->pdf->AddFont('Calibri','', 'Calibri.php');
        $this->pdf->SetTextColor(0,0,0);
        $this->pdf->SetMargins(30, 30);
        if ($this->logo != null) {
            $this->printLogo();
        }
        $this->pdf->SetXY(30,50);
        $this->printContent();
        return $this->pdf->Output('D', $this->fileName);
    }

    private function printLogo() {
        $boundaries = $this->getLogoBoundaries();

        $this->pdf->Image(
            $this->logo,
            $boundaries['x'] - $boundaries['width'],
            $boundaries['y'],
            $boundaries['width'],
            $boundaries['height']
        );
    }

    private function getLogoBoundaries() {
        $boundaries = array(
            "width"  => 50,
            "height" => 25,
            "x"      => 178,
            "y"      => 20
        );
        if ($this->styles != null) {
            if (isset($this->styles['default']['logo-max-width'])) {
                $boundaries['width'] = $this->styles['default']['logo-max-width'];
            }
            if (isset($this->styles['default']['logo-max-height'])) {
                $boundaries['height'] = $this->styles['default']['logo-max-height'];
            }
            if (isset($this->styles['default']['logo-x'])) {
                $boundaries['x'] = $this->styles['default']['logo-x'];
            }
            if (isset($this->styles['default']['logo-y'])) {
                $boundaries['y'] = $this->styles['default']['logo-y'];
            }
        }

        list($w, $h) = getimagesize($this->logo);
        if ($h / $w > $boundaries['height'] / $boundaries['width']) {
            $boundaries['width'] = $boundaries['height'] * $w / $h;
        } else {
            $boundaries['height'] = $boundaries['width'] * $h / $w;
        }

        return $boundaries;
    }

    private function printContent() {
        if ($this->content == null || $this->styles == null) {
            return;
        }
        foreach ($this->content as $item) {
            $pdfElement = $this->parse($item);
            if ($pdfElement->getCondition()->matches($this->variables)) {
                $pdfElement->print();
            }
        }
    }

    private function parse($element) {
        $condition = null;
        $elementType = null;
        $properties = [];
        foreach ($element as $key => $value) {
            if (in_array($key, $this->allowed_tags)) {
                $elementType = $key;
                $element = $value;
            } elseif ($key == "condition") {
                $condition = $value;
            } else {
                $properties[$key] = $value;
            }
        }
        $this->style = $this->applyStyles($this->styles, $elementType);
        return new PDFElement(
            $element,
            $elementType,
            $this->pdf,
            $this->style,
            $this->variables,
            $this->parseCondition($condition),
            $properties
        );
    }

    private function parseCondition($condition) {
        if ($condition == null) {
            return new TrueCondition();
        }
        list($variable, $theValue) = explode('=', $condition);
        if ($this->questions != null && isset($this->questions[$variable]) && isset($this->questions[$variable][$condition])) {
            return new Condition(
                $variable,
                $theValue,
                $this->parseCondition($this->questions[$variable][$condition])
            );
        }
        return new Condition($variable, $theValue);
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

}
