<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Ampache\Module\Util;

use Ampache\Module\Util\FileSystem\FileNameConverter;
use Ampache\Module\Util\FileSystem\FileNameConverterInterface;
use function DI\autowire;

return [
    Horde_Browser::class => autowire(Horde_Browser::class),
    FileNameConverterInterface::class => autowire(FileNameConverter::class),
    RequestParserInterface::class => autowire(RequestParser::class),
    AjaxUriRetrieverInterface::class => autowire(AjaxUriRetriever::class),
    EnvironmentInterface::class => autowire(Environment::class),
    ZipHandlerInterface::class => autowire(ZipHandler::class),
    SlideshowInterface::class => autowire(Slideshow::class),
    UiInterface::class => autowire(Ui::class),
    Mailer::class => autowire(),
    UtilityFactoryInterface::class => autowire(UtilityFactory::class),
];
