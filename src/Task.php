<?php

declare(strict_types = 1);

namespace Tak\Async;

use Swoole\Coroutine;

use Swoole\Coroutine\Channel;

use Throwable;

use Closure;

use function Swoole\Coroutine\run;

final class Task {
	protected array $cids = array();
	protected array $results = array();
	protected array $errors = array();
	protected Channel $channel;
	public Closure $catch;
	public Closure $finally;
	public bool $ignore = false;

	public function __construct(int $count = PHP_INT_MAX,callable ...$callbacks){
		array_map(fn(callable $callback) : object => $this->async($callback),$callbacks);
		$this->channel = new Channel($count);
	}
	public function async(callable $callback,mixed ...$args) : self {
		$closure = Closure::fromCallable($callback);
		if(Tools::isStaticClosure($closure) === false):
			$closure = $closure->bindTo($this);
		endif;
		$create = function() use($closure,$args) : void {
			$cid = Coroutine::create(function() use($closure,$args) : void {
				try {
					$result = call_user_func($closure,...$args);
					$this->channel->push(['id'=>Coroutine::getCid(),'result'=>$result]);
				} catch(Throwable $error){
					$this->channel->push(['id'=>Coroutine::getCid(),'error'=>$error]);
				}
			});
			if(is_int($cid)) $this->cids []= $cid;
		};
		if(Tools::inCoroutine()):
			$create->call($this);
		else:
			run($create);
		endif;
		return $this;
	}
	public function await(Cancellation $cancellation = null) : mixed {
		$join = function() use($cancellation) : void {
			if(is_null($cancellation) === false):
				$cancellation->setCids($this->cids);
				$cancellation->setChannel($this->channel);
				$cancellation->setStatus(true);
			endif;
			foreach($this->cids as $cid):
				do {
					$pop = $this->channel->pop();
				} while(in_array($pop['id'],$this->results) or in_array($pop['id'],$this->errors) or in_array($pop['id'],$this->cids) === false);
				if(isset($pop['result'])):
					$this->results[$pop['id']] = $pop['result'];
				elseif(isset($pop['error'])):
					$this->errors[$pop['id']] = $pop['error'];
				endif;
			endforeach;
			if(is_null($cancellation) === false):
				$cancellation->setStatus(false);
			endif;
		};
		if(Tools::inCoroutine()):
			$join->call($this);
		else:
			run($join);
		endif;
		if(empty($this->errors) === false):
			if(isset($this->catch)):
				foreach($this->errors as $pid => $error):
					if(Tools::inCoroutine()):
						Coroutine::create($this->catch,$error,$pid);
					else:
						call_user_func($this->catch,$error,$pid);
					endif;
				endforeach;
			elseif($this->ignore === false):
				$exception = new Errors($this->errors);
				$exception->throw();
			endif;
		endif;
		if(isset($this->finally)):
			if(Tools::inCoroutine()):
				Coroutine::defer($this->finally);
			else:
				call_user_func($this->finally);
			endif;
		endif;
		return count($this->cids) < 2 ? current($this->results) : $this->results;
	}
	public function sleep(float $seconds) : bool {
		return Coroutine::sleep($seconds);
	}
	public function cancel() : bool {
		$canceled = array_map(fn(int $cid) : bool => Coroutine::cancel($cid),$this->cids);
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
}

?>