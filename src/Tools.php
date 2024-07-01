<?php

declare(strict_types = 1);

namespace Tak\Async;

use Swoole\Coroutine;

use ReflectionFunction;

use Closure;

abstract class Tools {
	static public function isStaticClosure(Closure $closure) : bool {
		$reflection = new ReflectionFunction($closure);
		return $reflection->isStatic();
	}
	static public function inCoroutine() : bool {
		return boolval(Coroutine::getCid() > 0);
	}
}

?>