<?php

class brightnessCl {

    const zthresh = 9.7;
    const key = 'Accelerometer: x:y:z=';
    const defaultBright = 2;

    private string $status = 'unchanged';

    private function check(string $line, int $offset) {

	static $i = 0;

	$s = trim(substr($line, $offset));
	// belg('accel: ' . $s);
	$a = json_decode($s, true);
	$z = $a[2] ?? false;
	if ($z === false) return;

	if ($z > self::zthresh) $i++;

	if ($i < 2) return; 

	// belg('accel notify');

	if ($this->status !== 'changed') {
	    shCmdCl::brightness(self::defaultBright);
	    $this->status = 'changed';
	}
	
    }

    public function put(string $line) {

	static $prev = PHP_INT_MAX;
	static $n = strlen(self::key);

	if (($pos = strpos($line, self::key)) === false) return;
	$d = adbLineTSDiff($line);
	if (!isset($d)) return;
	if ($d > 2) return;
	$now = time();
	if ($now - $prev < 5) { 
	    $prev = $now;
	    return;
	}
	$prev = $now;

	$this->check($line, $pos + $n);

    }

    



    

}
