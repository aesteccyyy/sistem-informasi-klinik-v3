<div class="outpatient-emr-header">
    <div class="outpatient-emr-breadcrumb"><a href="{{ \App\Filament\Pages\UnifiedEmr::getUrl() }}">EMR</a><span>›</span><span>Rawat Jalan</span><span>›</span><strong>Detail Pasien</strong></div>
    <div class="outpatient-emr-title-row">
        <div><h1>Detail EMR Rawat Jalan</h1><p>Kelola pemeriksaan dan rencana perawatan pasien rawat jalan.</p></div>
        <a class="outpatient-emr-back" href="{{ $backUrl }}">← Kembali ke pasien</a>
    </div>
    <section class="outpatient-emr-summary">
        <div class="outpatient-emr-person">
            <span class="outpatient-emr-avatar">{{ $patient ? mb_substr($patient->name, 0, 1) : 'P' }}</span>
            <div><h2>{{ $patient?->name ?? 'Pasien' }} <span class="outpatient-emr-status">{{ $record->status?->label() ?? 'Rawat Jalan' }}</span></h2>
                <p>NIK: {{ $patient?->nik ?? '—' }} &nbsp;|&nbsp; {{ $patient?->gender?->label() ?? '—' }}{{ $patient?->age ? ', '.$patient->age.' tahun' : '' }}</p>
                <p>{{ $patient?->phone ?? 'Telepon —' }} &nbsp;·&nbsp; {{ $patient?->address ?? 'Alamat pasien —' }}</p>
            </div>
        </div>
        <div class="outpatient-emr-facts">
            <span>No. Kunjungan<b>{{ $record->visit_number ?? '—' }}</b></span>
            <span>Tanggal<b>{{ $record->schedule_date?->format('d-m-Y H:i') ?? '—' }}</b></span>
            <span>Klinik<b>{{ $clinic?->name ?? '—' }}</b></span>
            <span>Dokter<b>{{ $doctor?->name ?? '—' }}</b></span>
        </div>
    </section>
    <style>
        .fi-page:has(.outpatient-emr-header)>section{gap:1rem!important;padding-top:.75rem!important}
        .outpatient-emr-header{display:grid;gap:12px;color:#24374f}.outpatient-emr-breadcrumb{display:flex;align-items:center;gap:9px;color:#8492a6;font-size:12px}.outpatient-emr-breadcrumb a{color:#526985;text-decoration:none}.outpatient-emr-breadcrumb strong{color:#526985;font-weight:500}.outpatient-emr-title-row{display:flex;align-items:center;justify-content:space-between;gap:16px}.outpatient-emr-title-row h1{margin:0;color:#101828;font-size:24px;font-weight:720;letter-spacing:-.035em}.outpatient-emr-title-row p{margin:5px 0 0;color:#64748b;font-size:14px}.outpatient-emr-back{display:inline-flex;align-items:center;min-height:40px;padding:0 14px;border-radius:8px;background:#2563eb;color:white;text-decoration:none;font-size:13px;font-weight:650;box-shadow:0 2px 5px #2563eb2b}.outpatient-emr-back:hover{background:#1d4ed8;color:white}.outpatient-emr-summary{display:grid;grid-template-columns:minmax(280px,1.15fr) minmax(400px,2fr);align-items:center;gap:18px;padding:16px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 3px 14px #0f172a08}.outpatient-emr-person{display:flex;align-items:center;gap:14px;min-width:0}.outpatient-emr-avatar{flex:none;width:54px;height:54px;display:grid;place-items:center;border-radius:50%;background:#dbeafe;color:#2563eb;font-weight:700;font-size:19px}.outpatient-emr-person h2{margin:0;color:#172b45;font-size:16px;font-weight:700}.outpatient-emr-person p{margin:4px 0 0;color:#718096;font-size:11px;line-height:1.45}.outpatient-emr-status{display:inline-flex;margin-left:5px;padding:4px 8px;border-radius:6px;background:#dcfce7;color:#15803d;font-size:10px;font-weight:700;vertical-align:middle}.outpatient-emr-facts{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));align-items:center}.outpatient-emr-facts span{padding:5px 10px;border-left:1px solid #e6edf4;color:#8492a6;font-size:10px}.outpatient-emr-facts b{display:block;margin-top:5px;color:#263b57;font-size:11px;line-height:1.4;overflow-wrap:anywhere}
        .fi-resource-emr-visits .fi-section{border-radius:12px!important;box-shadow:0 3px 14px #0f172a08!important}.fi-resource-emr-visits .fi-section-header{padding:14px 18px!important;background:#eaf3ff!important}.fi-resource-emr-visits .fi-section-content{padding:18px!important}.fi-resource-emr-visits .fi-fo-field-wrp-label{font-size:.82rem!important}.fi-resource-emr-visits .fi-fo-repeater-item{border:1px solid #e2e8f0!important;border-radius:10px!important;box-shadow:none!important}.fi-resource-emr-visits .fi-fo-repeater-item-header{background:#f8fafc!important;border-radius:10px 10px 0 0!important}
        @media(max-width:1000px){.outpatient-emr-summary{grid-template-columns:1fr}.outpatient-emr-facts span:first-child{border-left:0}}@media(max-width:700px){.outpatient-emr-title-row{align-items:flex-start;flex-direction:column}.outpatient-emr-title-row h1{font-size:21px}.outpatient-emr-back{width:100%;justify-content:center}.outpatient-emr-facts{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.outpatient-emr-facts span{border-left:0;border-top:1px solid #e6edf4;padding:9px 2px 0}.fi-resource-emr-visits .fi-section-content{padding:12px!important}}
    </style>
</div>
