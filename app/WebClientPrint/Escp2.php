<?php

namespace App\WebClientPrint;

/**
 * This class intended to help ESC/P 2 command generation.
 * WebClientPrint component receives ESC/P 2 command in hex-byte format.
 * It's cumbersome to translate those commands from Reference Manual into hex-byte format.
 */
class Escp2
{
    // Constant values.
    public const MAXIMUM_RESOLUTION = 3600;
    private const INDEX_FROM = 'from';
    private const INDEX_TO = 'to';
    private const REPLACABLES = [
        [self::INDEX_FROM => 'esc', self::INDEX_TO => "\e"],
        [self::INDEX_FROM => 'em', self::INDEX_TO => "\x19"],
        [self::INDEX_FROM => 'can', self::INDEX_TO => "\x18"],
        [self::INDEX_FROM => 'del', self::INDEX_TO => "\x7f"],
        [self::INDEX_FROM => 'cr', self::INDEX_TO => "\r"],
        [self::INDEX_FROM => 'ff', self::INDEX_TO => "\f"],
        [self::INDEX_FROM => 'lf', self::INDEX_TO => "\n"],
        [self::INDEX_FROM => 'ht', self::INDEX_TO => "\t"],
        [self::INDEX_FROM => 'vt', self::INDEX_TO => "\v"],
        [self::INDEX_FROM => 'si', self::INDEX_TO => "\x0f"],
        [self::INDEX_FROM => 'dc2', self::INDEX_TO => "\x12"],
        [self::INDEX_FROM => 'so', self::INDEX_TO => "\x0e"],
        [self::INDEX_FROM => 'dc4', self::INDEX_TO => "\x14"],
    ];
    // Properties.
    private $unit;
    private $dpi;
    private $pageLengthInMilimeter;
    private $topMarginInMilimeter;
    private $bottomMarginInMilimeter;
    private $command;

    /**
     * Constructor.
     *
     * Print quality defines the DPI unit.
     * Possible values for print quality are as follow:
     * 1: 360 DPI (best)
     * 2: 180 DPI
     * 3: 120 DPI
     * 4:  90 DPI
     * 5:  72 DPI
     * 6:  60 DPI
     *
     * @param int $printQuality Define the printing quality.
     * @return void
     */
    public function __construct(int $printQuality = 1)
    {
        switch ($printQuality) {
            case 1:
                $this->unit = 10;
                break;
            case 2:
                $this->unit = 20;
                break;
            case 3:
                $this->unit = 30;
                break;
            case 4:
                $this->unit = 40;
                break;
            case 5:
                $this->unit = 50;
                break;
            case 6:
                $this->unit = 60;
                break;

            default:
                $this->unit = 5;
                break;
        }
        $this->dpi = self::MAXIMUM_RESOLUTION / $this->unit;
    }

    /**
     * Converts ESC/P 2 commands in plain string into HEX in byte format
     * ready to be sent to dot-matrix printer.
     * These are input and output samples:
     * 'esc @'      ==> "0x1b0x40"
     * 'esc em'     ==> "0x1b0x19"
     * 'esc 3 12'   ==> "0x1b0x030x0c"
     *
     * @param string $stringCommand
     * @return string
     */
    private function convertStringToEscp2Command(string $stringCommand): string
    {
        $arrayCommands = explode(' ', $stringCommand);
        $arrayEscp2Commands = array_map(function ($command) {
            // Single character means the command itself.
            $commandInPlainString = strlen($command) == 1 ? $command : strtolower($command);
            // Replace ASCII description with the character itself.
            // For example: "lf" becomes "\n".
            foreach (self::REPLACABLES as $replacable) {
                if ($commandInPlainString == $replacable[self::INDEX_FROM]) {
                    $commandInPlainString = $replacable[self::INDEX_TO];
                }
            }

            // Convert non-numeric command into ASCII code in decimal.
            $commandInDecimal = is_numeric($commandInPlainString) ? $commandInPlainString : ord($commandInPlainString);

            // Convert decimal into hex. Since this component only accepts command in hex format.
            $commandInHex = (string)dechex($commandInDecimal);
            // Add leading "0" to a single character hex.
            $commandInPaddedHex = str_pad($commandInHex, 2, '0', STR_PAD_LEFT);

            // Transform padded hex into byte format.
            $commandInHexByteFormat = '0x' . $commandInPaddedHex;

            return $commandInHexByteFormat;
        }, $arrayCommands);

        return implode('', $arrayEscp2Commands);
    }

