<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Sub-Class - UPC Supplemental Barcode 5 digits
 *
 * Working with UPC-A, UPC-E, EAN-13, EAN-8
 * This includes 5 digits (normaly for suggested retail price)
 * Must be placed next to UPC or EAN Code
 * If 90000 -> No suggested Retail Price
 * If 99991 -> Book Complimentary (normally free)
 * If 90001 to 98999 -> Internal Purpose of Publisher
 * If 99990 -> Used by the National Association of College Stores to mark used books
 * If 0xxxx -> Price Expressed in British Pounds (xx.xx)
 * If 5xxxx -> Price Expressed in U.S. dollars (US$xx.xx)
 *
 *--------------------------------------------------------------------
 * Copyright (C) Jean-Sebastien Goupil
 * http://www.barcodebakery.com
 */
namespace BarcodeBakery\Barcode;

use BarcodeBakery\Common\BCGBarcode1D;
use BarcodeBakery\Common\BCGLabel;
use BarcodeBakery\Common\BCGParseException;

class BCGupcext5 extends BCGBarcode1D
{
    protected array $codeParity = array();

    /**
     * Creates a UPC supplemental 5 digits barcode.
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

        // Parity, 0=Odd, 1=Even. Depending Checksum
        $this->codeParity = array(
            array(1, 1, 0, 0, 0),   /* 0 */
            array(1, 0, 1, 0, 0),   /* 1 */
            array(1, 0, 0, 1, 0),   /* 2 */
            array(1, 0, 0, 0, 1),   /* 3 */
            array(0, 1, 1, 0, 0),   /* 4 */
            array(0, 0, 1, 1, 0),   /* 5 */
            array(0, 0, 0, 1, 1),   /* 6 */
            array(0, 1, 0, 1, 0),   /* 7 */
            array(0, 1, 0, 0, 1),   /* 8 */
            array(0, 0, 1, 0, 1)    /* 9 */
        );
    }

    /**
     * Draws the barcode.
     *
     * @param resource $image The surface.
     * @return void
     */
    public function draw($image): void
    {
        // Checksum
        $this->calculateChecksum();

        // Starting Code
        $this->drawChar($image, '001', true);

        // Code
        for ($i = 0; $i < 5; $i++) {
            $this->drawChar($image, self::inverse($this->findCode($this->text[$i]), $this->codeParity[$this->checksumValue[0]][$i]), false);
            if ($i < 4) {
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
        $textlength = 5 * 7;
        $intercharlength = 2 * 4;

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
            throw new BCGParseException('upcext5', 'No data has been entered.');
        }

        // Checking if all chars are allowed
        for ($i = 0; $i < $c; $i++) {
            if (array_search($this->text[$i], $this->keys) === false) {
                throw new BCGParseException('upcext5', 'The character \'' . $this->text[$i] . '\' is not allowed.');
            }
        }

        // Must contain 5 digits
        if ($c !== 5) {
            throw new BCGParseException('upcext5', 'Must contain 5 digits.');
        }

        parent::validate();
    }

    /**
     * Overloaded method to calculate checksum.
     *
     * @return void
     */
    protected function calculateChecksum(): void
    {
        // Calculating Checksum
        // Consider the right-most digit of the message to be in an "odd" position,
        // and assign odd/even to each character moving from right to left
        // Odd Position = 3, Even Position = 9
        // Multiply it by the number
        // Add all of that and do ?mod10
        $odd = true;
        $this->checksumValue = array(0);
        $c = strlen($this->text);
        for ($i = $c; $i > 0; $i--) {
            if ($odd === true) {
                $multiplier = 3;
                $odd = false;
            } else {
                $multiplier = 9;
                $odd = true;
            }

            if (!isset($this->keys[$this->text[$i - 1]])) {
                return;
            }

            $this->checksumValue[0] += $this->keys[$this->text[$i - 1]] * $multiplier;
        }

        $this->checksumValue[0] = $this->checksumValue[0] % 10;
    }

    /**
     * Overloaded method to display the checksum.
     *
     * @return string|null The checksum value.
     */
    protected function processChecksum(): ?string
    {
        if ($this->checksumValue === null) { // Calculate the checksum only once
            $this->calculateChecksum();
        }

        if ($this->checksumValue !== null) {
            return $this->keys[$this->checksumValue[0]];
        }

        return null;
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
