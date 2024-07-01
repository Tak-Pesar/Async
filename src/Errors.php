<?php

declare(strict_types = 1);

namespace Tak\Async;

use Swoole\Coroutine;

use Exception;

use Closure;

final class Errors extends Exception {
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
	}
	public function getExceptions() : array {
		return $this->exceptions;
	}
	public function throw() : void {
		if(isset(self::$handler)):
			foreach($this->exceptions as $exception):
				if(Tools::inCoroutine()):
					Coroutine::create(self::$handler,$exception);
				else:
					call_user_func(self::$handler,$exception);
				endif;
			endforeach;
		else:
			throw $this;
		endif;
	}
	static public function setErrorHandler(callable $callback = null) : void {
		if(is_null($callback)):
			unset(self::$handler);
		else:
			self::$handler = Closure::fromCallable($callback);
		endif;
	}
}

?>