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
.form-select{background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%238593AB'%3e%3cpath d='M8 11L3 6h10z'/%3e%3c/svg%3e")!important;background-repeat:no-repeat;background-position:right 12px center;}
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

/* Cork custom switches */
.switch .switch-input:checked + .switch-label,
.form-switch-success .switch-input:checked{background:var(--accent)!important;}
.switch-label{background:var(--panel-3)!important;}
input:checked ~ .switch-label,.switch-input:checked{accent-color:var(--accent)!important;}

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
