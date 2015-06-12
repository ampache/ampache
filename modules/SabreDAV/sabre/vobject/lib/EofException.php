<?php

namespace Sabre\VObject;

/**
 * Exception thrown by parser when the end of the stream has been reached,
 * before this was expected.
 *
 * @copyright Copyright (C) 2011-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class EofException extends ParseException {

}
