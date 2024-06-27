<?php

declare(strict_types = 1);

namespace Tak\Async;

use Swoole\Coroutine\Channel;

final class LocalMutex {
	private Channel $channel;

	public function __construct(){
		$this->channel = new Channel(1);
		$this->channel->push(true);
	}
	public function acquire(float $timeout = -1) : void {
		$this->channel->pop($timeout);
	}
	public function release() : void {
		$this->channel->push(true);
	}
}

?>