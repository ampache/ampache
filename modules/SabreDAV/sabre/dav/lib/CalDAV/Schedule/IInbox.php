<?php

namespace Sabre\CalDAV\Schedule;

/**
 * Implement this interface to have a node be recognized as a CalDAV scheduling
 * inbox.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface IInbox extends \Sabre\CalDAV\ICalendarObjectContainer, \Sabre\DAVACL\IACL {

}
