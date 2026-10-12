<?php

namespace App\Filament\Resources;

use App\Enums\RoleEnum;
use App\Filament\Resources\DrugResource\Pages;
use App\Filament\Resources\DrugResource\RelationManagers;
use App\Models\Drug;
use App\Services\Satusehat\KfaCatalogClient;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DrugResource extends Resource
{
    protected static ?string $model = Drug::class;

    protected static ?string $navigationIcon = 'tabler-pills';

    // protected static ?string $modelLabel = 'employee';
    // protected static ?string $pluralModelLabel = 'employees';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'Medication Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('clinic_id')
                    ->relationship('clinic', 'name')
                    ->required(),
                Forms\Components\Select::make('drug_category_id')
                    ->relationship('drug_category', 'name')
                    ->required(),
                Forms\Components\TextInput::make('sku')
                    ->label('SKU')
                    ->maxLength(255),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Section::make('Pemetaan SATUSEHAT')
                    ->description('Cari nama obat, kekuatan, atau merek. Pilih template untuk resep atau produk aktual yang sesuai dengan stok.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('kfa_code')->label('Cari produk KFA')->searchable()->live()
                            ->getSearchResultsUsing(fn (string $search): array => app(KfaCatalogClient::class)->searchMedicines($search))
                            ->getOptionLabelUsing(fn ($value, Forms\Get $get): ?string => filled($get('kfa_display')) ? $get('kfa_display') . ' — KFA ' . $value : $value)
                            ->afterStateUpdated(function ($state, Forms\Set $set): void {
                                $set('kfa_display', filled($state) ? app(KfaCatalogClient::class)->findMedicineDisplay((string) $state) : null);
                            })
                            ->helperText(fn (): string => app(KfaCatalogClient::class)->isConfigured()
                                ? 'Masukkan minimal 2 karakter. Kredensial API KFA perlu disetujui Kemenkes.'
                                : 'Pencarian otomatis belum aktif. Isi kredensial API KFA yang telah disetujui Kemenkes; kode lama tetap tersimpan.'),
                        Forms\Components\TextInput::make('kfa_display')->label('Nama KFA')->maxLength(255),
                        Forms\Components\TextInput::make('kfa_system')->label('Sistem kode')->default('http://sys-ids.kemkes.go.id/kfa')->required(),
                        Forms\Components\TextInput::make('dosage_form')->label('Bentuk sediaan')->maxLength(100),
                        Forms\Components\TextInput::make('dosage_form_code')->label('Kode bentuk sediaan')->maxLength(32),
                    ]),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('stock')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('IDR'),
                Forms\Components\Toggle::make('in_stock')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                if (!$user->hasRole(RoleEnum::SUPER_ADMIN->value) && !$user->hasRole(RoleEnum::ADMIN->value)) {
                    $clinicId = $user->clinics()->value('clinics.id');
                    if (!$clinicId) {
                        $query->whereRaw('1 = 0');
                        return;
                    }

                    $query->where('clinic_id', $clinicId);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('clinic.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('drug_category.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('kfa_code')->label('KFA')->searchable(),
                Tables\Columns\TextColumn::make('stock')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\IconColumn::make('in_stock')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDrugs::route('/'),
            'create' => Pages\CreateDrug::route('/create'),
            'view' => Pages\ViewDrug::route('/{record}'),
            'edit' => Pages\EditDrug::route('/{record}/edit'),
        ];
    }
}
