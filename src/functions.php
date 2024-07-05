<?php

declare(strict_types = 1);

namespace Tak;

function async(callable $callback,mixed ...$args) : Task {
	return (new Tak\Async\Task)->async($callback,...$args);
}

function delay(float $seconds) : bool {
	return (new Tak\Async\Task)->sleep($seconds);
}

function setErrorHandler(callable $callback = null) : void {
	Tak\Async\Errors::setErrorHandler($callback);
}

?>