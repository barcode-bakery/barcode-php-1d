<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Sub-Class - Code 39
 *
 *--------------------------------------------------------------------
 * Copyright (C) Jean-Sebastien Goupil
 * http://www.barcodebakery.com
 */
namespace BarcodeBakery\Barcode;

use BarcodeBakery\Common\BCGBarcode1D;
use BarcodeBakery\Common\BCGParseException;

class BCGcode39 extends BCGBarcode1D
{
    protected int $starting;
    protected int $ending;
    protected bool $checksum;

    /**
     * Creates a Code 39 barcode.
     */
    public function __construct()
    {
        parent::__construct();

        $this->starting = $this->ending = 43;
        $this->keys = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '-', '.', ' ', '$', '/', '+', '%', '*');
        $this->code = array(    // 0 added to add an extra space
            '0001101000',   /* 0 */
            '1001000010',   /* 1 */
            '0011000010',   /* 2 */
            '1011000000',   /* 3 */
            '0001100010',   /* 4 */
            '1001100000',   /* 5 */
            '0011100000',   /* 6 */
            '0001001010',   /* 7 */
            '1001001000',   /* 8 */
            '0011001000',   /* 9 */
            '1000010010',   /* A */
            '0010010010',   /* B */
            '1010010000',   /* C */
            '0000110010',   /* D */
            '1000110000',   /* E */
            '0010110000',   /* F */
            '0000011010',   /* G */
            '1000011000',   /* H */
            '0010011000',   /* I */
            '0000111000',   /* J */
            '1000000110',   /* K */
            '0010000110',   /* L */
            '1010000100',   /* M */
            '0000100110',   /* N */
            '1000100100',   /* O */
            '0010100100',   /* P */
            '0000001110',   /* Q */
            '1000001100',   /* R */
            '0010001100',   /* S */
            '0000101100',   /* T */
            '1100000010',   /* U */
            '0110000010',   /* V */
            '1110000000',   /* W */
            '0100100010',   /* X */
            '1100100000',   /* Y */
            '0110100000',   /* Z */
            '0100001010',   /* - */
            '1100001000',   /* . */
            '0110001000',   /*   */
            '0101010000',   /* $ */
            '0101000100',   /* / */
            '0100010100',   /* + */
            '0001010100',   /* % */
            '0100101000'    /* * */
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
     * Parses the text before displaying it.
     *
     * @param string $text The text.
     * @return void
     */
    public function parse($text): void
    {
        parent::parse(strtoupper($text));    // Only Capital Letters are Allowed
    }

    /**
     * Draws the barcode.
     *
     * @param resource $image The surface.
     * @return void
     */
    public function draw($image): void
    {
        // Starting *
        $this->drawChar($image, $this->code[$this->starting], true);

        // Chars
        $c =  strlen($this->text);
        for ($i = 0; $i < $c; $i++) {
            $this->drawChar($image, $this->findCode($this->text[$i]), true);
        }

        // Checksum (rarely used)
        if ($this->checksum === true) {
            $this->calculateChecksum();
            $this->drawChar($image, $this->code[$this->checksumValue[0] % 43], true);
        }

        // Ending *
        $this->drawChar($image, $this->code[$this->ending], true);
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
        $textlength = 13 * strlen($this->text);
        $startlength = 13;
        $checksumlength = 0;
        if ($this->checksum === true) {
            $checksumlength = 13;
        }

        $endlength = 13;

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
            throw new BCGParseException('code39', 'No data has been entered.');
        }

        // Checking if all chars are allowed
        for ($i = 0; $i < $c; $i++) {
            if (array_search($this->text[$i], $this->keys) === false) {
                throw new BCGParseException('code39', 'The character \'' . $this->text[$i] . '\' is not allowed.');
            }
        }

        if (strpos($this->text, '*') !== false) {
            throw new BCGParseException('code39', 'The character \'*\' is not allowed.');
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
        $this->checksumValue = array(0);
        $c = strlen($this->text);
        for ($i = 0; $i < $c; $i++) {
            $this->checksumValue[0] += $this->findIndex($this->text[$i]);
        }

        $this->checksumValue[0] = $this->checksumValue[0] % 43;
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
