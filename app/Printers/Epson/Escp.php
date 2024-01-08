<?php

namespace App\Printers\Epson;

use App\Printers\Epson\Abstracts\CommandAbstract;
use App\Printers\Epson\Contracts\EscpContract;

/**
 * This class generates ESC/P commands ready to be sent to Epson printers.
 *
 * Supported printers:
 * - ActionPrinter 3000
 * - ActionPrinter 4000
 * - ActionPrinter L-750
 * - ActionPrinter 4500
 * - ActionPrinter L-1000
 * - DLQ-2000
 * - LQ-200
 * - LQ-400
 * - LQ-450
 * - LQ-500
 * - LQ-510
 * - LQ-550
 * - LQ-850
 * - LQ-850+
 * - LQ-860
 * - LQ-860+
 * - LQ-950
 * - LQ-1010
 * - LQ-1050
 * - LQ-1050+
 * - LQ-1060
 * - LQ-1060+
 * - LQ-2550
 * - SQ-850
 * - TLQ-4800
 * - TSQ-4800
 *
 * PHP version 8
 *
 * @category File
 * @package  App\Printers\Epson;
 * @author   Andrias Melianus S <it.andrias@borwita.co.id>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://www.borwita.co.id/
 */
class Escp extends CommandAbstract implements EscpContract
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
