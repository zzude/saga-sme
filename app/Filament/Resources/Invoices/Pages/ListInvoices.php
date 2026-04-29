<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Jobs\SubmitConsolidatedEInvoiceJob;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Carbon;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            Action::make('submitConsolidated')
                ->label('Consolidated e-Invoice')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->form([
                    Select::make('month')
                        ->label('Month')
                        ->options(
                            collect(range(1, 12))->mapWithKeys(
                                fn ($m) => [$m => Carbon::create()->month($m)->format('F')]
                            )
                        )
                        ->default(now()->subMonth()->month)
                        ->required(),
                    Select::make('year')
                        ->label('Year')
                        ->options(
                            collect(range(now()->year - 1, now()->year))
                                ->mapWithKeys(fn ($y) => [$y => $y])
                        )
                        ->default(now()->subMonth()->year)
                        ->required(),
                ])
                ->action(function (array $data) {
                    SubmitConsolidatedEInvoiceJob::dispatch(
                        auth()->user()->company_id,
                        (int) $data['year'],
                        (int) $data['month'],
                    );

                    Notification::make()
                        ->title('Consolidated e-Invoice job dispatched!')
                        ->body('Invoices for ' . Carbon::create()->month($data['month'])->format('F') . ' ' . $data['year'] . ' will be submitted to MyInvois.')
                        ->success()
                        ->send();
                }),
        ];
    }
}