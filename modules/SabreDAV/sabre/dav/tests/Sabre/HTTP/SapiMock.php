<?php

namespace Sabre\HTTP;

/**
 * HTTP Response Mock object
 *
 * This class exists to make the transition to sabre/http easier.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class SapiMock extends Sapi {

    static $sent = 0;

    /**
     * Overriding this so nothing is ever echo'd.
     *
     * @return void
     */
    static function sendResponse(\Sabre\HTTP\ResponseInterface $r) {

        self::$sent++;

    }

}
