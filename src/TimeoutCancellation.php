<?php

declare(strict_types = 1);

namespace Tak\Async;

use Swoole\Timer;

class TimeoutCancellation extends Cancellation {
	public function __construct(private float $timeout,public string $message = 'Time out !'){
		$this->setStatus(true);
	}
	public function setProcesses(array $processes) : void {
		$this->processes = $processes;
		Timer::after(intval($this->timeout * 1000),$this->cancel(...));
	}
}

?>