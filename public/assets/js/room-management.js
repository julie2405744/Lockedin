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
