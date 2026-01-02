<?php

declare(strict_types=1);

require_once('adbDevices.php');


use React\Stream\ReadableResourceStream;
use ReactLineStream\LineStream;
use React\EventLoop\Loop;

final class ADBLogReaderCl
{
    private	     string $cmd;

    private	     object $lines;
    private readonly object $loop;
    private mixed  $inputStream;

    private readonly mixed $cb;
    private readonly bool  $termed;

    private bool $isOpen = false;

    public function __construct(object $cb) {
	$this->loop = Loop::get();
	$this->cb = $cb;
    }

    public function logRestart() {
	$this->reinit('ext');
    }

    public function __destruct() { $this->close('destructor'); }


    private function reinit(string $ev) {

	belg('logcat r-einit event ' . $ev);

	if ($ev === 'ext' && $this->isOpen) {
	    belg('log cat ext reinit call, but open so happy and ignoring');
	    return;
	}

	$this->close('closing');

	if ($ev === 'close') { $this->cb->notify('adblog', $ev);	} 
	if ($this->termed ?? false) {
	    belg('logcat termed');
	    return;
	}

	if ($ev !== 'close') $this->init();
    }

    private function setCmd() : string {

	$c  = '';
	$pre  = $this->cb->shcmo->adbPrefix();
	$c .= $pre . 'logcat 2>&1';
	$this->cmd = $c;
	return $c;
    }

    const ils = ['- waiting for device -', '--------- beginning of main' ];

    private function doLine(string $line) {
	foreach(self::ils as $cl) {
	    if (strpos($line, $cl) !== false) belg('logcat: ' . $cl);
	}

	$this->cb->adbLogLine($line);
    }

    private function init() {
	$this->setCmd();
 	belg($this->cmd);
        kwas($this->inputStream = popen($this->cmd, 'r'), 'Cannot open stream: ' . $this->cmd);
  	$this->lines = new LineStream(new ReadableResourceStream($this->inputStream, $this->loop));
        $this->lines->on('data' , function (string $line)   { $this->doLine($line); });
	$this->lines->on('close', function ()		    { $this->reinit('close');	    });
	$this->isOpen = true;
    }

    public function isOpen() : bool { return $this->isOpen; }

    public function close(string $ev): void   
    { 
	$this->isOpen = false;
	belg($this->cmd ?? '(adbLog command not set yet) ' . ' closing event ' . $ev);
	if ($this->termed ?? false) {
	    belg('logcat termed');
	    return;
	}
	if ($ev === 'term') $this->termed = true;
	if (isset($this->lines)) {  $this->lines->close(); }
	unset(    $this->lines);
	if (isset($this->inputStream) && is_resource($this->inputStream) && 
		   ($meta = @stream_get_meta_data($this->inputStream)) &&
		   !empty($meta['stream_type'])) pclose($this->inputStream); 
	unset(    $this->inputStream);

    }
}

if (didCLICallMe(__FILE__)) {
    new ADBLogReaderCl();
}