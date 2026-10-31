<!--  BEGIN SIDEBAR  -->
<div class="sidebar-wrapper sidebar-theme" style="position: fixed; top: 0; left: 0; width: 250px; height: 100vh; overflow-y: auto; overflow-x: hidden; background: #1a1a2e; z-index: 1000; display: flex; flex-direction: column;">

    <nav id="sidebar" style="flex: 1; display: flex; flex-direction: column;">

        {{-- Logo --}}
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); min-height: 70px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <a href="{{ route('admin.index') }}">
                    <img src="{{ asset(getSiteSettings('favicon')) }}" alt="logo" style="width: 32px; height: 32px; border-radius: 6px;">
                </a>
                <a href="{{ route('admin.index') }}" style="color: #fff; font-size: 18px; font-weight: 700; text-decoration: none;">
                    {{ getSiteSettings('short_title') }}
                </a>
            </div>
            <div class="sidebarCollapse" style="cursor: pointer; color: rgba(255,255,255,0.6);">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="11 17 6 12 11 7"></polyline><polyline points="18 17 13 12 18 7"></polyline></svg>
            </div>
        </div>

        {{-- Profile --}}
        <div style="padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,0.1);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <img src="{{ asset('assets/adminoffice/src/assets/img/profile-30-2.png') }}" alt="avatar" style="width: 40px; height: 40px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.3);">
                <div>
                    <div style="color: #fff; font-weight: 600; font-size: 14px;">{{ Auth::guard('admin')->user()->username }}</div>
                    <div style="color: rgba(255,255,255,0.5); font-size: 12px;">{{ Auth::guard('admin')->user()->role->role->name ?? '' }}</div>
                </div>
            </div>
        </div>

        {{-- Menu --}}
        <ul style="list-style: none; padding: 12px 0; margin: 0; flex: 1;">

            @php
                $sideBars = App\Models\AdminSideBar::where('status','1')->where('parent_id', null)->orderBy('order','ASC')->get();
                $menuStyle = 'display: flex; align-items: center; justify-content: space-between; padding: 10px 20px; color: rgba(255,255,255,0.8); text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.2s; border-left: 3px solid transparent; gap: 10px;';
                $iconStyle = 'width: 20px; height: 20px; flex-shrink: 0; color: rgba(255,255,255,0.6);';
                $spanStyle = 'flex: 1; color: rgba(255,255,255,0.85); font-size: 14px;';
                $submenuStyle = 'list-style: none; padding: 4px 0 4px 52px; margin: 0; background: rgba(0,0,0,0.2);';
                $submenuLinkStyle = 'display: block; padding: 8px 16px; color: rgba(255,255,255,0.65); text-decoration: none; font-size: 13px; transition: color 0.2s;';
            @endphp

            @if(count($sideBars) > 0)
                @foreach($sideBars as $sidebar)
                    @can($sidebar->rule != null ? "$sidebar->rule" : 'view_'.$sidebar->model)
                        @if($sidebar->can_view == '0' && $sidebar->can_add == '0')
                            {{-- Simple link --}}
                            <li style="margin: 2px 0;">
                                <a href="{{ $sidebar->use_route == '1' && Route::has($sidebar->route) ? route($sidebar->route) : route('admin.ViewItem', $sidebar->model) }}"
                                   style="{{ $menuStyle }}"
                                   onmouseover="this.style.background='rgba(255,255,255,0.08)'; this.style.borderLeftColor='#4f8ef7';"
                                   onmouseout="this.style.background='transparent'; this.style.borderLeftColor='transparent';">
                                    <div style="display:flex; align-items:center; gap:12px; flex:1;">
                                        <span style="{{ $iconStyle }}">{!! $sidebar->icon ?? $sidebar->image ?? '<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'20\' height=\'20\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\'><path d=\'M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z\'></path></svg>' !!}</span>
                                        <span style="{{ $spanStyle }}">{{ $sidebar->name }}</span>
                                    </div>
                                </a>
                            </li>
                        @else
                            {{-- Dropdown --}}
                            <li style="margin: 2px 0;">
                                <a href="#menu_{{ $sidebar->id }}"
                                   data-bs-toggle="collapse"
                                   aria-expanded="false"
                                   style="{{ $menuStyle }} cursor: pointer;"
                                   onmouseover="this.style.background='rgba(255,255,255,0.08)';"
                                   onmouseout="this.style.background='transparent';">
                                    <div style="display:flex; align-items:center; gap:12px; flex:1;">
                                        <span style="{{ $iconStyle }}">{!! $sidebar->icon ?? $sidebar->image ?? '<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'20\' height=\'20\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\'><line x1=\'8\' y1=\'6\' x2=\'21\' y2=\'6\'></line><line x1=\'8\' y1=\'12\' x2=\'21\' y2=\'12\'></line><line x1=\'8\' y1=\'18\' x2=\'21\' y2=\'18\'></line></svg>' !!}</span>
                                        <span style="{{ $spanStyle }}">{{ $sidebar->name }}</span>
                                    </div>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.4)" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                </a>
                                <ul class="collapse" id="menu_{{ $sidebar->id }}" style="{{ $submenuStyle }}">
                                    @if($sidebar->is_menu == '0')
                                        @can($sidebar->rule != null ? "$sidebar->rule" : 'view_'.$sidebar->model)
                                            @if($sidebar->can_view == '1')
                                                <li>
                                                    <a href="{{ route('admin.ViewItem', $sidebar->model) }}" style="{{ $submenuLinkStyle }}"
                                                       onmouseover="this.style.color='#fff';" onmouseout="this.style.color='rgba(255,255,255,0.65)';">View</a>
                                                </li>
                                            @endif
                                        @endcan
                                        @can('create_'.$sidebar->model)
                                            @if($sidebar->can_add == '1')
                                                <li>
                                                    <a href="{{ route('admin.AddNewItem', $sidebar->model) }}" style="{{ $submenuLinkStyle }}"
                                                       onmouseover="this.style.color='#fff';" onmouseout="this.style.color='rgba(255,255,255,0.65)';">Add</a>
                                                </li>
                                            @endif
                                        @endcan
                                    @endif

                                    @foreach($sidebar->children as $child)
                                        @canany([$child->rule != null ? $child->rule : 'view_'.$child->model, 'create_'.$child->model])
                                            <li>
                                                <a href="{{ $child->use_route == '1' && Route::has($child->route) ? route($child->route, $child->route_parameter) : route('admin.ViewItem', $child->model) }}"
                                                   style="{{ $submenuLinkStyle }}"
                                                   onmouseover="this.style.color='#fff';" onmouseout="this.style.color='rgba(255,255,255,0.65)';">
                                                    {{ $child->name }}
                                                </a>
                                            </li>
                                        @endcanany
                                    @endforeach
                                </ul>
                            </li>
                        @endif
                    @endcan
                @endforeach
            @endif

            {{-- Hardcoded extra items --}}
            <li style="margin: 2px 0;">
                <a href="{{ route('admin.verifications') }}" style="{{ $menuStyle }}"
                   onmouseover="this.style.background='rgba(255,255,255,0.08)'; this.style.borderLeftColor='#4f8ef7';"
                   onmouseout="this.style.background='transparent'; this.style.borderLeftColor='transparent';">
                    <div style="display:flex; align-items:center; gap:12px; flex:1;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2"><path d="M12 2l7 4v6c0 5-3.5 9-7 10-3.5-1-7-5-7-10V6l7-4z"></path><path d="M9 12l2 2 4-4"></path></svg>
                        <span style="{{ $spanStyle }}">Verifications</span>
                    </div>
                </a>
            </li>

            <li style="margin: 2px 0;">
                <a href="{{ route('admin.error.log.index') }}" style="{{ $menuStyle }}"
                   onmouseover="this.style.background='rgba(255,255,255,0.08)'; this.style.borderLeftColor='#4f8ef7';"
                   onmouseout="this.style.background='transparent'; this.style.borderLeftColor='transparent';">
                    <div style="display:flex; align-items:center; gap:12px; flex:1;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2"><path d="M4 2h16v20H4V2z"></path><path d="M12 9l4 7H8l4-7z"></path></svg>
                        <span style="{{ $spanStyle }}">Error Logs</span>
                    </div>
                </a>
            </li>

            @canany(['view_exchange_rate', 'view_virtual_card_fund_fee'])
            <li style="margin: 2px 0;">
                <a href="#menu_exchange" data-bs-toggle="collapse" aria-expanded="false" style="{{ $menuStyle }} cursor: pointer;"
                   onmouseover="this.style.background='rgba(255,255,255,0.08)';" onmouseout="this.style.background='transparent';">
                    <div style="display:flex; align-items:center; gap:12px; flex:1;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>
                        <span style="{{ $spanStyle }}">Exchange Rates</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.4)" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
                <ul class="collapse" id="menu_exchange" style="{{ $submenuStyle }}">
                    @can('view_exchange_rate')
                    <li><a href="{{ url('/adminoffice/exchange-rates') }}" style="{{ $submenuLinkStyle }}"
                           onmouseover="this.style.color='#fff';" onmouseout="this.style.color='rgba(255,255,255,0.65)';">Virtual Account</a></li>
                    @endcan
                    @can('view_virtual_card_fund_fee')
                    <li><a href="{{ url('/adminoffice/virtual-card-exchange-rates') }}" style="{{ $submenuLinkStyle }}"
                           onmouseover="this.style.color='#fff';" onmouseout="this.style.color='rgba(255,255,255,0.65)';">USD Virtual Card</a></li>
                    @endcan
                </ul>
            </li>
            @endcanany

            @can('view_vat')
            <li style="margin: 2px 0;">
                <a href="{{ url('/adminoffice/vat_settings') }}" style="{{ $menuStyle }}"
                   onmouseover="this.style.background='rgba(255,255,255,0.08)'; this.style.borderLeftColor='#4f8ef7';"
                   onmouseout="this.style.background='transparent'; this.style.borderLeftColor='transparent';">
                    <div style="display:flex; align-items:center; gap:12px; flex:1;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2"><path d="M4 2h16v20H4V2z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <span style="{{ $spanStyle }}">VAT Settings</span>
                    </div>
                </a>
            </li>
            @endcan

            @can('view_stamp_duty')
            <li style="margin: 2px 0;">
                <a href="{{ url('/adminoffice/stamp_duty_settings') }}" style="{{ $menuStyle }}"
                   onmouseover="this.style.background='rgba(255,255,255,0.08)'; this.style.borderLeftColor='#4f8ef7';"
                   onmouseout="this.style.background='transparent'; this.style.borderLeftColor='transparent';">
                    <div style="display:flex; align-items:center; gap:12px; flex:1;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2"><path d="M4 2h16v20H4z"></path><circle cx="12" cy="13" r="3"></circle></svg>
                        <span style="{{ $spanStyle }}">Stamp Duty</span>
                    </div>
                </a>
            </li>
            @endcan

            @can('view_push_updates')
            <li style="margin: 2px 0;">
                <a href="{{ url('/adminoffice/push-updates') }}" style="{{ $menuStyle }}"
                   onmouseover="this.style.background='rgba(255,255,255,0.08)'; this.style.borderLeftColor='#4f8ef7';"
                   onmouseout="this.style.background='transparent'; this.style.borderLeftColor='transparent';">
                    <div style="display:flex; align-items:center; gap:12px; flex:1;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2"><path d="M4 11a9 9 0 0 1 9 9"></path><path d="M4 4a16 16 0 0 1 16 16"></path><circle cx="5" cy="19" r="1"></circle></svg>
                        <span style="{{ $spanStyle }}">Push Updates</span>
                    </div>
                </a>
            </li>
            @endcan

            @can('view_maintenance')
            <li style="margin: 2px 0;">
                <a href="{{ url('/adminoffice/maintenance') }}" style="{{ $menuStyle }}"
                   onmouseover="this.style.background='rgba(255,255,255,0.08)'; this.style.borderLeftColor='#4f8ef7';"
                   onmouseout="this.style.background='transparent'; this.style.borderLeftColor='transparent';">
                    <div style="display:flex; align-items:center; gap:12px; flex:1;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        <span style="{{ $spanStyle }}">Maintenance</span>
                    </div>
                </a>
            </li>
            @endcan

            {{-- Security monitoring --}}
            @can('view_security_alerts')
            <li style="margin: 2px 0;">
                <a href="{{ route('admin.security-alerts.index') }}" style="{{ $menuStyle }}"
                   onmouseover="this.style.background='rgba(255,255,255,0.08)'; this.style.borderLeftColor='#f59e0b';"
                   onmouseout="this.style.background='transparent'; this.style.borderLeftColor='transparent';">
                    <div style="display:flex; align-items:center; gap:12px; flex:1;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2"><path d="M12 2l8 4v6c0 5-3.5 9-8 10-4.5-1-8-5-8-10V6l8-4z"></path><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
                        <span style="{{ $spanStyle }}">Security Alerts</span>
                    </div>
                </a>
            </li>
            @endcan

            @canany(['view_naira_deposit_charges', 'view_usd_deposit_charges', 'view_eur_deposit_charges'])
            <li style="margin: 2px 0;">
                <a href="#menu_deposit" data-bs-toggle="collapse" aria-expanded="false" style="{{ $menuStyle }} cursor: pointer;"
                   onmouseover="this.style.background='rgba(255,255,255,0.08)';" onmouseout="this.style.background='transparent';">
                    <div style="display:flex; align-items:center; gap:12px; flex:1;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2"><circle cx="12" cy="12" r="8"></circle><path d="M12 6v6l3-3m-3 3l-3-3"></path></svg>
                        <span style="{{ $spanStyle }}">Deposit Charges</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.4)" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
                <ul class="collapse" id="menu_deposit" style="{{ $submenuStyle }}">
                    @can('view_naira_deposit_charges')
                    <li><a href="{{ route('admin.deposit.fee.index', 'NGN') }}" style="{{ $submenuLinkStyle }}"
                           onmouseover="this.style.color='#fff';" onmouseout="this.style.color='rgba(255,255,255,0.65)';">Naira Charges</a></li>
                    @endcan
                    @can('view_usd_deposit_charges')
                    <li><a href="{{ route('admin.deposit.fee.index', 'USD') }}" style="{{ $submenuLinkStyle }}"
                           onmouseover="this.style.color='#fff';" onmouseout="this.style.color='rgba(255,255,255,0.65)';">USD Charges</a></li>
                    @endcan
                    @can('view_eur_deposit_charges')
                    <li><a href="{{ route('admin.deposit.fee.index', 'EUR') }}" style="{{ $submenuLinkStyle }}"
                           onmouseover="this.style.color='#fff';" onmouseout="this.style.color='rgba(255,255,255,0.65)';">Euro Charges</a></li>
                    @endcan
                </ul>
            </li>
            @endcanany

            @canany(['view_naira_virtual_card', 'view_dollar_virtual_card'])
            <li style="margin: 2px 0;">
                <a href="#menu_vcards" data-bs-toggle="collapse" aria-expanded="false" style="{{ $menuStyle }} cursor: pointer;"
                   onmouseover="this.style.background='rgba(255,255,255,0.08)';" onmouseout="this.style.background='transparent';">
                    <div style="display:flex; align-items:center; gap:12px; flex:1;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                        <span style="{{ $spanStyle }}">Virtual Cards</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.4)" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
                <ul class="collapse" id="menu_vcards" style="{{ $submenuStyle }}">
                    @can('view_naira_virtual_card')
                    <li><a href="{{ url('/adminoffice/virtual-ngn-cards') }}" style="{{ $submenuLinkStyle }}"
                           onmouseover="this.style.color='#fff';" onmouseout="this.style.color='rgba(255,255,255,0.65)';">NGN Cards</a></li>
                    @endcan
                    @can('view_dollar_virtual_card')
                    <li><a href="{{ url('/adminoffice/virtual-usd-cards') }}" style="{{ $submenuLinkStyle }}"
                           onmouseover="this.style.color='#fff';" onmouseout="this.style.color='rgba(255,255,255,0.65)';">USD Cards</a></li>
                    @endcan
                </ul>
            </li>
            @endcanany

            @canany(['view_physical_cards', 'view_assigned_cards'])
            <li style="margin: 2px 0;">
                <a href="#menu_pcards" data-bs-toggle="collapse" aria-expanded="false" style="{{ $menuStyle }} cursor: pointer;"
                   onmouseover="this.style.background='rgba(255,255,255,0.08)';" onmouseout="this.style.background='transparent';">
                    <div style="display:flex; align-items:center; gap:12px; flex:1;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                        <span style="{{ $spanStyle }}">Physical Cards</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.4)" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
                <ul class="collapse" id="menu_pcards" style="{{ $submenuStyle }}">
                    @can('view_physical_cards')
                    <li><a href="{{ url('/adminoffice/physical_cards') }}" style="{{ $submenuLinkStyle }}"
                           onmouseover="this.style.color='#fff';" onmouseout="this.style.color='rgba(255,255,255,0.65)';">View Cards</a></li>
                    @endcan
                    @can('view_assigned_cards')
                    <li><a href="{{ url('/adminoffice/card_credentials') }}" style="{{ $submenuLinkStyle }}"
                           onmouseover="this.style.color='#fff';" onmouseout="this.style.color='rgba(255,255,255,0.65)';">Assign ATM Cards</a></li>
                    @endcan
                </ul>
            </li>
            @endcanany

            @canany(['view_foreign_account_requests', 'view_dollar_virtual_accounts', 'view_euro_virtual_accounts'])
            <li style="margin: 2px 0;">
                <a href="#menu_foreign" data-bs-toggle="collapse" aria-expanded="false" style="{{ $menuStyle }} cursor: pointer;"
                   onmouseover="this.style.background='rgba(255,255,255,0.08)';" onmouseout="this.style.background='transparent';">
                    <div style="display:flex; align-items:center; gap:12px; flex:1;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                        <span style="{{ $spanStyle }}">Foreign Accounts</span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.4)" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
                <ul class="collapse" id="menu_foreign" style="{{ $submenuStyle }}">
                    @can('view_dollar_virtual_accounts')
                    <li><a href="{{ url('/adminoffice/foreign-accounts', ['currency' => 'USD']) }}" style="{{ $submenuLinkStyle }}"
                           onmouseover="this.style.color='#fff';" onmouseout="this.style.color='rgba(255,255,255,0.65)';">Foreign Accounts (USD)</a></li>
                    @endcan
                    @can('view_euro_virtual_accounts')
                    <li><a href="{{ url('/adminoffice/foreign-accounts', ['currency' => 'EUR']) }}" style="{{ $submenuLinkStyle }}"
                           onmouseover="this.style.color='#fff';" onmouseout="this.style.color='rgba(255,255,255,0.65)';">Foreign Accounts (EUR)</a></li>
                    @endcan
                    @can('view_foreign_account_requests')
                    <li><a href="{{ url('/adminoffice/foreign-accounts/request') }}" style="{{ $submenuLinkStyle }}"
                           onmouseover="this.style.color='#fff';" onmouseout="this.style.color='rgba(255,255,255,0.65)';">Account Requests</a></li>
                    @endcan
                </ul>
            </li>
            @endcanany

            @can('view_recharge_card_epin_rates')
            <li style="margin: 2px 0;">
                <a href="{{ url('/adminoffice/epin_rates') }}" style="{{ $menuStyle }}"
                   onmouseover="this.style.background='rgba(255,255,255,0.08)'; this.style.borderLeftColor='#4f8ef7';"
                   onmouseout="this.style.background='transparent'; this.style.borderLeftColor='transparent';">
                    <div style="display:flex; align-items:center; gap:12px; flex:1;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2"><path d="M4 11a9 9 0 0 1 9 9"></path><path d="M4 4a16 16 0 0 1 16 16"></path><circle cx="5" cy="19" r="1"></circle></svg>
                        <span style="{{ $spanStyle }}">Recharge Epin Rates</span>
                    </div>
                </a>
            </li>
            @endcan

            {{-- 2FA Security --}}
            <li style="margin: 2px 0;">
                <a href="{{ route('admin.security.2fa') }}" style="{{ $menuStyle }}"
                   onmouseover="this.style.background='rgba(255,255,255,0.08)'; this.style.borderLeftColor='#4f8ef7';"
                   onmouseout="this.style.background='transparent'; this.style.borderLeftColor='transparent';">
                    <div style="display:flex; align-items:center; gap:12px; flex:1;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        <span style="{{ $spanStyle }}">2FA Security</span>
                    </div>
                </a>
            </li>

            {{-- Logout --}}
            <li style="margin: 20px 0 8px 0; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 12px;">
                <a href="{{ route('AdminLogout') }}" style="{{ $menuStyle }} color: #ff6b6b;"
                   onmouseover="this.style.background='rgba(255,100,100,0.1)';"
                   onmouseout="this.style.background='transparent';">
                    <div style="display:flex; align-items:center; gap:12px; flex:1;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ff6b6b" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                        <span style="color: #ff6b6b; font-size: 14px;">Logout</span>
                    </div>
                </a>
            </li>

        </ul>
    </nav>
</div>
<!--  END SIDEBAR  -->
