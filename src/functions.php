<?php

declare(strict_types = 1);

namespace Tak;

function async(callable $callback,mixed ...$args) : Async\Task {
	return (new Async\Task)->async($callback,...$args);
}

function delay(float $seconds) : bool {
	return (new Async\Task)->sleep($seconds);
}

function setErrorHandler(callable $callback = null) : void {
	Async\Errors::setErrorHandler($callback);
}

?>