<?php

declare(strict_types = 1);

namespace Tak\Async;

use Swoole\Coroutine;

use Swoole\Process;

use ReflectionFunction;

use Throwable;

use Closure;

final class Run {
	protected array $processes = array();
	protected array $results = array();
	protected array $errors = array();
	protected Closure $catch;
	public Closure $finally;
	private bool $ignore = false;

	public function __construct(callable ...$callbacks){
		array_map(fn(callable $callback) : object => $this->async($callback),$callbacks);
	}
	public function async(callable $callback,mixed ...$args) : self {
		$process = new Process(function(Process $worker) use($callback,$args) : void {
			$worker->name('php child process');
			Coroutine\run(function() use($worker,$callback,$args) : void {
				try {
					$closure = Closure::fromCallable($callback);
					if(self::isStaticClosure($closure) === false):
						$closure = $closure->bindTo($this);
					endif;
					$result = call_user_func($closure,...$args);
					$worker->push(serialize($result));
				} catch(Throwable $error){
					$worker->push(serialize($error));
				}
			});
		},false);
		$process->useQueue(1,2);
		$pid = $process->start();
		$this->processes[$pid] = $process;
		return $this;
	}
	public function await(Cancellation $cancellation = null) : mixed {
		if(is_null($cancellation) === false):
			$cancellation->setProcesses($this->processes);
		endif;
		Coroutine\run(function() : void {
			foreach($this->processes as $pid => $process):
				do {
					$ret = Process::wait(false);
					if($ret):
						$returned = unserialize($process->pop());
						if($returned instanceof Throwable):
							$this->errors[$ret['pid']] = $returned;
						else:
							$this->results[$ret['pid']] = $returned;
						endif;
					endif;
					Coroutine::sleep(0.001);
				} while($ret === false);
			endforeach;
		});
		if(is_null($cancellation) === false):
			$cancellation->setStatus(false);
		endif;
		if(empty($this->errors) === false):
			if(isset($this->catch)):
				foreach($this->errors as $pid => $error):
					Coroutine\run($this->catch,$error,$pid);
				endforeach;
			elseif($this->ignore === false):
				throw new Errors($this->errors);
			endif;
		endif;
		Coroutine\run($this->finally);
		return count($this->results) < 2 ? current($this->results) : $this->results;
	}
	public function close() : bool {
		$canceled = array_map(fn(Process $process) : bool => $process->close(),array_values($this->processes));
		return in_array(false,$canceled) === false;
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
	static function isStaticClosure(Closure $closure) : bool {
		$reflection = new ReflectionFunction($closure);
		return $reflection->isStatic();
	}
}

?>