<?php

declare(strict_types = 1);

namespace Tak\Async;

use Swoole\Coroutine;

use Swoole\Coroutine\Channel;

use Closure;

use RuntimeException;

final class DeferredFuture {
	protected Channel $channel;
	protected Future $future;
	private bool $isCompleted = false;

	public function __construct(){
		$this->channel = new Channel(1);
		$this->future = new Future($this);
	}
	public function complete(mixed $result) : void {
		if($this->isCompleted) throw new RuntimeException('Future is already completed !');
		$this->isCompleted = true;
		$this->channel->push($result);
		if(isset($this->future->finally)) Coroutine::create($this->future->finally);
	}
	public function error(...$arguments) : void {
		$this->complete(new Errors(...$arguments));
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
		endif;
		return $result;
	}
}

?>