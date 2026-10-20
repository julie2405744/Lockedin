<?php
/**
 * public/admin/modification-details.php
 * Admin modification detail and review actions — Member 5.
 *
 * GET  : show the original reservation beside the proposal.
 * POST : approve or reject a pending modification (admin only, CSRF protected).
 *
 * The decision itself is made by ReservationService (Member 3). This page only
 * forwards the request and shows the result that the service returned.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Core/Auth.php';
require_once __DIR__ . '/../../app/Core/Csrf.php';
require_once __DIR__ . '/../../app/Core/helpers.php';
require_once __DIR__ . '/../../app/Repositories/AdminReviewRepository.php';
require_once __DIR__ . '/../../app/Services/AdminReviewService.php';

Auth::startSession();
Auth::requireAdmin();

$currentUser = Auth::user();
$adminId     = (int) ($currentUser['id'] ?? 0);
$basePath    = defined('BASE_PATH') ? BASE_PATH : '';

$reviewService = new AdminReviewService(new AdminReviewRepository($pdo), $pdo);

/* ── POST: approve or reject ─────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $modificationId = (int) ($_POST['modification_id'] ?? 0);
    $decision       = trim((string) ($_POST['decision'] ?? ''));

    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
        $_SESSION['admin_flash'] = [
            'type'    => 'danger',
            'title'   => 'Your session expired',
            'message' => 'Nothing was changed. Refresh the page and try again.',
        ];
    } elseif ($modificationId <= 0) {
        $_SESSION['admin_flash'] = [
            'type'    => 'danger',
            'title'   => 'Unknown request',
            'message' => 'Nothing was changed because the request reference was missing.',
        ];
    } elseif ($decision !== 'approve' && $decision !== 'reject') {
        $_SESSION['admin_flash'] = [
            'type'    => 'danger',
            'title'   => 'Unknown action',
            'message' => 'Nothing was changed. Reload the page and try again.',
        ];
    } else {
        $reason = trim((string) ($_POST['reason'] ?? ''));
        $reason = $reason !== '' ? mb_substr($reason, 0, 255) : null;

        $result = $decision === 'approve'
            ? $reviewService->approveModification($modificationId, $adminId)
            : $reviewService->rejectModification($modificationId, $adminId, $reason);

        $_SESSION['admin_flash'] = [
            'type'    => $result['success'] ? 'success' : 'danger',
            'title'   => $result['success']
                ? ($decision === 'approve' ? 'Modification approved' : 'Modification rejected')
                : 'The request was not changed',
            'message' => $result['message'],
        ];
    }

    header('Location: ' . $basePath . '/admin/modification-details.php?id=' . $modificationId);
    exit;
}

/* ── GET: show the request ───────────────────────────────────── */
$modificationId = (int) ($_GET['id'] ?? 0);
$details        = $modificationId > 0 ? $reviewService->getModificationDetails($modificationId) : null;

if ($details === null || empty($details['success'])) {
    $_SESSION['admin_flash'] = [
        'type'    => 'danger',
        'title'   => 'Modification request not found',
        'message' => $details['message'] ?? 'That request does not exist or has been removed.',
    ];
    header('Location: ' . $basePath . '/admin/modifications.php');
    exit;
}

$modification = $details['data']['modification'] ?? [];

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

/* ── Page ────────────────────────────────────────────────────── */
$pageTitle    = 'Modification request #' . (int) ($modification['id'] ?? 0);
$pageSubtitle = 'Compare the original booking with the requested change, then approve or reject it.';
$pageStyles   = '<link rel="stylesheet" href="' . esc($basePath) . '/assets/css/admin-pages.css">'
    . '<link rel="stylesheet" href="' . esc($basePath) . '/assets/css/reservation-forms.css">';
$pageScripts  = '<script src="' . esc($basePath) . '/assets/js/report-filters.js" defer></script>';

ob_start();
require __DIR__ . '/../../app/Modules/Admin/Views/modification-details-view.php';
$content = ob_get_clean();

require __DIR__ . '/../../app/Views/layouts/app-layout.php';
