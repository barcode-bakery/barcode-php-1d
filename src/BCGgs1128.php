<?php
declare(strict_types=1);

/**
 *--------------------------------------------------------------------
 *
 * Calculate the GS1-128 based on the Code-128 encoding.
 *
 *--------------------------------------------------------------------
 * Copyright (C) Jean-Sebastien Goupil
 * http://www.barcodebakery.com
 */
namespace BarcodeBakery\Barcode;

use BarcodeBakery\Common\BCGBarcode1D;
use BarcodeBakery\Common\BCGParseException;
use BarcodeBakery\Common\GS1\KindOfData;

class BCGgs1128 extends BCGcode128
{
    const ID = 0;
    const CONTENT = 1;
    const MAX_ID_FORMATTED = 6;
    const MAX_ID_NOT_FORMATTED = 4;
    const MAX_GS1128_CHARS = 48;

    private bool $strictMode;
    private bool $allowsUnknownIdentifier;
    private bool $noLengthLimit;
    private array $identifiersId = array();
    private array $identifiersContent = array();
    private ?array $identifiersAi = null;

    /**
     * Creates a GS1-128 barcode.
     *
     * @param string $start The start table.
     */
    public function __construct(?string $start = null)
    {
        if ($start === null) {
            $start = 'C';
        }

        parent::__construct($start);

        $this->setStrictMode(true);
        $this->setTilde(true);
        $this->setAllowsUnknownIdentifier(false);
        $this->setNoLengthLimit(false);
    }

    /**
     * Gets the content checksum for an identifier.
     * Do not pass the identifier code.
     *
     * @param string $content The content.
     * @return int The checksum.
     */
    public static function getAiContentChecksum(string $content): int
    {
        return self::calculateChecksumMod10($content);
    }

    /**
     * Enables or disables the strict mode.
     *
     * @param bool $strictMode Strict mode.
     * @return void
     */
    public function setStrictMode(bool $strictMode): void
    {
        $this->strictMode = $strictMode;
    }

    /**
     * Gets if the strict mode is activated.
     *
     * @return bool True if enabled.
     */
    public function getStrictMode(): bool
    {
        return $this->strictMode;
    }

    /**
     * Allows unknown identifiers.
     *
     * @param bool $allow Allows the unknown identifier.
     * @return void
     */
    public function setAllowsUnknownIdentifier(bool $allow): void
    {
        $this->allowsUnknownIdentifier = (bool)$allow;
    }

    /**
     * Gets if unkmown identifiers are allowed.
     *
     * @return bool True if enabled.
     */
    public function getAllowsUnknownIdentifier(): bool
    {
        return $this->allowsUnknownIdentifier;
    }

    /**
     * Removes the limit of 48 characters.
     *
     * @param bool $noLengthLimit No limit.
     * @return void
     */
    public function setNoLengthLimit(bool $noLengthLimit): void
    {
        $this->noLengthLimit = (bool)$noLengthLimit;
    }

    /**
     * Gets if the limit of 48 characters is removed.
     *
     * @return bool True if enabled.
     */
    public function getNoLengthLimit(): bool
    {
        return $this->noLengthLimit;
    }

    /**
     * Sets the list of application identifiers.
     *
     * @param AIData[] aiDatas Application identifiers.
     * @return void
     */
    public function setApplicationIdentifiers(array $aiDatas): void
    {
        // Using array_column will convert the keys to integer.
        $this->identifiersAi = array_column(array_map(function ($entry) {
            return array(0 => $entry->getAI(), 1 => $entry);
        }, $aiDatas), 1, 0);
    }

    /**
     * Gets the list of application identifiers.
     *
     * @return AIData[] Application Identifiers.
     */
    public function getApplicationIdentifiers(): array
    {
        return array_values($this->identifiersAi);
    }

    /**
     * Parses Text.
     *
     * @param mixed $text The text.
     * @return void
     */
    public function parse($text): void
    {
        $this->identifiersId = array();
        $this->identifiersContent = array();
        parent::parse($this->parseGs1128($text));
    }

