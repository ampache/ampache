<?php

namespace Sabre\VObject\Property\ICalendar;

use DateTimeZone;
use Sabre\VObject\Property;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\TimeZoneUtil;

/**
 * DateTime property
 *
 * This object represents DATE-TIME values, as defined here:
 *
 * http://tools.ietf.org/html/rfc5545#section-3.3.4
 *
 * This particular object has a bit of hackish magic that it may also in some
 * cases represent a DATE value. This is because it's a common usecase to be
 * able to change a DATE-TIME into a DATE.
 *
 * @copyright Copyright (C) 2011-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class DateTime extends Property {

    /**
     * In case this is a multi-value property. This string will be used as a
     * delimiter.
     *
     * @var string|null
     */
    public $delimiter = ',';

    /**
     * Sets a multi-valued property.
     *
     * You may also specify DateTime objects here.
     *
     * @param array $parts
     * @return void
     */
    public function setParts(array $parts) {

        if (isset($parts[0]) && $parts[0] instanceof \DateTime) {
            $this->setDateTimes($parts);
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

        if (is_array($value) && isset($value[0]) && $value[0] instanceof \DateTime) {
            $this->setDateTimes($value);
        } elseif ($value instanceof \DateTime) {
            $this->setDateTimes(array($value));
        } else {
            parent::setValue($value);
        }

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

        $this->setValue(explode($this->delimiter, $val));

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
     * Returns true if this is a DATE-TIME value, false if it's a DATE.
     *
     * @return bool
     */
    public function hasTime() {

        return strtoupper((string)$this['VALUE']) !== 'DATE';

    }

    /**
     * Returns true if this is a floating DATE or DATE-TIME.
     *
     * Note that DATE is always floating.
     */
    public function isFloating() {

        return
            !$this->hasTime() ||
            (
                !isset($this['TZID']) &&
                strpos($this->getValue(),'Z')===false
            );

    }

    /**
     * Returns a date-time value.
     *
     * Note that if this property contained more than 1 date-time, only the
     * first will be returned. To get an array with multiple values, call
     * getDateTimes.
     *
     * If no timezone information is known, because it's either an all-day
     * property or floating time, we will use the DateTimeZone argument to
     * figure out the exact date.
     *
     * @param DateTimeZone $timeZone
     * @return \DateTime
     */
    public function getDateTime(DateTimeZone $timeZone = null) {

        $dt = $this->getDateTimes($timeZone);
        if (!$dt) return null;

        return $dt[0];

    }

    /**
     * Returns multiple date-time values.
     *
     * If no timezone information is known, because it's either an all-day
     * property or floating time, we will use the DateTimeZone argument to
     * figure out the exact date.
     *
     * @param DateTimeZone $timeZone
     * @return \DateTime[]
     */
    public function getDateTimes(DateTimeZone $timeZone = null) {

        // Does the property have a TZID?
        $tzid = $this['TZID'];

        if ($tzid) {
            $timeZone = TimeZoneUtil::getTimeZone((string)$tzid, $this->root);
        }

        $dts = array();
        foreach($this->getParts() as $part) {
            $dts[] = DateTimeParser::parse($part, $timeZone);
        }
        return $dts;

    }

    /**
     * Sets the property as a DateTime object.
     *
     * @param \DateTime $dt
     * @param bool isFloating If set to true, timezones will be ignored.
     * @return void
     */
    public function setDateTime(\DateTime $dt, $isFloating = false) {

        $this->setDateTimes(array($dt), $isFloating);

    }

    /**
     * Sets the property as multiple date-time objects.
     *
     * The first value will be used as a reference for the timezones, and all
     * the otehr values will be adjusted for that timezone
     *
     * @param \DateTime[] $dt
     * @param bool isFloating If set to true, timezones will be ignored.
     * @return void
     */
    public function setDateTimes(array $dt, $isFloating = false) {

        $values = array();

        if($this->hasTime()) {

            $tz = null;
            $isUtc = false;

            foreach($dt as $d) {

                if ($isFloating) {
                    $values[] = $d->format('Ymd\\THis');
                    continue;
                }
                if (is_null($tz)) {
                    $tz = $d->getTimeZone();
                    $isUtc = in_array($tz->getName() , array('UTC', 'GMT', 'Z'));
                    if (!$isUtc) {
                        $this->offsetSet('TZID', $tz->getName());
                    }
                } else {
                    $d->setTimeZone($tz);
                }

                if ($isUtc) {
                    $values[] = $d->format('Ymd\\THis\\Z');
                } else {
                    $values[] = $d->format('Ymd\\THis');
                }

            }
            if ($isUtc || $isFloating) {
                $this->offsetUnset('TZID');
            }

        } else {

            foreach($dt as $d) {

                $values[] = $d->format('Ymd');

            }
            $this->offsetUnset('TZID');

        }

        $this->value = $values;

    }

    /**
     * Returns the type of value.
     *
     * This corresponds to the VALUE= parameter. Every property also has a
     * 'default' valueType.
     *
     * @return string
     */
    public function getValueType() {

        return $this->hasTime()?'DATE-TIME':'DATE';

    }

    /**
     * Returns the value, in the format it should be encoded for json.
     *
     * This method must always return an array.
     *
     * @return array
     */
    public function getJsonValue() {

        $dts = $this->getDateTimes();
        $hasTime = $this->hasTime();
        $isFloating = $this->isFloating();

        $tz = $dts[0]->getTimeZone();
        $isUtc = $isFloating ? false : in_array($tz->getName() , array('UTC', 'GMT', 'Z'));

        return array_map(
            function($dt) use ($hasTime, $isUtc) {

                if ($hasTime) {
                    return $dt->format('Y-m-d\\TH:i:s') . ($isUtc?'Z':'');
                } else {
                    return $dt->format('Y-m-d');
                }

            },
            $dts
        );

    }

    /**
     * Sets the json value, as it would appear in a jCard or jCal object.
     *
     * The value must always be an array.
     *
     * @param array $value
     * @return void
     */
    public function setJsonValue(array $value) {

        // dates and times in jCal have one difference to dates and times in
        // iCalendar. In jCal date-parts are separated by dashes, and
        // time-parts are separated by colons. It makes sense to just remove
        // those.
        $this->setValue(
            array_map(
                function($item) {

                    return strtr($item, array(':'=>'', '-'=>''));

                },
                $value
            )
        );

    }
    /**
     * We need to intercept offsetSet, because it may be used to alter the
     * VALUE from DATE-TIME to DATE or vice-versa.
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function offsetSet($name, $value) {

        parent::offsetSet($name, $value);
        if (strtoupper($name)!=='VALUE') {
            return;
        }

        // This will ensure that dates are correctly encoded.
        $this->setDateTimes($this->getDateTimes());

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
        $valueType = $this->getValueType();
        $value = $this->getValue();
        try {
            switch($valueType) {
                case 'DATE' :
                    $foo = DateTimeParser::parseDate($value);
                    break;
                case 'DATE-TIME' :
                    $foo = DateTimeParser::parseDateTime($value);
                    break;
            }
        } catch (\LogicException $e) {
            $messages[] = array(
                'level' => 3,
                'message' => 'The supplied value (' . $value . ') is not a correct ' . $valueType,
                'node' => $this,
            );
        }
        return $messages;

    }
}
