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
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Art;

$art_url  = Art::url((int)Core::get_get('id'), 'album');
$htmllang = str_replace("_", "-", AmpConfig::get('lang', 'en_US')); ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $htmllang; ?>" lang="<?php echo $htmllang; ?>">
<head>
<!-- Propelled by Ampache | ampache.org -->
<?php Ui::show_custom_style(); ?>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo AmpConfig::get('site_charset', 'UTF-8'); ?>" />
<title><?php echo AmpConfig::get('site_title') . " - " . T_("Album Art"); ?></title>
</head>
<body onload="self.resizeTo(document.images[0].width+30, document.images[0].height+70)">
<?php
echo '<a href="javascript:window.close()" title="' . T_('Click to close window') . '">';
echo '<img src="' . $art_url . '" alt="" />';
echo "</a>"; ?>
</body>
</html>
