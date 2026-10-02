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

namespace Ampache\Module\Util;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Gui\Form\ConfirmationView;
use Ampache\Gui\Form\ConfirmationWithReturnView;
use Ampache\Gui\Form\ContinueView;
use Ampache\Gui\Partial\BoxBottomView;
use Ampache\Gui\Partial\BoxTopView;
use Ampache\Gui\Partial\FooterView;
use Ampache\Gui\Partial\HeaderView;
use Ampache\Gui\Partial\PageMeta;
use Ampache\Gui\Partial\RightbarView;
use Ampache\Gui\Sidebar\SidebarViewFactoryInterface;
use Ampache\Gui\System\QueryStatsView;
use Ampache\Gui\System\StandaloneErrorTypeEnum;
use Ampache\Gui\System\StandaloneErrorView;
use Ampache\Module\Api\Api;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Database\database_object;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Playlist\PlaylistLoaderInterface;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Plugin\Plugin;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Module\System\Preference;
use Ampache\Module\Util\Rss\RssUrl;
use Ampache\Module\Util\Rss\Type\RssFeedTypeEnum;
use Ampache\Plugin\PluginDisplayOnFooterInterface;
use Ampache\Repository\CollectionRepositoryInterface;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\PrivateMessageRepositoryInterface;

/**
 * A collection of methods related to the user interface
 */
class Ui implements UiInterface
{
    /** @var array<string, bool> material symbols already emitted in a sprite for the current page */
    private static array $_emitted_symbols = [];

    /** @var array<string, string> $_icon_cache */
    private static array $_icon_cache = [];

    /** @var array<string, string> $_image_cache */
    private static array $_image_cache = [];

    /** @var array<string, array{attrs: string, viewbox: string, inner: string}|null> parsed material symbol source files */
    private static array $_symbol_cache = [];

    /** @var array<string, string> resolved template paths, keyed by theme|extern|template */
    private static array $_template_cache = [];

    private static int $_ticker = 0;

