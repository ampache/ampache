<?php

namespace Sabre\Event;

/**
 * EventEmitter object.
 *
 * Instantiate this class, or subclass it for easily creating event emitters.
 *
 * @copyright Copyright (C) 2013-2014 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/
 */
class EventEmitter implements EventEmitterInterface {

    use EventEmitterTrait;

}
