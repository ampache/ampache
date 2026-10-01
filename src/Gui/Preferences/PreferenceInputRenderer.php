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

namespace Ampache\Gui\Preferences;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Module\System\Plugin\Plugin;
use Ampache\Module\Util\Ui;
use Ampache\Plugin\AmpacheLastfm;
use Ampache\Plugin\Ampachelibrefm;

/**
 * Draws the control for one preference from its type, carrying `data-default` and `data-initial` for the page
 */
final readonly class PreferenceInputRenderer
{
    /** Preferences that are an authorization link rather than a field */
    private const array GRANT_LINKS = ['lastfm_grant_link', 'librefm_grant_link'];
    /** Preferences holding several values at once, stored comma separated */
    private const array MULTIPLE = [
        'disabled_custom_metadata_fields',
        'personalfav_playlist',
        'personalfav_smartlist',
    ];

    /** @return array<array-key, string> */
    private static function booleanChoices(): array
    {
        return ['1' => T_('On'), '0' => T_('Off')];
    }

    /**
     * How a stored value reads, so the default and server columns speak the same language as the control.
     */
    public function label(PreferenceItem $item, ?string $value): string
    {
        if ($value === null || $value === '') {
            return T_('(empty)');
        }

        $choices = $this->choicesFor($item);
        if ($choices === []) {
            return $value;
        }

        // locale labels carry entities on purpose; the caller escapes, so they are decoded here or shown raw
        return html_entity_decode(
            implode(', ', array_map(fn(string $part): string => $this->choiceLabel($choices, $part), $this->parts($item, $value))),
            ENT_QUOTES,
            'UTF-8'
        );
    }

    public function render(PreferenceItem $item, PreferenceSubject $subject): string
    {
        if (!$item->editable) {
            return $this->renderReadOnly($item);
        }

        // after `editable`: the link sends the operator to a third party, so demo mode has to refuse first
        if (in_array($item->name, self::GRANT_LINKS, true)) {
            return $this->renderGrantLink($item, $subject);
        }

        if ($item->isSecret) {
            return $this->renderSecret($item);
        }

        if ($item->type === PreferenceType::BOOLEAN) {
            return $this->renderChoices($item, self::booleanChoices());
        }

        if ($item->choices !== null && $item->choices !== []) {
            return $this->renderChoices($item, $item->choices);
        }

        // an empty list is still a set of ids, never a quantity, so it does not fall through to a number box
        if ($item->choices === []) {
            return $this->renderEmptyChoices($item);
        }

        if ($item->type === PreferenceType::INTEGER) {
            return $this->renderNumber($item);
        }

        return $this->renderText($item);
    }

    /**
     * The value the control actually holds, which is what "unsaved" and "back to default" compare.
     */
    private function canonical(PreferenceItem $item, ?string $value): string
    {
        $value   = (string) $value;
        $choices = $this->choicesFor($item);

        if ($choices === [] || $value === '') {
            return $value;
        }

        return implode(',', array_map(fn(string $part): string => $this->choiceKey($choices, $part), $this->parts($item, $value)));
    }

    /**
     * Matches a stored value to the option that spells it, so `0.80` still selects the `0.8` entry
     *
     * @param array<array-key, string> $choices
     */
    private function choiceKey(array $choices, string $value): string
    {
        if (array_key_exists($value, $choices)) {
            return $value;
        }

        if (is_numeric($value)) {
            foreach (array_keys($choices) as $candidate) {
                if (is_numeric($candidate) && (float) $candidate === (float) $value) {
                    return (string) $candidate;
                }
            }
        }

        return $value;
    }

    /**
     * @param array<array-key, string> $choices
     */
    private function choiceLabel(array $choices, string $value): string
    {
        $key = $this->choiceKey($choices, $value);

        return $choices[$key] ?? $value;
    }

    /**
     * The values the control accepts, which a boolean carries in code rather than in the database.
     *
     * @return array<array-key, string>
     */
    private function choicesFor(PreferenceItem $item): array
    {
        return ($item->type === PreferenceType::BOOLEAN) ? self::booleanChoices() : ($item->choices ?? []);
    }

    /**
     * @param ?string $shown the value the field displays, when it is not the stored one
     * @param ?string $shownDefault the default as the field would display it, when that differs too
     */
    private function dataAttributes(PreferenceItem $item, ?string $shown = null, ?string $shownDefault = null): string
    {
        return sprintf(
            'data-pref="%s" data-default="%s" data-initial="%s"%s',
            $this->e($item->name),
            $this->e($shownDefault ?? $this->canonical($item, $item->shippedDefault)),
            $this->e($shown ?? $this->canonical($item, $item->value)),
            ($item->systemValue === null)
                ? ''
                : sprintf(' data-server="%s"', $this->e($this->canonical($item, $item->systemValue)))
        );
    }

    /**
     * Labels from the locale folder carry intentional entities, so they are decoded before being escaped.
     */
    private function e(string $value): string
    {
        return htmlspecialchars(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
    }

    private function isMultiple(PreferenceItem $item): bool
    {
        return in_array($item->name, self::MULTIPLE, true);
    }

    /**
     * Units and what a zero means, per preference, built so that `xgettext` sees every `T_()` literal
     *
     * @return array<string, array{0: int, 1: int, 2: string, 3: ?string, 4: ?string}>
     */
    private function numberHints(): array
    {
        return [
            'transcode_bitrate' => [0, 1000, T_('bps'), null, T_('the source file rate')],
            'transcode_bitrate_webplayer' => [0, 1000, T_('bps'), 'transcode_bitrate', null],
            'transcode_bitrate_api' => [0, 1000, T_('bps'), 'transcode_bitrate', null],
            'max_bit_rate' => [0, 1000, T_('bps'), null, T_('no ceiling')],
            'min_bit_rate' => [0, 1000, T_('bps'), null, T_('no floor')],
            'rate_limit' => [0, 1024, T_('KB/s'), null, T_('unlimited')],
        ];
    }

    /**
     * The stored values of a control, one for a plain field and several for a comma-separated multi-select
     *
     * @return list<string>
     */
    private function parts(PreferenceItem $item, string $value): array
    {
        return $this->isMultiple($item) ? explode(',', $value) : [$value];
    }

    /**
     * @param array<array-key, string> $choices
     */
    private function renderChoices(PreferenceItem $item, array $choices): string
    {
        $multiple  = $this->isMultiple($item);
        $canonical = $this->canonical($item, $item->value);
        $selected  = $multiple
            ? array_filter(explode(',', $canonical), static fn(string $part): bool => $part !== '')
            : [$canonical];

        $options = '';
        foreach ($choices as $value => $label) {
            $options .= sprintf(
                '<option value="%s"%s>%s</option>',
                $this->e((string) $value),
                in_array((string) $value, $selected, true) ? ' selected="selected"' : '',
                $this->e($label)
            );
        }

        return sprintf(
            '<select class="pref-control" id="%s" name="%s"%s %s>%s</select>',
            $this->e($item->inputId()),
            $this->e($item->name) . ($multiple ? '[]' : ''),
            $multiple ? ' multiple="multiple" size="5"' : '',
            $this->dataAttributes($item),
            $options
        );
    }

    /**
     * A list with nothing to offer: shown disabled, with the value carried in a hidden field so that
     * saving the form does not silently blank it.
     */
    private function renderEmptyChoices(PreferenceItem $item): string
    {
        return sprintf(
            '<select class="pref-control" id="%s" disabled="disabled"%s><option value="">%s</option></select>'
            . '<input type="hidden" name="%s" value="%s" %s>',
            $this->e($item->inputId()),
            ($this->isMultiple($item)) ? ' multiple="multiple" size="5"' : '',
            $this->e(T_('Nothing to choose from')),
            $this->e($item->name),
            $this->e($this->canonical($item, $item->value)),
            $this->dataAttributes($item)
        );
    }

    /**
     * The link grants the signed-in account, so it is never shown while editing someone else's page.
     */
    private function renderGrantLink(PreferenceItem $item, PreferenceSubject $subject): string
    {
        if (!$subject->isSelf) {
            return sprintf('<span class="pref-readonly">%s</span>', $this->e(T_('Only the account holder can grant access')));
        }

        $pluginName = ucfirst(str_replace('_grant_link', '', $item->name));
        $plugin     = new Plugin($pluginName);
        $user       = $subject->user;

        if (
            !(
                $plugin->_plugin instanceof Ampachelibrefm
                || $plugin->_plugin instanceof AmpacheLastfm
            )
        ) {
            return '';
        }

        // load() fails on the empty challenge this link exists to fill, so only the api key decides
        $plugin->load($user);
        if ((string) $plugin->_plugin->api_key === '') {
            return '';
        }

        // the token rides along to the service and back, so a third party cannot trigger the callback write
        $callback = rawurlencode(
            AmpConfig::get_web_path() . '/preferences.php?tab=plugins&action=grant&plugin=' . $pluginName
            . '&form_validation=' . Core::form_register('grant', 'get')
        );

        return sprintf(
            '<a class="pref-grant" href="%s/api/auth/?api_key=%s&cb=%s" target="_blank" rel="noopener">%s</a>',
            $this->e($plugin->_plugin->url),
            rawurlencode((string) $plugin->_plugin->api_key),
            $callback,
            /* HINT: Plugin Name */
            Ui::get_material_symbol('extension', sprintf(T_('Click to grant %s access to Ampache'), $pluginName))
        );
    }

    private function renderNumber(PreferenceItem $item): string
    {
        [$min, $step, $unit, $fallback, $zeroMeans] = $this->numberHints()[$item->name] ?? [null, null, null, null, null];

        // an empty box means "use the server value", so a zero is shown as empty to let the placeholder speak
        $value       = ($fallback !== null && (int) $item->value <= 0) ? '' : $item->value;
        $placeholder = ($fallback === null)
            ? ''
            : sprintf(' placeholder="%s"', $this->e((string) AmpConfig::get_int($fallback, 128000)));

        return sprintf(
            '<input class="pref-control" type="number" id="%s" name="%s" value="%s"%s%s%s %s />%s%s',
            $this->e($item->inputId()),
            $this->e($item->name),
            $this->e($value),
            ($min === null) ? '' : sprintf(' min="%d"', $min),
            ($step === null) ? '' : sprintf(' step="%d"', $step),
            $placeholder,
            // the field shows blank for a zero, so its default reads blank too or the row is changed for ever
            $this->dataAttributes($item, $value, ($fallback !== null) ? $value : null),
            ($unit === null) ? '' : sprintf(' <span class="pref-unit">%s</span>', $this->e($unit)),
            // spelling out what a zero means beats a parenthesis that wraps under the field
            ($zeroMeans === null)
                ? ''
                : sprintf(
                    ' <span class="pref-zero" data-pref-zero%s>%s</span>',
                    ((int) $value === 0 && $value !== '') ? '' : ' hidden',
                    $this->e('— ' . $zeroMeans)
                )
        );
    }

    /**
     * Without the right level the value is shown, never offered: no input means nothing to post.
     */
    private function renderReadOnly(PreferenceItem $item): string
    {
        $shown = ($item->isSecret)
            ? (($item->secretIsSet) ? '******' : '')
            : $this->label($item, $item->value);

        return sprintf('<span class="pref-readonly">%s</span>', $this->e($shown));
    }

    /**
     * A secret is write-only, so the field starts blank and a blank submit keeps what is stored.
     */
    private function renderSecret(PreferenceItem $item): string
    {
        // blank is the default too, or every secret reads as changed; a server value would be the secret
        return sprintf(
            '<input class="pref-control" type="password" id="%s" name="%s" value="" placeholder="%s"'
            . ' autocomplete="new-password" data-pref="%s" data-initial="" data-default="" data-pref-secret />',
            $this->e($item->inputId()),
            $this->e($item->name),
            $item->secretIsSet ? '******' : '',
            $this->e($item->name)
        );
    }

    private function renderText(PreferenceItem $item): string
    {
        return sprintf(
            '<input class="pref-control" type="text" id="%s" name="%s" value="%s" %s />',
            $this->e($item->inputId()),
            $this->e($item->name),
            $this->e($item->value),
            $this->dataAttributes($item)
        );
    }
}
