<?php

declare(strict_types = 1);

namespace Tak\Async;

use Swoole\Coroutine;

use Closure;

final class Future {
	protected Closure $catch;
	public Closure $finally;
	private bool $ignore = false;

	public function __construct(private DeferredFuture $deferred){
	}
	public function await(float $timeout = -1) : mixed {
		$result = $this->deferred->await($timeout);
		if($result instanceof Errors):
			if(isset($this->catch)):
				Coroutine::create($this->catch,$result);
			elseif($this->ignore === false):
				throw $result;
			endif;
			return null;
		endif;
		return $result;
	}
	public function ignore() : self {
		$this->ignore = true;
		return $this;
	}
	public function catch(callable $catch) : self {
		$this->catch = Closure::fromCallable($catch);
		return $this;
	}
	public function finally(callable $finally) : self {
		$this->finally = Closure::fromCallable($finally);
		return $this;
	}
}

?>