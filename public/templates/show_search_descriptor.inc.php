<?php

declare(strict_types=0);

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
 *
 */

use Ampache\Config\AmpConfig;

$char_set    = AmpConfig::get('site_charset', 'UTF-8');
$web_path    = AmpConfig::get_web_path();
$favicon     = AmpConfig::get('custom_favicon', false) ?: $web_path . "/favicon.ico";
$short_name  = scrub_out(AmpConfig::get('site_title'));
$description = scrub_out(T_('Search Ampache'));

header(sprintf('Content-type: application/opensearchdescription+xml; charset=%s; filename=opensearch.xml', $char_set));

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" .
    "<OpenSearchDescription  xmlns=\"http://a9.com/-/spec/opensearch/1.1/\">" .
    "<ShortName>" . $short_name . "</ShortName>" .
    "<Description>" . $description . "</Description>" .
    "<InputEncoding>" . $char_set . "></InputEncoding>" .
    "<OutputEncoding>" . $char_set . "></OutputEncoding>" .
    "<Image width=\"16\" height=\"16\">" . $favicon . "</Image>" .
    "<Url type=\"text/html\" method=\"get\" template=\"" . $web_path . "/search.php\">" .
    "<Param name=\"type\" value=\"song\"></Param>" .
    "<Param name=\"rule_1\" value=\"anywhere\"></Param>" .
    "<Param name=\"rule_1_operator\" value=\"0\"></Param>" .
    "<Param name=\"rule_1_input\" value=\"{searchTerms}\"></Param>" .
    "<Param name=\"action\" value=\"search\"></Param>" .
    "</Url>" .
    "<Url type=\"application/opensearchdescription+xml\" rel=\"self\" template=\"" . $web_path . "/opensearch.php?action=descriptor\" />" .
    "</OpenSearchDescription>";