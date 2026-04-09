<?php
/**
 * Migration script: normalize all tables in citas_medicas to utf8mb4_unicode_ci
 *
 * Fixes "Illegal mix of collations" errors caused by tables created with the
 * server default (utf8mb4_0900_ai_ci) instead of the application collation
 * (utf8mb4_unicode_ci).
 *
 * Safe to run multiple times — tables already on the correct collation are
 * reported as verified and left untouched by MySQL.
 */

$db_host = getenv('MYSQLHOST')     ?: '127.0.0.1';
$db_port = getenv('MYSQLPORT')     ?: '3306';
$db_user = getenv('MYSQLUSER')     ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_name = getenv('MYSQLDATABASE') ?: 'citas_medicas';

$target_charset   = 'utf8mb4';
$target_collation = 'utf8mb4_unicode_ci';

// ── Helper ────────────────────────────────────────────────────────────────────
function log_msg(string $msg): void
{
    $ts = date('Y-m-d H:i:s');
    echo "[{$ts}] {$msg}" . PHP_EOL;
}

// ── Connect ───────────────────────────────────────────────────────────────────
log_msg("Connecting to {$db_host}:{$db_port} / {$db_name} …");

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 30,
    ]);
    log_msg("Connected successfully.");
} catch (PDOException $e) {
    log_msg("ERROR: Could not connect to database — " . $e->getMessage());
    exit(1);
}

// ── Tables to migrate ─────────────────────────────────────────────────────────
// Only the tables that were created without an explicit COLLATE clause and
// therefore inherited the server default (utf8mb4_0900_ai_ci).
$tables_to_migrate = ['medicos', 'citas'];

// ── Run ALTER TABLE ───────────────────────────────────────────────────────────
$errors = 0;

foreach ($tables_to_migrate as $table) {
    $sql = "ALTER TABLE `{$table}` CONVERT TO CHARACTER SET {$target_charset} COLLATE {$target_collation}";
    log_msg("Migrating table `{$table}` → {$target_collation} …");
    try {
        $pdo->exec($sql);
        log_msg("  ✓ `{$table}` converted successfully.");
    } catch (PDOException $e) {
        log_msg("  ✗ Failed to convert `{$table}`: " . $e->getMessage());
        $errors++;
    }
}

// ── Verify all tables ─────────────────────────────────────────────────────────
log_msg("Verifying collations in database `{$db_name}` …");

$stmt = $pdo->prepare("
    SELECT TABLE_NAME, TABLE_COLLATION
    FROM   information_schema.TABLES
    WHERE  TABLE_SCHEMA = :db
    ORDER  BY TABLE_NAME
");
$stmt->execute([':db' => $db_name]);
$tables = $stmt->fetchAll();

$mismatched = [];
foreach ($tables as $row) {
    $name      = $row['TABLE_NAME'];
    $collation = $row['TABLE_COLLATION'];
    $ok        = ($collation === $target_collation);
    $icon      = $ok ? '✓' : '✗';
    log_msg("  {$icon} {$name}: {$collation}");
    if (!$ok) {
        $mismatched[] = $name;
    }
}

// ── Summary ───────────────────────────────────────────────────────────────────
echo PHP_EOL;
if ($errors === 0 && empty($mismatched)) {
    log_msg("Migration complete — all tables use {$target_collation}.");
    exit(0);
} else {
    if ($errors > 0) {
        log_msg("WARNING: {$errors} ALTER TABLE statement(s) failed (see above).");
    }
    if (!empty($mismatched)) {
        log_msg("WARNING: " . count($mismatched) . " table(s) still have a different collation: "
            . implode(', ', $mismatched));
    }
    // Exit non-zero so the Dockerfile entrypoint can surface the issue,
    // but do NOT hard-stop the container — the app may still be usable.
    exit(1);
}
