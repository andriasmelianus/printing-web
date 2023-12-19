<?php

namespace App\Http\Controllers;

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

use Session;

class PrintESCPOSController extends Controller
{
    public const ESC = '0x1B';
    public const LF = '0x0A';
    public const RESET = '@';

    public const ESC_INITIALIZE = self::ESC . self::RESET;
    // BOLD
    public const FONT_BOLD_START = self::ESC . 'E';
    public const FONT_BOLD_END = self::ESC . 'F';
    // ITALIC
    public const FONT_ITALIC_START = self::ESC . '4';
    public const FONT_ITALIC_END = self::ESC . '5';
    // PROPORTIONAL (Non-monospaced)
    public const FONT_PROPORTIONAL_START = self::ESC . 'p1';
    public const FONT_PROPORTIONAL_END = self::ESC . 'p0';
    // Typeface
    public const TYPEFACE_ROMAN = self::ESC . 'k0';
    public const TYPEFACE_SANS_SERIF = self::ESC . 'k1';
    public const TYPEFACE_COURIER = self::ESC . 'k2';
    public const TYPEFACE_PRESTIGE = self::ESC . 'k3';
    public const TYPEFACE_SCRIPT = self::ESC . 'k4';
    public const TYPEFACE_OCR_B = self::ESC . 'k5';
    public const TYPEFACE_OCR_A = self::ESC . 'k6';
    public const TYPEFACE_ORATOR = self::ESC . 'k7';
    public const TYPEFACE_ORATOR_S = self::ESC . 'k8';
    public const TYPEFACE_SCRIPT_C = self::ESC . 'k9';
    public const TYPEFACE_ROMAN_T = self::ESC . 'kA';
    public const TYPEFACE_SANS_SERIF_H = self::ESC . 'kB';
    public const TYPEFACE_SV_BUSABA = self::ESC . 'k' . '0x1E';
    public const TYPEFACE_SV_JITTRA = self::ESC . 'k' . '0x1F';

    public function printCommands(Request $request)
    {

        if ($request->exists(WebClientPrint::CLIENT_PRINT_JOB)) {

            $useDefaultPrinter = ($request->input('useDefaultPrinter') === 'checked');
            $printerName = urldecode($request->input('printerName'));

            $cmds = self::ESC_INITIALIZE;
            $cmds .= self::ESC . '0'; // 1/8-inch line spacing
            $cmds .= self::ESC . 'g'; // Font 15 cpi

            $cmds .= self::FONT_PROPORTIONAL_START;
            $cmds .= 'NPWP: 01.682.572.1-641.000, Ijin PBF: FP.1293819' . self::LF;
            $cmds .= 'Alamat: JL. RAYA TAMAN 48A' . self::LF;
            $cmds .= self::FONT_BOLD_START . 'Alamat: JL. RAYA TAMAN 48A' . self::FONT_BOLD_END . self::LF;
            $cmds .= self::FONT_ITALIC_START . 'Alamat: JL. RAYA TAMAN 48A' . self::FONT_ITALIC_END . self::LF;
            // Non-monospaced
            // Typeface variations
            $cmds .= self::TYPEFACE_SANS_SERIF;
            $cmds .= 'Alamat: JL. RAYA TAMAN 48A'  . self::LF;
            $cmds .= self::TYPEFACE_COURIER;
            $cmds .= 'Alamat: JL. RAYA TAMAN 48A' . self::LF;
            $cmds .= self::TYPEFACE_PRESTIGE;
            $cmds .= 'Alamat: JL. RAYA TAMAN 48A' . self::LF;
            $cmds .= self::TYPEFACE_SCRIPT;
            $cmds .= 'Alamat: JL. RAYA TAMAN 48A' . self::LF;
            $cmds .= self::FONT_PROPORTIONAL_END;
            // Monospaced Roman
            $cmds .= self::TYPEFACE_ROMAN;
            $cmds .= 'Alamat: JL. RAYA TAMAN 48A' . self::LF;
            $cmds .= 'Alamat: JL. RAYA TAMAN 48A' . self::LF;
            $cmds .= 'Alamat: JL. RAYA TAMAN 48A' . self::LF;
            $cmds .= 'Alamat: JL. RAYA TAMAN 48A' . self::LF;

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
