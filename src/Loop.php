<?php

declare(strict_types = 1);

namespace Tak\Async;

use Swoole\Coroutine;

use Swoole\Timer;

use Closure;

final class Loop {
	protected int $id = 0;

	public function __construct(public readonly Closure $callback,private int $interval,int $start = 0,int $finish = 0){
		Timer::after($start,$this->start(...));
		Timer::after($finish,$this->finish(...));
	}
	public function start() : void {
		if(Timer::exists($this->id) === false):
			$this->id = Timer::tick($this->interval,$this->run(...));
		endif;
	}
	private function run() : void {
		$result = call_user_func($this->callback,$this);
		if($result === 0):
			$this->stop();
		elseif($result !== $this->interval):
			$this->interval = intval($result);
			Timer::clear($this->id);
			$this->start();
		endif;
	}
	public function stop() : void {
		Timer::clear($this->id);
	}
	public function finish() : void {
		$this->interval = 0;
		$this->stop();
	}
}

?>