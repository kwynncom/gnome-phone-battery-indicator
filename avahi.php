<?php // a near copy of usb.php - perhaps abstract later

require_once('utils.php');

use React\Stream\ReadableResourceStream;
use ReactLineStream\LineStream;
use React\EventLoop\Loop;

class avahiMonitorADBCl {

    const cmd = 'avahi-browse -r _adb-tls-connect._tcp';
    const cmdc = 'adb connect Android.local:';

    private	     object $lines;
    private readonly object $loop;
    private mixed  $inputStream;
    private mixed $process;

    private readonly object $noto;

    public function __construct(object $noto) {
	$this->noto = $noto;
	$this->init();
    }

    private function checkDat(string $l) {

	static $lat = 0;

	if (!preg_match('/\s+port\s+=\s+\[(\d+)\]/', $l, $m)) return;
	belg('found wireless ADB port ' . $m[1], true);

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
	$this->noto->notify('avahi', 'port found');
    }
    



    public function close() {
	belg('avahi close begin: ' . self::cmd);

	if (isset($this->lines)) {  $this->lines->close(); }
	unset(    $this->lines);
	if (isset($this->inputStream) && is_resource($this->inputStream) && 
		   ($meta = @stream_get_meta_data($this->inputStream)) &&
		   !empty($meta['stream_type'])) fclose($this->inputStream); 
	unset(    $this->inputStream);


	proc_terminate	    ($this->process, SIGTERM);
	
	if (false) {
	    sleep(1);
	    if (proc_get_status ($this->process)['running']) {
		proc_terminate  ($this->process, SIGKILL);
	    }
	}


	$this->safeProcessCl();
	$this->process = null;
	belg('avahi close end: ' . self::cmd);

    }

    private function safeProcessCl() {
	if (isset($this->process) && is_resource($this->process)) {
	    $status = proc_get_status($this->process);

	    // Optional: only call proc_close() if the process is still actually running
	    if ($status['running']) {
		proc_close($this->process);
	    } else {
		// Process already exited → calling proc_close() is safe but usually returns -1
		proc_close($this->process);
	    }

	    // Important: prevent accidental double-close attempts later
	    $this->process = null;   // or unset($this->process);
	}
    }

    private function init() {

 	belg(self::cmd);
	$descriptors = [    1 => ['pipe', 'w'],   	];
	$this->process = proc_open(self::cmd, $descriptors, $pipes);
	$this->inputStream = $pipes[1];
        kwas($this->inputStream, 'Cannot open stream: ' . self::cmd);	

	$this->loop = Loop::get();
  	$this->lines = new LineStream(new ReadableResourceStream($this->inputStream, $this->loop));
        $this->lines->on('data' , function (string $line)   { $this->checkDat($line); });

    }

}

if (didCLICallMe(__FILE__)) {
    new avahiMonitorADBCl();
}