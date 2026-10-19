<?php
/**
 * app/Repositories/UserRepository.php
 * Database access for the users table — Member 1.
 *
 * All queries use PDO prepared statements.
 * Other modules must not query the users table directly.
 */

class UserRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Find a user by their email address.
     *
     * @return array|null Row as associative array, or null if not found.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, email, password_hash, role, created_at
             FROM   users
             WHERE  email = :email
             LIMIT  1'
        );
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    /**
     * Find a user by their ID.
     *
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, email, role, created_at
             FROM   users
             WHERE  id = :id
             LIMIT  1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    /**
     * Insert a new user record.
     *
     * @param array $data Keys: name, email, password_hash, role
     * @return int|null  The new user ID, or null on failure.
     */
    public function create(array $data): ?int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role)
             VALUES (:name, :email, :password_hash, :role)'
        );

        $ok = $stmt->execute([
            ':name'          => $data['name'],
            ':email'         => $data['email'],
            ':password_hash' => $data['password_hash'],
            ':role'          => $data['role'],
        ]);

        if (!$ok) {
            return null;
        }

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update a user's name (basic profile update).
     *
     * @return bool
     */
    public function updateName(int $id, string $name): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET name = :name WHERE id = :id'
        );
        return $stmt->execute([':name' => $name, ':id' => $id]);
    }

    /**
     * Update a user's hashed password.
     *
     * @return bool
     */
    public function updatePassword(int $id, string $passwordHash): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET password_hash = :hash WHERE id = :id'
        );
        return $stmt->execute([':hash' => $passwordHash, ':id' => $id]);
    }
}
