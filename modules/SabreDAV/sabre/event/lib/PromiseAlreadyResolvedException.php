<?php

namespace Sabre\Event;

/**
 * This exception is thrown when the user tried to reject or fulfill a promise,
 * after either of these actions were already performed.
 *
 * @copyright Copyright (C) 2013-2014 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class PromiseAlreadyResolvedException extends \LogicException {

}
