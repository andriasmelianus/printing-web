<?php

namespace App\Printers\Epson\Abstracts;

/**
 * This class helps the Epson printers command generation process.
 *
 * PHP version 8
 *
 * @category File
 * @package  App\Printers\Epson\Abstracts;
 * @author   Andrias Melianus S <it.andrias@borwita.co.id>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://www.borwita.co.id/
 */
abstract class CommandAbstract
{
    // Constant values.
    public const OUTPUT_FORMAT_PLAIN_TEXT = 1;
    public const OUTPUT_FORMAT_HEXBYTE = 2;
    public const PAGE_LENGTH_MAXIMUM = 22;
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
    // Protected properties.
    protected $proportionalModeEnabled;
    protected $condensedFontEnabled;
    // Private properties.
    private $outputFormat;
    private $commands;

    /**
     * Constructor.
     *
     * @param int $outputFormat
     * @return void
     */
    public function __construct(int $outputFormat = self::OUTPUT_FORMAT_HEXBYTE)
    {
        $this->outputFormat = $outputFormat;
        $this->commands = [];
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
    private function convertStringToHexByteCommand(string $stringCommand): string
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
     * Get formatted command string ready to be sent to the printer device.
     * This function is intended to give more option in command output format.
     *
     * @param string $command
     * @return string
     */
    private function translateCommand(string $command): string
    {
        $formattedCommand = $command;
        switch ($this->outputFormat) {
            case self::OUTPUT_FORMAT_HEXBYTE:
                $formattedCommand = $this->convertStringToHexByteCommand($command);
                break;
        }
        return $formattedCommand;
    }

    /**
     * Convert milimeter into inch value.
     *
     * @param float $milimeter
     * @return float
     */
    public function convertMilimeterToInch(float $milimeter): float
    {
        return $milimeter / 25.4;
    }

    /**
     * Convert inch into milimeter value.
     *
     * @param float $inch
     * @return float
     */
    public function convertInchToMilimeter(float $milimeter): float
    {
        return $milimeter * 25.4;
    }

    /**
     * Add a command to the command list.
     *
     * @param string $newCommand
     * @return void
     */
    public function addCommand(string $newCommand): void
    {
        array_push($this->commands, $this->translateCommand($newCommand));
    }

    /**
     * Add command to set page length in inches.
     *
     * @param integer $lengthInInches
     * @return void
     */
    protected function appendPageLengthCommand(int $lengthInInches): void
    {
        $pageLength = $lengthInInches > self::PAGE_LENGTH_MAXIMUM ? self::PAGE_LENGTH_MAXIMUM : abs($lengthInInches);

        $this->addCommand('esc C nul ' . $pageLength);
    }

    /**
     * Add command to set line spacing to 1/6 inch.
     *
     * @return void
     */
    protected function append16SpacingCommand(): void
    {
        $this->addCommand('esc 2');
    }

    /**
     * Append command to change typeface supported by printer.
     *
     * @param integer $typefaceCode
     * @return void
     */
    protected function appendTypefaceCommand(int $typefaceCode): void
    {
        $this->addCommand('esc k ' . $typefaceCode);
    }

    /**
     * Add command to set line spacing to 1/8 inch.
     *
     * @return void
     */
    protected function append18SpacingCommand(): void
    {
        $this->addCommand('esc 0');
    }

    /**
     * Add plain text to the command list.
     * Plain text will be printed as it is.
     *
     * @param string $text
     * @return void
     */
    protected function appendPlainTextCommand(string $text): void
    {
        array_push($this->commands, $text);
    }

    /**
     * Add bold text to the command list.
     *
     * @param string $text
     * @return void
     */
    protected function appendBoldTextCommand(string $boldText): void
    {
        $this->addCommand('esc E');
        $this->appendPlainTextCommand($boldText);
        $this->addCommand('esc F');
    }

    /**
     * Add italic text to the command list.
     *
     * @param string $text
     * @return void
     */
    protected function appendItalicTextCommand(string $italicText): void
    {
        $this->addCommand('esc 4');
        $this->appendPlainTextCommand($italicText);
        $this->addCommand('esc 5');
    }

    /**
     * Add tab character.
     *
     * @param int $count
     * @return void
     */
    protected function appendTabCommand(int $count = 1): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->addCommand('ht');
        }
    }

    /**
     * Add vertical tab character.
     *
     * @param integer $count
     * @return void
     */
    protected function appendVerticalTabCommand(int $count = 1): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->addCommand('vt');
        }
    }

    /**
     * Add line-feed character.
     *
     * @param int $count
     * @return void
     */
    protected function appendLineFeedCommand(int $count = 1): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->addCommand('lf');
        }
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
     * @return void
     */
    protected function appendCarriageReturnCommand(int $count = 1): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->addCommand('cr');
        }
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
     * @return void
     */
    protected function appendFormFeedCommand(int $count = 1): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->addCarriageReturn(); // As recommended by reference manual.
            $this->addCommand('ff');
        }
    }

    /**
     * Generate commands.
     *
     * @return string
     */
    public function generate(): string
    {
        return implode('', $this->commands);
    }

    /**
     * Clear existing commands.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->commands = [];
    }
}