    /**
     * Formats data for gs1-128.
     *
     * @return string Final formatted data.
     */
    private function formatGs1128(): string
    {
        $formattedText = '~F1';
        $formattedLabel = '';
        $c = count($this->identifiersId);

        for ($i = 0; $i < $c; $i++) {
            if ($i > 0) {
                $formattedLabel .= ' ';
            }

            if ($this->identifiersId[$i] !== null) {
                $formattedLabel .= '(' . $this->identifiersId[$i] . ')';
            }

            $formattedText .= $this->identifiersId[$i];

            $formattedLabel .= $this->identifiersContent[$i];
            $formattedText .= $this->identifiersContent[$i];

            if (isset($this->identifiersAi[$this->identifiersId[$i]])) {
                $aiData = $this->identifiersAi[$this->identifiersId[$i]];
            } elseif (isset($this->identifiersId[$i][3])) {
                $identifierWithVar = substr($this->identifiersId[$i], 0, -1) . 'y';
                $aiData = isset($this->identifiersAi[$identifierWithVar]) ? $this->identifiersAi[$identifierWithVar] : null;
            } else {
                $aiData = null;
            }

            /* We'll check if we need to add a ~F1 (<GS>) char */
            /* If we use the legacy mode, we always add a ~F1 (<GS>) char between AIs */
            if ($aiData !== null) {
                if ((strlen($this->identifiersContent[$i]) < $aiData->getMaxLength() && ($i + 1) !== $c) || (!$this->strictMode && ($i + 1) !== $c)) {
                    $formattedText .= '~F1';
                }
            } elseif ($this->allowsUnknownIdentifier && $this->identifiersId[$i] === null && ($i + 1) !== $c) {
                /* If this id is unknown, we add a ~F1 (<GS>) char */
                $formattedText .= '~F1';
            }
        }

        if ($this->noLengthLimit === false) {
            $calculableCharacters = str_replace('~F1', chr(29), $formattedText);
            $calculableCharacters = str_replace('(', '', $calculableCharacters);
            $calculableCharacters = str_replace(')', '', $calculableCharacters);

            if (strlen($calculableCharacters) - 1 > self::MAX_GS1128_CHARS) {
                throw new BCGParseException('gs1128', 'The barcode can\'t contain more than ' . self::MAX_GS1128_CHARS . ' characters.');
            }
        }

        if ($this->label === self::AUTO_LABEL) {
            $this->label = $formattedLabel;
        }

        return $formattedText;
    }

    /**
     * Parses the inputs.
     *
     * @param mixed $text The inputs.
     * @return string Final formatted data.
     */
    private function parseGs1128($text): ?string
    {
        /* We format correctly what the user gives */
        if (is_array($text)) {
            $formatArray = array();
            foreach ($text as $content) {
                if (is_array($content)) { /* double array */
                    if (count($content) === 2) {
                        if (is_array($content[self::ID]) || is_array($content[self::CONTENT])) {
                            throw new BCGParseException('gs1128', 'Double arrays can\'t contain arrays.');
                        } else {
                            $formatArray[] = '(' . $content[self::ID] . ')' . $content[self::CONTENT];
                        }
                    } else {
                        throw new BCGParseException('gs1128', 'Double arrays must contain 2 values.');
                    }
                } else { /* simple array */
                    $formatArray[] = $content;
                }
            }

            unset($text);
            $text = $formatArray;
        } else { /* string */
            $text = array($text);
        }

        $textCount = count($text);
        for ($cmpt = 0; $cmpt < $textCount; $cmpt++) {
            /* We parse the content of the array */
            if (!$this->parseContent($text[$cmpt])) {
                return null;
            }
        }

        return $this->formatGs1128();
    }

