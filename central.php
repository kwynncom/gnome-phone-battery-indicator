<?php declare(strict_types=1);

use React\EventLoop\Loop;

require_once('utils.php');
require_once('shellCommands.php');
require_once('avahi.php');
require_once('adbBattery.php');
require_once('adbLog.php');
require_once('usb.php');
require_once('adbLinesBatt.php');
require_once('adbDevices.php');
require_once('brightness.php');

class GrandCentralBattCl {

    public function confirmedTimestamp() {
	battLogCl::noop('.');
	$this->resetHeartBeat();
    }

    private	     int    $hbi    = 0;

    private function resetHeartBeat() {	$this->hbi = 0;    }

    private function setHeartBeatN() { $this->hbi++;    }

    private function initHeartBeat() {
	Loop::addPeriodicTimer(0.7, function ()  {
	    if (($this->Ubf === 0) || !$this->adbReader->isOpen()) { $this->resetHeartBeat(); 	return;   }
	    battLogCl::noop((string)($this->hbi % 10));
	});
    }
   
    private readonly object $lineOBatt;
    private readonly object $lineOBright;
    private readonly object $adbReader;
    private readonly object $usbo;
    public  readonly object $shcmo;
    private	     int | float    $Ubf = 0;
    private readonly object $adbdevo;
    private readonly object $avahio;

    
    public function __construct() {
	beout('');
	battKillCl::killPrev();
	$this->adbdevo = new adbDevicesCl($this);
	$this->shcmo = new shCmdCl($this->adbdevo);
	$this->avahio = new avahiMonitorADBCl($this); // devo below here
	$this->adbReader = new ADBLogReaderCl($this);
	$this->resetCF(false);
	$this->lineOBatt = new adbLinesCl($this);
	$this->lineOBright = new brightnessCl($this->shcmo);
	$this->initHeartBeat();
	$this->usbo = new usbMonitorCl($this);
	$this->initSignals();
	Loop::run();
    }

    private function checkDevices() {
	$this->adbdevo->doitDev();
    }

    private function resetCF(bool $isGood) {
	if ($this->termed ?? false) $isGood = false;
	if (!$isGood) beout('');

	if ($this->termed ?? false) return;

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

	if ($this->discharging) {
	    beout('');
	    $prev = '';
	    $U = $now;
	    belg('-');
	    return;
	}

	belg('+');

	if (($lev === $prev) && ($now - $this->Ubf < 5)) return;

	if (($lev !== $prev) || ($now - $U > 30)) {
	    beout($lev);
	    $prev = $lev;
	    $U = $now;
	}

    }

    private function doLevelFromFile() {

	if (microtime(true) - $this->Ubf < 2) return;


	$dis = self::doShCmd(shCmdCl::asbscmdConst);
	if ($dis === 'Discharging') {
	    belg($dis, true);
	    $this->discharging = true;
	}

	$res = adbBattCl::levFromPhFileStr(self::doShCmd(shCmdCl::asbccmdConst));
	if ($res < 0) { 
	    return $this->resetCF(false); 
	} else {
	    if (!$this->discharging) beout($res);
	    $this->resetCF(true);
	}

	$this->Ubf = microtime(true);

	
    }

    public function adbLogLine(string $line) {
	$this->setHeartBeatN();
	if (preg_match('/^error: /', $line)) { belg($line);    }
	$this->lineOBatt->doLine($line);
	$this->lineOBright->put($line);
    }

    public function notify(string $from, string $type, mixed $dat = null) {

	if ($from === 'adblog' && $type === 'close') {
	    belg('adblog close');
	    $this->resetCF(false);
	}

	if ($from === 'adblog' && $type === 'happy') {
	    if ((time() - $this->Ubf) < 1) {
		$this->doLevelFromFile();
	    }
	}

	if ($from === 'usb' || $from === 'avahi') $this->checkDevices();

	if ($from === 'devices') {
	    belg('devices response is ' . $type);
	    if	    ($type === 'perm') beout('need permission');
	    else if ($type === 'found') $this->doLevelFromFile();
	    else {
		belg('from devices: ' . $type);
	    }
	}

	if ($from === 'lines' && $type === 'batteryStatus') {
	    // belg('battStatus: ' . $dat);
	    if ($dat === 3) $this->discharging = true;
	    else	    $this->discharging = false;
	}
    }

    private bool $discharging = false;

    private function initSignals() {
	pcntl_async_signals(true);
	pcntl_signal(SIGINT , [$this, 'exit']);
	pcntl_signal(SIGTERM, [$this, 'exit']);
    }

    public readonly bool $termed;

    public function exit() {
	$this->termed = true;
	beout('');
	belg('b3 e-xit called' . "\n");
	if (isset($this->avahio)) { $this->avahio->close(); }
	belg('k-illing adbReader');
	if (isset($this->adbReader)) { 
	    $this->adbReader->close('term'); 
	}
	belg('k-illing usb (from central) - start');
	if (isset($this->usbo)) { $this->usbo->close(); }  
	belg('k-illing usb (from central) - end');
	$loop = Loop::get();
	$loop->stop();
	beout('');
	belg('before lock release');
	PidFileGuard::release();
	belg('after lock release');
	belg('about to exit');
	exit(0);
    }

   public function doShCmd(string $which) : mixed {
	return $this->shcmo->dosh($which);
    } 

   public function __destruct() { $this->exit();  }

}
