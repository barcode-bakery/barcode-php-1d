<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Sub-Class - Interleaved 2 of 5
 *
 *--------------------------------------------------------------------
 * Copyright (C) Jean-Sebastien Goupil
 * http://www.barcodebakery.com
 */
namespace BarcodeBakery\Barcode;

use BarcodeBakery\Common\BCGBarcode1D;
use BarcodeBakery\Common\BCGParseException;

class BCGi25 extends BCGBarcode1D
{
    private bool $checksum;
    private int $ratio;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->keys = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        $this->code = array(
            '00110',    /* 0 */
            '10001',    /* 1 */
            '01001',    /* 2 */
            '11000',    /* 3 */
            '00101',    /* 4 */
            '10100',    /* 5 */
            '01100',    /* 6 */
            '00011',    /* 7 */
            '10010',    /* 8 */
            '01010'     /* 9 */
        );

        $this->setChecksum(false);
        $this->setRatio(2);
    }

    /**
     * Sets the checksum.
     *
     * @param bool $checksum Displays the checksum.
     * @return void
     */
    public function setChecksum(bool $checksum): void
    {
        $this->checksum = (bool)$checksum;
    }

    /**
     * Sets the ratio of the black bar compared to the white bars.
     *
     * @param int $ratio The ratio.
     * @return void
     */
    public function setRatio(int $ratio): void
    {
        $this->ratio = $ratio;
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
        $this->drawChar($image, '0000', true);

        // Chars
        $c = strlen($tempText);
        for ($i = 0; $i < $c; $i += 2) {
            $tempBar = '';
            $c2 = strlen($this->findCode($tempText[$i]));
            for ($j = 0; $j < $c2; $j++) {
                $tempBar .= substr($this->findCode($tempText[$i]), $j, 1);
                $tempBar .= substr($this->findCode($tempText[$i + 1]), $j, 1);
            }

            $this->drawChar($image, $this->changeBars($tempBar), true);
        }

        // Ending Code
        $this->drawChar($image, $this->changeBars('100'), true);
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
        $textlength = (3 + ($this->ratio + 1) * 2) * strlen($this->text);
        $startlength = 4;
        $checksumlength = 0;
        if ($this->checksum === true) {
            $checksumlength = (3 + ($this->ratio + 1) * 2);
        }

        $endlength = 2 + ($this->ratio + 1);

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
            throw new BCGParseException('i25', 'No data has been entered.');
        }

        // Checking if all chars are allowed
        for ($i = 0; $i < $c; $i++) {
            if (array_search($this->text[$i], $this->keys) === false) {
                throw new BCGParseException('i25', 'The character \'' . $this->text[$i] . '\' is not allowed.');
            }
        }

        // Must be even
        if ($c % 2 !== 0 && $this->checksum === false) {
            throw new BCGParseException('i25', 'i25 must contain an even amount of digits if checksum is false.');
        } elseif ($c % 2 === 0 && $this->checksum === true) {
            throw new BCGParseException('i25', 'i25 must contain an odd amount of digits if checksum is true.');
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

    /**
     * Changes the size of the bars based on the ratio
     *
     * @param string $in The bars.
     * @return string New bars.
     */
    private function changeBars(string $in): string
    {
        if ($this->ratio > 1) {
            $c = strlen($in);
            for ($i = 0; $i < $c; $i++) {
                $in[$i] = $in[$i] === '1' ? $this->ratio : $in[$i];
            }
        }

        return $in;
    }
}
