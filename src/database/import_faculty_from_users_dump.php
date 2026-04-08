<?php

declare(strict_types=1);

/**
 * Import faculty users from legacy users dump into current Laravel schema.
 *
 * Usage inside container:
 * php /var/www/database/import_faculty_from_users_dump.php
 *
 * Optional env:
 * - SOURCE_SQL (default: /tmp/users.sql)
 * - DB_HOST (default: db)
 * - DB_PORT (default: 3306)
 * - DB_DATABASE (default: teachers_performance)
 * - DB_USERNAME (default: tp_user)
 * - DB_PASSWORD (default: secret)
 */

$sourceSql = getenv('SOURCE_SQL') ?: '/tmp/users.sql';

$dbHost = getenv('DB_HOST') ?: 'db';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_DATABASE') ?: 'teachers_performance';
$dbUser = getenv('DB_USERNAME') ?: 'tp_user';
$dbPass = getenv('DB_PASSWORD') ?: 'secret';

if (!is_readable($sourceSql)) {
    fwrite(STDERR, "Cannot read source sql: {$sourceSql}\n");
    exit(1);
}

$content = file_get_contents($sourceSql);
if ($content === false) {
    fwrite(STDERR, "Failed to read source sql: {$sourceSql}\n");
    exit(1);
}

if (!preg_match('/INSERT INTO `users`[^;]+;/s', $content, $match)) {
    fwrite(STDERR, "No INSERT INTO `users` found in source sql.\n");
    exit(1);
}

$rows = [];
if (preg_match_all("/\\((\\d+),\\s*'((?:\\\\'|[^'])*)',\\s*'((?:\\\\'|[^'])*)',\\s*'((?:\\\\'|[^'])*)',\\s*'((?:\\\\'|[^'])*)',\\s*(\\d+),\\s*(NULL|\\d+),\\s*'((?:\\\\'|[^'])*)'\\)/", $match[0], $matches, PREG_SET_ORDER)) {
    foreach ($matches as $m) {
        $role = stripslashes($m[5]);
        if ($role !== 'faculty') {
            continue;
        }

        $rows[] = [
            'name' => stripslashes($m[2]),
            'email' => strtolower(stripslashes($m[3])),
            'password' => stripslashes($m[4]),
            'department_id' => $m[7] === 'NULL' ? null : (int)$m[7],
            'created_at' => stripslashes($m[8]),
        ];
    }
}

if (count($rows) === 0) {
    echo "No faculty rows found in dump.\n";
    exit(0);
}

$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
$pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$validDepartmentIds = [];
foreach ($pdo->query('SELECT id FROM departments') as $deptRow) {
    $validDepartmentIds[(int)$deptRow['id']] = true;
}

$selectUser = $pdo->prepare('SELECT id, department_id FROM users WHERE email = ? LIMIT 1');
$insertUser = $pdo->prepare(
    "INSERT INTO users (name, email, password, role, is_active, department_id, must_change_password, created_at, updated_at)
     VALUES (?, ?, ?, 'faculty', 1, ?, 0, ?, ?)"
);
$updateUser = $pdo->prepare(
    "UPDATE users
     SET name = ?, role = 'faculty', is_active = 1, department_id = COALESCE(?, department_id)
     WHERE id = ?"
);

$selectProfile = $pdo->prepare('SELECT id, department_id FROM faculty_profiles WHERE user_id = ? LIMIT 1');
$insertProfile = $pdo->prepare('INSERT INTO faculty_profiles (user_id, department_id, department_position, created_at) VALUES (?, ?, \'faculty\', ?)');
$updateProfileDept = $pdo->prepare('UPDATE faculty_profiles SET department_id = ? WHERE id = ?');

$insertedUsers = 0;
$updatedUsers = 0;
$insertedProfiles = 0;
$updatedProfiles = 0;

$pdo->beginTransaction();
try {
    foreach ($rows as $row) {
        $deptId = $row['department_id'];
        if ($deptId !== null && !isset($validDepartmentIds[$deptId])) {
            $deptId = null;
        }

        $createdAt = $row['created_at'] ?: date('Y-m-d H:i:s');
        $updatedAt = $createdAt;

        $selectUser->execute([$row['email']]);
        $existingUser = $selectUser->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            $userId = (int)$existingUser['id'];
            $updateUser->execute([$row['name'], $deptId, $userId]);
            $updatedUsers++;
        } else {
            $insertUser->execute([
                $row['name'],
                $row['email'],
                $row['password'],
                $deptId,
                $createdAt,
                $updatedAt,
            ]);
            $userId = (int)$pdo->lastInsertId();
            $insertedUsers++;
        }

        $selectProfile->execute([$userId]);
        $existingProfile = $selectProfile->fetch(PDO::FETCH_ASSOC);

        if ($existingProfile) {
            if ($deptId !== null && (int)$existingProfile['department_id'] !== $deptId) {
                $updateProfileDept->execute([$deptId, (int)$existingProfile['id']]);
                $updatedProfiles++;
            }
        } else {
            $insertProfile->execute([$userId, $deptId, $createdAt]);
            $insertedProfiles++;
        }
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
}

echo "Faculty import done.\n";
echo "Faculty rows in dump: " . count($rows) . "\n";
echo "Users inserted: {$insertedUsers}\n";
echo "Users updated: {$updatedUsers}\n";
echo "Faculty profiles inserted: {$insertedProfiles}\n";
echo "Faculty profiles updated: {$updatedProfiles}\n";
