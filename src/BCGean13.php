<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Sub-Class - EAN-13
 *
 * EAN-13 contains
 *    - 2 system digits (1 not displayed but coded with parity)
 *    - 5 manufacturer code digits
 *    - 5 product digits
 *    - 1 checksum digit
 *
 * The checksum is always displayed.
 *
 *--------------------------------------------------------------------
 * Copyright (C) Jean-Sebastien Goupil
 * http://www.barcodebakery.com
 */
namespace BarcodeBakery\Barcode;

use BarcodeBakery\Common\BCGBarcode;
use BarcodeBakery\Common\BCGBarcode1D;
use BarcodeBakery\Common\BCGLabel;
use BarcodeBakery\Common\BCGParseException;

class BCGean13 extends BCGBarcode1D
{
    protected array $codeParity = array();
    protected ?BCGLabel $labelLeft = null;
    protected ?BCGLabel $labelCenter1 = null;
    protected ?BCGLabel $labelCenter2 = null;
    protected bool $alignLabel;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->keys = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');

        // Left-Hand Odd Parity starting with a space
        // Left-Hand Even Parity is the inverse (0=0012) starting with a space
        // Right-Hand is the same of Left-Hand starting with a bar
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

        // Parity, 0=Odd, 1=Even for manufacturer code. Depending on 1st System Digit
        $this->codeParity = array(
            array(0, 0, 0, 0, 0),   /* 0 */
            array(0, 1, 0, 1, 1),   /* 1 */
            array(0, 1, 1, 0, 1),   /* 2 */
            array(0, 1, 1, 1, 0),   /* 3 */
            array(1, 0, 0, 1, 1),   /* 4 */
            array(1, 1, 0, 0, 1),   /* 5 */
            array(1, 1, 1, 0, 0),   /* 6 */
            array(1, 0, 1, 0, 1),   /* 7 */
            array(1, 0, 1, 1, 0),   /* 8 */
            array(1, 1, 0, 1, 0)    /* 9 */
        );

