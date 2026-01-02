<?php

// use React\EventLoop\Timer\TimerInterface;
use React\EventLoop\Loop;

require_once('adbLevel.php');
require_once('/var/kwynn/batt/PRIVATEphones.php');
require_once('adbWait.php');

class adbDevicesCl implements KWPhonesPRIVATE {

const cmd = shCmdCl::advicmdConst;
const needPerms = ['no permissions', 'unauthorized'];

private readonly object $cento;
private readonly object $waito;
private int $iatts = 0;


public function __construct($oin) {
    $this->cento = $oin; unset($oin);
    $this->iatts = 0;
    $this->waito = new adbWaitCl($this->cento);
}


const nsleep = 4;

private function slowReinitLoop() : bool {
    if (++$this->iatts > 3) {
	belg($this->iatts . ' adb devices init attempts.  Sleeping for ' . self::nsleep);
	if (self::nsleep) sleep(self::nsleep);
	return true;
    }
    return false;
}

public function setok() {
    $this->iatts = 0;
}

public function doit() {
    if (!$this->slowReinitLoop()) { 
	$this->debounce(); 
    }
    else { $this->devs10(); }
}

private function debounce() {

    static $debounceTimer = null;
    $loop = Loop::get();

    if ($debounceTimer) {       
	$loop->cancelTimer($debounceTimer);    
	$debounceTimer = null;
    } 
    else { $this->devs10();    }

    $debounceTimer = $loop->addTimer(3.0, function () use (&$debounceTimer) {
        belg('debounce call');
        $debounceTimer = null;
	$this->devs10();
    });
}

private function devs10() {

    belg('devs10()');

    $ret = $this->devsActual();
    $send = 'unkdev';

    if (is_string($ret)) $send = $ret;
    if ($ret === true)   $send = 'found';
    if ($ret === false)  {
	$send = 'nothing';
	$this->waito->wait();
    }

    $this->cento->notify('devices', $send);
}

private function devsActual() : bool | string {

    $s = $this->cento->doShCmd(self::cmd);

    $a = explode("\n", $s); unset($s);

    $dline = false;

    foreach($a as $rawl) {
	$l = trim($rawl); unset($rawl);
	if ((!$dline) && ($l === 'List of devices attached')) {
	      $dline = true;
	      continue; 
	}
	if (!$dline) continue;
	if (!$l) continue;

	$l = self::hideSerials($l);
	belg($l . "\n");

	if (self::needPerms($l)) { return 'perm'; }

	return true;
    }

    return false;
} // func

private static function hideSerials(string $sin) : string {
    foreach(KWPhonesPRIVATE::list as $r) {
	$sin = str_replace($r['serial'], $r['alias'], $sin);
    }
    return $sin;
}

private static function needPerms(string $l) : bool {
    foreach(self::needPerms as $k) {
	if (strpos($l, $k) !== false) {
	    belg ('need perm: ' . $l);
	    return true;
	}
    }
    return false;
} // func

} // class