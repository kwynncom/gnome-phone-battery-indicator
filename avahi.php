<?php // a near copy of usb.php - perhaps abstract later

require_once('utils.php');

use React\Stream\ReadableResourceStream;
use ReactLineStream\LineStream;
use React\EventLoop\Loop;

class avahiMonitorADBCl {

    const cmd = 'avahi-browse -r _adb-tls-connect._tcp';
    const cmdc = 'adb connect Android.local:';

    private readonly object $lines;
    private readonly object $loop;
    private readonly mixed  $inputStream;
    private readonly object $noto;

    public function __construct(object $noto) {
	$this->noto = $noto;
	$this->init();
    }

    private function checkDat(string $l) {

	static $lat = 0;

	if (!preg_match('/\s+port\s+=\s+\[(\d+)\]/', $l, $m)) return;
	belg('found wireless USB port ' . $m[1], true);

	$now = microtime(true);
	if ($now - $lat < 1) return;

	$int = intval($m[1]);
	kwas($int > 0, 'err # 221732 ');

	$lat = $now;
	$this->conn($int);
	
    }

    private function conn(int $p) {
	$res = trim(shell_exec(self::cmdc . $p));
	belg(self::cmdc . ' result ' . $res, true);
	if ($res) return;
	$this->noto('avahi', 'port found');
	
	
    }
    

    public function close() {
	belg('avahi close()');
	pclose($this->inputStream);
	$this->lines->close();
    }

    private function init() {

	$this->loop = Loop::get();
	belg(self::cmd, true);
        kwas($this->inputStream = popen(self::cmd, 'r'), 'Cannot open stream: ' . self::cmd);
        $resourceStream = new ReadableResourceStream($this->inputStream, $this->loop);
	$this->lines = new LineStream($resourceStream);
        $this->lines->on('data' , function (string $line) { $this->checkDat($line); });

    }

}

if (didCLICallMe(__FILE__)) {
    new avahiMonitorADBCl();
}