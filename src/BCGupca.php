<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Sub-Class - UPC-A
 *
 * UPC-A contains
 *    - 2 system digits (1 not provided, a 0 is added automatically)
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

class BCGupca extends BCGean13
{
    protected ?BCGLabel $labelRight = null;

    /**
     * Creates a UPC-A barcode.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Draws the barcode.
     *
     * @param resource $image The surface.
     * @return void
     */
    public function draw($image): void
    {
        // The following code is exactly the same as EAN13. We just add a 0 in front of the code !
        $this->text = '0' . $this->text; // We will remove it at the end... don't worry

        parent::draw($image);

        // We remove the 0 in front, as we said :)
        $this->text = substr($this->text, 1);
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
        $tempText = $this->text . $this->keys[$this->checksumValue[0]];
        $rememberX = $this->positionX;
        $rememberH = $this->thickness;

        // We increase the bars
        // First 2 Bars
        $this->thickness = $this->thickness + intval($plus / $this->scale);
        $this->positionX = 0;
        $this->drawSingleBar($image, BCGBarcode::COLOR_FG);
        $this->positionX += 2;
        $this->drawSingleBar($image, BCGBarcode::COLOR_FG);

        // Attemping to increase the 2 following bars
        $this->positionX += 1;
        $tempValue = $this->findCode($tempText[1]);
        $this->drawChar($image, $tempValue, false);

        // Center Guard Bar
        $this->positionX += 36;
        $this->drawSingleBar($image, BCGBarcode::COLOR_FG);
        $this->positionX += 2;
        $this->drawSingleBar($image, BCGBarcode::COLOR_FG);

        // Attemping to increase the 2 last bars
        $this->positionX += 37;
        $tempValue = $this->findCode($tempText[12]);
        $this->drawChar($image, $tempValue, true);

        // Completly last bars
        $this->drawSingleBar($image, BCGBarcode::COLOR_FG);
        $this->positionX += 2;
        $this->drawSingleBar($image, BCGBarcode::COLOR_FG);

        $this->positionX = $rememberX;
        $this->thickness = $rememberH;
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

            $this->labelCenter1 = new BCGLabel(substr($label, 1, 5), $font, BCGLabel::POSITION_BOTTOM, BCGLabel::ALIGN_LEFT);
            $labelCenter1Dimension = $this->labelCenter1->getDimension();
            $this->labelCenter1->setOffset((int)(($this->scale * 44 - $labelCenter1Dimension[0]) / 2 + $this->scale * 6));

            $this->labelCenter2 = new BCGLabel(substr($label, 6, 5), $font, BCGLabel::POSITION_BOTTOM, BCGLabel::ALIGN_LEFT);
            $this->labelCenter2->setOffset((int)(($this->scale * 44 - $labelCenter1Dimension[0]) / 2 + $this->scale * 45));

            $this->labelRight = new BCGLabel($this->keys[$this->checksumValue[0]], $font, BCGLabel::POSITION_RIGHT, BCGLabel::ALIGN_BOTTOM);
            $this->labelRight->setSpacing(4 * $this->scale);

            if ($this->alignLabel) {
                $labelDimension = $this->labelCenter1->getDimension();
                $this->labelLeft->setOffset($labelDimension[1]);
                $this->labelRight->setOffset($labelDimension[1]);
            } else {
                $labelDimension = $this->labelLeft->getDimension();
                $this->labelLeft->setOffset((int)($labelDimension[1] / 2));
                $labelDimension = $this->labelLeft->getDimension();
                $this->labelRight->setOffset((int)($labelDimension[1] / 2));
            }

            $this->addLabel($this->labelLeft);
            $this->addLabel($this->labelCenter1);
            $this->addLabel($this->labelCenter2);
            $this->addLabel($this->labelRight);
        }
    }

    /**
     * Check correct length.
     *
     * @return void
     */
    protected function checkCorrectLength(): void
    {
        // If we have 12 chars, just flush the last one without throwing anything
        $c = strlen($this->text);
        if ($c === 12) {
            $this->text = substr($this->text, 0, 11);
        } elseif ($c !== 11) {
            throw new BCGParseException('upca', 'Must contain 11 digits, the 12th digit is automatically added.');
        }
    }
}