        $this->alignDefaultLabel(true);
    }

    /**
     * Aligns the default label.
     *
     * @param bool $align Aligns the label.
     * @return void
     */
    public function alignDefaultLabel($align): void
    {
        $this->alignLabel = (bool)$align;
    }

    /**
     * Draws the barcode.
     *
     * @param resource $image The surface.
     * @return void
     */
    public function draw($image): void
    {
        $this->drawBars($image);
        $this->drawText($image, 0, 0, $this->positionX, $this->thickness);

        if ($this->isDefaultEanLabelEnabled()) {
            $dimension = $this->labelCenter1->getDimension();
            $this->drawExtendedBars($image, $dimension[1] - 2);
        }
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
        $startlength = 3;
        $centerlength = 5;
        $textlength = 12 * 7;
        $endlength = 3;

        $width += $startlength + $centerlength + $textlength + $endlength;
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
        if ($this->isDefaultEanLabelEnabled()) {
            $this->processChecksum();
            $label = $this->getLabel();
            $font = $this->font;

            $this->labelLeft = new BCGLabel(substr($label, 0, 1), $font, BCGLabel::POSITION_LEFT, BCGLabel::ALIGN_BOTTOM);
            $this->labelLeft->setSpacing(4 * $this->scale);

            $this->labelCenter1 = new BCGLabel(substr($label, 1, 6), $font, BCGLabel::POSITION_BOTTOM, BCGLabel::ALIGN_LEFT);
            $labelCenter1Dimension = $this->labelCenter1->getDimension();
            $this->labelCenter1->setOffset((int)(($this->scale * 44 - $labelCenter1Dimension[0]) / 2 + $this->scale * 2));

            $this->labelCenter2 = new BCGLabel(substr($label, 7, 5) . $this->keys[$this->checksumValue[0]], $font, BCGLabel::POSITION_BOTTOM, BCGLabel::ALIGN_LEFT);
            $this->labelCenter2->setOffset((int)(($this->scale * 44 - $labelCenter1Dimension[0]) / 2 + $this->scale * 48));

            if ($this->alignLabel) {
                $labelDimension = $this->labelCenter1->getDimension();
                $this->labelLeft->setOffset($labelDimension[1]);
            } else {
                $labelDimension = $this->labelLeft->getDimension();
                $this->labelLeft->setOffset((int)($labelDimension[1] / 2));
            }

            $this->addLabel($this->labelLeft);
            $this->addLabel($this->labelCenter1);
            $this->addLabel($this->labelCenter2);
        }
    }

    /**
     * Checks if the default ean label is enabled.
     *
     * @return bool True if default label is enabled.
     */
    protected function isDefaultEanLabelEnabled(): bool
    {
        $label = $this->getLabel();
        $font = $this->font;
        return $label !== null && $label !== '' && $font !== null && $this->defaultLabel !== null;
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
            throw new BCGParseException('ean13', 'No data has been entered.');
        }

        $this->checkCharsAllowed();
        $this->checkCorrectLength();

        parent::validate();
    }

    /**
     * Check chars allowed.
     *
     * @return void
     */
    protected function checkCharsAllowed(): void
    {
        // Checking if all chars are allowed
        $c = strlen($this->text);
        for ($i = 0; $i < $c; $i++) {
            if (array_search($this->text[$i], $this->keys) === false) {
                throw new BCGParseException('ean13', 'The character \'' . $this->text[$i] . '\' is not allowed.');
            }
        }
    }

    /**
     * Check correct length.
     *
     * @return void
     */
    protected function checkCorrectLength(): void
    {
        // If we have 13 chars, just flush the last one without throwing anything
        $c = strlen($this->text);
        if ($c === 13) {
            $this->text = substr($this->text, 0, 12);
        } elseif ($c !== 12) {
            throw new BCGParseException('ean13', 'Must contain 12 digits, the 13th digit is automatically added.');
        }
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
        // Odd Position = 3, Even Position = 1
        // Multiply it by the number
        // Add all of that and do 10-(?mod10)
        $odd = true;
        $this->checksumValue = array(0);
        $c = strlen($this->text);
        for ($i = $c; $i > 0; $i--) {
            if ($odd === true) {
                $multiplier = 3;
                $odd = false;
            } else {
                $multiplier = 1;
                $odd = true;
            }

            if (!isset($this->keys[$this->text[$i - 1]])) {
                return;
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
     * Draws the bars.
     *
     * @param resource $image The surface.
     * @return void
     */
    protected function drawBars($image): void
    {
        // Checksum
        $this->calculateChecksum();
        $tempText = $this->text . $this->keys[$this->checksumValue[0]];

        // Starting Code
        $this->drawChar($image, '000', true);

        // Draw Second Code
        $this->drawChar($image, $this->findCode($tempText[1]), false);

        // Draw Manufacturer Code
        for ($i = 0; $i < 5; $i++) {
            $this->drawChar($image, self::inverse($this->findCode($tempText[$i + 2]), $this->codeParity[(int)$tempText[0]][$i]), false);
        }

        // Draw Center Guard Bar
        $this->drawChar($image, '00000', false);

        // Draw Product Code
        for ($i = 7; $i < 13; $i++) {
            $this->drawChar($image, $this->findCode($tempText[$i]), true);
        }

        // Draw Right Guard Bar
        $this->drawChar($image, '000', true);
    }

    /**
     * Draws the extended bars on the image.
     *
     * @param resource $image The surface.
     * @param int $plus How much more we should display the bars.
     * @return void
     */
    protected function drawExtendedBars($image, int $plus): void
    {
        $rememberX = $this->positionX;
        $rememberH = $this->thickness;

        // We increase the bars
        $this->thickness = $this->thickness + intval($plus / $this->scale);
        $this->positionX = 0;
        $this->drawSingleBar($image, BCGBarcode::COLOR_FG);
        $this->positionX += 2;
        $this->drawSingleBar($image, BCGBarcode::COLOR_FG);

        // Center Guard Bar
        $this->positionX += 44;
        $this->drawSingleBar($image, BCGBarcode::COLOR_FG);
        $this->positionX += 2;
        $this->drawSingleBar($image, BCGBarcode::COLOR_FG);

        // Last Bars
        $this->positionX += 44;
        $this->drawSingleBar($image, BCGBarcode::COLOR_FG);
        $this->positionX += 2;
        $this->drawSingleBar($image, BCGBarcode::COLOR_FG);

        $this->positionX = $rememberX;
        $this->thickness = $rememberH;
    }

    /**
     * Inverses the string when the $inverse parameter is equal to 1.
     *
     * @param string $text The text.
     * @param int $inverse The inverse.
     * @return string The reversed string.
     */
    private static function inverse(string $text, int $inverse = 1): string
    {
        if ($inverse === 1) {
            $text = strrev($text);
        }

        return $text;
    }
}
