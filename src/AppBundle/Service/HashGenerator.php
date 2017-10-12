<?php

namespace AppBundle\Service;

class HashGenerator {

    private $allowed_chars;

    public function __construct() {
        $this->allowed_chars = array(
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
            'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
            'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
            '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0',
            '.', '-', '_', '$', '!'
        );
    }

    public function generate($length = 100) {
        $result = "";
        $last_index = count($this->allowed_chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $result .= $this->allowed_chars[rand(0, $last_index)];
        }
        return $result;
    }

}
