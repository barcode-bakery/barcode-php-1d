<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Sub-Class - UPC Supplemental Barcode 2 digits
 *
 * Working with UPC-A, UPC-E, EAN-13, EAN-8
 * This includes 2 digits (normaly for publications)
 * Must be placed next to UPC or EAN Code
 *
 *--------------------------------------------------------------------
 * Copyright (C) Jean-Sebastien Goupil
 * http://www.barcodebakery.com
 */
namespace BarcodeBakery\Barcode;

use BarcodeBakery\Common\BCGBarcode1D;
use BarcodeBakery\Common\BCGLabel;
use BarcodeBakery\Common\BCGParseException;

class BCGupcext2 extends BCGBarcode1D
{
    protected array $codeParity = array();

    /**
     * Creates a UPC supplemental 2 digits barcode.
     */
    public function __construct()
    {
        parent::__construct();

        $this->keys = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        $this->code = array(
            '2100',     /* 0 */
            '1110',     /* 1 */
            '1011',     /* 2 */
            '0300',     /* 3 */
            '0021',     /* 4 */
            '0120',     /* 5 */
            '0003',     /* 6 */
            '0201',     /* 7 */
            '0102',     /* 8 */
            '2001'      /* 9 */
        );

        // Parity, 0=Odd, 1=Even. Depending on ?%4
        $this->codeParity = array(
            array(0, 0),    /* 0 */
            array(0, 1),    /* 1 */
            array(1, 0),    /* 2 */
            array(1, 1)     /* 3 */
        );
    }

    /**
     * Draws the barcode.
     *
     * @param resource $image The surface.
     */
    public function draw($image): void
    {
        // Starting Code
        $this->drawChar($image, '001', true);

        // Code
        for ($i = 0; $i < 2; $i++) {
            $this->drawChar($image, self::inverse($this->findCode($this->text[$i]), $this->codeParity[intval($this->text) % 4][$i]), false);
            if ($i === 0) {
                $this->drawChar($image, '00', false);    // Inter-char
            }
        }

        $this->drawText($image, 0, 0, $this->positionX, $this->thickness);
    }

    /**
     * Returns the maximal size of a barcode.
     *
     * @param int $width The width.
     * @param int $height The height.
     * @return int[] An array, [0] being the width, [1] being the height.
     */
    public function getDimension(int $width, int $height): array
    {
        $startlength = 4;
        $textlength = 2 * 7;
        $intercharlength = 2;

        $width += $startlength + $textlength + $intercharlength;
        $height += $this->thickness;
        return parent::getDimension($width, $height);
    }

    /**
     * Adds the default label.
     *
     * @return void
     */
    protected function addDefaultLabel(): void
    {
        parent::addDefaultLabel();

        if ($this->defaultLabel !== null) {
            $this->defaultLabel->setPosition(BCGLabel::POSITION_TOP);
        }
    }

    /**
     * Validates the input.
     *
     * @return void
     */
    protected function validate(): void
    {
        $c = strlen($this->text);
        if ($c === 0) {
            throw new BCGParseException('upcext2', 'No data has been entered.');
        }

        // Checking if all chars are allowed
        for ($i = 0; $i < $c; $i++) {
            if (array_search($this->text[$i], $this->keys) === false) {
                throw new BCGParseException('upcext2', 'The character \'' . $this->text[$i] . '\' is not allowed.');
            }
        }

        // Must contain 2 digits
        if ($c !== 2) {
            throw new BCGParseException('upcext2', 'Must contain 2 digits.');
        }

        parent::validate();
    }

    /**
     * Inverses the string when the $inverse parameter is equal to 1.
     *
     * @param string $text The text.
     * @param int $inverse The inverse.
     * @return string the reversed string.
     */
    private static function inverse(string $text, int $inverse = 1): string
    {
        if ($inverse === 1) {
            $text = strrev($text);
        }

        return $text;
    }
}
