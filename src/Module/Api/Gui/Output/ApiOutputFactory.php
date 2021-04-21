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
 *
 */

declare(strict_types=1);

namespace Ampache\Module\Api\Gui\Output;

use Ampache\Module\Util\XmlWriterInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;

final class ApiOutputFactory implements ApiOutputFactoryInterface
{
    private ModelFactoryInterface $modelFactory;

    private AlbumRepositoryInterface $albumRepository;

    private SongRepositoryInterface $songRepository;

    private XmlWriterInterface $xmlWriter;

    private PodcastEpisodeRepositoryInterface $podcastEpisodeRepository;

    private PodcastRepositoryInterface $podcastRepository;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        AlbumRepositoryInterface $albumRepository,
        SongRepositoryInterface $songRepository,
        XmlWriterInterface $xmlWriter,
        PodcastEpisodeRepositoryInterface $podcastEpisodeRepository,
        PodcastRepositoryInterface $podcastRepository
    ) {
        $this->modelFactory             = $modelFactory;
        $this->albumRepository          = $albumRepository;
        $this->songRepository           = $songRepository;
        $this->xmlWriter                = $xmlWriter;
        $this->podcastEpisodeRepository = $podcastEpisodeRepository;
        $this->podcastRepository        = $podcastRepository;
    }

    public function createJsonOutput(): ApiOutputInterface
    {
        return new JsonOutput(
            $this->modelFactory,
            $this->albumRepository,
            $this->songRepository,
            $this->podcastEpisodeRepository,
            $this->podcastRepository
        );
    }

    public function createXmlOutput(): ApiOutputInterface
    {
        return new XmlOutput(
            $this->modelFactory,
            $this->xmlWriter,
            $this->albumRepository,
            $this->songRepository,
            $this->podcastEpisodeRepository,
            $this->podcastRepository
        );
    }
}
