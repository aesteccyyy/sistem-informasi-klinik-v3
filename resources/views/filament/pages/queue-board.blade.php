<div wire:poll.10s x-data
     x-on:queue-called.window="if (window.speechSynthesis) { window.speechSynthesis.cancel(); let announcement = new SpeechSynthesisUtterance('Nomor antrean, ' + $event.detail.code + '. Silakan menuju ruang pemeriksaan.'); announcement.lang = 'id-ID'; announcement.rate = 0.9; announcement.pitch = 1.05; announcement.volume = 1; let voices = window.speechSynthesis.getVoices(); let indonesianVoices = voices.filter(voice => voice.lang && voice.lang.toLowerCase().startsWith('id')); announcement.voice = indonesianVoices.find(voice => /natural|premium|google/i.test(voice.name)) || indonesianVoices.find(voice => voice.localService) || indonesianVoices[0] || null; window.speechSynthesis.speak(announcement); }"
     class="queue-board">
    <style>
        .queue-board { color: #172033; }
        .queue-controls { display:flex; flex-wrap:wrap; gap:14px; align-items:end; justify-content:space-between; margin-bottom:20px; }
        .queue-filters { display:flex; flex-wrap:wrap; gap:12px; align-items:end; }
        .queue-field label { display:block; color:#5d6879; font-size:13px; font-weight:600; margin:0 0 6px; }
        .queue-field select,.queue-field input { min-width:200px; height:42px; border:1px solid #d8dee8; border-radius:10px; padding:0 12px; background:white; }
        .queue-action { border:0; border-radius:10px; padding:11px 17px; font-weight:700; color:white; background:#1669d5; cursor:pointer; }
        .queue-action.secondary { color:#25324a; border:1px solid #d8dee8; background:#fff; }
        .queue-action.skip { background:#d88a24; }
        .queue-action:disabled { opacity:.5; cursor:not-allowed; }
        .queue-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; margin-bottom:18px; }
        .queue-stat { background:#fff; border:1px solid #e2e7ef; border-radius:14px; padding:15px 18px; box-shadow:0 3px 12px rgba(18,40,70,.04); }
        .queue-stat span { display:block; color:#68758a; font-size:13px; }
        .queue-stat strong { display:block; margin-top:3px; font-size:24px; }
        .queue-main { display:grid; grid-template-columns:minmax(0,1.1fr) minmax(300px,.9fr); gap:16px; }
        .queue-panel { background:#fff; border:1px solid #e2e7ef; border-radius:16px; overflow:hidden; box-shadow:0 3px 12px rgba(18,40,70,.04); }
        .queue-panel h2 { margin:0; padding:15px 18px; border-bottom:1px solid #edf0f5; font-size:16px; }
        .queue-now { min-height:270px; display:flex; flex-direction:column; justify-content:center; align-items:center; padding:24px; background:linear-gradient(145deg,#f1f8ff,#edf8f5); text-align:center; }
        .queue-now small { color:#63738a; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
        .queue-code { margin:4px 0; color:#155fc2; font-size:64px; font-weight:800; line-height:1.15; letter-spacing:.02em; }
        .queue-empty { color:#68758a; font-size:16px; }
        .queue-list { margin:0; padding:0; list-style:none; }
        .queue-list li { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:12px 18px; border-bottom:1px solid #edf0f5; }
        .queue-list li:last-child { border-bottom:0; }
        .queue-list b { color:#24436c; font-size:18px; }
        .queue-list span { color:#758197; font-size:12px; }
        .queue-empty-list { padding:25px 18px; color:#758197; text-align:center; }
        .queue-subactions { display:flex; gap:8px; flex-wrap:wrap; justify-content:center; margin-top:16px; }
        @media(max-width:800px) { .queue-main { grid-template-columns:1fr; } .queue-stats { grid-template-columns:1fr; } .queue-controls { align-items:stretch; } .queue-filters { width:100%; } .queue-field { flex:1; } .queue-field select,.queue-field input { min-width:0; width:100%; } }
    </style>

    @php
        $clinics = $this->clinics();
        $current = $this->currentAppointment();
        $waiting = $this->waitingAppointments();
        $stats = $this->queueStats();
    @endphp

    @if ($clinics->isEmpty())
        <div class="queue-panel queue-empty-list">Akun Anda belum ditautkan ke klinik. Hubungi administrator untuk mengatur akses klinik.</div>
    @else
        <div class="queue-controls">
            <div class="queue-filters">
                <div class="queue-field">
                    <label for="queue-clinic">Klinik</label>
                    <select id="queue-clinic" wire:model.live="clinicId">
                        @foreach ($clinics as $clinic)
                            <option value="{{ $clinic->id }}">{{ $clinic->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="queue-field">
                    <label for="queue-date">Tanggal appointment</label>
                    <input id="queue-date" type="date" wire:model.live="queueDate">
                </div>
            </div>
            @if ($this->canManageQueue() && $this->isToday())
                <button type="button" class="queue-action" wire:click="callNext" wire:loading.attr="disabled">
                    Panggil Berikutnya
                </button>
            @endif
            <a class="queue-action secondary" href="{{ \App\Filament\Pages\QueueDashboard::getUrl() }}" target="_blank" rel="noopener">
                Buka Dashboard Panggilan
            </a>
        </div>

        <div class="queue-stats">
            <div class="queue-stat"><span>Menunggu</span><strong>{{ $stats['waiting'] }}</strong></div>
            <div class="queue-stat"><span>Selesai dipanggil</span><strong>{{ $stats['served'] }}</strong></div>
            <div class="queue-stat"><span>Dilewati</span><strong>{{ $stats['skipped'] }}</strong></div>
        </div>

        <div class="queue-main">
            <section class="queue-panel">
                <h2>Antrian Saat Ini</h2>
                <div class="queue-now">
                    @if ($current)
                        <small>Silakan menuju ruang pemeriksaan</small>
                        <div class="queue-code">{{ $current->queue_code }}</div>
                        @if ($this->canManageQueue() && $this->isToday())
                            <div class="queue-subactions">
                                <button type="button" class="queue-action secondary" wire:click="recallCurrent">Panggil Ulang</button>
                                <button type="button" class="queue-action skip" wire:click="skipCurrent">Lewati</button>
                            </div>
                        @endif
                    @else
                        <div class="queue-empty">Belum ada antrian yang dipanggil.</div>
                    @endif
                </div>
            </section>

            <section class="queue-panel">
                <h2>Daftar Menunggu</h2>
                @if ($waiting->isEmpty())
                    <div class="queue-empty-list">Tidak ada antrian menunggu.</div>
                @else
                    <ul class="queue-list">
                        @foreach ($waiting as $appointment)
                            <li>
                                <b>{{ $appointment->queue_code }}</b>
                                <span>{{ $appointment->clinic_user?->user?->name ?? 'Dokter belum ditentukan' }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    @endif
</div>
