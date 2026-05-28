<?php

namespace App\Filament\Resources\QuotationResource\Schemas;

use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class QuotationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Maklumat Sebut Harga')
                ->columns(3)
                ->schema([
                    TextEntry::make('quotation_number')->label('No. Sebut Harga'),
                    TextEntry::make('revision')->label('Semakan')
                        ->formatStateUsing(fn ($state) => "R{$state}"),
                    TextEntry::make('status')->label('Status')
                        ->badge()
                        ->color(fn ($state) => match($state) {
                            'draft'    => 'gray',
                            'sent'     => 'info',
                            'accepted' => 'success',
                            'rejected' => 'danger',
                            default    => 'gray',
                        }),
                    TextEntry::make('quotation_date')->label('Tarikh')->date('d/m/Y'),
                    TextEntry::make('valid_until')->label('Sah Hingga')->date('d/m/Y'),
                    TextEntry::make('payment_terms_days')->label('Terma Bayaran')->suffix(' hari'),
                ]),

            Section::make('Pelanggan')
                ->columns(2)
                ->schema([
                    TextEntry::make('customer.name')->label('Nama Pelanggan'),
                    TextEntry::make('reference_number')->label('No. Rujukan')->placeholder('-'),
                ]),

            Section::make('Senarai Item')
                ->schema([
                    RepeatableEntry::make('items')
                        ->label('')
                        ->schema([
                            Grid::make(8)->schema([
                                TextEntry::make('line_no')->label('No.')->columnSpan(1),
                                TextEntry::make('description')->label('Penerangan')->columnSpan(2),
                                TextEntry::make('unit_of_measure')->label('Unit')->columnSpan(1),
                                TextEntry::make('quantity')->label('Kuantiti')->numeric(decimalPlaces: 2)->columnSpan(1),
                                TextEntry::make('unit_price')->label('Harga Unit (RM)')->numeric(decimalPlaces: 2)->columnSpan(1),
                                TextEntry::make('discount_percent')->label('Diskaun %')->suffix('%')->columnSpan(1),
                                TextEntry::make('total_amount')->label('Jumlah (RM)')
                                    ->formatStateUsing(fn ($state) => 'RM ' . number_format($state, 2))
                                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                    ->color('primary')
                                    ->columnSpan(1),
                            ]),
                        ]),
                ]),

            Section::make('Ringkasan Jumlah')
                ->columns(3)
                ->schema([
                    TextEntry::make('subtotal')
                        ->label('Subtotal (RM)')
                        ->formatStateUsing(fn ($state) => 'RM ' . number_format($state, 2)),
                    TextEntry::make('discount_amount')
                        ->label('Diskaun (RM)')
                        ->formatStateUsing(fn ($state) => 'RM ' . number_format($state, 2)),
                    TextEntry::make('total_amount')
                        ->label('JUMLAH KESELURUHAN (RM)')
                        ->formatStateUsing(fn ($state) => 'RM ' . number_format($state, 2))
                        ->weight(\Filament\Support\Enums\FontWeight::Bold)
                        ->color('primary'),
                ]),

            Section::make('Terma & Catatan')
                ->columns(2)
                ->schema([
                    TextEntry::make('terms_conditions')->label('Terma & Syarat')->placeholder('-'),
                    TextEntry::make('remarks')->label('Catatan')->placeholder('-'),
                ]),
        ]);
    }
}