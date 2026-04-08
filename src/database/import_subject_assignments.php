<?php

declare(strict_types=1);

/**
 * Maps subject_assignments from evaluation_db dump onto local DB using:
 * - Old subject id -> natural key from subjects.sql (import source)
 * - Old faculty id -> faculty_profiles.id via faculty_profile_id_map.json
 *
 * From host (if MySQL port is forwarded and auth works):
 *   php import_subject_assignments.php
 *
 * From Docker (recommended on Windows when host PHP cannot authenticate):
 *   docker cp /path/subjects.sql tp-app:/tmp/subjects.sql
 *   docker cp "/path/evaluation_db (7).sql" tp-app:/tmp/eval_assignments.sql
 *   docker exec -e DB_HOST=db -e DB_PORT=3306 \
 *     -e SUBJECTS_SQL=/tmp/subjects.sql -e ASSIGNMENTS_SQL=/tmp/eval_assignments.sql \
 *     tp-app php /var/www/database/import_subject_assignments.php
 */

$subjectsDump = getenv('SUBJECTS_SQL') ?: 'C:\\Users\\Jessie S Mahinay\\Downloads\\subjects.sql';
$assignmentsDump = getenv('ASSIGNMENTS_SQL')
    ?: 'C:\\Users\\Jessie S Mahinay\\Downloads\\evaluation_db (7).sql';
$facultyMapPath = __DIR__ . '/../storage/app/faculty_profile_id_map.json';

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3307';
$dbName = getenv('DB_DATABASE') ?: 'teachers_performance';
$dbUser = getenv('DB_USERNAME') ?: 'tp_user';
$dbPass = getenv('DB_PASSWORD') ?: 'secret';

if (! is_readable($subjectsDump)) {
    fwrite(STDERR, "Cannot read subjects dump: {$subjectsDump}\n");
    exit(1);
}
if (! is_readable($assignmentsDump)) {
    fwrite(STDERR, "Cannot read assignments dump: {$assignmentsDump}\n");
    exit(1);
}
if (! is_readable($facultyMapPath)) {
    fwrite(STDERR, "Cannot read faculty map: {$facultyMapPath}\n");
    exit(1);
}

/** @var array<string,int|string> $facultyMap */
$facultyMap = json_decode(file_get_contents($facultyMapPath), true, 512, JSON_THROW_ON_ERROR);

$subjectsContent = file_get_contents($subjectsDump);
if (! preg_match('/INSERT INTO `subjects`[^;]+;/s', $subjectsContent, $m)) {
    fwrite(STDERR, "No subjects INSERT in {$subjectsDump}\n");
    exit(1);
}

// old_subject_id => [code, course, year_level, section, semester, school_year|null]
$subjectByOldId = [];
foreach (preg_split('/\R/', $m[0]) as $line) {
    $line = trim($line);
    if ($line === '' || strpos($line, 'INSERT') === 0) {
        continue;
    }
    if (preg_match('/^\((\d+),\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\d+,\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*\'([^\']*)\',\s*(NULL|\'[^\']*\')/', $line, $g)) {
        $sy = $g[8] === 'NULL' ? null : trim($g[8], "'");
        $subjectByOldId[(int) $g[1]] = [$g[2], $g[3], $g[4], $g[5], $g[6], $g[7], $sy];
    }
}

$assignContent = file_get_contents($assignmentsDump);
if (! preg_match('/INSERT INTO `subject_assignments`[^;]+;/s', $assignContent, $am)) {
    fwrite(STDERR, "No subject_assignments INSERT in {$assignmentsDump}\n");
    exit(1);
}

$pairs = [];
foreach (preg_split('/\R/', $am[0]) as $line) {
    $line = trim($line);
    if ($line === '' || strpos($line, 'INSERT') === 0) {
        continue;
    }
    if (preg_match('/^\(\d+,\s*(\d+),\s*(\d+)\)/', $line, $g)) {
        $pairs[] = [(int) $g[1], (int) $g[2]];
    }
}

$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
$pdo = new PDO($dsn, $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$deptStmt = $pdo->query("SELECT id FROM departments WHERE code = 'CCIS' LIMIT 1");
$ccisId = $deptStmt ? (int) $deptStmt->fetchColumn() : 0;
if ($ccisId < 1) {
    fwrite(STDERR, "CCIS department not found.\n");
    exit(1);
}

$findSubject = $pdo->prepare(
    'SELECT id FROM subjects WHERE department_id = ? AND code = ? AND course = ? AND year_level = ? '
    . 'AND section = ? AND semester = ? AND school_year <=> ? LIMIT 1'
);

$exists = $pdo->prepare('SELECT 1 FROM subject_assignments WHERE subject_id = ? AND faculty_id = ? LIMIT 1');
$insert = $pdo->prepare('INSERT INTO subject_assignments (subject_id, faculty_id) VALUES (?, ?)');

$inserted = 0;
$skipped = 0;
$seen = [];

foreach ($pairs as [$oldSid, $oldFid]) {
    $key = "{$oldSid}:{$oldFid}";
    if (isset($seen[$key])) {
        continue;
    }
    $seen[$key] = true;

    if (! isset($subjectByOldId[$oldSid])) {
        $skipped++;
        continue;
    }
    $mapKey = (string) $oldFid;
    if (! isset($facultyMap[$mapKey])) {
        $skipped++;
        continue;
    }
    $newFid = (int) $facultyMap[$mapKey];

    [$code, $_title, $course, $yl, $sec, $semester, $sy] = $subjectByOldId[$oldSid];

    $findSubject->execute([$ccisId, $code, $course, $yl, $sec, $semester, $sy]);
    $newSid = $findSubject->fetchColumn();
    if ($newSid === false) {
        $skipped++;
        continue;
    }
    $newSid = (int) $newSid;

    $exists->execute([$newSid, $newFid]);
    if ($exists->fetchColumn()) {
        continue;
    }

    $insert->execute([$newSid, $newFid]);
    $inserted++;
}

echo "subject_assignments: inserted {$inserted}, skipped (no subject/faculty/duplicate) {$skipped}\n";
