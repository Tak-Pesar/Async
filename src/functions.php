<?php

declare(strict_types = 1);

namespace Tak;

use Swoole\Coroutine:

function async(callable $callback,mixed ...$args) : Run {
	$run = new Run;
	return $run->async($callback,...$args);
}

function delay(float $seconds) : bool {
	return Coroutine::sleep($seconds);
}

function setErrorHandler(callable $callback) : void {
	Errors::setErrorHandler($callback);
}

?>