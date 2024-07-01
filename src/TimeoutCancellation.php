<?php

declare(strict_types = 1);

namespace Tak\Async;

use Swoole\Timer;

final class TimeoutCancellation extends Cancellation {
	private int $timer;

	public function __construct(private float $timeout,public string $message = 'Time out !'){
	}
	public function setStatus(bool $status) : void {
		$this->status = $status;
		if($status):
			if(isset($this->timer)):
				$exception = new Errors('The cancellation has already been used !');
				$exception->throw();
			else:
				$this->timer = Timer::after(intval($this->timeout * 1000),$this->cancel(...));
			endif;
		else:
			Timer::clear($this->timer);
		endif;
	}
}

?>