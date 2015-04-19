<?php

namespace Sabre\VObject\Recur;

use DateTime;
use InvalidArgumentException;
use Iterator;
use Sabre\VObject\DateTimeParser;


/**
 * RRuleParser
 *
 * This class receives an RRULE string, and allows you to iterate to get a list
 * of dates in that recurrence.
 *
 * For instance, passing: FREQ=DAILY;LIMIT=5 will cause the iterator to contain
 * 5 items, one for each day.
 *
 * @copyright Copyright (C) 2011-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class RDateIterator implements Iterator {

    /**
     * Creates the Iterator.
     *
     * @param string|array $rrule
     * @param DateTime $start
     */
    public function __construct($rrule, DateTime $start) {

        $this->startDate = $start;
        $this->parseRDate($rrule);
        $this->currentDate = clone $this->startDate;

    }

    /* Implementation of the Iterator interface {{{ */

    public function current() {

        if (!$this->valid()) return null;
        return clone $this->currentDate;

    }

    /**
     * Returns the current item number.
     *
     * @return int
     */
    public function key() {

        return $this->counter;

    }

    /**
     * Returns whether the current item is a valid item for the recurrence
     * iterator.
     *
     * @return bool
     */
    public function valid() {

        return ($this->counter <= count($this->dates));

    }

    /**
     * Resets the iterator.
     *
     * @return void
     */
    public function rewind() {

        $this->currentDate = clone $this->startDate;
        $this->counter = 0;

    }

    /**
     * Goes on to the next iteration.
     *
     * @return void
     */
    public function next() {

        $this->counter++;
        if (!$this->valid()) return;

        $this->currentDate =
            DateTimeParser::parse(
                $this->dates[$this->counter-1]
            );

    }

    /* End of Iterator implementation }}} */

    /**
     * Returns true if this recurring event never ends.
     *
     * @return bool
     */
    public function isInfinite() {

        return false;

    }

    /**
     * This method allows you to quickly go to the next occurrence after the
     * specified date.
     *
     * @param DateTime $dt
     * @return void
     */
    public function fastForward(\DateTime $dt) {

        while($this->valid() && $this->currentDate < $dt ) {
            $this->next();
        }

    }

    /**
     * The reference start date/time for the rrule.
     *
     * All calculations are based on this initial date.
     *
     * @var DateTime
     */
    protected $startDate;

    /**
     * The date of the current iteration. You can get this by calling
     * ->current().
     *
     * @var DateTime
     */
    protected $currentDate;

    /**
     * The current item in the list.
     *
     * You can get this number with the key() method.
     *
     * @var int
     */
    protected $counter = 0;

    /* }}} */

    /**
     * This method receives a string from an RRULE property, and populates this
     * class with all the values.
     *
     * @param string|array $rrule
     * @return void
     */
    protected function parseRDate($rdate) {

        if (is_string($rdate)) {
            $rdate = explode(',', $rdate);
        }

        $this->dates = $rdate;

    }

}
