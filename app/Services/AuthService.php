<?php
/**
 * app/Services/AuthService.php
 * Registration and login business logic — Member 1.
 *
 * All authentication operations go through this service.
 * Other modules must not implement their own login/registration logic.
 */

require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Repositories/UserRepository.php';

class AuthService
{
    private UserRepository $userRepo;

    public function __construct(PDO $pdo)
    {
        $this->userRepo = new UserRepository($pdo);
    }

    /**
     * Register a new student account.
     *
     * Public registration always creates a student.
     * The role field is never accepted from external input.
     *
     * @return array ['success' => bool, 'error' => ?string]
     *               error values: 'email_taken' | 'db_error'
     */
    public function register(string $name, string $email, string $password): array
    {
        // Guard: email uniqueness
        if ($this->userRepo->findByEmail($email) !== null) {
            return ['success' => false, 'error' => 'email_taken'];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $id = $this->userRepo->create([
            'name'          => $name,
            'email'         => $email,
            'password_hash' => $hash,
            'role'          => 'student', // always student; never from input
        ]);

        if ($id === null) {
            return ['success' => false, 'error' => 'db_error'];
        }

        return ['success' => true, 'error' => null];
    }

    /**
     * Attempt to authenticate with email and password.
     *
     * On success the session is populated via Auth::setUser().
     *
     * @return array ['success' => bool]
     */
    public function login(string $email, string $password): array
    {
        $user = $this->userRepo->findByEmail($email);

        if ($user === null) {
            // Perform a dummy verify to keep timing consistent
            password_verify($password, '$2y$10$invalid.hash.placeholder.xxxxxxxxxxxxx');
            return ['success' => false];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false];
        }

        // Session is set here — ID regenerated inside Auth::setUser()
        Auth::setUser([
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ]);

        return ['success' => true];
    }

    /**
     * Log out the current user.
     */
    public function logout(): void
    {
        Auth::logout();
    }
}
