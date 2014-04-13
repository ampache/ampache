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

class EventEmitter2 extends EventEmitter
{
    protected $options;
    protected $anyListeners = array();

    public function __construct(array $options = array())
    {
        $this->options = array_merge(array(
            'delimiter' => '.',
        ), $options);
    }

    public function onAny($listener)
    {
        $this->anyListeners[] = $listener;
    }

    public function offAny($listener)
    {
        if (false !== $index = array_search($listener, $this->anyListeners, true)) {
            unset($this->anyListeners[$index]);
        }
    }

    public function many($event, $timesToListen, $listener)
    {
        $that = $this;

        $timesListened = 0;

        if ($timesToListen == 0) {
            return;
        }

        if ($timesToListen < 0) {
            throw new \OutOfRangeException('You cannot listen less than zero times.');
        }

        $manyListener = function () use ($that, &$timesListened, &$manyListener, $event, $timesToListen, $listener) {
            if (++$timesListened == $timesToListen) {
                $that->removeListener($event, $manyListener);
            }

            call_user_func_array($listener, func_get_args());
        };

        $this->on($event, $manyListener);
    }

    public function emit($event, array $arguments = array())
    {
        foreach ($this->anyListeners as $listener) {
            call_user_func_array($listener, $arguments);
        }

        parent::emit($event, $arguments);
    }

    public function listeners($event)
    {
        $matchedListeners = array();

        foreach ($this->listeners as $name => $listeners) {
            foreach ($listeners as $listener) {
                if ($this->matchEventName($event, $name)) {
                    $matchedListeners[] = $listener;
                }
            }
        }

        return $matchedListeners;
    }

    protected function matchEventName($matchPattern, $eventName)
    {
        $patternParts = explode($this->options['delimiter'], $matchPattern);
        $nameParts = explode($this->options['delimiter'], $eventName);

        if (count($patternParts) != count($nameParts)) {
            return false;
        }

        $size = min(count($patternParts), count($nameParts));
        for ($i = 0; $i < $size; $i++) {
            $patternPart = $patternParts[$i];
            $namePart = $nameParts[$i];

            if ('*' === $patternPart || '*' === $namePart) {
                continue;
            }

            if ($namePart === $patternPart) {
                continue;
            }

            return false;
        }

        return true;
    }
}
