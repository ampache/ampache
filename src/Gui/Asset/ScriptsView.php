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
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Ampache\Gui\Asset;

use Ampache\Gui\View\AbstractView;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\EnvironmentInterface;
use Exception;
use Idleberg\ViteManifest\Manifest;
use Override;

/**
 * The script tags every page carries.
 *
 * The bundle comes from vite: a dev server when one is running, the built entrypoint otherwise, and a
 * console warning when neither is available.
 */
final class ScriptsView extends AbstractView
{
    private const array LIBRARIES = [
        ['/lib/components/jquery/jquery.min.js', false],
        ['/lib/components/jquery-ui/jquery-ui.min.js', false],
        ['/lib/modules/prettyphoto/js/jquery.prettyPhoto.min.js', false],
        ['/lib/components/tag-it/js/tag-it.min.js', false],
        ['/lib/components/js-cookie/js.cookie.js', false],
        ['/lib/components/jscroll/jquery.jscroll.min.js', false],
        ['/lib/components/jquery-qrcode/jquery.qrcode.min.js', true],
        ['/lib/modules/rhinoslider/js/rhinoslider-1.05.min.js', true],
        ['/lib/components/datetimepicker/jquery.datetimepicker.full.min.js', true],
        ['/lib/components/filepond/filepond.min.js', false],
        ['/lib/components/jquery-contextmenu/jquery.contextMenu.js', false],
    ];

    public function __construct(
        private readonly EnvironmentInterface $environment,
        private readonly string $webPath,
        private readonly AjaxUriRetrieverInterface $ajaxUriRetriever,
    ) {}

    /**
     * `js_globals.php` still reads this out of the enclosing scope, so the view puts it back.
     */
    public function getAjaxUriRetriever(): AjaxUriRetrieverInterface
    {
        return $this->ajaxUriRetriever;
    }

    /**
     * The built bundle's url, or null when no manifest has been built.
     */
    public function getEntrypointUrl(): ?string
    {
        $manifest = __DIR__ . '/../../../public/client/dist/.vite/manifest.json';
        if (!file_exists($manifest)) {
            return null;
        }

        try {
            $entrypoint = new Manifest($manifest, $this->webPath . '/dist/')->getEntrypoint('src/js/main.js', false);
        } catch (Exception $exception) {
            debug_event(self::class, 'Vite manifest error: ' . $exception->getMessage(), 3);

            return null;
        }

        return ($entrypoint) ? (string) $entrypoint['url'] : null;
    }

    /**
     * @return list<array{url: string, defer: bool}>
     */
    public function getLibraries(): array
    {
        $libraries = [];
        foreach (self::LIBRARIES as [$path, $defer]) {
            $libraries[] = ['url' => $this->webPath . $path, 'defer' => $defer];
        }

        return $libraries;
    }

    public function getWebPath(): string
    {
        return $this->webPath;
    }

    public function hasCustomScript(): bool
    {
        return file_exists(__DIR__ . '/../../../public/client/lib/javascript/custom.js');
    }

    public function isDevServer(): bool
    {
        return $this->environment->isDevJS('src/js/main.js');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('asset/scripts.phtml');
    }
}
