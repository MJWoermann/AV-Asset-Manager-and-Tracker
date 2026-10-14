<?php

namespace App\Services;

use App\Enums\FileSourceType;
use App\Models\Asset;
use App\Models\AssetCustomFieldValue;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldSet;
use App\Models\File;
use App\Models\FileLink;
use App\Models\ItemType;
use App\Models\Location;
use App\Models\ScanSession;
use App\Models\Setting;
use App\Models\UnknownScan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Throwable;

class AppBackupService
{
    public const LAST_RUN_KEY = 'backup.last_run';

    public function __construct(
        private FileAttachmentService $attachments,
        private AttachmentDiskMigrator $diskMigrator,
    ) {}

    /**
     * @return array{path: string, disk: string, manifest: array<string, mixed>}
     */
    public function backup(?string $disk = null, ?int $keep = null): array
    {
        $disk = $this->resolveBackupDisk($disk);
        $stamp = now()->format('Y-m-d_His');
        $base = 'backups/'.$stamp;

        $manifest = [
            'app' => config('app.name'),
            'app_version' => config('app.version') ?: $this->gitSha(),
            'created_at' => now()->toIso8601String(),
            'disk' => $disk,
            'attachments_disk' => config('attachments.disk'),
            'database_driver' => config('database.default'),
            'tables' => [],
        ];

        $sqlWritten = $this->writeDatabaseDump($disk, $base.'/database.sql.gz');
        $manifest['database_dump'] = $sqlWritten ? 'database.sql.gz' : null;

        if (! $sqlWritten) {
            $manifest['tables'] = $this->exportAllTablesJson($disk, $base.'/data/tables');
        }

        $this->putJson($disk, $base.'/data/settings.json', Setting::query()->orderBy('key')->get()->toArray());
        $this->putJson($disk, $base.'/data/roles_permissions.json', [
            'roles' => Role::query()->with('permissions')->orderBy('name')->get()->toArray(),
            'permissions' => Permission::query()->orderBy('name')->get()->toArray(),
        ]);
        $this->putJson($disk, $base.'/data/locations.json', Location::query()->orderBy('id')->get()->toArray());
        $this->putJson($disk, $base.'/data/item_types.json', ItemType::query()->with('fieldSets')->orderBy('id')->get()->toArray());
        $this->putJson($disk, $base.'/data/custom_field_sets.json', [
            'sets' => CustomFieldSet::query()->orderBy('id')->get()->toArray(),
            'definitions' => CustomFieldDefinition::query()->orderBy('id')->get()->toArray(),
        ]);
        $this->putJson($disk, $base.'/data/assets.json', [
            'assets' => Asset::query()->orderBy('id')->get()->toArray(),
            'custom_field_values' => AssetCustomFieldValue::query()->orderBy('id')->get()->toArray(),
        ]);
        $this->putJson($disk, $base.'/data/lists.json', [
            'lists' => AssetList::query()->orderBy('id')->get()->toArray(),
            'items' => AssetListItem::query()->orderBy('id')->get()->toArray(),
        ]);
        $this->putJson($disk, $base.'/data/scan_sessions.json', [
            'sessions' => ScanSession::query()->orderBy('id')->get()->toArray(),
            'unknown_scans' => UnknownScan::query()->orderBy('id')->get()->toArray(),
        ]);

        $files = File::query()->orderBy('id')->get();
        $links = FileLink::query()->orderBy('id')->get();
        $this->putJson($disk, $base.'/data/files_index.json', [
            'files' => $files->toArray(),
            'file_links' => $links->toArray(),
        ]);

        $mirrored = 0;
        foreach ($files->filter(fn (File $file): bool => $file->isUpload()) as $file) {
            foreach (array_filter([$file->path, $file->thumbnail_path]) as $path) {
                $sourceDisk = $file->disk ?: (string) config('attachments.disk', 'local');
                if (! $path || ! Storage::disk($sourceDisk)->exists($path)) {
                    continue;
                }

                $this->streamMirror($sourceDisk, $disk, $path, $base.'/attachments/'.$path);
                $mirrored++;
            }
        }

        $manifest['attachments_mirrored'] = $mirrored;
        $this->putJson($disk, $base.'/manifest.json', $manifest);

        if ($keep !== null && $keep > 0) {
            $this->pruneOldBackups($disk, $keep);
        }

        Setting::putValue(self::LAST_RUN_KEY, [
            'path' => $base,
            'disk' => $disk,
            'finished_at' => now()->toIso8601String(),
            'attachments_mirrored' => $mirrored,
            'database_dump' => $manifest['database_dump'],
        ]);

        return [
            'path' => $base,
            'disk' => $disk,
            'manifest' => $manifest,
        ];
    }

