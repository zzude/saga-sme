<?php

namespace App\Filament\Resources\Invoices\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->required(),
                Select::make('period_id')
                    ->relationship('period', 'name')
                    ->required(),
                TextInput::make('invoice_no')
                    ->required(),
                DatePicker::make('date')
                    ->required(),
                DatePicker::make('due_date')
                    ->required(),
                Select::make('status')
                    ->options([
            'draft' => 'Draft',
            'sent' => 'Sent',
            'partial' => 'Partial',
            'paid' => 'Paid',
            'overdue' => 'Overdue',
            'void' => 'Void',
        ])
                    ->default('draft')
                    ->required(),
                TextInput::make('currency_code')
                    ->required()
                    ->default('MYR'),
                TextInput::make('exchange_rate')
                    ->required()
                    ->numeric()
                    ->default(1.0),
                TextInput::make('subtotal')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('tax_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('total')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('paid_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('balance_due')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Textarea::make('notes')
                    ->default(null)
                    ->columnSpanFull(),
                DateTimePicker::make('posted_at'),
                TextInput::make('created_by')
                    ->required()
                    ->numeric(),
                TextInput::make('updated_by')
                    ->numeric()
                    ->default(null),
            ]);
    }
}
