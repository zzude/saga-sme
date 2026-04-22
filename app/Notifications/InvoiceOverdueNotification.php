<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InvoiceOverdueNotification extends Notification
{
    use Queueable;

    public function __construct(public Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return ["database"];
    }

    public function toArray(object $notifiable): array
    {
        return [
            "format"     => "filament",
            "title"      => "Invoice Overdue",
            "body"       => "Invoice {$this->invoice->invoice_no} dari {$this->invoice->customer->name} telah overdue sejak {$this->invoice->due_date->format("d M Y")}. Baki: MYR " . number_format($this->invoice->balance_due, 2),
            "icon"       => "heroicon-o-exclamation-circle",
            "color"      => "danger",
            "invoice_id" => $this->invoice->id,
            "invoice_no" => $this->invoice->invoice_no,
        ];
    }
}
