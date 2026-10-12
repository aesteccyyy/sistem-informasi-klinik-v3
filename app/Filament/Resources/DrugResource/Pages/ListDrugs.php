<?php

namespace App\Filament\Resources\DrugResource\Pages;

use App\Enums\RoleEnum;
use App\Filament\Resources\DrugResource;
use App\Models\Clinic;
use App\Models\Drug;
use App\Models\DrugCategory;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListDrugs extends ListRecords
{
    protected static string $resource = DrugResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('exportDrugs')
                ->label('Ekspor ke Excel (CSV)')
                ->icon('heroicon-o-arrow-down-tray')
                ->form([
                    Select::make('clinic_id')
                        ->label('Klinik')
                        ->options(fn () => static::clinicOptions())
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data) {
                    abort_unless(static::canAccessClinic((int) $data['clinic_id']), 403);

                    $clinic = Clinic::findOrFail($data['clinic_id']);
                    $rows = Drug::query()->with('drug_category')
                        ->where('clinic_id', $clinic->id)
                        ->orderBy('name')
                        ->get();

                    return response()->streamDownload(function () use ($rows) {
                        $handle = fopen('php://output', 'w');
                        // Excel recognizes this marker and opens Indonesian text as UTF-8.
                        fwrite($handle, "\xEF\xBB\xBF");
                        fputcsv($handle, ['SKU', 'Nama', 'Kategori', 'Deskripsi', 'Stok', 'Harga', 'Tersedia', 'KFA Code', 'KFA Name', 'KFA System', 'Bentuk Sediaan', 'Kode Bentuk Sediaan'], ';');
                        foreach ($rows as $drug) {
                            fputcsv($handle, [
                                $drug->sku,
                                $drug->name,
                                $drug->drug_category?->name,
                                $drug->description,
                                $drug->stock,
                                $drug->price,
                                $drug->in_stock ? 'Ya' : 'Tidak',
                                $drug->kfa_code,
                                $drug->kfa_display,
                                $drug->kfa_system,
                                $drug->dosage_form,
                                $drug->dosage_form_code,
                            ], ';');
                        }
                        fclose($handle);
                    }, 'obat-' . Str::slug($clinic->name) . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
                }),
            Actions\Action::make('importDrugs')
                ->label('Impor dari Excel (CSV)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->form([
                    Select::make('clinic_id')
                        ->label('Klinik tujuan')
                        ->options(fn () => static::clinicOptions())
                        ->searchable()
                        ->required(),
                    FileUpload::make('file')
                        ->label('File CSV')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->storeFiles(false)
                        ->required()
                        ->helperText('Simpan file Excel sebagai CSV UTF-8 dengan pemisah titik koma. Kolom wajib: Nama dan Kategori. Kategori harus sudah terdaftar.'),
                ])
                ->action(function (array $data) {
                    abort_unless(static::canAccessClinic((int) $data['clinic_id']), 403);

                    $path = $data['file'];
                    if ($path instanceof TemporaryUploadedFile) {
                        $path = $path->getRealPath();
                    } else {
                        $path = \Illuminate\Support\Facades\Storage::disk('livewire-tmp')->path($path);
                    }

                    $handle = fopen($path, 'r');
                    if (!$handle) {
                        Notification::make()->title('File tidak dapat dibaca')->danger()->send();
                        return;
                    }

                    try {
                        $headers = fgetcsv($handle, 0, ';');
                        if (!$headers) {
                            throw new \RuntimeException('File CSV kosong atau formatnya tidak sesuai.');
                        }
                        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
                        $headers = array_map(fn ($header) => Str::lower(trim((string) $header)), $headers);
                        $requiredHeaders = ['nama', 'kategori'];
                        if (array_diff($requiredHeaders, $headers)) {
                            throw new \RuntimeException('Kolom Nama dan Kategori wajib tersedia. Gunakan template ekspor obat.');
                        }

                        $indices = array_flip($headers);
                        $line = 1;
                        $imported = 0;
                        DB::transaction(function () use ($handle, $indices, $data, &$line, &$imported) {
                            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                                $line++;
                                if (count($row) === 1 && trim((string) $row[0]) === '') continue;
                                $value = fn (string $key, mixed $default = null) => isset($indices[$key]) ? trim((string) ($row[$indices[$key]] ?? '')) : $default;
                                $name = $value('nama');
                                $categoryName = $value('kategori');
                                $category = DrugCategory::whereRaw('LOWER(name) = ?', [Str::lower($categoryName)])->first();
                                if ($name === '' || !$category) {
                                    throw new \RuntimeException("Baris {$line}: nama obat kosong atau kategori '{$categoryName}' belum terdaftar.");
                                }

                                $sku = $value('sku');
                                $stock = $value('stok', '0');
                                $price = $value('harga', '0');
                                if (!is_numeric($stock) || (int) $stock < 0 || !is_numeric($price) || (int) $price < 0) {
                                    throw new \RuntimeException("Baris {$line}: stok dan harga harus berupa angka nol atau lebih.");
                                }
                                $available = Str::lower($value('tersedia', 'ya'));
                                $attributes = [
                                    'drug_category_id' => $category->id,
                                    'name' => $name,
                                    'description' => $value('deskripsi') ?: null,
                                    'stock' => (int) $stock,
                                    'price' => (int) $price,
                                    'in_stock' => in_array($available, ['ya', 'yes', '1', 'true'], true),
                                    'kfa_code' => $value('kfa code') ?: null,
                                    'kfa_display' => $value('kfa name') ?: null,
                                    'kfa_system' => $value('kfa system', 'http://sys-ids.kemkes.go.id/kfa') ?: 'http://sys-ids.kemkes.go.id/kfa',
                                    'dosage_form' => $value('bentuk sediaan') ?: null,
                                    'dosage_form_code' => $value('kode bentuk sediaan') ?: null,
                                ];

                                if ($sku !== '') {
                                    Drug::updateOrCreate(
                                        ['clinic_id' => $data['clinic_id'], 'sku' => $sku],
                                        $attributes,
                                    );
                                } else {
                                    Drug::create($attributes + ['clinic_id' => $data['clinic_id']]);
                                }
                                $imported++;
                            }
                        });

                        Notification::make()->title("{$imported} obat berhasil diimpor atau diperbarui.")->success()->send();
                    } catch (\Throwable $exception) {
                        Notification::make()->title('Impor dibatalkan')->body($exception->getMessage())->danger()->persistent()->send();
                    } finally {
                        fclose($handle);
                    }
                }),
            Actions\CreateAction::make(),
        ];
    }

    private static function clinicOptions(): array
    {
        $user = auth()->user();
        $clinics = Clinic::query();
        if (!$user->hasRole(RoleEnum::SUPER_ADMIN->value) && !$user->hasRole(RoleEnum::ADMIN->value)) {
            $clinics->whereIn('id', $user->clinics()->select('clinics.id'));
        }

        return $clinics->orderBy('name')->pluck('name', 'id')->all();
    }

    private static function canAccessClinic(int $clinicId): bool
    {
        $user = auth()->user();
        return $user->hasRole(RoleEnum::SUPER_ADMIN->value)
            || $user->hasRole(RoleEnum::ADMIN->value)
            || $user->clinics()->where('clinics.id', $clinicId)->exists();
    }
}
