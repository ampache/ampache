<?php

namespace Sabre\VObject\Property;

use Sabre\VObject\DateTimeParser;

/**
 * Time property
 *
 * This object encodes TIME values.
 *
 * @copyright Copyright (C) 2011-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Time extends Text {

    /**
     * In case this is a multi-value property. This string will be used as a
     * delimiter.
     *
     * @var string|null
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

        return "TIME";

    }

    /**
     * Returns the value, in the format it should be encoded for json.
     *
     * This method must always return an array.
     *
     * @return array
     */
    public function getJsonValue() {

        $parts = DateTimeParser::parseVCardTime($this->getValue());

        $timeStr = '';

        // Hour
        if (!is_null($parts['hour'])) {
            $timeStr.=$parts['hour'];

            if (!is_null($parts['minute'])) {
                $timeStr.=':';
            }
        } else {
            // We know either minute or second _must_ be set, so we insert a
            // dash for an empty value.
            $timeStr.='-';
        }

        // Minute
        if (!is_null($parts['minute'])) {
            $timeStr.=$parts['minute'];

            if (!is_null($parts['second'])) {
                $timeStr.=':';
            }
        } else {
            if (isset($parts['second'])) {
                // Dash for empty minute
                $timeStr.='-';
            }
        }

        // Second
        if (!is_null($parts['second'])) {
            $timeStr.=$parts['second'];
        }

        // Timezone
        if (!is_null($parts['timezone'])) {
            $timeStr.=$parts['timezone'];
        }

        return array($timeStr);

    }

}
