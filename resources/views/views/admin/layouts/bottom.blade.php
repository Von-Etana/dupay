<!-- GLOBAL MANDATORY SCRIPTS (kept intact so DataTables, dropdowns, overlays keep working) -->
<script src="{{ asset('assets/admin/src/plugins/src/global/vendors.min.js')}}"></script>
<script src="{{ asset('assets/admin/src/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{ asset('assets/admin/src/plugins/src/mousetrap/mousetrap.min.js')}}"></script>
<script src="{{ asset('assets/admin/src/assets/js/custom.js')}}"></script>

<script>
(function () {
    // Disable PerfectScrollbar so the page scrolls natively.
    // (Root cause of the old scroll bug — handled cleanly, no DOM-nuking.)
    if (typeof PerfectScrollbar !== 'undefined') {
        PerfectScrollbar = function () { return { update: function () {}, destroy: function () {} }; };
    }
    document.querySelectorAll('.ps, .ps--active-y, .ps--active-x').forEach(function (el) {
        el.classList.remove('ps', 'ps--active-y', 'ps--active-x', 'ps--focus');
        el.querySelectorAll('.ps__rail-x, .ps__rail-y').forEach(function (r) { r.remove(); });
    });

    // Mobile sidebar toggle
    document.querySelectorAll('.sidebarCollapse').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            document.body.classList.toggle('sidebar-open');
        });
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
</script>

@yield('scripts')

</body>
</html>
