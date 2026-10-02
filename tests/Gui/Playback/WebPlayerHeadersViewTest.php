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

namespace Ampache\Gui\Playback;

use Ampache\Config\AmpConfig;
use Ampache\MockeryTestCase;
use Ampache\Module\Util\AjaxUriRetrieverInterface;

/**
 * A share page framed by another site has no use for the site's own jQuery UI widgets or stylesheet
 * bundle, so `?embed=1` drops them -- this pins that the header actually stops emitting them, and that
 * every other caller (which never passes `embedded`) keeps getting them as before.
 */
class WebPlayerHeadersViewTest extends MockeryTestCase
{
    /** @var array<string, mixed> */
    private array $config = [];

    public function testEmbeddedDropsJqueryUiAndTheStylesheetBundle(): void
    {
        $html = $this->render(embedded: true);

        $this->assertStringNotContainsString('jquery-ui.min.js', $html);
        $this->assertStringNotContainsString('jquery-ui.min.css', $html);
        $this->assertStringNotContainsString('jquery-editdialog.css', $html);
        // the only other thing that block carries: the site-wide bundle a stranger's page has no use for
        $this->assertStringNotContainsString('templates/base.css', $html);
    }

    public function testEmbeddedStillCarriesTheCorePlayerScripts(): void
    {
        // dropping jQuery UI must not take jQuery itself, or jplayer, down with it
        $html = $this->render(embedded: true);

        $this->assertStringContainsString('jquery/jquery.min.js', $html);
        $this->assertStringContainsString('jplayer/jquery.jplayer.min.js', $html);
    }

    public function testIsEmbeddedReflectsTheConstructorFlag(): void
    {
        $this->assertTrue($this->subject(embedded: true)->isEmbedded());
        $this->assertFalse($this->subject(embedded: false)->isEmbedded());
        $this->assertFalse($this->subject()->isEmbedded(), 'callers that never pass it keep the pre-existing behaviour');
    }

    public function testNotEmbeddedKeepsJqueryUiAndTheStylesheetBundle(): void
    {
        $html = $this->render(embedded: false);

        $this->assertStringContainsString('jquery-ui.min.js', $html);
        $this->assertStringContainsString('jquery-ui.min.css', $html);
        $this->assertStringContainsString('jquery-editdialog.css', $html);
    }

    protected function setUp(): void
    {
        foreach (['theme_path', 'theme_css_base', 'theme_color', 'cookie_secure', 'webplayer_debug', 'song_page_title', 'lang'] as $key) {
            $this->config[$key] = AmpConfig::get($key);
        }

        AmpConfig::set('theme_path', '/themes/reborn', true);
        AmpConfig::set('theme_css_base', ['default.css', 'screen'], true);
        AmpConfig::set('theme_color', 'dark', true);
        AmpConfig::set('cookie_secure', false, true);
        AmpConfig::set('webplayer_debug', false, true);
        AmpConfig::set('song_page_title', false, true);
        AmpConfig::set('lang', 'en_US', true);
    }

    protected function tearDown(): void
    {
        foreach ($this->config as $key => $value) {
            AmpConfig::set($key, $value, true);
        }
    }

    private function render(bool $embedded): string
    {
        return $this->subject($embedded)->render();
    }

    private function subject(?bool $embedded = null): WebPlayerHeadersView
    {
        $ajaxUriRetriever = $this->mock(AjaxUriRetrieverInterface::class);
        $ajaxUriRetriever->allows('getAjaxServerUri')->andReturns('');
        $ajaxUriRetriever->allows('getAjaxUri')->andReturns('');

        return ($embedded === null)
            ? new WebPlayerHeadersView('', $ajaxUriRetriever)
            : new WebPlayerHeadersView('', $ajaxUriRetriever, iframed: false, isShare: true, embedded: $embedded);
    }
}
