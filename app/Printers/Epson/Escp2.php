<?php

namespace App\Printers\Epson;

use App\Printers\Epson\Abstracts\CommandAbstract;
use App\Printers\Epson\Contracts\EscpContract;

/**
 * This class generates ESC/P 2 commands ready to be sent to Epson printers.
 *
 * Supported printers:
 * - ActionPrinter 3250
 * - ActionPrinter 3260
 * - ActionPrinter 5000
 * - ActionPrinter 5000+
 * - ActionPrinter 5500
 * - DLQ-3000
 * - DLQ-3000 ('96 ~)
 * - LQ-100
 * - LQ-150
 * - LQ-300
 * - LQ-570
 * - LQ-570+
 * - LQ-670
 * - LQ-870
 * - LQ-1070
 * - LQ-1070+
 * - LQ-1170
 * - LQ-2070
 * - LQ-2170
 * - Stylus 300
 * - Stylus 400
 * - Stylus 800
 * - Stylus 800+
 * - Stylus 1000
 * - Stylus COLOR
 * - SQ-870
 * - SQ-1170
 *
 * PHP version 8
 *
 * @category File
 * @package  App\Printers\Epson;
 * @author   Andrias Melianus S <it.andrias@borwita.co.id>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://www.borwita.co.id/
 */
class Escp2 extends CommandAbstract implements EscpContract
{
    // Constant values.
    // Print quality.
    public const PRINT_QUALITY_720_DPI = 1;
    public const PRINT_QUALITY_360_DPI = 2;
    public const PRINT_QUALITY_180_DPI = 3;
    public const PRINT_QUALITY_120_DPI = 4;
    public const PRINT_QUALITY_90_DPI = 5;
    public const PRINT_QUALITY_72_DPI = 6;
    public const PRINT_QUALITY_60_DPI = 7;
    // Supported typefaces.
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
    // Supported font sizes.
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
    // Miscellaneous.
    private const MAXIMUM_RESOLUTION = 3600;
    // Properties.
    private $printQuality;
    private $unit;
    private $dpi;
    private $pageLengthInMilimeter;
    private $topMarginInMilimeter;
    private $bottomMarginInMilimeter;

