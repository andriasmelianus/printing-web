<?php

namespace App\WebClientPrint;

class Escp2
{
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

    /**
     * Convert ESC/P 2 commands in plain string into HEX in byte format ready to send to dot-matrix printer.
     * These are input and output samples:
     * 'esc @'      ==> "0x1b0x40"
     * 'esc em'     ==> "0x1b0x19"
     *
     * @param string $command
     * @return string
     */
    public function convertStringToEscp2Command(string $command): string
    {
        $arrayCommands = explode(' ', $command);
        $arrayEscp2Commands = array_map(function ($command) {
            $commandInLowerCase = strtolower($command);
            $commandInPlainString = $commandInLowerCase;
            foreach (self::REPLACABLES as $replacable) {
                if ($commandInPlainString == $replacable[self::INDEX_FROM]) {
                    $commandInPlainString = $replacable[self::INDEX_TO];
                }
            }

            $commandInDecimal = $commandInPlainString;
            if (!is_numeric($commandInPlainString)) {
                $commandInDecimal = ord($commandInPlainString);
            }

            $commandInHex = (string)dechex($commandInDecimal);
            $commandInHex = str_pad($commandInHex, 2, '0', STR_PAD_LEFT);

            $commandInHexByteFormat = '0x' . $commandInHex;

            return $commandInHexByteFormat;
        }, $arrayCommands);

        return implode('', $arrayEscp2Commands);
    }
}
