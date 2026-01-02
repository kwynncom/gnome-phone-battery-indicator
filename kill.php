<?php // written and re-written by Grok

final class PidFileGuard
{
    private static mixed  $filePointer = null;
    private static string $currentPidFile = '';

    private function __construct() {}

    public static function acquire(string $pidFile, bool $forceKill = true): void
    {
        $dir = dirname($pidFile);
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new RuntimeException("Cannot create directory for PID file: $dir");
        }

        $fp = @fopen($pidFile, 'c+');
        if (!$fp) {
            throw new RuntimeException("Cannot open PID file: $pidFile");
        }

        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            rewind($fp);
            $oldPid = (int)trim(stream_get_contents($fp));

            if ($oldPid > 1 && posix_kill($oldPid, 0)) {
                self::killProcess($oldPid, $forceKill);
            } else {
                echo "Old PID $oldPid is dead, but lock held by stale processes. Finding holders...\n";
                exec('fuser ' . escapeshellarg($pidFile) . ' 2>/dev/null', $output);
                $holders = [];
                foreach ($output as $line) {
                    $pids = preg_split('/\s+/', trim($line));
                    foreach ($pids as $p) {
                        $hp = (int)$p;
                        if ($hp > 1) $holders[] = $hp;
                    }
                }
                $holders = array_unique($holders);
                $myPid = getmypid();
                foreach ($holders as $hPid) {
                    if ($hPid === $myPid || !posix_kill($hPid, 0)) continue;
                    echo "Killing stale holder PID $hPid ...\n";
                    posix_kill($hPid, SIGTERM);
                    $waitStart = microtime(true);
                    while (microtime(true) - $waitStart < 5 && posix_kill($hPid, 0)) {
                        usleep(200000);
                    }
                    if (posix_kill($hPid, 0) && $forceKill) {
                        echo "Force killing stale holder PID $hPid ...\n";
                        posix_kill($hPid, SIGKILL);
                    }
                }
                usleep(500000);
            }

            if (!flock($fp, LOCK_EX | LOCK_NB)) {
                fclose($fp);
                throw new RuntimeException("Lock still held after kill attempts (check for unrelated processes holding $pidFile)");
            }
        }

        ftruncate($fp, 0);
        fwrite($fp, getmypid() . "\n");
        fflush($fp);

        self::$filePointer = $fp;
        self::$currentPidFile = $pidFile;

        register_shutdown_function([self::class, 'releaseOnShutdown']);
    }

    private static function killProcess(int $pid, bool $forceKill): void
    {
        if ($pid <= 1 || !posix_kill($pid, 0)) {
            return;
        }
        echo "Killing instance PID $pid ...\n";
        posix_kill($pid, SIGTERM);
        $waitStart = microtime(true);
        while (microtime(true) - $waitStart < 5 && posix_kill($pid, 0)) {
            usleep(200000);
        }
        if (posix_kill($pid, 0) && $forceKill) {
            echo "Force killing PID $pid ...\n";
            posix_kill($pid, SIGKILL);
        }
    }

    public static function isRunning(string $pidFile): bool
    {
        if (!file_exists($pidFile)) return false;

        $fp = @fopen($pidFile, 'r');
        if (!$fp) return true;

        if (flock($fp, LOCK_EX | LOCK_NB)) {
            flock($fp, LOCK_UN);
            fclose($fp);
            return false;
        }

        $pid = (int)trim(fgets($fp));
        fclose($fp);

        return posix_kill($pid, 0);
    }

    private static function releaseOnShutdown(): void
    {
        self::release();
    }

    public static function release(): void
    {
        if (self::$filePointer === null) {
            return;
        }

        flock(self::$filePointer, LOCK_UN);
        fclose(self::$filePointer);
        if (file_exists(self::$currentPidFile)) {
            @unlink(self::$currentPidFile);
        }

        self::$filePointer = null;
        self::$currentPidFile = '';
    }
}