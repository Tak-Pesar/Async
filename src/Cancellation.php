<?php

declare(strict_types = 1);

namespace Tak\Async;

use Swoole\Process;

use Closure;

class Cancellation {
	readonly private array $processes;
	readonly private float $canceled;
	protected bool $status;

	public function __construct(public string $message = 'The task was canceled !'){
		$this->setStatus(true);
	}
	public function cancel() : bool {
		if(isset($this->processes) and $this->isCanceled() === false and $this->getStatus()):
			foreach($this->processes as $pid => $process):
				if(Process::kill($pid,SIG_DFL)):
					$process->push(serialize(new Errors($this->message)));
					Process::kill($pid,SIGKILL);
				endif;
			endforeach;
			$this->canceled = microtime(true);
			return true;
		else:
			return false;
		endif;
	}
	public function isCanceled() : bool {
		return isset($this->canceled);
	}
	public function setStatus(bool $status) : void {
		$this->status = $status;
	}
	public function getStatus() : bool {
		return $this->status;
	}
	public function setProcesses(array $processes) : void {
		$this->processes = $processes;
	}
}

?>