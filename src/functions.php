<?php

declare(strict_types = 1);

namespace Tak;

function async(callable $callback,mixed ...$args) : Task {
	return (new Task)->async($callback,...$args);
}

function delay(float $seconds) : bool {
	return (new Task)->sleep($seconds);
}

function setErrorHandler(callable $callback = null) : void {
	Errors::setErrorHandler($callback);
}

?>