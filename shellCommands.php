<?php

class shCmdCl {

    const advicmdConst = 'advi';
    const asbccmdConst = 'asbc';

    public function dosh(string $which) : mixed {
	switch($which) {
	    case self::advicmdConst : return $this->advi(); break;
	    case self::asbccmdConst : return $this->asbc(); break;
	}
    }

    private function asbc() : string {
	$c  = '';
	$c .= $this->adbPrefix();
	$c .= 'shell cat /sys/class/power_supply/battery/capacity 2>&1';
	belg("$c\n", true);
	$s = shell_exec($c);
	if (!$s || !is_string($s)) return '';
	return $s;
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