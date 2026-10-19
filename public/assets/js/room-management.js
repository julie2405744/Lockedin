/* =============================================================
   InFocus — Room Management module JS — Member 2
   Vanilla JavaScript only.
   Milestone F1: confirmation dialogs for destructive management
   actions (uses [data-confirm] forms).
   ============================================================= */

(function () {
    'use strict';

    /**
     * Intercept form submissions for marked destructive actions.
     * Native confirm() is used: it is accessible, blocking, and
     * requires no extra markup. Upgrades can swap in the shared
     * dialog component later without changing the HTML contract.
     */
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || !(form instanceof HTMLFormElement)) {
            return;
        }

        var message = form.getAttribute('data-confirm');
        if (!message) {
            return;
        }

        if (!window.confirm(message)) {
            event.preventDefault();
        }
    });
})();

/* =============================================================
   Milestone F4 — operating-hours interval editor.
   Add/remove time-interval rows straight from <template> tags;
   confirm before clearing a room's whole schedule.
   ============================================================= */

(function () {
    'use strict';

    function quoteSafeAttr(value) {
        /* Value comes from our own numeric data attributes; narrow it anyway. */
        return String(value).replace(/[^A-Za-z0-9_-]/g, '');
    }

    document.addEventListener('click', function (event) {
        var target = event.target instanceof Element ? event.target : null;
        if (!target) {
            return;
        }

        var addBtn = target.closest('[data-add-interval]');
        if (addBtn) {
            var selector = quoteSafeAttr(addBtn.getAttribute('data-add-interval'));
            var list     = document.querySelector('[data-interval-list="' + selector + '"]');
            var template = document.querySelector('template[data-interval-template="' + selector + '"]');
            if (list && template && template.content.firstElementChild) {
                list.appendChild(template.content.firstElementChild.cloneNode(true));
            }
            return;
        }

        var removeBtn = target.closest('[data-remove-interval]');
        if (removeBtn) {
            var row = removeBtn.closest('[data-interval-row]');
            if (row && row.parentNode) {
                row.parentNode.removeChild(row);
            }
        }
    });

    /**
     * Saving the schedule deletes and re-creates every interval. If the
     * admin cleared all rows, warn explicitly before wiping the week.
     */
    document.addEventListener('submit', function (event) {
        var form = event.target instanceof HTMLFormElement ? event.target : null;
        if (!form || form.getAttribute('data-confirm-empty') === null) {
            return;
        }
        if (form.querySelectorAll('[data-interval-row]').length === 0) {
            if (!window.confirm(form.getAttribute('data-confirm-empty'))) {
                event.preventDefault();
            }
        }
    });
})();
