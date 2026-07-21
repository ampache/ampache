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
        // LIKE is case insensitive under the default collation, so this catches `image/JPG` too,
        // and rewriting an already correct value to the same string is harmless
        $this->updateDatabase("UPDATE `image` SET `mime` = 'image/jpeg' WHERE `mime` LIKE 'image/jpg' OR `mime` LIKE 'image/jpeg';");
    }
}
