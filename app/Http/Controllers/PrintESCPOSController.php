<?php

namespace App\Http\Controllers;

use App\Borwita\Printing\Faktur;
use App\WebClientPrint\Escp2;
use Illuminate\Http\Request;


//*********************************
// IMPORTANT NOTE
// ==============
// If your website requires user authentication, then
// THIS FILE MUST be set to ALLOW ANONYMOUS access!!!
//
//*********************************

//Includes WebClientPrint classes
include_once(app_path() . '/WebClientPrint/WebClientPrint.php');

use Neodynamic\SDK\Web\WebClientPrint;
use Neodynamic\SDK\Web\Utils;
use Neodynamic\SDK\Web\DefaultPrinter;
use Neodynamic\SDK\Web\InstalledPrinter;
use Neodynamic\SDK\Web\PrintFile;
use Neodynamic\SDK\Web\ClientPrintJob;
use PhpParser\Node\Stmt\Echo_;
use Session;

class PrintESCPOSController extends Controller
{
    public const ESC = '0x1B';
    public const LF = '0x0A';
    public const RESET = '@';
    public const TYPEFACE = '0x6B';

    // Initialization
    public const ESC_INITIALIZE = self::ESC . self::RESET;
    public const SETUP_UNIT = self::ESC . '0x28' . '0x55' . '0x01' . '0x00' . '0x0A';
    // Page setup
    public const SETUP_PAGE_LENGTH = self::ESC . '0x28' . '0x43' . '0x02' . '0x00' . '0x78' . '0x0F';
    // LINE SPACING
    public const SPACING_1_8 = self::ESC . '0x30';
    public const SPACING_1_6 = self::ESC . '0x32';
    // TABS
    public const TAB_SETUP = self::ESC . '0x44';
    public const TAB_CLEAR = self::ESC . '0x00';
    // PROPORTIONAL (Non-monospaced)
    public const FONT_PROPORTIONAL_START = self::ESC . '0x70' . '0x01';
    public const FONT_PROPORTIONAL_END = self::ESC . '0x70' . '0x00';
    // CONDENSED
    public const FONT_CONDENSED_ON = self::ESC . '0x0F';
    public const FONT_CONDENSED_OFF = self::ESC . '0x12';
    // BOLD
    public const FONT_BOLD_START = self::ESC . 'E';
    public const FONT_BOLD_END = self::ESC . 'F';
    // ITALIC
    public const FONT_ITALIC_START = self::ESC . '4';
    public const FONT_ITALIC_END = self::ESC . '5';
    // Typeface
    public const TYPEFACE_ROMAN = self::ESC . self::TYPEFACE . '0x00';
    public const TYPEFACE_SANS_SERIF = self::ESC . self::TYPEFACE . '0x01';
    public const TYPEFACE_COURIER = self::ESC . self::TYPEFACE . '0x02';
    public const TYPEFACE_PRESTIGE = self::ESC . self::TYPEFACE . '0x03';
    public const TYPEFACE_SCRIPT = self::ESC . self::TYPEFACE . '0x04';
    public const TYPEFACE_OCR_B = self::ESC . self::TYPEFACE . '0x05';
    public const TYPEFACE_OCR_A = self::ESC . self::TYPEFACE . '0x06';
    public const TYPEFACE_ORATOR = self::ESC . self::TYPEFACE . '0x07';
    public const TYPEFACE_ORATOR_S = self::ESC . self::TYPEFACE . '0x08';
    public const TYPEFACE_SCRIPT_C = self::ESC . self::TYPEFACE . '0x09';
    public const TYPEFACE_ROMAN_T = self::ESC . self::TYPEFACE . '0x0A';
    public const TYPEFACE_SANS_SERIF_H = self::ESC . self::TYPEFACE . '0x0B';
    public const TYPEFACE_SV_BUSABA = self::ESC . self::TYPEFACE . '0x1E';
    public const TYPEFACE_SV_JITTRA = self::ESC . self::TYPEFACE . '0x1F';
    // User-defined characters
    public const USER_DEFINED_CHARACTERS_ON = self::ESC . '0x25' . '0x01';
    public const USER_DEFINED_CHARACTERS_OFF = self::ESC . '0x25' . '0x00';
    // Custom pitch & point
    // 18 characters per inch,
    public const CUSTOM_PITCH_POINT = self::ESC . '0x58' . '0x14' . '0x15' . '0x00';

