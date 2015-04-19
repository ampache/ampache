<?php

namespace Sabre\VObject;

/**
 * iCalendar/vCard/jCal/jCard reader object.
 *
 * This object provides a few (static) convenience methods to quickly access
 * the parsers.
 *
 * @copyright Copyright (C) 2011-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Reader {

    /**
     * If this option is passed to the reader, it will be less strict about the
     * validity of the lines.
     */
    const OPTION_FORGIVING = 1;

    /**
     * If this option is turned on, any lines we cannot parse will be ignored
     * by the reader.
     */
    const OPTION_IGNORE_INVALID_LINES = 2;

    /**
     * Parses a vCard or iCalendar object, and returns the top component.
     *
     * The options argument is a bitfield. Pass any of the OPTIONS constant to
     * alter the parsers' behaviour.
     *
     * You can either supply a string, or a readable stream for input.
     *
     * @param string|resource $data
     * @param int $options
     * @return Document
     */
    static public function read($data, $options = 0) {

        $parser = new Parser\MimeDir();
        $result = $parser->parse($data, $options);

        return $result;

    }

    /**
     * Parses a jCard or jCal object, and returns the top component.
     *
     * The options argument is a bitfield. Pass any of the OPTIONS constant to
     * alter the parsers' behaviour.
     *
     * You can either a string, a readable stream, or an array for it's input.
     * Specifying the array is useful if json_decode was already called on the
     * input.
     *
     * @param string|resource|array $data
     * @param int $options
     * @return Node
     */
    static public function readJson($data, $options = 0) {

        $parser = new Parser\Json();
        $result = $parser->parse($data, $options);

        return $result;

    }

}
