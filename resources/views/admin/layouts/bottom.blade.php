<!-- BEGIN GLOBAL MANDATORY SCRIPTS -->
{{--  <script src="{{ asset('assets/js/jquery.js') }}"></script>  --}}


<script src="{{ asset('assets/admin/src/plugins/src/global/vendors.min.js')}}"></script>
<script src="{{ asset('assets/admin/src/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{ asset('assets/admin/src/plugins/src/perfect-scrollbar/perfect-scrollbar.min.js')}}"></script>
<script src="{{ asset('assets/admin/src/plugins/src/mousetrap/mousetrap.min.js')}}"></script>
<script src="{{ asset('assets/admin/layouts/app.js')}}"></script>
<script src="{{ asset('assets/admin/src/assets/js/custom.js')}}"></script>
<script>
// ── Nuclear scroll fix — kills PS everywhere including dashboard ──────────

function killPerfectScrollbar() {
    // Destroy all PS instances
    if (typeof PerfectScrollbar !== 'undefined') {
        document.querySelectorAll('.ps, .ps--active-y, .ps--active-x').forEach(function(el) {
            try { if (el._ps) { el._ps.destroy(); el._ps = null; } } catch(e) {}
            el.classList.remove('ps','ps--active-y','ps--active-x','ps--focus');
            el.style.cssText += ';overflow:auto!important;height:auto!important;max-height:none!important;position:relative!important;';
            // Remove ps scrollbar elements
            el.querySelectorAll('.ps__rail-x,.ps__rail-y,.ps__thumb-x,.ps__thumb-y').forEach(function(r){ r.remove(); });
        });
    }
    // Override PerfectScrollbar constructor so it can never be re-created
    if (typeof PerfectScrollbar !== 'undefined') {
        PerfectScrollbar = function() { return { update:function(){}, destroy:function(){} }; };
    }
    // Force body scroll
    document.body.style.cssText += ';overflow:auto!important;overflow-x:hidden!important;height:auto!important;';
    document.documentElement.style.cssText += ';overflow:auto!important;overflow-x:hidden!important;height:auto!important;';
    // Force containers
    ['container','content'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.style.cssText += ';overflow:visible!important;height:auto!important;max-height:none!important;';
    });
    ['.main-container','.main-content','.layout-px-spacing'].forEach(function(sel) {
        document.querySelectorAll(sel).forEach(function(el) {
            el.style.cssText += ';overflow:visible!important;height:auto!important;';
        });
    });
}

// Run immediately
document.addEventListener('DOMContentLoaded', function() {
    // Kill perfect-scrollbar on all elements
    if (typeof PerfectScrollbar !== 'undefined') {
        document.querySelectorAll('.ps').forEach(function(el) {
            try {
                var ps = el._ps;
                if (ps) { ps.destroy(); }
            } catch(e) {}
            el.classList.remove('ps', 'ps--active-y', 'ps--active-x');
            el.style.overflow = 'auto';
            el.style.overflowX = 'hidden';
            el.style.height = 'auto';
            el.style.maxHeight = 'none';
            el.style.position = 'relative';
        });
    }

    // Force native scroll on body and containers
    var els = [
        document.documentElement,
        document.body,
        document.getElementById('container'),
        document.getElementById('content'),
        document.querySelector('.main-container'),
        document.querySelector('.main-content'),
        document.querySelector('#content'),
    ];
    els.forEach(function(el) {
        if (!el) return;
        el.style.overflow = 'visible';
        el.style.overflowY = 'visible';
        el.style.height = 'auto';
        el.style.maxHeight = 'none';
        el.style.webkitOverflowScrolling = 'touch';
    });

    // Specifically allow body to scroll
    document.body.style.overflow = 'auto';
    document.body.style.overflowX = 'hidden';
    document.documentElement.style.overflow = 'auto';
    document.documentElement.style.overflowX = 'hidden';
});

// Also run after window loads (some themes reinit PS on load)
window.addEventListener('load', function() {
    document.body.style.overflow = 'auto';
    document.body.style.overflowX = 'hidden';
    document.documentElement.style.overflow = 'auto';
    document.documentElement.style.overflowX = 'hidden';

    var main = document.querySelector('.main-content') || document.getElementById('content');
    if (main) {
        main.style.overflow = 'visible';
        main.style.height = 'auto';
    }

    // Kill PS now
    killPerfectScrollbar();

    // ── Mac 2013 trackpad fix ─────────────────────────────────────────────
    // Old Macs use wheel events not touch events — intercept and scroll body
    var wheelHandler = function(e) {
        // If perfect-scrollbar or theme is blocking, manually scroll
        if (e.defaultPrevented) return;
        window.scrollBy({
            top: e.deltaY || e.detail || e.wheelDelta * -1,
            left: 0,
            behavior: 'auto'
        });
    };

    // Remove any existing wheel blockers on containers
    var containers = document.querySelectorAll(
        '.main-container, #container, #content, .main-content, .ps'
    );
    containers.forEach(function(el) {
        // Clone to remove all event listeners
        var clone = el.cloneNode(false);
        while (el.firstChild) clone.appendChild(el.firstChild);
        if (el.parentNode) el.parentNode.replaceChild(clone, el);
    });

    // Allow native wheel scroll on document
    document.addEventListener('wheel', function(e) {
        // Do nothing — just ensure it's not prevented
    }, { passive: true });

    // Older Safari on Mac 2013 uses mousewheel event
    document.addEventListener('mousewheel', function(e) {
        if (e.defaultPrevented) {
            window.scrollBy(0, -(e.wheelDelta || e.detail * -40));
        }
    }, { passive: false });

    document.addEventListener('DOMMouseScroll', function(e) {
        if (e.defaultPrevented) {
            window.scrollBy(0, e.detail * 40);
        }
    }, { passive: false });

    // Re-apply overflow after any JS theme re-init
    // Run multiple times to catch any late JS reinit
    [100, 300, 600, 1000, 2000].forEach(function(delay) {
        setTimeout(killPerfectScrollbar, delay);
    });
});
</script>
<!-- END GLOBAL MANDATORY SCRIPTS -->

