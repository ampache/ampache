<?php

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

declare(strict_types=0);

namespace Ampache\Repository\Model;

use Ampache\Module\System\Dba;

class Movie extends Video
{
    protected const DB_TABLENAME = 'movie';

    public ?string $original_name;
    public ?string $summary;
    public ?int $year;
    public ?string $prefix;

    public $video;
    public $f_original_name;

    /**
     * Constructor
     * This pulls the movie information from the database and returns
     * a constructed object
     * @param $movie_id
     */
    public function __construct($movie_id)
    {
        parent::__construct($movie_id);

        $info = $this->get_info($movie_id, static::DB_TABLENAME);
        if (empty($info)) {
            return;
        }
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
    } // Constructor

    public function getId(): int
    {
        return (int)($this->id ?? 0);
    }

    /**
     * garbage_collection
     *
     * This cleans out unused movies
     */
    public static function garbage_collection(): void
    {
        $sql = "DELETE FROM `movie` USING `movie` LEFT JOIN `video` ON `video`.`id` = `movie`.`id` WHERE `video`.`id` IS NULL";
        Dba::write($sql);
    }

    /**
     * create
     * This takes a key'd array of data as input and inserts a new movie entry, it returns the record id
     * @param array $data
     * @param array $gtypes
     * @param array $options
     */
    public static function insert(array $data, $gtypes = array(), $options = array()): int
    {
        $trimmed = Catalog::trim_prefix(trim((string)$data['original_name']));
        $name    = $trimmed['string'];
        $prefix  = $trimmed['prefix'];

        $sql = "INSERT INTO `movie` (`id`, `original_name`, `prefix`, `summary`, `year`) VALUES (?, ?, ?, ?, ?)";
        Dba::write($sql, array($data['id'], $name, $prefix, $data['summary'], $data['year']));

        return (int)$data['id'];
    } // create

    /**
     * update
     * This takes a key'd array of data as input and updates a movie entry
     * @param array $data
     */
    public function update(array $data): int
    {
        parent::update($data);

        if (isset($data['original_name'])) {
            $trimmed = Catalog::trim_prefix(trim((string)$data['original_name']));
            $name    = $trimmed['string'];
            $prefix  = $trimmed['prefix'];
        } else {
            $name   = $this->original_name;
            $prefix = $this->prefix;
        }
        $summary = $data['summary'] ?? $this->summary;
        $year    = Catalog::normalize_year($data['year'] ?? $this->year);

        $sql = "UPDATE `movie` SET `original_name` = ?, `prefix` = ?, `summary` = ?, `year` = ? WHERE `id` = ?";
        Dba::write($sql, array($name, $prefix, $summary, $year, $this->id));

        $this->original_name = $name;
        $this->prefix        = $prefix;
        $this->summary       = $summary;
        $this->year          = $year;

        return $this->id;
    } // update

    /**
     * format
     * this function takes the object and formats some values
     *
     * @param bool $details
     */

    public function format($details = true): void
    {
        parent::format($details);

        $this->f_original_name = trim((string)$this->prefix . " " . $this->get_fullname());
        $this->f_name          = ($this->f_original_name ?? $this->get_fullname());
        $this->f_full_title    = $this->f_name;
        $this->f_link          = '<a href="' . $this->link . '">' . scrub_out($this->f_name) . '</a>';
    } // format

    /**
     * Get item keywords for metadata searches.
     * @return array
     */
    public function get_keywords()
    {
        $keywords         = parent::get_keywords();
        $keywords['type'] = array(
            'important' => false,
            'label' => null,
            'value' => 'movie'
        );

        return $keywords;
    }

    public function get_default_art_kind(): string
    {
        return 'default';
    }

    /**
     * remove
     * Delete the object from disk and/or database where applicable.
     */
    public function remove(): bool
    {
        $deleted = parent::remove();
        if ($deleted) {
            $sql     = "DELETE FROM `movie` WHERE `id` = ?";
            $deleted = (Dba::write($sql, array($this->id)) !== false);
        }

        return $deleted;
    }
}