    /**
     * Constructor.
     *
     * @param int $printQuality
     * @param int $outputFormat
     * @return void
     */
    public function __construct(
        int $printQuality = self::PRINT_QUALITY_360_DPI,
        int $outputFormat = self::OUTPUT_FORMAT_HEXBYTE
    ) {
        parent::__construct($outputFormat);

        $this->printQuality = $printQuality;
        switch ($this->printQuality) {
            case self::PRINT_QUALITY_720_DPI:
                $this->unit = 5;
                break;
            case self::PRINT_QUALITY_360_DPI:
                $this->unit = 10;
                break;
            case self::PRINT_QUALITY_180_DPI:
                $this->unit = 20;
                break;
            case self::PRINT_QUALITY_120_DPI:
                $this->unit = 30;
                break;
            case self::PRINT_QUALITY_90_DPI:
                $this->unit = 40;
                break;
            case self::PRINT_QUALITY_72_DPI:
                $this->unit = 50;
                break;
            case self::PRINT_QUALITY_60_DPI:
                $this->unit = 60;
                break;

            default:
                $this->unit = 10;
                break;
        }
        $this->dpi = self::MAXIMUM_RESOLUTION / $this->unit;
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
     * Generates command to initialize printer.
     * Also define the unit.
     *
     * @param bool $clearPreviousCommands Default value is false.
     * @return self
     */
    public function initialize(bool $clearPreviousCommands = false): self
    {
        if ($clearPreviousCommands) {
            $this->clear();
        }

        $this->addCommand('esc @');
        $this->addCommand('esc ( U 1 0 ' . $this->unit);

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
     * @return self
     */
    public function setPageLengthInDefinedUnit(float $lengthInMilimeter): self
    {
        $this->pageLengthInMilimeter = $lengthInMilimeter;

        $lengthInInches = $this->convertMilimeterToInch($this->pageLengthInMilimeter);
        $mL = $this->calculateML($lengthInInches);
        $mH = $this->calculateMH($lengthInInches);
        // nL and nH is defined as constant in the command.
        $this->addCommand('esc ( C 2 0 ' . $mL . ' ' . $mH);

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
     * @return self
     */
    public function setMarginTopBottom(float $topMarginInMilimeter, float $bottomMarginInMilimeter): self
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
        $this->addCommand('esc ( c 4 0 ' . $tL . ' ' . $tH . ' ' . $bL . ' ' . $bH);

        return $this;
    }

    /**
     * Set the page length in inches.
     * Length must be increment of 1 inch.
     *
     * @param integer $lengthInInches
     * @return self
     */
    public function setPageLength(int $lengthInInches): self
    {
        $this->appendPageLengthCommand($lengthInInches);

        return $this;
    }

    /**
     * Set line spacing to 1/6 inch.
     *
     * @return self
     */
    public function setLineSpacing16(): self
    {
        $this->appendLineSpacing16Command();

        return $this;
    }

    /**
     * Set line spacing to 1/8 inch.
     *
     * @return self
     */
    public function setLineSpacing18(): self
    {
        $this->appendLineSpacing18Command();

        return $this;
    }

    /**
     * Set line spacing to n/180 inch.
     * This function intended to give more precise for line spacing,
     * beside the pre-defined 1/6 and 1/8-inch line spacing.
     *
     * @param int $n Line spacing value to be divided.
     * @return self
     */
    public function setLineSpacingN180(int $n): self
    {
        $this->addCommand('esc 3 ' . $n);

        return $this;
    }

    /**
     * Set line spacing to n/360 inch.
     * This function intended to give more precise for line spacing,
     * beside the pre-defined 1/6 and 1/8-inch line spacing.
     *
     * @param int $n Line spacing value to be divided.
     * @return self
     */
    public function setLineSpacingN360(int $n): self
    {
        $this->addCommand('esc + ' . $n);

        return $this;
    }

    /**
     * Set line spacing to n/60 inch.
     * This function intended to give more precision for line spacing,
     * beside the pre-defined 1/6 and 1/8-inch line spacing.
     *
     * @param int $n Line spacing value to be divided.
     * @return self
     */
    public function setLineSpacingN60(int $n): self
    {
        $this->addCommand('esc A ' . $n);

        return $this;
    }

    /**
     * Enable proportional mode (non-monospaced font).
     *
     * - This command cancels the HMI set with the ESC c command.
     * - This command cancels multipoint mode.
     * - Changes made to the fixed-pitch setting with the ESC P,
     *   ESC M, or ESC g commands during proportional mode take
     *   effect when the printer exits proportional mode.
     * - The printer automatically switches to LQ printing when
     *   proportional spacing is selected.
     *
     * @return self
     */
    public function enableProportionalMode(): self
    {
        $this->addCommand('esc p 1');
        $this->proportionalModeEnabled = true;

        return $this;
    }

    /**
     * Disable proportional mode (non-monospaced font).
     *
     * - This command cancels the HMI set with the ESC c command.
     * - This command cancels multipoint mode.
     * - Changes made to the fixed-pitch setting with the ESC P,
     *   ESC M, or ESC g commands during proportional mode take
     *   effect when the printer exits proportional mode.
     * - The printer automatically switches to LQ printing when
     *   proportional spacing is selected.
     *
     * @return self
     */
    public function disableProportionalMode(): self
    {
        $this->addCommand('esc p 0');
        $this->proportionalModeEnabled = false;

        return $this;
    }

    /**
     * Enable condensed font. Text width will shrink about 50%.
     * Represented as "Select condensed printing".
     *
     * @return self
     */
    public function enableCondensedFont(): self
    {
        $this->addCommand('si');
        $this->condensedFontEnabled = true;

        return $this;
    }

    /**
     * Disable condensed font.
     * Represented as "Cancel condensed printing".
     *
     * @return self
     */
    public function disableCondensedFont(): self
    {
        $this->addCommand('dc2');
        $this->condensedFontEnabled = false;

        return $this;
    }

    /**
     * Reset the characters per inch to the default CPI setting (10).
     *
     * @return self
     */
    public function resetCpi(): self
    {
        if ($this->proportionalModeEnabled) {
            $this->disableProportionalMode();
        }
        if ($this->condensedFontEnabled) {
            $this->disableCondensedFont();
        }

        $this->addCommand('esc P');

        return $this;
    }

    /**
     * Change typeface supported by the printer.
     *
     * @param integer $typefaceCode
     * @return self
     */
    public function setTypeface(int $typefaceCode): self
    {
        $this->appendTypefaceCommand($typefaceCode);

        return $this;
    }

    /**
     * Set font appearance with master select command.
     *
     * @param int  $cpi Characters per inch. Supported values are: 10 and 12.
     * @param bool $isProportional
     * @param bool $isCondensed
     * @param bool $isBold
     * @param bool $isDoubleStrike
     * @param bool $isDoubleWidth
     * @param bool $isItalic
     * @param bool $isUnderline
     * @return self
     */
    public function setFont(
        int $cpi = 10,
        bool $isProportional = false,
        bool $isCondensed = false,
        bool $isBold = false,
        bool $isDoubleStrike = false,
        bool $isDoubleWidth = false,
        bool $isItalic = false,
        bool $isUnderline = false
    ): self {
        $this->appendMasterSelectCommand($cpi, $isProportional, $isCondensed, $isBold, $isDoubleStrike, $isDoubleWidth, $isItalic, $isUnderline);

        return $this;
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
     * @return self
     */
    public function setFontSize(float $pointSize = self::FONT_SIZE_10, int $charactersPerInch = 0): self
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
            $m = (int)(360 / $charactersPerInch);
        }
        $nL = $this->calculateNL($pointSize);
        $nH = $this->calculateNH($pointSize);
        $this->addCommand('esc X ' . $m . ' ' . $nL . ' ' . $nH);

        return $this;
    }

    /**
     * Add plain text.
     *
     * @param string $text
     * @param bool $isFollowedByLineFeed
     * @return self
     */
    public function addText(string $text, bool $endWithLineFeed = false): self
    {
        $this->appendPlainTextCommand($text);

        if ($endWithLineFeed) {
            $this->appendLineFeedCommand();
        }

        return $this;
    }

    /**
     * Add bold text.
     *
     * @param string $text
     * @param bool $isFollowedByLineFeed
     * @return self
     */
    public function addBoldText(string $boldText, bool $endWithLineFeed = false): self
    {
        $this->appendBoldTextCommand($boldText);

        if ($endWithLineFeed) {
            $this->appendLineFeedCommand();
        }

        return $this;
    }

    /**
     * Add italic text.
     *
     * @param string $text
     * @param bool $isFollowedByLineFeed
     * @return self
     */
    public function addItalicText(string $italicText, bool $endWithLineFeed = false): self
    {
        $this->appendItalicTextCommand($italicText);

        if ($endWithLineFeed) {
            $this->appendLineFeedCommand();
        }

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
     * @return self
     */
    public function setTabStop(array $tabStops): self
    {
        $stringCommand = 'esc D ';
        foreach ($tabStops as $tabStop) {
            $stringCommand .= $tabStop . ' ';
        }
        $stringCommand .= 'nul';
        $this->addCommand($stringCommand);

        return $this;
    }

    /**
     * Reset tab stop settings to the default values: 8 characters.
     *
     * @return self
     */
    public function resetTabStop(): self
    {
        $this->addCommand('esc D nul');

        return $this;
    }

    /**
     * Sets vertical tab positionsn (in the current character pitch) at the rows
     * specified by n1 to nk.
     *
     * - The values for n must be in ascending order; a value of n less than the
     *   previous n ends tab setting (just like the NUL code).
     * - Changing the line spacing does not affect previous tab settings.
     * - The tab settings move to match any subsequent movement in the top-margin
     *   position.
     * - Send an ESC B NUL command to cancel all tab setting.
     * - A maximum of 16 vertical tabs can be set.
     * - The printer stores all tab settings, even if outside the printing area;
     *   if you increase the page length to include previously set tabs, you can
     *   move to those positions with the VT (tab vertically) command.
     * - Sending the ESC B command clears any previous tab settings.
     *
     * @param array $verticalTabStops
     * @return self
     */
    public function setVerticalTabStop(array $verticalTabStops): self
    {
        $stringCommand = 'esc B ';
        foreach ($verticalTabStops as $verticalTabStop) {
            $stringCommand .= $verticalTabStop . ' ';
        }
        $stringCommand .= 'nul';
        $this->addCommand($stringCommand);

        return $this;
    }

    /**
     * Reset vertical tab stop settings to the default values.
     *
     * @return self
     */
    public function resetVerticalTabStop(): self
    {
        $this->addCommand('esc B nul');

        return $this;
    }

    /**
     * Add tab character.
     *
     * @param int $count
     * @return self
     */
    public function addTab(int $count = 1): self
    {
        $this->appendTabCommand($count);

        return $this;
    }

    /**
     * Add vertical tab character.
     *
     * @param integer $count
     * @return self
     */
    public function addVerticalTab(int $count = 1): self
    {
        $this->appendVerticalTabCommand($count);

        return $this;
    }

    /**
     * Add line-feed character.
     *
     * @param int $count
     * @return self
     */
    public function addLineFeed(int $count = 1): self
    {
        $this->appendLineFeedCommand($count);

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
     * @return self
     */
    public function addCarriageReturn(int $count = 1): self
    {
        $this->appendCarriageReturnCommand($count);

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
     * @return self
     */
    public function addFormFeed(int $count = 1): self
    {
        $this->appendFormFeedCommand($count);

        return $this;
    }
}
