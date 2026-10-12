<?php

namespace App\Filament\Resources\AppointmentResource\RelationManagers;

use App\Models\Appointment;
use App\Models\Drug;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Number;
use Illuminate\Database\Eloquent\Model;
use App\Enums\RoleEnum;


class PrescriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'prescriptions';

    // protected $listeners = ['refreshAppointment' => '$refresh'];


    public function form(Form $form): Form
    {
        $appointment = $this->getOwnerRecord();
        //clock()->info($appointment->clinic_user()->first()->clinic_id);
        return $form
            ->schema([
                Section::make(__('Prescription Data'))
                    // ->label('Clinic\'s Basic Info')
                    ->description(__('Required Drug\'s data for the Prescription'))
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        Placeholder::make('clinic')
                            ->label('Clinic')
                            ->content($appointment->clinic_user?->clinic?->name ?? 'Klinik belum ditetapkan'),
                        Forms\Components\Select::make('drug_id')
                            ->options(fn (): array => $this->availableDrugs($appointment->clinic_user?->clinic_id))
                            ->getSearchResultsUsing(fn (string $search): array => $this->availableDrugs(
                                $appointment->clinic_user?->clinic_id,
                                $search,
                            ))
                            ->getOptionLabelUsing(fn ($value): ?string => Drug::query()
                                ->whereKey($value)
                                ->where('clinic_id', $appointment->clinic_user?->clinic_id)
                                ->value('name'))
                            ->searchable()
                            ->required()
                            ->columnSpanFull()
                            ->live()
                            ->helperText(fn (): string => $this->drugAvailabilityMessage($appointment->clinic_user?->clinic_id)),
                        Placeholder::make('drug_category')
                            ->content(function ($get) {
                                $drug = Drug::find($get('drug_id'));
                                $category = ($drug) ? $drug->drug_category()->first()->name : '-';
                                return $category;
                            })
                            ->live(),
                        Placeholder::make('drug_stock')
                            ->content(function ($get) {
                                $drug = Drug::find($get('drug_id'));
                                $stock = ($drug) ? $drug->stock : '-';
                                return $stock;
                            })
                            ->live(),
                        Forms\Components\TextArea::make('dosage_instruction')
                            ->label('Aturan pakai')
                            ->required()
                            ->helperText('Contoh: diminum sesudah makan. Tulis instruksi yang jelas untuk pasien.')
                            ->columnSpanFull(),
                        Forms\Components\TextArea::make('description')
                            ->label('Catatan resep')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('dose_value')->label('Dosis per kali minum')->numeric()->minValue(0.001),
                        Forms\Components\Select::make('dose_unit')->label('Satuan dosis')->options([
                            'mg' => 'mg', 'g' => 'g', 'mcg' => 'mcg', 'mL' => 'mL', 'U' => 'Unit (U)',
                        ])->required(),
                        Forms\Components\Select::make('frequency')->label('Frekuensi')->options([
                            'QD' => '1 kali sehari (QD)', 'BID' => '2 kali sehari (BID)', 'TID' => '3 kali sehari (TID)',
                            'QID' => '4 kali sehari (QID)', 'Q4H' => 'Setiap 4 jam (Q4H)', 'Q6H' => 'Setiap 6 jam (Q6H)',
                        ])->required(),
                        Forms\Components\Select::make('route')->label('Rute pemberian')->options([
                            'oral' => 'Oral', 'intravenous' => 'Intravena', 'topical' => 'Topikal',
                            'inhalation' => 'Inhalasi', 'ophthalmic' => 'Mata', 'rectal' => 'Rektal', 'vaginal' => 'Vaginal',
                        ])->required(),
                        Forms\Components\TextInput::make('duration_days')->label('Durasi (hari)')->numeric()->minValue(1),
                        Forms\Components\Select::make('quantity_unit')->label('Satuan jumlah')->options([
                            'TAB' => 'Tablet', 'CAP' => 'Kapsul', 'mL' => 'mL', 'L' => 'Liter', 'g' => 'Gram', 'mg' => 'mg',
                        ])->default('TAB')->required()->helperText('Tablet/kapsul memakai kode OrderableDrugForm; ukuran volume/berat memakai satuan UCUM.'),
                        // Forms\Components\Toggle::make('is_prepared')->label(__('Is Prepared'))
                        //     ->required(),
                    ]),

                Section::make(__('Price Calculation'))
                    // ->label(__('Automatic calculation for the Treatment\'s Price'))
                    ->description(__('Automatic calculation for the Prescription\'s Price'))
                    ->columnSpan(1)
                    ->collapsible()
                    ->collapsed(false)
                    ->schema([
                        Forms\Components\TextInput::make('quantity')
                            ->label(__('Quantity'))
                            ->required()
                            ->live()
                            ->numeric()
                            ->minValue(1),
                        Placeholder::make('drug_price')
                            ->label(__('Drug Price'))
                            ->content(function ($get) {
                                $drug = Drug::find($get('drug_id'));
                                $price = ($drug) ? $drug->price : 0;
                                return Number::currency($price, 'IDR');
                            })
                            ->live(),
                        Forms\Components\TextInput::make('discount')
                            ->required()
                            ->live()
                            ->suffix('%')
                            ->numeric(),
                        Placeholder::make('amount')
                            ->label(__('Amount'))
                            ->content(function ($get) {
                                $drug = Drug::find($get('drug_id'));
                                $price = ($drug) ? $drug->price : 0;
                                $q = $get('quantity');
                                $p = $price;
                                $d = $get('discount');
                                $a = $q * ($p - ($p * ($d / 100)));
                                return Number::currency($a, 'IDR');
                            })
                            ->live(),
                    ]),
            ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('drug.sku')->label(__('SKU')),
                Tables\Columns\TextColumn::make('drug.name')->label(__('Drug')),
                Tables\Columns\TextColumn::make('drug.drug_category.name')->label(__('Drug Category')),
                Tables\Columns\TextColumn::make('description')->label(__('Description')),
                Tables\Columns\TextColumn::make('dosage_instruction')->label('Instruksi dosis'),
                Tables\Columns\TextColumn::make('pharmacy_status')->label('Status apotik')->badge(),
                Tables\Columns\TextColumn::make('quantity')->label(__('Quantity')),
                Tables\Columns\TextColumn::make('price')->label(__('Price'))->money('IDR'),
                Tables\Columns\TextColumn::make('discount')->label(__('Discount')),
                Tables\Columns\TextColumn::make('amount')->label(__('Amount'))->money('IDR'),
                //Tables\Columns\IconColumn::make('is_prepared')->label(__('Is Prepared'))->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->visible(fn (): bool => auth()->user()?->canManageMedication() ?? false),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->visible(fn () => auth()->user()?->canManageMedication() ?? false),
                // ->after(function ($livewire) {
                //     $livewire->dispatch('refreshAppointment');
                // }),
                Tables\Actions\DeleteAction::make()->visible(fn () => auth()->user()?->canManageMedication() ?? false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    private function availableDrugs(?int $clinicId, ?string $search = null): array
    {
        if (!$clinicId) {
            return [];
        }

        return Drug::query()
            ->where('clinic_id', $clinicId)
            ->where('in_stock', true)
            ->where('stock', '>', 0)
            ->when($search, fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (Drug $drug): array => [
                $drug->id => trim($drug->name . ($drug->sku ? ' : ' . $drug->sku : '')),
            ])
            ->all();
    }

    private function drugAvailabilityMessage(?int $clinicId): string
    {
        if (!$clinicId) {
            return 'Dokter belum terhubung ke klinik. Admin perlu menambahkan dokter melalui data klinik.';
        }

        if (!Drug::query()->where('clinic_id', $clinicId)->where('in_stock', true)->where('stock', '>', 0)->exists()) {
            return 'Belum ada obat dengan stok tersedia di klinik ini. Admin/farmasi perlu mengatur klinik, jumlah stok, dan status tersedia pada menu Obat.';
        }

        return 'Pencarian menampilkan obat dengan stok tersedia di klinik appointment ini.';
    }
}