    /**
     * Calculate Mh value.
     * Mh is required in setting page length.
     *
     * @param float $value
     * @return int
     */
    private function calculateMH(float $value): int
    {
        return (int)($value * $this->dpi / 256);
    }

    /**
     * Calculate Ml value.
     * Ml is required in setting page length.
     *
     * @param float $value
     * @return int
     */
    private function calculateML(float $value): int
    {
        return (int)($value * $this->dpi % 256);
    }

    /**
     * Convert milimeter into inch value.
     *
     * @param float $milimeter
     * @return float
     */
    private function convertMilimeterToInch(float $milimeter): float
    {
        return $milimeter / 25.4;
    }

    /**
     * Generate constructed commands.
     *
     * @return string
     */
    public function generate(): string
    {
        return $this->command;
    }

    /**
     * Clear previously generated commands.
     *
     * @return App\WebClientPrint\Escp2
     */
    public function clear()
    {
        $this->command = '';

        return $this;
    }

    /**
     * Generates command to initialize printer.
     * Also define the unit.
     *
     * @param string $initializeCommand Default value is @.
     * @return App\WebClientPrint\Escp2
     */
    public function initializePrinter(string $initializeCommand = '@')
    {
        $this->command = $this->convertStringToEscp2Command('esc ' . $initializeCommand);
        $this->command .= $this->convertStringToEscp2Command('esc ( U 1 0 ' . $this->unit);

        return $this;
    }

    /**
     * Set the page length from top to bottom from defined unit.
     *
     * - This command is available only on printers featuring ESC/P 2.
     * - Set the page length before paper is loaded or when the print position
     *   is at the top-of-form position. Otherwise, the current print position
     *   becomes the top-of-form position (this results in undesirable contradictions
     *   between the actual and logical page settings).
     * - Setting the page length cancels the top and bottom-margin settings
     * - Changing the defined unit does not affect the current page-length setting.
     *
     * @param float $lengthInMilimeter Page length in milimeters.
     * @return App\WebClientPrint\Escp2
     */
    public function setPageLengthInDefinedUnit(float $lengthInMilimeter)
    {
        $this->pageLengthInMilimeter = $lengthInMilimeter;

        $lengthInInches = $this->convertMilimeterToInch($this->pageLengthInMilimeter);
        $mL = $this->calculateML($lengthInInches);
        $mH = $this->calculateMH($lengthInInches);
        // nL and nH is defined as constant in the command.
        $this->command .= $this->convertStringToEscp2Command('esc ( C 2 0 ' . $mL . ' ' . $mH);

        return $this;
    }

    /**
     * Set the top and bottom margins in the defined units.
     * This command is represented as "Set page format" on Reference Manual.
     *
     * - This command is available only on printers featuring ESC/P 2.
     * - Measure both top and bottom margins from the top edge of the page.
     * - The baseline for printing characters on the first line is 20/180 inch
     *   below the top-margin position.
     * - Send this command before paper is loaded, or when paper is at the
     *   top-of-form position. Otherwise, the current print position becomes
     *   the top-margin position (this results in undesirable contradictions
     *   between the actual and logical page settings).
     * - This command cancels any previous top and bottom-margin settings.
     * - Changing the defined unit does not affect the current page-length setting.
     *
     * @param float $topMarginInMilimeter Margin measured from top.
     * @param float $bottomMarginInMilimeter Margin measured from bottom.
     * @return App\WebClientPrint\Escp2
     */
    public function setMarginTopBottom(float $topMarginInMilimeter, float $bottomMarginInMilimeter)
    {
        $this->topMarginInMilimeter = $topMarginInMilimeter;
        /**
         * Bottom margin in this context is little bit different with bottom margin
         * defined in CSS. Bottom margin in ESC/P 2 measured from the top of page
         * to the end of printing area.
         */
        $this->bottomMarginInMilimeter = $this->pageLengthInMilimeter - $bottomMarginInMilimeter;

        $topMarginInInches = $this->convertMilimeterToInch($this->topMarginInMilimeter);
        $bottomMarginInInches = $this->convertMilimeterToInch($this->bottomMarginInMilimeter);
        // The calculation formula is similar to ML & MH calculation.
        $tL = $this->calculateML($topMarginInInches);
        $tH = $this->calculateMH($topMarginInInches);
        $bL = $this->calculateML($bottomMarginInInches);
        $bH = $this->calculateMH($bottomMarginInInches);
        $this->command .= $this->convertStringToEscp2Command('esc ( c 4 0 ' . $tL . ' ' . $tH . ' ' . $bL . ' ' . $bH);

        return $this;
    }