    /**
     * Restore SQL (or JSON table pack) then re-import attachment blobs onto ATTACHMENTS_DISK.
     *
     * @return array{path: string, disk: string, attachments_restored: int}
     */
    public function restore(string $backupPath, ?string $disk = null): array
    {
        $disk = $this->resolveBackupDisk($disk);
        $backupPath = trim($backupPath, '/');

        if (! Storage::disk($disk)->exists($backupPath.'/manifest.json')) {
            throw new RuntimeException("Backup manifest not found at [{$backupPath}] on disk [{$disk}].");
        }

        $sqlPath = $backupPath.'/database.sql.gz';
        if (Storage::disk($disk)->exists($sqlPath)) {
            $this->restoreSqlDump($disk, $sqlPath);
        } elseif (Storage::disk($disk)->exists($backupPath.'/data/tables')) {
            $this->restoreTablesJson($disk, $backupPath.'/data/tables');
        } else {
            throw new RuntimeException('Backup has neither database.sql.gz nor data/tables.');
        }

        $targetDisk = (string) config('attachments.disk', 'local');
        $this->diskMigrator->assertAllowedDisk($targetDisk);

        $restored = 0;
        $filesIndex = $this->readJson($disk, $backupPath.'/data/files_index.json');
        foreach ($filesIndex['files'] ?? [] as $row) {
            if (($row['source_type'] ?? null) !== FileSourceType::Upload->value) {
                continue;
            }

            foreach (array_filter([$row['path'] ?? null, $row['thumbnail_path'] ?? null]) as $path) {
                $source = $backupPath.'/attachments/'.$path;
                if (! Storage::disk($disk)->exists($source)) {
                    continue;
                }

                $this->streamMirror($disk, $targetDisk, $source, $path);
                $restored++;
            }
        }

        return [
            'path' => $backupPath,
            'disk' => $disk,
            'attachments_restored' => $restored,
        ];
    }

    public function resolveBackupDisk(?string $disk = null): string
    {
        $disk = $disk ?: (string) config('attachments.backup_disk');

        if ($disk === '') {
            throw new InvalidArgumentException('BACKUP_DISK is not configured. Pass --disk= or set BACKUP_DISK.');
        }

        $this->diskMigrator->assertAllowedDisk($disk);

        return $disk;
    }

    protected function writeDatabaseDump(string $disk, string $remotePath): bool
    {
        $driver = config('database.default');
        $tmp = tempnam(sys_get_temp_dir(), 'avdump');

        if ($tmp === false) {
            return false;
        }

        $sqlPath = $tmp.'.sql';
        @unlink($tmp);

        try {
            $ok = match ($driver) {
                'sqlite' => $this->dumpSqlite($sqlPath),
                'mysql', 'mariadb' => $this->dumpMysql($sqlPath),
                default => false,
            };

            if (! $ok || ! is_file($sqlPath)) {
                return false;
            }

            $gzPath = $sqlPath.'.gz';
            $this->gzipFile($sqlPath, $gzPath);
            $this->putLocalFile($disk, $remotePath, $gzPath);

            return true;
        } finally {
            @unlink($sqlPath);
            @unlink($sqlPath.'.gz');
        }
    }

