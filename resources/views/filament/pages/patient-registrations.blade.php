<x-filament-panels::page>
    <div class="clinic-emr-layout">
        <x-clinic-global-navigation />
        <div class="clinic-emr-content">
<div class="patient-registration-page">
    <header class="registration-heading">
        <div>
            <h1>Registrasi Pasien</h1>
            <p>Daftarkan pasien dari data Patients yang sudah tersedia dan pantau kunjungan harian.</p>
        </div>
        @if ($this->newRegistrationUrl())
            <a class="registration-action outpatient" href="{{ $this->newRegistrationUrl() }}">＋ Registrasi Baru</a>
        @endif
    </header>

    <section class="registration-filters">
        <label>
            <span>Klinik</span>
            <select wire:model.live="clinicId">
                @foreach ($clinics as $clinic)
                    <option value="{{ $clinic->id }}">{{ $clinic->name }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span>Jenis pelayanan</span>
            <select wire:model.live="serviceType">
                <option value="all">Semua pelayanan</option>
                <option value="outpatient">Rawat jalan</option>
                <option value="inpatient">Rawat inap</option>
                <option value="emergency">IGD</option>
            </select>
        </label>
        <label class="registration-search">
            <span>Cari pasien / nomor RM</span>
            <input type="search" wire:model.live.debounce.350ms="search" placeholder="Nama, NIK, atau nomor RM">
        </label>
        <label>
            <span>Tanggal registrasi rawat jalan</span>
            <input type="date" wire:model.live="registrationDate" required>
        </label>
    </section>

    <section class="registration-list">
        <div class="registration-list-heading">
            <div>
                <h2>Status Registrasi</h2>
                <p>{{ $registrations->count() }} data ditampilkan · Rawat jalan dan IGD mengikuti tanggal registrasi {{ \Illuminate\Support\Carbon::parse($registrationDate)->format('d/m/Y') }} (kasus IGD aktif tetap tampil selama penanganan) · Pasien rawat inap aktif tetap ditampilkan sampai pasien keluar.</p>
            </div>
        </div>
        <div class="registration-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No. RM</th>
                        <th>Pasien</th>
                        <th>Jenis</th>
                        <th>Tanggal Masuk / Kunjungan</th>
                        <th>Klinik</th>
                        <th>Dokter</th>
                        <th>Ruang / Layanan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registrations as $registration)
                        <tr>
                            <td class="registration-mrn">{{ $registration['medical_record_number'] }}</td>
                            <td><strong>{{ $registration['patient'] }}</strong><small>NIK: {{ $registration['nik'] }}</small></td>
                            <td><span class="registration-service {{ $registration['service_key'] }}">{{ $registration['service'] }}</span></td>
                            <td>{{ $registration['date']?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>{{ $registration['clinic'] }}</td>
                            <td>{{ $registration['doctor'] }}</td>
                            <td>{{ $registration['location'] }}</td>
                            <td>
                                <span class="registration-status {{ $registration['status_color'] }}">{{ $registration['status'] }}</span>
                                @if ($registration['void_reason'])<small class="registration-void-reason">Alasan: {{ $registration['void_reason'] }}</small>@endif
                            </td>
                            <td class="registration-actions-cell">
                                @if ($registration['can_open'])
                                    <a class="registration-open" href="{{ $registration['url'] }}">Buka EMR →</a>
                                @endif
                                @if ($canVoidRegistrations && $registration['can_void'])
                                    <button class="registration-void" type="button" wire:click="openVoidModal('{{ $registration['void_type'] }}', {{ $registration['id'] }})">Void</button>
                                @elseif (!$registration['can_open'] && !$registration['can_void'])
                                    <span class="registration-no-access">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="registration-empty">Belum ada registrasi untuk filter yang dipilih.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($showVoidModal)
        <div class="registration-modal-backdrop" role="presentation" wire:click.self="cancelVoid">
            <section class="registration-modal" role="dialog" aria-modal="true" aria-labelledby="void-modal-title">
                <h2 id="void-modal-title">Batalkan registrasi?</h2>
                <p>Registrasi akan ditandai <strong>Batal berobat</strong>. Alasan pembatalan wajib dicatat dan dapat dilihat kembali.</p>
                <form wire:submit="voidRegistration">
                    <label for="void-reason">Alasan pembatalan</label>
                    <textarea id="void-reason" wire:model="voidReason" rows="4" maxlength="2000" required placeholder="Tuliskan alasan pasien membatalkan berobat"></textarea>
                    @error('voidReason')<small class="registration-void-error">{{ $message }}</small>@enderror
                    <div class="registration-modal-actions">
                        <button type="button" class="registration-cancel" wire:click="cancelVoid">Kembali</button>
                        <button type="submit" class="registration-confirm-void" wire:loading.attr="disabled">Simpan pembatalan</button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    <style>
        .patient-registration-page{display:grid;gap:16px;color:#1e293b}
        .registration-heading,.registration-filters,.registration-list{background:#fff;border:1px solid #e4eaf1;border-radius:12px;box-shadow:0 3px 14px #0f172a08}
        .registration-heading{padding:20px 22px;display:flex;align-items:center;justify-content:space-between;gap:16px}.registration-heading h1{margin:0;color:#172b45;font-size:22px;font-weight:750;letter-spacing:-.025em}.registration-heading p{margin:6px 0 0;color:#6b7e96;font-size:13px}.registration-actions{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:8px}.registration-action{display:inline-flex;align-items:center;justify-content:center;border-radius:8px;padding:10px 12px;color:#fff;text-decoration:none;font-size:12px;font-weight:700;white-space:nowrap;box-shadow:0 2px 5px #0f172a12}.registration-action:hover{color:#fff;filter:brightness(.96)}.registration-action.outpatient{background:#2563eb}.registration-action.inpatient{background:#059669}.registration-action.emergency{background:#e35b45}
        .registration-filters{display:grid;grid-template-columns:minmax(170px,.9fr) minmax(210px,1fr) minmax(260px,1.7fr) minmax(205px,.9fr);gap:12px;padding:16px}
        .registration-filters label{display:grid;align-content:start;gap:6px;color:#52657f;font-size:12px;font-weight:650;min-width:0}.registration-filters input,.registration-filters select{width:100%;height:42px;border:1px solid #d9e2ee;border-radius:8px;background:#fbfdff;padding:0 11px;color:#293e58;font:inherit;font-size:13px}.registration-filters input:focus,.registration-filters select:focus{outline:0;border-color:#5592f2;box-shadow:0 0 0 3px #3b82f61c}
        .registration-list{overflow:hidden}.registration-list-heading{padding:17px 18px;border-bottom:1px solid #edf1f5}.registration-list-heading h2{margin:0;color:#203752;font-size:15px;font-weight:730}.registration-list-heading p{margin:5px 0 0;color:#72839a;font-size:12px;line-height:1.5}.registration-table-wrap{overflow:auto}.registration-table-wrap table{width:100%;min-width:1100px;border-collapse:collapse;font-size:12px}.registration-table-wrap th{background:#f7f9fc;color:#667892;text-align:left;font-size:10px;letter-spacing:.045em;text-transform:uppercase;white-space:nowrap}.registration-table-wrap th,.registration-table-wrap td{padding:12px 11px;border-bottom:1px solid #edf1f5;vertical-align:middle}.registration-table-wrap tbody tr:hover{background:#f9fbff}.registration-table-wrap td strong,.registration-table-wrap td small{display:block}.registration-table-wrap td small{margin-top:4px;color:#8a98ab;font-size:10px}.registration-mrn{font-variant-numeric:tabular-nums;font-weight:700;color:#334b68;white-space:nowrap}.registration-service,.registration-status{display:inline-flex;border-radius:999px;padding:5px 8px;font-size:10px;font-weight:700;white-space:nowrap}.registration-service.outpatient{background:#eff6ff;color:#1d4ed8}.registration-service.inpatient{background:#ecfdf5;color:#047857}.registration-status.success{background:#dcfce7;color:#15803d}.registration-status.gray{background:#f1f5f9;color:#64748b}.registration-open{display:inline-flex;align-items:center;border-radius:7px;background:#2563eb;padding:8px 10px;color:#fff;text-decoration:none;font-size:11px;font-weight:700;white-space:nowrap}.registration-open:hover{background:#1d4ed8;color:#fff}.registration-no-access{color:#a1adbc}.registration-empty{text-align:center;color:#8090a4;padding:36px!important}
        .registration-status.primary,.registration-status.info{background:#eff6ff;color:#1d4ed8}.registration-status.warning{background:#fff7ed;color:#c2410c}.registration-status.danger{background:#fef2f2;color:#b91c1c}
        .registration-service.emergency{background:#fff1ed;color:#c2412c}.registration-actions-cell{min-width:150px;white-space:nowrap}
        .registration-void{display:inline-flex;align-items:center;margin-left:5px;border:1px solid #fecaca;border-radius:7px;background:#fff;color:#b91c1c;padding:7px 9px;font-size:11px;font-weight:700;white-space:nowrap}.registration-void:hover{background:#fef2f2}.registration-void-reason{display:block;margin-top:5px;color:#9f3a38;font-size:10px;line-height:1.4;max-width:220px;white-space:normal}.registration-modal-backdrop{position:fixed;inset:0;z-index:1000;display:grid;place-items:center;background:#0f172a80;padding:16px}.registration-modal{width:min(480px,100%);border-radius:14px;background:#fff;padding:22px;box-shadow:0 20px 60px #0f172a40;color:#24364b}.registration-modal h2{margin:0;font-size:18px;font-weight:750}.registration-modal>p{margin:8px 0 18px;color:#687b92;font-size:13px;line-height:1.5}.registration-modal form{display:grid;gap:8px}.registration-modal label{font-size:12px;font-weight:700;color:#41556f}.registration-modal textarea{width:100%;border:1px solid #d5deea;border-radius:8px;background:#fbfdff;padding:10px;color:#263b56;font:inherit;font-size:13px;resize:vertical}.registration-modal textarea:focus{outline:0;border-color:#5592f2;box-shadow:0 0 0 3px #3b82f61c}.registration-void-error{color:#b91c1c;font-size:11px}.registration-modal-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:9px}.registration-modal-actions button{border:0;border-radius:8px;padding:9px 12px;font-size:12px;font-weight:700}.registration-cancel{background:#f1f5f9;color:#475569}.registration-confirm-void{background:#c24135;color:#fff}.registration-confirm-void:hover{background:#a9322a}
        @media(max-width:1050px){.registration-filters{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:760px){.registration-heading{align-items:flex-start;flex-direction:column}.registration-actions{justify-content:flex-start}}@media(max-width:600px){.registration-filters{grid-template-columns:1fr}.registration-heading{padding:16px}.registration-heading h1{font-size:19px}}
    </style>
</div>
        </div>
    </div>
</x-filament-panels::page>
