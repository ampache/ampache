<?php

namespace Sabre\Event;

use Exception;

/**
 * An implementation of the Promise pattern.
 *
 * Promises basically allow you to avoid what is commonly called 'callback
 * hell'. It allows for easily chaining of asynchronous operations.
 *
 * @copyright Copyright (C) 2013-2014 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Promise {

    /**
     * Pending promise. No result yet.
     */
    const PENDING = 0;

    /**
     * The promise has been fulfilled. It was successful.
     */
    const FULFILLED = 1;

    /**
     * The promise was rejected. The operation failed.
     */
    const REJECTED = 2;

    /**
     * The current state of this promise.
     *
     * @var int
     */
    protected $state = self::PENDING;

    /**
     * A list of subscribers. Subscribers are the callbacks that want us to let
     * them know if the callback was fulfilled or rejected.
     *
     * @var array
     */
    protected $subscribers = [];

    /**
     * The result of the promise.
     *
     * If the promise was fulfilled, this will be the result value. If the
     * promise was rejected, this is most commonly an exception.
     *
     * @var mixed
     */
    protected $value = null;

    /**
     * Creates the promise.
     *
     * The passed argument is the executor. The executor is automatically
     * called with two arguments.
     *
     * Each are callbacks that map to $this->fulfill and $this->reject.
     * Using the executor is optional.
     *
     * @param callable $executor
     */
    public function __construct(callable $executor = null) {

        if ($executor) {
            $executor(
                [$this, 'fulfill'],
                [$this, 'reject']
            );
        }

    }

    /**
     * This method allows you to specify the callback that will be called after
     * the promise has been fulfilled or rejected.
     *
     * Both arguments are optional.
     *
     * This method returns a new promise, which can be used for chaining.
     * If either the onFulfilled or onRejected callback is called, you may
     * return a result from this callback.
     *
     * If the result of this callback is yet another promise, the result of
     * _that_ promise will be used to set the result of the returned promise.
     *
     * If either of the callbacks return any other value, the returned promise
     * is automatically fulfilled with that value.
     *
     * If either of the callbacks throw an exception, the returned promise will
     * be rejected and the exception will be passed back.
     *
     * @param callable $onFulfilled
     * @param callable $onRejected
     * @return Promise
     */
    public function then(callable $onFulfilled = null, callable $onRejected = null) {

        $subPromise = new Promise();
        switch($this->state) {
            case self::PENDING :
                $this->subscribers[] = [$subPromise, $onFulfilled, $onRejected];
                break;
            case self::FULFILLED :
                $this->invokeCallback($subPromise, $onFulfilled);
                break;
            case self::REJECTED :
                $this->invokeCallback($subPromise, $onRejected);
                break;
        }
        return $subPromise;

    }

    /**
     * Add a callback for when this promise is rejected.
     *
     * I would have used the word 'catch', but it's a reserved word in PHP, so
     * we're not allowed to call our function that.
     *
     * @param callable $onRejected
     * @return void
     */
    public function error(callable $onRejected) {

        return $this->then(null, $onRejected);

    }

    /**
     * Marks this promise as fulfilled and sets its return value.
     *
     * @param mixed $value
     * @return void
     */
    public function fulfill($value = null) {
        if ($this->state !== self::PENDING) {
            throw new PromiseAlreadyResolvedException('This promise is already resolved, and you\'re not allowed to resolve a promise more than once');
        }
        $this->state = self::FULFILLED;
        $this->value = $value;
        foreach($this->subscribers as $subscriber) {
            $this->invokeCallback($subscriber[0], $subscriber[1]);
        }
    }

    /**
     * Marks this promise as rejected, and set it's rejection reason.
     *
     * @param mixed $value
     * @return void
     */
    public function reject($reason = null) {
        if ($this->state !== self::PENDING) {
            throw new PromiseAlreadyResolvedException('This promise is already resolved, and you\'re not allowed to resolve a promise more than once');
        }
        $this->state = self::REJECTED;
        $this->value = $reason;
        foreach($this->subscribers as $subscriber) {
            $this->invokeCallback($subscriber[0], $subscriber[2]);
        }

    }

    /**
     * It's possible to send an array of promises to the all method. This
     * method returns a promise that will be fulfilled, only if all the passed
     * promises are fulfilled.
     *
     * @param Promise[] $promises
     * @return Promise
     */
    static function all(array $promises) {

        return new self(function($success, $fail) use ($promises) {

            $successCount = 0;
            $completeResult = [];

            foreach($promises as $promiseIndex => $subPromise) {

                $subPromise->then(
                    function($result) use ($promiseIndex, &$completeResult, &$successCount, $success, $promises) {
                        $completeResult[$promiseIndex] = $result;
                        $successCount++;
                        if ($successCount===count($promises)) {
                            $success($completeResult);
                        }
                        return $result;
                })->error(
                    function($reason) use ($fail) {
                        $fail($reason);
                    }
                );

            }
        });

    }

    /**
     * This method is used to call either an onFulfilled or onRejected callback.
     *
     * This method makes sure that the result of these callbacks are handled
     * correctly, and any chained promises are also correctly fulfilled or
     * rejected.
     *
     * @param Promise $subPromise
     * @param callable $callBack
     * @return void
     */
    protected function invokeCallback(Promise $subPromise, callable $callBack = null) {

        if (is_callable($callBack)) {
            try {
                $result = $callBack($this->value);
                if ($result instanceof Promise) {
                    $result->then([$subPromise,'fulfill'], [$subPromise,'reject']);
                } else {
                    $subPromise->fulfill($result);
                }
            } catch (Exception $e) {
                $subPromise->reject($e);
            }
        } else {
            if ($this->state === self::FULFILLED) {
                $subPromise->fulfill($this->value);
            } else {
                $subPromise->reject($this->value);
            }
        }
    }


}
