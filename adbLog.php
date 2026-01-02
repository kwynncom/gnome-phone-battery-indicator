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

	$this->ckProcForTerm();

	if (($this->cb->termed ?? false) || ($this->termed ?? false)) {
	    if (!($this->termed ?? false)) $this->termed = true;
	    $ev = 'marked termed from central';
	}

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

	if ($ev !== 'close' && (!($this->termed ?? false))) $this->initReal();
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

    private mixed $process;

    private function ckProcForTerm() {
	if (!($this->process ?? false)) return;
	$status = proc_get_status($this->process);
	if (!$status) {
	    belg('loccat proc status not set');
	    return;
	}
	if ($status['running']) { 
	    belg('logcat proc running');
	    return;
	}
	if ($status['signaled'] && (!($this->termed ?? false))) {
	    $this->termed = true;
	    belg('logcat signaled');
	}
    }

    private function dolcc() {
	$this->setCmd();
 	belg($this->cmd);
	$descriptors = [    1 => ['pipe', 'w'],   	];
	$this->process = proc_open($this->cmd, $descriptors, $pipes);
	$this->inputStream = $pipes[1];
        kwas($this->inputStream, 'Cannot open stream: ' . $this->cmd);	
    }

    private function initReal() {
	$this->dolcc();
  	$this->lines = new LineStream(new ReadableResourceStream($this->inputStream, $this->loop));
        $this->lines->on('data' , function (string $line)   { $this->doLine($line); });
	$this->lines->on('close', function ()		    { $this->reinit('close');	    });
	$this->isOpen = true;
    }

    public function isOpen() : bool { return $this->isOpen; }

    public function close(string $ev): void   
    { 
	$this->isOpen = false;
	belg('logcat close: ' . ($this->cmd ?? '(adbLog command not set yet) ') . ' closing event ' . $ev);
	if ($this->termed ?? false) {
	    belg('logcat termed');
	    return;
	}
	if ($ev === 'term' && (!($this->termed ?? false))) $this->termed = true;
	if (isset($this->lines)) {  $this->lines->close(); }
	unset(    $this->lines);
	if (isset($this->inputStream) && is_resource($this->inputStream) && 
		   ($meta = @stream_get_meta_data($this->inputStream)) &&
		   !empty($meta['stream_type'])) fclose($this->inputStream); 
	unset(    $this->inputStream);

	if ($this->process ?? false) proc_close($this->process);
	$this->process = null;

    }
}
