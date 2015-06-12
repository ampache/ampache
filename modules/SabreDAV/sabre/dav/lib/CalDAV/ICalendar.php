<?php

namespace Sabre\CalDAV;

use Sabre\DAV;
use Sabre\DAVACL;

/**
 * Calendar interface
 *
 * Implement this interface to allow a node to be recognized as an calendar.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface ICalendar extends ICalendarObjectContainer, DAVACL\IACL {

}
