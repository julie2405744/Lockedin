/**
 * InFocus — student room discovery interactions
 * Member 4. Vanilla JS only; no booking or availability rules here.
 * The server is the authority on search results and every reservation
 * decision — this file only improves filter usability.
 */
(function () {
    'use strict';

    /* ── Collapsible filter panel (mobile) ─────────────────────── */
    var toggle = document.getElementById('filter-toggle');
    var body = document.getElementById('filter-panel-body');

    if (toggle && body) {
        // Start collapsed only on small screens; the CSS media query
        // hides the body either way, so match the current visual state.
        var isSmall = window.matchMedia('(max-width: 575px)').matches;
        if (isSmall) {
            body.setAttribute('data-collapsed', 'true');
            toggle.setAttribute('aria-expanded', 'false');
        }

        toggle.addEventListener('click', function () {
            var collapsed = body.getAttribute('data-collapsed') === 'true';
            body.setAttribute('data-collapsed', collapsed ? 'false' : 'true');
            toggle.setAttribute('aria-expanded', collapsed ? 'true' : 'false');
        });

        // Keep keyboard-reachable: the toggle stays a real button and the
        // panel is inside the normal tab order when expanded.
        window.matchMedia('(max-width: 575px)').addEventListener('change', function (event) {
            if (!event.matches) {
                body.setAttribute('data-collapsed', 'false');
                toggle.setAttribute('aria-expanded', 'true');
            }
        });
    }

    /* ── Keep end time at or after start time when picking ─────── */
    var dateInput = document.getElementById('filter-date');
    var startInput = document.getElementById('filter-start');
    var endInput = document.getElementById('filter-end');

    function syncTimeMin() {
        if (!startInput || !endInput) return;
        endInput.min = startInput.value || '';
        if (startInput.value && endInput.value && endInput.value < startInput.value) {
            endInput.value = startInput.value;
        }
    }

    if (startInput && endInput) {
        startInput.addEventListener('change', syncTimeMin);
    }

    /* ── Reset back to the unfiltered directory ────────────────── */
    var resetLink = document.getElementById('filter-reset');

    if (resetLink) {
        resetLink.addEventListener('click', function (event) {
            event.preventDefault();
            if (dateInput) dateInput.value = '';
            if (startInput) startInput.value = '';
            if (endInput) endInput.value = '';
            var capacity = document.getElementById('filter-capacity');
            if (capacity) capacity.value = '';
            var category = document.getElementById('filter-category');
            if (category) category.value = '';
            var checks = document.querySelectorAll('.filter-panel input[type="checkbox"]');
            for (var i = 0; i < checks.length; i++) {
                checks[i].checked = false;
            }
            // Submit the cleared form so the visible results follow.
            var form = document.getElementById('room-filter-form');
            if (form) form.submit();
        });
    }
})();
