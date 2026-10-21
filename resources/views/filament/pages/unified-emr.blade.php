<x-filament-panels::page>
    <div class="clinic-emr-layout">
        <x-clinic-global-navigation />
        <div class="clinic-emr-content">
    <div class="unified-emr">
        <header class="emr-heading">
            <div>
                <h1>Rekam Medis Elektronik</h1>
                <p>Daftar kunjungan rawat jalan. Registrasi rawat inap dan IGD dikelola melalui menu Registrasi dan halaman layanan masing-masing.</p>
            </div>
        </header>

        <section class="emr-filters">
            <label><span>Klinik</span>
                <select wire:model.live="clinicId">
                    <option value="">Semua klinik yang dapat diakses</option>
                    @foreach($clinics as $clinic)<option value="{{ $clinic->id }}">{{ $clinic->name }}</option>@endforeach
                </select>
            </label>
            <label><span>Jenis pelayanan</span>
                <select wire:model.live="visitType">
                    <option value="all">Semua kunjungan</option>
                    <option value="outpatient">Rawat Jalan · Outpatient</option>
                </select>
            </label>
            <label class="emr-search"><span>Cari pasien / nomor RM</span><input wire:model.live.debounce.350ms="search" placeholder="Nama, NIK, atau nomor RM"></label>
            <label><span>Dari tanggal</span><input type="date" wire:model.live="fromDate"></label>
            <label><span>Sampai tanggal</span><input type="date" wire:model.live="toDate"></label>
        </section>

        <section class="emr-results">
            <div class="emr-results-head"><div><h2>Kunjungan Pasien</h2><p>{{ $visits->count() }} kunjungan ditampilkan (maksimal 150 per jenis layanan).</p></div></div>
            <div class="emr-table-wrap">
                <table>
                    <thead><tr><th>No. RM</th><th>Pasien</th><th>Jenis</th><th>Tanggal</th><th>Klinik</th><th>Dokter</th><th>Ruang / Layanan</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse($visits as $visit)
                            <tr>
                                <td class="visit-number">{{ $visit['number'] }}</td>
                                <td><strong>{{ $visit['patient'] }}</strong><small>NIK: {{ $visit['nik'] }}</small></td>
                                <td><span class="kind-badge {{ $visit['kind'] }}">{{ $visit['kind_label'] }}</span></td>
                                <td>{{ $visit['date']?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td>{{ $visit['clinic'] }}</td>
                                <td>{{ $visit['doctor'] }}</td>
                                <td>{{ $visit['location'] }}</td>
                                <td><span class="status-badge {{ $visit['status_color'] }}">{{ $visit['status'] }}</span></td>
                                <td>@if($visit['can_open'])<a class="emr-open" href="{{ $visit['url'] }}">Buka EMR <span>→</span></a>@else<span class="emr-no-access">Tidak ada akses</span>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="9"><div class="emr-empty"><span>⌕</span><strong>Tidak ada kunjungan ditemukan</strong><small>Ubah filter atau kata pencarian untuk melihat kunjungan lainnya.</small></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <style>
        .fi-page:has(.unified-emr)>section{padding-top:.5rem!important;gap:0!important}
        .unified-emr{--emr-line:#e2e8f0;--emr-ink:#172b45;--emr-muted:#64748b;color:var(--emr-ink);display:grid;gap:1rem}
        .emr-heading,.emr-results{background:#fff;border:1px solid var(--emr-line);border-radius:14px;box-shadow:0 4px 16px #0f172a08}
        .emr-heading{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.2rem 1.4rem}
        .emr-heading h1{margin:0;font-size:1.35rem;font-weight:700;letter-spacing:-.025em}.emr-heading p,.emr-results-head p{margin:.3rem 0 0;color:var(--emr-muted);font-size:.85rem}
        .emr-actions{display:flex;gap:.6rem;flex-wrap:wrap}.emr-button,.emr-open{display:inline-flex;align-items:center;justify-content:center;gap:.35rem;border-radius:8px;background:#2563eb;color:white;padding:.65rem .85rem;font-weight:650;font-size:.83rem;text-decoration:none}.emr-button.secondary{background:#eff6ff;color:#1d4ed8;border:1px solid #dbeafe}
        .emr-filters{display:grid;grid-template-columns:minmax(150px,1fr) minmax(190px,1.2fr) minmax(250px,2fr) minmax(140px,.8fr) minmax(140px,.8fr);gap:.8rem;background:#fff;border:1px solid var(--emr-line);border-radius:14px;padding:1rem;box-shadow:0 4px 16px #0f172a08}
        .emr-filters label{display:grid;gap:.4rem;min-width:0}.emr-filters label>span{font-weight:600;color:#475569;font-size:.76rem}.emr-filters input,.emr-filters select{width:100%;min-height:2.65rem;border:1px solid #d6e0eb;border-radius:8px;background:#fbfdff;color:#334155;padding:.55rem .7rem;font-size:.84rem}.emr-filters input:focus,.emr-filters select:focus{outline:0;border-color:#7daaf4;box-shadow:0 0 0 3px #2563eb18}
        .emr-results{overflow:hidden}.emr-results-head{padding:1rem 1.2rem;border-bottom:1px solid var(--emr-line)}.emr-results-head h2{margin:0;font-size:1rem;font-weight:700}
        .emr-table-wrap{overflow:auto} .emr-table-wrap table{border-collapse:collapse;width:100%;min-width:1050px;font-size:.8rem}.emr-table-wrap th{text-align:left;background:#f8fafc;color:#64748b;font-size:.7rem;letter-spacing:.035em;text-transform:uppercase;font-weight:700;white-space:nowrap}.emr-table-wrap th,.emr-table-wrap td{padding:.8rem .9rem;border-bottom:1px solid #edf1f5;vertical-align:middle}.emr-table-wrap tbody tr:hover{background:#f8fbff}.emr-table-wrap td small{display:block;color:#8491a3;margin-top:.25rem}.visit-number{font-variant-numeric:tabular-nums;color:#334155;white-space:nowrap}.kind-badge,.status-badge{display:inline-flex;white-space:nowrap;border-radius:999px;padding:.3rem .55rem;font-size:.7rem;font-weight:650}.kind-badge.outpatient{background:#eff6ff;color:#1d4ed8}.kind-badge.inpatient{background:#ecfdf5;color:#047857}.kind-badge.emergency{background:#fff7ed;color:#c2410c}.status-badge.success{background:#dcfce7;color:#15803d}.status-badge.gray{background:#f1f5f9;color:#64748b}.emr-open{padding:.45rem .6rem;font-size:.75rem;white-space:nowrap}.emr-no-access{font-size:.72rem;color:#94a3b8;white-space:nowrap}.emr-empty{display:grid;justify-items:center;gap:.35rem;padding:3.5rem 1rem;color:#475569}.emr-empty>span{font-size:1.7rem;color:#94a3b8}.emr-empty small{color:#94a3b8}
        @media(max-width:1000px){.emr-filters{grid-template-columns:repeat(2,minmax(0,1fr))}.emr-filters .emr-search{grid-column:1/-1}}@media(max-width:620px){.emr-heading{align-items:flex-start;flex-direction:column}.emr-filters{grid-template-columns:1fr}.emr-filters .emr-search{grid-column:auto}}
    </style>
        </div>
    </div>
</x-filament-panels::page>
