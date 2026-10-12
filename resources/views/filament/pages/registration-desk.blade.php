<div class="space-y-6 registration-desk">
    <style>
        .queue-ticket { width: 80mm; max-width: 100%; padding: 5mm; margin: 0 auto; color: #111; background: #fff; font-family: Arial, sans-serif; text-align: center; }
        .queue-ticket .clinic-name { font-size: 15px; font-weight: 700; }
        .queue-ticket .queue-number { margin: 5mm 0; font-size: 34px; font-weight: 800; line-height: 1; }
        .queue-ticket .ticket-rule { border: 0; border-top: 1px dashed #555; margin: 4mm 0; }
        .queue-ticket .ticket-info { font-size: 12px; line-height: 1.5; }
        .queue-ticket .ticket-date { margin-top: 4mm; font-size: 11px; }
        @media print {
            @page { size: 80mm auto; margin: 0; }
            body * { visibility: hidden !important; }
            .queue-ticket, .queue-ticket * { visibility: visible !important; }
            .queue-ticket { position: absolute; left: 0; top: 0; width: 80mm; max-width: none; margin: 0; padding: 4mm; }
            .no-print { display: none !important; }
        }
    </style>

    <div class="no-print rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <form wire:submit="register" class="space-y-6">
            {{ $this->form }}
            <x-filament::button type="submit" icon="heroicon-o-clipboard-document-check">
                Daftarkan dan Buat Nomor Antrean
            </x-filament::button>
        </form>
    </div>

    @if ($registeredAppointment)
        <div class="space-y-4">
            <div class="no-print flex justify-center">
                <x-filament::button icon="heroicon-o-printer" color="success" onclick="window.print()">
                    Cetak Tiket (Thermal 80 mm)
                </x-filament::button>
            </div>
            <section class="queue-ticket">
                <div class="clinic-name">{{ $registeredAppointment->clinic_user?->clinic?->name ?? config('app.name') }}</div>
                <hr class="ticket-rule">
                <div class="ticket-info">NOMOR ANTREAN</div>
                <div class="queue-number">{{ $registeredAppointment->queue_code }}</div>
                <hr class="ticket-rule">
                <div class="ticket-info">
                    <strong>{{ $registeredAppointment->patient?->name }}</strong><br>
                    Dokter: {{ $registeredAppointment->clinic_user?->user?->name ?? '-' }}
                </div>
                <div class="ticket-date">{{ $registeredAppointment->schedule_date?->format('d/m/Y') }}</div>
                <hr class="ticket-rule">
                <div class="ticket-info">Harap menunggu nomor Anda dipanggil.</div>
            </section>
        </div>
    @endif
</div>
