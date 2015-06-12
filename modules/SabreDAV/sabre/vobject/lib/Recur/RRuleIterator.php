<?php

namespace Sabre\VObject\Recur;

use DateTime;
use InvalidArgumentException;
use Iterator;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\Property;


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
class RRuleIterator implements Iterator {

    /**
     * Creates the Iterator
     *
     * @param string|array $rrule
     * @param DateTime $start
     */
    public function __construct($rrule, DateTime $start) {

        $this->startDate = $start;
        $this->parseRRule($rrule);
        $this->currentDate = clone $this->startDate;

    }

    /* Implementation of the Iterator interface {{{ */

    public function current() {

        if (!$this->valid()) return null;
        return clone $this->currentDate;

    }

    /**
     * Returns the current item number
     *
     * @return int
     */
    public function key() {

        return $this->counter;

    }

    /**
     * Returns whether the current item is a valid item for the recurrence
     * iterator. This will return false if we've gone beyond the UNTIL or COUNT
     * statements.
     *
     * @return bool
     */
    public function valid() {

        if (!is_null($this->count)) {
            return $this->counter < $this->count;
        }
        return is_null($this->until) || $this->currentDate <= $this->until;

    }

    /**
     * Resets the iterator
     *
     * @return void
     */
    public function rewind() {

        $this->currentDate = clone $this->startDate;
        $this->counter = 0;

    }

    /**
     * Goes on to the next iteration
     *
     * @return void
     */
    public function next() {

        $previousStamp = $this->currentDate->getTimeStamp();

        // Otherwise, we find the next event in the normal RRULE
        // sequence.
        switch($this->frequency) {

            case 'hourly' :
                $this->nextHourly();
                break;

            case 'daily' :
                $this->nextDaily();
                break;

            case 'weekly' :
                $this->nextWeekly();
                break;

            case 'monthly' :
                $this->nextMonthly();
                break;

            case 'yearly' :
                $this->nextYearly();
                break;

        }
        $this->counter++;

    }

    /* End of Iterator implementation }}} */

