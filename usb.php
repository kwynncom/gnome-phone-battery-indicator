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

    private readonly object $lines;
    private readonly object $loop;
    private readonly mixed  $inputStream;

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
	$this->loop = Loop::get();
	belg(self::cmd, true);
        kwas($this->inputStream = popen(self::cmd, 'r'), 'Cannot open stream: ' . self::cmd);
        $resourceStream = new ReadableResourceStream($this->inputStream, $this->loop);
	$this->lines = new LineStream($resourceStream);
        $this->lines->on('data' , function (string $line) { $this->checkDat($line); });
    }

    public function close() {
	pclose($this->inputStream);
	$this->lines->close();
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