<?php

declare(strict_types=1);

// use React\EventLoop\Timer\TimerInterface;
use React\EventLoop\Loop;

require_once('/opt/kwynn/kwutils.php');

require_once('kill.php');

interface battExtIntf {
    const nMaxLoop       = 20;  // PHP_INT_MAX
    const usbTimeoutInit =  5;
    const timeoutSteadyState = 67;

    const msgSeek = 'seeking USB'; // 3 messages not used at the moment, I don't think.
    const msgRm   = 'USB disconnect...';
    const msgAdd  = 'USB connected...';

}

class battKillCl {

    const lockf = '/tmp/kwtt_2025_1213_01.pid';

    public static function isPrev() : bool {
	return PidFileGuard::isRunning(self::lockf);
    }


    public static function killPrev() {
	$res = self::isPrev();
	if ($res) belg('another process to kill...');
	PidFileGuard::acquire(self::lockf);
    }



}

$BEOUTO;

if (!isset($BEOUTO)) { 
    $BEOUTO = new battLogCl();
}

function beout($s) {
    global $BEOUTO;

    $BEOUTO->put($s, true);
    $c = 'busctl --user emit /kwynn/batt com.kwynn IamArbitraryNameButNeeded s ' . '"' . $s . '"';
    shell_exec($c);
}

function belg(string $s, bool $star = false) {
    global $BEOUTO;

    $BEOUTO->put($s, false, $star);
}

function getbeout() : string|int {
    global $BEOUTO;
    return $BEOUTO->get();
}



class battLogCl {

    private readonly string $logf;

    private static string|int $current = '(init)';

    public static function get() : string|int {
	return self::$current;
    }

    public function put(string|int $s, bool $emitting = false, bool $star = false) {
	static $i = 1;
	static $hbs = '';
	static $lnothb = 0;
	static $prevnl = true;

	$ishb = !$star && !$emitting && is_string($s) && (strlen($s) === 1);


	if ($ishb) {
	    // if (microtime(true) - $lnothb < 0.1) { return; }

	    $hbs .= $s;
	    
	}
	else {
	    $lnothb = microtime(true);
	}

	if ($emitting) self::$current = $s;

	if (!$s && is_string($s) && strlen(trim($s)) === 0) $s = '(blanking)';

	$t  = '';

	$hbl = strlen($hbs);

	if (!$ishb || $hbl <= 1) {
	    $t .= $i;
	    $t .= ' ';
	    $t .= date('H:i:s');
	    $t .= ' ';
	    if ($star || $emitting) $t .= '********* ';
	    if ($emitting) $t .= 'emitting ';
	}

	$t .= $s;

	$cnl = !$ishb || $hbl >= 64;
	$prefixnl = !$ishb && $hbl > 0;
	
	$t = ($prefixnl ? PHP_EOL : '') . trim($t) . ($cnl ? PHP_EOL : '');
	$this->putA($t, $cnl);
	if ($cnl) $i++;

	if ($cnl || !$ishb) $hbs = '';

    }

    public function __construct() {
	$this->initLog();
    }

    private function putA(string $s, bool $cnl) {
	file_put_contents($this->logf, $s, FILE_APPEND);
	echo($s);

	if (!$cnl) {
	    fflush(STDOUT);
	}
    }

    private function initLog() {
	$f = '/tmp/belg.txt';
	kwas(touch($f), "cannot create / touch $f");
	kwas(chmod($f, 0600), "cannot chmod $f");
	$this->logf = $f;
	$this->put(date('Y-m-d'));
    }

    public static function noop(string $out = '?') {
	static $prev = 0;
	$now = microtime(true);
	if ($now - $prev < 0.10) return;
	belg($out);
	$prev = $now;
    }
}

