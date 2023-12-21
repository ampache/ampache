<?php

declare(strict_types=1);

/**
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

namespace Ampache\Module\Util\Rss;

use Ampache\Gui\TalFactoryInterface;
use Ampache\Module\Util\Rss\Surrogate\RssItemInterface;
use Ampache\Repository\Model\User;
use PhpTal\PHPTAL;

/**
 * Builds ths rss-podcast xml structure
 *
 * Uses phptal and a podcast-template to build the podcast xml
 */
final class RssPodcastBuilder implements RssPodcastBuilderInterface
{
    private TalFactoryInterface $talFactory;

    public function __construct(
        TalFactoryInterface $talFactory
    ) {
        $this->talFactory   = $talFactory;
    }

    /**
     * Returns the rss-podcast xml
     */
    public function build(
        RssItemInterface $rssItemAdapter,
        User $user
    ): string {
        $tal = $this->talFactory->createPhpTal();
        $tal->setOutputMode(PHPTAL::XML);
        $tal->setTemplate((string) realpath(__DIR__ . '/../../../../resources/templates/rss/podcast.xml'));
        $tal->set('THIS', $rssItemAdapter);

        return $tal->execute();
    }
}
