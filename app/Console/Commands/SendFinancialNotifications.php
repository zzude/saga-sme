<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Bill;
use App\Models\User;
use App\Notifications\InvoiceOverdueNotification;
use App\Notifications\BillDueSoonNotification;
use Illuminate\Console\Command;

class SendFinancialNotifications extends Command
{
    protected $signature = "notify:financial";
    protected $description = "Send overdue invoice and due soon bill notifications";

    public function handle(): void
    {
        $recipients = User::role(["super_admin", "admin", "treasurer"])->get();

        // Overdue invoices
        $overdueInvoices = Invoice::whereIn("status", ["sent", "partial"])
            ->whereDate("due_date", "<", now())
            ->with("customer")
            ->get();

        foreach ($overdueInvoices as $invoice) {
            foreach ($recipients as $user) {
                $user->notify(new InvoiceOverdueNotification($invoice));
            }
        }

        $this->info("Sent overdue notifications for {$overdueInvoices->count()} invoice(s).");

        // Bills due within 7 days
        $dueSoonBills = Bill::whereIn("status", ["draft", "partial"])
            ->whereDate("due_date", ">", now())
            ->whereDate("due_date", "<=", now()->addDays(7))
            ->with("vendor")
            ->get();

        foreach ($dueSoonBills as $bill) {
            foreach ($recipients as $user) {
                $user->notify(new BillDueSoonNotification($bill));
            }
        }

        $this->info("Sent due soon notifications for {$dueSoonBills->count()} bill(s).");
    }
}
