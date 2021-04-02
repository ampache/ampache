<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 */

declare(strict_types=1);

namespace Ampache\Module\Podcast;

use Ampache\Module\System\AmpError;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\ExternalResourceLoaderInterface;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast;

final class PodcastCreator implements PodcastCreatorInterface
{
    private ModelFactoryInterface $modelFactory;

    private ExternalResourceLoaderInterface $externalResourceLoader;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        ExternalResourceLoaderInterface $externalResourceLoader
    ) {
        $this->modelFactory = $modelFactory;
        $this->externalResourceLoader = $externalResourceLoader;
    }

    public function create(
        string $feedUrl,
        int $catalog_id
    ): ?Podcast {
        // Feed must be http/https
        if (strpos($feedUrl, "http://") !== 0 && strpos($feedUrl, "https://") !== 0) {
            AmpError::add('feed', T_('Feed URL is invalid'));
        }

        if ($catalog_id < 1) {
            AmpError::add('catalog', T_('Target Catalog is required'));
        } else {
            $catalog = Catalog::create_from_id($catalog_id);
            if ($catalog->gather_types !== "podcast") {
                AmpError::add('catalog', T_('Wrong target Catalog type'));
            }
        }

        if (AmpError::occurred()) {
            return null;
        }

        $title         = T_('Unknown');
        $website       = null;
        $description   = null;
        $language      = null;
        $copyright     = null;
        $generator     = null;
        $lastbuilddate = 0;
        $episodes      = false;
        $arturl        = '';

        // don't allow duplicate podcasts
        $sql        = "SELECT `id` FROM `podcast` WHERE `feed`= '" . Dba::escape($feedUrl) . "'";
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results, false)) {
            if ((int) $row['id'] > 0) {
                return $this->modelFactory->createPodcast((int) $row['id']);
            }
        }

        $xmlstr = $this->externalResourceLoader->retrieve($feedUrl);
        if ($xmlstr === null) {
            AmpError::add('feed', T_('Can not access the feed'));
        } else {
            $xml = simplexml_load_string($xmlstr->getBody()->getContents());
            if ($xml === false) {
                AmpError::add('feed', T_('Can not read the feed'));
            } else {
                $title            = html_entity_decode((string)$xml->channel->title);
                $website          = (string)$xml->channel->link;
                $description      = html_entity_decode((string)$xml->channel->description);
                $language         = (string)$xml->channel->language;
                $copyright        = html_entity_decode((string)$xml->channel->copyright);
                $generator        = html_entity_decode((string)$xml->channel->generator);
                $lastbuilddatestr = (string)$xml->channel->lastBuildDate;
                if ($lastbuilddatestr) {
                    $lastbuilddate = strtotime($lastbuilddatestr);
                }

                if ($xml->channel->image) {
                    $arturl = (string)$xml->channel->image->url;
                }

                $episodes = $xml->channel->item;
            }
        }

        if (AmpError::occurred()) {
            return null;
        }

        $sql        = "INSERT INTO `podcast` (`feed`, `catalog`, `title`, `website`, `description`, `language`, `copyright`, `generator`, `lastbuilddate`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $db_results = Dba::write($sql, array(
            $feedUrl,
            $catalog_id,
            $title,
            $website,
            $description,
            $language,
            $copyright,
            $generator,
            $lastbuilddate
        ));
        if (!$db_results) {
            return null;
        }
        $podcast_id = (int)Dba::insert_id();
        $podcast    = new Podcast($podcast_id);
        $dirpath    = $podcast->get_root_path();
        if (!is_dir($dirpath)) {
            if (mkdir($dirpath) === false) {
                debug_event(self::class, 'Cannot create directory ' . $dirpath, 1);
            }
        }
        if (!empty($arturl)) {
            $art = new Art((int)$podcast_id, 'podcast');
            $art->insert_url($arturl);
        }
        if ($episodes) {
            $podcast->add_episodes($episodes);
        }

        return $podcast;
    }
}
