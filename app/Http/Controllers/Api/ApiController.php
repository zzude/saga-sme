<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\JournalLine;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    public function customers(Request $request): JsonResponse
    {
        $customers = Customer::where("company_id", $request->user()->company_id)
            ->where("is_active", true)
            ->select("id", "customer_code", "name", "email", "phone", "credit_term_days", "credit_limit")
            ->paginate(50);

        return response()->json($customers);
    }

    public function invoices(Request $request): JsonResponse
    {
        $invoices = Invoice::where("company_id", $request->user()->company_id)
            ->with(["customer:id,name", "period:id,name"])
            ->select("id", "invoice_no", "customer_id", "period_id", "date", "due_date", "status", "total", "balance_due")
            ->latest()
            ->paginate(50);

        return response()->json($invoices);
    }

    public function trialBalance(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $accounts = Account::where("company_id", $companyId)
            ->where("level", 3)
            ->withSum(["journalLines as total_debit" => function($q) {
                $q->whereHas("journal", fn($q) => $q->where("status", "posted"));
            }], "debit")
            ->withSum(["journalLines as total_credit" => function($q) {
                $q->whereHas("journal", fn($q) => $q->where("status", "posted"));
            }], "credit")
            ->get()
            ->map(fn($a) => [
                "code"    => $a->code,
                "name"    => $a->name,
                "type"    => $a->type,
                "debit"   => round($a->total_debit ?? 0, 2),
                "credit"  => round($a->total_credit ?? 0, 2),
                "balance" => round(($a->total_debit ?? 0) - ($a->total_credit ?? 0), 2),
            ]);

        return response()->json([
            "data"       => $accounts,
            "generated"  => now()->toDateTimeString(),
            "company_id" => $companyId,
        ]);
    }

    public function generateToken(Request $request): JsonResponse
    {
        $request->validate(["name" => "required|string"]);
        $token = $request->user()->createToken($request->name);
        return response()->json(["token" => $token->plainTextToken]);
    }

    public function revokeTokens(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();
        return response()->json(["message" => "All tokens revoked."]);
    }
}
