/* =============================================================
   InFocus — Admin pages module JS — Member 5
   Vanilla JavaScript only.

   Covers the small amount of usability the admin screens need:
     1. A confirmation step before a review action is submitted.
     2. A visible "working" state on the button that was pressed.
     3. Quick reporting ranges that fill the date filters in place.

   No report value is ever calculated here — the numbers always come
   from ReportService on the server.
   ============================================================= */

(function () {
    'use strict';

    /* ── 1. Confirmation before a review action ────────────────── */

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

    /* ── 2. Submit state on the pressed button ─────────────────── */

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || !(form instanceof HTMLFormElement)) {
            return;
        }

        var button = form.querySelector('[data-submit-state]');
        if (!button || button.disabled) {
            return;
        }

        var label = button.getAttribute('data-submit-state');
        button.setAttribute('aria-disabled', 'true');
        button.disabled = true;

        if (label) {
            button.textContent = label;
        }
    });

    /* ── 3. Quick reporting ranges ─────────────────────────────── */

    function pad(number) {
        return number < 10 ? '0' + number : String(number);
    }

    function toInputValue(date) {
        return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
    }

    document.addEventListener('click', function (event) {
        var target = event.target instanceof Element ? event.target : null;
        if (!target) {
            return;
        }

        var trigger = target.closest('[data-range-days]');
        if (!trigger) {
            return;
        }

        var days = parseInt(trigger.getAttribute('data-range-days'), 10);
        if (isNaN(days) || days < 1) {
            return;
        }

        var scope = trigger.getAttribute('data-range-scope') || 'report-filters';
        var form = document.querySelector('[data-range-form="' + scope + '"]');
        if (!form) {
            return;
        }

        var toField   = form.querySelector('[name="to"]');
        var fromField = form.querySelector('[name="from"]');
        if (!toField || !fromField) {
            return;
        }

        var to   = new Date();
        var from = new Date();
        from.setDate(to.getDate() - (days - 1));

        toField.value   = toInputValue(to);
        fromField.value = toInputValue(from);

        var active = form.querySelectorAll('[data-range-days]');
        for (var i = 0; i < active.length; i++) {
            active[i].removeAttribute('aria-pressed');
        }
        trigger.setAttribute('aria-pressed', 'true');
    });
})();
