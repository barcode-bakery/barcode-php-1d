<?php

declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Sub-Class - Codabar
 *
 *--------------------------------------------------------------------
 * Copyright (C) Jean-Sebastien Goupil
 * http://www.barcodebakery.com
 */

namespace BarcodeBakery\Barcode;

use BarcodeBakery\Common\BCGBarcode1D;
use BarcodeBakery\Common\BCGParseException;

class BCGcodabar extends BCGBarcode1D
{
    /**
     * Creates a Codabar barcode.
     */
    public function __construct()
    {
        parent::__construct();

        $this->keys = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '-', '$', ':', '/', '.', '+', 'A', 'B', 'C', 'D'];
        $this->code = [    // 0 added to add an extra space
            '00000110',     /* 0 */
            '00001100',     /* 1 */
            '00010010',     /* 2 */
            '11000000',     /* 3 */
            '00100100',     /* 4 */
            '10000100',     /* 5 */
            '01000010',     /* 6 */
            '01001000',     /* 7 */
            '01100000',     /* 8 */
            '10010000',     /* 9 */
            '00011000',     /* - */
            '00110000',     /* $ */
            '10001010',     /* : */
            '10100010',     /* / */
            '10101000',     /* . */
            '00111110',     /* + */
            '00110100',     /* A */
            '01010010',     /* B */
            '00010110',     /* C */
            '00011100'      /* D */
        ];
    }

    /**
     * Parses the text before displaying it.
     *
     * @param mixed $text The text.
     * @return void
     */
    #[\Override]
    public function parse(mixed $text): void
    {
        parent::parse(strtoupper($text));    // Only Capital Letters are Allowed
    }

    /**
     * Draws the barcode.
     *
     * @param \GdImage $image The surface.
     * @return void
     */
    #[\Override]
    public function draw(\GdImage $image): void
    {
        $c = strlen($this->text);
        for ($i = 0; $i < $c; $i++) {
            $this->drawChar($image, $this->findCode($this->text[$i]), true);
        }

        $this->drawText($image, 0, 0, $this->positionX, $this->thickness);
    }

    /**
     * Returns the maximal size of a barcode.
     *
     * @param int $width The width.
     * @param int $height The height.
     * @return array{int, int} An array, [0] being the width, [1] being the height.
     */
    #[\Override]
    public function getDimension(int $width, int $height): array
    {
        $textLength = 0;
        $c = strlen($this->text);
        for ($i = 0; $i < $c; $i++) {
            $index = $this->findIndex($this->text[$i]);
            if ($index !== false) {
                $textLength += 8;
                $textLength += substr_count($this->code[$index], '1');
            }
        }

        $width += $textLength;
        $height += $this->thickness;
        return parent::getDimension($width, $height);
    }

    /**
     * Validates the input.
     *
     * @return void
     */
    #[\Override]
    protected function validate(): void
    {
        $c = strlen($this->text);
        if ($c === 0) {
            throw new BCGParseException('codabar', 'No data has been entered.');
        }

        // Checking if all chars are allowed
        for ($i = 0; $i < $c; $i++) {
            if (!in_array($this->text[$i], $this->keys, true)) {
                throw new BCGParseException('codabar', 'The character \'' . $this->text[$i] . '\' is not allowed.');
            }
        }

        // Must start by A, B, C or D
        if ($c === 0 || !in_array($this->text[0], ['A', 'B', 'C', 'D'], true)) {
            throw new BCGParseException('codabar', 'The text must start by the character A, B, C, or D.');
        }

        // Must end by A, B, C or D
        $c2 = $c - 1;
        if ($c2 === 0 || !in_array($this->text[$c2], ['A', 'B', 'C', 'D'], true)) {
            throw new BCGParseException('codabar', 'The text must end by the character A, B, C, or D.');
        }

        parent::validate();
    }
}
