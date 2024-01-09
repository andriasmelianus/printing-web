<?php

namespace App\Printers\Epson;

use App\Printers\Epson\Abstracts\CommandAbstract;
use App\Printers\Epson\Contracts\EscpContract;

/**
 * This class generates ESC/P 9-pin commands ready to be sent to Epson printers.
 *
 * Supported printers:
 * - ActionPrinter T-750
 * - ActionPrinter T-1000
 * - ActionPrinter Apex 80
 * - ActionPrinter 2000
 * - ActionPrinter 2250
 * - ActionPrinter 2500
 * - DFX-5000
 * - DFX-5000+
 * - DFX-8000
 * - FX-850
 * - FX-870
 * - FX-1050
 * - FX-1170
 * - FX-2170
 * - LX-100
 * - LX-300
 * - LX-400
 * - LX-800
 * - LX-810
 * - LX-850
 * - LX-1050
 * - LX-1050+
 *
 * PHP version 8
 *
 * @category File
 * @package  App\Printers\Epson;
 * @author   Andrias Melianus S <it.andrias@borwita.co.id>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://www.borwita.co.id/
 */
class Escp9Pin extends CommandAbstract implements EscpContract
{
    // Supported typefaces.
    public const TYPEFACE_ROMAN = 0;
    public const TYPEFACE_SANS_SERIF = 1;
    public const TYPEFACE_COURIER = 2;

    /**
     * Constructor.
     *
     * @param int $outputFormat
     * @return void
     */
    public function __construct(int $outputFormat = self::OUTPUT_FORMAT_HEXBYTE)
    {
        parent::__construct($outputFormat);
    }

    /**
     * Initialize the printer.
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
    public function set16Spacing(): self
    {
        $this->append16SpacingCommand();

        return $this;
    }

    /**
     * Set line spacing to 1/8 inch.
     *
     * @return self
     */
    public function set18Spacing(): self
    {
        $this->append18SpacingCommand();

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
}