    /**
     * Splits the id and the content for each application identifiers (AIs).
     *
     * @param string $text The unformatted text.
     * @return bool True on success.
     */
    private function parseContent(string $text): bool
    {
        /* $yAlreadySet has 3 states: */
        /* null: There is no variable in the ID; true: the variable is already set; false: the variable is not set yet; */
        $content = null;
        $yAlreadySet = null;
        $realNameId = null;
        $separatorsFound = 0;
        $checksumAdded = 0;
        $decimalPointRemoved = 0;
        $toParse = str_replace('~F1', chr(29), $text);
        $nbCharToParse = strlen($toParse);
        $nbCharId = 0;
        $isFormatted = $toParse[0] === '(';
        $maxCharId = $isFormatted ? self::MAX_ID_FORMATTED : self::MAX_ID_NOT_FORMATTED;
        $id = strtolower(substr($toParse, 0, min($maxCharId, $nbCharToParse)));
        $id = $isFormatted ? $this->findIdFormatted($id, $yAlreadySet, $realNameId) : $this->findIdNotFormatted($id, $yAlreadySet, $realNameId);

        if ($id === null) {
            if ($this->allowsUnknownIdentifier === false) {
                return false;
            }

            $id = null;
            $nbCharId = 0;
            $content = $toParse;
        } else {
            $nbCharId = strlen($id) + ($isFormatted ? 2 : 0);
            $n = min($this->identifiersAi[$realNameId]->getMaxLength(), $nbCharToParse);
            $content = substr($toParse, $nbCharId, $n);

            if ($id !== null) {
                /* If we have an AI with an "y" var, we check if there is a decimal point in the next *MAXLENGTH* characters */
                /* if there is one, we take an extra character */
                if ($yAlreadySet !== null) {
                    if (strpos($content, '.') !== false || strpos($content, ',') !== false) {
                        $n++;
                        if ($n <= $nbCharToParse) {
                            /* We take an extra char */
                            $content = substr($toParse, $nbCharId, $n);
                        }
                    }
                }
            }
        }

        /* We check for separator */
        $separator = strpos($content, chr(29));
        if ($separator !== false) {
            $content = substr($content, 0, $separator);
            $separatorsFound++;
        }

        if ($id !== null) {
            /* We check the conformity */
            if (!$this->checkConformity($content, $id, $realNameId)) {
                return false;
            }

            /* We check the checksum */
            if (!$this->checkChecksum($content, $id, $realNameId, $checksumAdded)) {
                return false;
            }

            /* We check the vars */
            if (!$this->checkVars($content, $id, $yAlreadySet, $decimalPointRemoved)) {
                return false;
            }
        }

        $this->identifiersId[] = $id;
        $this->identifiersContent[] = $content;

        $nbCharLastContent = (((strlen($content) + $nbCharId) - $checksumAdded) + $decimalPointRemoved) + $separatorsFound;
        if ($nbCharToParse - $nbCharLastContent > 0) {
            /* If there is more than one content in this array, we parse again */
            $otherContent = substr($toParse, $nbCharLastContent, $nbCharToParse);
            $nbCharOtherContent = strlen($otherContent);

            if ($otherContent[0] === chr(29)) {
                $otherContent = substr($otherContent, 1);
                $nbCharOtherContent--;
            }

            if ($nbCharOtherContent > 0) {
                $text = $otherContent;
                return $this->parseContent($text);
            }
        }

        return true;
    }

    /**
     * Checks if an id exists.
     *
     * @param string $id The AI.
     * @param bool|null $yAlreadySet Y Status.
     * @param string|null $realNameId The real AI.
     * @return bool True if the AI exists.
     */
    private function idExists(string $id, ?bool &$yAlreadySet, ?string &$realNameId): bool
    {
        $yFound = isset($id[3]) && $id[3] === 'y';
        $idVarAdded = substr($id, 0, -1) . 'y';

        if ($this->identifiersAi !== null) {
            if (isset($this->identifiersAi[$id])) {
                if ($yFound) {
                    $yAlreadySet = false;
                }

                $realNameId = $id;
                return true;
            } elseif (!$yFound && isset($this->identifiersAi[$idVarAdded])) {
                /* if the id don't exist, we try to find this id with "y" at the last char */
                $yAlreadySet = true;
                $realNameId = $idVarAdded;
                return true;
            }
        }

        return false;
    }