<!-- BEGIN PAGE LEVEL PLUGINS/CUSTOM SCRIPTS -->
<script src="{{ asset('assets/admin/src/plugins/src/apex/apexcharts.min.js')}}"></script>
<script src="{{ asset('assets/admin/src/assets/js/dashboard/dash_1.js')}}"></script>
<!-- BEGIN PAGE LEVEL PLUGINS/CUSTOM SCRIPTS -->
<script src="{{ asset('assets/js/select2.js')}}"></script>
<script src="{{ asset('assets/admin/src/plugins/src/jquery-ui/jquery-ui.min.js')}}"></script>
<script src="{{ asset('assets/admin/src/assets/js/apps/contact-2.js')}}"></script>
<script src="{{ asset('assets/admin/src/plugins/src/table/datatable/datatables-2.js')}}"></script>
<script src="{{ asset('assets/js/bootstrap-switch-button.min.js') }}"></script>
<script src="{{ asset('assets/js/overlay.js') }}"></script>
<script src="{{ asset('assets/js/sweetalert.min.js') }}"></script>
<script src="{{ asset('assets/js/ismart.js') }}"></script>
<script src="{{ asset('assets/admin/src/plugins/src/vanillaSelectBox/vanillaSelectBox.js') }}"></script>
<script src="{{ asset('assets/admin/src/plugins/src/vanillaSelectBox/custom-vanillaSelectBox.js') }}"></script>

<script>

   @if (session()->has('message'))
           swal({
               title: "{{  session()->get('title')   }}",
               text: "{{ session()->get('message')  }}",
               type: "{{  session()->get('type')   }}",
               confirmButtonText: "OK"
           });
           @php session()->forget('message') @endphp
       @endif


//Menu Active
function getClosest(elem, selector) {

    // Element.matches() polyfill
    if (!Element.prototype.matches) {
        Element.prototype.matches =
            Element.prototype.matchesSelector ||
            Element.prototype.mozMatchesSelector ||
            Element.prototype.msMatchesSelector ||
            Element.prototype.oMatchesSelector ||
            Element.prototype.webkitMatchesSelector ||
            function (s) {
                var matches = (this.document || this.ownerDocument).querySelectorAll(s),
                    i = matches.length;
                while (--i >= 0 && matches.item(i) !== this) { }
                return i > -1;
            };
    }

    // Get the closest matching element
    for (; elem && elem !== document; elem = elem.parentNode) {
        if (elem.matches(selector)) return elem;
    }
    return null;

};

function activateMenu() {
    var menuItems = document.querySelectorAll('#sidebar a');
    if (menuItems){

        var matchingMenuItem = null;
        for (var idx = 0; idx < menuItems.length; idx++) {
            if (menuItems[idx].href === window.location.href || menuItems[idx].getAttribute("href").indexOf(current) !== -1) {
                matchingMenuItem = menuItems[idx];
            }
        }

        if (matchingMenuItem) {
            matchingMenuItem.parentElement.classList.add('active');
            //matchingMenuItem.parentElement.classList.add('show');
            var immediateParent = getClosest(matchingMenuItem, 'ul');
            if (immediateParent) {
                immediateParent.classList.add('active');
                immediateParent.classList.add('show');
            }

            var parent = getClosest(matchingMenuItem, '.submenu');
            if (parent) {
                parent.classList.add('active');
                var parentMenuitem = parent.querySelector('.menu');
                if (parentMenuitem) {
                    parentMenuitem.classList.add('active');
                }
                var parentOfParent = getClosest(parent, '.menu');
                if (parentOfParent) {
                    parentOfParent.classList.add('active');
                }
            } else {
                var parentOfParent = getClosest(matchingMenuItem, '.menu');
                if (parentOfParent) {
                    parentOfParent.classList.add('active');
                }
            }
        }else{
            var current = location.pathname.substring(location.pathname.lastIndexOf('/') + 1);
            //alert(current);
        }
    }
}

activateMenu();

   </script>
@yield('scripts')

</body>
</html>