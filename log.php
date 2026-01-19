<?php

declare(strict_types=1);

function belg(string|int $s, bool $star = false) {     battLogCl::put($s, false, $star); }

class battLogCl {

    private static string $logf;

    public static function put(string|int $s, bool $emitting = false, bool $star = false) {
	static $i = 1;
	static $hbs = '';
	static $lnothb = 0;
	static $prevnl = true;

	$ishb = !$star && !$emitting && is_string($s) && (strlen($s) === 1);
	if ($ishb) {  $hbs .= $s;	}
	else	   {  $lnothb = microtime(true);}

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
	self::putA($t, $cnl);
	if ($cnl) $i++;

	if ($cnl || !$ishb) $hbs = '';

    }

    public function __construct() {
	$this->initLog();
    }

    private static function putA(string $s, bool $cnl) {

	if (!(self::$logf ?? false)) self::initLog();

	file_put_contents(self::$logf, $s, FILE_APPEND);
	echo($s);

	if (!$cnl) {
	    fflush(STDOUT);
	}
    }

    private static function initLog() {
	$f = '/tmp/belg.txt';
	kwas(touch($f), "cannot create / touch $f");
	kwas(chmod($f, 0600), "cannot chmod $f");
	self::$logf = $f;
	self::put(date('Y-m-d') . ' PID ' . getmypid());
    }

    public static function noop(string $out = '?') {
	static $prev = 0;
	$now = microtime(true);
	if ($now - $prev < 0.10) return;
	belg($out);
	$prev = $now;
    }
}