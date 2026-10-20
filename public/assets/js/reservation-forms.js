
(function () {
    'use strict';

    /* ── Small date/duration helpers ─────────────────────────── */

    function parseLocal(value) {
        if (!value) {
            return null;
        }
        var date = new Date(value);
        return isNaN(date.getTime()) ? null : date;
    }

    function durationText(start, end) {
        var from = parseLocal(start);
        var to   = parseLocal(end);
        if (!from || !to || to <= from) {
            return '';
        }
        var minutes = Math.round((to - from) / 60000);
        var hours   = Math.floor(minutes / 60);
        var mins    = minutes % 60;
        if (hours && mins) {
            return hours + 'h ' + mins + 'm';
        }
        if (hours) {
            return hours + 'h';
        }
        return mins + 'm';
    }

    function formatDateTime(value) {
        var date = parseLocal(value);
        if (!date) {
            return '—';
        }
        try {
            return date.toLocaleString(undefined, {
                weekday: 'short',
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (error) {
            return value;
        }
    }

    function roomLabelFromSelect(select) {
        if (!select) {
            return '';
        }
        var option = select.options[select.selectedIndex];
        if (!option || option.value === '') {
            return '';
        }
        return option.getAttribute('data-room-name') || option.textContent.trim();
    }

    /* ── Live booking / modification summary ─────────────────── */

    function initSummary() {
        var summaries = document.querySelectorAll('[data-booking-summary]');
        if (!summaries.length) {
            return;
        }

        Array.prototype.forEach.call(summaries, function (summary) {
            var formId = summary.getAttribute('data-booking-summary-form');
            var scope  = formId ? document.getElementById(formId) : null;
            if (!scope) {
                scope = document;
            }

            var startInput  = scope.querySelector('[data-summary-start]');
            var endInput    = scope.querySelector('[data-summary-end]');
            var roomSelect  = scope.querySelector('[data-summary-room-select]');
            var roomOut     = summary.querySelector('[data-summary-room]');
            var startOut    = summary.querySelector('[data-summary-start-out]');
            var endOut      = summary.querySelector('[data-summary-end-out]');
            var durationOut = summary.querySelector('[data-summary-duration]');
            var durationErr = scope.querySelector('[data-duration-hint]');

            function update() {
                if (roomOut) {
                    var label = roomSelect
                        ? roomLabelFromSelect(roomSelect)
                        : (roomOut.getAttribute('data-room-name') || '');
                    if (roomSelect) {
                        roomOut.textContent = label || 'Select a room';
                    }
                }

                var start = startInput ? startInput.value : '';
                var end   = endInput ? endInput.value : '';

                if (startOut) {
                    startOut.textContent = start ? formatDateTime(start) : '—';
                }
                if (endOut) {
                    endOut.textContent = end ? formatDateTime(end) : '—';
                }

                var duration = durationText(start, end);
                var invalid  = Boolean(start) && Boolean(end) && !duration;

                if (durationOut) {
                    durationOut.textContent = duration || '—';
                    durationOut.classList.toggle('reservation-duration--invalid', invalid);
                }

                if (durationErr) {
                    if (invalid) {
                        durationErr.textContent = 'The end time must be after the start time.';
                        durationErr.className = 'form-error';
                        durationErr.hidden = false;
                    } else {
                        durationErr.hidden = true;
                    }
                }
            }

            ['input', 'change'].forEach(function (eventName) {
                if (startInput) {
                    startInput.addEventListener(eventName, update);
                }
                if (endInput) {
                    endInput.addEventListener(eventName, update);
                }
                if (roomSelect) {
                    roomSelect.addEventListener(eventName, update);
                }
            });

            update();
        });
    }

    /* ── Confirmation for destructive actions ────────────────── */

    function initConfirm() {
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
    }

    /* ── Submit-button busy state ────────────────────────────── */

    function initSubmitState() {
        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!form || !(form instanceof HTMLFormElement)) {
                return;
            }
            if (event.defaultPrevented) {
                return;
            }

            var submit = form.querySelector('[data-submit-state]');
            if (!submit) {
                return;
            }

            var busyText = submit.getAttribute('data-submit-state');
            submit.setAttribute('aria-busy', 'true');
            submit.setAttribute('aria-disabled', 'true');
            if (busyText) {
                submit.textContent = busyText;
            }
            // Allow the submission to proceed; disabling here would drop the value.
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initSummary();
        initConfirm();
        initSubmitState();
    });
})();
