<?php

namespace App\Notifications;

use App\Models\Bill;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BillDueSoonNotification extends Notification
{
    use Queueable;

    public function __construct(public Bill $bill) {}

    public function via(object $notifiable): array
    {
        return ["database"];
    }

    public function toDatabase(object $notifiable): array
    {
        $daysLeft = now()->diffInDays($this->bill->due_date, false);
        return [
            "title"   => "Bill Due Soon",
            "body"    => "Bill {$this->bill->bill_no} dari {$this->bill->vendor->name} akan due dalam {$daysLeft} hari ({$this->bill->due_date->format("d M Y")}). Amaun: MYR " . number_format($this->bill->total, 2),
            "icon"    => "heroicon-o-clock",
            "color"   => "warning",
            "bill_id" => $this->bill->id,
            "bill_no" => $this->bill->bill_no,
        ];
    }
}
