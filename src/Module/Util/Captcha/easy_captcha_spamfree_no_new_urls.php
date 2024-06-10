<?php
/*
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
 *
 */

declare(strict_types=0);

namespace Ampache\Module\Util\Captcha;

/**
 * Class easy_captcha_spamfree_no_new_urls
 * simply check if no URLs were submitted - that's what most spambots do,
 * and simply grant access then
 */
class easy_captcha_spamfree_no_new_urls
{
    #-- you have to adapt this, to check for newly added URLs only, in Wikis e.g.
    #   - for simple comment submission forms, this default however suffices:
    /**
     * @param integer $input
     * @return boolean
     */
    public function solved($input = 0)
    {
        // FIXME $uu is undefined
        return !preg_match("#(https?://\w+[^/,.]+)#ims", serialize($_GET + $_POST), $uu);
    }
}
