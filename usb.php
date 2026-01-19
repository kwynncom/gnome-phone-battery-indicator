<?php

declare(strict_types=1);

require_once('utils.php');
require_once('/var/kwynn/batt/PRIVATEphones.php');
require_once('adbDevices.php');

use React\Stream\ReadableResourceStream;
use ReactLineStream\LineStream;
use React\EventLoop\Loop;

class usbMonitorCl {

    const cmd = 'udevadm monitor -s usb 2>&1';

    private	     object $lines;
    private readonly object $loop;
    private mixed  $inputStream;
    private mixed $process;

    private readonly object $noti;

    public function __construct(object $noti) {
	$this->noti = $noti;
	$this->init();
	if ($this->lsusb()) $this->noti->notify('usb', 'lsusb', true);
    }

    private function checkDat(string $l) {

	static $lat = 0;

	$check = false;

	if (strpos($l, ' add ') !== false) $check = 'add';
	if (strpos($l, 'KERNEL - the kernel uevent') !== false) {
	    belg('usb started monitoring');
	    $check = 'init';
	}
	
	$now = microtime(true);
	if ($check) {
	    if ($now - $lat < 1) return;
	    if ($check === 'add') belg($l);
	    $lat = $now;
	    $this->noti->notify('usb', $check);
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

    public function close() {
	belg('avahi close: ' . self::cmd . ' closing event ');

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
	belg('avahi close: ' . self::cmd . ' closing event ');

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

    private static function lsusb() : bool {

	$b = microtime(true);
	$s = shell_exec('timeout -k 0.1 0.15 lsusb');
	$e = microtime(true);
	belg('l-susb took ' . sprintf('%0.3f', $e - $b) . 's');
	if (!$s || !is_string($s)) return false;

	foreach(KWPhonesPRIVATE::list as $r) {
	    if (strpos($s, $r['vidpid']) !== false) {
		belg('usb found specific device');
		return true;
	    }
	}

	return false;
    }
}