    /**
     * Finds ID with formatted content.
     *
     * @param string $id The AI.
     * @param bool|null $yAlreadySet Y Status.
     * @param string|null $realNameId The real AI.
     * @return string|null The ID if found.
     */
    private function findIdFormatted(string $id, ?bool &$yAlreadySet, ?string &$realNameId): ?string
    {
        $pos = strpos($id, ')');
        if ($pos === false) {
            throw new BCGParseException('gs1128', 'Identifiers must have no more than 4 characters.');
        } else {
            if ($pos < 3) {
                throw new BCGParseException('gs1128', 'Identifiers must have at least 2 characters.');
            }

            $id = substr($id, 1, $pos - 1);
            if ($this->idExists($id, $yAlreadySet, $realNameId)) {
                return $id;
            }

            if ($this->allowsUnknownIdentifier === false) {
                throw new BCGParseException('gs1128', 'The identifier ' . $id . ' doesn\'t exist. Have you installed the default AI with "setApplicationIdentifiers()"? Or allow unknown identifiers with "setAllowsUnknownIdentifier(true)".');
            }

            return null;
        }
    }

    /**
     * Finds ID with non-formatted content.
     *
     * @param string $id The AI.
     * @param bool|null $yAlreadySet Y Status.
     * @param string|null $realNameId The real AI.
     * @return string|null The ID if found.
     */
    private function findIdNotFormatted(string $id, ?bool &$yAlreadySet, ?string &$realNameId): ?string
    {
        $tofind = $id;

        while (strlen($tofind) >= 2) {
            if ($this->idExists($tofind, $yAlreadySet, $realNameId)) {
                return $tofind;
            } else {
                $tofind = substr($tofind, 0, -1);
            }
        }

        if ($this->allowsUnknownIdentifier === false) {
            throw new BCGParseException('gs1128', 'Error in formatting, can\'t find an identifier. Have you installed the default AI with "setApplicationIdentifiers()"? Or allow unknown identifiers with "setAllowsUnknownIdentifier(true)".');
        }

        return null;
    }

    /**
     * Checks confirmity of the content.
     *
     * @param string $content The content.
     * @param string $id The AI.
     * @param string|null $realNameId The real AI.
     * @return bool True if valid.
     */
    private function checkConformity(string &$content, string $id, ?string $realNameId): bool
    {
        switch ($this->identifiersAi[$realNameId]->getKindOfData()) {
            case KindOfData::NUMERIC:
                $content = str_replace(',', '.', $content);
                if (!preg_match("/^[0-9.]+$/", $content)) {
                    throw new BCGParseException('gs1128', 'The value of "' . $id . '" must be numerical.');
                }

                break;
            case KindOfData::DATETIME:
                $validDateTime = true;
                if (preg_match("/^[0-9]{8,12}$/", $content)) {
                    $year = substr($content, 0, 2);
                    $month = substr($content, 2, 2);
                    $day = substr($content, 4, 2);
                    $hour = substr(content, 6, 2);
                    $minute = strlen(content) >= 10 ? substr(content, 8, 2) : null;
                    $second = strlen(content) >= 12 ? substr(content, 10, 2) : null;

                    /* day can be 00 if we only need month and year */
                    if (intval($month) < 1
                        || intval($month) > 12
                        || intval($day) < 0
                        || intval($day) > 31
                        || intval(hour) > 23
                        || (minute !== null && intval(minute) > 59)
                        || (second !== null && intval(second) > 59)
                    ) {
                        $validDateTime = false;
                    }
                } else {
                    $validDateTime = false;
                }

                if (!$validDateTime) {
                    throw new BCGParseException('gs1128', 'The value of "' . $id . '" must be in YYMMDDHHMMSS format. Some AI might not allow seconds.');
                }

                break;
            case KindOfData::DATE:
                $validDate = true;
                if (preg_match("/^[0-9]{6}$/", $content)) {
                    $year = substr($content, 0, 2);
                    $month = substr($content, 2, 2);
                    $day = substr($content, 4, 2);

                    /* day can be 00 if we only need month and year */
                    if (intval($month) < 1 || intval($month) > 12 || intval($day) > 31) {
                        $validDate = false;
                    }
                } else {
                    $validDate = false;
                }

                if (!$validDate) {
                    throw new BCGParseException('gs1128', 'The value of "' . $id . '" must be in YYMMDD format.');
                }

                break;
        }

        // We check the length of the content
        $nbCharContent = strlen($content);
        $checksumChar = 0;
        $minlengthContent = $this->identifiersAi[$realNameId]->getMinLength();
        $maxlengthContent = $this->identifiersAi[$realNameId]->getMaxLength();

        if ($this->identifiersAi[$realNameId]->getChecksum()) {
            $checksumChar++;
        }

        if ($nbCharContent < ($minlengthContent - $checksumChar)) {
            if ($minlengthContent === $maxlengthContent) {
                throw new BCGParseException('gs1128', 'The value of "' . $id . '" must contain ' . $minlengthContent . ' character(s).');
            } else {
                throw new BCGParseException('gs1128', 'The value of "' . $id . '" must contain between ' . $minlengthContent . ' and ' . $maxlengthContent . ' character(s).');
            }
        }

        return true;
    }

