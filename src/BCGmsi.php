<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Sub-Class - MSI Plessey
 *
 *--------------------------------------------------------------------
 * Copyright (C) Jean-Sebastien Goupil
 * http://www.barcodebakery.com
 */
namespace BarcodeBakery\Barcode;

use BarcodeBakery\Common\BCGArgumentException;
use BarcodeBakery\Common\BCGBarcode1D;
use BarcodeBakery\Common\BCGParseException;

class BCGmsi extends BCGBarcode1D
{
    private int $checksum;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->keys = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        $this->code = array(
            '01010101',     /* 0 */
            '01010110',     /* 1 */
            '01011001',     /* 2 */
            '01011010',     /* 3 */
            '01100101',     /* 4 */
            '01100110',     /* 5 */
            '01101001',     /* 6 */
            '01101010',     /* 7 */
            '10010101',     /* 8 */
            '10010110'      /* 9 */
        );

        $this->setChecksum(0);
    }

    /**
     * Sets how many checksums we display. 0 to 2.
     *
     * @param int $checksum The amount of checksums.
     * @return void
     */
    public function setChecksum(int $checksum): void
    {
        $checksum = intval($checksum);
        if ($checksum < 0 && $checksum > 2) {
            throw new BCGArgumentException('The checksum must be between 0 and 2 included.', 'checksum');
        }

        $this->checksum = $checksum;
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
        $this->drawChar($image, '10', true);

        // Chars
        $c = strlen($this->text);
        for ($i = 0; $i < $c; $i++) {
            $this->drawChar($image, $this->findCode($this->text[$i]), true);
        }

        $c = count($this->checksumValue);
        for ($i = 0; $i < $c; $i++) {
            $this->drawChar($image, $this->findCode($this->checksumValue[$i]), true);
        }

        // Ending Code
        $this->drawChar($image, '010', true);
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
        $textlength = 12 * strlen($this->text);
        $startlength = 3;
        $checksumlength = $this->checksum * 12;
        $endlength = 4;

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
            throw new BCGParseException('msi', 'No data has been entered.');
        }

        // Checking if all chars are allowed
        for ($i = 0; $i < $c; $i++) {
            if (array_search($this->text[$i], $this->keys) === false) {
                throw new BCGParseException('msi', 'The character \'' . $this->text[$i] . '\' is not allowed.');
            }
        }
    }

    /**
     * Overloaded method to calculate checksum.
     *
     * @return void
     */
    protected function calculateChecksum(): void
    {
        // Forming a new number
        // If the original number is even, we take all even position
        // If the original number is odd, we take all odd position
        // 123456 = 246
        // 12345 = 135
        // Multiply by 2
        // Add up all the digit in the result (270 : 2+7+0)
        // Add up other digit not used.
        // 10 - (? Modulo 10). If result = 10, change to 0
        $lastText = $this->text;
        $this->checksumValue = array();
        for ($i = 0; $i < $this->checksum; $i++) {
            $newText = '';
            $newNumber = 0;
            $c = strlen($lastText);
            if ($c % 2 === 0) { // Even
                $starting = 1;
            } else {
                $starting = 0;
            }

            for ($j = $starting; $j < $c; $j += 2) {
                $newText .= $lastText[$j];
            }

            $newText = strval(intval($newText) * 2);
            $c2 = strlen($newText);
            for ($j = 0; $j < $c2; $j++) {
                $newNumber += intval($newText[$j]);
            }

            for ($j = ($starting === 0) ? 1 : 0; $j < $c; $j += 2) {
                $newNumber += intval($lastText[$j]);
            }

            $newNumber = (10 - $newNumber % 10) % 10;
            $this->checksumValue[] = $newNumber;
            $lastText .= $newNumber;
        }
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
            $ret = '';
            $c = count($this->checksumValue);
            for ($i = 0; $i < $c; $i++) {
                $ret .= $this->keys[$this->checksumValue[$i]];
            }

            return $ret;
        }

        return null;
    }
}
