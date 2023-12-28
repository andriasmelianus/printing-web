<?php

namespace App\Borwita\Printing;

use App\WebClientPrint\Escp2;

/**
 * Class to generate ESC/P 2 printer commands to print formatted faktur
 * on a pre-printed continuous paper.
 */
class Faktur
{
    // Units are in millimeters.
    public const PAGE_LENGTH = 140;
    public const MARGIN_TOP = 5;
    public const MARGIN_BOTTOM = 5;
    // Max rows detail
    public const DETAIL_ROWS_MAX = 13;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Generate ESC/P 2 printer commands based on pre-printed continuous paper.
     *
     * Available header elements:
     * - segment_code: WS01_ABV
     * - page_count: 10
     * - company_tax_license: NPWP: 01.682.572.1-641.000, Ijin PBF: FP.10.04/IV/0234/2019
     * - remark_1: Untuk informasi, saran, dan keluhan, silahkan hubungi Customer Service di 081130582777
     * - company_address: Alamat: Jl. RAYA TAMAN 48A - TAMAN, SIDOARJO
     * - company_license_address: Alamat PBF: Jl. RAYA TAMAN 48A RT 005 RW 001 KEL. TAMAN, KEC. TAMAN
     * - company_license_city_province: KAB. SIDOARJO, JAWA TIMUR
     * - number: 01/01/N/2022/537557
     * - credit_note: 0 Hari - 20/10/2022
     * - customer_name: CV (TEST) TEPAT * / SULTAN ISKANDAR
     * - date: 20/10/2022
     * - customer_address: SULTAN ISKANDAR MUDA NO 29-31 AMPEL SEMAMPIR
     * - customer_city_province: KOTA SURABAYA JAWA TIMUR
     * - customer_city_phone: SURABAYA - 081232771066
     * - remark_2: Ket : <CASH> (HA) -    ; Bns KAOSPTNWSL: 4 DIRECT Sep22 Rp. 77,066
     *
     * Available detail elements:
     * - sku: 245124609B
     * - product_name: PTN SHP BLACK 10ml (40+2) NEW
     * - qty: 10 CRT
     * - price: 9,428
     * - discount_regular: 141
     * - discount_program: 732
     * - discount_cash: 64
     * - subtotal: 3,566,130
     *
     * @param array $header
     * @param array $details
     * @return string
     */
    public function generateEscp2Commands(array $header, array $details): string
    {
        $escp2Printer = new Escp2();
        $escp2Printer->initializePrinter()
            ->setPageLengthInDefinedUnit(self::PAGE_LENGTH)
            ->setMarginTopBottom(self::MARGIN_TOP, self::MARGIN_BOTTOM)
            ->setTypeface(Escp2::TYPEFACE_SANS_SERIF_H)
            ->enableProportionalMode()
            ->setFontSize(Escp2::FONT_SIZE_8)

            ->setTabStop([52, 100])
            ->addTab()
            ->addText($header['segment_code'])
            ->addTab()
            ->addText($header['page_count'], true)
            ->resetTabStop()

            ->setTabStop([3, 47])
            ->setLineSpacing18()
            ->addTab()
            ->addText($header['company_tax_license'])
            ->addTab()
            ->addText($header['remark_1'], true)
            ->addTab()
            ->addText($header['company_address'], true)
            ->resetTabStop()

            ->setTabStop([3, 10, 84, 87])
            ->addTab()
            ->addText($header['company_license_address'], true)
            ->setFontSize(Escp2::FONT_SIZE_8)
            ->addTab(2)
            ->addText($header['company_license_city_province'])
            ->addTab(2)
            ->setFontSize(Escp2::FONT_SIZE_10)
            ->setLineSpacingN360(65)
            ->addText($header['number'], true)

            ->addTab()
            ->setFontSize(Escp2::FONT_SIZE_8)
            ->addText('Kepada Yth :')
            ->addTab()
            ->setFontSize(Escp2::FONT_SIZE_10)
            ->addText($header['credit_note'], true)

            ->setLineSpacingN360(10)
            ->addLineFeed()
            ->setLineSpacing18()
            ->resetTabStop()
            ->setTabStop([3, 4, 64])
            ->setFontSize(Escp2::FONT_SIZE_10)
            ->addTab()
            ->addText($header['customer_name'])
            ->addTab()
            ->addText($header['date'], true)
            ->setFontSize(Escp2::FONT_SIZE_8)
            ->addTab()
            ->addText($header['customer_address'], true)
            ->addTab()
            ->addText($header['customer_city_province'], true)
            ->addTab()
            ->addText($header['customer_city_phone'], true)
            ->addLineFeed()
            ->resetTabStop()
            ->addText($header['remark_2'], true)

            // Details configuration.
            ->setFontSize(Escp2::FONT_SIZE_8)
            ->setLineSpacingN360(55)
            ->addLineFeed(3)
            ->setTabStop([2, 12, 46, 63, 73, 81, 89, 96]); // <-- Format faktur pertama (8 kolom).
        // ->setTabStop([1, 11, 43, 58, 68, 75, 82, 88, 96]); // <-- Format faktur kedua (9 kolom).

        // Details data.
        for ($i = 0; $i < count($details); $i++) {
            // ### Begin row 1 details
            $detail = $details[$i];

            $escp2Printer->addTab()
                ->addText($detail['sku'])
                ->addTab()
                ->addText($detail['product_name'])
                ->addTab()
                ->addText($detail['qty'])
                ->addTab()
                ->disableProportionalMode()
                ->enableCondensedFont()
                ->addText(str_pad($detail['price'], 10, ' ', STR_PAD_LEFT))
                ->addTab()
                ->addText(str_pad($detail['discount_regular'], 6, ' ', STR_PAD_LEFT))
                ->addTab()
                ->addText(str_pad($detail['discount_program'], 6, ' ', STR_PAD_LEFT))
                ->addTab()
                ->addText(str_pad($detail['discount_cash'], 6, ' ', STR_PAD_LEFT))
                ->addTab()
                ->addText(str_pad($detail['subtotal'], 12, ' ', STR_PAD_LEFT), true)
                ->disableCondensedFont()
                ->enableProportionalMode();
            // ### End row 1 details;
        }

        return $escp2Printer->addCariageReturn()
            ->addFormFeed()
            ->generate();
    }
}
