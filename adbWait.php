<?php

require_once('utils.php');

use React\Stream\ReadableResourceStream;
use ReactLineStream\LineStream;
use React\EventLoop\Loop;
use React\ChildProcess\Process;

class adbWaitCl {

    private readonly object $cb;
    private int $n = 0;

    public function __construct(object $cb) {
	$this->cb = $cb;
    }

    public function wait() {
	$c = 'adb wait-for-device 2>&1';
	if (++$this->n > 10) {
	    belg($c . ' too many restarts.  Not restarting.');
	    return;
	}

	belg($c . ' call ' . $this->n, true);
	$process = new Process($c);
	$process->start(Loop::get());
	$process->on('exit', function ($exitCode) {
	    if ($exitCode === 0) {
		$this->cb->notify('devices', 'found');
	    }
	});
    }
}

// if (didCLICallMe(__FILE__)) new adbWaitCl();