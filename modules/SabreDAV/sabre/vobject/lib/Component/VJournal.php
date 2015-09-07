<?php

namespace Sabre\VObject\Component;

use Sabre\VObject;

/**
 * VJournal component
 *
 * This component contains some additional functionality specific for VJOURNALs.
 *
 * @copyright Copyright (C) 2011-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class VJournal extends VObject\Component {

    /**
     * Returns true or false depending on if the event falls in the specified
     * time-range. This is used for filtering purposes.
     *
     * The rules used to determine if an event falls within the specified
     * time-range is based on the CalDAV specification.
     *
     * @param DateTime $start
     * @param DateTime $end
     * @return bool
     */
    public function isInTimeRange(\DateTime $start, \DateTime $end) {

        $dtstart = isset($this->DTSTART)?$this->DTSTART->getDateTime():null;
        if ($dtstart) {
            $effectiveEnd = clone $dtstart;
            if (!$this->DTSTART->hasTime()) {
                $effectiveEnd->modify('+1 day');
            }

            return ($start <= $effectiveEnd && $end > $dtstart);

        }
        return false;

    }

    /**
     * A simple list of validation rules.
     *
     * This is simply a list of properties, and how many times they either
     * must or must not appear.
     *
     * Possible values per property:
     *   * 0 - Must not appear.
     *   * 1 - Must appear exactly once.
     *   * + - Must appear at least once.
     *   * * - Can appear any number of times.
     *   * ? - May appear, but not more than once.
     *
     * @var array
     */
    public function getValidationRules() {

        return array(
            'UID' => 1,
            'DTSTAMP' => 1,

            'CLASS' => '?',
            'CREATED' => '?',
            'DTSTART' => '?',
            'LAST-MODIFIED' => '?',
            'ORGANIZER' => '?',
            'RECURRENCE-ID' => '?',
            'SEQUENCE' => '?',
            'STATUS' => '?',
            'SUMMARY' => '?',
            'URL' => '?',

            'RRULE' => '?',

            'ATTACH' => '*',
            'ATTENDEE' => '*',
            'CATEGORIES' => '*',
            'COMMENT' => '*',
            'CONTACT' => '*',
            'DESCRIPTION' => '*',
            'EXDATE' => '*',
            'RELATED-TO' => '*',
            'RDATE' => '*',
        );

    }
}
