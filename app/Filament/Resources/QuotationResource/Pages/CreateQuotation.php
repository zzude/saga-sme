<?php

namespace App\Filament\Resources\QuotationResource\Pages;

use App\Filament\Resources\QuotationResource;
use App\Models\Quotation;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = QuotationResource::class;

    /**
     * Provide a concrete model instance instead of a class string so that
     * Filament's getModelInstance() never falls through to app($classString).
     * On CREATE, defaultForm() passes the class string, which causes the
     * container to fire resolution callbacks in a recursive spiral.
     */
    public function defaultForm(Schema $schema): Schema
    {
        return parent::defaultForm($schema)->model(new Quotation());
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $generated = Quotation::generateQuotationNumber(0);
        $data['quotation_number']   = $generated['number'];
        $data['quotation_ref']      = $generated['ref'];
        $data['revision']           = 0;
        $data['is_latest_revision'] = true;
        $data['company_id']         = auth()->user()->company_id;
        $data['created_by']         = auth()->id();
        $data['status']             = 'draft';
        return $data;
    }

    protected function afterCreate(): void
    {
        // Fix company_id on all items
        $this->record->items()->update([
            'company_id' => $this->record->company_id,
        ]);

        // Re-number lines
        $lineNo = 1;
        foreach ($this->record->items()->orderBy('id')->get() as $item) {
            $item->update(['line_no' => $lineNo++]);
        }

        // Recalculate totals
        $this->record->recalculateTotals();
    }

    protected function getRedirectUrl(): string
    {
        return QuotationResource::getUrl('view', ['record' => $this->record]);
    }
}
