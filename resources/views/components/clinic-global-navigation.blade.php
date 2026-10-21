@php($navigationGroups = \Filament\Facades\Filament::getNavigation())

<nav class="clinic-global-nav" aria-label="Navigasi utama">
    <a class="clinic-global-nav__brand" href="{{ \App\Filament\Pages\Dashboard::getUrl() }}">
        <span class="clinic-global-nav__mark">NH</span>
        <span><strong>Nusahome Lab</strong><small>SIM Klinik</small></span>
    </a>

    <div class="clinic-global-nav__items">
        @foreach ($navigationGroups as $group)
            <section class="clinic-global-nav__group">
                @if ($group->getLabel())<h2>{{ $group->getLabel() }}</h2>@endif
                @foreach ($group->getItems() as $item)
                    @if ($item->getUrl())
                        <a href="{{ $item->getUrl() }}" @class(['clinic-global-nav__item', 'active' => $item->isActive()]) @if ($item->shouldOpenUrlInNewTab()) target="_blank" rel="noopener noreferrer" @endif>
                            @if ($item->getIcon())<x-filament::icon :icon="$item->getIcon()" class="clinic-global-nav__icon" />@endif
                            <span>{{ $item->getLabel() }}</span>
                            @if ($item->getBadge())<small>{{ $item->getBadge() }}</small>@endif
                        </a>
                    @endif
                @endforeach
            </section>
        @endforeach
    </div>
</nav>

<style>
    .clinic-emr-layout{display:grid;grid-template-columns:var(--sidebar-width,14.5rem) minmax(0,1fr);align-items:start;gap:18px;width:100%;min-width:0}
    .clinic-emr-content{min-width:0}
    .clinic-global-nav{position:sticky;top:1rem;display:flex;flex-direction:column;width:var(--sidebar-width,14.5rem);max-height:calc(100vh - 2rem);overflow:hidden;border:1px solid #e2e8f0;border-radius:14px;background:#fff;box-shadow:0 4px 18px #0f172a0a;color:#334155}
    .clinic-global-nav__brand{display:flex;align-items:center;gap:11px;padding:17px 16px;border-bottom:1px solid #e8edf4;color:#1e293b;text-decoration:none}
    .clinic-global-nav__brand strong,.clinic-global-nav__brand small{display:block}.clinic-global-nav__brand strong{font-size:14px;font-weight:750}.clinic-global-nav__brand small{margin-top:3px;color:#718096;font-size:12px}
    .clinic-global-nav__mark{display:grid;place-items:center;width:36px;height:36px;border-radius:10px;background:#eaf2ff;color:#2563eb;font-size:12px;font-weight:800}
    .clinic-global-nav__items{overflow-y:auto;padding:9px}
    .clinic-global-nav__group{display:grid;gap:3px;margin-bottom:12px}.clinic-global-nav__group:last-child{margin-bottom:0}
    .clinic-global-nav__group h2{margin:7px 8px 4px;color:#7c8ba1;font-size:11px;font-weight:750;letter-spacing:.06em;text-transform:uppercase}
    .clinic-global-nav__item{display:flex;align-items:center;gap:10px;min-height:40px;padding:8px 10px;border-radius:8px;color:#475569;text-decoration:none;font-size:13px;font-weight:550;transition:background .15s,color .15s}
    .clinic-global-nav__item:hover{background:#f1f6ff;color:#1d4ed8}.clinic-global-nav__item.active{background:#eaf2ff;color:#1d4ed8;font-weight:700}
    .clinic-global-nav__icon{width:17px;height:17px;flex:none;color:#8091a7}.clinic-global-nav__item.active .clinic-global-nav__icon{color:#2563eb}.clinic-global-nav__item small{margin-left:auto;border-radius:999px;background:#eff6ff;padding:2px 6px;color:#2563eb;font-size:10px}
    body:has(.clinic-emr-layout) .fi-main-sidebar{display:none!important}
    @media(max-width:1023px){.clinic-emr-layout{grid-template-columns:minmax(0,1fr);gap:12px}.clinic-global-nav{position:relative;top:auto;width:100%;max-height:220px}.clinic-global-nav__items{display:flex;gap:12px;overflow-x:auto;overflow-y:hidden}.clinic-global-nav__group{display:flex;align-items:center;gap:4px;flex:none;margin:0}.clinic-global-nav__group h2{display:none}.clinic-global-nav__item{white-space:nowrap}}
</style>
