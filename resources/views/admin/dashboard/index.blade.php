@extends('admin.layouts.app')
@section('title', 'Dashboard')
@section('style')

<link href="{{ asset('assets/admin/src/plugins/src/apex/apexcharts.min.css') }}" rel="stylesheet" type="text/css">

<style>
:root {
    --bg: #f5f6fa;
    --surface: #ffffff;
    --surface-2: #f9fafb;
    --border: #e8eaed;
    --text-primary: #111827;
    --text-secondary: #6b7280;
    --text-muted: #9ca3af;
    --accent: #111827;
    --green: #059669;
    --green-bg: #ecfdf5;
    --red: #dc2626;
    --red-bg: #fef2f2;
    --blue: #2563eb;
    --blue-bg: #eff6ff;
    --amber: #d97706;
    --amber-bg: #fffbeb;
    --purple: #7c3aed;
    --purple-bg: #f5f3ff;
    --radius: 14px;
    --radius-sm: 8px;
    --shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
    --shadow-md: 0 4px 16px rgba(0,0,0,0.08);
}

* { box-sizing: border-box; }

/* ── Page ─────────────────────────────────────────────────────── */
.db-page {
    padding: 28px;
    background: var(--bg);
    min-height: 100vh;
}

/* ── Breadcrumb ───────────────────────────────────────────────── */
.db-breadcrumb {
    font-size: 12px;
    color: var(--text-muted);
    margin-bottom: 24px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* ── Header ───────────────────────────────────────────────────── */
.db-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 28px;
    flex-wrap: wrap;
    gap: 12px;
}
.db-header-title { font-size: 22px; font-weight: 700; color: var(--text-primary); margin: 0; }
.db-header-sub { font-size: 13px; color: var(--text-muted); margin-top: 2px; }
.db-date-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 6px 14px;
    font-size: 12px; font-weight: 600;
    color: var(--text-secondary);
}

/* ── Stat Cards Grid ──────────────────────────────────────────── */
.db-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
@media(max-width:1200px) { .db-stats-grid { grid-template-columns: repeat(2,1fr); } }
@media(max-width:600px)  { .db-stats-grid { grid-template-columns: 1fr; } }

/* ── Stat Card ────────────────────────────────────────────────── */
.db-stat {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 22px;
    box-shadow: var(--shadow);
    position: relative;
    overflow: hidden;
    transition: box-shadow 0.2s, transform 0.2s;
}
.db-stat:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
.db-stat::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--accent);
    border-radius: var(--radius) var(--radius) 0 0;
}
.db-stat.green::after  { background: var(--green); }
.db-stat.blue::after   { background: var(--blue); }
.db-stat.purple::after { background: var(--purple); }
.db-stat.amber::after  { background: var(--amber); }
.db-stat.red::after    { background: var(--red); }

