<?php

declare(strict_types = 1);

namespace Tak\Async;

use Swoole\Coroutine;

use Swoole\Coroutine\Channel;

use Throwable;

use Closure;

final class DeferredFuture {
	protected Channel $channel;
	protected Future $future;
	private bool $isCompleted = false;

	public function __construct(){
		$this->channel = new Channel(1);
		$this->future = new Future($this);
	}
	public function complete(mixed $result) : void {
		if($this->isCompleted):
			$exception = new Errors('Future is already completed !');
			$exception->throw();
		else:
			$this->isCompleted = true;
			$this->channel->push($result);
		endif;
	}
	public function error(Throwable $exception) : void {
		$this->complete(new Errors($exception));
	}
	public function getFuture() : Future {
		return $this->future;
	}
	public function isComplete() : bool {
		return $this->isCompleted;
	}
	public function await(float $timeout = -1) : mixed {
		$result = $this->channel->pop($timeout);
		if($this->channel->errCode === SWOOLE_CHANNEL_TIMEOUT):
			$result = new Errors('Timeout waiting for result !');
		elseif($this->channel->errCode === SWOOLE_CHANNEL_CLOSED):
			$result = new Errors('The channel is closed !');
		endif;
		return $result;
	}
}

?>