<?php

namespace Sabre\VObject\Property\VCard;

use
    Sabre\VObject\DateTimeParser,
    Sabre\VObject\Property\Text;

/**
 * TimeStamp property
 *
 * This object encodes TIMESTAMP values.
 *
 * @copyright Copyright (C) 2011-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class TimeStamp extends Text {

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

        return "TIMESTAMP";

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

        $dateStr =
            $parts['year'] . '-' .
            $parts['month'] . '-' .
            $parts['date'] . 'T' .
            $parts['hour'] . ':' .
            $parts['minute'] . ':' .
            $parts['second'];

        // Timezone
        if (!is_null($parts['timezone'])) {
            $dateStr.=$parts['timezone'];
        }

        return array($dateStr);

    }
}
