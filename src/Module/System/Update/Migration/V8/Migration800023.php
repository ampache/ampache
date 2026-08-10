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
 */

namespace Ampache\Module\System\Update\Migration\V8;

use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Correct art mime types stored from a filename extension.
 *
 * Uploaded art used to take its mime from the uploaded filename (`'image/' . $extension`), so a
 * `.jpg` upload was stored as `image/jpg`, which is not a registered type, and a `.JPG` one as
 * `image/JPG`. `image.php` serves the stored value as the Content-Type; browsers sniff past it but
 * stricter API and Subsonic clients need not. Art::insert() now reads the real type from the image
 * data, so this only has to repair the rows already written.
 *
 * Safe to roll back to Ampache7: it reads the mime out of this column and serves it, and nothing in
 * either version compares against the literal `image/jpg`.
 */
final class Migration800023 extends AbstractMigration
{
    protected array $changelog = ['Correct stored art mime types written from a filename extension (`image/jpg` -> `image/jpeg`)'];

    public function migrate(): void
    {
        // `unique_image` covers (width, height, mime, size, object_type, object_id, kind), so where the
        // same artwork was stored twice with the two spellings, rewriting the `image/jpg` row would
        // collide with its `image/jpeg` twin. Those rows are left alone rather than deleting artwork
        // during an upgrade; everything without a twin is corrected. The join is NULL safe because
        // width, height, size and kind are all nullable, and the comparisons are case insensitive
        // under the default collation, so `image/JPG` is covered too.
        $this->updateDatabase(
            "UPDATE `image` AS `fix` "
            . "LEFT JOIN `image` AS `clash` "
            . "ON `clash`.`object_type` = `fix`.`object_type` "
            . "AND `clash`.`object_id` = `fix`.`object_id` "
            . "AND `clash`.`width` <=> `fix`.`width` "
            . "AND `clash`.`height` <=> `fix`.`height` "
            . "AND `clash`.`size` <=> `fix`.`size` "
            . "AND `clash`.`kind` <=> `fix`.`kind` "
            . "AND `clash`.`mime` = 'image/jpeg' "
            . "AND `clash`.`id` <> `fix`.`id` "
            . "SET `fix`.`mime` = 'image/jpeg' "
            . "WHERE `fix`.`mime` = 'image/jpg' AND `clash`.`id` IS NULL;"
        );
    }
}