    // Mechanical control
    // public const PAPER_LOAD_EJECT = self::ESC . '0x19' . '0x31';

    public function printCommands(Request $request)
    {
        if ($request->exists(WebClientPrint::CLIENT_PRINT_JOB)) {
            $useDefaultPrinter = ($request->input('useDefaultPrinter') === 'checked');
            $printerName = urldecode($request->input('printerName'));

            $header = [
                'segment_code' => 'WS01_ABV',
                'page_count' => 10,
                'company_tax_license' => 'NPWP: 01.682.572.1-641.000, Ijin PBF: FP.10.04/IV/0234/2019',
                'remark_1' => 'Untuk informasi, saran, dan keluhan, silahkan hubungi Customer Service di 081130582777',
                'company_address' => 'Alamat: Jl. RAYA TAMAN 48A - TAMAN, SIDOARJO',
                'company_license_address' => 'Alamat PBF: Jl. RAYA TAMAN 48A RT 005 RW 001 KEL. TAMAN, KEC. TAMAN',
                'company_license_city_province' => 'KAB. SIDOARJO, JAWA TIMUR',
                'number' => '01/01/N/2022/537557',
                'credit_note' => '0 Hari - 20/10/2022',
                'customer_name' => 'CV (TEST) TEPAT * / SULTAN ISKANDAR',
                'customer_address' => 'SULTAN ISKANDAR MUDA NO 29-31 AMPEL SEMAMPIR',
                'customer_city_province' => 'KOTA SURABAYA JAWA TIMUR',
                'customer_city_phone' => 'SURABAYA - 081232771066',
                'remark_2' => 'Ket : (HA) - ; Bns KAOSPTNWSL: 4 DIRECT Sep22 Rp. 77,066',
                'date' => '20/10/2022',
            ];
            $details1 = [
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (1)', 'qty' => '10 CRT', 'price' => '9,428', 'discount_regular' => '141', 'discount_program' => '7', 'discount_cash' => '164', 'subtotal' => '3,566,130'],
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (2)', 'qty' => '1 CRT', 'price' => '428', 'discount_regular' => '141', 'discount_program' => '2', 'discount_cash' => '6', 'subtotal' => '123,166,130'],
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (3)', 'qty' => '4 CRT', 'price' => '10,428', 'discount_regular' => '41', 'discount_program' => '32', 'discount_cash' => '64', 'subtotal' => '566,130'],
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (4)', 'qty' => '5 CRT', 'price' => '28', 'discount_regular' => '1', 'discount_program' => '732', 'discount_cash' => '64', 'subtotal' => '120,566,130'],
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (5)', 'qty' => '2 CRT', 'price' => '0', 'discount_regular' => '141', 'discount_program' => '732', 'discount_cash' => '64', 'subtotal' => '6,130'],
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (6)', 'qty' => '12 CRT', 'price' => '28', 'discount_regular' => '1041', 'discount_program' => '1,212', 'discount_cash' => '64', 'subtotal' => '130'],
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (7)', 'qty' => '2 CRT', 'price' => '1,228', 'discount_regular' => '11', 'discount_program' => '0', 'discount_cash' => '64', 'subtotal' => '30'],
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (8)', 'qty' => '1 CRT', 'price' => '928', 'discount_regular' => '11', 'discount_program' => '0', 'discount_cash' => '64', 'subtotal' => '0'],
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (9)', 'qty' => '5 CRT', 'price' => '18', 'discount_regular' => '91', 'discount_program' => '73', 'discount_cash' => '64', 'subtotal' => '1,566,781,130'],
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (10)', 'qty' => '5 CRT', 'price' => '61', 'discount_regular' => '12', 'discount_program' => '2', 'discount_cash' => '64', 'subtotal' => '566,130'],
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (11)', 'qty' => '6 CRT', 'price' => '8', 'discount_regular' => '1', 'discount_program' => '22', 'discount_cash' => '64', 'subtotal' => '26,130'],
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (12)', 'qty' => '5 CRT', 'price' => '158', 'discount_regular' => '118', 'discount_program' => '7', 'discount_cash' => '64', 'subtotal' => '66,130'],
            ];
            $details2 = [
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (13)', 'qty' => '22 CRT', 'price' => '1', 'discount_regular' => '23', 'discount_program' => '78', 'discount_cash' => '64', 'subtotal' => '6,130'],
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (14)', 'qty' => '1 CRT', 'price' => '1', 'discount_regular' => '23', 'discount_program' => '78', 'discount_cash' => '64', 'subtotal' => '6,130'],
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (15)', 'qty' => '5 CRT', 'price' => '1', 'discount_regular' => '23', 'discount_program' => '78', 'discount_cash' => '64', 'subtotal' => '6,130'],
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (16)', 'qty' => '6 CRT', 'price' => '1', 'discount_regular' => '23', 'discount_program' => '78', 'discount_cash' => '64', 'subtotal' => '6,130'],
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (17)', 'qty' => '2 CRT', 'price' => '1', 'discount_regular' => '23', 'discount_program' => '78', 'discount_cash' => '64', 'subtotal' => '6,130'],
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (18)', 'qty' => '2 CRT', 'price' => '1', 'discount_regular' => '23', 'discount_program' => '78', 'discount_cash' => '64', 'subtotal' => '6,130'],
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (19)', 'qty' => '1 CRT', 'price' => '1', 'discount_regular' => '23', 'discount_program' => '78', 'discount_cash' => '64', 'subtotal' => '6,130'],
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (20)', 'qty' => '5 CRT', 'price' => '1', 'discount_regular' => '23', 'discount_program' => '78', 'discount_cash' => '64', 'subtotal' => '6,130'],
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (21)', 'qty' => '6 CRT', 'price' => '1', 'discount_regular' => '23', 'discount_program' => '78', 'discount_cash' => '64', 'subtotal' => '6,130'],
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (22)', 'qty' => '9 CRT', 'price' => '1', 'discount_regular' => '23', 'discount_program' => '78', 'discount_cash' => '64', 'subtotal' => '6,130'],
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (23)', 'qty' => '31 CRT', 'price' => '1', 'discount_regular' => '23', 'discount_program' => '78', 'discount_cash' => '64', 'subtotal' => '6,130'],
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (24)', 'qty' => '2 CRT', 'price' => '1', 'discount_regular' => '23', 'discount_program' => '78', 'discount_cash' => '64', 'subtotal' => '6,130'],
            ];
            $details3 = [
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (25)', 'qty' => '2 CRT', 'price' => '1', 'discount_regular' => '23', 'discount_program' => '78', 'discount_cash' => '64', 'subtotal' => '6,130'],
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (26)', 'qty' => '12 CRT', 'price' => '1', 'discount_regular' => '23', 'discount_program' => '78', 'discount_cash' => '64', 'subtotal' => '6,130'],
                ['sku' => '245124609B', 'product_name' => 'PTN SHP BLACK 10ml (27)', 'qty' => '12 CRT', 'price' => '1', 'discount_regular' => '23', 'discount_program' => '78', 'discount_cash' => '64', 'subtotal' => '6,130'],
            ];
            $faktur = new Faktur();
            $cmds = '';
            $cmds .= $faktur->generateEscp2Commands($header, $details1); // <- 1 halaman faktur
            $cmds .= $faktur->generateEscp2Commands($header, $details2); // <- 1 halaman faktur
            $cmds .= $faktur->generateEscp2Commands($header, $details3); // <- 1 halaman faktur

            // $printer = new Escp2();
            // $cmds = $printer->initializePrinter()
            //     ->setPageLengthInDefinedUnit(140)
            //     ->setMarginTopBottom(5, 5)
            //     ->setTypeface(Escp2::TYPEFACE_PRESTIGE)
            //     ->setFontSize(Escp2::FONT_SIZE_8)
            //     ->addText('2,345,678,901', true)
            //     ->setFontSize(Escp2::FONT_SIZE_8, 12)
            //     ->addBoldText('2,345,678,901', true)
            //     ->setFontSize(Escp2::FONT_SIZE_8, 15)
            //     ->addBoldText('2,345,678,901', true)

            //     ->addCarriageReturn()
            //     ->addFormFeed()
            //     ->generate();

            // $cmds = self::ESC_INITIALIZE;
            // $cmds .= 'Initialize printer...' . self::LF;

            // $cmds .= self::SETUP_PAGE_LENGTH;

            // $cmds .= self::FONT_PROPORTIONAL_START; // Non-monospaced text
            // $cmds .= 'Text with proportional on' . self::LF;

            // $cmds .= self::SPACING_1_8; // 1/8-inch line spacing
            // $cmds .= 'Spacing 1/8 selected' . self::LF;

            // $cmds .= self::FONT_BOLD_START . 'Bold text' . self::FONT_BOLD_END . self::LF;

            // $cmds .= self::FONT_ITALIC_START . 'Italic text' . self::FONT_ITALIC_END . self::LF;

            // $cmds .= self::USER_DEFINED_CHARACTERS_OFF;
            // $cmds .= 'User defined switched off.' . self::LF;

            // $cmds .= self::TYPEFACE_SANS_SERIF;
            // $cmds .= 'User defined switched off. But with sans serif typeface.' . self::LF . self::LF;

            // $cmds .= self::TAB_SETUP . '0x14' . '0x28' . '0x3C' . '0x00';
            // $cmds .= 'Setting up pre-defined TABs.' . self::LF;
            // $cmds .= 'First text' . '0x09' . '[TAB] Second text' . '0x09' . '[TAB] Third text' . self::LF;
            // $cmds .= self::FONT_CONDENSED_ON;
            // $cmds .= 'Turning on condensed text...' . self::LF;
            // $cmds .= 'First text' . '0x09' . '[TAB] Second text' . '0x09' . '[TAB] Third text' . self::LF;
            // $cmds .= self::FONT_CONDENSED_OFF;
            // $cmds .= 'Turning off condensed text...' . self::LF . self::LF;
            // $cmds .= 'Setting up PITCH & POINT...' . self::LF;
            // $cmds .= self::CUSTOM_PITCH_POINT;
            // $cmds .= 'Untuk informasi, saran, dan keluhan, silakan hubungi Customer Service di 081130582777' . self::LF;
            // $cmds .= 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Architecto dolorum facilis maxime quia, nesciunt, voluptatem excepturi voluptas aut asperiores molestiae, eveniet dolorem nulla neque impedit aperiam possimus voluptatibus quisquam itaque. ';

            // $cmds .= self::ESC_INITIALIZE;



            // SAMPLE FROM OFFICIAL WEBSITE.
            //Create ESC/POS commands for sample receipt
            // $esc = '0x1B'; //ESC byte in hex notation
            // $newLine = '0x0A'; //LF byte in hex notation

            // $cmds = '';
            // $cmds = $esc . "@"; //Initializes the printer (ESC @)
            // $cmds .= $esc . '!' . '0x38'; //Emphasized + Double-height + Double-width mode selected (ESC ! (8 + 16 + 32)) 56 dec => 38 hex
            // $cmds .= 'BEST DEAL STORES'; //text to print
            // $cmds .= $newLine . $newLine;
            // $cmds .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
            // $cmds .= 'COOKIES                   5.00';
            // $cmds .= $newLine;
            // $cmds .= 'MILK 65 Fl oz             3.78';
            // $cmds .= $newLine . $newLine;
            // $cmds .= 'SUBTOTAL                  8.78';
            // $cmds .= $newLine;
            // $cmds .= 'TAX 5%                    0.44';
            // $cmds .= $newLine;
            // $cmds .= 'TOTAL                     9.22';
            // $cmds .= $newLine;
            // $cmds .= 'CASH TEND                10.00';
            // $cmds .= $newLine;
            // $cmds .= 'CASH DUE                  0.78';
            // $cmds .= $newLine . $newLine;
            // $cmds .= $esc . '!' . '0x18'; //Emphasized + Double-height mode selected (ESC ! (16 + 8)) 24 dec => 18 hex
            // $cmds .= '# ITEMS SOLD 2';
            // $cmds .= $esc . '!' . '0x00'; //Character font A selected (ESC ! 0)
            // $cmds .= $newLine . $newLine;
            // $cmds .= '11/03/13  19:53:17';

            //Create a ClientPrintJob obj that will be processed at the client side by the WCPP
            $cpj = new ClientPrintJob();
            //set ESCPOS commands to print...
            $cpj->printerCommands = $cmds;
            $cpj->formatHexValues = true;

            if ($useDefaultPrinter || $printerName === 'null') {
                $cpj->clientPrinter = new DefaultPrinter();
            } else {
                $cpj->clientPrinter = new InstalledPrinter($printerName);
            }

            //Send ClientPrintJob back to the client
            return response($cpj->sendToClient())
                ->header('Content-Type', 'application/octet-stream');
        }
    }
}
