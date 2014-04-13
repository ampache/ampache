<?php

/*
 * This file is part of Evenement.
 *
 * (c) Igor Wiedler <igor@wiedler.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Evenement;

class EventEmitter implements EventEmitterInterface
{
    protected $listeners = array();

    public function on($event, $listener)
    {
        if (!is_callable($listener)) {
            throw new \InvalidArgumentException('The provided listener was not a valid callable.');
        }

        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = array();
        }

        $this->listeners[$event][] = $listener;
    }

    public function once($event, $listener)
    {
        $that = $this;

        $onceListener = function () use ($that, &$onceListener, $event, $listener) {
            $that->removeListener($event, $onceListener);

            call_user_func_array($listener, func_get_args());
        };

        $this->on($event, $onceListener);
    }

    public function removeListener($event, $listener)
    {
        if (isset($this->listeners[$event])) {
            if (false !== $index = array_search($listener, $this->listeners[$event], true)) {
                unset($this->listeners[$event][$index]);
            }
        }
    }

    public function removeAllListeners($event = null)
    {
        if ($event !== null) {
            unset($this->listeners[$event]);
        } else {
            $this->listeners = array();
        }
    }

    public function listeners($event)
    {
        return isset($this->listeners[$event]) ? $this->listeners[$event] : array();
    }

    public function emit($event, array $arguments = array())
    {
        foreach ($this->listeners($event) as $listener) {
            call_user_func_array($listener, $arguments);
        }
    }
}
