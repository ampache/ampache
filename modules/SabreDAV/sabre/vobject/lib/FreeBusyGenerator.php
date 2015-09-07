<?php

namespace Sabre\VObject;

use DateTimeZone;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Recur\EventIterator;
use Sabre\VObject\Recur\NoInstancesException;

/**
 * This class helps with generating FREEBUSY reports based on existing sets of
 * objects.
 *
 * It only looks at VEVENT and VFREEBUSY objects from the sourcedata, and
 * generates a single VFREEBUSY object.
 *
 * VFREEBUSY components are described in RFC5545, The rules for what should
 * go in a single freebusy report is taken from RFC4791, section 7.10.
 *
 * @copyright Copyright (C) 2011-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class FreeBusyGenerator {

    /**
     * Input objects
     *
     * @var array
     */
    protected $objects;

    /**
     * Start of range
     *
     * @var DateTime|null
     */
    protected $start;

    /**
     * End of range
     *
     * @var DateTime|null
     */
    protected $end;

    /**
     * VCALENDAR object
     *
     * @var Component
     */
    protected $baseObject;

    /**
     * Reference timezone.
     *
     * When we are calculating busy times, and we come across so-called
     * floating times (times without a timezone), we use the reference timezone
     * instead.
     *
     * This is also used for all-day events.
     *
     * This defaults to UTC.
     *
     * @var DateTimeZone
     */
    protected $timeZone;

    /**
     * Creates the generator.
     *
     * Check the setTimeRange and setObjects methods for details about the
     * arguments.
     *
     * @param DateTime $start
     * @param DateTime $end
     * @param mixed $objects
     * @param DateTimeZone $timeZone
     * @return void
     */
    public function __construct(\DateTime $start = null, \DateTime $end = null, $objects = null, DateTimeZone $timeZone = null) {

        if ($start && $end) {
            $this->setTimeRange($start, $end);
        }

        if ($objects) {
            $this->setObjects($objects);
        }
        if (is_null($timeZone)) {
            $timeZone = new DateTimeZone('UTC');
        }
        $this->setTimeZone($timeZone);

    }

    /**
     * Sets the VCALENDAR object.
     *
     * If this is set, it will not be generated for you. You are responsible
     * for setting things like the METHOD, CALSCALE, VERSION, etc..
     *
     * The VFREEBUSY object will be automatically added though.
     *
     * @param Component $vcalendar
     * @return void
     */
    public function setBaseObject(Component $vcalendar) {

        $this->baseObject = $vcalendar;

    }

    /**
     * Sets the input objects
     *
     * You must either specify a valendar object as a strong, or as the parse
     * Component.
     * It's also possible to specify multiple objects as an array.
     *
     * @param mixed $objects
     * @return void
     */
    public function setObjects($objects) {

        if (!is_array($objects)) {
            $objects = array($objects);
        }

        $this->objects = array();
        foreach($objects as $object) {

            if (is_string($object)) {
                $this->objects[] = Reader::read($object);
            } elseif ($object instanceof Component) {
                $this->objects[] = $object;
            } else {
                throw new \InvalidArgumentException('You can only pass strings or \\Sabre\\VObject\\Component arguments to setObjects');
            }

        }

    }

    /**
     * Sets the time range
     *
     * Any freebusy object falling outside of this time range will be ignored.
     *
     * @param DateTime $start
     * @param DateTime $end
     * @return void
     */
    public function setTimeRange(\DateTime $start = null, \DateTime $end = null) {

        $this->start = $start;
        $this->end = $end;

    }

    /**
     * Sets the reference timezone for floating times.
     *
     * @param DateTimeZone $timeZone
     * @return void
     */
    public function setTimeZone(DateTimeZone $timeZone) {

        $this->timeZone = $timeZone;

    }

    /**
     * Parses the input data and returns a correct VFREEBUSY object, wrapped in
     * a VCALENDAR.
     *
     * @return Component
     */
    public function getResult() {

        $busyTimes = array();

        foreach($this->objects as $key=>$object) {

            foreach($object->getBaseComponents() as $component) {

                switch($component->name) {

                    case 'VEVENT' :

                        $FBTYPE = 'BUSY';
                        if (isset($component->TRANSP) && (strtoupper($component->TRANSP) === 'TRANSPARENT')) {
                            break;
                        }
                        if (isset($component->STATUS)) {
                            $status = strtoupper($component->STATUS);
                            if ($status==='CANCELLED') {
                                break;
                            }
                            if ($status==='TENTATIVE') {
                                $FBTYPE = 'BUSY-TENTATIVE';
                            }
                        }

                        $times = array();

                        if ($component->RRULE) {
                            try {
                                $iterator = new EventIterator($object, (string)$component->uid, $this->timeZone);
                            } catch (NoInstancesException $e) {
                                // This event is recurring, but it doesn't have a single
                                // instance. We are skipping this event from the output
                                // entirely.
                                unset($this->objects[$key]);
                                continue;
                            }

                            if ($this->start) {
                                $iterator->fastForward($this->start);
                            }

                            $maxRecurrences = 200;

                            while($iterator->valid() && --$maxRecurrences) {

                                $startTime = $iterator->getDTStart();
                                if ($this->end && $startTime > $this->end) {
                                    break;
                                }
                                $times[] = array(
                                    $iterator->getDTStart(),
                                    $iterator->getDTEnd(),
                                );

                                $iterator->next();

                            }

                        } else {

                            $startTime = $component->DTSTART->getDateTime($this->timeZone);
                            if ($this->end && $startTime > $this->end) {
                                break;
                            }
                            $endTime = null;
                            if (isset($component->DTEND)) {
                                $endTime = $component->DTEND->getDateTime($this->timeZone);
                            } elseif (isset($component->DURATION)) {
                                $duration = DateTimeParser::parseDuration((string)$component->DURATION);
                                $endTime = clone $startTime;
                                $endTime->add($duration);
                            } elseif (!$component->DTSTART->hasTime()) {
                                $endTime = clone $startTime;
                                $endTime->modify('+1 day');
                            } else {
                                // The event had no duration (0 seconds)
                                break;
                            }

                            $times[] = array($startTime, $endTime);

                        }

                        foreach($times as $time) {

                            if ($this->end && $time[0] > $this->end) break;
                            if ($this->start && $time[1] < $this->start) break;

                            $busyTimes[] = array(
                                $time[0],
                                $time[1],
                                $FBTYPE,
                            );
                        }
                        break;

                    case 'VFREEBUSY' :
                        foreach($component->FREEBUSY as $freebusy) {

                            $fbType = isset($freebusy['FBTYPE'])?strtoupper($freebusy['FBTYPE']):'BUSY';

                            // Skipping intervals marked as 'free'
                            if ($fbType==='FREE')
                                continue;

                            $values = explode(',', $freebusy);
                            foreach($values as $value) {
                                list($startTime, $endTime) = explode('/', $value);
                                $startTime = DateTimeParser::parseDateTime($startTime);

                                if (substr($endTime,0,1)==='P' || substr($endTime,0,2)==='-P') {
                                    $duration = DateTimeParser::parseDuration($endTime);
                                    $endTime = clone $startTime;
                                    $endTime->add($duration);
                                } else {
                                    $endTime = DateTimeParser::parseDateTime($endTime);
                                }

                                if($this->start && $this->start > $endTime) continue;
                                if($this->end && $this->end < $startTime) continue;
                                $busyTimes[] = array(
                                    $startTime,
                                    $endTime,
                                    $fbType
                                );

                            }


                        }
                        break;



                }


            }

        }

        if ($this->baseObject) {
            $calendar = $this->baseObject;
        } else {
            $calendar = new VCalendar();
        }

        $vfreebusy = $calendar->createComponent('VFREEBUSY');
        $calendar->add($vfreebusy);

        if ($this->start) {
            $dtstart = $calendar->createProperty('DTSTART');
            $dtstart->setDateTime($this->start);
            $vfreebusy->add($dtstart);
        }
        if ($this->end) {
            $dtend = $calendar->createProperty('DTEND');
            $dtend->setDateTime($this->end);
            $vfreebusy->add($dtend);
        }
        $dtstamp = $calendar->createProperty('DTSTAMP');
        $dtstamp->setDateTime(new \DateTime('now', new \DateTimeZone('UTC')));
        $vfreebusy->add($dtstamp);

        foreach($busyTimes as $busyTime) {

            $busyTime[0]->setTimeZone(new \DateTimeZone('UTC'));
            $busyTime[1]->setTimeZone(new \DateTimeZone('UTC'));

            $prop = $calendar->createProperty(
                'FREEBUSY',
                $busyTime[0]->format('Ymd\\THis\\Z') . '/' . $busyTime[1]->format('Ymd\\THis\\Z')
            );
            $prop['FBTYPE'] = $busyTime[2];
            $vfreebusy->add($prop);

        }

        return $calendar;

    }

}
