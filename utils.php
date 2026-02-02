<?php

declare(strict_types=1);

require_once('/opt/kwynn/kwutils.php');
require_once('log.php');
require_once('kill.php');


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

function adbLineTSDiff(string $line): null | float
{
    if (preg_match('/^(\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{3})/', $line, $matches)) {
	$timestampPart = $matches[1];
	$currentYear = date('Y');
	$fullDateStr = $currentYear . '-' . $timestampPart;
	$dt = DateTime::createFromFormat('Y-m-d H:i:s.v', $fullDateStr);

	if ($dt === false) { return null;    }

	$now = new DateTime('now');
	$diffInSeconds = $now->format('U.u') - $dt->format('U.u');

	return $diffInSeconds;
    }

    return null;
}