    /**
     * Verifies the checksum.
     *
     * @param string $content The content.
     * @param string $id The AI.
     * @param string|null $realNameId The real AI.
     * @param int $checksumAdded The checksum was added.
     * @return bool True if valid.
     */
    private function checkChecksum(string &$content, string $id, ?string $realNameId, int &$checksumAdded): bool
    {
        if ($this->identifiersAi[$realNameId]->getChecksum()) {
            $nbCharContent = strlen($content);
            $minlengthContent = $this->identifiersAi[$realNameId]->getMinLength();
            if ($nbCharContent === ($minlengthContent - 1)) {
                /* we need to calculate the checksum */
                $content .= self::getAiContentChecksum($content);
                $checksumAdded++;
            } elseif ($nbCharContent === $minlengthContent) {
                /* we need to check the checksum */
                $checksum = self::getAiContentChecksum(substr($content, 0, -1));
                if (intval($content[$nbCharContent - 1]) !== $checksum) {
                    throw new BCGParseException('gs1128', 'The checksum of "(' . $id . ') ' . $content . '" must be: ' . $checksum);
                }
            }
        }

        return true;
    }

    /**
     * Checks vars "y".
     *
     * @param string $content The content.
     * @param string $id The AI.
     * @param bool|null $yAlreadySet Y Status.
     * @param int $decimalPointRemoved The decimal point was removed.
     * @return bool True if valid.
     */
    private function checkVars(string &$content, string &$id, ?bool $yAlreadySet, int &$decimalPointRemoved): bool
    {
        $nbCharContent = strlen($content);
        /* We check for "y" var in AI */
        if ($yAlreadySet) {
            /* We'll check if we have a decimal point */
            if (strpos($content, '.') !== false) {
                throw new BCGParseException('gs1128', 'If you do not use any "y" variable, you have to insert a whole number.');
            }
        } elseif ($yAlreadySet !== null) {
            /* We need to replace the "y" var with the position of the decimal point */
            $pos = strpos($content, '.');
            if ($pos === false) {
                $pos = $nbCharContent - 1;
            }

            $id = str_replace('y', $nbCharContent - ($pos + 1), strtolower($id));
            $content = str_replace('.', '', $content);
            $decimalPointRemoved++;
        }

        return true;
    }

    /**
     * Checksum Mod10.
     *
     * @param string $content The content.
     * @return int The checksum.
     */
    private static function calculateChecksumMod10(string $content): int
    {
        // Calculating Checksum
        // Consider the right-most digit of the message to be in an "odd" position,
        // and assign odd/even to each character moving from right to left
        // Odd Position = 3, Even Position = 1
        // Multiply it by the number
        // Add all of that and do 10-(?mod10)
        $odd = true;
        $checksumValue = 0;
        $c = strlen($content);

        for ($i = $c; $i > 0; $i--) {
            if ($odd === true) {
                $multiplier = 3;
                $odd = false;
            } else {
                $multiplier = 1;
                $odd = true;
            }

            $checksumValue += ($content[$i - 1] * $multiplier);
        }

        return (10 - $checksumValue % 10) % 10;
    }
}
