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

class Personal_Video extends Video
{
    protected const DB_TABLENAME = 'personal_video';

    public ?string $location;
    public ?string $summary;

    public $video;
    public $f_location;

    /**
     * Constructor
     * This pulls the personal video information from the database and returns
     * a constructed object
     * @param int|null $object_id
     */
    public function __construct($object_id = 0)
    {
        if (!$object_id) {
            return;
        }
        parent::__construct($object_id);

        $info = $this->get_info($object_id, static::DB_TABLENAME);
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
    }

    public function getId(): int
    {
        return (int)($this->id ?? 0);
    }

    /**
     * garbage_collection
     *
     * This cleans out unused personal videos
     */
    public static function garbage_collection(): void
    {
        $sql = "DELETE FROM `personal_video` USING `personal_video` LEFT JOIN `video` ON `video`.`id` = `personal_video`.`id` WHERE `video`.`id` IS NULL";
        Dba::write($sql);
    }

    /**
     * create
     * This takes a key'd array of data as input and inserts a new personal video entry, it returns the record id
     * @param array $data
     * @param array $gtypes
     */
    public static function insert(array $data, $gtypes = array(), $options = array()): int
    {
        $sql = "INSERT INTO `personal_video` (`id`, `location`, `summary`) VALUES (?, ?, ?)";
        Dba::write($sql, array($data['id'], $data['location'], $data['summary']));

        return (int)$data['id'];
    }

    /**
     * update
     * This takes a key'd array of data as input and updates a personal video entry
     * @param array $data
     */
    public function update(array $data): int
    {
        parent::update($data);

        $sql = "UPDATE `personal_video` SET `location` = ?, `summary` = ? WHERE `id` = ?";
        Dba::write($sql, array($data['location'], $data['summary'], $this->id));

        return $this->id;
    }

    /**
     * format
     * this function takes the object and formats some values
     *
     * @param bool $details
     */

    public function format($details = true): void
    {
        parent::format($details);

        $this->f_location = $this->location;
    }

    /**
     * remove
     * Delete the object from disk and/or database where applicable.
     */
    public function remove(): bool
    {
        $deleted = parent::remove();
        if ($deleted) {
            $sql     = "DELETE FROM `personal_video` WHERE `id` = ?";
            $deleted = (Dba::write($sql, array($this->id)) !== false);
        }

        return $deleted;
    }
}
