<?php

namespace App\Filament\Resources\AppointmentResource\RelationManagers;

use App\Enums\AppointmentStatusEnum;
use App\Models\TreatmentService;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Number;

class TreatmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'treatments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('Treatment Data'))
                    ->description('Required Treatment data')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('treatment_service_id')
                            ->label('Pilih tarif tindakan')
                            ->options(fn (): array => TreatmentService::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn (TreatmentService $service): array => [
                                    $service->id => $service->name . ' - IDR ' . number_format($service->price, 0, ',', '.'),
                                ])
                                ->all())
                            ->getSearchResultsUsing(fn (string $search): array => TreatmentService::query()
                                ->where('is_active', true)
                                ->where('name', 'like', "%{$search}%")
                                ->orderBy('name')
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn (TreatmentService $service): array => [
                                    $service->id => $service->name . ' - IDR ' . number_format($service->price, 0, ',', '.'),
                                ])
                                ->all())
                            ->getOptionLabelUsing(fn ($value): ?string => TreatmentService::query()
                                ->where('is_active', true)
                                ->find($value)?->name)
                            ->searchable()
                            ->native(false)
                            ->visibleOn('create')
                            ->dehydrated(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set): void {
                                $service = TreatmentService::query()
                                    ->where('is_active', true)
                                    ->find($state);

                                if (!$service) {
                                    return;
                                }

                                $set('treatment_category_id', $service->treatment_category_id);
                                $set('name', $service->name);
                                $set('description', $service->description ?: $service->name);
                                $set('price', $service->price);
                            })
                            ->helperText('Tarif default dapat disesuaikan sebelum appointment dikonfirmasi.'),
                        Forms\Components\TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('treatment_category_id')
                            ->label(__('Treatement Category'))
                            ->relationship('treatment_category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\TextArea::make('description')
                            ->label(__('Description'))
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make(__('Price Calculation'))
                    ->description(__('Automatic calculation for the Treatment\'s Price'))
                    ->columnSpan(1)
                    ->collapsible()
                    ->collapsed(false)
                    ->schema([
                        Forms\Components\TextInput::make('quantity')
                            ->label(__('Quantity'))
                            ->required()
                            ->live()
                            ->numeric()
                            ->integer()
                            ->minValue(1),
                        Forms\Components\TextInput::make('price')
                            ->label(__('Price'))
                            ->required()
                            ->live()
                            ->prefix('IDR')
                            ->numeric()
                            ->minValue(0),
                        Forms\Components\TextInput::make('discount')
                            ->label(__('Discount'))
                            ->required()
                            ->live()
                            ->suffix('%')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),
                        Placeholder::make('amount')
                            ->label(__('Amount'))
                            ->content(function ($get) {
                                $q = $get('quantity');
                                $p = $get('price');
                                $d = $get('discount');
                                $a = $q * ($p - ($p * ($d / 100)));
                                return Number::currency($a, 'IDR');
                                // Use $get('products') to get an array of items.
                                // Loop through each item and make a total
                                // Return the total from this function
                            })
                            ->live(),
                    ]),
            ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('price')->money('IDR'),
                Tables\Columns\TextColumn::make('discount'),
                Tables\Columns\TextColumn::make('amount')->money('IDR'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Billing Tindakan')
                    ->visible(fn (): bool => $this->getOwnerRecord()->status !== AppointmentStatusEnum::CONFIRMED),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => $this->getOwnerRecord()->status !== AppointmentStatusEnum::CONFIRMED),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (): bool => $this->getOwnerRecord()->status !== AppointmentStatusEnum::CONFIRMED),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
