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

declare(strict_types=0);

namespace Ampache\Repository\Model;

use Ampache\Config\AmpConfig;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;

/**
 * This is the class responsible for handling the Label object
 * it is related to the label table in the database.
 */
final class Label extends database_object implements LabelInterface
{
    protected const DB_TABLENAME = 'label';

    public int $id;

    private LabelRepositoryInterface $labelRepository;

    private SongRepositoryInterface $songRepository;

    /** @var array<string, mixed>|null */
    private ?array $dbData = null;

    public function __construct(
        LabelRepositoryInterface $labelRepository,
        SongRepositoryInterface $songRepository,
        int $id
    ) {
        $this->labelRepository = $labelRepository;
        $this->songRepository  = $songRepository;
        $this->id              = $id;
    }

    private function getDbData(): array
    {
        if ($this->dbData === null) {
            $this->dbData = $this->labelRepository->getDataById($this->getId());
        }

        return $this->dbData;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return $this->getDbData() === [];
    }

    public function getUserId(): int
    {
        return (int) ($this->getDbData()['user'] ?? 0);
    }

    public function getSummary(): string
    {
        return $this->getDbData()['summary'] ?? '';
    }

    public function getWebsite(): string
    {
        return $this->getDbData()['website'] ?? '';
    }

    public function getEmail(): string
    {
        return $this->getDbData()['email'] ?? '';
    }

    public function getAddress(): string
    {
        return $this->getDbData()['address'] ?? '';
    }

    public function getCategory(): string
    {
        return $this->getDbData()['category'] ?? '';
    }

    public function getName(): string
    {
        return $this->getDbData()['name'] ?? '';
    }

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
    public function display_art($thumb = 2, $force = false)
    {
        if (Art::has_db($this->getId(), 'label') || $force) {
            echo Art::display('label', $this->getId(), $this->get_fullname(), $thumb, $this->getLink());
        }
    }

    /**
     * @param boolean $details
     */
    public function format($details = true)
    {
    }

    public function getArtistCount(): int
    {
        return count($this->labelRepository->getArtists($this->getId()));
    }

    public function getLink(): string
    {
        return AmpConfig::get('web_path') . '/labels.php?action=show&label=' . $this->getId();
    }

    public function getNameFormatted(): string
    {
        return scrub_out($this->getName());
    }

    /**
     * @return array|integer[]
     */
    public function get_catalogs()
    {
        return array();
    }

    /**
     * @return array
     */
    public function get_childrens()
    {
        return [
            'artist' => array_map(
                static function (int $artistId): array {
                    return [
                        'object_type' => 'artist',
                        'object_id' => $artistId
                    ];
                },
                $this->labelRepository->getArtists($this->getId())
            )
        ];
    }

    /**
     * @return string
     */
    public function get_default_art_kind()
    {
        return 'default';
    }

    /**
     * @return string
     */
    public function get_description()
    {
        return $this->getSummary();
    }

    /**
     * @return string
     */
    public function get_fullname()
    {
        return $this->getNameFormatted();
    }

    /**
     * get_keywords
     * @return array
     */
    public function get_keywords()
    {
        $keywords          = array();
        $keywords['label'] = array(
            'important' => true,
            'label' => T_('Label'),
            'value' => $this->getNameFormatted()
        );

        return $keywords;
    }

    /**
     * @param string $filter_type
     * @return array|mixed
     */
    public function get_medias($filter_type = null)
    {
        $medias = array();
        if ($filter_type === null || $filter_type == 'song') {
            $songs = $this->songRepository->getByLabel($this->getName());
            foreach ($songs as $song_id) {
                $medias[] = array(
                    'object_type' => 'song',
                    'object_id' => $song_id
                );
            }
        }

        return $medias;
    }

    /**
     * @return null
     */
    public function get_parent()
    {
        return null;
    }

    /**
     * @return integer
     */
    public function get_user_owner()
    {
        return $this->getUserId();
    }

    /**
     * search_childrens
     * @param string $name
     * @return array
     */
    public function search_childrens($name)
    {
        $search                    = array();
        $search['type']            = "artist";
        $search['rule_0_input']    = $name;
        $search['rule_0_operator'] = 4;
        $search['rule_0']          = "title";
        $artists                   = Search::run($search);

        $childrens = array();
        foreach ($artists as $artist_id) {
            $childrens[] = array(
                'object_type' => 'artist',
                'object_id' => $artist_id
            );
        }

        return $childrens;
    }

    /**
     * @param array<string, mixed> $data
     * @return int
     */
    public function update(array $data)
    {
        if ($this->labelRepository->lookup($data['name'], $this->getId()) !== 0) {
            return false;
        }

        $this->labelRepository->update(
            $this->getId(),
            $data['name'] ?? $this->getName(),
            $data['category'] ?? $this->getCategory(),
            $data['summary'] ?? $this->getSummary(),
            $data['address'] ?? $this->getAddress(),
            $data['email'] ?? $this->getEmail(),
            $data['website'] ?? $this->getWebsite()
        );

        return $this->getId();
    }
}