    protected function dumpSqlite(string $sqlPath): bool
    {
        $database = (string) config('database.connections.sqlite.database');

        if ($database === '' || ! is_file($database)) {
            return false;
        }

        $sqlite3 = $this->findBinary('sqlite3');
        if ($sqlite3 !== null) {
            $result = Process::run([$sqlite3, $database, '.dump']);
            if ($result->successful()) {
                file_put_contents($sqlPath, $result->output());

                return true;
            }
        }

        return $this->dumpSqliteWithPdo($database, $sqlPath);
    }

    protected function dumpSqliteWithPdo(string $database, string $sqlPath): bool
    {
        try {
            $pdo = new \PDO('sqlite:'.$database);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $sql = "PRAGMA foreign_keys=OFF;\nBEGIN;\n";

            $tables = $pdo->query(
                "SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
            )->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($tables as $table) {
                $sql .= $table['sql'].";\n";
                $rows = $pdo->query('SELECT * FROM '.$this->quoteIdent($table['name']));
                while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                    $cols = array_map(fn ($c) => $this->quoteIdent($c), array_keys($row));
                    $vals = array_map(fn ($v) => $this->sqlLiteral($v), array_values($row));
                    $sql .= 'INSERT INTO '.$this->quoteIdent($table['name']).' ('.implode(', ', $cols).') VALUES ('.implode(', ', $vals).");\n";
                }
            }

            $sql .= "COMMIT;\n";
            file_put_contents($sqlPath, $sql);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    protected function dumpMysql(string $sqlPath): bool
    {
        $mysqldump = $this->findBinary('mysqldump');
        if ($mysqldump === null) {
            return false;
        }

        $connection = config('database.connections.'.config('database.default'));
        $result = Process::env([
            'MYSQL_PWD' => (string) ($connection['password'] ?? ''),
        ])->run([
            $mysqldump,
            '-h', (string) ($connection['host'] ?? '127.0.0.1'),
            '-P', (string) ($connection['port'] ?? 3306),
            '-u', (string) ($connection['username'] ?? ''),
            '--single-transaction',
            '--routines',
            '--triggers',
            (string) ($connection['database'] ?? ''),
        ]);

        if (! $result->successful()) {
            return false;
        }

        file_put_contents($sqlPath, $result->output());

        return true;
    }

    /**
     * @return list<string>
     */
    protected function exportAllTablesJson(string $disk, string $directory): array
    {
        $tables = [];
        $schema = DB::connection()->getSchemaBuilder();

        foreach ($schema->getTableListing() as $table) {
            $name = Str::afterLast($table, '.');
            $rows = DB::table($name)->orderBy($this->firstColumn($name))->get()->map(fn ($row) => (array) $row)->all();
            $this->putJson($disk, $directory.'/'.$name.'.json', $rows);
            $tables[] = $name;
        }

        return $tables;
    }

    protected function restoreSqlDump(string $disk, string $remoteGzPath): void
    {
        $tmpGz = tempnam(sys_get_temp_dir(), 'avrest');
        $sqlPath = $tmpGz.'.sql';
        @unlink($tmpGz);

        try {
            file_put_contents($tmpGz.'.gz', Storage::disk($disk)->get($remoteGzPath));
            $this->gunzipFile($tmpGz.'.gz', $sqlPath);
            $sql = file_get_contents($sqlPath);

            if ($sql === false) {
                throw new RuntimeException('Unable to read restored SQL dump.');
            }

            DB::unprepared($sql);
        } finally {
            @unlink($tmpGz.'.gz');
            @unlink($sqlPath);
        }
    }

    protected function restoreTablesJson(string $disk, string $directory): void
    {
        $files = Storage::disk($disk)->files($directory);
        sort($files);

        $driver = config('database.default');

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF');
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        try {
            foreach ($files as $file) {
                $table = pathinfo($file, PATHINFO_FILENAME);
                $rows = $this->readJson($disk, $file);
                DB::table($table)->delete();
                foreach (array_chunk($rows, 100) as $chunk) {
                    if ($chunk !== []) {
                        DB::table($table)->insert($chunk);
                    }
                }
            }
        } finally {
            if ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys=ON');
            } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }
    }

