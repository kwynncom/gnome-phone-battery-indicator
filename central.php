<?php

declare(strict_types=1);
use React\EventLoop\Loop;

require_once('utils.php');
require_once('shellCommands.php');
require_once('adbLevel.php');
require_once('adbLog.php');
require_once('usb.php');
require_once('adbLines.php');
require_once('adbDevices.php');

class GrandCentralBattCl {

    public function confirmedTimestamp() {
	battLogCl::noop('.');
	$this->resetHeartBeat();
    }

    private	     int    $hbi    = 0;

    private function resetHeartBeat() {	$this->hbi = 0;    }

    private function setHeartBeatN() { $this->hbi++;    }

    private function initHeartBeat() {
	Loop::addPeriodicTimer(0.8, function ()  {
	    if (!$this->Ubf || !$this->adbReader->isOpen()) { $this->resetHeartBeat(); 	return;   }
	    battLogCl::noop((string)($this->hbi % 10));
	});
    }
   
    private readonly object $lineO;
    private readonly object $adbReader;
    private readonly object $usbo;
    public  readonly object $shcmo;
    private	     int    $Ubf = 0;
    private readonly object $adbdevo;

    
    public function __construct() {
	beout('');
	$this->shcmo = new shCmdCl();
	$this->adbdevo = new adbDevicesCl($this);
	$this->adbReader = new ADBLogReaderCl($this);
	$this->resetCF(false);
	$this->lineO = new adbLinesCl($this);
	$this->initHeartBeat();
	$this->usbo = new usbMonitorCl($this);
	$this->initSignals();
	battKillCl::killPrev();
	Loop::run();
    }

    private function checkDevices() {
	$this->adbdevo->doit();
    }

    private function resetCF(bool $isGood) {
	if (!$isGood) beout('');
	belg('resetCF.  isGood = ' . ($isGood ? 'true' : 'false'));
	$this->Ubf = 0;
	$this->resetHeartBeat();
	if ($isGood) { 
	    $this->adbReader->logRestart(); 
	    $this->adbdevo->setok();
	}
	if (!$isGood) $this->checkDevices();
 
    }

    public function levelFromADBLog(int $lev) {
	static $prev;
	static $U = 0;

	$now = time();
	if ($now - $this->Ubf < 5) return;

	if (($lev !== $prev) || ($now - $U > 30)) {
	    beout($lev);
	    $U = $now;
	}
	else belg('+');
	$prev = $lev;
    }

    private function doLevelFromFile() {
	$res = adbLevelCl::getLevelFromPhoneFileActual(self::doShCmd(shCmdCl::asbccmdConst));
	if ($res < 0) { 
	    return $this->resetCF(false); 
	} else {
	    beout($res);
	    $this->resetCF(true);
	}

	$this->Ubf = time();

	
    }

    public function adbLogLine(string $line) {
	$this->setHeartBeatN();
	if (preg_match('/^error: /', $line)) { belg($line);    }
	$this->lineO->doLine($line);
    }

    public function notify(string $from, string $type) {

	if ($from === 'adblog' && $type === 'close') {
	    belg('adblog close');
	    $this->resetCF(false);
	}

	if ($from === 'usb') $this->checkDevices();

	if ($from === 'devices') {
	    if ($type === 'perm') beout('need permission');
	    belg('devices response is ' . $type);
	    if ($type === 'found') $this->doLevelFromFile();
	}
    }



    private function initSignals() {
	pcntl_async_signals(true);
	pcntl_signal(SIGINT , [$this, 'exit']);
	pcntl_signal(SIGTERM, [$this, 'exit']);
    }

    public function exit() {

	beout('');
	belg('b3 e-xit called' . "\n");
	if (isset($this->adbReader)) { $this->adbReader->close('term'); }
	$loop = Loop::get();
	$loop->stop();
	if (isset($this->usbo)) { $this->usbo->close(); }
	PidFileGuard::release();
	beout('');

	exit(0);
    }

   public function doShCmd(string $which) : mixed {
	return $this->shcmo->dosh($which);
    } 

   public function __destruct() { $this->exit();  }

}
