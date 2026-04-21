<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/invoice/{invoice}/pdf', function (\App\Models\Invoice $invoice) {
    $invoice->load(['customer', 'lines', 'period']);
    $company = \App\Models\Company::find($invoice->company_id);

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.invoice-pdf', [
        'invoice' => $invoice,
        'company' => $company,
    ])->setPaper('a4', 'portrait')
      ->set_option('margin_top', '0')
      ->set_option('margin_bottom', '0')
      ->set_option('margin_left', '0')
      ->set_option('margin_right', '0');

    return response()->streamDownload(
        fn () => print($pdf->output()),
        $invoice->invoice_no . '.pdf'
    );
})->name('invoice.pdf')->middleware('auth');

Route::get('/bill/{bill}/pdf', function (\App\Models\Bill $bill) {
    $bill->load(['vendor', 'lines', 'period']);
    $company = \App\Models\Company::find($bill->company_id);

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.bill-pdf', [
        'bill'    => $bill,
        'company' => $company,
    ])->setPaper('a4', 'portrait')
      ->set_option('margin_top', '0')
      ->set_option('margin_bottom', '0')
      ->set_option('margin_left', '0')
      ->set_option('margin_right', '0');

    return response()->streamDownload(
        fn () => print($pdf->output()),
        $bill->bill_no . '.pdf'
    );
})->name('bill.pdf')->middleware('auth');