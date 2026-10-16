<?php
/**
 * AutoPoint — ЛР6: робота з базою даних SQLite через PDO.
 * Файл бази створюється автоматично під час першого звернення.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/** Повертає одне спільне з'єднання PDO і створює таблицю, якщо її ще немає. */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    if (!is_dir(dirname(DB_FILE))) {
        mkdir(dirname(DB_FILE), 0775, true);
    }
    $pdo = new PDO('sqlite:' . DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('CREATE TABLE IF NOT EXISTS responses (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        created_at  TEXT NOT NULL,
        name        TEXT NOT NULL,
        email       TEXT NOT NULL,
        budget      TEXT NOT NULL,
        body        TEXT NOT NULL,
        fuel        TEXT NOT NULL,
        priorities  TEXT NOT NULL,
        model       TEXT NOT NULL,
        file        TEXT NOT NULL
    )');
    return $pdo;
}

/** Зберігає відповідь у таблицю responses і повертає її id. */
function db_insert_response(array $r, string $file): int
{
    $stmt = db()->prepare('INSERT INTO responses
        (created_at, name, email, budget, body, fuel, priorities, model, file)
        VALUES (:created_at, :name, :email, :budget, :body, :fuel, :priorities, :model, :file)');
    $stmt->execute([
        ':created_at' => $r['submitted_at'],
        ':name'       => $r['name'],
        ':email'      => $r['email'],
        ':budget'     => $r['answers']['budget'],
        ':body'       => $r['answers']['body'],
        ':fuel'       => $r['answers']['fuel'],
        ':priorities' => implode(',', $r['answers']['priorities']),
        ':model'      => $r['answers']['model'],
        ':file'       => $file,
    ]);
    return (int) db()->lastInsertId();
}

/** Усі відповіді, найновіші — першими. */
function db_all_responses(): array
{
    return db()->query('SELECT * FROM responses ORDER BY id DESC')->fetchAll();
}

function db_find_response(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM responses WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function db_delete_response(int $id): void
{
    $stmt = db()->prepare('DELETE FROM responses WHERE id = ?');
    $stmt->execute([$id]);
}
