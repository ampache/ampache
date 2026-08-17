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

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Util\Ui;
use Override;

/**
 * The stylesheet links every page carries.
 *
 * Theme css is cache-busted by file mtime so an edit loads immediately, falling back to the app version
 * when the file cannot be stat'd.
 */
final class StylesheetsView extends AbstractView
{
    private const array LIBRARIES = [
        '/lib/modules/prettyphoto/css/prettyPhoto.min.css',
        '/templates/jquery-ui.custom.css',
        '/templates/jquery-editdialog.css',
        '/lib/modules/jquery-ui-ampache/jquery-ui.min.css',
        '/lib/components/tag-it/css/jquery.tagit.css',
        '/lib/modules/rhinoslider/css/rhinoslider-1.05.css',
        '/lib/components/datetimepicker/jquery.datetimepicker.min.css',
        '/lib/components/jquery-contextmenu/jquery.contextMenu.min.css',
        '/lib/components/filepond/filepond.min.css',
    ];

    public function __construct(
        private readonly string $webPath,
    ) {}

    public function getBaseUrl(): string
    {
        return $this->webPath . '/templates/base.css?v=' . $this->getBust(__DIR__ . '/../../../public/client/templates/base.css');
    }

    public function getColourUrl(): string
    {
        $file = $this->getThemeColor() . '.css';

        return $this->webPath . $this->getThemePath() . '/' . $file . '?v=' . $this->getBust($this->getThemeFsPath() . '/' . $file);
    }

    public function getCustomStyle(): string
    {
        ob_start();
        Ui::show_custom_style();

        return (string) ob_get_clean();
    }

    public function getCustomUrl(): string
    {
        return $this->webPath . '/templates/custom.css';
    }

    /**
     * @return list<string>
     */
    public function getLibraries(): array
    {
        $libraries = [];
        foreach (self::LIBRARIES as $path) {
            $libraries[] = $this->webPath . $path;
        }

        return $libraries;
    }

    public function getPrintUrl(): string
    {
        return $this->webPath . '/templates/print.css';
    }

    public function getRtlUrl(): string
    {
        return $this->webPath . $this->getThemePath() . '/rtl.css';
    }

    /**
     * The theme's own sheets, each with the media it applies to.
     *
     * @return list<array{url: string, media: string}>
     */
    public function getThemeSheets(): array
    {
        $base = AmpConfig::get('theme_css_base', ['default.css', 'screen']);
        if ($base === null) {
            $base = ['default.css', 'screen'];
        }

        if (is_string($base)) {
            $base = [$base];
        }

        $sheets = [];
        foreach ((array) $base as $entry) {
            $file  = (string) $entry[0];
            $media = (string) ($entry[1] ?? 'screen');
            $url   = $this->webPath . $this->getThemePath() . '/' . $file;
            if (str_ends_with($file, '.css')) {
                $url .= '?v=' . $this->getBust($this->getThemeFsPath() . '/' . $file);
            }

            $sheets[] = ['url' => $url, 'media' => $media];
        }

        return $sheets;
    }

    public function hasCustomStylesheet(): bool
    {
        return file_exists(__DIR__ . '/../../../public/client/templates/custom.css');
    }

    public function showRtl(): bool
    {
        return is_rtl((string) AmpConfig::get('lang', 'en_US'))
            && is_file($this->getThemeFsPath() . '/rtl.css');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('asset/stylesheets.phtml');
    }

    private function getBust(string $path): string
    {
        return (is_file($path)) ? (string) filemtime($path) : (string) AmpConfig::get('version');
    }

    private function getThemeColor(): string
    {
        return (string) AmpConfig::get('theme_color', 'dark');
    }

    private function getThemeFsPath(): string
    {
        return __DIR__ . '/../../../public' . $this->getThemePath();
    }

    private function getThemePath(): string
    {
        return AmpConfig::get('theme_path', '/themes/reborn') . '/templates';
    }
}
