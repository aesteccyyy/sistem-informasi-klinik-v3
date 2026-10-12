<x-filament-panels::page.simple>
    <style>
        .fi-simple-layout { background: linear-gradient(135deg, #f7f5fb 0%, #f4eff8 55%, #fff7fb 100%); }
        .fi-simple-main-ctn { padding: 2rem 1.25rem; }
        .fi-simple-main { width: min(100%, 980px) !important; max-width: 980px !important; padding: 0 !important; overflow: visible; border: 0 !important; border-radius: 0 !important; background: transparent !important; box-shadow: none !important; }
        .fi-simple-page > section { gap: 1.25rem !important; }
        .clinic-login { min-height: 470px; display: grid; grid-template-columns: minmax(300px, 340px) minmax(0, 1fr); overflow: hidden; border: 1px solid #e7e1ed; border-radius: 22px; background: rgba(255,255,255,.86); box-shadow: 0 16px 42px rgba(55, 38, 74, .11); }
        .clinic-login__form { padding: 44px 40px; display: flex; flex-direction: column; justify-content: center; border-right: 1px solid #eee8f2; background: rgba(255,255,255,.58); }
        .clinic-login__identity { display: flex; align-items: center; justify-content: center; gap: 12px; padding-bottom: 28px; margin-bottom: 28px; border-bottom: 1px solid #e8e2eb; color: #364152; }
        .clinic-login__mark { width: 46px; height: 46px; display: grid; place-items: center; border-radius: 15px; color: #fff; background: linear-gradient(145deg, #14b8a6, #2563eb); font-weight: 800; letter-spacing: -.06em; }
        .clinic-login__identity strong { display: block; font-size: 16px; }
        .clinic-login__identity span { display: block; margin-top: 2px; color: #7b8492; font-size: 12px; }
        .clinic-login__form h1 { margin: 0; color: #202534; font-size: 25px; font-weight: 750; letter-spacing: -.03em; }
        .clinic-login__form > p { margin: 8px 0 24px; color: #667085; font-size: 14px; }
        .clinic-login__art { position: relative; min-height: 470px; padding: 36px 32px; overflow: hidden; background: radial-gradient(ellipse at 68% 62%, #e5e8ff 0%, #f0eafb 42%, #f8f4fa 78%); }
        .clinic-login__art:before, .clinic-login__art:after { content: ''; position: absolute; border-radius: 50%; background: rgba(255,255,255,.44); }
        .clinic-login__art:before { width: 280px; height: 280px; right: -65px; top: -90px; }
        .clinic-login__art:after { width: 180px; height: 180px; left: -75px; bottom: -65px; }
        .clinic-login__welcome { position: relative; z-index: 1; }
        .clinic-login__welcome h2 { margin: 0; color: #202534; font-size: 27px; font-weight: 750; letter-spacing: -.035em; }
        .clinic-login__welcome p { margin: 6px 0 0; color: #667085; font-size: 14px; }
        .clinic-login__welcome b { color: #394150; }
        .clinic-login__illustration { position: absolute; z-index: 1; left: 5%; right: 5%; top: 22%; width: 90%; height: 70%; }
        .clinic-login .fi-input-wrp { border-radius: 12px; }
        .clinic-login .fi-btn { border-radius: 999px; min-height: 46px; }
        .clinic-login .fi-fo-field-wrp-label { font-weight: 600; }
        .clinic-login__brand { width: fit-content; margin: 0 auto; padding: 7px 15px; border: 1px solid rgba(106, 91, 124, .14); border-radius: 12px; background: rgba(255,255,255,.86); box-shadow: 0 4px 16px rgba(55,38,74,.08); color: #64716d; font-size: 17px; font-weight: 800; letter-spacing: .03em; }
        .clinic-login__brand span { color: #12a99a; }
        @media (max-width: 760px) {
            .fi-simple-main-ctn { padding: 1.5rem .75rem; }
            .clinic-login { min-height: auto; }
            .clinic-login { grid-template-columns: 1fr; min-height: auto; }
            .clinic-login__form { padding: 30px 24px; border-right: 0; }
            .clinic-login__art { min-height: 290px; padding: 24px; }
            .clinic-login__art h2 { font-size: 22px; }
            .clinic-login__illustration { top: 28%; height: 68%; }
        }
    </style>

    <div class="clinic-login__brand">SIM<span>KLINIK</span> <span style="color:#b7b1c1;font-weight:400">·</span> Nusahome Lab</div>

    <div class="clinic-login">
        <section class="clinic-login__form" aria-label="Form masuk">
            <div class="clinic-login__identity">
                <div class="clinic-login__mark">NH</div>
                <div><strong>Nusahome Lab</strong><span>Sistem Informasi Klinik</span></div>
            </div>
            <h1>Masuk Aplikasi</h1>
            <p>Silakan masukkan email dan kata sandi Anda.</p>

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}
            <x-filament-panels::form wire:submit="authenticate">
                {{ $this->form }}
                <x-filament-panels::form.actions
                    :actions="$this->getCachedFormActions()"
                    :full-width="$this->hasFullWidthFormActions()"
                />
            </x-filament-panels::form>
            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}
        </section>

        <section class="clinic-login__art" aria-label="Informasi Nusahome Lab">
            <div class="clinic-login__welcome">
                <h2>Selamat datang</h2>
                <p>di aplikasi <b>Sistem Informasi Klinik · Nusahome Lab</b></p>
            </div>
            <svg class="clinic-login__illustration" viewBox="0 0 720 430" role="img" aria-label="Ilustrasi layanan klinik">
                <defs>
                    <linearGradient id="floor" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#fff"/><stop offset="1" stop-color="#e9eafa"/></linearGradient>
                    <linearGradient id="screen" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#17b9ad"/><stop offset="1" stop-color="#2685d8"/></linearGradient>
                    <filter id="shadow" x="-30%" y="-30%" width="160%" height="180%"><feDropShadow dx="0" dy="10" stdDeviation="8" flood-color="#59627d" flood-opacity=".16"/></filter>
                </defs>
                <g opacity=".9" filter="url(#shadow)">
                    <path d="M360 48 630 190 360 345 90 190Z" fill="url(#floor)" stroke="#d9dced" stroke-width="2"/>
                    <path d="M150 190 360 310 570 190M255 105 465 225M465 105 255 225" fill="none" stroke="#e2e5f0" stroke-width="2"/>
                    <path d="M196 137 277 91 346 130 265 177Z" fill="#fff" stroke="#d5dae8"/>
                    <path d="M207 135 275 97 332 130 264 169Z" fill="url(#screen)"/>
                    <path d="M221 135h49m-49 8h36m-36 8h25" stroke="#d8ffff" stroke-width="3" stroke-linecap="round"/>
                    <path d="M390 97 462 56 513 85 441 127Z" fill="#fff" stroke="#d5dae8"/>
                    <path d="M399 96 461 61 502 84 440 119Z" fill="#e9f4f5"/>
                    <path d="M452 74v18m-9-9h18" stroke="#ee6b83" stroke-width="5" stroke-linecap="round"/>
                    <path d="M330 231 383 201 436 231 383 262Z" fill="#39bcb4"/>
                    <path d="M330 231v23l53 31v-23Z" fill="#269d9d"/>
                    <path d="M383 262v23l53-31v-23Z" fill="#278d98"/>
                    <path d="M305 281 384 326 462 281" fill="none" stroke="#d5dae8" stroke-width="4"/>
                    <rect x="480" y="146" width="67" height="42" rx="7" transform="rotate(30 480 146)" fill="#fff" stroke="#d5dae8"/>
                    <rect x="489" y="151" width="48" height="27" rx="4" transform="rotate(30 489 151)" fill="#daf8f3"/>
                    <path d="m501 165 7 5 10-12" fill="none" stroke="#17aa9e" stroke-width="3"/>
                </g>
                <g stroke-linecap="round" stroke-linejoin="round">
                    <g transform="translate(178 232)"><circle cx="0" cy="0" r="8" fill="#f1b99c"/><path d="M-8 9h16l5 31h-26Z" fill="#f07a91"/><path d="M-5 40-9 62m17-22 5 22" stroke="#455371" stroke-width="6"/><path d="M-10 16-22 34m38-18 12 13" stroke="#f1b99c" stroke-width="5"/></g>
                    <g transform="translate(293 202)"><circle cx="0" cy="0" r="8" fill="#b8795e"/><path d="M-9 9h18l4 32h-26Z" fill="#fff"/><path d="M-4 41-7 64m16-23 4 23" stroke="#46516d" stroke-width="6"/><path d="M-10 16-22 33m40-17 13 15" stroke="#b8795e" stroke-width="5"/><path d="M-4 17h8v12h-8Z" fill="#17b9ad"/></g>
                    <g transform="translate(473 227)"><circle cx="0" cy="0" r="8" fill="#ecc09f"/><path d="M-9 9h18l4 31h-26Z" fill="#566ba5"/><path d="M-5 40-8 61m16-21 5 21" stroke="#455371" stroke-width="6"/><path d="M-10 17-22 33m40-16 12 13" stroke="#ecc09f" stroke-width="5"/></g>
                    <g transform="translate(365 167)"><circle cx="0" cy="0" r="7" fill="#d59a78"/><path d="M-8 8h16l4 27h-24Z" fill="#26afa8"/><path d="M-4 35-6 53m13-18 4 18" stroke="#485577" stroke-width="5"/><path d="M-9 15-19 28m34-13 10 12" stroke="#d59a78" stroke-width="4"/></g>
                </g>
                <g fill="#23b6aa"><path d="M111 270q17-35 34 0z"/><path d="M555 255q17-35 34 0z"/><path d="M401 343q13-27 26 0z"/></g>
                <g fill="#ffbd69"><circle cx="129" cy="263" r="3"/><circle cx="573" cy="248" r="3"/><circle cx="414" cy="337" r="3"/></g>
            </svg>
        </section>
    </div>
</x-filament-panels::page.simple>
