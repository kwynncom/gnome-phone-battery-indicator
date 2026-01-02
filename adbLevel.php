<?php

require_once('utils.php');

class adbLevelCl {

    public static function getLevelFromPhoneFileActual(mixed $s) : int {
	$tlev = self::filt($s);
	if ($tlev === false) { return -1; }
	$level = $tlev; unset($tlev);
	return $level;
    }

    public static function filt(mixed $res) : int | false {
	try {
	    kwas($res && is_string($res), 'bad res type');
	    $res = trim($res);
	    kwas(is_numeric($res), 'not numeric: ' . $res);
	    kwas(is_string($res), 'not string');
	    $n = strlen($res);
	    kwas($n > 0 && $n <= 3, 'invalid l-evel - string'); unset($n);
	    $i10 = intval  ($res); unset($res);
	    kwas($i10 >= 0 && $i10 <= 100, 'invalid l-evel as int');
	    $level = $i10; unset($i10);
	    return $level;
	} catch(Throwable $ex) {
	    $msg = $ex->getMessage();
	    belg('bad level ' . $msg . "\n");
	}

	belg('returning false');
	return false;
    }

}