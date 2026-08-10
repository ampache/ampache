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

namespace Ampache\Gui\Label;

use Ampache\Gui\View\AbstractView;
use Ampache\Module\Database\Query\Browse;
use Ampache\Repository\Model\Label;
use Override;

/**
 * The detail page for a record label, with its artists, albums and songs in tabs.
 */
final class LabelView extends AbstractView
{
    /**
     * The external search sites, as config key => [icon, display name, url template].
     *
     * The template takes the label name url-encoded, so each site is one row here rather than one copied
     * block apiece.
     */
    private const array EXTERNAL_LINKS = [
        'external_links_google' => ['google', 'Google', 'https://www.google.com/search?q=%%22%s%%22'],
        'external_links_duckduckgo' => ['duckduckgo', 'DuckDuckGo', 'https://www.duckduckgo.com/?q=%s'],
        'external_links_wikipedia' => ['wikipedia', 'Wikipedia', 'https://en.wikipedia.org/wiki/Special:Search?search=%%22%s%%22&go=Go'],
        'external_links_lastfm' => ['lastfm', 'Last.fm', 'https://www.last.fm/search?q=%%22%s%%22&type=label'],
        'external_links_bandcamp' => ['bandcamp', 'Bandcamp', 'https://bandcamp.com/search?q=%s&item_type=b'],
        'external_links_discogs' => ['discogs', 'Discogs', 'https://www.discogs.com/search/?q=%s&type=label'],
    ];

    /**
     * @param array<int> $artistIds
     * @param array<string, bool> $enabledExternalLinks
     */
    public function __construct(
        private readonly string $webPath,
        private readonly Label $label,
        private readonly Browse $browse,
        private readonly array $artistIds,
        private readonly array $enabledExternalLinks,
        private readonly bool $mayShout,
        private readonly bool $mayEdit,
        private readonly bool $mayDelete,
    ) {}

    /**
     * The config keys that switch each external site on, so a caller can read them without repeating them.
     *
     * @return list<string>
     */
    public static function getExternalLinkKeys(): array
    {
        return array_keys(self::EXTERNAL_LINKS);
    }

    /**
     * @return array<int>
     */
    public function getArtistIds(): array
    {
        return $this->artistIds;
    }

    public function getBrowse(): Browse
    {
        return $this->browse;
    }

    public function getDeleteUrl(): string
    {
        return $this->webPath . '/labels.php?action=delete&label_id=' . $this->label->id;
    }

    /**
     * @return list<array{icon: string, title: string, url: string}>
     */
    public function getExternalLinks(): array
    {
        $encodedName = rawurlencode($this->getName());

        $links = [];
        foreach (self::EXTERNAL_LINKS as $configKey => [$icon, $siteName, $urlTemplate]) {
            if (($this->enabledExternalLinks[$configKey] ?? false) === false) {
                continue;
            }

            $links[] = [
                'icon' => $icon,
                'title' => sprintf(T_('Search on %s ...'), $siteName),
                'url' => sprintf($urlTemplate, $encodedName),
            ];
        }

        return $links;
    }

    public function getLabel(): Label
    {
        return $this->label;
    }

    public function getName(): string
    {
        return (string) $this->label->get_fullname();
    }

    public function getShoutUrl(): string
    {
        return $this->webPath . '/shout.php?action=show_add_shout&type=label&id=' . $this->label->id;
    }

    /**
     * A label's website comes from whoever created it, so only an http url is offered as a link.
     */
    public function getWebsiteUrl(): ?string
    {
        $website = (string) $this->label->website;

        return (preg_match('#^https?://#i', $website) === 1) ? $website : null;
    }

    public function mayDelete(): bool
    {
        return $this->mayDelete;
    }

    public function mayEdit(): bool
    {
        return $this->mayEdit;
    }

    public function mayShout(): bool
    {
        return $this->mayShout;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('label.phtml');
    }
}
