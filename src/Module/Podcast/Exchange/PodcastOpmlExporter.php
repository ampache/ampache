<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Podcast\Exchange;

use Ampache\Gui\TalFactoryInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\PodcastRepositoryInterface;
use Generator;
use PhpTal\PHPTAL;

/**
 * Exports the podcasts in opml format
 *
 * @see http://opml.org/spec2.opml
 */
final class PodcastOpmlExporter implements PodcastExporterInterface
{
    private TalFactoryInterface $talFactory;

    private PodcastRepositoryInterface $podcastRepository;

    public function __construct(
        TalFactoryInterface $talFactory,
        PodcastRepositoryInterface $podcastRepository
    ) {
        $this->talFactory        = $talFactory;
        $this->podcastRepository = $podcastRepository;
    }

    /**
     * Exports all podcasts-subscriptions and returns the result
     */
    public function export(): string
    {
        $talPage = $this->talFactory->createPhpTal();
        $talPage->setTemplate((string) realpath(__DIR__ . '/../../../../resources/templates/podcast/export.opml'));
        $talPage->setOutputMode(PHPTAL::XML);
        $talPage->set('TITLE', T_('Ampache podcast subscriptions'));
        $talPage->set('CREATION_DATE', date(DATE_RFC822));
        $talPage->set('PODCASTS', $this->retrievePodcasts());

        return $talPage->execute();
    }

    /**
     * Returns the content-type of the export result
     */
    public function getContentType(): string
    {
        return 'text/x-opml';
    }

    /**
     * @return Generator<array{
     *  title: string,
     *  feedUrl: string,
     *  website: string,
     *  language: string,
     *  description: string
     * }>
     */
    private function retrievePodcasts(): Generator
    {
        $podcasts = $this->podcastRepository->findAll();

        /** @var Podcast $podcast */
        foreach ($podcasts as $podcast) {
            yield [
                'title' => $podcast->getTitle(),
                'feedUrl' => $podcast->getFeedUrl(),
                'website' => $podcast->getWebsite(),
                'language' => $podcast->getLanguage(),
                'description' => $podcast->getDescription(),
            ];
        }
    }
}
