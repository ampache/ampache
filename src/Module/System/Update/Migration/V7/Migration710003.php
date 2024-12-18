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

namespace Ampache\Module\System\Update\Migration\V7;

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

final class Migration710003 extends AbstractMigration
{
    protected array $changelog = ['Add indexes to `album_map`, `catalog_map`, `artist_map`, `image`, `recommendation`, `rating`, `user_flag`, `user_activity` and `playlist_data` table'];

    public function migrate(): void
    {
        Dba::write("ALTER TABLE `album_map` DROP KEY `object_type_id_IDX`;", [], true);
        $this->updateDatabase("CREATE INDEX `object_type_id_IDX` USING BTREE ON `album_map` (`object_type`,`object_id`);");

        Dba::write("ALTER TABLE `catalog_map` DROP KEY `catalog_id_object_type_IDX`;", [], true);
        $this->updateDatabase("CREATE INDEX `catalog_id_object_type_IDX` USING BTREE ON `catalog_map` (`catalog_id`,`object_type`);");
        Dba::write("ALTER TABLE `catalog_map` DROP KEY `catalog_id_object_id_IDX`;", [], true);
        $this->updateDatabase("CREATE INDEX `catalog_id_object_id_IDX` USING BTREE ON `catalog_map` (`catalog_id`,`object_id`);");
        Dba::write("ALTER TABLE `catalog_map` DROP KEY `catalog_id_object_type_id_IDX`;", [], true);
        $this->updateDatabase("CREATE INDEX `catalog_id_object_type_id_IDX` USING BTREE ON `catalog_map` (`catalog_id`,`object_type`,`object_id`);");

        Dba::write("ALTER TABLE `artist_map` DROP KEY `artist_id_object_type_id_IDX`;", [], true);
        $this->updateDatabase("CREATE INDEX `artist_id_object_type_id_IDX` USING BTREE ON `artist_map` (`artist_id`,`object_type`,`object_id`);");

        Dba::write("ALTER TABLE `image` DROP KEY `object_type_size_kind_IDX`;", [], true);
        $this->updateDatabase("CREATE INDEX `object_type_size_kind_IDX` USING BTREE ON `image` (`object_type`,`size`,`kind`);");
        Dba::write("ALTER TABLE `image` DROP KEY `object_type_size_mime_IDX`;", [], true);
        $this->updateDatabase("CREATE INDEX `object_type_size_mime_IDX` USING BTREE ON `image` (`object_type`,`size`,`mime`);");

        Dba::write("ALTER TABLE `recommendation` DROP KEY `object_type_object_id_IDX`;", [], true);
        $this->updateDatabase("CREATE INDEX `object_type_object_id_IDX` USING BTREE ON `recommendation` (`object_type`,`object_id`);");
        Dba::write("ALTER TABLE `recommendation` DROP KEY `object_type_IDX`;", [], true);
        $this->updateDatabase("CREATE INDEX `object_type_IDX` USING BTREE ON `recommendation` (`object_type`);");

        Dba::write("ALTER TABLE `rating` DROP KEY `user_object_type_IDX`;", [], true);
        $this->updateDatabase("CREATE INDEX `user_object_type_IDX` USING BTREE ON `rating` (`user`,`object_type`);");
        Dba::write("ALTER TABLE `rating` DROP KEY `user_object_id_IDX`;", [], true);
        $this->updateDatabase("CREATE INDEX `user_object_id_IDX` USING BTREE ON `rating` (`user`,`object_id`);");

        Dba::write("ALTER TABLE `user_flag` DROP KEY `user_object_type_IDX`;", [], true);
        $this->updateDatabase("CREATE INDEX `user_object_type_IDX` USING BTREE ON `user_flag` (`user`,`object_type`);");
        Dba::write("ALTER TABLE `user_flag` DROP KEY `user_object_id_IDX`;", [], true);
        $this->updateDatabase("CREATE INDEX `user_object_id_IDX` USING BTREE ON `user_flag` (`user`,`object_id`);");

        Dba::write("ALTER TABLE `user_activity` DROP KEY `user_object_type_IDX`;", [], true);
        $this->updateDatabase("CREATE INDEX `user_object_type_IDX` USING BTREE ON `user_activity` (`user`,`object_type`);");
        Dba::write("ALTER TABLE `user_activity` DROP KEY `user_object_id_IDX`;", [], true);
        $this->updateDatabase("CREATE INDEX `user_object_id_IDX` USING BTREE ON `user_activity` (`user`,`object_id`);");

        Dba::write("ALTER TABLE `playlist_data` DROP KEY `playlist_object_type_IDX`;", [], true);
        $this->updateDatabase("CREATE INDEX `playlist_object_type_IDX` USING BTREE ON `playlist_data` (`playlist`, `object_type`);");

    }
}
