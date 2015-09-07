<?php

namespace Sabre\VObject\Component;

use Sabre\VObject;

/**
 * The VAvailability component
 *
 * This component adds functionality to a component, specific for VAVAILABILITY
 * components.
 *
 * @copyright Copyright (C) 2011-2015 fruux GmbH (https://fruux.com/).
 * @author Ivan Enderlin
 * @license http://sabre.io/license/ Modified BSD License
 */
class VAvailability extends VObject\Component {

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
    function getValidationRules() {

        return array(
            'UID' => 1,
            'DTSTAMP' => 1,

            'BUSYTYPE' => '?',
            'CLASS' => '?',
            'CREATED' => '?',
            'DESCRIPTION' => '?',
            'DTSTART' => '?',
            'LAST-MODIFIED' => '?',
            'ORGANIZER' => '?',
            'PRIORITY' => '?',
            'SEQUENCE' => '?',
            'SUMMARY' => '?',
            'URL' => '?',
            'DTEND' => '?',
            'DURATION' => '?',

            'CATEGORIES' => '*',
            'COMMENT' => '*',
            'CONTACT' => '*',
        );

    }

    /**
     * Validates the node for correctness.
     *
     * The following options are supported:
     *   Node::REPAIR - May attempt to automatically repair the problem.
     *   Node::PROFILE_CARDDAV - Validate the vCard for CardDAV purposes.
     *   Node::PROFILE_CALDAV - Validate the iCalendar for CalDAV purposes.
     *
     * This method returns an array with detected problems.
     * Every element has the following properties:
     *
     *  * level - problem level.
     *  * message - A human-readable string describing the issue.
     *  * node - A reference to the problematic node.
     *
     * The level means:
     *   1 - The issue was repaired (only happens if REPAIR was turned on).
     *   2 - A warning.
     *   3 - An error.
     *
     * @param int $options
     * @return array
     */
    function validate($options = 0) {

        $result = parent::validate($options);

        if (isset($this->DTEND) && isset($this->DURATION)) {
            $result[] = array(
                'level' => 3,
                'message' => 'DTEND and DURATION cannot both be present',
                'node' => $this
            );
        }

        return $result;

    }
}
