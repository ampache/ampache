<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Gui;

use Ampache\Gui\Browse\ListRenderer\BrowseListRendererLocator;
use Ampache\Gui\Browse\ListRenderer\BrowseListRendererLocatorInterface;
use Ampache\Gui\Browse\ListRenderer\LabelListRenderer;
use Ampache\Gui\Browse\ListRenderer\LiveStreamListRenderer;
use Ampache\Gui\Browse\ListRenderer\PodcastListRenderer;
use Ampache\Gui\Browse\ListRenderer\ShoutboxListRenderer;
use Ampache\Gui\Browse\ListRenderer\WantedListRenderer;
use Ampache\Gui\Form\LoginFormViewFactory;
use Ampache\Gui\Form\LoginFormViewFactoryInterface;
use Ampache\Gui\Form\StatsFormViewFactory;
use Ampache\Gui\Form\StatsFormViewFactoryInterface;

use function DI\autowire;
use function DI\get;

return [
    GuiFactoryInterface::class => autowire(GuiFactory::class),
    // one renderer per migrated browse type; a type absent here still falls back to its .inc.php template
    BrowseListRendererLocatorInterface::class => autowire(BrowseListRendererLocator::class)
        ->constructorParameter('renderers', [
            'label' => get(LabelListRenderer::class),
            'live_stream' => get(LiveStreamListRenderer::class),
            'podcast' => get(PodcastListRenderer::class),
            'shoutbox' => get(ShoutboxListRenderer::class),
            'wanted' => get(WantedListRenderer::class),
        ]),
    LoginFormViewFactoryInterface::class => autowire(LoginFormViewFactory::class),
    StatsFormViewFactoryInterface::class => autowire(StatsFormViewFactory::class),
];
