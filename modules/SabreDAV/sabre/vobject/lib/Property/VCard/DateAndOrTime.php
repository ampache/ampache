<?php

namespace Sabre\VObject\Property\VCard;

use
    Sabre\VObject\DateTimeParser,
    Sabre\VObject\Property\Text,
    Sabre\VObject\Property,
    DateTime;

/**
 * DateAndOrTime property
 *
 * This object encodes DATE-AND-OR-TIME values.
 *
 * @copyright Copyright (C) 2011-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class DateAndOrTime extends Property {

    /**
     * Field separator
     *
     * @var null|string
     */
    public $delimiter = null;

    /**
     * Returns the type of value.
     *
     * This corresponds to the VALUE= parameter. Every property also has a
     * 'default' valueType.
     *
     * @return string
     */
    public function getValueType() {

        return "DATE-AND-OR-TIME";

    }

    /**
     * Sets a multi-valued property.
     *
     * You may also specify DateTime objects here.
     *
     * @param array $parts
     * @return void
     */
    public function setParts(array $parts) {

        if (count($parts)>1) {
            throw new \InvalidArgumentException('Only one value allowed');
        }
        if (isset($parts[0]) && $parts[0] instanceof \DateTime) {
            $this->setDateTime($parts[0]);
        } else {
            parent::setParts($parts);
        }

    }

    /**
     * Updates the current value.
     *
     * This may be either a single, or multiple strings in an array.
     *
     * Instead of strings, you may also use DateTime here.
     *
     * @param string|array|\DateTime $value
     * @return void
     */
    public function setValue($value) {

        if ($value instanceof \DateTime) {
            $this->setDateTime($value);
        } else {
            parent::setValue($value);
        }

    }

    /**
     * Sets the property as a DateTime object.
     *
     * @param \DateTime $dt
     * @return void
     */
    public function setDateTime(\DateTime $dt) {

        $values = array();

        $tz = null;
        $isUtc = false;

        $tz = $dt->getTimeZone();
        $isUtc = in_array($tz->getName() , array('UTC', 'GMT', 'Z'));

        if ($isUtc) {
            $value = $dt->format('Ymd\\THis\\Z');
        } else {
            // Calculating the offset.
            $value = $dt->format('Ymd\\THisO');
        }

        $this->value = $value;

    }

    /**
     * Returns a date-time value.
     *
     * Note that if this property contained more than 1 date-time, only the
     * first will be returned. To get an array with multiple values, call
     * getDateTimes.
     *
     * If no time was specified, we will always use midnight (in the default
     * timezone) as the time.
     *
     * If parts of the date were omitted, such as the year, we will grab the
     * current values for those. So at the time of writing, if the year was
     * omitted, we would have filled in 2014.
     *
     * @return \DateTime
     */
    public function getDateTime() {

        $dts = array();
        $now = new DateTime();

        $tzFormat = $now->getTimezone()->getOffset($now)===0?'\\Z':'O';
        $nowParts = DateTimeParser::parseVCardDateTime($now->format('Ymd\\This' . $tzFormat));

        $value = $this->getValue();

        $dateParts = DateTimeParser::parseVCardDateTime($this->getValue());

        // This sets all the missing parts to the current date/time.
        // So if the year was missing for a birthday, we're making it 'this
        // year'.
        foreach($dateParts as $k=>$v) {
            if (is_null($v)) {
                $dateParts[$k] = $nowParts[$k];
            }
        }
        return new DateTime("$dateParts[year]-$dateParts[month]-$dateParts[date] $dateParts[hour]:$dateParts[minute]:$dateParts[second] $dateParts[timezone]");

    }

    /**
     * Returns the value, in the format it should be encoded for json.
     *
     * This method must always return an array.
     *
     * @return array
     */
    public function getJsonValue() {

        $parts = DateTimeParser::parseVCardDateTime($this->getValue());

        $dateStr = '';

        // Year
        if (!is_null($parts['year'])) {
            $dateStr.=$parts['year'];

            if (!is_null($parts['month'])) {
                // If a year and a month is set, we need to insert a separator
                // dash.
                $dateStr.='-';
            }

        } else {

            if (!is_null($parts['month']) || !is_null($parts['date'])) {
                // Inserting two dashes
                $dateStr.='--';
            }

        }

        // Month

        if (!is_null($parts['month'])) {
            $dateStr.=$parts['month'];

            if (isset($parts['date'])) {
                // If month and date are set, we need the separator dash.
                $dateStr.='-';
            }
        } else {
            if (isset($parts['date'])) {
                // If the month is empty, and a date is set, we need a 'empty
                // dash'
                $dateStr.='-';
            }
        }

        // Date
        if (!is_null($parts['date'])) {
            $dateStr.=$parts['date'];
        }


        // Early exit if we don't have a time string.
        if (is_null($parts['hour']) && is_null($parts['minute']) && is_null($parts['second'])) {
            return array($dateStr);
        }

        $dateStr.='T';

        // Hour
        if (!is_null($parts['hour'])) {
            $dateStr.=$parts['hour'];

            if (!is_null($parts['minute'])) {
                $dateStr.=':';
            }
        } else {
            // We know either minute or second _must_ be set, so we insert a
            // dash for an empty value.
            $dateStr.='-';
        }

        // Minute
        if (!is_null($parts['minute'])) {
            $dateStr.=$parts['minute'];

            if (!is_null($parts['second'])) {
                $dateStr.=':';
            }
        } else {
            if (isset($parts['second'])) {
                // Dash for empty minute
                $dateStr.='-';
            }
        }

        // Second
        if (!is_null($parts['second'])) {
            $dateStr.=$parts['second'];
        }

        // Timezone
        if (!is_null($parts['timezone'])) {
            $dateStr.=$parts['timezone'];
        }

        return array($dateStr);

    }

    /**
     * Sets a raw value coming from a mimedir (iCalendar/vCard) file.
     *
     * This has been 'unfolded', so only 1 line will be passed. Unescaping is
     * not yet done, but parameters are not included.
     *
     * @param string $val
     * @return void
     */
    public function setRawMimeDirValue($val) {

        $this->setValue($val);

    }

    /**
     * Returns a raw mime-dir representation of the value.
     *
     * @return string
     */
    public function getRawMimeDirValue() {

        return implode($this->delimiter, $this->getParts());

    }

    /**
     * Validates the node for correctness.
     *
     * The following options are supported:
     *   Node::REPAIR - May attempt to automatically repair the problem.
     *
     * This method returns an array with detected problems.
     * Every element has the following properties:
     *
     *  * level - problem level.
     *  * message - A human-readable string describing the issue.
     *  * node - A reference to the problematic node.
     *
     * The level means:
     *   1 - The issue was repaired (only happens if REPAIR was turned on)
     *   2 - An inconsequential issue
     *   3 - A severe issue.
     *
     * @param int $options
     * @return array
     */
    public function validate($options = 0) {

        $messages = parent::validate($options);
        $value = $this->getValue();
        try {
            DateTimeParser::parseVCardDateTime($value);
        } catch (\InvalidArgumentException $e) {
            $messages[] = array(
                'level' => 3,
                'message' => 'The supplied value (' . $value . ') is not a correct DATE-AND-OR-TIME property',
                'node' => $this,
            );
        }
        return $messages;

    }
}
