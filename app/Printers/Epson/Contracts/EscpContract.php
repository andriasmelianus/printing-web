<?php

namespace App\Printers\Epson\Contracts;

interface EscpContract
{
    public function initialize(bool $clearPreviousCommands);
    public function setPageLength(int $lengthInInches);

    public function set16Spacing();
    public function set18Spacing();

    public function enableProportionalMode();
    public function disableProportionalMode();
    public function enableCondensedFont();
    public function disableCondensedFont();
    public function resetCpi();
    public function setTypeface(int $typefaceCode);
    public function setFont(
        int $cpi = 10,
        bool $isProportional = false,
        bool $isCondensed = false,
        bool $isBold = false,
        bool $isDoubleStrike = false,
        bool $isDoubleWidth = false,
        bool $isItalic = false,
        bool $isUnderline = false
    );

    public function addText(string $text, bool $endWithLineFeed = false);
    public function addBoldText(string $boldText, bool $endWithLineFeed = false);
    public function addItalicText(string $italicText, bool $endWithLineFeed = false);
    public function addTab(int $count = 1);
    public function addVerticalTab(int $count = 1);
    public function addLineFeed(int $count = 1);
    public function addCarriageReturn(int $count = 1);
    public function addFormFeed(int $count = 1);
}
