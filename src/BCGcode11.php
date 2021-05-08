<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Sub-Class - Code 11
 *
 *--------------------------------------------------------------------
 * Copyright (C) Jean-Sebastien Goupil
 * http://www.barcodebakery.com
 */
namespace BarcodeBakery\Barcode;

use BarcodeBakery\Common\BCGBarcode1D;
use BarcodeBakery\Common\BCGParseException;

class BCGcode11 extends BCGBarcode1D
{
    /**
     * Creates a Code 11 barcode.
     */
    public function __construct()
    {
        parent::__construct();

        $this->keys = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '-');
        $this->code = array(    // 0 added to add an extra space
            '000010',   /* 0 */
            '100010',   /* 1 */
            '010010',   /* 2 */
            '110000',   /* 3 */
            '001010',   /* 4 */
            '101000',   /* 5 */
            '011000',   /* 6 */
            '000110',   /* 7 */
            '100100',   /* 8 */
            '100000',   /* 9 */
            '001000'    /* - */
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
        // Starting Code
        $this->drawChar($image, '001100', true);

        // Chars
        $c = strlen($this->text);
        for ($i = 0; $i < $c; $i++) {
            $this->drawChar($image, $this->findCode($this->text[$i]), true);
        }

        // Checksum
        $this->calculateChecksum();
        $c = count($this->checksumValue);
        for ($i = 0; $i < $c; $i++) {
            $this->drawChar($image, $this->code[$this->checksumValue[$i]], true);
        }

        // Ending Code
        $this->drawChar($image, '00110', true);
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
        $startlength = 8;

        $textlength = 0;
        $c = strlen($this->text);
        for ($i = 0; $i < $c; $i++) {
            $textlength += $this->getIndexLength($this->findIndex($this->text[$i]));
        }

        $checksumlength = 0;
        $this->calculateChecksum();
        $c = count($this->checksumValue);
        for ($i = 0; $i < $c; $i++) {
            $checksumlength += $this->getIndexLength($this->checksumValue[$i]);
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
            throw new BCGParseException('code11', 'No data has been entered.');
        }

        // Checking if all chars are allowed
        for ($i = 0; $i < $c; $i++) {
            if (array_search($this->text[$i], $this->keys) === false) {
                throw new BCGParseException('code11', 'The character \'' . $this->text[$i] . '\' is not allowed.');
            }
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
        // Checksum
        // First CheckSUM "C"
        // The "C" checksum character is the modulo 11 remainder of the sum of the weighted
        // value of the data characters. The weighting value starts at "1" for the right-most
        // data character, 2 for the second to last, 3 for the third-to-last, and so on up to 20.
        // After 10, the sequence wraps around back to 1.

        // Second CheckSUM "K"
        // Same as CheckSUM "C" but we count the CheckSum "C" at the end
        // After 9, the sequence wraps around back to 1.
        $sequenceMultiplier = array(10, 9);
        $tempText = $this->text;
        $this->checksumValue = array();
        for ($z = 0; $z < 2; $z++) {
            $c = strlen($tempText);

            // We don't display the K CheckSum if the original text had a length less than 10
            if ($c <= 10 && $z === 1) {
                break;
            }

            $checksum = 0;
            for ($i = $c, $j = 0; $i > 0; $i--, $j++) {
                $multiplier = $i % $sequenceMultiplier[$z];
                if ($multiplier === 0) {
                    $multiplier = $sequenceMultiplier[$z];
                }

                $checksum += $this->findIndex($tempText[$j]) * $multiplier;
            }

            $this->checksumValue[$z] = $checksum % 11;
            $tempText .= $this->keys[$this->checksumValue[$z]];
        }
    }

    /**
     * Overloaded method to display the checksum.
     *
     * @return string|null The checksum value.
     */
    protected function processChecksum(): ?string
    {
        if ($this->checksumValue === false) { // Calculate the checksum only once
            $this->calculateChecksum();
        }

        if ($this->checksumValue !== false) {
            $ret = '';
            $c = count($this->checksumValue);
            for ($i = 0; $i < $c; $i++) {
                $ret .= $this->keys[$this->checksumValue[$i]];
            }

            return $ret;
        }

        return null;
    }

    private function getIndexLength(int $index): int
    {
        $length = 0;
        if ($index !== false) {
            $length += 6;
            $length += substr_count($this->code[$index], '1');
        }

        return $length;
    }
}
