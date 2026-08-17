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

namespace Ampache\Gui\View;

use Ampache\Config\AmpConfig;
use Stringable;
use Throwable;

/**
 * Renders a paired `.phtml` template in the scope of the view object itself.
 *
 * The template addresses its data as `$this->...`, so the values it prints are the typed return values of the
 * view rather than whatever an `extract()` happened to put in scope.
 *
 * Deliberately not `readonly`: a view memoises the expensive lookups its template asks for more than once, and
 * a readonly base would force every subclass to repeat them instead.
 */
abstract class AbstractView implements TemplateInterface
{
    /**
     * The escaping seam: every dynamic value a template prints goes through here.
     *
     * Public rather than protected because a template is a separate file, which static analysis reads as calling
     * in from outside the class however the scope behaves at runtime.
     */
    final public function e(string|Stringable|int|float|null $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * The greppable opt-out for markup that is already HTML, such as `Ajax::button_with_text()` output.
     */
    final public function raw(string $html): string
    {
        return $html;
    }

    final public function render(): string
    {
        ob_start();

        try {
            require $this->templateFile();
        } catch (Throwable $throwable) {
            ob_end_clean();

            throw $throwable;
        }

        return (string) ob_get_clean();
    }

    /**
     * Theme override first, then the shipped template under `resources/templates/`.
     *
     * Templates live under `resources/templates/`, outside the web root, because a `.phtml` is not matched by
     * the php handler in the nginx config Ampache ships and would be served as source from `public/`.
     *
     * A theme copy is executable PHP, so it is honoured only when `allow_php_themes` is set -- the gate
     * `Ui::find_template()` applies by extension, which a non-`.php` extension would slip past.
     */
    final protected function findTemplate(string $template): string
    {
        if (AmpConfig::get('allow_php_themes')) {
            $themePath = (string) AmpConfig::get('theme_path', '/themes/reborn');
            $themeFile = realpath(
                sprintf('%s/../../../%s/templates/%s', __DIR__, $themePath, $template)
            );

            if ($themeFile !== false && is_file($themeFile)) {
                return $themeFile;
            }
        }

        return __DIR__ . '/../../../resources/templates/' . $template;
    }

    /**
     * Absolute path to the template this view renders, normally via `$this->findTemplate()`.
     */
    abstract protected function templateFile(): string;
}
