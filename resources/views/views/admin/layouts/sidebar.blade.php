<!--  BEGIN SIDEBAR  -->
<style>
.dw-side{position:fixed;top:0;left:0;width:250px;height:100vh;overflow-y:auto;overflow-x:hidden;
  background:var(--panel);border-right:1px solid var(--line);z-index:1000;display:flex;flex-direction:column;}
.dw-side-top{display:flex;align-items:center;justify-content:space-between;padding:16px 18px;
  border-bottom:1px solid var(--line);min-height:62px;}
.dw-logo{display:flex;align-items:center;gap:10px;text-decoration:none;}
.dw-logo img{width:30px;height:30px;border-radius:9px;}
.dw-logo .wm{font-family:'Space Grotesk',sans-serif;font-weight:600;font-size:17px;color:var(--text);letter-spacing:-.3px;}
.dw-collapse{cursor:pointer;color:var(--faint);display:none;}

.dw-profile{padding:14px 18px;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:11px;}
.dw-profile .av{width:36px;height:36px;border-radius:9px;flex-shrink:0;
  background:linear-gradient(135deg,#1f6f57,#0e3a2c);display:grid;place-items:center;
  font-family:'Space Grotesk';font-weight:600;font-size:14px;color:#fff;overflow:hidden;}
.dw-profile .av img{width:100%;height:100%;object-fit:cover;}
.dw-profile .nm{font-size:13px;font-weight:600;color:var(--text);line-height:1.3;}
.dw-profile .rl{font-size:11px;color:var(--faint);font-family:'JetBrains Mono',monospace;}

.dw-nav{flex:1;padding:8px 12px;list-style:none;margin:0;}
.dw-nav .grp{font-family:'JetBrains Mono',monospace;font-size:10px;text-transform:uppercase;
  letter-spacing:.14em;color:var(--faint);padding:14px 10px 6px;}
.dw-item{display:flex;align-items:center;gap:11px;padding:9px 10px;border-radius:9px;
  color:var(--muted);font-weight:500;font-size:13.5px;margin-bottom:2px;cursor:pointer;
  transition:.16s;text-decoration:none;}
.dw-item svg{width:18px;height:18px;stroke:currentColor;fill:none;stroke-width:1.8;flex-shrink:0;}
.dw-item .txt{flex:1;}
.dw-item .caret{width:14px;height:14px;transition:transform .2s;}
.dw-item:hover{background:var(--panel-2);color:var(--text);}
.dw-item.active{background:var(--accent-soft);color:var(--accent);}
.dw-item[aria-expanded="true"] .caret{transform:rotate(90deg);}
.dw-sub{list-style:none;margin:2px 0 4px;padding:0 0 0 40px;}
.dw-sub a{display:block;padding:7px 10px;color:var(--muted);text-decoration:none;font-size:13px;border-radius:8px;transition:.16s;}
.dw-sub a:hover{color:var(--accent);background:var(--panel-2);}
.dw-logout{color:var(--down)!important;}
.dw-logout:hover{background:rgba(255,107,129,.10)!important;color:var(--down)!important;}
@media(max-width:991px){
  .dw-side{transform:translateX(-100%);transition:transform .25s;}
  body.sidebar-open .dw-side{transform:translateX(0);}
  .dw-collapse{display:inline-flex;}
}
</style>

<div class="dw-side sidebar-wrapper" id="dwSidebar">

    <div class="dw-side-top">
        <a href="{{ route('admin.index') }}" class="dw-logo">
            <img src="{{ asset(getSiteSettings('favicon')) }}" alt="logo">
            <span class="wm">{{ getSiteSettings('short_title') }}</span>
        </a>
        <span class="dw-collapse sidebarCollapse">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><polyline points="18 6 6 18"/><polyline points="6 6 18 18"/></svg>
        </span>
    </div>

    <div class="dw-profile">
        <div class="av">
            <img src="{{ asset('assets/adminoffice/src/assets/img/profile-30-2.png') }}" alt="avatar"
                 onerror="this.style.display='none';this.parentNode.textContent='{{ strtoupper(substr(Auth::guard('admin')->user()->username,0,2)) }}';">
        </div>
        <div style="min-width:0;">
            <div class="nm">{{ Auth::guard('admin')->user()->username }}</div>
            <div class="rl">{{ Auth::guard('admin')->user()->role->role->name ?? 'admin' }}</div>
        </div>
    </div>

    <ul class="dw-nav">

        @php
            $sideBars = App\Models\AdminSideBar::where('status','1')->where('parent_id', null)->orderBy('order','ASC')->get();
            $defaultIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>';
            $caret = '<svg class="caret" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>';
        @endphp

        <div class="grp">Main</div>

        @if(count($sideBars) > 0)
            @foreach($sideBars as $sidebar)
                @can($sidebar->rule != null ? "$sidebar->rule" : 'view_'.$sidebar->model)
                    @if($sidebar->can_view == '0' && $sidebar->can_add == '0')
                        <li>
                            <a href="{{ $sidebar->use_route == '1' && Route::has($sidebar->route) ? route($sidebar->route) : route('admin.ViewItem', $sidebar->model) }}" class="dw-item">
                                <span style="display:inline-flex;">{!! $sidebar->icon ?? $sidebar->image ?? $defaultIcon !!}</span>
                                <span class="txt">{{ $sidebar->name }}</span>
                            </a>
                        </li>
                    @else
                        <li>
                            <a href="#menu_{{ $sidebar->id }}" data-bs-toggle="collapse" aria-expanded="false" class="dw-item">
                                <span style="display:inline-flex;">{!! $sidebar->icon ?? $sidebar->image ?? $defaultIcon !!}</span>
                                <span class="txt">{{ $sidebar->name }}</span>
                                {!! $caret !!}
                            </a>
                            <ul class="collapse dw-sub" id="menu_{{ $sidebar->id }}">
                                @if($sidebar->is_menu == '0')
                                    @can($sidebar->rule != null ? "$sidebar->rule" : 'view_'.$sidebar->model)
                                        @if($sidebar->can_view == '1')
                                            <li><a href="{{ route('admin.ViewItem', $sidebar->model) }}">View</a></li>
                                        @endif
                                    @endcan
                                    @can('create_'.$sidebar->model)
                                        @if($sidebar->can_add == '1')
                                            <li><a href="{{ route('admin.AddNewItem', $sidebar->model) }}">Add</a></li>
                                        @endif
                                    @endcan
                                @endif
                                @foreach($sidebar->children as $child)
                                    @canany([$child->rule != null ? $child->rule : 'view_'.$child->model, 'create_'.$child->model])
                                        <li><a href="{{ $child->use_route == '1' && Route::has($child->route) ? route($child->route, $child->route_parameter) : route('admin.ViewItem', $child->model) }}">{{ $child->name }}</a></li>
                                    @endcanany
                                @endforeach
                            </ul>
                        </li>
                    @endif
                @endcan
            @endforeach
        @endif

        <div class="grp">Operations</div>

        <li>
            <a href="{{ route('admin.verifications') }}" class="dw-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2l7 4v6c0 5-3.5 9-7 10-3.5-1-7-5-7-10V6l7-4z"/><path d="M9 12l2 2 4-4"/></svg>
                <span class="txt">Verifications</span>
            </a>
        </li>

        @canany(['view_naira_virtual_card', 'view_dollar_virtual_card'])
        <li>
            <a href="#menu_vcards" data-bs-toggle="collapse" aria-expanded="false" class="dw-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                <span class="txt">Virtual Cards</span>{!! $caret !!}
            </a>
            <ul class="collapse dw-sub" id="menu_vcards">
                @can('view_naira_virtual_card')<li><a href="{{ url('/adminoffice/virtual-ngn-cards') }}">NGN Cards</a></li>@endcan
                @can('view_dollar_virtual_card')<li><a href="{{ url('/adminoffice/virtual-usd-cards') }}">USD Cards</a></li>@endcan
            </ul>
        </li>
        @endcanany

        @canany(['view_physical_cards', 'view_assigned_cards'])
        <li>
            <a href="#menu_pcards" data-bs-toggle="collapse" aria-expanded="false" class="dw-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                <span class="txt">Physical Cards</span>{!! $caret !!}
            </a>
            <ul class="collapse dw-sub" id="menu_pcards">
                @can('view_physical_cards')<li><a href="{{ url('/adminoffice/physical_cards') }}">View Cards</a></li>@endcan
                @can('view_assigned_cards')<li><a href="{{ url('/adminoffice/card_credentials') }}">Assign ATM Cards</a></li>@endcan
            </ul>
        </li>
        @endcanany

        @canany(['view_foreign_account_requests', 'view_dollar_virtual_accounts', 'view_euro_virtual_accounts'])
        <li>
            <a href="#menu_foreign" data-bs-toggle="collapse" aria-expanded="false" class="dw-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                <span class="txt">Foreign Accounts</span>{!! $caret !!}
            </a>
            <ul class="collapse dw-sub" id="menu_foreign">
                @can('view_dollar_virtual_accounts')<li><a href="{{ url('/adminoffice/foreign-accounts', ['currency' => 'USD']) }}">Foreign Accounts (USD)</a></li>@endcan
                @can('view_euro_virtual_accounts')<li><a href="{{ url('/adminoffice/foreign-accounts', ['currency' => 'EUR']) }}">Foreign Accounts (EUR)</a></li>@endcan
                @can('view_foreign_account_requests')<li><a href="{{ url('/adminoffice/foreign-accounts/request') }}">Account Requests</a></li>@endcan
            </ul>
        </li>
        @endcanany

        <div class="grp">Rates &amp; Charges</div>

        @canany(['view_exchange_rate', 'view_virtual_card_fund_fee'])
        <li>
            <a href="#menu_exchange" data-bs-toggle="collapse" aria-expanded="false" class="dw-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                <span class="txt">Exchange Rates</span>{!! $caret !!}
            </a>
            <ul class="collapse dw-sub" id="menu_exchange">
                @can('view_exchange_rate')<li><a href="{{ url('/adminoffice/exchange-rates') }}">Virtual Account</a></li>@endcan
                @can('view_virtual_card_fund_fee')<li><a href="{{ url('/adminoffice/virtual-card-exchange-rates') }}">USD Virtual Card</a></li>@endcan
            </ul>
        </li>
        @endcanany

        @canany(['view_naira_deposit_charges', 'view_usd_deposit_charges', 'view_eur_deposit_charges'])
        <li>
            <a href="#menu_deposit" data-bs-toggle="collapse" aria-expanded="false" class="dw-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><path d="M12 6v6l3-3m-3 3l-3-3"/></svg>
                <span class="txt">Deposit Charges</span>{!! $caret !!}
            </a>
            <ul class="collapse dw-sub" id="menu_deposit">
                @can('view_naira_deposit_charges')<li><a href="{{ route('admin.deposit.fee.index', 'NGN') }}">Naira Charges</a></li>@endcan
                @can('view_usd_deposit_charges')<li><a href="{{ route('admin.deposit.fee.index', 'USD') }}">USD Charges</a></li>@endcan
                @can('view_eur_deposit_charges')<li><a href="{{ route('admin.deposit.fee.index', 'EUR') }}">Euro Charges</a></li>@endcan
            </ul>
        </li>
        @endcanany

        @can('view_recharge_card_epin_rates')
        <li>
            <a href="{{ url('/adminoffice/epin_rates') }}" class="dw-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M4 11a9 9 0 0 1 9 9"/><path d="M4 4a16 16 0 0 1 16 16"/><circle cx="5" cy="19" r="1"/></svg>
                <span class="txt">Recharge Epin Rates</span>
            </a>
        </li>
        @endcan

        @can('view_vat')
        <li>
            <a href="{{ url('/adminoffice/vat_settings') }}" class="dw-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M4 2h16v20H4V2z"/><circle cx="12" cy="12" r="3"/></svg>
                <span class="txt">VAT Settings</span>
            </a>
        </li>
        @endcan

        @can('view_stamp_duty')
        <li>
            <a href="{{ url('/adminoffice/stamp_duty_settings') }}" class="dw-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M4 2h16v20H4z"/><circle cx="12" cy="13" r="3"/></svg>
                <span class="txt">Stamp Duty</span>
            </a>
        </li>
        @endcan

        <div class="grp">System</div>

        @can('view_push_updates')
        <li>
            <a href="{{ url('/adminoffice/push-updates') }}" class="dw-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M4 11a9 9 0 0 1 9 9"/><path d="M4 4a16 16 0 0 1 16 16"/><circle cx="5" cy="19" r="1"/></svg>
                <span class="txt">Push Updates</span>
            </a>
        </li>
        @endcan

        @can('view_maintenance')
        <li>
            <a href="{{ url('/adminoffice/maintenance') }}" class="dw-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span class="txt">Maintenance</span>
            </a>
        </li>
        @endcan

        <li>
            <a href="{{ route('admin.error.log.index') }}" class="dw-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M4 2h16v20H4V2z"/><path d="M12 9l4 7H8l4-7z"/></svg>
                <span class="txt">Error Logs</span>
            </a>
        </li>

        <li style="margin-top:8px;border-top:1px solid var(--line);padding-top:8px;">
            <a href="{{ route('AdminLogout') }}" class="dw-item dw-logout">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span class="txt">Logout</span>
            </a>
        </li>

    </ul>
</div>
<!--  END SIDEBAR  -->
