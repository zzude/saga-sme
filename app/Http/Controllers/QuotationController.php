<?php

// =============================================================================
// QuotationController.php
// app/Http/Controllers/QuotationController.php
// =============================================================================

namespace App\Http\Controllers;

use App\Models\Quotation;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class QuotationController extends Controller
{
    public function pdf(Quotation $quotation)
    {
        $company  = $quotation->company ?? auth()->user()->company;
        $hasSst   = $quotation->items->contains(fn ($i) => $i->sst_amount > 0);

        $pdf = Pdf::loadView('pdf.quotation-pdf', compact('quotation', 'company', 'hasSst'))
            ->setPaper('a4', 'portrait');

        $filename = "{$quotation->quotation_number}.pdf";

        return $pdf->stream($filename);
    }
}


// =============================================================================
// Migration: add quotation_id to invoices table (backlink)
// database/migrations/2026_05_20_000003_add_quotation_id_to_invoices_table.php
// =============================================================================

/*
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('quotation_id')
                ->nullable()
                ->after('company_id');
            // So we can show "Daripada Sebut Harga: QT-2026-00001" on invoice
        });
    }
    public function down(): void {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('quotation_id');
        });
    }
};
*/


// =============================================================================
// routes/web.php — add this route
// =============================================================================

/*
Route::middleware(['auth'])->group(function () {
    Route::get('/quotation/{quotation}/pdf', [QuotationController::class, 'pdf'])
        ->name('quotation.pdf');
});
*/