    protected function streamMirror(string $fromDisk, string $toDisk, string $fromPath, string $toPath): void
    {
        $stream = $this->attachments->readStream($fromDisk, $fromPath);

        try {
            $ok = Storage::disk($toDisk)->writeStream($toPath, $stream);
            if ($ok === false) {
                throw new RuntimeException("Failed mirroring [{$fromPath}] to [{$toPath}] on [{$toDisk}].");
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    protected function pruneOldBackups(string $disk, int $keep): void
    {
        $dirs = collect(Storage::disk($disk)->directories('backups'))
            ->sort()
            ->values();

        $excess = $dirs->count() - $keep;
        if ($excess <= 0) {
            return;
        }

        foreach ($dirs->take($excess) as $dir) {
            Storage::disk($disk)->deleteDirectory($dir);
        }
    }

    /**
     * @param  array<string, mixed>|list<mixed>  $data
     */
    protected function putJson(string $disk, string $path, array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new RuntimeException("Unable to encode JSON for [{$path}].");
        }

        $this->attachments->putContents($disk, $path, $json);
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    protected function readJson(string $disk, string $path): array
    {
        $raw = Storage::disk($disk)->get($path);
        $decoded = json_decode($raw ?: 'null', true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function putLocalFile(string $disk, string $remotePath, string $localPath): void
    {
        $stream = fopen($localPath, 'rb');
        if ($stream === false) {
            throw new RuntimeException("Unable to open [{$localPath}].");
        }

        try {
            Storage::disk($disk)->writeStream($remotePath, $stream);
        } finally {
            fclose($stream);
        }
    }

    protected function gzipFile(string $source, string $dest): void
    {
        $in = fopen($source, 'rb');
        $out = gzopen($dest, 'wb9');

        if ($in === false || $out === false) {
            throw new RuntimeException('Unable to gzip database dump.');
        }

        while (! feof($in)) {
            gzwrite($out, fread($in, 1024 * 1024) ?: '');
        }

        fclose($in);
        gzclose($out);
    }

    protected function gunzipFile(string $source, string $dest): void
    {
        $in = gzopen($source, 'rb');
        $out = fopen($dest, 'wb');

        if ($in === false || $out === false) {
            throw new RuntimeException('Unable to gunzip database dump.');
        }

        while (! gzeof($in)) {
            fwrite($out, gzread($in, 1024 * 1024) ?: '');
        }

        gzclose($in);
        fclose($out);
    }

    protected function findBinary(string $name): ?string
    {
        $whichCmd = PHP_OS_FAMILY === 'Windows' ? 'where' : 'which';
        foreach ([$whichCmd] as $which) {
            $result = Process::run([$which, $name]);
            if ($result->successful()) {
                $path = trim(explode("\n", str_replace("\r", '', $result->output()))[0] ?? '');
                if ($path !== '') {
                    return $path;
                }
            }
        }

        return null;
    }

    protected function gitSha(): ?string
    {
        if (! is_dir(base_path('.git'))) {
            return null;
        }

        $result = Process::path(base_path())->run(['git', 'rev-parse', '--short', 'HEAD']);

        return $result->successful() ? trim($result->output()) : null;
    }

    protected function firstColumn(string $table): string
    {
        $columns = DB::connection()->getSchemaBuilder()->getColumnListing($table);

        return $columns[0] ?? 'id';
    }

    protected function quoteIdent(string $name): string
    {
        return '"'.str_replace('"', '""', $name).'"';
    }

    protected function sqlLiteral(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'".str_replace("'", "''", (string) $value)."'";
    }
}
