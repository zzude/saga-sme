<?php

namespace App\Filament\App\Resources\CompanyTaxProfileResource\Pages;

use App\Filament\App\Resources\CompanyTaxProfileResource;
use App\Models\CompanyTaxProfile;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Auth;

class ManageCompanyTaxProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = CompanyTaxProfileResource::class;
    protected string $view = 'filament.app.pages.manage-company-tax-profile';

    public ?int $id = null;
    public ?string $tax_reg_no = null;
    public ?string $tax_type = 'service';
    public ?string $effective_date = null;
    public bool $is_registered = false;

    public function mount(): void
    {
        $companyId = Auth::user()->company_id;
        $profile = CompanyTaxProfile::where('company_id', $companyId)->first();

        if ($profile) {
            $this->id           = $profile->id;
            $this->tax_reg_no   = $profile->tax_reg_no;
            $this->tax_type     = $profile->tax_type;
            $this->effective_date = $profile->effective_date?->format('Y-m-d');
            $this->is_registered = $profile->is_registered;
        }

        $this->form->fill([
            'tax_reg_no'     => $this->tax_reg_no,
            'tax_type'       => $this->tax_type,
            'effective_date' => $this->effective_date,
            'is_registered'  => $this->is_registered,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('tax_reg_no')
                ->label('SST Registration No')
                ->placeholder('B16-xxxxxxxx')
                ->nullable(),
            Select::make('tax_type')
                ->label('Tax Type')
                ->options([
                    'sales'   => 'Sales Tax',
                    'service' => 'Service Tax',
                    'both'    => 'Both',
                ])
                ->required(),
            DatePicker::make('effective_date')
                ->label('Effective Date')
                ->nullable(),
            Toggle::make('is_registered')
                ->label('SST Registered')
                ->default(false),
        ])->columns(2);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Profile')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action(function () {
                    $data = $this->form->getState();
                    $companyId = Auth::user()->company_id;

                    CompanyTaxProfile::updateOrCreate(
                        ['company_id' => $companyId],
                        $data
                    );

                    Notification::make()
                        ->title('SST Profile saved!')
                        ->success()
                        ->send();
                }),
        ];
    }
}