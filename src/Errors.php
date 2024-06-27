<?php

declare(strict_types = 1);

namespace Tak\Async;

use Swoole\Coroutine;

use Exception;

use Closure;

class Errors extends Exception {
	private readonly array $exceptions;
	static private Closure $handler;

	public function __construct(string | array $exceptions,mixed ...$args){
		if(is_string($exceptions)):
			parent::__construct($exceptions,...$args);
			$this->exceptions = array($this);
		else:
			parent::__construct('Multiple exceptions occurred !',...$args);
			$this->exceptions = $exceptions;
		endif;
		if(isset(self::$handler)) Coroutine::create(self::$handler,...$this->exceptions);
	}
	public function getExceptions() : array {
		return $this->exceptions;
	}
	static public function setErrorHandler(callable $callback) : void {
		self::$handler = Closure::fromCallable($callback);
	}
}

?>