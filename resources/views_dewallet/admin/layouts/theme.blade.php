{{--
    Dupay Admin — DeWallet dark theme
    Global override layer. Restyles Cork/Bootstrap component classes so every
    existing page (lists, forms, tables, modals) inherits the new look without
    touching module blades. To recolor the whole panel, change --accent below.
--}}
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#070A12; --panel:#0D1320; --panel-2:#121A28; --panel-3:#18202F;
  --line:rgba(255,255,255,0.07); --line-strong:rgba(255,255,255,0.13);
  --accent:#00E5A0; --accent-dim:#00b07d; --accent-soft:rgba(0,229,160,0.10);
  --gold:#F5C451; --violet:#8B7BFF; --blue:#4D8DFF;
  --text:#EAF0F8; --muted:#8593AB; --faint:#56627A;
  --up:#2BD98F; --down:#FF6B81; --warn:#F5C451;
  --radius:14px; --radius-sm:9px;
}

/* ── Base ─────────────────────────────────────────────────────── */
html,body{background:var(--bg)!important;color:var(--text)!important;}
body,body.layout-boxed{
  font-family:'Inter',system-ui,sans-serif!important;
  font-size:14px!important; overflow-x:hidden!important;
}
*{scrollbar-width:thin;scrollbar-color:var(--line-strong) transparent;}
::-webkit-scrollbar{width:9px;height:9px;}
::-webkit-scrollbar-track{background:transparent;}
::-webkit-scrollbar-thumb{background:var(--line-strong);border-radius:6px;}
::-webkit-scrollbar-thumb:hover{background:var(--faint);}
::selection{background:var(--accent);color:#04130d;}
a{color:var(--accent);}
h1,h2,h3,h4,h5,h6{font-family:'Space Grotesk','Inter',sans-serif!important;color:var(--text)!important;letter-spacing:-.3px;}
p{color:var(--muted);}
hr{border-color:var(--line);}

/* Loader */
#load_screen{background:var(--bg)!important;}
.spinner-grow{color:var(--accent)!important;}

/* ── Layout scaffolding (clean, no PerfectScrollbar) ──────────── */
.main-container,#container{background:var(--bg)!important;display:block!important;}
#content,.main-content{
  background:var(--bg)!important;
  margin-left:250px!important;
  min-height:100vh!important;
  padding-top:62px!important;   /* under fixed topbar */
}
@media(max-width:991px){ #content,.main-content{margin-left:0!important;} }
.layout-px-spacing{padding:0 24px!important;min-height:auto!important;}
.layout-top-spacing{margin-top:24px!important;}
.layout-spacing{margin-bottom:20px!important;}
.middle-content{max-width:100%!important;}

/* ── Topbar / header ──────────────────────────────────────────── */
.header-container{
  position:fixed!important;top:0;right:0;left:250px;z-index:900;
  background:rgba(7,10,18,.82)!important;backdrop-filter:blur(14px);
  border-bottom:1px solid var(--line)!important;height:62px!important;
  max-width:none!important;padding:0 24px!important;
}
@media(max-width:991px){ .header-container{left:0;} }
.header.navbar{min-height:62px!important;background:transparent!important;box-shadow:none!important;padding:0!important;border:none!important;}
.navbar .sidebarCollapse{color:var(--text)!important;cursor:pointer;display:inline-flex;align-items:center;}
.navbar-item .nav-link{color:var(--text)!important;}
.navbar .nav-link svg{color:var(--text)!important;}
.user-profile-dropdown .avatar img{border:1px solid var(--line-strong)!important;}
.header .dropdown-menu{
  background:var(--panel-2)!important;border:1px solid var(--line)!important;
  border-radius:12px!important;box-shadow:0 18px 44px -12px rgba(0,0,0,.7)!important;color:var(--text)!important;
}
.header .dropdown-menu h5,.header .dropdown-menu p,.header .dropdown-item a{color:var(--text)!important;}
.header .dropdown-menu p{color:var(--muted)!important;}
.header .dropdown-item:hover{background:var(--panel-3)!important;}
.avatar-online::before,.avatar-indicators::before{background:var(--up)!important;border-color:var(--panel-2)!important;}

/* ── Cards / widgets / boxes ─────────────────────────────────── */
.widget,.statbox,.box,.card,.widget-content-area,.searchable-container .widget-content{
  background:var(--panel)!important;
  border:1px solid var(--line)!important;
  border-radius:var(--radius)!important;
  box-shadow:none!important;
  color:var(--text)!important;
}
.card{overflow:hidden;}
.widget-header,.card-header{
  background:transparent!important;
  border-bottom:1px solid var(--line)!important;
  padding:16px 20px!important;
}
.widget-header h4,.card-title,.widget-heading h5,.widget-heading,.card-header *{
  color:var(--text)!important;font-family:'Space Grotesk',sans-serif!important;font-weight:600!important;
}
.widget-content,.card-body,.widget-content-area{color:var(--text)!important;padding:20px!important;}
.seperator-header{border:none!important;padding:0 0 16px 0!important;}

/* ── Breadcrumb ───────────────────────────────────────────────── */
.page-meta{margin-bottom:4px!important;}
.breadcrumb{background:transparent!important;padding:0!important;margin:0!important;}
.breadcrumb-item,.breadcrumb-item a{
  color:var(--faint)!important;font-family:'JetBrains Mono',monospace!important;
  font-size:11px!important;text-transform:uppercase;letter-spacing:.08em;
}
.breadcrumb-item a{color:var(--muted)!important;}
.breadcrumb-item a:hover{color:var(--accent)!important;}
.breadcrumb-item.active{color:var(--text)!important;}
.breadcrumb-item+.breadcrumb-item::before{color:var(--faint)!important;}

/* ── Buttons ──────────────────────────────────────────────────── */
.btn{
  font-family:'Inter',sans-serif!important;font-weight:600!important;
  border-radius:10px!important;box-shadow:none!important;transition:.18s!important;
  display:inline-flex;align-items:center;gap:7px;
}
.btn-sm{padding:7px 14px!important;font-size:12.5px!important;}
.btn-primary,.btn-secondary{
  background:var(--accent)!important;border:1px solid var(--accent)!important;color:#04130d!important;
}
.btn-primary:hover,.btn-secondary:hover{background:#19f0af!important;border-color:#19f0af!important;color:#04130d!important;}
.btn-success{background:var(--up)!important;border-color:var(--up)!important;color:#04130d!important;}
.btn-info{background:var(--blue)!important;border-color:var(--blue)!important;color:#fff!important;}
.btn-danger{background:var(--down)!important;border-color:var(--down)!important;color:#2a0810!important;}
.btn-warning{background:var(--gold)!important;border-color:var(--gold)!important;color:#2a2205!important;}
.btn-outline-primary,.btn-outline-secondary{
  background:var(--panel-2)!important;border:1px solid var(--line-strong)!important;color:var(--text)!important;
}
.btn-outline-primary:hover,.btn-outline-secondary:hover{border-color:var(--accent)!important;color:var(--accent)!important;}

/* ── Badges ───────────────────────────────────────────────────── */
.badge{
  font-family:'JetBrains Mono',monospace!important;font-weight:600!important;
  border-radius:7px!important;padding:4px 9px!important;font-size:11px!important;
}
.badge-success,.badge-primary{background:var(--accent-soft)!important;color:var(--accent)!important;}
.badge-danger{background:rgba(255,107,129,.13)!important;color:var(--down)!important;}
.badge-warning{background:rgba(245,196,81,.13)!important;color:var(--warn)!important;}
.badge-info{background:rgba(77,141,255,.13)!important;color:var(--blue)!important;}
.badge-secondary,.badge-dark{background:var(--panel-3)!important;color:var(--muted)!important;}
.outline-badge-success{border:1px solid var(--accent)!important;color:var(--accent)!important;background:transparent!important;}
.outline-badge-danger{border:1px solid var(--down)!important;color:var(--down)!important;background:transparent!important;}
.outline-badge-primary{border:1px solid var(--accent)!important;color:var(--accent)!important;background:transparent!important;}
.outline-badge-warning{border:1px solid var(--warn)!important;color:var(--warn)!important;background:transparent!important;}
.outline-badge-info{border:1px solid var(--blue)!important;color:var(--blue)!important;background:transparent!important;}
/* Status label classes used by the status system */
.badge-light-success,.text-success{background:var(--accent-soft)!important;color:var(--accent)!important;}
.badge-light-danger,.text-danger{background:rgba(255,107,129,.13)!important;color:var(--down)!important;}
.badge-light-warning{background:rgba(245,196,81,.13)!important;color:var(--warn)!important;}

/* ── Forms ────────────────────────────────────────────────────── */
label,.label,.form-label{color:var(--muted)!important;font-size:12.5px!important;font-weight:500!important;margin-bottom:6px!important;}
.form-control,.form-select,textarea.form-control,select.form-control,input.form-control{
  background:var(--bg)!important;
  border:1px solid var(--line)!important;
  border-radius:10px!important;
  color:var(--text)!important;
  font-size:13.5px!important;
  padding:11px 14px!important;
}
.form-control::placeholder{color:var(--faint)!important;}
.form-control:focus,.form-select:focus{
  background:var(--bg)!important;border-color:var(--accent)!important;
  box-shadow:0 0 0 3px var(--accent-soft)!important;color:var(--text)!important;
}
.form-select{background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%238593AB'%3e%3cpath d='M8 11L3 6h10z'/%3e%3c/svg%3e")!important;background-repeat:no-repeat!important;background-position:right 12px center!important;background-size:12px 12px!important;padding-right:34px!important;appearance:none!important;-webkit-appearance:none!important;-moz-appearance:none!important;}
/* select2 */
.select2-container--default .select2-selection--single,
.select2-container--default .select2-selection--multiple{
  background:var(--bg)!important;border:1px solid var(--line)!important;border-radius:10px!important;min-height:44px!important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered{color:var(--text)!important;line-height:42px!important;}
.select2-container--default .select2-selection--single .select2-selection__arrow{top:9px!important;}
.select2-dropdown{background:var(--panel-2)!important;border:1px solid var(--line)!important;color:var(--text)!important;}
.select2-container--default .select2-results__option--highlighted[aria-selected]{background:var(--accent)!important;color:#04130d!important;}
.select2-search__field{background:var(--bg)!important;color:var(--text)!important;border:1px solid var(--line)!important;}
.select2-selection__choice{background:var(--panel-3)!important;border:1px solid var(--line)!important;color:var(--text)!important;}

/* Segmented verify/activate toggles (Cork inner-label style, dark) */
/* verify/activate toggles: inline, no overlap, switch stays clickable */
.form-switch-custom.inner-label-toggle{background:transparent!important;box-shadow:none!important;border:none!important;padding:0!important;display:inline-block!important;}
.form-switch-custom .input-checkbox{display:inline-flex!important;align-items:center!important;gap:12px!important;flex-wrap:nowrap!important;background:transparent!important;padding:0!important;border:none!important;position:static!important;width:auto!important;}
.form-switch-custom .switch-chk-label{position:static!important;background:transparent!important;color:var(--muted)!important;font-weight:600!important;font-size:13px!important;white-space:nowrap!important;padding:0!important;margin:0!important;width:auto!important;min-width:0!important;max-width:none!important;flex:0 0 auto!important;pointer-events:none!important;box-shadow:none!important;border-radius:0!important;}
.inner-label-toggle.show .switch-chk-label.label-right{color:var(--accent)!important;}
.inner-label-toggle:not(.show) .switch-chk-label.label-left{color:var(--down)!important;}
/* the switch is the only clickable part */
.form-switch-custom .input-checkbox .switch-input{pointer-events:auto!important;z-index:2!important;}

/* ── Tables + DataTables ──────────────────────────────────────── */
.table{color:var(--text)!important;border-color:var(--line)!important;}
.table thead th,.table > thead > tr > th{
  background:transparent!important;color:var(--faint)!important;
  font-family:'JetBrains Mono',monospace!important;font-size:10.5px!important;
  text-transform:uppercase;letter-spacing:.08em;font-weight:500!important;
  border-bottom:1px solid var(--line)!important;padding:12px 14px!important;
}
.table tbody td,.table > tbody > tr > td{
  color:var(--text)!important;border-top:none!important;
  border-bottom:1px solid var(--line)!important;padding:13px 14px!important;font-size:13px!important;
  background:transparent!important;
}
.table tbody tr:hover td,.table-hover tbody tr:hover td{background:var(--panel-2)!important;}
.table td a{color:var(--accent)!important;}
.dataTables_wrapper{color:var(--muted)!important;}
.dataTables_wrapper .dataTables_length select,
.dataTables_wrapper .dataTables_filter input{
  background:var(--bg)!important;border:1px solid var(--line)!important;color:var(--text)!important;border-radius:9px!important;padding:7px 12px!important;
}
.dataTables_wrapper .dataTables_info,.dataTables_wrapper label{color:var(--muted)!important;}
.dataTables_wrapper .dataTables_paginate .paginate_button{
  color:var(--muted)!important;border:1px solid var(--line)!important;border-radius:8px!important;background:var(--panel-2)!important;margin:0 3px;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current,
.dataTables_wrapper .dataTables_paginate .paginate_button:hover{
  background:var(--accent)!important;color:#04130d!important;border-color:var(--accent)!important;
}
table.dataTable tbody tr{background:transparent!important;}
.checkbox-column input,input[type=checkbox]{accent-color:var(--accent);}
.avatar img,.d-flex .avatar img{border-radius:9px!important;border:1px solid var(--line)!important;}

/* ── Modals ───────────────────────────────────────────────────── */
.modal-content{
  background:var(--panel)!important;border:1px solid var(--line-strong)!important;
  border-radius:16px!important;box-shadow:0 30px 80px -20px rgba(0,0,0,.8)!important;color:var(--text)!important;
}
.modal-header,.modal-footer{border-color:var(--line)!important;}
.modal-title{color:var(--text)!important;}
.modal-backdrop.show{opacity:.72!important;}
.btn-close{filter:invert(1) grayscale(1) brightness(1.6)!important;opacity:.6!important;}
.btn-close:hover{opacity:1!important;}

/* ── Alerts ───────────────────────────────────────────────────── */
.alert{border-radius:11px!important;border:1px solid var(--line)!important;font-size:13px!important;}
.alert-light-primary,.alert-primary{background:var(--accent-soft)!important;color:var(--accent)!important;border-color:rgba(0,229,160,.25)!important;}
.alert-light-danger,.alert-danger{background:rgba(255,107,129,.10)!important;color:var(--down)!important;border-color:rgba(255,107,129,.25)!important;}
.alert-light-success{background:var(--accent-soft)!important;color:var(--accent)!important;}
.alert strong{color:inherit!important;}

/* ── Auth (login) card ────────────────────────────────────────── */
body.form{background:var(--bg)!important;}
.auth-container{background:var(--bg)!important;min-height:100vh;}
.auth-container .card{background:var(--panel)!important;border:1px solid var(--line)!important;}
.auth-container h2{color:var(--text)!important;}

/* ── Footer ───────────────────────────────────────────────────── */
.footer-wrapper{
  background:transparent!important;border-top:1px solid var(--line)!important;
  color:var(--faint)!important;margin-top:32px!important;padding:20px 24px!important;
}
.footer-wrapper p{color:var(--faint)!important;font-size:12px!important;margin:0;}
.footer-wrapper a{color:var(--accent)!important;}

/* SweetAlert dark touch */
.sweet-alert{background:var(--panel-2)!important;color:var(--text)!important;border-radius:16px!important;}
.sweet-alert h2,.sweet-alert p{color:var(--text)!important;}
</style>
<style>
/* ══════════════════════════════════════════════════════════════════
   DeWallet theme — corrective patch (tables, switches, filters)
   Appended last so it wins over earlier rules.
   ══════════════════════════════════════════════════════════════════ */

/* ── Button/badge text always visible (not only on hover) ─────────── */
.btn-primary, .btn-secondary, .btn-success, .btn-danger, .btn-warning { color:#04130d !important; }
.btn-info { color:#ffffff !important; }
.btn-primary *, .btn-secondary *, .btn-success *, .btn-danger *, .btn-warning * { color:inherit !important; }
.btn .btn-text-inner, .btn-text-inner { color:inherit !important; opacity:1 !important; }
.btn-sm { color:inherit !important; }

/* ── Kill white zebra striping; force dark rows via Bootstrap vars ── */
.table, table.dataTable {
  --bs-table-bg:transparent !important;
  --bs-table-color:var(--text) !important;
  --bs-table-striped-bg:transparent !important;
  --bs-table-striped-color:var(--text) !important;
  --bs-table-hover-bg:var(--panel-2) !important;
  --bs-table-hover-color:var(--text) !important;
  --bs-table-active-bg:var(--panel-2) !important;
  --bs-table-border-color:var(--line) !important;
}
table.dataTable tbody tr,
table.dataTable tbody tr.odd,
table.dataTable tbody tr.even,
table.dataTable.stripe tbody tr.odd,
table.dataTable.display tbody tr.odd,
.table-striped > tbody > tr:nth-of-type(odd) > *,
.table-striped > tbody > tr:nth-of-type(even) > * {
  background-color:transparent !important;
  color:var(--text) !important;
  box-shadow:none !important;
  --bs-table-accent-bg:transparent !important;
}
table.dataTable tbody tr:hover,
table.dataTable.hover tbody tr:hover,
.table-hover > tbody > tr:hover > * {
  background-color:var(--panel-2) !important;
  color:var(--text) !important;
}
table.dataTable tbody td, table.dataTable tbody th { color:var(--text) !important; }

/* DataTables controls (Show entries / Search / info / paging) */
.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter,
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_paginate,
.dataTables_wrapper label { color:var(--muted) !important; }
.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select {
  background:var(--bg) !important; color:var(--text) !important;
  border:1px solid var(--line) !important; border-radius:8px !important; padding:6px 10px !important;
}

/* ── Compact iOS toggle — ONLY status switches + verify/activate switches ── */
.switch-input.statusTypeUpdate[type=checkbox],
.form-switch-custom .input-checkbox .switch-input[type=checkbox] {
  -webkit-appearance:none !important; appearance:none !important;
  width:44px !important; min-width:44px !important; max-width:44px !important; height:24px !important;
  border-radius:12px !important; background:var(--panel-3) !important; border:1px solid var(--line) !important;
  position:relative !important; cursor:pointer !important; transition:.2s !important;
  vertical-align:middle !important; margin:0 6px !important; padding:0 !important; flex:0 0 auto !important; display:inline-block !important; box-shadow:none !important;
}
.switch-input.statusTypeUpdate[type=checkbox]::after,
.form-switch-custom .input-checkbox .switch-input[type=checkbox]::after {
  content:'' !important; position:absolute !important; top:2px !important; left:2px !important;
  width:18px !important; height:18px !important; border-radius:50% !important;
  background:#fff !important; transition:.2s !important; box-shadow:0 1px 2px rgba(0,0,0,.4) !important; transform:none !important;
}
.switch-input.statusTypeUpdate[type=checkbox]:checked,
.form-switch-custom .input-checkbox .switch-input[type=checkbox]:checked { background:var(--accent) !important; border-color:var(--accent) !important; }
.switch-input.statusTypeUpdate[type=checkbox]:checked::after,
.form-switch-custom .input-checkbox .switch-input[type=checkbox]:checked::after { transform:translateX(20px) !important; }

/* ── vanillaSelectBox (filters) dark tint ─────────────────────────── */
.vsb-main, .vsb-menu, .vanilla-selectBox, .vsb-main .vsb-menu {
  background:var(--bg) !important; border:1px solid var(--line) !important;
  border-radius:10px !important; color:var(--text) !important;
}
.vsb-main *, .vsb-menu * { color:var(--text) !important; }
.vsb-main button { background:var(--bg) !important; }
.vsb-menu li:hover, .vsb-menu li.active, .vsb-menu li.selected {
  background:var(--accent-soft) !important; color:var(--accent) !important;
}

/* ── Date inputs + input-group separators ─────────────────────────── */
input[type=date] {
  background:var(--bg) !important; color:var(--text) !important;
  border:1px solid var(--line) !important; border-radius:10px !important;
}
input[type=date]::-webkit-calendar-picker-indicator { filter:invert(.7); }
.input-group-text {
  background:var(--panel-2) !important; border:1px solid var(--line) !important; color:var(--muted) !important;
}
</style>
<style>
/* Kill DataTables odd-row + sorted-column light backgrounds */
table.dataTable tbody tr.odd,
table.dataTable tbody tr.even,
table.dataTable tbody tr.odd > td,
table.dataTable tbody tr.even > td,
table.dataTable tbody tr.odd > .sorting_1,
table.dataTable tbody tr.even > .sorting_1,
table.dataTable tbody tr > .sorting_1,
table.dataTable.stripe tbody tr.odd,
table.dataTable.display tbody tr.odd,
table.dataTable.display tbody tr.odd > .sorting_1 {
  background-color: transparent !important;
  background: transparent !important;
}
table.dataTable tbody tr.odd:hover > td,
table.dataTable tbody tr.even:hover > td,
table.dataTable tbody tr:hover > .sorting_1 {
  background-color: var(--panel-2) !important;
}
</style>
<style>
/* Row action icons (view/edit/delete) + light-dark buttons on dark theme */
.table-controls > li > a { color: var(--muted) !important; }
.table-controls > li > a:hover { color: var(--accent) !important; }
.table-controls svg { stroke: currentColor !important; }
.btn-light-dark { background: var(--panel-3) !important; color: var(--text) !important; border:1px solid var(--line) !important; }
.btn-light-dark:hover { background: var(--panel-2) !important; color: var(--text) !important; }
</style>
<style>
/* Highest-specificity kill for DataTables sorted-column + odd row shading */
table.dataTable tbody tr.odd td.sorting_1,
table.dataTable tbody tr.even td.sorting_1,
table.dataTable tbody tr td.sorting_1,
table.dataTable.stripe tbody tr.odd td,
table.dataTable.stripe tbody tr.odd td.sorting_1,
table.dataTable.order-column tbody tr td.sorting_1,
table.dataTable.display tbody tr.odd td.sorting_1,
table.dataTable.display tbody tr.odd > td {
  background-color: transparent !important;
  background-image: none !important;
}
/* also catch any inline background JS may have set */
table.dataTable tbody tr[style],
table.dataTable tbody tr[style] td { background: transparent !important; }
table.dataTable tbody tr:hover td { background-color: var(--panel-2) !important; }
</style>

<style>
/* Balance/wallet cards readable on dark (user detail page) */
.widget-wallet-one{background:var(--panel)!important;border:1px solid var(--line)!important;border-radius:14px!important;box-shadow:none!important;}
.widget-wallet-one .wallet-title{color:var(--muted)!important;}
.widget-wallet-one .total-amount{color:var(--text)!important;font-weight:700!important;}
.widget-wallet-one .btn-text-inner{color:var(--text)!important;}
.widget-wallet-one .wallet-action span p:last-child{color:var(--text)!important;}
/* Long email/contact rows wrap instead of overflowing */
.contacts-block__item{word-break:break-all;overflow-wrap:anywhere;}
.contacts-block__item a,#email{word-break:break-all;overflow-wrap:anywhere;color:var(--accent)!important;}
</style><style>
/* ApexCharts / chart SVGs rendering as blank white panels */
.apexcharts-canvas, .apexcharts-svg, svg.apexcharts-svg {
  background: transparent !important;
}
.apexcharts-svg rect[fill="#ffffff"], .apexcharts-svg rect[fill="#fff"] { fill: transparent !important; }
.apexcharts-inner, .apexcharts-graphical { background: transparent !important; }
.apexcharts-text, .apexcharts-title-text, .apexcharts-legend-text, .apexcharts-yaxis-label, .apexcharts-xaxis-label { fill: var(--muted) !important; }
.apexcharts-gridline { stroke: var(--line) !important; }
/* the chart wrapper the page uses */
.chart-container, .sales-dashboard, .chart-card { background: var(--panel) !important; }
</style>
<style>
/* ApexCharts background rect renders as a white panel — make it transparent */
.apexcharts-svg rect[fill="#fff"],
.apexcharts-svg rect[fill="#ffffff"],
.apexcharts-svg rect[fill="#FFF"],
.apexcharts-svg rect[fill="white"] { fill: transparent !important; }
.apexcharts-svg { background: transparent !important; }
.apexcharts-grid rect[fill="#f3f3f3"] { fill: transparent !important; }
</style>

<style>
/* Inflow/Outflow + other blank ApexCharts foreignObject white panels */
.apexcharts-canvas foreignObject { background: transparent !important; }
.apexcharts-canvas foreignObject > div,
.apexcharts-canvas foreignObject * { background: transparent !important; }
#inflowOutflowChart, #inflowOutflowChart * { background: transparent !important; }
/* If a chart is blank (no series), hide its canvas so no white box shows */
.apexcharts-canvas { background: transparent !important; }
foreignObject div[xmlns] { background: transparent !important; }
</style>

<style>
/* ===== FIX: .input-checkbox rendering as a giant white panel ===== */
.form-switch-custom .input-checkbox,
.inner-label-toggle .input-checkbox,
.input-checkbox {
  display: inline-flex !important;
  align-items: center !important;
  gap: 10px !important;
  width: auto !important;
  max-width: max-content !important;
  min-width: 0 !important;
  height: auto !important;
  min-height: 0 !important;
  max-height: none !important;
  background: transparent !important;
  background-color: transparent !important;
  border: none !important;
  box-shadow: none !important;
  padding: 0 !important;
  margin: 0 !important;
  position: static !important;
  overflow: visible !important;
  flex: 0 0 auto !important;
}
.input-checkbox .switch-input[type=checkbox] {
  flex: 0 0 auto !important;
  width: 44px !important;
  height: 24px !important;
}
.input-checkbox .switch-chk-label {
  width: auto !important;
  background: transparent !important;
  white-space: nowrap !important;
  flex: 0 0 auto !important;
}
</style>
<style>
/* Renamed toggle wrapper (was .input-checkbox) — compact, no white */
.dw-switch-wrap {
  display:inline-flex !important; align-items:center !important; gap:10px !important;
  width:auto !important; max-width:max-content !important; height:auto !important;
  background:transparent !important; border:none !important; box-shadow:none !important;
  padding:0 !important; margin:0 !important; position:static !important; overflow:visible !important;
}
.dw-switch-wrap .switch-input[type=checkbox]{ flex:0 0 auto !important; width:44px !important; height:24px !important; }
.dw-switch-wrap .switch-chk-label{ background:transparent !important; white-space:nowrap !important; width:auto !important; }
</style>
<style>
/* ===== Complete dark toggle styling for dw-switch-wrap ===== */
.dw-switch-wrap {
  display:inline-flex !important; align-items:center !important; gap:10px !important;
  width:auto !important; background:transparent !important; border:none !important;
  box-shadow:none !important; padding:0 !important; margin:0 !important;
}
.dw-switch-wrap .switch-chk-label {
  font-size:13px !important; font-weight:600 !important; color:#8593AB !important;
  background:transparent !important; white-space:nowrap !important; width:auto !important; padding:0 !important;
}
.inner-label-toggle.show .switch-chk-label.label-right { color:#00E5A0 !important; }
.inner-label-toggle:not(.show) .switch-chk-label.label-left { color:#FF6B81 !important; }

/* the switch input as a compact iOS pill */
.dw-switch-wrap .switch-input[type=checkbox]{
  -webkit-appearance:none !important; appearance:none !important;
  width:46px !important; min-width:46px !important; max-width:46px !important; height:26px !important;
  border-radius:13px !important; background:#2a3446 !important; border:1px solid rgba(255,255,255,.12) !important;
  position:relative !important; cursor:pointer !important; margin:0 6px !important; padding:0 !important;
  flex:0 0 auto !important; vertical-align:middle !important; box-shadow:none !important; transition:.2s !important;
}
.dw-switch-wrap .switch-input[type=checkbox]::after{
  content:'' !important; position:absolute !important; top:2px !important; left:2px !important;
  width:20px !important; height:20px !important; border-radius:50% !important; background:#fff !important;
  transition:.2s !important; box-shadow:0 1px 3px rgba(0,0,0,.4) !important;
}
.dw-switch-wrap .switch-input[type=checkbox]:checked{ background:#00E5A0 !important; border-color:#00E5A0 !important; }
.dw-switch-wrap .switch-input[type=checkbox]:checked::after{ transform:translateX(20px) !important; }
</style>

{{-- ══════════════════════════════════════════════════════════════════
     LIGHT MODE LAYER
     Activated by <html data-theme="light">. Because the whole panel is
     driven by the CSS variables below, overriding them flips sidebar,
     topbar, cards, tables, forms, modals, badges, buttons, DataTables,
     select2, alerts, footer AND the dashboard (--db-*) in one shot.
     Chart axis-labels / gridlines / backgrounds already reference
     var(--muted)/var(--line) with !important, so ApexCharts re-colours
     automatically. Appended last so it wins cleanly. Default stays dark.
     ══════════════════════════════════════════════════════════════════ --}}
<style id="dw-light-theme">
html[data-theme="light"]{
  /* ── core semantic tokens ── */
  --bg:#EEF1F6; --panel:#FFFFFF; --panel-2:#F3F5F9; --panel-3:#E7ECF3;
  --line:rgba(15,23,42,0.08); --line-strong:rgba(15,23,42,0.15);
  --accent:#00966B; --accent-dim:#007a56; --accent-soft:rgba(0,150,107,0.10);
  --gold:#B7891A; --violet:#6C5CE7; --blue:#2F6FED;
  --text:#1B2432; --muted:#5B6678; --faint:#8A94A6;
  --up:#00A76F; --down:#E5484D; --warn:#B7891A;

  /* ── dashboard (--db-*) tokens: override the dashboard page's own :root ── */
  --db-bg:#EEF1F6; --db-surface:#FFFFFF; --db-surface-2:#F3F5F9; --db-surface-3:#E7ECF3;
  --db-border:rgba(15,23,42,0.08); --db-border-strong:rgba(15,23,42,0.15);
  --db-text:#1B2432; --db-muted:#5B6678; --db-faint:#8A94A6;
  --db-accent:#00966B; --db-accent-soft:rgba(0,150,107,0.10);
  --db-green:#00A76F;  --db-green-bg:rgba(0,167,111,0.12);
  --db-blue:#2F6FED;   --db-blue-bg:rgba(47,111,237,0.12);
  --db-amber:#B7891A;  --db-amber-bg:rgba(183,137,26,0.12);
  --db-violet:#6C5CE7; --db-violet-bg:rgba(108,92,231,0.12);
  --db-red:#E5484D;    --db-red-bg:rgba(229,72,77,0.12);
}

/* ── base surfaces ── */
html[data-theme="light"] body,
html[data-theme="light"]{ color:var(--text)!important; }

/* ── topbar (hardcoded dark rgba in the dark rules) ── */
html[data-theme="light"] .header-container{
  background:rgba(255,255,255,0.82)!important;
  border-bottom:1px solid var(--line)!important;
}

/* ── dropdown / modal shadows: soften for light ── */
html[data-theme="light"] .header .dropdown-menu{
  box-shadow:0 16px 40px -14px rgba(15,23,42,0.22)!important;
}
html[data-theme="light"] .modal-content{
  box-shadow:0 24px 60px -18px rgba(15,23,42,0.28)!important;
}
html[data-theme="light"] .sweet-alert{
  box-shadow:0 24px 60px -18px rgba(15,23,42,0.28)!important;
}

/* ── modal close button: don't invert on light ── */
html[data-theme="light"] .btn-close{ filter:none!important; opacity:.65!important; }
html[data-theme="light"] .btn-close:hover{ opacity:1!important; }

/* ── date picker indicator: don't invert on light ── */
html[data-theme="light"] input[type=date]::-webkit-calendar-picker-indicator{ filter:none!important; }

/* ── selection colour readable on light ── */
html[data-theme="light"] ::selection{ background:var(--accent)!important; color:#fff!important; }

/* ── status toggle pill (had hardcoded #2a3446 / #00E5A0 / #8593AB) ── */
html[data-theme="light"] .dw-switch-wrap .switch-input[type=checkbox]{
  background:#D3D9E2!important; border-color:rgba(15,23,42,0.14)!important;
}
html[data-theme="light"] .dw-switch-wrap .switch-input[type=checkbox]:checked{
  background:var(--accent)!important; border-color:var(--accent)!important;
}
html[data-theme="light"] .dw-switch-wrap .switch-chk-label{ color:var(--muted)!important; }
html[data-theme="light"] .inner-label-toggle.show .switch-chk-label.label-right{ color:var(--accent)!important; }
html[data-theme="light"] .inner-label-toggle:not(.show) .switch-chk-label.label-left{ color:var(--down)!important; }

/* ── loader spinner already uses var(--accent); loader bg ── */
html[data-theme="light"] #load_screen{ background:var(--bg)!important; }

/* ── DataTables paginate hover text stays readable on light ── */
html[data-theme="light"] .dataTables_wrapper .dataTables_paginate .paginate_button{ background:var(--panel-2)!important; }

/* ══════════════════════════════════════════════════════════════════
   THEME TOGGLE BUTTON (topbar)
   ══════════════════════════════════════════════════════════════════ */
.dw-theme-toggle{
  width:38px; height:38px; border-radius:10px; padding:0;
  display:inline-flex; align-items:center; justify-content:center;
  background:var(--panel-2); border:1px solid var(--line);
  color:var(--text); cursor:pointer; transition:.18s;
}
.dw-theme-toggle:hover{ border-color:var(--accent); color:var(--accent); }
.dw-theme-toggle svg{ width:18px; height:18px; stroke:currentColor; fill:none; stroke-width:2; }
.dw-theme-toggle .ico-sun{ display:none; }
html[data-theme="light"] .dw-theme-toggle .ico-moon{ display:none; }
html[data-theme="light"] .dw-theme-toggle .ico-sun{ display:block; }
</style>

{{-- ── Transaction amount-card light-mode fix ────────────────────────────
     The cards use Bootstrap bg-primary/warning/success bg-opacity-10 (very
     pale tint) with text-white hardcoded. On light mode that renders as white
     text on a near-white background — invisible. Override with solid tinted
     backgrounds + dark/coloured text so they pop on both themes.
──────────────────────────────────────────────────────────────────────── --}}
<style id="dw-amount-card-fix">
html[data-theme="light"] .amount-card{
  border:1px solid transparent;
}
/* Amount card — blue */
html[data-theme="light"] .amount-card.bg-primary.bg-opacity-10{
  background:rgba(47,111,237,0.14)!important;
  border-color:rgba(47,111,237,0.22)!important;
}
html[data-theme="light"] .amount-card.bg-primary.bg-opacity-10 .amount-label,
html[data-theme="light"] .amount-card.bg-primary.bg-opacity-10 .amount-value{
  color:#1a4fa8!important;
}
/* Charge card — amber/warning */
html[data-theme="light"] .amount-card.bg-warning.bg-opacity-10{
  background:rgba(183,137,26,0.14)!important;
  border-color:rgba(183,137,26,0.24)!important;
}
html[data-theme="light"] .amount-card.bg-warning.bg-opacity-10 .amount-label,
html[data-theme="light"] .amount-card.bg-warning.bg-opacity-10 .amount-value{
  color:#7a5500!important;
}
/* Real Price / VAT card — green */
html[data-theme="light"] .amount-card.bg-success.bg-opacity-10{
  background:rgba(0,150,107,0.14)!important;
  border-color:rgba(0,150,107,0.22)!important;
}
html[data-theme="light"] .amount-card.bg-success.bg-opacity-10 .amount-label,
html[data-theme="light"] .amount-card.bg-success.bg-opacity-10 .amount-value{
  color:#005c3f!important;
}
</style>
{{-- ══════════════════════════════════════════════════════════════════
     SWEETALERT (v1) ICON + MODAL NORMALISER
     Appended last so it wins the cascade in both themes.

     Why the success icon looked broken:
     SweetAlert v1 doesn't use an icon — it draws a green ring and then
     masks it with two ROTATING WHITE RECTANGLES (.sa-icon::before,
     .sa-icon::after, .sa-fix) that are hard-coded to `background:white`,
     animated over 4.25s. On this panel the modal background is
     var(--panel-2), never white, so those masks show up as a white blob
     in dark mode and a dark wedge in light mode — and a screenshot
     catches whatever animation frame it lands on.

     Fix: strip every animated part and every mask, and paint one static
     inline SVG per icon type. Background-independent, so it renders
     pixel-identical in light and dark.
     ══════════════════════════════════════════════════════════════════ --}}
<style id="dw-swal-fix">
/* ── Modal shell ─────────────────────────────────────────────────── */
.sweet-alert{
  background:var(--panel-2)!important;
  color:var(--text)!important;
  border:1px solid var(--line)!important;
  border-radius:16px!important;
  padding:28px 24px 24px!important;
}
.sweet-alert h2{
  font-family:'Space Grotesk','Inter',sans-serif!important;
  color:var(--text)!important;
  font-size:26px!important; font-weight:600!important;
  margin:6px 0 8px!important; line-height:1.25!important;
}
.sweet-alert p,
.sweet-alert .sa-content{
  color:var(--muted)!important;
  font-size:15px!important; line-height:1.5!important;
}

/* ── Buttons: match the panel accent instead of SweetAlert's blue ── */
.sweet-alert .sa-button-container,
.sweet-alert .sa-confirm-button-container{ margin-top:8px!important; }

.sweet-alert button.confirm{
  background-color:var(--accent)!important;
  color:#04130d!important;
  border:none!important; border-radius:10px!important;
  font-weight:600!important; padding:10px 30px!important;
  box-shadow:none!important;
}
.sweet-alert button.confirm:hover,
.sweet-alert button.confirm:focus{
  filter:brightness(1.08)!important;
  box-shadow:0 0 0 3px var(--accent-soft)!important;
  outline:none!important;
}
html[data-theme="light"] .sweet-alert button.confirm{ color:#ffffff!important; }

.sweet-alert button.cancel{
  background-color:var(--panel-3)!important;
  color:var(--text)!important;
  border:1px solid var(--line)!important;
  border-radius:10px!important;
  font-weight:600!important; padding:10px 30px!important;
}

/* ── Icon reset: kill the ring, the masks, the lines, the animation ── */
.sweet-alert .sa-icon:not(.sa-custom){
  width:88px!important; height:88px!important;
  margin:4px auto 16px!important; padding:0!important;
  border:none!important; border-radius:0!important;
  background-color:transparent!important;
  background-repeat:no-repeat!important;
  background-position:center center!important;
  background-size:88px 88px!important;
  box-shadow:none!important;
  animation:none!important; -webkit-animation:none!important;
  transform:none!important;
  position:relative!important;
  /* NOTE: no `display` rule here on purpose. SweetAlert v1 renders all four
     icon divs (error/warning/info/success) into every modal and reveals only
     the active one by setting style.display inline from JS. A `display:block
     !important` here beats that inline style and makes ALL FOUR stack up. */
}
.sweet-alert .sa-icon:not(.sa-custom) > *,
.sweet-alert .sa-icon:not(.sa-custom)::before,
.sweet-alert .sa-icon:not(.sa-custom)::after{
  display:none!important; content:none!important;
  animation:none!important; -webkit-animation:none!important;
}

/* ── Static SVG icons (identical in light + dark) ─────────────────── */
.sweet-alert .sa-icon.sa-success{
  background-image:url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 88 88'%3E%3Ccircle cx='44' cy='44' r='40' fill='none' stroke='%2300A76F' stroke-width='4' opacity='0.35'/%3E%3Ccircle cx='44' cy='44' r='33' fill='%2300A76F' opacity='0.12'/%3E%3Cpath d='M28 45.5 L39 56 L61 32' fill='none' stroke='%2300A76F' stroke-width='6' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E")!important;
}
.sweet-alert .sa-icon.sa-error{
  background-image:url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 88 88'%3E%3Ccircle cx='44' cy='44' r='40' fill='none' stroke='%23E5484D' stroke-width='4' opacity='0.35'/%3E%3Ccircle cx='44' cy='44' r='33' fill='%23E5484D' opacity='0.12'/%3E%3Cpath d='M32 32 L56 56 M56 32 L32 56' fill='none' stroke='%23E5484D' stroke-width='6' stroke-linecap='round'/%3E%3C/svg%3E")!important;
}
.sweet-alert .sa-icon.sa-warning{
  background-image:url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 88 88'%3E%3Ccircle cx='44' cy='44' r='40' fill='none' stroke='%23F5C451' stroke-width='4' opacity='0.35'/%3E%3Ccircle cx='44' cy='44' r='33' fill='%23F5C451' opacity='0.12'/%3E%3Cpath d='M44 26 L44 48' fill='none' stroke='%23F5C451' stroke-width='6' stroke-linecap='round'/%3E%3Ccircle cx='44' cy='60' r='3.6' fill='%23F5C451'/%3E%3C/svg%3E")!important;
}
.sweet-alert .sa-icon.sa-info{
  background-image:url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 88 88'%3E%3Ccircle cx='44' cy='44' r='40' fill='none' stroke='%234D8DFF' stroke-width='4' opacity='0.35'/%3E%3Ccircle cx='44' cy='44' r='33' fill='%234D8DFF' opacity='0.12'/%3E%3Ccircle cx='44' cy='28' r='3.6' fill='%234D8DFF'/%3E%3Cpath d='M44 40 L44 62' fill='none' stroke='%234D8DFF' stroke-width='6' stroke-linecap='round'/%3E%3C/svg%3E")!important;
}

/* Warning colour reads better on light */
html[data-theme="light"] .sweet-alert .sa-icon.sa-warning{
  background-image:url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 88 88'%3E%3Ccircle cx='44' cy='44' r='40' fill='none' stroke='%23B7891A' stroke-width='4' opacity='0.35'/%3E%3Ccircle cx='44' cy='44' r='33' fill='%23B7891A' opacity='0.12'/%3E%3Cpath d='M44 26 L44 48' fill='none' stroke='%23B7891A' stroke-width='6' stroke-linecap='round'/%3E%3Ccircle cx='44' cy='60' r='3.6' fill='%23B7891A'/%3E%3C/svg%3E")!important;
}

/* Overlay */
.sweet-overlay{ background-color:rgba(6,9,16,.55)!important; }
html[data-theme="light"] .sweet-overlay{ background-color:rgba(15,23,42,.35)!important; }
</style>