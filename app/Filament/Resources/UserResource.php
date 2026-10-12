<?php

namespace App\Filament\Resources;

use App\Enums\GenderEnum;
use App\Enums\RoleEnum;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Split;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;


class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'tabler-user-cog';

    protected static ?string $modelLabel = 'employee';
    protected static ?string $pluralModelLabel = 'employees';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'Clinic Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic Info')
                    ->label('Employee\'s Basic Info')
                    ->description('Contains default data to be used as authentication to the system')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->readOnly()
                            ->dehydrated(false)
                            ->visibleOn('edit'),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required()
                            ->visibleOn('create')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('nik')
                            ->label('NIK')
                            ->length(16)
                            ->rule('digits:16')
                            ->unique(ignoreRecord: true)
                            ->visible(fn (): bool => auth()->user()->hasRole([
                                RoleEnum::SUPER_ADMIN->value,
                                RoleEnum::ADMIN->value,
                            ])),
                        Forms\Components\Select::make('gender')
                            ->options([
                                GenderEnum::MALE->value => GenderEnum::MALE->label(),
                                GenderEnum::FEMALE->value => GenderEnum::FEMALE->label()
                            ]),
                        Forms\Components\TextInput::make('fee')
                            ->label('Fee (For doctor\'s only)')
                            // ->required()
                            ->numeric()
                            ->default(0)
                            ->hidden(fn (Get $get): bool => !in_array(auth()->user()->getRoleNames()->first(), [RoleEnum::SUPER_ADMIN->value, RoleEnum::ADMIN->value, RoleEnum::DOCTOR->value])),
                        Forms\Components\TextInput::make('satusehat_practitioner_id')
                            ->label('SATUSEHAT Practitioner IHS ID')
                            ->visible(fn (): bool => auth()->user()->hasRole([
                                RoleEnum::SUPER_ADMIN->value,
                                RoleEnum::ADMIN->value,
                            ])),
                        Forms\Components\Toggle::make('can_manage_medication')
                            ->label('Boleh mengelola obat tanpa farmasi')
                            ->helperText('Dokter dapat membuat, mengonfirmasi, dan menyelesaikan resep langsung.')
                            ->visible(fn (): bool => auth()->user()?->hasRole(RoleEnum::SUPER_ADMIN->value) ?? false),
                        Forms\Components\Toggle::make('can_access_transactions')
                            ->label('Boleh mengakses transaksi')
                            ->helperText('Izinkan pengguna membuka dan mengelola menu transaksi.')
                            ->visible(fn (): bool => auth()->user()?->hasRole(RoleEnum::SUPER_ADMIN->value) ?? false),

                    ]),

                Section::make('Role Info')
                    ->label('Employee\'s Role')
                    ->description('Contains default role assigned to the employee')
                    ->columnSpan(1)
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->live()
                            ->hidden(fn (Get $get): bool => !in_array(auth()->user()->getRoleNames()->first(), [RoleEnum::SUPER_ADMIN->value, RoleEnum::ADMIN->value])),
                    ]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                if (!in_array($user->getRoleNames()->first(), [RoleEnum::SUPER_ADMIN->value, RoleEnum::ADMIN->value])) {
                    $query->where('id', $user->id);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('nik')
                    ->label('NIK')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn (): bool => auth()->user()->hasRole([
                        RoleEnum::SUPER_ADMIN->value,
                        RoleEnum::ADMIN->value,
                    ])),
                Tables\Columns\TextColumn::make('satusehat_practitioner_id')
                    ->label('SATUSEHAT Practitioner IHS ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn (): bool => auth()->user()->hasRole([
                        RoleEnum::SUPER_ADMIN->value,
                        RoleEnum::ADMIN->value,
                    ])),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => Str::upper($state))
                    ->color(fn (string $state): string => match ($state) {
                        RoleEnum::SUPER_ADMIN->value => 'danger',
                        RoleEnum::ADMIN->value => 'gray',
                        RoleEnum::OPERATOR->value => 'info',
                        RoleEnum::PHARMACIST->value => 'warning',
                        RoleEnum::DOCTOR->value => 'success',
                    })
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('phone')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('gender')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => Str::upper($state->value))
                    ->color(fn (object $state): string => match ($state) {
                        GenderEnum::MALE => 'info',
                        GenderEnum::FEMALE => 'warning',
                    }),
                Tables\Columns\TextColumn::make('fee')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                SelectFilter::make('gender')
                    ->options([
                        GenderEnum::MALE->value => GenderEnum::MALE->label(),
                        GenderEnum::FEMALE->value => GenderEnum::FEMALE->label()
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
