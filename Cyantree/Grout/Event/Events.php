<?php
namespace Cyantree\Grout\Event;

class Events{
	private $_events = array();
	public function join($event, $callback, $callbackData = null){
		if(!isset($this->_events[$event])){
			$this->_events[$event] = array();
		}

		$this->_events[$event][] = array($callback, $callbackData);
	}

	public function leave($event, $callback){
		if(!isset($this->_events[$event])) return;

		$callbacks = &$this->_events[$event];

		$id = 0;
		$count = count($callbacks);
		while($id < $count){
			if($callbacks[$id][0] == $callback){
				array_splice($callbacks, $id, 1);
				break;
			}
			$id++;
		}
	}

	public function trigger($type, $data = null){
		$e = new Event();
		$e->type = $type;
		$e->data = $data;

		if(!isset($this->_events[$type])) return $e;

		$callbacks = $this->_events[$type];

		foreach($callbacks as $callback){
			call_user_func($callback[0], $e, $callback[1]);
			if($e->stopPropagation) break;
		}

		return $e;
	}
}