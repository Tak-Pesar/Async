<?php

declare(strict_types = 1);

namespace Tak\Async;

use Swoole\Coroutine\Channel;

use Closure;

class Cancellation {
	protected array $cids;
	protected Channel $channel;
	readonly private float $canceled;
	protected bool $status = false;

	public function __construct(public string $message = 'The task was canceled !'){
	}
	public function cancel() : bool {
		if(isset($this->cids) and $this->isCanceled() === false and $this->getStatus()):
			foreach($this->cids as $cid):
				$this->channel->push(['id'=>$cid,'error'=>new Errors($this->message)]);
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
	public function setCids(array $cids) : void {
		$this->cids = $cids;
	}
	public function setChannel(Channel $channel) : void {
		$this->channel = $channel;
	}
}

?>