.db-stat-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 14px;
    background: var(--surface-2);
    border: 1px solid var(--border);
    color: var(--text-secondary);
}
.db-stat.green .db-stat-icon  { background: var(--green-bg);  color: var(--green);  border-color: #a7f3d0; }
.db-stat.blue .db-stat-icon   { background: var(--blue-bg);   color: var(--blue);   border-color: #bfdbfe; }
.db-stat.purple .db-stat-icon { background: var(--purple-bg); color: var(--purple); border-color: #ddd6fe; }
.db-stat.amber .db-stat-icon  { background: var(--amber-bg);  color: var(--amber);  border-color: #fde68a; }
.db-stat.red .db-stat-icon    { background: var(--red-bg);    color: var(--red);    border-color: #fecaca; }

.db-stat-label { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
.db-stat-value { font-size: 26px; font-weight: 700; color: var(--text-primary); line-height: 1.2; }
.db-stat-sub { font-size: 12px; color: var(--text-muted); margin-top: 8px; }

/* ── Section header ───────────────────────────────────────────── */
.db-section-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
@media(max-width:900px) { .db-section-grid { grid-template-columns: 1fr 1fr; } }
@media(max-width:600px) { .db-section-grid { grid-template-columns: 1fr; } }

.db-section-grid-2 {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 16px;
    margin-bottom: 24px;
}
@media(max-width:900px) { .db-section-grid-2 { grid-template-columns: 1fr; } }

/* ── Card ─────────────────────────────────────────────────────── */
.db-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
}
.db-card-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
}
.db-card-title {
    font-size: 14px; font-weight: 700; color: var(--text-primary);
    display: flex; align-items: center; gap: 8px;
}
.db-card-body { padding: 20px; }

/* ── Mini stat rows (today/yesterday) ────────────────────────── */
.db-mini-stats { display: flex; flex-direction: column; gap: 12px; }
.db-mini-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 16px;
    background: var(--surface-2);
    border-radius: var(--radius-sm);
    border: 1px solid var(--border);
}
.db-mini-row .label { font-size: 12px; color: var(--text-muted); font-weight: 600; }
.db-mini-row .value { font-size: 15px; font-weight: 700; color: var(--text-primary); }
.db-mini-row .dot {
    width: 8px; height: 8px; border-radius: 50%;
    margin-right: 8px; display: inline-block;
}
.db-mini-row .dot.today { background: var(--green); }
.db-mini-row .dot.yesterday { background: var(--border); }

/* ── Table ────────────────────────────────────────────────────── */
.db-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.db-table thead tr { border-bottom: 1px solid var(--border); }
.db-table thead th { padding: 10px 16px; text-align: left; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
.db-table tbody tr { border-bottom: 1px solid var(--border); transition: background 0.15s; }
.db-table tbody tr:last-child { border-bottom: none; }
.db-table tbody tr:hover { background: var(--surface-2); }
.db-table tbody td { padding: 12px 16px; color: var(--text-primary); vertical-align: middle; }
.db-table .user-cell { display: flex; align-items: center; gap: 10px; }
.db-table .user-cell img { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid var(--border); }
.db-table .user-cell .name { font-weight: 600; font-size: 13px; }

/* ── Browser list ─────────────────────────────────────────────── */
.db-browser-list { display: flex; flex-direction: column; gap: 14px; }
.db-browser-row { display: flex; align-items: center; gap: 12px; }
.db-browser-icon { width: 32px; height: 32px; border-radius: 8px; background: var(--surface-2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: var(--text-secondary); }
.db-browser-info { flex: 1; }
.db-browser-name { font-size: 13px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px; display: flex; justify-content: space-between; }
.db-browser-bar { height: 4px; background: var(--border); border-radius: 4px; overflow: hidden; }
.db-browser-bar-fill { height: 100%; background: var(--accent); border-radius: 4px; transition: width 0.6s ease; }

/* ── Full width card ──────────────────────────────────────────── */
.db-full-card { margin-bottom: 24px; }
</style>

@endsection
@section('content')
<div class="db-page">

    <div class="db-breadcrumb">Dashboard</div>

    <div class="db-header">
        <div>
            <h1 class="db-header-title">Overview</h1>
            <p class="db-header-sub">Welcome back, {{ Auth::guard('admin')->user()->username }}</p>
        </div>
        <div class="db-date-badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            {{ now()->format('d M Y') }}
        </div>
    </div>

    {{-- ── Top Stat Cards ────────────────────────────────────────────── --}}
    <div class="db-stats-grid">

        @can('view_user_stats')
        <div class="db-stat green">
            <div class="db-stat-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="db-stat-label">Total Users</div>
            <div class="db-stat-value">{{ number_format($totalUser) }}</div>
            <div class="db-stat-sub">Registered accounts</div>
        </div>
        @endcan

        @can('view_balance_stats')
        <div class="db-stat blue">
            <div class="db-stat-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div class="db-stat-label">Total NGN Balance</div>
            <div class="db-stat-value">{{ $totalBalance }}</div>
            <div class="db-stat-sub">Across all wallets</div>
        </div>
        @endcan

        @can('view_total_usd_users')
        <div class="db-stat purple">
            <div class="db-stat-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="db-stat-label">USD Users</div>
            <div class="db-stat-value">{{ number_format($totalUsdForeignUsers) }}</div>
            <div class="db-stat-sub">Foreign account holders</div>
        </div>
        @endcan

        @can('view_usd_balance')
        <div class="db-stat amber">
            <div class="db-stat-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><line x1="21.17" y1="8" x2="12" y2="8"/><line x1="3.95" y1="6.06" x2="8.54" y2="14"/><line x1="10.88" y1="21.94" x2="15.46" y2="14"/></svg>
            </div>
            <div class="db-stat-label">USD Balance</div>
            <div class="db-stat-value">${{ number_format($totalUSDBalance, 2) }}</div>
            <div class="db-stat-sub">Total USD held</div>
        </div>
        @endcan

        @can('view_total_eur_users')
        <div class="db-stat purple">
            <div class="db-stat-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            </div>
            <div class="db-stat-label">EUR Users</div>
            <div class="db-stat-value">{{ number_format($totalEurForeignUsers) }}</div>
            <div class="db-stat-sub">Foreign account holders</div>
        </div>
        @endcan

        @can('view_eur_balance')
        <div class="db-stat red">
            <div class="db-stat-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div class="db-stat-label">EUR Balance</div>
            <div class="db-stat-value">€{{ number_format($totalEURBalance, 2) }}</div>
            <div class="db-stat-sub">Total EUR held</div>
        </div>
        @endcan

    </div>

    {{-- ── Statistics Rows ───────────────────────────────────────────── --}}
    @canany(['view_daily_user_stats','view_transaction_stats','view_deposit_stats'])
    <div class="db-section-grid">

        @can('view_daily_user_stats')
        <div class="db-card">
            <div class="db-card-header">
                <div class="db-card-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    User Statistics
                </div>
            </div>
            <div class="db-card-body">
                <div class="db-mini-stats">
                    <div class="db-mini-row">
                        <span class="label"><span class="dot today"></span>Today</span>
                        <span class="value">{{ $dailyUserStats }}</span>
                    </div>
                    <div class="db-mini-row">
                        <span class="label"><span class="dot yesterday"></span>Yesterday</span>
                        <span class="value">{{ $yesterdayUserStats }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endcan

        @can('view_transaction_stats')
        <div class="db-card">
            <div class="db-card-header">
                <div class="db-card-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
                    NGN Transactions
                </div>
            </div>
            <div class="db-card-body">
                <div class="db-mini-stats">
                    <div class="db-mini-row">
                        <span class="label"><span class="dot today"></span>Today</span>
                        <span class="value">{{ getPrice(App\Models\Transaction::whereDate('created_at',$currentDate)->where('type','0')->where('status','success')->where('currency','NGN')->sum('amount')) }}</span>
                    </div>
                    <div class="db-mini-row">
                        <span class="label"><span class="dot yesterday"></span>Yesterday</span>
                        <span class="value">{{ getPrice(App\Models\Transaction::whereDate('created_at',$yesterdayDate)->where('type','0')->where('status','success')->where('currency','NGN')->sum('amount')) }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endcan

        @can('view_deposit_stats')
        <div class="db-card">
            <div class="db-card-header">
                <div class="db-card-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
                    NGN Deposits
                </div>
            </div>
            <div class="db-card-body">
                <div class="db-mini-stats">
                    <div class="db-mini-row">
                        <span class="label"><span class="dot today"></span>Today</span>
                        <span class="value">{{ getPrice(App\Models\Transaction::whereService_type('Wallet Funding')->whereDate('created_at',$currentDate)->where('currency','NGN')->sum('amount')) }}</span>
                    </div>
                    <div class="db-mini-row">
                        <span class="label"><span class="dot yesterday"></span>Yesterday</span>
                        <span class="value">{{ getPrice(App\Models\Transaction::whereService_type('Wallet Funding')->whereDate('created_at',$yesterdayDate)->where('currency','NGN')->sum('amount')) }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endcan

    </div>
    @endcanany

    {{-- USD Stats --}}
    @canany(['view_usd_stats','view_usd_transaction_stats','view_usd_deposit_stats'])
    <div class="db-section-grid">

        @can('view_usd_stats')
        <div class="db-card">
            <div class="db-card-header">
                <div class="db-card-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    USD Users
                </div>
            </div>
            <div class="db-card-body">
                <div class="db-mini-stats">
                    <div class="db-mini-row">
                        <span class="label"><span class="dot today"></span>Today</span>
                        <span class="value">{{ $todayUsdForeignUsers }}</span>
                    </div>
                    <div class="db-mini-row">
                        <span class="label"><span class="dot yesterday"></span>Yesterday</span>
                        <span class="value">{{ $yesterdayUsdForeignUsers }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endcan

        @can('view_usd_transaction_stats')
        <div class="db-card">
            <div class="db-card-header">
                <div class="db-card-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
                    USD Transactions
                </div>
            </div>
            <div class="db-card-body">
                <div class="db-mini-stats">
                    <div class="db-mini-row">
                        <span class="label"><span class="dot today"></span>Today</span>
                        <span class="value">${{ number_format(App\Models\Transaction::whereDate('created_at',$currentDate)->where('type','0')->where('status','success')->where('currency','USD')->sum('amount'),2) }}</span>
                    </div>
                    <div class="db-mini-row">
                        <span class="label"><span class="dot yesterday"></span>Yesterday</span>
                        <span class="value">${{ number_format(App\Models\Transaction::whereDate('created_at',$yesterdayDate)->where('type','0')->where('status','success')->where('currency','USD')->sum('amount'),2) }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endcan

        @can('view_usd_deposit_stats')
        <div class="db-card">
            <div class="db-card-header">
                <div class="db-card-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
                    USD Deposits
                </div>
            </div>
            <div class="db-card-body">
                <div class="db-mini-stats">
                    <div class="db-mini-row">
                        <span class="label"><span class="dot today"></span>Today</span>
                        <span class="value">${{ number_format(App\Models\Transaction::whereService_type('Wallet Funding')->whereDate('created_at',$currentDate)->where('currency','USD')->sum('amount'),2) }}</span>
                    </div>
                    <div class="db-mini-row">
                        <span class="label"><span class="dot yesterday"></span>Yesterday</span>
                        <span class="value">${{ number_format(App\Models\Transaction::whereService_type('Wallet Funding')->whereDate('created_at',$yesterdayDate)->where('currency','USD')->sum('amount'),2) }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endcan

    </div>
    @endcanany

    {{-- EUR Stats --}}
    @canany(['view_eur_stats','view_eur_transaction_stats','view_eur_deposit_stats'])
    <div class="db-section-grid">

        @can('view_eur_stats')
        <div class="db-card">
            <div class="db-card-header">
                <div class="db-card-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    EUR Users
                </div>
            </div>
            <div class="db-card-body">
                <div class="db-mini-stats">
                    <div class="db-mini-row">
                        <span class="label"><span class="dot today"></span>Today</span>
                        <span class="value">{{ $todayEurForeignUsers }}</span>
                    </div>
                    <div class="db-mini-row">
                        <span class="label"><span class="dot yesterday"></span>Yesterday</span>
                        <span class="value">{{ $yesterdayEurForeignUsers }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endcan

        @can('view_eur_transaction_stats')
        <div class="db-card">
            <div class="db-card-header">
                <div class="db-card-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
                    EUR Transactions
                </div>
            </div>
            <div class="db-card-body">
                <div class="db-mini-stats">
                    <div class="db-mini-row">
                        <span class="label"><span class="dot today"></span>Today</span>
                        <span class="value">€{{ number_format(App\Models\Transaction::whereDate('created_at',$currentDate)->where('type','0')->where('status','success')->where('currency','EUR')->sum('amount'),2) }}</span>
                    </div>
                    <div class="db-mini-row">
                        <span class="label"><span class="dot yesterday"></span>Yesterday</span>
                        <span class="value">€{{ number_format(App\Models\Transaction::whereDate('created_at',$yesterdayDate)->where('type','0')->where('status','success')->where('currency','EUR')->sum('amount'),2) }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endcan

        @can('view_eur_deposit_stats')
        <div class="db-card">
            <div class="db-card-header">
                <div class="db-card-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
                    EUR Deposits
                </div>
            </div>
            <div class="db-card-body">
                <div class="db-mini-stats">
                    <div class="db-mini-row">
                        <span class="label"><span class="dot today"></span>Today</span>
                        <span class="value">€{{ number_format(App\Models\Transaction::whereService_type('Wallet Funding')->whereDate('created_at',$currentDate)->where('currency','EUR')->sum('amount'),2) }}</span>
                    </div>
                    <div class="db-mini-row">
                        <span class="label"><span class="dot yesterday"></span>Yesterday</span>
                        <span class="value">€{{ number_format(App\Models\Transaction::whereService_type('Wallet Funding')->whereDate('created_at',$yesterdayDate)->where('currency','EUR')->sum('amount'),2) }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endcan

    </div>
    @endcanany

    {{-- ── Visitor Chart + Browsers ──────────────────────────────────── --}}
    @can('view_visitor_stats')
    <div class="db-section-grid-2 db-full-card">
        <div class="db-card">
            <div class="db-card-header">
                <div class="db-card-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    Unique Visitors
                </div>
            </div>
            <div class="db-card-body">
                <div id="iSmartUniqueVisits" style="min-height:280px;"></div>
            </div>
        </div>

        <div class="db-card">
            <div class="db-card-header">
                <div class="db-card-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    Browsers
                </div>
            </div>
            <div class="db-card-body">
                <div class="db-browser-list">
                    @if($visitorBrowser['browsers'])
                    @foreach($visitorBrowser['browsers'] as $browser)
                    <div class="db-browser-row">
                        <div class="db-browser-icon">
                            @switch(strtolower($browser->browser))
                                @case('chrome')
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><line x1="21.17" y1="8" x2="12" y2="8"/><line x1="3.95" y1="6.06" x2="8.54" y2="14"/><line x1="10.88" y1="21.94" x2="15.46" y2="14"/></svg>
                                @break
                                @case('safari')
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>
                                @break
                                @default
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10z"/></svg>
                            @endswitch
                        </div>
                        <div class="db-browser-info">
                            <div class="db-browser-name">
                                <span>{{ $browser->browser }}</span>
                                <span style="color:var(--text-muted);font-size:12px;">{{ $browser->percentage }}%</span>
                            </div>
                            <div class="db-browser-bar">
                                <div class="db-browser-bar-fill" style="width:{{ $browser->percentage }}%"></div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endcan

    {{-- ── Recent Customers ──────────────────────────────────────────── --}}
    @can('view_recent_user_stats')
    <div class="db-card db-full-card">
        <div class="db-card-header">
            <div class="db-card-title">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                Recent Customers
            </div>
            <span style="font-size:12px;color:var(--text-muted);">Latest signups</span>
        </div>
        <div style="overflow-x:auto;">
            <table class="db-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Balance</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($newUser as $item)
                    <tr>
                        <td>
                            <div class="user-cell">
                                <img src="{{ $item->profile->getProfileImage() }}" alt="avatar">
                                <span class="name">{{ $item->profile->getName() }}</span>
                            </div>
                        </td>
                        <td style="font-weight:600;">{{ $item->balance }}</td>
                        <td style="color:var(--text-secondary);">{{ $item->email }}</td>
                        <td style="color:var(--text-secondary);">{{ $item->phone_number }}</td>
                        <td style="color:var(--text-muted);">{{ date('d M Y', strtotime($item->created_at)) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endcan

    <div class="db-card db-full-card" style="margin-top: 18px;">
        <div class="db-card-header">
            <div class="db-card-title">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l8 4v6c0 5-3.5 9-8 10-4.5-1-8-5-8-10V6l8-4z"></path><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
                Security Overview
            </div>
            <a href="{{ route('admin.security-alerts.index') }}" style="font-size:12px;color:var(--text-muted);">View logs &rarr;</a>
        </div>
        <div style="display:flex;gap:12px;flex-wrap:wrap;padding:18px;">
            <div class="db-stat-sub"><strong>{{ number_format($openSecurityAlerts) }}</strong> open alerts</div>
            <div class="db-stat-sub"><strong>{{ number_format($todayAdminActivity) }}</strong> admin actions today</div>
            @if($openSecurityAlerts > 0)
                <div class="db-stat-sub" style="color:#f59e0b;"><strong>Attention required:</strong> review unacknowledged alerts.</div>
            @endif
        </div>
    </div>

</div>
@endsection

@section('scripts')
@can('view_visitor_stats')
<script>
function visitorChartList(url) {
    $.ajax({
        url: url,
        type: 'GET',
        data: { type: 'daily' },
        success: function(response) {
            if (!response.success) return;
            var options = {
                chart: { height: 280, type: 'bar', toolbar: { show: false }, background: 'transparent' },
                colors: ['#111827', '#e5e7eb'],
                plotOptions: { bar: { horizontal: false, columnWidth: '45%', borderRadius: 6 } },
                dataLabels: { enabled: false },
                grid: { borderColor: '#f0f1f3', strokeDashArray: 4 },
                stroke: { show: true, width: 2, colors: ['transparent'] },
                series: response.data.series,
                xaxis: { ...response.data.xaxis, labels: { style: { fontSize: '11px', colors: '#9ca3af' } } },
                yaxis: { labels: { style: { fontSize: '11px', colors: '#9ca3af' } } },
                legend: { position: 'bottom', fontSize: '12px', markers: { width: 8, height: 8 } },
                fill: { opacity: 1 },
                tooltip: { y: { formatter: function(val) { return val; } } }
            };
            new ApexCharts(document.querySelector('#iSmartUniqueVisits'), options).render();
        }
    });
}
visitorChartList("{{ route('visitorChart') }}");
</script>
@endcan
@endsection
