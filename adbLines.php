<?php

declare(strict_types=1);

require_once('utils.php');
require_once('adbLevel.php');

class adbLinesCl {

    const av5l = 'D/BatteryService';

    private readonly int    $av5ln;

    private readonly object $noti;

    private function setSemiConsts() {
	$this->av5ln = strlen(self::av5l);
    }

    public function __construct(?object $noti = null) {
	if ($noti) $this->noti = $noti;
	$this->setSemiConsts();
    }

    public static function test() {
	$o = new self();
	$o->testI();
    }

    private function testI() {
	$f = '/tmp/a/1.log';
	kwas(is_readable($f), "no test file $f");
	$t = file_get_contents($f);
	$line = $this->findLastMatchingLine($t);
	belg($line);
    }

    public function doLine(string $s): ?string
    {
	$fts = false;
	$lines = preg_split('/\R/', $s);
	for ($i = count($lines) - 1; $i >= 0; $i--) {
	    $line = $lines[$i];
	    if (!isset($line[180])) continue;
	    if (self::batteryLine($line) ?? false)  { return $line; }
	    if (!$fts && $this->checkLastTS($line)) { $fts  = true; }
	}

	return null;
    }


    private function checkLastTS(string $line) : bool | null {
	$tsr = self::checkLineTimestamp($line);
	if ($tsr === true) return null;
	if ($tsr > 3) return false;
        $this->noti->confirmedTimestamp();
        return true;
    }


    private static function bf13(string $s) : bool {
	if (strpos($s, ' D BatteryService: Processing new values: ') === false) return false;
	$d = self::checkLineTimestamp($s);
	if ($d === true) return true;
	if ($d > 5) return false;
	else return true;
    }

    private function bfv05(string $s) : bool { return substr(trim($s), 0, $this->av5ln) === self::av5l;    }

    private function batteryFilt05(string $s) : bool {
	if (self::bf13 ($s)) return true;
	if ($this->bfv05($s)) return true;
	return false;
    }

    private function batteryLine(string $s) : ?int {

	if (!self::batteryFilt05($s)) return null;

	preg_match('/level:(\d{1,3}),/', $s, $m);
	$t56 = self::batteryFilt10($m);
	if ($t56 ?? false) return $t56;

        
	preg_match('/ batteryLevel=(\d{1,3}),/', $s, $m);
	$t61 = self::batteryFilt10($m);
	if ($t61 ?? false) return $t61;

	return null;
    }	

    private function batteryFilt10($m) : ?int {
	// if ($m[0] ?? false) belg('match = ' . $m[0]);
	if (isset($m[1])) {
	    $tlev = adbLevelCl::filt($m[1]);
	    if ($tlev === false) return null;

	    if ($this->noti ?? false) {
		$this->noti->levelFromADBLog($tlev);
	    }
	    // belg('bfilt10 ' . $tlev);
	    return $tlev;
	}

	return null;
    }



    private static function checkLineTimestamp(string $line): true|float
    {
	if (preg_match('/^(\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{3})/', $line, $matches)) {
	    $timestampPart = $matches[1];
	    $currentYear = date('Y');
	    $fullDateStr = $currentYear . '-' . $timestampPart;
	    $dt = DateTime::createFromFormat('Y-m-d H:i:s.v', $fullDateStr);

	    if ($dt === false) { return true;    }

	    $now = new DateTime('now');
	    $diffInSeconds = $now->format('U.u') - $dt->format('U.u');

	    return $diffInSeconds;
	}

	return true;
    }


}

if (didCLICallMe(__FILE__)) adbLinesCl::test();