    /**
     * Returns true if this recurring event never ends.
     *
     * @return bool
     */
    public function isInfinite() {

        return !$this->count && !$this->until;

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
     * Frequency is one of: secondly, minutely, hourly, daily, weekly, monthly,
     * yearly.
     *
     * @var string
     */
    protected $frequency;

    /**
     * The number of recurrences, or 'null' if infinitely recurring.
     *
     * @var int
     */
    protected $count;

    /**
     * The interval.
     *
     * If for example frequency is set to daily, interval = 2 would mean every
     * 2 days.
     *
     * @var int
     */
    protected $interval = 1;

    /**
     * The last instance of this recurrence, inclusively
     *
     * @var \DateTime|null
     */
    protected $until;

    /**
     * Which seconds to recur.
     *
     * This is an array of integers (between 0 and 60)
     *
     * @var array
     */
    protected $bySecond;

    /**
     * Which minutes to recur
     *
     * This is an array of integers (between 0 and 59)
     *
     * @var array
     */
    protected $byMinute;

    /**
     * Which hours to recur
     *
     * This is an array of integers (between 0 and 23)
     *
     * @var array
     */
    protected $byHour;

    /**
     * The current item in the list.
     *
     * You can get this number with the key() method.
     *
     * @var int
     */
    protected $counter = 0;

    /**
     * Which weekdays to recur.
     *
     * This is an array of weekdays
     *
     * This may also be preceeded by a positive or negative integer. If present,
     * this indicates the nth occurrence of a specific day within the monthly or
     * yearly rrule. For instance, -2TU indicates the second-last tuesday of
     * the month, or year.
     *
     * @var array
     */
    protected $byDay;

    /**
     * Which days of the month to recur
     *
     * This is an array of days of the months (1-31). The value can also be
     * negative. -5 for instance means the 5th last day of the month.
     *
     * @var array
     */
    protected $byMonthDay;

    /**
     * Which days of the year to recur.
     *
     * This is an array with days of the year (1 to 366). The values can also
     * be negative. For instance, -1 will always represent the last day of the
     * year. (December 31st).
     *
     * @var array
     */
    protected $byYearDay;

    /**
     * Which week numbers to recur.
     *
     * This is an array of integers from 1 to 53. The values can also be
     * negative. -1 will always refer to the last week of the year.
     *
     * @var array
     */
    protected $byWeekNo;

    /**
     * Which months to recur.
     *
     * This is an array of integers from 1 to 12.
     *
     * @var array
     */
    protected $byMonth;

    /**
     * Which items in an existing st to recur.
     *
     * These numbers work together with an existing by* rule. It specifies
     * exactly which items of the existing by-rule to filter.
     *
     * Valid values are 1 to 366 and -1 to -366. As an example, this can be
     * used to recur the last workday of the month.
     *
     * This would be done by setting frequency to 'monthly', byDay to
     * 'MO,TU,WE,TH,FR' and bySetPos to -1.
     *
     * @var array
     */
    protected $bySetPos;

    /**
     * When the week starts.
     *
     * @var string
     */
    protected $weekStart = 'MO';

    /* Functions that advance the iterator {{{ */

    /**
     * Does the processing for advancing the iterator for hourly frequency.
     *
     * @return void
     */
    protected function nextHourly() {

        $this->currentDate->modify('+' . $this->interval . ' hours');

    }

    /**
     * Does the processing for advancing the iterator for daily frequency.
     *
     * @return void
     */
    protected function nextDaily() {

        if (!$this->byHour && !$this->byDay) {
            $this->currentDate->modify('+' . $this->interval . ' days');
            return;
        }

        if (isset($this->byHour)) {
            $recurrenceHours = $this->getHours();
        }

        if (isset($this->byDay)) {
            $recurrenceDays = $this->getDays();
        }

        if (isset($this->byMonth)) {
            $recurrenceMonths = $this->getMonths();
        }

        do {
            if ($this->byHour) {
                if ($this->currentDate->format('G') == '23') {
                    // to obey the interval rule
                    $this->currentDate->modify('+' . $this->interval-1 . ' days');
                }

                $this->currentDate->modify('+1 hours');

            } else {
                $this->currentDate->modify('+' . $this->interval . ' days');

            }

            // Current month of the year
            $currentMonth = $this->currentDate->format('n');

            // Current day of the week
            $currentDay = $this->currentDate->format('w');

            // Current hour of the day
            $currentHour = $this->currentDate->format('G');

        } while (
            ($this->byDay   && !in_array($currentDay, $recurrenceDays)) ||
            ($this->byHour  && !in_array($currentHour, $recurrenceHours)) ||
            ($this->byMonth && !in_array($currentMonth, $recurrenceMonths))
        );

    }

    /**
     * Does the processing for advancing the iterator for weekly frequency.
     *
     * @return void
     */
    protected function nextWeekly() {

        if (!$this->byHour && !$this->byDay) {
            $this->currentDate->modify('+' . $this->interval . ' weeks');
            return;
        }

        if ($this->byHour) {
            $recurrenceHours = $this->getHours();
        }

        if ($this->byDay) {
            $recurrenceDays = $this->getDays();
        }

        // First day of the week:
        $firstDay = $this->dayMap[$this->weekStart];

        do {

            if ($this->byHour) {
                $this->currentDate->modify('+1 hours');
            } else {
                $this->currentDate->modify('+1 days');
            }

            // Current day of the week
            $currentDay = (int) $this->currentDate->format('w');

            // Current hour of the day
            $currentHour = (int) $this->currentDate->format('G');

            // We need to roll over to the next week
            if ($currentDay === $firstDay && (!$this->byHour || $currentHour == '0')) {
                $this->currentDate->modify('+' . $this->interval-1 . ' weeks');

                // We need to go to the first day of this week, but only if we
                // are not already on this first day of this week.
                if($this->currentDate->format('w') != $firstDay) {
                    $this->currentDate->modify('last ' . $this->dayNames[$this->dayMap[$this->weekStart]]);
                }
            }

            // We have a match
        } while (($this->byDay && !in_array($currentDay, $recurrenceDays)) || ($this->byHour && !in_array($currentHour, $recurrenceHours)));
    }

    /**
     * Does the processing for advancing the iterator for monthly frequency.
     *
     * @return void
     */
    protected function nextMonthly() {

        $currentDayOfMonth = $this->currentDate->format('j');
        if (!$this->byMonthDay && !$this->byDay) {

            // If the current day is higher than the 28th, rollover can
            // occur to the next month. We Must skip these invalid
            // entries.
            if ($currentDayOfMonth < 29) {
                $this->currentDate->modify('+' . $this->interval . ' months');
            } else {
                $increase = 0;
                do {
                    $increase++;
                    $tempDate = clone $this->currentDate;
                    $tempDate->modify('+ ' . ($this->interval*$increase) . ' months');
                } while ($tempDate->format('j') != $currentDayOfMonth);
                $this->currentDate = $tempDate;
            }
            return;
        }

        while(true) {

            $occurrences = $this->getMonthlyOccurrences();

            foreach($occurrences as $occurrence) {

                // The first occurrence thats higher than the current
                // day of the month wins.
                if ($occurrence > $currentDayOfMonth) {
                    break 2;
                }

            }

            // If we made it all the way here, it means there were no
            // valid occurrences, and we need to advance to the next
            // month.
            //
            // This line does not currently work in hhvm. Temporary workaround
            // follows:
            // $this->currentDate->modify('first day of this month');
            $this->currentDate = new \DateTime($this->currentDate->format('Y-m-1 H:i:s'), $this->currentDate->getTimezone());
            // end of workaround
            $this->currentDate->modify('+ ' . $this->interval . ' months');

            // This goes to 0 because we need to start counting at the
            // beginning.
            $currentDayOfMonth = 0;

        }

        $this->currentDate->setDate($this->currentDate->format('Y'), $this->currentDate->format('n'), $occurrence);

    }

    /**
     * Does the processing for advancing the iterator for yearly frequency.
     *
     * @return void
     */
    protected function nextYearly() {

        $currentMonth = $this->currentDate->format('n');
        $currentYear = $this->currentDate->format('Y');
        $currentDayOfMonth = $this->currentDate->format('j');

        // No sub-rules, so we just advance by year
        if (!$this->byMonth) {

            // Unless it was a leap day!
            if ($currentMonth==2 && $currentDayOfMonth==29) {

                $counter = 0;
                do {
                    $counter++;
                    // Here we increase the year count by the interval, until
                    // we hit a date that's also in a leap year.
                    //
                    // We could just find the next interval that's dividable by
                    // 4, but that would ignore the rule that there's no leap
                    // year every year that's dividable by a 100, but not by
                    // 400. (1800, 1900, 2100). So we just rely on the datetime
                    // functions instead.
                    $nextDate = clone $this->currentDate;
                    $nextDate->modify('+ ' . ($this->interval*$counter) . ' years');
                } while ($nextDate->format('n')!=2);
                $this->currentDate = $nextDate;

                return;

            }

            // The easiest form
            $this->currentDate->modify('+' . $this->interval . ' years');
            return;

        }

        $currentMonth = $this->currentDate->format('n');
        $currentYear = $this->currentDate->format('Y');
        $currentDayOfMonth = $this->currentDate->format('j');

        $advancedToNewMonth = false;

        // If we got a byDay or getMonthDay filter, we must first expand
        // further.
        if ($this->byDay || $this->byMonthDay) {

            while(true) {

                $occurrences = $this->getMonthlyOccurrences();

                foreach($occurrences as $occurrence) {

                    // The first occurrence that's higher than the current
                    // day of the month wins.
                    // If we advanced to the next month or year, the first
                    // occurrence is always correct.
                    if ($occurrence > $currentDayOfMonth || $advancedToNewMonth) {
                        break 2;
                    }

                }

                // If we made it here, it means we need to advance to
                // the next month or year.
                $currentDayOfMonth = 1;
                $advancedToNewMonth = true;
                do {

                    $currentMonth++;
                    if ($currentMonth>12) {
                        $currentYear+=$this->interval;
                        $currentMonth = 1;
                    }
                } while (!in_array($currentMonth, $this->byMonth));

                $this->currentDate->setDate($currentYear, $currentMonth, $currentDayOfMonth);

            }

            // If we made it here, it means we got a valid occurrence
            $this->currentDate->setDate($currentYear, $currentMonth, $occurrence);
            return;

        } else {

            // These are the 'byMonth' rules, if there are no byDay or
            // byMonthDay sub-rules.
            do {

                $currentMonth++;
                if ($currentMonth>12) {
                    $currentYear+=$this->interval;
                    $currentMonth = 1;
                }
            } while (!in_array($currentMonth, $this->byMonth));
            $this->currentDate->setDate($currentYear, $currentMonth, $currentDayOfMonth);

            return;

        }

    }

    /* }}} */

    /**
     * This method receives a string from an RRULE property, and populates this
     * class with all the values.
     *
     * @param string|array $rrule
     * @return void
     */
    protected function parseRRule($rrule) {

        if (is_string($rrule)) {
            $rrule = Property\ICalendar\Recur::stringToArray($rrule);
        }

        foreach($rrule as $key=>$value) {

            $key = strtoupper($key);
            switch($key) {

                case 'FREQ' :
                    $value = strtolower($value);
                    if (!in_array(
                        $value,
                        array('secondly','minutely','hourly','daily','weekly','monthly','yearly')
                    )) {
                        throw new InvalidArgumentException('Unknown value for FREQ=' . strtoupper($value));
                    }
                    $this->frequency = $value;
                    break;

                case 'UNTIL' :
                    $this->until = DateTimeParser::parse($value, $this->startDate->getTimezone());

                    // In some cases events are generated with an UNTIL=
                    // parameter before the actual start of the event.
                    //
                    // Not sure why this is happening. We assume that the
                    // intention was that the event only recurs once.
                    //
                    // So we are modifying the parameter so our code doesn't
                    // break.
                    if($this->until < $this->startDate) {
                        $this->until = $this->startDate;
                    }
                    break;

                case 'INTERVAL' :
                    // No break

                case 'COUNT' :
                    $val = (int)$value;
                    if ($val < 1) {
                        throw new \InvalidArgumentException(strtoupper($key) . ' in RRULE must be a positive integer!');
                    }
                    $key = strtolower($key);
                    $this->$key = $val;
                    break;

                case 'BYSECOND' :
                    $this->bySecond = (array)$value;
                    break;

                case 'BYMINUTE' :
                    $this->byMinute = (array)$value;
                    break;

                case 'BYHOUR' :
                    $this->byHour = (array)$value;
                    break;

                case 'BYDAY' :
                    $value = (array)$value;
                    foreach($value as $part) {
                        if (!preg_match('#^  (-|\+)? ([1-5])? (MO|TU|WE|TH|FR|SA|SU) $# xi', $part)) {
                            throw new \InvalidArgumentException('Invalid part in BYDAY clause: ' . $part);
                        }
                    }
                    $this->byDay = $value;
                    break;

                case 'BYMONTHDAY' :
                    $this->byMonthDay = (array)$value;
                    break;

                case 'BYYEARDAY' :
                    $this->byYearDay = (array)$value;
                    break;

                case 'BYWEEKNO' :
                    $this->byWeekNo = (array)$value;
                    break;

                case 'BYMONTH' :
                    $this->byMonth = (array)$value;
                    break;

                case 'BYSETPOS' :
                    $this->bySetPos = (array)$value;
                    break;

                case 'WKST' :
                    $this->weekStart = strtoupper($value);
                    break;

                default:
                    throw new \InvalidArgumentException('Not supported: ' . strtoupper($key));

            }

        }

    }

    /**
     * Mappings between the day number and english day name.
     *
     * @var array
     */
    protected $dayNames = array(
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    );

    /**
     * Returns all the occurrences for a monthly frequency with a 'byDay' or
     * 'byMonthDay' expansion for the current month.
     *
     * The returned list is an array of integers with the day of month (1-31).
     *
     * @return array
     */
    protected function getMonthlyOccurrences() {

        $startDate = clone $this->currentDate;

        $byDayResults = array();

        // Our strategy is to simply go through the byDays, advance the date to
        // that point and add it to the results.
        if ($this->byDay) foreach($this->byDay as $day) {

            $dayName = $this->dayNames[$this->dayMap[substr($day,-2)]];


            // Dayname will be something like 'wednesday'. Now we need to find
            // all wednesdays in this month.
            $dayHits = array();

            // workaround for missing 'first day of the month' support in hhvm
            $checkDate = new \DateTime($startDate->format('Y-m-1'));
            // workaround modify always advancing the date even if the current day is a $dayName in hhvm
            if ($checkDate->format('l') !== $dayName) {
                $checkDate->modify($dayName);
            }

            do {
                $dayHits[] = $checkDate->format('j');
                $checkDate->modify('next ' . $dayName);
            } while ($checkDate->format('n') === $startDate->format('n'));

            // So now we have 'all wednesdays' for month. It is however
            // possible that the user only really wanted the 1st, 2nd or last
            // wednesday.
            if (strlen($day)>2) {
                $offset = (int)substr($day,0,-2);

                if ($offset>0) {
                    // It is possible that the day does not exist, such as a
                    // 5th or 6th wednesday of the month.
                    if (isset($dayHits[$offset-1])) {
                        $byDayResults[] = $dayHits[$offset-1];
                    }
                } else {

                    // if it was negative we count from the end of the array
                    $byDayResults[] = $dayHits[count($dayHits) + $offset];
                }
            } else {
                // There was no counter (first, second, last wednesdays), so we
                // just need to add the all to the list).
                $byDayResults = array_merge($byDayResults, $dayHits);

            }

        }

        $byMonthDayResults = array();
        if ($this->byMonthDay) foreach($this->byMonthDay as $monthDay) {

            // Removing values that are out of range for this month
            if ($monthDay > $startDate->format('t') ||
                $monthDay < 0-$startDate->format('t')) {
                    continue;
            }
            if ($monthDay>0) {
                $byMonthDayResults[] = $monthDay;
            } else {
                // Negative values
                $byMonthDayResults[] = $startDate->format('t') + 1 + $monthDay;
            }
        }

        // If there was just byDay or just byMonthDay, they just specify our
        // (almost) final list. If both were provided, then byDay limits the
        // list.
        if ($this->byMonthDay && $this->byDay) {
            $result = array_intersect($byMonthDayResults, $byDayResults);
        } elseif ($this->byMonthDay) {
            $result = $byMonthDayResults;
        } else {
            $result = $byDayResults;
        }
        $result = array_unique($result);
        sort($result, SORT_NUMERIC);

        // The last thing that needs checking is the BYSETPOS. If it's set, it
        // means only certain items in the set survive the filter.
        if (!$this->bySetPos) {
            return $result;
        }

        $filteredResult = array();
        foreach($this->bySetPos as $setPos) {

            if ($setPos<0) {
                $setPos = count($result)-($setPos+1);
            }
            if (isset($result[$setPos-1])) {
                $filteredResult[] = $result[$setPos-1];
            }
        }

        sort($filteredResult, SORT_NUMERIC);
        return $filteredResult;

    }

    /**
     * Simple mapping from iCalendar day names to day numbers
     *
     * @var array
     */
    protected $dayMap = array(
        'SU' => 0,
        'MO' => 1,
        'TU' => 2,
        'WE' => 3,
        'TH' => 4,
        'FR' => 5,
        'SA' => 6,
    );

    protected function getHours()
    {
        $recurrenceHours = array();
        foreach($this->byHour as $byHour) {
            $recurrenceHours[] = $byHour;
        }

        return $recurrenceHours;
    }

    protected function getDays() {

        $recurrenceDays = array();
        foreach($this->byDay as $byDay) {

            // The day may be preceeded with a positive (+n) or
            // negative (-n) integer. However, this does not make
            // sense in 'weekly' so we ignore it here.
            $recurrenceDays[] = $this->dayMap[substr($byDay,-2)];

        }

        return $recurrenceDays;
    }

    protected function getMonths() {

        $recurrenceMonths = array();
        foreach($this->byMonth as $byMonth) {
            $recurrenceMonths[] = $byMonth;
        }

        return $recurrenceMonths;
    }
}
