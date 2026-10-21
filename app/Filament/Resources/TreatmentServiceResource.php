<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TreatmentServiceResource\Pages;
use App\Models\TreatmentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TreatmentServiceResource extends Resource
{
    protected static ?string $model = TreatmentService::class;

    protected static ?string $navigationIcon = 'tabler-receipt-2';

    protected static ?string $navigationParentItem = 'Treatment Categories';

    protected static ?string $navigationLabel = 'Tarif Tindakan';

    protected static ?string $modelLabel = 'tarif tindakan';

    protected static ?string $pluralModelLabel = 'tarif tindakan';

    protected static ?string $navigationGroup = 'Administrasi Klinik';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('treatment_category_id')
                ->label('Kategori tindakan')
                ->relationship('treatmentCategory', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\TextInput::make('name')
                ->label('Nama tindakan')
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('description')
                ->label('Deskripsi')
                ->columnSpanFull(),
            Forms\Components\TextInput::make('price')
                ->label('Tarif default')
                ->numeric()
                ->integer()
                ->minValue(0)
                ->prefix('IDR')
                ->required(),
            Forms\Components\Toggle::make('is_active')
                ->label('Aktif')
                ->default(true)
                ->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama tindakan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('treatmentCategory.name')
                    ->label('Kategori')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Tarif default')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTreatmentServices::route('/'),
            'create' => Pages\CreateTreatmentService::route('/create'),
            'edit' => Pages\EditTreatmentService::route('/{record}/edit'),
        ];
    }
}
