/**
 * InFocus — navigation.js
 * Mobile navigation, user-menu dropdown, and dialog handling.
 * Member 1 ownership. No framework dependencies.
 */

(function () {
    'use strict';

    /* ── Mobile navigation ──────────────────────────────────────── */
    const mobileToggle = document.getElementById('mobile-nav-toggle');
    const mobileNav    = document.getElementById('mobile-nav');
    const mobileClose  = document.getElementById('mobile-nav-close');
    const backdrop     = document.getElementById('mobile-nav-backdrop');
    let lastFocusedElement = null;

    function openMobileNav() {
        if (!mobileNav) return;
        lastFocusedElement = document.activeElement;
        mobileNav.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        if (mobileToggle) mobileToggle.setAttribute('aria-expanded', 'true');
        if (mobileClose) mobileClose.focus();
    }

    function closeMobileNav() {
        if (!mobileNav || !mobileNav.classList.contains('is-open')) return;
        mobileNav.classList.remove('is-open');
        document.body.style.overflow = '';
        if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
            lastFocusedElement.focus();
        } else if (mobileToggle) {
            mobileToggle.setAttribute('aria-expanded', 'false');
        }
        if (mobileToggle) mobileToggle.setAttribute('aria-expanded', 'false');
        lastFocusedElement = null;
    }

    if (mobileToggle) mobileToggle.addEventListener('click', openMobileNav);
    if (mobileClose)  mobileClose.addEventListener('click', closeMobileNav);
    if (backdrop)     backdrop.addEventListener('click', closeMobileNav);

    // Close on Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeMobileNav();
            closeUserMenu();
        }
    });

    // Close mobile nav when a nav link is clicked
    if (mobileNav) {
        mobileNav.querySelectorAll('.mobile-nav__link').forEach(function (link) {
            link.addEventListener('click', closeMobileNav);
        });
    }

    /* ── User menu dropdown ─────────────────────────────────────── */
    const userMenuToggle   = document.getElementById('user-menu-toggle');
    const userMenuDropdown = document.getElementById('user-menu-dropdown');

    function openUserMenu() {
        if (!userMenuDropdown) return;
        userMenuDropdown.classList.add('is-open');
        if (userMenuToggle) userMenuToggle.setAttribute('aria-expanded', 'true');
    }

    function closeUserMenu() {
        if (!userMenuDropdown) return;
        userMenuDropdown.classList.remove('is-open');
        if (userMenuToggle) userMenuToggle.setAttribute('aria-expanded', 'false');
    }

    if (userMenuToggle) {
        userMenuToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            userMenuDropdown.classList.contains('is-open') ? closeUserMenu() : openUserMenu();
        });
    }

    // Close when clicking outside
    document.addEventListener('click', function (e) {
        if (userMenuDropdown && !userMenuDropdown.contains(e.target) && e.target !== userMenuToggle) {
            closeUserMenu();
        }
    });

    /* ── Confirmation dialogs ───────────────────────────────────── */
    // Usage: <button data-confirm-dialog="dialog-id">Delete</button>
    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('[data-confirm-dialog]');
        if (!trigger) return;
        const dialogId = trigger.getAttribute('data-confirm-dialog');
        const dialog   = document.getElementById(dialogId);
        if (!dialog) return;
        e.preventDefault();
        dialog.removeAttribute('hidden');
        // Focus first focusable element
        const focusable = dialog.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (focusable) focusable.focus();
    });

    document.addEventListener('click', function (e) {
        const cancelBtn = e.target.closest('[data-dialog-cancel]');
        if (!cancelBtn) return;
        const dialogId = cancelBtn.getAttribute('data-dialog-cancel');
        const dialog   = dialogId ? document.getElementById(dialogId) : cancelBtn.closest('.dialog-overlay');
        if (dialog) dialog.setAttribute('hidden', '');
    });

    /* ── Flash message auto-dismiss ─────────────────────────────── */
    document.querySelectorAll('[data-auto-dismiss]').forEach(function (el) {
        const delay = parseInt(el.getAttribute('data-auto-dismiss'), 10) || 4000;
        setTimeout(function () {
            el.style.transition = 'opacity 0.4s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 400);
        }, delay);
    });

})();
