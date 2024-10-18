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
use Ampache\Module\Util\EnvironmentInterface;
use Ampache\Module\Util\Ui;
use Idleberg\ViteManifest\Manifest;

global $dic;
$web_path          = AmpConfig::get_web_path();
$environment       = $dic->get(EnvironmentInterface::class);
$manifest          = __DIR__ . '/../dist/.vite/manifest.json';
$entrypoint        = false;
if (file_exists($manifest)) {
    $vm         = new Manifest($manifest, "");
    $entrypoint = $vm->getEntrypoint("src/js/main.js", false);
}
?>

<script src="<?php echo $web_path; ?>/lib/components/jquery/jquery.min.js"></script>
<script src="<?php echo $web_path; ?>/lib/components/jquery-ui/jquery-ui.min.js"></script>
<script src="<?php echo $web_path; ?>/lib/components/prettyphoto/js/jquery.prettyPhoto.min.js"></script>
<script src="<?php echo $web_path; ?>/lib/components/tag-it/js/tag-it.min.js"></script>
<script src="<?php echo $web_path; ?>/lib/components/js-cookie/js.cookie.js"></script>
<script src="<?php echo $web_path; ?>/lib/components/jscroll/jquery.jscroll.min.js"></script>
<script src="<?php echo $web_path; ?>/lib/components/jquery-qrcode/jquery.qrcode.min.js" defer></script>
<script src="<?php echo $web_path; ?>/lib/modules/rhinoslider/js/rhinoslider-1.05.min.js" defer></script>
<script src="<?php echo $web_path; ?>/lib/components/datetimepicker/jquery.datetimepicker.full.min.js" defer></script>
<script src="<?php echo $web_path; ?>/lib/components/filepond/filepond.min.js"></script>
<script src="<?php echo $web_path; ?>/lib/components/jquery-contextmenu/jquery.contextMenu.js"></script>

<?php require_once Ui::find_template('js_globals.php'); ?>

<?php if ($environment->isDevJS('src/js/main.js')): ?>
    <script type="module" src="http://localhost:5177/@vite/client" crossorigin></script>
    <script type="module" src="http://localhost:5177/src/js/main.js" crossorigin></script>
<?php elseif ($entrypoint): ?>
    <script type="module" src="<?php echo "{$web_path}/dist/{$entrypoint['url']}" ?>" crossorigin></script>
<?php else: ?>
    <script>console.warn("No vite manifest file was found")</script>
<?php endif; ?>

<?php if (file_exists(__DIR__ . '/../lib/javascript/custom.js')) { ?>
    <script src="<?php echo $web_path; ?>/lib/javascript/custom.js" defer></script>
<?php } ?>
