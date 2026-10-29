<!-- GLOBAL MANDATORY SCRIPTS (same stack as classic admin so DataTables etc. work) -->
<script src="{{ asset('assets/admin/src/plugins/src/global/vendors.min.js')}}"></script>
<script src="{{ asset('assets/admin/src/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{ asset('assets/admin/src/plugins/src/mousetrap/mousetrap.min.js')}}"></script>
<script src="{{ asset('assets/admin/src/assets/js/custom.js')}}"></script>

<!-- Plugin scripts the module pages depend on -->
<script src="{{ asset('assets/admin/src/plugins/src/apex/apexcharts.min.js')}}"></script>
<script src="{{ asset('assets/js/select2.js')}}"></script>
<script src="{{ asset('assets/admin/src/plugins/src/jquery-ui/jquery-ui.min.js')}}"></script>
<script src="{{ asset('assets/admin/src/plugins/src/table/datatable/datatables-2.js')}}"></script>
<script src="{{ asset('assets/js/bootstrap-switch-button.min.js') }}"></script>
<script src="{{ asset('assets/js/overlay.js') }}"></script>
<script src="{{ asset('assets/js/sweetalert.min.js') }}"></script>
<script src="{{ asset('assets/js/ismart.js') }}"></script>
<script src="{{ asset('assets/admin/src/plugins/src/vanillaSelectBox/vanillaSelectBox.js') }}"></script>
<script src="{{ asset('assets/admin/src/plugins/src/vanillaSelectBox/custom-vanillaSelectBox.js') }}"></script>

<script>

    // Success/notification dialog from session flash (matches classic admin)
    @if (session()->has('message'))
        swal({
            title: "{{ session()->get('title') }}",
            text: "{{ session()->get('message') }}",
            type: "{{ session()->get('type') }}",
            confirmButtonText: "OK"
        });
        @php session()->forget('message') @endphp
    @endif

(function () {
    // Disable PerfectScrollbar so the page scrolls natively (old scroll-bug fix).
    if (typeof PerfectScrollbar !== 'undefined') {
        PerfectScrollbar = function () { return { update: function () {}, destroy: function () {} }; };
    }
    document.querySelectorAll('.ps, .ps--active-y, .ps--active-x').forEach(function (el) {
        el.classList.remove('ps', 'ps--active-y', 'ps--active-x', 'ps--focus');
        el.querySelectorAll('.ps__rail-x, .ps__rail-y').forEach(function (r) { r.remove(); });
    });

    // Mobile sidebar toggle
    document.querySelectorAll('.sidebarCollapse').forEach(function (btn) {
        btn.addEventListener('click', function (e) { e.preventDefault(); document.body.classList.toggle('sidebar-open'); });
    });
    if (window.innerWidth <= 991) {
        document.querySelectorAll('.dw-sub a, .dw-item[href]').forEach(function (a) {
            a.addEventListener('click', function () { document.body.classList.remove('sidebar-open'); });
        });
    }

    // Highlight active nav item + open its parent group
    var here = window.location.href.split('#')[0];
    document.querySelectorAll('.dw-nav a[href]').forEach(function (a) {
        var href = a.getAttribute('href');
        if (!href || href.charAt(0) === '#') return;
        if (a.href.split('#')[0] === here) {
            a.classList.add('active');
            var sub = a.closest('.dw-sub');
            if (sub) {
                sub.classList.add('show');
                var toggle = document.querySelector('[href="#' + sub.id + '"]');
                if (toggle) { toggle.classList.add('active'); toggle.setAttribute('aria-expanded', 'true'); }
            }
        }
    });
})();

// ── Force DataTables rows dark (beats page-level light stripe CSS) ──────
(function () {
    if (!window.jQuery) return;
    var $ = window.jQuery;
    function paintRows() {
        document.querySelectorAll('table.dataTable tbody tr').forEach(function (tr) {
            tr.style.setProperty('background-color', 'transparent', 'important');
            tr.querySelectorAll('td').forEach(function (td) {
                td.style.setProperty('background-color', 'transparent', 'important');
                td.style.setProperty('background-image', 'none', 'important');
            });
        });
    }
    // Run on every draw/search/sort/page change, and once at load.
    $(document).on('draw.dt init.dt order.dt search.dt page.dt', paintRows);
    $(function () { setTimeout(paintRows, 300); });

    // Preserve a subtle hover without reintroducing stripes. Theme-aware.
    function hoverBg() {
        var v = getComputedStyle(document.documentElement).getPropertyValue('--panel-2').trim();
        return v || '#121A28';
    }
    $(document).on('mouseenter', 'table.dataTable tbody tr', function () {
        this.style.setProperty('background-color', hoverBg(), 'important');
    }).on('mouseleave', 'table.dataTable tbody tr', function () {
        this.style.setProperty('background-color', 'transparent', 'important');
    });
})();

// ── Light / Dark theme toggle ──────────────────────────────────────────
(function () {
    var KEY  = 'dupay-admin-theme';
    var root = document.documentElement;
    var btns = document.querySelectorAll('.dw-theme-toggle');
    var meta = document.querySelector('meta[name="theme-color"]');

    function apply(mode) {
        if (mode === 'light') { root.setAttribute('data-theme', 'light'); }
        else { root.removeAttribute('data-theme'); }
        if (meta) { meta.setAttribute('content', mode === 'light' ? '#EEF1F6' : '#070A12'); }
        // Let any chart / widget that wants to hard re-theme react to this.
        document.dispatchEvent(new CustomEvent('themechange', { detail: { mode: mode } }));
    }

    // Sync UI with whatever the no-flash head script already applied.
    var saved = null;
    try { saved = localStorage.getItem(KEY); } catch (e) {}
    apply(saved === 'light' ? 'light' : 'dark');

    btns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var current = root.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
            var next = current === 'light' ? 'dark' : 'light';
            try { localStorage.setItem(KEY, next); } catch (e) {}
            apply(next);
        });
    });
})();
</script>

@yield('scripts')

</body>
</html>