<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Sub-Class - Standard 2 of 5
 *
 * TODO I25 and S25 -> 1/3 or 1/2 for the big bar
 *
 *--------------------------------------------------------------------
 * Copyright (C) Jean-Sebastien Goupil
 * http://www.barcodebakery.com
 */
namespace BarcodeBakery\Barcode;

use BarcodeBakery\Common\BCGBarcode1D;
use BarcodeBakery\Common\BCGParseException;

class BCGs25 extends BCGBarcode1D
{
    private bool $checksum;

    /**
     * Creates a Standard 2 of 5 barcode.
     */
    public function __construct()
    {
        parent::__construct();

        $this->keys = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        $this->code = array(
            '0000202000',   /* 0 */
            '2000000020',   /* 1 */
            '0020000020',   /* 2 */
            '2020000000',   /* 3 */
            '0000200020',   /* 4 */
            '2000200000',   /* 5 */
            '0020200000',   /* 6 */
            '0000002020',   /* 7 */
            '2000002000',   /* 8 */
            '0020002000'    /* 9 */
        );

        $this->setChecksum(false);
    }

    /**
     * Sets if we display the checksum.
     *
     * @param bool $checksum Displays the checksum.
     * @return void
     */
    public function setChecksum(bool $checksum): void
    {
        $this->checksum = (bool)$checksum;
    }

    /**
     * Draws the barcode.
     *
     * @param resource $image The surface.
     * @return void
     */
    public function draw($image): void
    {
        $tempText = $this->text;

        // Checksum
        if ($this->checksum === true) {
            $this->calculateChecksum();
            $tempText .= $this->keys[$this->checksumValue[0]];
        }

        // Starting Code
        $this->drawChar($image, '101000', true);

        // Chars
        $c = strlen($tempText);
        for ($i = 0; $i < $c; $i++) {
            $this->drawChar($image, $this->findCode($tempText[$i]), true);
        }

        // Ending Code
        $this->drawChar($image, '10001', true);
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
        $c = strlen($this->text);
        $startlength = 8;
        $textlength = $c * 14;
        $checksumlength = 0;
        if ($c % 2 !== 0) {
            $checksumlength = 14;
        }

        $endlength = 7;

        $width += $startlength + $textlength + $checksumlength + $endlength;
        $height += $this->thickness;
        return parent::getDimension($width, $height);
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
            throw new BCGParseException('s25', 'No data has been entered.');
        }

        // Checking if all chars are allowed
        for ($i = 0; $i < $c; $i++) {
            if (array_search($this->text[$i], $this->keys) === false) {
                throw new BCGParseException('s25', 'The character \'' . $this->text[$i] . '\' is not allowed.');
            }
        }

        // Must be even
        if ($c % 2 !== 0 && $this->checksum === false) {
            throw new BCGParseException('s25', 's25 must contain an even amount of digits if checksum is false.');
        } elseif ($c % 2 === 0 && $this->checksum === true) {
            throw new BCGParseException('s25', 's25 must contain an odd amount of digits if checksum is true.');
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
        // Consider the right-most digit of the message to be in an "even" position,
        // and assign odd/even to each character moving from right to left
        // Even Position = 3, Odd Position = 1
        // Multiply it by the number
        // Add all of that and do 10-(?mod10)
        $even = true;
        $this->checksumValue = array(0);
        $c = strlen($this->text);
        for ($i = $c; $i > 0; $i--) {
            if ($even === true) {
                $multiplier = 3;
                $even = false;
            } else {
                $multiplier = 1;
                $even = true;
            }

            $this->checksumValue[0] += $this->keys[$this->text[$i - 1]] * $multiplier;
        }
        $this->checksumValue[0] = (10 - $this->checksumValue[0] % 10) % 10;
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
}
