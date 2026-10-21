<x-filament-panels::page>
    <div class="clinic-emr-layout">
        <x-clinic-global-navigation />
        <div class="clinic-emr-content">
            <div class="new-registration-page">
                <header class="new-registration-heading">
                    <div><h1>Registrasi Baru</h1><p>Daftarkan pasien rawat jalan, rawat inap, atau IGD dari satu formulir.</p></div>
                    <a href="{{ \App\Filament\Pages\PatientRegistrations::getUrl() }}" class="registration-back">Kembali ke Registrasi</a>
                </header>

                <form wire:submit="register" class="new-registration-form">
                    <section class="registration-card">
                        <header><h2>Jenis Pelayanan</h2><p>Pilih jenis kunjungan yang akan didaftarkan.</p></header>
                        <div class="registration-fields two-columns">
                            <label class="registration-field"><span>Klinik</span><select wire:model.live="clinicId" required>@foreach($clinics as $clinic)<option value="{{ $clinic->id }}">{{ $clinic->name }}</option>@endforeach</select>@error('clinicId')<small>{{ $message }}</small>@enderror</label>
                            <label class="registration-field"><span>Jenis pelayanan</span><select wire:model.live="serviceType" required>@foreach($availableServices as $service)<option value="{{ $service }}">{{ ['outpatient'=>'Rawat Jalan · Outpatient','inpatient'=>'Rawat Inap · Inpatient','emergency'=>'IGD · Emergency'][$service] }}</option>@endforeach</select>@error('serviceType')<small>{{ $message }}</small>@enderror</label>
                        </div>
                    </section>

                    <section class="registration-card">
                        <header><h2>Data Pasien</h2><p>Pasien harus sudah terdaftar melalui menu Patients.</p></header>
                        @if($selectedPatient)
                            <div class="registration-selected-patient"><div><b>{{ $selectedPatient->name }}</b><span>RM {{ $selectedPatient->medical_record_number ?? '—' }} · NIK {{ $selectedPatient->nik ?? '—' }}</span></div><button type="button" wire:click="clearPatient">Ganti pasien</button></div>
                        @else
                            <label class="registration-field"><span>Cari pasien (Nama / NIK / No. RM)</span><input wire:model.live.debounce.300ms="patientSearch" autocomplete="off" placeholder="Ketik minimal 2 karakter"></label>
                            @error('patientId')<small class="registration-validation">{{ $message }}</small>@enderror
                            @if($patients->isNotEmpty())<div class="registration-patient-results">@foreach($patients as $patient)<button type="button" wire:click="choosePatient({{ $patient->id }})"><b>{{ $patient->name }}</b><small>RM {{ $patient->medical_record_number ?? '—' }} · NIK {{ $patient->nik ?? '—' }}</small></button>@endforeach</div>@endif
                        @endif
                    </section>

                    @if($serviceType === 'outpatient')
                        <section class="registration-card"><header><h2>Data Rawat Jalan</h2></header><div class="registration-fields two-columns">
                            <label class="registration-field"><span>Dokter</span><select wire:model="registration.clinic_user_id"><option value="">Pilih dokter</option>@foreach($doctors as $doctor)<option value="{{ $doctor->id }}">{{ $doctor->user->name }}</option>@endforeach</select>@error('registration.clinic_user_id')<small>{{ $message }}</small>@enderror</label>
                            <label class="registration-field"><span>Tanggal kunjungan</span><input type="date" wire:model="registration.schedule_date">@error('registration.schedule_date')<small>{{ $message }}</small>@enderror</label>
                        </div></section>
                    @elseif($serviceType === 'inpatient')
                        <section class="registration-card"><header><h2>Data Admisi Rawat Inap</h2></header><div class="registration-fields two-columns">
                            <label class="registration-field"><span>Tanggal & jam masuk</span><input type="datetime-local" wire:model="registration.arrived_at">@error('registration.arrived_at')<small>{{ $message }}</small>@enderror</label>
                            <label class="registration-field"><span>Dokter penanggung jawab</span><select wire:model="registration.clinic_user_id"><option value="">Pilih dokter (opsional)</option>@foreach($doctors as $doctor)<option value="{{ $doctor->id }}">{{ $doctor->user->name }}</option>@endforeach</select>@error('registration.clinic_user_id')<small>{{ $message }}</small>@enderror</label>
                            <label class="registration-field"><span>Asal pasien</span><select wire:model="registration.admission_source"><option>Poliklinik</option><option>Datang langsung</option><option>Rujukan</option></select></label>
                            <label class="registration-field"><span>Cara masuk</span><select wire:model="registration.admission_method"><option>Jalan sendiri</option><option>Kursi roda</option><option>Brankar</option><option>Ambulans</option></select></label>
                            <label class="registration-field"><span>Penjamin</span><input wire:model="registration.guarantor" placeholder="Umum"></label>
                            <label class="registration-field"><span>Diagnosis awal</span><input wire:model="registration.initial_diagnosis">@error('registration.initial_diagnosis')<small>{{ $message }}</small>@enderror</label>
                            <label class="registration-field"><span>Ruang perawatan</span><select wire:model.live="registration.care_room_id"><option value="">Pilih ruang</option>@foreach($rooms as $room)<option value="{{ $room->id }}">{{ $room->name }} · {{ $room->care_class ?? 'Kelas —' }}</option>@endforeach</select>@error('registration.care_room_id')<small>{{ $message }}</small>@enderror</label>
                            <label class="registration-field"><span>Bed tersedia</span><select wire:model="registration.care_bed_id"><option value="">Pilih bed</option>@foreach($beds as $bed)<option value="{{ $bed->id }}">{{ $bed->code }}</option>@endforeach</select>@error('registration.care_bed_id')<small>{{ $message }}</small>@enderror</label>
                            <label class="registration-field full-width"><span>Alasan rawat inap</span><textarea rows="3" wire:model="registration.admission_reason"></textarea>@error('registration.admission_reason')<small>{{ $message }}</small>@enderror</label>
                        </div></section>
                    @else
                        <section class="registration-card"><header><h2>Data Registrasi IGD</h2></header><div class="registration-fields two-columns">
                            <label class="registration-field"><span>Tanggal & jam datang</span><input type="datetime-local" wire:model="registration.arrived_at">@error('registration.arrived_at')<small>{{ $message }}</small>@enderror</label>
                            <label class="registration-field"><span>Dokter penanggung jawab</span><select wire:model="registration.clinic_user_id"><option value="">Pilih dokter (opsional)</option>@foreach($doctors as $doctor)<option value="{{ $doctor->id }}">{{ $doctor->user->name }}</option>@endforeach</select>@error('registration.clinic_user_id')<small>{{ $message }}</small>@enderror</label>
                            <label class="registration-field"><span>Triase</span><select wire:model="registration.triage_level"><option value="">Pilih triase</option><option value="Resusitasi">Merah · Resusitasi</option><option value="Emergensi">Kuning · Emergensi</option><option value="Urgensi">Hijau · Urgensi</option><option value="Non-urgent">Biru · Non-urgent</option><option value="Meninggal">Hitam · Meninggal</option></select>@error('registration.triage_level')<small>{{ $message }}</small>@enderror</label>
                            <label class="registration-field"><span>Penjamin</span><input wire:model="registration.guarantor" placeholder="Umum"></label>
                            <label class="registration-field full-width"><span>Asal pasien</span><select wire:model="registration.admission_source"><option>Datang langsung</option><option>Rujukan</option><option>Poliklinik</option><option>Ambulans</option></select></label>
                            <label class="registration-field full-width"><span>Keluhan / alasan datang</span><textarea rows="3" wire:model="registration.admission_reason"></textarea>@error('registration.admission_reason')<small>{{ $message }}</small>@enderror</label>
                        </div></section>
                    @endif

                    <div class="registration-submit-row"><button type="submit" wire:loading.attr="disabled">Simpan Registrasi</button></div>
                </form>

                @if($registeredAppointment)
                    <section class="registration-success"><b>Registrasi rawat jalan berhasil</b><span>{{ $registeredAppointment->patient?->name }} · Nomor antrean <strong>{{ $registeredAppointment->queue_code }}</strong> · {{ $registeredAppointment->schedule_date?->format('d/m/Y') }}</span></section>
                @endif
            </div>
        </div>
    </div>
    <style>
        .new-registration-page{display:grid;gap:16px;color:#1e293b}.new-registration-heading,.registration-card{border:1px solid #e2e8f0;border-radius:12px;background:#fff;box-shadow:0 3px 14px #0f172a08}.new-registration-heading{display:flex;justify-content:space-between;align-items:center;gap:14px;padding:20px 22px}.new-registration-heading h1{margin:0;color:#172b45;font-size:22px;font-weight:750}.new-registration-heading p{margin:5px 0 0;color:#71839b;font-size:13px}.registration-back{border-radius:8px;background:#eff6ff;padding:10px 13px;color:#1d4ed8;font-size:12px;font-weight:700;text-decoration:none;white-space:nowrap}.new-registration-form{display:grid;gap:14px}.registration-card{padding:18px 20px}.registration-card>header{margin-bottom:16px}.registration-card h2{margin:0;color:#203752;font-size:16px;font-weight:730}.registration-card header p{margin:5px 0 0;color:#71839b;font-size:12px}.registration-fields{display:grid;gap:14px}.registration-fields.two-columns{grid-template-columns:repeat(2,minmax(0,1fr))}.registration-field{display:grid;align-content:start;gap:6px;min-width:0;color:#52657f;font-size:12px;font-weight:650}.registration-field input,.registration-field select,.registration-field textarea{width:100%;min-height:42px;border:1px solid #d9e2ee;border-radius:8px;background:#fbfdff;padding:9px 11px;color:#293e58;font:inherit;font-size:13px}.registration-field textarea{resize:vertical}.registration-field input:focus,.registration-field select:focus,.registration-field textarea:focus{outline:0;border-color:#5592f2;box-shadow:0 0 0 3px #3b82f61c}.registration-field small,.registration-validation{color:#b91c1c;font-size:11px;font-weight:500}.full-width{grid-column:1/-1}.registration-patient-results{display:grid;gap:6px;margin-top:9px}.registration-patient-results button{display:grid;gap:4px;border:1px solid #e1e9f2;border-radius:8px;background:#fbfdff;padding:10px 12px;text-align:left;color:#263b56}.registration-patient-results button:hover{border-color:#91b8f3;background:#f3f8ff}.registration-patient-results small{color:#8191a6;font-size:11px}.registration-selected-patient{display:flex;align-items:center;justify-content:space-between;gap:12px;border:1px solid #cce8d8;border-radius:9px;background:#f2fbf6;padding:12px 14px}.registration-selected-patient div{display:grid;gap:4px}.registration-selected-patient span{color:#66806f;font-size:12px}.registration-selected-patient button{border:0;background:transparent;color:#2563eb;font-size:12px;font-weight:700}.registration-submit-row{display:flex;justify-content:flex-end}.registration-submit-row button{border:0;border-radius:8px;background:#2563eb;padding:11px 18px;color:#fff;font-size:13px;font-weight:750}.registration-submit-row button:disabled{opacity:.6}.registration-success{display:grid;gap:6px;border:1px solid #bce5ca;border-radius:10px;background:#f0fdf4;padding:14px 17px;color:#166534;font-size:13px}.registration-success span{color:#41634b;font-size:12px}
        @media(max-width:680px){.new-registration-heading{align-items:flex-start;flex-direction:column}.registration-fields.two-columns{grid-template-columns:1fr}.registration-card{padding:15px}.registration-submit-row button{width:100%}}
    </style>
</x-filament-panels::page>
