@extends('layouts.app')

@section('body')
    <h1>How to directly Print ESCPOS Commands without Preview or Printer Dialog</h1>
    <label class="checkbox">
        <input type="checkbox" id="useDefaultPrinter" /> <strong>Use default printer</strong> or...
    </label>
    <div id="loadPrinters">
        <br />
        WebClientPrint can detect the installed printers in your machine.
        <br />
        <input type="button" onclick="javascript:jsWebClientPrint.getPrinters();" value="Load installed printers..." />

        <br /><br />
    </div>
    <div id="installedPrinters" style="visibility:hidden">
        <br />
        <label for="installedPrinterName">Select an installed Printer:</label>
        <select name="installedPrinterName" id="installedPrinterName"></select>
    </div>

    <br /><br />
    <input type="button" style="font-size:18px" onclick="javascript:jsWebClientPrint.print('useDefaultPrinter=' + $('#useDefaultPrinter').attr('checked') + '&printerName=' + $('#installedPrinterName').val());" value="Print Label..." />
@endsection

@section('scripts')
    <script type="text/javascript">
        var wcppGetPrintersTimeout_ms = 30000; //60 sec
        var wcppGetPrintersTimeoutStep_ms = 500; //0.5 sec

        function wcpGetPrintersOnSuccess() {
            // Display client installed printers
            if (arguments[0].length > 0) {
                var p = arguments[0].split("|");
                var options = '';
                for (var i = 0; i < p.length; i++) {
                    options += '<option>' + p[i] + '</option>';
                }
                $('#installedPrinters').css('visibility', 'visible');
                $('#installedPrinterName').html(options);
                $('#installedPrinterName').focus();
                $('#loadPrinters').hide();
            } else {
                alert("No printers are installed in your system.");
            }
        }

        function wcpGetPrintersOnFailure() {
            // Do something if printers cannot be got from the client
            alert("No printers are installed in your system.");
        }
    </script>


    {!! // Register the WebClientPrint script code
        // The $wcpScript was generated by PrintESCPOSController@index
    
        $wcpScript !!}
@endsection
