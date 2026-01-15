<?php

declare(strict_types=1);

require_once('/opt/kwynn/kwutils.php');
require_once('kill.php');
require_once('log.php');

function beout(string | int $s) {
    
    static $prev;
    static $lat = 0;

    $now = time();

    $emit = true;
    if (($s === $prev) && ($now - $lat < 5)) { $emit = false; }

    belg($s, $emit);

    if ($emit) {
	batt_bus_emitF($s);
	$prev = $s;
	$lat = $now;
    }
}

function batt_bus_emitF(string | int $s) {
    shell_exec('busctl --user emit /kwynn/batt/gnome/topright com.kwynn ignoreMe s ' . '"' . $s . '"');
}

class battKillCl {

    const lockf = '/tmp/kwtt_2025_1213_01.pid';

    public static function killPrev() {
	$res = PidFileGuard::isRunning(self::lockf);
	if ($res) belg('another process to kill...');
	PidFileGuard::acquire(self::lockf);
    }
}