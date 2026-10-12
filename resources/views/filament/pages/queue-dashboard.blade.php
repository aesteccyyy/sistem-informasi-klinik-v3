<div wire:poll.10s class="public-queue-screen" x-data
     x-on:queue-called.window="if (window.speechSynthesis) { window.speechSynthesis.cancel(); let announcement = new SpeechSynthesisUtterance('Nomor antrean, ' + $event.detail.code + '. Silakan menuju ruang pemeriksaan.'); announcement.lang = 'id-ID'; announcement.rate = 0.9; announcement.pitch = 1.05; announcement.volume = 1; let voices = window.speechSynthesis.getVoices(); let indonesianVoices = voices.filter(voice => voice.lang && voice.lang.toLowerCase().startsWith('id')); announcement.voice = indonesianVoices.find(voice => /natural|premium|google/i.test(voice.name)) || indonesianVoices.find(voice => voice.localService) || indonesianVoices[0] || null; window.speechSynthesis.speak(announcement); }">
    <style>
        .public-queue-screen { min-height:72vh; color:#12233f; }
        .screen-toolbar { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:20px; }
        .screen-brand { font-size:19px; font-weight:800; color:#17395f; }
        .screen-controls { display:flex; align-items:center; gap:10px; }
        .screen-controls select,.screen-controls input { height:40px; padding:0 11px; border:1px solid #d5dfeb; border-radius:9px; background:#fff; }
        .screen-grid { display:grid; grid-template-columns:minmax(0,1.25fr) minmax(280px,.75fr); gap:18px; }
        .screen-current,.screen-waiting { border:1px solid #deE6f0; border-radius:20px; overflow:hidden; background:white; box-shadow:0 10px 28px rgba(25,50,85,.07); }
        .screen-current { min-height:470px; display:flex; align-items:center; justify-content:center; text-align:center; padding:30px; background:linear-gradient(135deg,#eef8ff 0%,#eefaf7 100%); }
        .screen-current small { font-size:16px; color:#657894; text-transform:uppercase; letter-spacing:.12em; font-weight:700; }
        .screen-current h1 { margin:15px 0; color:#1260bc; font-size:clamp(70px,12vw,150px); line-height:1; font-weight:900; letter-spacing:.03em; }
        .screen-current p { margin:0; color:#53657e; font-size:20px; }
        .screen-waiting h2 { margin:0; padding:20px 22px; border-bottom:1px solid #e8edf4; color:#183a60; font-size:21px; }
        .screen-waiting ol { margin:0; padding:5px 22px; list-style:none; }
        .screen-waiting li { display:flex; align-items:center; justify-content:space-between; padding:14px 0; border-bottom:1px solid #eff2f7; }
        .screen-waiting li:last-child { border-bottom:0; }
        .screen-waiting b { color:#245384; font-size:24px; }
        .screen-waiting span { color:#71819a; font-size:14px; }
        .screen-empty { color:#71819a; padding:26px 22px; text-align:center; font-size:17px; }
        @media(max-width:850px) { .screen-grid { grid-template-columns:1fr; } .screen-current { min-height:330px; } .screen-toolbar { align-items:flex-start; flex-direction:column; } .screen-controls { width:100%; flex-wrap:wrap; } }
    </style>

    @php
        $clinics = $this->clinics();
        $current = $this->currentAppointment();
        $waiting = $this->waitingAppointments();
    @endphp

    <div class="screen-toolbar">
        <div class="screen-brand">SIMKLINIK · Papan Panggilan Antrian</div>
        <div class="screen-controls">
            <select aria-label="Pilih klinik" wire:model.live="clinicId">
                @foreach ($clinics as $clinic)
                    <option value="{{ $clinic->id }}">{{ $clinic->name }}</option>
                @endforeach
            </select>
            <input aria-label="Pilih tanggal" type="date" wire:model.live="queueDate">
        </div>
    </div>

    @if ($clinics->isEmpty())
        <div class="screen-current"><p>Akun ini belum ditautkan ke klinik.</p></div>
    @else
        <div class="screen-grid">
            <section class="screen-current" aria-live="polite">
                @if ($current)
                    <div>
                        <small>Nomor antrian dipanggil</small>
                        <h1>{{ $current->queue_code }}</h1>
                        <p>Silakan menuju ruang pemeriksaan</p>
                    </div>
                @else
                    <p>Belum ada nomor antrian yang dipanggil</p>
                @endif
            </section>

            <section class="screen-waiting">
                <h2>Antrian Berikutnya</h2>
                @if ($waiting->isEmpty())
                    <div class="screen-empty">Tidak ada antrian menunggu</div>
                @else
                    <ol>
                        @foreach ($waiting as $appointment)
                            <li><b>{{ $appointment->queue_code }}</b><span>{{ $appointment->clinic_user?->user?->name ?? 'Dokter' }}</span></li>
                        @endforeach
                    </ol>
                @endif
            </section>
        </div>
    @endif
</div>
