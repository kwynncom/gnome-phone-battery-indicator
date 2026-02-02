<?php

class shCmdCl {

    const advicmdConst = 'advi';
    const asbccmdConst = 'asbc';
    const asbscmdConst = 'asbs';

    public function dosh(string $which) : mixed {
	switch($which) {
	    case self::advicmdConst : return $this->advi(); break;
	    case self::asbccmdConst : return $this->asbc(); break;
	    case self::asbscmdConst : return $this->asbs(); break;
	    default: kwas(false, 'unknown shell command (err # 052412 )'); break;
	}
    }

    public function brightness(int $bright = 1) {
	$c  = '';
	$c .= $this->adbPrefix();
	$c .= 'shell settings put system screen_brightness ' . $bright . ' 2>&1 ';
	belg("$c\n", true);
	shell_exec($c);
    }

    private function asbs() : string {
	return trim($this->pbfcInternal('status'));
    }

    private function pbfcInternal(string $f) : string {
	$c  = '';
	$c .= $this->adbPrefix();
	$c .= 'shell cat /sys/class/power_supply/battery/' . $f . ' 2>&1';
	belg("$c\n", true);
	$s = shell_exec($c);
	if (!$s || !is_string($s)) return '';
	return $s;	
    }

    private function asbc() : string {
	return $this->pbfcInternal('capacity');
    }

    

    public function adbPrefix() : string {
	$t  = '';
	$t .= 'adb';
	$t .= ' ';
	return $t;
    }

    private function advi() : mixed {

	$c = $this->adbPrefix() . 'devices 2>&1';
	belg($c, true);
	$s = shell_exec($c);
	return $s;
    }

}