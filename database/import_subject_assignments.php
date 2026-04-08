<?php

declare(strict_types=1);

/**
 * Delegates to Laravel app copy (mounted in Docker at /var/www/database).
 *
 * Host: php database/import_subject_assignments.php
 * Docker: see docblock in src/database/import_subject_assignments.php
 */
require dirname(__DIR__) . '/src/database/import_subject_assignments.php';
