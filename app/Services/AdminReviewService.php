<?php
/**
 * app/Services/AdminReviewService.php
 * Admin review operations for reservations and modification requests — Member 5.
 *
 * Ownership: Member 5 (admin dashboard and reviews). See docs/member-tasks.md §8.
 *
 * Reads come from AdminReviewRepository so that the queue and the detail pages
 * always share one shape.
 *
 * Every decision (approve / reject) is delegated to ReservationService, which
 * is owned by Member 3 and is the single authority for reservation rules —
 * see docs/api-contracts.md §3.3. This service never writes a reservation
 * status itself and never repeats a booking rule.
 *
 * Until Member 3's ReservationService lands, the review actions report that
 * the review engine is not connected and change nothing. That keeps the
 * promise "never claim a change succeeded before the backend confirms it"
 * even during the integration window.
 */

class AdminReviewService
{
    private AdminReviewRepository $repo;
    private ?PDO $pdo;

    public function __construct(AdminReviewRepository $repo, ?PDO $pdo = null)
    {
        $this->repo = $repo;
        $this->pdo  = $pdo;
    }

    /* ── Queues ──────────────────────────────────────────────────── */

    /**
     * The reservation queue an administrator works through.
     *
     * @param array $filters Optional 'status' (default 'pending'), 'room_id', 'limit'.
     * @return array{success:bool,message:string,data:array|null}
     */
    public function getPendingReservations(array $filters = []): array
    {
        $rows = $this->repo->findReservationsForReview($filters);

        return [
            'success' => true,
            'message' => '',
            'data'    => [
                'rows' => $rows,
            ],
        ];
    }

    /**
     * The modification queue an administrator works through.
     *
     * @param array $filters Optional 'status' (default 'pending'), 'limit'.
     * @return array{success:bool,message:string,data:array|null}
     */
    public function getPendingModifications(array $filters = []): array
    {
        $rows = $this->repo->findModificationsForReview($filters);

        return [
            'success' => true,
            'message' => '',
            'data'    => [
                'rows' => $rows,
            ],
        ];
    }

    /* ── Details ─────────────────────────────────────────────────── */

    /**
     * A reservation with everything the review screen needs.
     *
     * @return array{success:bool,message:string,data:array|null}
     */
    public function getReservationDetails(int $reservationId): ?array
    {
        $reservation = $this->repo->findReservationById($reservationId);

        if ($reservation === null) {
            return [
                'success' => false,
                'message' => 'That reservation does not exist or has been removed.',
                'data'    => null,
            ];
        }

        $modification = $this->repo->findLatestModificationForReservation($reservationId);

        return [
            'success' => true,
            'message' => '',
            'data'    => [
                'reservation' => $reservation,
                'modification' => $modification,
            ],
        ];
    }

    /**
     * A modification request with the original reservation and the proposal.
     *
     * @return array{success:bool,message:string,data:array|null}
     */
    public function getModificationDetails(int $modificationId): ?array
    {
        $modification = $this->repo->findModificationById($modificationId);

        if ($modification === null) {
            return [
                'success' => false,
                'message' => 'That modification request does not exist or has been removed.',
                'data'    => null,
            ];
        }

        return [
            'success' => true,
            'message' => '',
            'data'    => [
                'modification' => $modification,
            ],
        ];
    }

    /* ── Decisions (delegated to ReservationService) ─────────────── */

    /**
     * Approve a pending reservation.
     *
     * @return array{success:bool,message:string,data:array|null}
     */
    public function approveReservation(int $reservationId, int $adminId): array
    {
        return $this->callReservationService('approveReservation', [$reservationId, $adminId]);
    }

    /**
     * Reject a pending reservation.
     *
     * @return array{success:bool,message:string,data:array|null}
     */
    public function rejectReservation(int $reservationId, int $adminId, ?string $reason = null): array
    {
        return $this->callReservationService('rejectReservation', [$reservationId, $adminId, $reason]);
    }

    /**
     * Approve a pending modification request.
     *
     * @return array{success:bool,message:string,data:array|null}
     */
    public function approveModification(int $modificationId, int $adminId): array
    {
        return $this->callReservationService('approveModification', [$modificationId, $adminId]);
    }

    /**
     * Reject a pending modification request.
     *
     * @return array{success:bool,message:string,data:array|null}
     */
    public function rejectModification(int $modificationId, int $adminId, ?string $reason = null): array
    {
        return $this->callReservationService('rejectModification', [$modificationId, $adminId, $reason]);
    }

    /**
     * True when Member 3's reservation engine is available.
     */
    public function reviewEngineAvailable(): bool
    {
        return class_exists('ReservationService');
    }

    /* ── Internals ───────────────────────────────────────────────── */

    /**
     * Call a method on ReservationService and normalise its result.
     *
     * @param string $method Method name from docs/api-contracts.md §3.3.
     * @param array  $args   Positional arguments for that method.
     * @return array{success:bool,message:string,data:array|null}
     */
    private function callReservationService(string $method, array $args): array
    {
        $service = $this->makeReservationService();

        if ($service === null) {
            return [
                'success' => false,
                'message' => 'The reservation review engine is not connected yet, so nothing was changed. '
                    . 'ReservationService is owned by Member 3 — see docs/api-contracts.md §3.3.',
                'data'    => null,
            ];
        }

        if (!method_exists($service, $method)) {
            return [
                'success' => false,
                'message' => 'The reservation review engine does not support this action yet, so nothing was changed.',
                'data'    => null,
            ];
        }

        try {
            $result = $service->{$method}(...$args);
        } catch (Throwable $e) {
            error_log('Admin review action failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'The review action could not be completed. Nothing was changed.',
                'data'    => null,
            ];
        }

        return $this->normalizeResult($result, $method);
    }

    /**
     * Build the shared reservation service when it is installed.
     */
    private function makeReservationService(): ?object
    {
        if (!class_exists('ReservationService')) {
            return null;
        }

        try {
            $reflection = new ReflectionClass('ReservationService');
            $constructor = $reflection->getConstructor();

            if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
                return new ReservationService();
            }

            return new ReservationService($this->pdo);
        } catch (Throwable $e) {
            error_log('ReservationService could not be instantiated: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Normalise whatever the service returned into the shared result shape.
     *
     * @param mixed  $result
     * @param string $method
     * @return array{success:bool,message:string,data:array|null}
     */
    private function normalizeResult($result, string $method): array
    {
        if (is_bool($result)) {
            return [
                'success' => $result,
                'message' => $result ? 'The request was updated.' : 'The request could not be updated.',
                'data'    => null,
            ];
        }

        if (!is_array($result)) {
            return [
                'success' => false,
                'message' => 'The review action returned an unexpected result, so nothing was changed.',
                'data'    => null,
            ];
        }

        $success = (bool) ($result['success'] ?? false);
        $message = trim((string) ($result['message'] ?? ''));
        $data    = $result['data'] ?? null;

        if ($message === '') {
            $message = $success
                ? 'The request was updated.'
                : 'The request could not be updated.';
        }

        unset($result['success'], $result['message'], $result['data']);

        return [
            'success' => $success,
            'message' => $message,
            'data'    => is_array($data) ? $data + $result : $result,
        ];
    }
}
