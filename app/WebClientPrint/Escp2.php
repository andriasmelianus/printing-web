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
    public const TYPEFACE_ROMAN = 0;
    public const TYPEFACE_SANS_SERIF = 1;
    public const TYPEFACE_COURIER = 2;
    public const TYPEFACE_PRESTIGE = 3;
    public const TYPEFACE_SCRIPT = 4;
    public const TYPEFACE_OCR_B = 5;
    public const TYPEFACE_OCR_A = 6;
    public const TYPEFACE_ORATOR = 7;
    public const TYPEFACE_ORATOR_S = 8;
    public const TYPEFACE_SCRIPT_C = 9;
    public const TYPEFACE_ROMAN_T = 10;
    public const TYPEFACE_SANS_SERIF_H = 11;
    public const TYPEFACE_SV_BUSABA = 30;
    public const TYPEFACE_SV_JITTRA = 31;
    public const FONT_SIZE_8 = 8;
    public const FONT_SIZE_10 = 10.5;
    public const FONT_SIZE_12 = 12;
    public const FONT_SIZE_14 = 14;
    public const FONT_SIZE_16 = 16;
    public const FONT_SIZE_18 = 18;
    public const FONT_SIZE_20 = 21;
    public const FONT_SIZE_22 = 22;
    public const FONT_SIZE_24 = 24;
    public const FONT_SIZE_26 = 26;
    public const FONT_SIZE_28 = 28;
    public const FONT_SIZE_30 = 30;
    public const FONT_SIZE_32 = 32;
    private const MAXIMUM_RESOLUTION = 3600;
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
        [self::INDEX_FROM => 'sp', self::INDEX_TO => " "],
        [self::INDEX_FROM => 'nul', self::INDEX_TO => "\x0"],
        [self::INDEX_FROM => 'null', self::INDEX_TO => "\x0"],
    ];
    // Properties.
    private $unit;
    private $dpi;
    private $pageLengthInMilimeter;
    private $topMarginInMilimeter;
    private $bottomMarginInMilimeter;
    private $leftMarginInMilimeter;
    private $rightMarginInMilimeter;
    private $proportionalModeEnabled = false;
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
            // Detect array index within array_map().
            static $index = 0;

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
            // Except second block of command. It should be converted to hex format.
            // After the second block, numeric will be treated as plain value.
            $commandInDecimal = $index > 1 && is_numeric($commandInPlainString) ? $commandInPlainString : ord($commandInPlainString);

            // Convert decimal into hex. Since this component only accepts command in hex format.
            $commandInHex = (string)dechex($commandInDecimal);
            // Add leading "0" to a single character hex.
            $commandInPaddedHex = str_pad($commandInHex, 2, '0', STR_PAD_LEFT);

            // Transform padded hex into byte format.
            $commandInHexByteFormat = '0x' . $commandInPaddedHex;

            // Increase the index.
            $index++;

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
     * Calculate Nh value.
     * Nh is required in setting page length.
     *
     * @param float $value
     * @return int
     */
    private function calculateNH(float $value): int
    {
        return (int)($value * 2 / 256);
    }

    /**
     * Calculate Nl value.
     * Nl is required in setting page length.
     *
     * @param float $value
     * @return int
     */
    private function calculateNL(float $value): int
    {
        return (int)($value * 2 % 256);
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
     * @return Escp2
     */
    public function clear(): Escp2
    {
        $this->command = '';

        return $this;
    }

    /**
     * Generates command to load/eject paper.
     * Possible value for $value are:
     * 1: MP Tray
     * 2: LC1
     *
     * @param integer $value
     * @return Escp2
     */
    public function loadEjectPaper(int $value = 1): Escp2
    {
        $this->command .= $this->convertStringToEscp2Command('esc em ' . $value);

        return $this;
    }

    /**
     * Generates command to initialize printer.
     * Also define the unit.
     *
     * @param string $initializeCommand Default value is @.
     * @return Escp2
     */
    public function initializePrinter(string $initializeCommand = '@'): Escp2
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
     * @return Escp2
     */
    public function setPageLengthInDefinedUnit(float $lengthInMilimeter): Escp2
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
     * @return Escp2
     */
    public function setMarginTopBottom(float $topMarginInMilimeter, float $bottomMarginInMilimeter): Escp2
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
     * Set the left margin.
     * # WARNING! THIS FUNCTION IS NOT IMPLEMENTED YET, USE absolute horizontal print position instead. #
     *
     * - Set the left margin at the beginning of a line; the printer ignores
     *   any data preceding this command on the same line in the buffer.
     * - The following commands affect character pitch: ESC X, ESC c, ESC P,
     *   ESC M, ESC g, ESC W, ESC p, ESC SP, SO, ESC !, AND SI.
     * - Always set the pitch before setting the margins. Do not assume what
     *   the pitch setting will be.
     * - Always set the margins at the beginning of a print job.
     * - Always set the left margin to be at lesat one column (at 10 cpi)
     *   less than the right margin.
     * - The printer calculates the left margin based on 10 cpi if proportional
     *   spacing is selected with the ESC p command.
     * - Moving the left-margin position moves the tab settings by the same
     *   distance.
     *
     * @param float $leftMarginInMilimeter
     * @return Escp2
     */
    public function setLeftMargin(float $leftMarginInMilimeter): Escp2
    {
        $this->leftMarginInMilimeter = $leftMarginInMilimeter;

        // $this->command .= $this->convertStringToEscp2Command('esc l ');

        return $this;
    }

    /**
     * Set the starting print position from defined left margin.
     *
     * - Set the defined unit with the ESC ( U command.
     * - The default defined unit setting for this command is 1/60 inch.
     * - The new position is measured from the current left-margin-position.
     * - The printer ignores this command if the specified position is to the
     *   right of the right margin.
     *
     * @return Escp2
     */
    public function setAbsoluteHorizontalPosition(float $leftMarginInMilimeter): Escp2
    {
        $leftMarginInMilimeterWithoutLeftMargin = $leftMarginInMilimeter - ($this->leftMarginInMilimeter ? $this->leftMarginInMilimeter : 0);
        $leftMarginInInch = $this->convertMilimeterToInch($leftMarginInMilimeterWithoutLeftMargin);
        $nL = (int)(($leftMarginInInch / $this->dpi) / 256);
        $nH = (int)(($leftMarginInInch / $this->dpi) % 256);
        $this->command .= $this->convertStringToEscp2Command('esc $ ' . $nL . ' ' . $nH);

        return $this;
    }

    /**
     * Define printer built-in typeface.
     *
     * @param integer $typeface
     * @return Escp2
     */
    public function setTypeface(int $typeface = self::TYPEFACE_ROMAN): Escp2
    {
        $this->command .= $this->convertStringToEscp2Command('esc k ' . $typeface);

        return $this;
    }

    /**
     * Reset the characters per inch to the default CPI setting (10).
     *
     * @return Escp2
     */
    public function resetCpi(): Escp2
    {
        $cpiCommand = $this->convertStringToEscp2Command('esc P');
        if ($this->proportionalModeEnabled) {
            $this->disableProportionalMode();
            $this->command .= $cpiCommand;
            $this->enableProportionalMode();
        } else {
            $this->command .= $cpiCommand;
        }

        return $this;
    }

    /**
     * Set the characters per inch setting to 12.
     *
     * @return Escp2
     */
    public function setCpiTo12(): Escp2
    {
        $cpiCommand = $this->convertStringToEscp2Command('esc M');
        if ($this->proportionalModeEnabled) {
            $this->disableProportionalMode();
            $this->command .= $cpiCommand;
            $this->enableProportionalMode();
        } else {
            $this->command .= $cpiCommand;
        }

        return $this;
    }

    /**
     * Set the characters per inch setting to 15.
     *
     * @return Escp2
     */
    public function setCpiTo15(): Escp2
    {
        $cpiCommand = $this->convertStringToEscp2Command('esc g');
        if ($this->proportionalModeEnabled) {
            $this->disableProportionalMode();
            $this->command .= $cpiCommand;
            $this->enableProportionalMode();
        } else {
            $this->command .= $cpiCommand;
        }

        return $this;
    }

    /**
     * Enable proportional mode (non-monospaced font).
     *
     * @return Escp2
     */
    public function enableProportionalMode(): Escp2
    {
        $this->command .= $this->convertStringToEscp2Command('esc p 1');
        $this->proportionalModeEnabled = true;

        return $this;
    }

    /**
     * Disable proportional mode (non-monospaced font).
     *
     * @return Escp2
     */
    public function disableProportionalMode(): Escp2
    {
        $this->command .= $this->convertStringToEscp2Command('esc p 0');
        $this->proportionalModeEnabled = false;

        return $this;
    }

    /**
     * Enable condensed font. Text width will shrink about 50%.
     * Represented as "Select condensed printing".
     *
     * @return Escp2
     */
    public function enableCondensedFont(): Escp2
    {
        $this->command .= $this->convertStringToEscp2Command('si');

        return $this;
    }

    /**
     * Disable condensed font.
     * Represented as "Cancel condensed printing".
     *
     * @return Escp2
     */
    public function disableCondensedFont(): Escp2
    {
        $this->command .= $this->convertStringToEscp2Command('dc2');

        return $this;
    }

    /**
     * Set line spacing to 1/8 inch.
     *
     * @return Escp2
     */
    public function setLineSpacing18(): Escp2
    {
        $this->command .= $this->convertStringToEscp2Command('esc 0');

        return $this;
    }

    /**
     * Set line spacing to 1/6 inch.
     *
     * @return Escp2
     */
    public function setLineSpacing16(): Escp2
    {
        $this->command .= $this->convertStringToEscp2Command('esc 2');

        return $this;
    }

    /**
     * Set line spacing to n/180 inch.
     * This function intended to give more precise for line spacing,
     * beside the pre-defined 1/6 and 1/8-inch line spacing.
     *
     * @param int $n Line spacing value to be divided.
     * @return Escp2
     */
    public function setLineSpacingN180(int $n): Escp2
    {
        $this->command .= $this->convertStringToEscp2Command('esc 3 ' . $n);

        return $this;
    }

    /**
     * Set line spacing to n/360 inch.
     * This function intended to give more precise for line spacing,
     * beside the pre-defined 1/6 and 1/8-inch line spacing.
     *
     * @param int $n Line spacing value to be divided.
     * @return Escp2
     */
    public function setLineSpacingN360(int $n): Escp2
    {
        $this->command .= $this->convertStringToEscp2Command('esc + ' . $n);

        return $this;
    }

    /**
     * Set line spacing to n/60 inch.
     * This function intended to give more precision for line spacing,
     * beside the pre-defined 1/6 and 1/8-inch line spacing.
     *
     * @param int $n Line spacing value to be divided.
     * @return Escp2
     */
    public function setLineSpacingN60(int $n): Escp2
    {
        $this->command .= $this->convertStringToEscp2Command('esc A ' . $n);

        return $this;
    }

    /**
     * Sets horizontal tab positions (in the current character pitch) at the columns specified by n1 to nk, as measured from the left-margin position.
     * Default size is 8 characters.
     *
     * - The values must be in ascending order. A value less than previous
     *   will end the tab setting (like the NULL code).
     * - Changing the character pitch does not affect current tab settings.
     * - Send an Esc D NUL command to cancel all tab settings.
     * - The tab settings move to match any movement in the left margin.
     * - A maximum of 32 horizontal tabs can be set.
     * - The printer does not move the print position to any tabs beyond the
     *   the right-margin position. However, all tab settings are stored
     *   in the printer's memory; if you move the right margin, you can access
     *   previously ignored tabs.
     * - The printer calculates tab positions based on 10 CPI if proportional
     *   spacing is selected with the ESC p comand.
     * - Sending the ESC D command clears any previous tab settings.
     *
     * @param array $tabStops Maximum of 32 elements
     * @return Escp2
     */
    public function setTabStop(array $tabStops): Escp2
    {
        $stringCommand = 'esc D ';
        foreach ($tabStops as $tabStop) {
            $stringCommand .= $tabStop . ' ';
        }
        $stringCommand .= 'nul';
        $this->command .= $this->convertStringToEscp2Command($stringCommand);

        return $this;
    }

    /**
     * Reset tab stop settings to the default values: 8 characters.
     *
     * @return Escp2
     */
    public function resetTabStop(): Escp2
    {
        $this->command .= $this->convertStringToEscp2Command('esc D nul');

        return $this;
    }

    /**
     * Add tab character.
     *
     * @param int $count
     * @return Escp2
     */
    public function addTab(int $count = 1): Escp2
    {
        for ($i = 0; $i < $count; $i++) {
            $this->command .= $this->convertStringToEscp2Command('ht');
        }

        return $this;
    }

    /**
     * Add line-feed character.
     *
     * @param int $count
     * @return Escp2
     */
    public function addLineFeed(int $count = 1): Escp2
    {
        for ($i = 0; $i < $count; $i++) {
            $this->command .= $this->convertStringToEscp2Command('lf');
        }

        return $this;
    }

    /**
     * Add carriage-return character.
     * Moves the print position to the left-margin position.
     *
     * - Always send a CR command at the end of each line of
     *   text or graphics data.
     * - When automatic line-feed is selected (through
     *   DIP-switch or panel setting), the CR command is
     *   accompanied by a LF command.
     *
     * @param integer $count
     * @return Escp2
     */
    public function addCarriageReturn(int $count = 1): Escp2
    {
        for ($i = 0; $i < $count; $i++) {
            $this->command .= $this->convertStringToEscp2Command('cr');
        }

        return $this;
    }

    /**
     * Add form-feed character.
     *
     * - Advances the vertical print position on continuous paper to the
     *   top-of-form position of the next page.
     * - Ejects single-sheet paper.
     * - Moves the horizontal print position to the left-margin position.
     * - Prints all data in the buffer.
     *
     * - Always send a FF command at the end of each page and each print job.
     * - It is recommended to always send a CR command before the FF command.
     * - The FF command cacncels one-line double-width printing selected
     *   with the SO or ESC SO commands.
     *
     * @param int $count
     * @return Escp2
     */
    public function addFormFeed(int $count = 1): Escp2
    {
        for ($i = 0; $i < $count; $i++) {
            $this->addCariageReturn(); // As recommended by reference manual.
            $this->command .= $this->convertStringToEscp2Command('ff');
        }

        return $this;
    }

    /**
     * Add simple text.
     *
     * @param string $text
     * @param bool $isFollowedByLineFeed
     * @return Escp2
     */
    public function addText(string $text, bool $isFollowedByLineFeed = false): Escp2
    {
        $this->command .= $text;
        if ($isFollowedByLineFeed) {
            $this->addLineFeed();
        }

        return $this;
    }

    /**
     * Add bold text.
     *
     * @param string $text
     * @param bool $isFollowedByLineFeed
     * @return Escp2
     */
    public function addBoldText(string $text, bool $isFollowedByLineFeed = false): Escp2
    {
        $boldText = $this->convertStringToEscp2Command('esc E') . $text . $this->convertStringToEscp2Command('esc F');
        return $this->addText($boldText, $isFollowedByLineFeed);
    }

    /**
     * Add italic text.
     *
     * @param string $text
     * @param bool $isFollowedByLineFeed
     * @return Escp2
     */
    public function addItalicText(string $text, bool $isFollowedByLineFeed = false): Escp2
    {
        $italicText = $this->convertStringToEscp2Command('esc 4') . $text . $this->convertStringToEscp2Command('esc 5');
        return $this->addText($italicText, $isFollowedByLineFeed);
    }

    /**
     * Set the font size (height).
     * This function is represented as "Set the pitch and point" in the reference manual.
     *
     * - This command is available only on printers featuring ESC/P 2.
     * - This command overrides the current pitch setting.
     * - Only the following point sizes are available: see self::FONT_SIZE_*
     * - Selecting a combination of 15 cpi and 10 or 20-point characters results in
     *   15-cpi ROM characters being chosen; the height of these characters is about
     *   2/3 that of normal characters. Select the pitch with the ESC C command to
     *   obtain normal height 10 or 20-point characters at 15-cpi.
     * - During multipoint mode the printer ignores the ESC W, ESC w, ESC SP, SI,
     *   ESC SI, SO, and ESC SO commands.
     * - The following commands cancel multipoint mode, returning the printer to
     *   10.5-point characters: ESC P, ESC M, ESC g, ESC p, ESC !, and ESC @.
     *
     * @param float $pointSize Pick a value from available constants.
     * @param int $charactersPerInch Setting it to 1 means proportional mode.
     * @return Escp2
     */
    public function setFontSize(float $pointSize = self::FONT_SIZE_10, int $charactersPerInch = 0): Escp2
    {
        $m = 0;
        /**
         * $charactersPerInch = 1 means proportional mode.
         * When $charactersPerInch is set above 5, then the user is intended to set the custom CPI.
         */
        $isStillProportional = ($charactersPerInch > 0 && $charactersPerInch < 5);
        if ($this->proportionalModeEnabled || $isStillProportional) {
            $m = 1;
        }
        if ($charactersPerInch >= 5 && $charactersPerInch <= 22) {
            $m = 360 / $charactersPerInch;
        }
        $nL = $this->calculateNL($pointSize);
        $nH = $this->calculateNH($pointSize);
        $this->command .= $this->convertStringToEscp2Command('esc X ' . $m . ' ' . $nL . ' ' . $nH);

        return $this;
    }
}
