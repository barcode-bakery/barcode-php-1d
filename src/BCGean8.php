<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Sub-Class - EAN-8
 *
 * EAN-8 contains
 *    - 4 digits
 *    - 3 digits
 *    - 1 checksum
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

class BCGean8 extends BCGBarcode1D
{
    protected ?BCGLabel $labelLeft = null;
    protected ?BCGLabel $labelRight = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->keys = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');

        // Left-Hand Odd Parity starting with a space
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
    }

    /**
     * Draws the barcode.
     *
     * @param resource $image The surface.
     */
    public function draw($image): void
    {
        // Checksum
        $this->calculateChecksum();
        $tempText = $this->text . $this->keys[$this->checksumValue[0]];

        // Starting Code
        $this->drawChar($image, '000', true);

        // Draw First 4 Chars (Left-Hand)
        for ($i = 0; $i < 4; $i++) {
            $this->drawChar($image, $this->findCode($tempText[$i]), false);
        }

        // Draw Center Guard Bar
        $this->drawChar($image, '00000', false);

        // Draw Last 4 Chars (Right-Hand)
        for ($i = 4; $i < 8; $i++) {
            $this->drawChar($image, $this->findCode($tempText[$i]), true);
        }

        // Draw Right Guard Bar
        $this->drawChar($image, '000', true);
        $this->drawText($image, 0, 0, $this->positionX, $this->thickness);

        if ($this->isDefaultEanLabelEnabled()) {
            $dimension = $this->labelRight->getDimension();
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
        $textlength = 8 * 7;
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

            $this->labelLeft = new BCGLabel(substr($label, 0, 4), $font, BCGLabel::POSITION_BOTTOM, BCGLabel::ALIGN_LEFT);
            $labelLeftDimension = $this->labelLeft->getDimension();
            $this->labelLeft->setOffset((int)(($this->scale * 30 - $labelLeftDimension[0]) / 2 + $this->scale * 2));

            $this->labelRight = new BCGLabel(substr($label, 4, 3) . $this->keys[$this->checksumValue[0]], $font, BCGLabel::POSITION_BOTTOM, BCGLabel::ALIGN_LEFT);
            $labelRightDimension = $this->labelRight->getDimension();
            $this->labelRight->setOffset((int)(($this->scale * 30 - $labelRightDimension[0]) / 2 + $this->scale * 34));

            $this->addLabel($this->labelLeft);
            $this->addLabel($this->labelRight);
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
            throw new BCGParseException('ean8', 'No data has been entered.');
        }

        // Checking if all chars are allowed
        for ($i = 0; $i < $c; $i++) {
            if (array_search($this->text[$i], $this->keys) === false) {
                throw new BCGParseException('ean8', 'The character \'' . $this->text[$i] . '\' is not allowed.');
            }
        }

        // If we have 8 chars just flush the last one
        if ($c === 8) {
            $this->text = substr($this->text, 0, 7);
        } elseif ($c !== 7) {
            throw new BCGParseException('ean8', 'Must contain 7 digits, the 8th digit is automatically added.');
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
     * Draws the extended bars on the image.
     *
     * @param resource $image The surface.
     * @param int $plus How much more we should display the bars.
     * @return void
     */
    private function drawExtendedBars($image, int $plus): void
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
        $this->positionX += 30;
        $this->drawSingleBar($image, BCGBarcode::COLOR_FG);
        $this->positionX += 2;
        $this->drawSingleBar($image, BCGBarcode::COLOR_FG);

        // Last Bars
        $this->positionX += 30;
        $this->drawSingleBar($image, BCGBarcode::COLOR_FG);
        $this->positionX += 2;
        $this->drawSingleBar($image, BCGBarcode::COLOR_FG);

        $this->positionX = $rememberX;
        $this->thickness = $rememberH;
    }
}