    /**
     * Set line spacing to 1/8 inch.
     *
     * @return App\WebClientPrint\Escp2
     */
    public function setLineSpacing18()
    {
        $this->command .= $this->convertStringToEscp2Command('esc 0');

        return $this;
    }

    /**
     * Set line spacing to 1/6 inch.
     *
     * @return App\WebClientPrint\Escp2
     */
    public function setLineSpacing16()
    {
        $this->command .= $this->convertStringToEscp2Command('esc 2');

        return $this;
    }

    /**
     * Set line spacing to n/180 inch.
     *
     * @param int $n Line spacing value to be divided.
     * @return App\WebClientPrint\Escp2
     */
    public function setLineSpacingN180(int $n)
    {
        $this->command .= $this->convertStringToEscp2Command('esc 3 ' . $n);

        return $this;
    }

    /**
     * Set line spacing to n/360 inch.
     *
     * @param int $n Line spacing value to be divided.
     * @return App\WebClientPrint\Escp2
     */
    public function setLineSpacingN360(int $n)
    {
        $this->command .= $this->convertStringToEscp2Command('esc + ' . $n);

        return $this;
    }

    /**
     * Set line spacing to n/60 inch.
     *
     * @param int $n Line spacing value to be divided.
     * @return App\WebClientPrint\Escp2
     */
    public function setLineSpacingN60(int $n)
    {
        $this->command .= $this->convertStringToEscp2Command('esc A ' . $n);

        return $this;
    }

    /**
     * Generates command to load/eject paper.
     * Possible value for $value are:
     * 1: MP Tray
     * 2: LC1
     *
     * @param integer $value
     * @return App\WebClientPrint\Escp2
     */
    public function loadEjectPaper(int $value = 1)
    {
        $this->command .= $this->convertStringToEscp2Command('esc em ' . $value);

        return $this;
    }

    /**
     * Add simple text.
     *
     * @param string $text
     * @param bool $isFollowedByLineFeed
     * @return App\WebClientPrint\Escp2
     */
    public function addText(string $text, bool $isFollowedByLineFeed = false)
    {
        $this->command .= $text;
        $this->command .= $isFollowedByLineFeed ? $this->convertStringToEscp2Command('lf') : '';

        return $this;
    }

    /**
     * Add bold text.
     *
     * @param string $text
     * @param bool $isFollowedByLineFeed
     * @return App\WebClientPrint\Escp2
     */
    public function addBoldText(string $text, bool $isFollowedByLineFeed = false)
    {
        $boldText = $this->convertStringToEscp2Command('esc e') . $text . $this->convertStringToEscp2Command('esc f');
        return $this->addText($boldText, $isFollowedByLineFeed);
    }

    /**
     * Add italic text.
     *
     * @param string $text
     * @param bool $isFollowedByLineFeed
     * @return App\WebClientPrint\Escp2
     */
    public function addItalicText(string $text, bool $isFollowedByLineFeed = false)
    {
        $italicText = $this->convertStringToEscp2Command('esc 4') . $text . $this->convertStringToEscp2Command('esc 5');
        return $this->addText($italicText, $isFollowedByLineFeed);
    }
}
