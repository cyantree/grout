<?php
namespace Cyantree\Grout\Event;

class Events
{
    private $events = array();

    public function join($event, $callback, $callbackData = null, $prepend = false)
    {
        if (!isset($this->events[$event])) {
            $this->events[$event] = array();
        }

        if ($prepend) {
            array_unshift($this->events[$event], array($callback, $callbackData));

        } else {
            $this->events[$event][] = array($callback, $callbackData);
        }
    }

    public function leave($event, $callback)
    {
        if (!isset($this->events[$event])) {
            return;
        }

        $callbacks = & $this->events[$event];

        $id = 0;
        $count = count($callbacks);
        while ($id < $count) {
            if ($callbacks[$id][0] == $callback) {
                array_splice($callbacks, $id, 1);
                break;
            }
            $id++;
        }
    }

    public function trigger($type, $data = null, $context = null)
    {
        $e = new Event();
        $e->type = $type;
        $e->data = $data;
        $e->context = $context;

        return $this->triggerEvent($e);
    }

    public function triggerEvent(Event $event)
    {
        if (!isset($this->events[$event->type])) {
            return $event;
        }

        $callbacks = $this->events[$event->type];

        foreach ($callbacks as $callback) {
            call_user_func($callback[0], $event, $callback[1]);
            if ($event->stopPropagation) {
                break;
            }
        }

        return $event;
    }
}