    /** @var array<string, bool> material symbols referenced on the current page (sprite content) */
    private static array $_used_symbols = [];

    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
        private readonly AjaxUriRetrieverInterface $ajaxUriRetriever,
        private readonly CollectionRepositoryInterface $collectionRepository,
        private readonly EnvironmentInterface $environment,
        private readonly LibraryItemLoaderInterface $libraryItemLoader,
        private readonly PlaylistLoaderInterface $playlistLoader,
        private readonly PrivateMessageRepositoryInterface $privateMessageRepository,
        private readonly ZipHandlerInterface $zipHandler,
        private readonly SidebarViewFactoryInterface $sidebarViewFactory,
    ) {}

    /**
     * ajax_include
     *
     * Does some trickery with the output buffer to return the output of a
     * template.
     *
     * @param array<string, mixed> $context values the template renders with
     */
    public static function ajax_include(string $template, array $context = []): string
    {
        $templatePath = self::find_template('') . $template;

        ob_start();
        extract($context);

        require $templatePath;
        $output = ob_get_contents();
        ob_end_clean();

        return $output ?: '';
    }

    /**
     * check_iconv
     *
     * Checks to see whether iconv is available.
     */
    public static function check_iconv(): bool
    {
        return function_exists('iconv') && function_exists('iconv_substr');
    }

    /**
     * check_ticker
     *
     * Stupid little cutesie thing to ratelimit output of long-running
     * operations.
     */
    public static function check_ticker(): bool
    {
        if (defined('SSE_OUTPUT') || defined('CLI') || defined('API')) {
            return false;
        }

        if (!self::$_ticker || (time() > self::$_ticker + 1)) {
            self::$_ticker = time();

            return true;
        }

        return false;
    }

    /**
     * clean_utf8
     *
     * Removes characters that aren't valid in XML
     * (which is a subset of valid UTF-8, but close enough for our purposes.)
     * See http://www.w3.org/TR/2006/REC-xml-20060816/#charsets
     */
    public static function clean_utf8(string $string): string
    {
        if ($string !== '' && $string !== '0') {
            $clean = preg_replace(
                '/[^\x{9}\x{a}\x{d}\x{20}-\x{d7ff}\x{e000}-\x{fffd}\x{10000}-\x{10ffff}]|[\x{7f}-\x{84}\x{86}-\x{9f}\x{fdd0}-\x{fddf}\x{1fffe}-\x{1ffff}\x{2fffe}-\x{2ffff}\x{3fffe}-\x{3ffff}\x{4fffe}-\x{4ffff}\x{5fffe}-\x{5ffff}\x{6fffe}-\x{6ffff}\x{7fffe}-\x{7ffff}\x{8fffe}-\x{8ffff}\x{9fffe}-\x{9ffff}\x{afffe}-\x{affff}\x{bfffe}-\x{bffff}\x{cfffe}-\x{cffff}\x{dfffe}-\x{dffff}\x{efffe}-\x{effff}\x{ffffe}-\x{fffff}\x{10fffe}-\x{10ffff}]/u',
                '',
                $string
            );

            if ($clean) {
                return rtrim((string) $clean);
            }

            debug_event(self::class, 'Charset cleanup failed, something might break', 1);
        }

        return '';
    }

    /**
     * find_template
     *
     * Return the path to the template file wanted. The file can be overwritten
     * by the theme if it's not a php file, or if it is and if option
     * allow_php_themes is set to true.
     */
    public static function find_template(string $template, bool $extern = false): string
    {
        // Path only depends on theme + extern + template name, so resolve once per request.
        $theme    = (string) AmpConfig::get('theme_path', '/themes/reborn');
        $cacheKey = $theme . '|' . ($extern ? '1' : '0') . '|' . $template;
        if (isset(self::$_template_cache[$cacheKey])) {
            return self::$_template_cache[$cacheKey];
        }

        $path      = $theme . '/templates/' . $template;
        $realpath  = __DIR__ . '/../../../public/' . $path;
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (($extension !== 'php' || AmpConfig::get('allow_php_themes')) && file_exists($realpath) && is_file($realpath)) {
            return self::$_template_cache[$cacheKey] = $path;
        }

        if ($extern) {
            return self::$_template_cache[$cacheKey] = '/templates/' . $template;
        }

        return self::$_template_cache[$cacheKey] = __DIR__ . '/../../../public/templates/' . $template;
    }

    /**
     * A cache-busting token for a theme asset: its mtime, falling back to the app version.
     *
     * The `public/` base lives here with the other path literals, so the structure transform sees it.
     */
    public static function find_template_version(string $template): string
    {
        $realpath = __DIR__ . '/../../../public' . self::find_template($template, true);

        return (is_file($realpath)) ? (string) filemtime($realpath) : (string) AmpConfig::get('version');
    }

    /**
     * format_bytes
     *
     * Turns a size in bytes into the best human-readable value
     * @param int|float|string $value Size in bytes
     * @param int $precision Number of decimal places to show
     * @param int $pass Internal counter for recursion
     */
    public static function format_bytes(int|float|string $value, int $precision = 2, int $pass = 0): string
    {
        if (!$value) {
            return '';
        }

        if (is_string($value)) {
            $value = (float) $value;
        }

        while (strlen((string) floor($value)) > 3) {
            $value /= 1024;
            $pass++;
        }

        $unit = match ($pass) {
            1 => 'kB',
            2 => 'MB',
            3 => 'GB',
            4 => 'TB',
            5 => 'PB',
            default => 'B',
        };

        return (round($value, $precision)) . ' ' . $unit;
    }

    /**
     * The label for the control that opens the add-to-list dialog.
     * @param bool $short for a control with no room for the full label, such as a multi-select action bar
     */
    public static function get_add_to_list_label(bool $short = false): string
    {
        if (!AmpConfig::get('show_collection')) {
            return T_('Add to playlist');
        }

        return ($short)
            ? T_('Add to list')
            : T_('Add to playlist / collection');
    }

    /**
     * get_icon
     *
     * Returns an <img> or <svg> tag for the specified icon
     */
    public static function get_icon(string $name, ?string $title = null, ?string $id_attrib = null, ?string $class_attrib = null): string
    {
        $title ??= T_(ucfirst($name));
        $icon_url = self::_find_icon($name);
        $icontype = pathinfo($icon_url, PATHINFO_EXTENSION);
        $tag      = '';
        if ($icontype == 'svg') {
            // load svg file
            $svgicon = simplexml_load_file($icon_url);
            if ($svgicon !== false) {
                if (empty($svgicon->title)) {
                    $svgicon->addChild('title', $title);
                } else {
                    $svgicon->title = $title;
                }

                if (empty($svgicon->desc)) {
                    $svgicon->addChild('desc', $title);
                } else {
                    $svgicon->desc = $title;
                }

                if (!in_array($id_attrib, [null, '', '0'], true)) {
                    $svgicon->addAttribute('id', $id_attrib);
                }

                if (in_array($class_attrib, [null, '', '0'], true)) {
                    $class_attrib = 'icon icon-' . $name;
                }

                $svgicon->addAttribute('class', $class_attrib);

                $tag = explode("\n", (string) $svgicon->asXML(), 2)[1];
            }
        } else {
            // fall back to png
            $tag = '<img src="' . $icon_url . '" ';
            $tag .= 'alt="' . $title . '" ';
            $tag .= 'title="' . $title . '" ';
            if ($id_attrib !== null) {
                $tag .= 'id="' . $id_attrib . '" ';
            }

            if ($class_attrib !== null) {
                $tag .= 'class="' . $class_attrib . '" ';
            }

            $tag .= '/>';
        }

        return $tag;
    }

    /**
     * get_image
     *
     * Returns an <img> or <svg> tag for the specified image
     */
    public static function get_image(string $name, ?string $title = null, ?string $id_attrib = null, ?string $class_attrib = null): string
    {
        $title ??= ucfirst($name);
        $image_url = self::_find_image($name);
        $imagetype = pathinfo($image_url, PATHINFO_EXTENSION);
        $tag       = '';
        if ($imagetype == 'svg') {
            // load svg file
            $svgimage = simplexml_load_file($image_url);
            if ($svgimage !== false) {
                if (empty($svgimage->title)) {
                    $svgimage->addChild('title', $title);
                } else {
                    $svgimage->title = $title;
                }

                if (empty($svgimage->desc)) {
                    $svgimage->addChild('desc', $title);
                } else {
                    $svgimage->desc = $title;
                }

                if (!in_array($id_attrib, [null, '', '0'], true)) {
                    $svgimage->addAttribute('id', $id_attrib);
                }

                $class_attrib ??= 'image image-' . $name;
                $svgimage->addAttribute('class', $class_attrib);

                $tag = explode("\n", (string) $svgimage->asXML(), 2)[1];
            }
        } else {
            // fall back to png
            $tag = '<img src="' . $image_url . '" ';
            $tag .= 'alt="' . $title . '" ';
            $tag .= 'title="' . $title . '" ';
            if ($id_attrib !== null) {
                $tag .= 'id="' . $id_attrib . '" ';
            }

            if ($class_attrib !== null) {
                $tag .= 'class="' . $class_attrib . '" ';
            }

            $tag .= '/>';
        }

        return $tag;
    }

    /**
     * get_logo_url
     *
     * Get the custom logo or logo relating to your theme color
     */
    public static function get_logo_url(?string $color = null): string
    {
        if (AmpConfig::get('custom_logo')) {
            return AmpConfig::get('custom_logo');
        }

        if ($color !== null) {
            return AmpConfig::get_web_path() . AmpConfig::get('theme_path', '/themes/reborn') . '/images/ampache-' . $color . '.png';
        }

        return AmpConfig::get_web_path() . AmpConfig::get('theme_path', '/themes/reborn') . '/images/ampache-' . AmpConfig::get('theme_color', 'dark') . '.png';
    }

    /**
     * get_material_symbol
     *
     * Returns an <svg> tag for the specified Material Symbol
     */
    public static function get_material_symbol(string $name, ?string $title = null, ?string $id_attrib = null, ?string $class_attrib = null): string
    {
        // Same icons repeat all over a page: translate each name once.
        static $title_cache = [];
        $title              = $title ?? $title_cache[$name] ??= T_(ucfirst($name));
        $symbol_key         = $name;
        // Skip the per-call disk stat once the symbol is cached. Hundreds of calls per page.
        if (array_key_exists($name, self::$_symbol_cache)) {
            $symbol = self::$_symbol_cache[$name];
        } else {
            $filepath = __DIR__ . '/../../../resources/images/material-symbols/' . $name . '.svg';
            if (!is_file($filepath)) {
                // fall back to error icon if icon is missing
                debug_event(self::class, 'Runtime Error: icon ' . $name . ' not found.', 1);
                $symbol_key = 'icon_error';
                $filepath   = __DIR__ . '/../../../resources/images/icon_error.svg';
            }

            $symbol = self::_load_symbol_parts($symbol_key, $filepath);
        }

        if ($symbol === null) {
            return '';
        }

        self::$_used_symbols[$symbol_key] = true;

        // In AJAX fragments there is no page sprite emitted before </body>,
        // and each fragment is injected into its own DOM node via innerHTML,
        // so it must be self-contained. Emit the hidden <symbol> inline the
        // first time an icon appears in this response; every later occurrence
        // is just a <use>. Duplicate ids are ignored by the browser, so this
        // is safe even when the page sprite already holds the same symbol.
        $prefix = '';
        if (defined('AJAX_INCLUDE') && !isset(self::$_emitted_symbols[$symbol_key])) {
            self::$_emitted_symbols[$symbol_key] = true;
            $viewbox                             = ($symbol['viewbox'] !== '')
                ? ' viewBox="' . $symbol['viewbox'] . '"'
                : '';
            $prefix = '<svg xmlns="http://www.w3.org/2000/svg" width="0" height="0" class="ms-sprite-svg" aria-hidden="true">'
                . '<symbol id="ms-' . scrub_out($symbol_key) . '"' . $viewbox . '>' . $symbol['inner'] . '</symbol></svg>';
        }

        $tag = $prefix . '<svg' . $symbol['attrs'];
        if (!empty($id_attrib)) {
            $tag .= ' id="' . scrub_out($id_attrib) . '"';
        }

        $tag .= ' class="material-symbol material-symbol-' . scrub_out($name) . ' ' . scrub_out((string) $class_attrib) . '">';
        $tag .= '<title>' . scrub_out($title) . '</title>';
        $tag .= '<use href="#ms-' . scrub_out($symbol_key) . '"></use>';

        return $tag . '</svg>';
    }

    /**
     * This dumps out some html and an icon for the type of rss that we specify
     *
     * @param array<string, string>|null $params
     */
    public static function getRssLink(
        RssFeedTypeEnum $type,
        ?User $user = null,
        string $title = '',
        ?array $params = null,
        string $slug = '',
    ): string {
        $query = ['type' => $type->value];
        if ($user !== null && AmpConfig::get('use_auth')) {
            // On an open instance (use_auth false) every visitor shares the same default user,
            // so a token adds nothing and would leak into publicly served pages
            $query['rsstoken'] = $user->getRssToken();
        }

        if (is_array($params)) {
            foreach ($params as $key => $value) {
                $query[$key] = $value;
            }
        }

        $string = (
            '<a class="nohtml" href="' . scrub_out(RssUrl::published($query, $slug))
            . '" target="_blank" rel="nofollow noopener">'
            . self::get_material_symbol(
                'rss_feed',
                T_('RSS Feed')
            )
        );
        if ($title !== '' && $title !== '0') {
            $string .= '&nbsp;' . $title;
        }

        return $string . '</a>';
    }

    /**
     * is_grid_view
     */
    public static function is_grid_view(string $type): bool
    {
        $name = 'browse_' . $type . '_grid_view';
        if (isset($_COOKIE[$name])) {
            return ($_COOKIE[$name] == 'true');
        }

        return false;
    }

    /**
     * make_fragment_self_contained
     *
     * One ajax response is not one piece of markup: a handler fills a $results map and each entry is
     * dropped into a different element. get_material_symbol only emits an icon's <symbol> on the first
     * use in the *request* though, so the definition lands in whichever fragment rendered that icon
     * first and its siblings carry a bare <use>. They draw, because the id is somewhere in the
     * document -- right up until a later action replaces the fragment holding the definition, and
     * every other reference to it goes blank.
     *
     * So hand each fragment the definitions it references but does not carry, and none of them depends
     * on another one surviving. Only the gaps are filled, so this costs nothing for the fragment that
     * already emitted them inline.
     */
    public static function make_fragment_self_contained(string $html): string
    {
        if (!str_contains($html, '#ms-')) {
            return $html;
        }

        if (!preg_match_all('/<use\s[^>]*href="#ms-([^"]+)"/', $html, $used)) {
            return $html;
        }

        $missing = array_unique($used[1]);
        if (preg_match_all('/<symbol\s+id="ms-([^"]+)"/', $html, $held)) {
            $missing = array_diff($missing, $held[1]);
        }

        $symbols = '';
        foreach ($missing as $symbol_key) {
            $symbol = self::$_symbol_cache[$symbol_key] ?? null;
            if ($symbol === null) {
                continue;
            }

            $viewbox = ($symbol['viewbox'] !== '')
                ? ' viewBox="' . $symbol['viewbox'] . '"'
                : '';
            $symbols .= '<symbol id="ms-' . scrub_out($symbol_key) . '"' . $viewbox . '>' . $symbol['inner'] . '</symbol>';
        }

        if ($symbols === '') {
            return $html;
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" width="0" height="0" class="ms-sprite-svg" aria-hidden="true">'
            . $symbols
            . '</svg>'
            . $html;
    }

    /**
     * material_symbol_sprite
     *
     * Returns a single hidden <svg> sprite containing one <symbol> per
     * material symbol rendered on the current page. Must be echoed once,
     * right before </body>. Returns an empty string when no icon was used.
     */
    public static function material_symbol_sprite(): string
    {
        // Incremental: only emit symbols that were not part of a previous
        // sprite. The main layout may call this more than once (e.g. at the
        // end of the content block and again before </body>) to catch icons
        // rendered after the first call (footer, web player controls).
        $pending = array_diff_key(self::$_used_symbols, self::$_emitted_symbols);
        if ($pending === []) {
            return '';
        }

        $sprite = '<svg xmlns="http://www.w3.org/2000/svg" width="0" height="0" class="ms-sprite-svg" aria-hidden="true">';
        foreach (array_keys($pending) as $symbol_key) {
            self::$_emitted_symbols[$symbol_key] = true;
            $symbol                              = self::$_symbol_cache[$symbol_key] ?? null;
            if ($symbol === null) {
                continue;
            }

            // The viewBox of the source file is carried by the <symbol>
            // (see _load_symbol_parts for why it must not stay on the
            // per-icon <svg> tag).
            $viewbox = ($symbol['viewbox'] !== '')
                ? ' viewBox="' . $symbol['viewbox'] . '"'
                : '';
            $sprite .= '<symbol id="ms-' . scrub_out((string) $symbol_key) . '"' . $viewbox . '>' . $symbol['inner'] . '</symbol>';
        }

        return $sprite . '</svg>';
    }

    /**
     * This function takes a boolean value and then prints out a friendly text
     * message.
     */
    public static function printBool(?bool $value = false, bool $current = true): string
    {
        if (!$current) {
            return '<span class="item_inactive">' . ($value ? T_('On') : T_('Off')) . '</span>';
        }

        return $value ? '<span class="item_on">' . T_('On') . '</span>' : '<span class="item_off">' . T_('Off') . '</span>';
    }

    /**
     * show_box_bottom
     *
     * This shows the bottom of the box
     */
    public static function show_box_bottom(): void
    {
        echo new BoxBottomView()->render();
    }

    /**
     * show_box_top
     *
     * This shows the top of the box.
     */
    public static function show_box_top(string $title = '', string $class = ''): void
    {
        echo new BoxTopView($title, $class)->render();
    }

    public static function show_custom_style(): void
    {
        if (AmpConfig::get('custom_login_background', false)) {
            echo "<style> body { background-position: center; background-size: cover; background-image: url('" . AmpConfig::get('custom_login_background') . "') !important; }</style>";
        }

        if (AmpConfig::get('custom_login_logo', false)) {
            echo "<style>#loginPage #headerlogo, #registerPage #logo { background-image: url('" . AmpConfig::get('custom_login_logo') . "') !important; }</style>";
        }

        $pageMeta = PageMeta::render();
        echo self::branding_tags($pageMeta === '');
        echo $pageMeta;
    }

    /**
     * show_footer
     *
     * Shows the footer template and possibly profiling info.
     */
    public static function show_footer(): void
    {
        if (!defined("TABLE_RENDERED")) {
            show_table_render();
        }

        $user = Core::get_global('user');
        if ($user instanceof User) {
            $plugins = Plugin::get_plugins(PluginTypeEnum::FOOTER_WIDGET);
            foreach ($plugins as $plugin_name) {
                $plugin = new Plugin($plugin_name);
                if ($plugin->_plugin instanceof PluginDisplayOnFooterInterface && $plugin->load($user)) {
                    $plugin->_plugin->display_on_footer();
                }
            }
        }

        echo new FooterView()->render();
        if (Core::get_request('profiling') !== '') {
            Dba::show_profile();
        }
    }

    /**
     * unformat_bytes
     *
     * Parses a human-readable size
     * @noinspection PhpMissingBreakStatementInspection
     */
    public static function unformat_bytes(int|string $value): string
    {
        if (preg_match('/^(\d+) *([[:alpha:]]+)$/', (string) $value, $matches)) {
            $value = (int) $matches[1];
            $unit  = strtolower(substr($matches[2], 0, 1));
        } else {
            return (string) $value;
        }

        switch ($unit) {
            case 'p':
                $value *= 1024;
                // Intentional break fall-through
            case 't':
                $value *= 1024;
                // Intentional break fall-through
            case 'g':
                $value *= 1024;
                // Intentional break fall-through
            case 'm':
                $value *= 1024;
                // Intentional break fall-through
            case 'k':
                $value *= 1024;
        }

        return (string) $value;
    }

    /**
     * update_text
     *
     * Convenience function that, if the output is going to a browser,
     * blarfs JS to do a fancy update. Otherwise it just outputs the text.
     */
    public static function update_text(string $field, int|string $value): void
    {
        if (defined('API')) {
            return;
        }

        if (defined('CLI')) {
            echo $value . "\n";

            return;
        }

        static $update_id = 1;

        if (defined('SSE_OUTPUT')) {
            echo "id: " . $update_id . "\n";
            echo "data: " . json_encode(['fn' => 'displayNotification', 'args' => [$value, 5000]]) . "\n\n";
        } elseif ($field !== '' && $field !== '0') {
            echo "<script>updateText('" . $field . "', '" . json_encode($value) . "');</script>\n";
        } else {
            echo "<br />" . $value . "<br /><br />\n";
        }

        ob_flush();
        flush();
        $update_id++;
    }

    /**
     * _find_icon
     *
     * Does the finding icon thing. match svg first over png
     */
    private static function _find_icon(string $name): string
    {
        if (isset(self::$_icon_cache[$name])) {
            return self::$_icon_cache[$name];
        }

        $path = 'themes/' . AmpConfig::get('theme_name', 'reborn') . '/images/icons/';
        // Can't use GLOB_BRACE for Alpine compatibility https://github.com/ampache/ampache/issues/4008
        $filesearch = array_merge(
            glob(__DIR__ . '/../../../public/' . $path . 'icon_' . $name . '.svg') ?: [],
            glob(__DIR__ . '/../../../public/' . $path . 'icon_' . $name . '.png') ?: []
        );

        if ($filesearch === []) {
            // if the theme is missing an icon, fall back to default images folder
            $path = 'images/';
            // check private resources folder for svg files
            $filesearch = glob(__DIR__ . '/../../../resources/' . $path . 'icon_' . $name . '.svg');
            if ($filesearch === [] || $filesearch === false) {
                // finally fall back to the public images folder
                // Can't use GLOB_BRACE for Alpine compatibility https://github.com/ampache/ampache/issues/4008
                $filesearch = array_merge(
                    glob(__DIR__ . '/../../../public/' . $path . 'icon_' . $name . '.svg') ?: [],
                    glob(__DIR__ . '/../../../public/' . $path . 'icon_' . $name . '.png') ?: []
                );
            }
        }

        if ($filesearch !== [] && is_file($filesearch[0])) {
            $filename = pathinfo($filesearch[0], PATHINFO_BASENAME);
        } else {
            // fall back to error icon if icon is missing
            debug_event(self::class, 'Runtime Error: icon ' . $name . ' not found.', 1);

            return __DIR__ . '/../../../resources/images/icon_error.svg';
        }

        if (pathinfo($filename, PATHINFO_EXTENSION) === 'svg') {
            $url = $filesearch[0];
        } else {
            $url = AmpConfig::get_web_path() . '/' . $path . $filename;
        }

        // cache the url so you don't need to keep searching
        self::$_icon_cache[$name] = $url;

        return $url;
    }

    /**
     * _find_image
     *
     * Does the finding image thing. match svg first over png
     */
    private static function _find_image(string $name): string
    {
        if (isset(self::$_image_cache[$name])) {
            return self::$_image_cache[$name];
        }

        // always check themes first
        $path = 'themes/' . AmpConfig::get('theme_name', 'reborn') . '/images/';
        // Can't use GLOB_BRACE for Alpine compatibility https://github.com/ampache/ampache/issues/4008
        $filesearch = array_merge(
            glob(__DIR__ . '/../../../public/' . $path . $name . '.svg') ?: [],
            glob(__DIR__ . '/../../../public/' . $path . $name . '.png') ?: []
        );

        if ($filesearch === []) {
            $path = 'images/';
            // check private resources folder for svg files
            $filesearch = glob(__DIR__ . '/../../../resources/' . $path . $name . '.svg') ?: [];
            if ($filesearch === []) {
                // finally fall back to the public images folder
                // Can't use GLOB_BRACE for Alpine compatibility https://github.com/ampache/ampache/issues/4008
                $filesearch = array_merge(
                    glob(__DIR__ . '/../../../public/' . $path . $name . '.svg') ?: [],
                    glob(__DIR__ . '/../../../public/' . $path . $name . '.png') ?: []
                );
            }
        }

        if ($filesearch === []) {
            // if the theme is missing an image. fall back to default images folder
            $filename = $name . '.png';
            $path     = 'images/';
        } else {
            $filename = pathinfo($filesearch[0], PATHINFO_BASENAME);
        }

        if (
            $filesearch
            && pathinfo($filename, PATHINFO_EXTENSION) === 'svg'
        ) {
            $url = $filesearch[0];
        } else {
            $url = AmpConfig::get_web_path() . '/' . $path . $filename;
        }

        // cache the url so you don't need to keep searching
        self::$_image_cache[$name] = $url;

        return $url;
    }

    /**
     * _load_symbol_parts
     *
     * Splits an svg icon file into: its root attributes without viewBox
     * (kept on the per-icon <svg> tag), its viewBox (moved onto the
     * <symbol> in the page sprite) and its inner content (moved once into
     * the sprite).
     *
     * The viewBox MUST be on the <symbol> and MUST NOT be on the outer
     * <svg>: material symbols draw in "0 -960 960 960" (negative Y) and
     * the nested viewport created by <use> lands in the 0..960 region of
     * the outer coordinate system - with the original viewBox kept on the
     * outer <svg>, the whole drawing sits outside the visible area.
     *
     * @return array{attrs: string, viewbox: string, inner: string}|null
     */
    private static function _load_symbol_parts(string $symbol_key, string $filepath): ?array
    {
        if (!array_key_exists($symbol_key, self::$_symbol_cache)) {
            $content = file_get_contents($filepath);
            if ($content !== false && preg_match('/<svg([^>]*)>(.*)<\/svg>/s', $content, $matches)) {
                $viewbox = (preg_match('/\sviewBox="([^"]*)"/', $matches[1], $vb_match))
                    ? $vb_match[1]
                    : '';
                self::$_symbol_cache[$symbol_key] = [
                    // xmlns is implied for inline svg in an html document, and this markup repeats
                    // thousands of times on a large listing
                    'attrs' => rtrim((string) preg_replace(['/\sviewBox="[^"]*"/', '/\sxmlns="[^"]*"/'], '', $matches[1])),
                    'viewbox' => $viewbox,
                    'inner' => trim($matches[2]),
                ];
            } else {
                self::$_symbol_cache[$symbol_key] = null;
            }
        }

        return self::$_symbol_cache[$symbol_key];
    }

    /**
     * A link preview is fetched by somebody else's server, so a relative image never resolves
     */
    private static function absolute(string $url): string
    {
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        if ($url === '' || $host === '' || preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//#i', $url) === 1) {
            return $url;
        }

        $ssl = (Core::get_server('HTTP_X_FORWARDED_PROTO') === 'https' || Core::get_server('HTTPS') === 'on');

        return (($ssl) ? 'https://' : 'http://') . $host . '/' . ltrim($url, '/');
    }

    /**
     * The icon and link-preview tags, from whatever the administrator supplied
     *
     * The shipped artwork is all-or-nothing: an instance that customises its icon never gets
     * Ampache's own next to it. A vector answers every size at once, so it is preferred where it
     * works; where a raster is required and only a vector was given, the tag is left out rather
     * than filled with somebody else's logo.
     *
     * @param bool $withSocialCard false when the page already described its own object through PageMeta
     */
    private static function branding_tags(bool $withSocialCard): string
    {
        $webPath = AmpConfig::get_web_path();
        $custom  = trim((string) AmpConfig::get('custom_favicon', ''));
        $vector  = str_ends_with(strtolower((string) parse_url($custom, PHP_URL_PATH)), '.svg');

        $tags = [];
        if ($custom === '') {
            $tags[] = '<link rel="icon" href="' . $webPath . '/favicon.svg" type="image/svg+xml">';
            $tags[] = '<link rel="icon" href="' . $webPath . '/favicon.ico" sizes="48x48">';
        } elseif ($vector) {
            $tags[] = '<link rel="icon" href="' . self::esc($custom) . '" type="image/svg+xml">';
        } else {
            $tags[] = '<link rel="icon" href="' . self::esc($custom) . '">';
        }

        // a phone home screen and a shared link both refuse svg, so each falls back to its own
        // setting, then to the raster favicon, and is dropped when only a vector is on offer
        $touch = trim((string) AmpConfig::get('custom_apple_touch_icon', ''))
            ?: (($custom !== '' && !$vector) ? $custom : '')
            ?: (($custom === '') ? $webPath . '/apple-touch-icon.png' : '');
        if ($touch !== '') {
            $tags[] = '<link rel="apple-touch-icon" href="' . self::esc($touch) . '">';
        }

        // a shared link always shows something: the shipped card stands in when nothing usable was
        // supplied, since Ampache's artwork beats the stray page image a scraper settles on
        if ($withSocialCard) {
            $supplied = trim((string) AmpConfig::get('custom_share_image', ''));
            $square   = ($supplied === '' && $custom !== '' && !$vector);
            $share    = $supplied ?: (($square) ? $custom : $webPath . '/ampache-card.png');

            $tags[] = '<meta property="og:image" content="' . self::esc(self::absolute($share)) . '">';
            // a favicon standing in for the wide artwork would be cropped by the banner card format
            $tags[] = '<meta name="twitter:card" content="' . (($square) ? 'summary' : 'summary_large_image') . '">';
        }

        $title = trim((string) AmpConfig::get('site_title', ''));
        if ($title !== '') {
            $tags[] = '<meta property="og:site_name" content="' . self::esc($title) . '">';
        }

        if ($withSocialCard) {
            $description = trim((string) AmpConfig::get('site_description', ''));
            if ($description !== '') {
                $tags[] = '<meta name="description" content="' . self::esc($description) . '">';
                $tags[] = '<meta property="og:description" content="' . self::esc($description) . '">';
            }

            $tags[] = '<meta property="og:type" content="website">';
        }

        return implode("\n", $tags) . "\n";
    }

    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function accessDenied(string $error = 'Access Denied'): void
    {
        // Clear any buffered crap
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        // A handler that already flushed its buffer has sent the headers, so setting the status here
        // would only raise "headers already sent". showErrorPage() guards the same way.
        if (!headers_sent()) {
            header('HTTP/1.1 403 ' . $error);
        }

        echo $this->_createStandaloneErrorView(StandaloneErrorTypeEnum::ACCESS_DENIED)->render();
    }

    /**
     * Displays an error page when you can't write the config
     */
    public function permissionDenied(string $fileName): void
    {
        // Clear any buffered crap
        ob_end_clean();
        header("HTTP/1.1 403 Permission Denied");
        echo $this->_createStandaloneErrorView(StandaloneErrorTypeEnum::PERMISSION_DENIED, $fileName)->render();
    }

    /**
     * This function is used to escape user data that is getting redisplayed
     * onto the page, it htmlentities the mojo
     * This is the inverse of the scrub_in function
     */
    public function scrubOut(?string $string): string
    {
        if ($string === null) {
            return '';
        }

        return htmlentities($string, ENT_QUOTES, AmpConfig::get('site_charset', 'UTF-8'));
    }

    /**
     * Show the requested template file
     */
    public function show(string $template, array $context = []): void
    {
        extract($context);

        require_once self::find_template($template);
    }

    public function showBoxBottom(): void
    {
        static::show_box_bottom();
    }

    public function showBoxTop(string $title = '', string $class = ''): void
    {
        static::show_box_top($title, $class);
    }

    /**
     * shows a confirmation of an action
     */
    public function showConfirmation(
        string $title,
        string $text,
        string $next_url,
        ?int $cancel = 0,
        ?string $form_name = 'confirmation',
        ?bool $visible = true,
    ): void {
        $webPath   = $this->configContainer->getWebPath();
        $path      = $this->isAbsoluteUrl($next_url) ? $next_url : sprintf('%s/%s', $webPath, $next_url);
        $cancelUrl = null;
        if ($cancel) {
            // return_referer() already went through scrub_in(), so its & is html-escaped; decode before use or e() escapes it twice.
            $referer = htmlspecialchars_decode(return_referer(), ENT_NOQUOTES);
            // explicit '' survives the client-structure build unchanged, unlike $webPath; the referer already has its own "admin/" prefix when needed.
            $refererBase = str_starts_with($referer, 'admin/') ? $this->configContainer->getWebPath('') : $webPath;
            $cancelUrl   = sprintf('%s/%s', $refererBase, $referer);
        }

        echo new ConfirmationView(
            $webPath,
            $title,
            $text,
            $path,
            (string) $form_name,
            $cancelUrl
        )->render();
    }

    /**
     * shows a confirmation of an action
     */
    public function showConfirmationWithReturn(
        string $title,
        string $text,
        string $return_url,
        string $cancel_url,
        ?string $form_name = 'confirmation',
        ?bool $visible = true,
    ): void {
        $webPath = $this->configContainer->getWebPath();
        $return  = $this->isAbsoluteUrl($return_url) ? $return_url : sprintf('%s/%s', $webPath, $return_url);
        $cancel  = $this->isAbsoluteUrl($cancel_url) ? $cancel_url : sprintf('%s/%s', $webPath, $cancel_url);

        echo new ConfirmationWithReturnView(
            $webPath,
            $title,
            $text,
            $return,
            (string) $form_name,
            $cancel
        )->render();
    }

    /**
     * shows a simple continue button after an action
     */
    public function showContinue(
        string $title,
        string $text,
        string $next_url,
    ): void {
        $webPath = $this->configContainer->getWebPath();
        $path    = $this->isAbsoluteUrl($next_url) ? $next_url : sprintf('%s/%s', $webPath, $next_url);

        echo new ContinueView(
            $webPath,
            $title,
            $text,
            $path
        )->render();
    }

    /**
     * Displays the default error page
     */
    public function showErrorPage(): void
    {
        // the error usually arrives part way through a page, so throw away whatever has been written so far
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
        }

        echo $this->_createStandaloneErrorView(StandaloneErrorTypeEnum::ERROR)->render();
    }

    public function showFooter(): void
    {
        static::show_footer();
    }

    public function showHeader(): void
    {
        // Users locked into the mini player never see the full interface. This is the only caller of
        // header.inc.php so it covers every full page; ajax, stream, play, util, image and the API
        // don't come through here, so playback and artwork are untouched. m.php builds its own header
        // so there is no redirect loop. NOTE: this hides the interface, it does not replace access
        // levels; they remain the thing that actually gates data.
        $user = Core::get_global('user');
        if (
            $user instanceof User
            && $user->getId() > 0
            && !headers_sent()
            && !Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
            && Preference::get_by_user($user->getId(), 'mini_player')
        ) {
            header('Location: ' . AmpConfig::get_web_path() . '/m/');

            exit;
        }

        $isAdmin = Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN);
        if (!array_key_exists('state', $_SESSION) || !array_key_exists('sidebar_tab', $_SESSION['state'])) {
            $_SESSION['state']['sidebar_tab'] = 'home';
        }

        echo new HeaderView(
            AmpConfig::get_web_path(),
            AmpConfig::get_web_path('/admin'),
            $this->environment,
            $this->ajaxUriRetriever,
            $this->collectionRepository,
            $this->libraryItemLoader,
            $this->playlistLoader,
            $this->privateMessageRepository,
            $this->zipHandler,
            $this->sidebarViewFactory,
            ($user instanceof User) ? $user : null,
            (string) $_SESSION['state']['sidebar_tab'],
            $isAdmin,
            $isAdmin || Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER),
            Upload::can_upload($user),
            User::is_registered() && ($user?->getId() ?? 0) > 0
        )->render();
    }

    public function showObjectNotFound(): void
    {
        $this->showHeader();
        echo T_('You have requested an object that does not exist');
        $this->showQueryStats();
        $this->showFooter();
    }

    /**
     * This shows the query stats
     */
    public function showQueryStats(): void
    {
        if (!AmpConfig::get('show_footer_statistics')) {
            return;
        }

        echo new QueryStatsView(
            Dba::$stats['query'],
            database_object::$cache_hit,
            (float) AmpConfig::get('load_time_begin'),
            memory_get_peak_usage(true)
        )->render();
    }

    public function showRightbar(): string
    {
        return new RightbarView(
            $this->collectionRepository,
            $this->libraryItemLoader,
            $this->playlistLoader,
            $this->zipHandler,
            AmpConfig::get_web_path()
        )->render();
    }

    /**
     * The three standalone error pages carry their own chrome, so each needs the logo and title the
     * normal header would otherwise have supplied.
     */
    private function _createStandaloneErrorView(
        StandaloneErrorTypeEnum $type,
        string $detail = '',
    ): StandaloneErrorView {
        $logoUrl = (string) AmpConfig::get('custom_login_logo', '');

        return new StandaloneErrorView(
            $type,
            AmpConfig::get_web_path(),
            ($logoUrl === '') ? self::get_logo_url('dark') : $logoUrl,
            (string) AmpConfig::get('site_title'),
            (bool) AmpConfig::get('demo_mode'),
            $detail
        );
    }

    /**
     * callers pass both absolute urls (which may target a plain web-root page with no `/client` segment on
     * the client structure) and bare page paths; only the latter need the web path prefixed.
     */
    private function isAbsoluteUrl(string $url): bool
    {
        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
    }
}
