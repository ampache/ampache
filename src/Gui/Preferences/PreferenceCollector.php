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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\System\Plugin\Plugin;
use Ampache\Module\System\Preference;
use Ampache\Plugin\PluginPreferenceHelpInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;

/**
 * Builds the `PreferenceItem` list a preferences screen renders, for one subject.
 */
final readonly class PreferenceCollector
{
    public const string PLUGIN_CATEGORY = 'plugins';

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PreferenceHelpCatalog $helpCatalog,
        private PreferenceChoiceProviderInterface $choiceProvider,
        private PreferencePrerequisiteCatalog $prerequisites,
        private ConfigContainerInterface $configContainer,
    ) {}

    /**
     * @param User $operator whoever is filling in the form, which is not always the subject
     * @return array<string, list<PreferenceItem>> category => its preferences, in the repository's order
     */
    public function collect(PreferenceSubject $subject, User $operator): array
    {
        [$rows, $held, $systemValues, $demoMode] = $this->gather($subject);
        $pluginHelp                              = $this->pluginHelp($rows);

        $collected = [];
        foreach ($rows as $row) {
            $collected[$row['category']][] = $this->item(
                $row,
                $systemValues[$row['name']] ?? null,
                $this->choiceProvider->find($row['name'], $subject, $held),
                !$demoMode && $operator->access >= $row['level'],
                $this->prerequisites->find($row['name'], $held),
                $pluginHelp[$row['name']] ?? null,
                $held,
            );
        }

        return $collected;
    }

    /**
     * Same data as collect(), but only builds the one tab a settings screen actually renders. collect()
     * was building and then discarding every other category's items on every page view, running a
     * choice-provider lookup (catalog/LocalPlay queries) for rows nobody was going to see.
     *
     * @param User $operator whoever is filling in the form, which is not always the subject
     * @return array{0: string, 1: list<PreferenceItem>} the resolved tab (falls back to the first
     *         category when $tab names none of them) and its items
     */
    public function collectTab(PreferenceSubject $subject, User $operator, string $tab): array
    {
        [$rows, $held, $systemValues, $demoMode] = $this->gather($subject);

        $rowsByCategory = [];
        foreach ($rows as $row) {
            $rowsByCategory[$row['category']][] = $row;
        }

        $resolvedTab  = isset($rowsByCategory[$tab]) ? $tab : (string) (array_key_first($rowsByCategory) ?? '');
        $categoryRows = $rowsByCategory[$resolvedTab] ?? [];
        $pluginHelp   = $this->pluginHelp($categoryRows);

        $items = [];
        foreach ($categoryRows as $row) {
            $items[] = $this->item(
                $row,
                $systemValues[$row['name']] ?? null,
                $this->choiceProvider->find($row['name'], $subject, $held),
                !$demoMode && $operator->access >= $row['level'],
                $this->prerequisites->find($row['name'], $held),
                $pluginHelp[$row['name']] ?? null,
                $held,
            );
        }

        return [$resolvedTab, $items];
    }

    /**
     * The config-file settings the rules need, keyed as the rules name them.
     *
     * @return array<string, string>
     */
    private function configSettings(): array
    {
        $settings = [];
        foreach ($this->prerequisites->configKeys() as $key) {
            $settings[PreferencePrerequisite::CONFIG_PREFIX . $key] = ($this->configContainer->get($key)) ? '1' : '';
        }

        return $settings;
    }

    /**
     * The rows and the subject-wide lookups every item needs, shared by collect() and collectTab()
     *
     * @return array{0: list<array{name: string, description: string, category: string, subcategory: ?string, type: string, level: int, value: ?string, default_value: ?string}>, 1: array<string, string>, 2: array<string, string>, 3: bool}
     */
    private function gather(PreferenceSubject $subject): array
    {
        // `Preference::has_access()` locks every field in demo mode; `User::has_access()` does the opposite
        $demoMode     = $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE);
        $systemValues = ($subject->isServer) ? [] : $this->systemValues();

        $rows = $this->userRepository->getPreferenceRows($subject->userId, null, !$subject->isServer);

        // a rule reads preferences from other tabs, so the whole picture is built before any item is.
        // The server rows go in first: a user's own rows never carry the `system` category, and a rule
        // asking whether a backend is on would otherwise read nothing and never fire on their page.
        $held = $this->configSettings() + $systemValues;
        foreach ($rows as $row) {
            $held[$row['name']] = (string) ($row['value'] ?? '');
        }

        return [$rows, $held, $systemValues, $demoMode];
    }

    /**
     * Turns one `UserRepository::getPreferenceRows()` row into the item a screen renders
     *
     * @param array{name: string, description: string, category: string, subcategory: ?string, type: string, level: int, value: mixed, default_value?: mixed} $row
     * @param ?string $systemValue the `user = -1` value, null when the subject is the system itself
     * @param ?array<array-key, string> $choices from `PreferenceChoiceProvider`
     * @param array<string, string> $held this subject's own values, by name, for a number field's fallback
     */
    private function item(array $row, ?string $systemValue, ?array $choices, bool $editable, ?string $warning, ?PreferenceHelp $pluginHelp, array $held): PreferenceItem
    {
        $name         = $row['name'];
        $value        = (string) ($row['value'] ?? '');
        $isSecret     = Preference::isSecretName($name);
        $fallbackName = PreferenceInputRenderer::numberHints()[$name][3] ?? null;

        return new PreferenceItem(
            name: $name,
            description: $row['description'],
            type: PreferenceType::fromDatabase($row['type']),
            subcategory: ($row['subcategory'] === null || $row['subcategory'] === '') ? null : $row['subcategory'],
            level: $row['level'],
            // a secret is write-only: it must not reach a template that could echo it
            value: $isSecret ? '' : $value,
            // a plugin's own preference has no entry in the static catalogue, so it falls back to what the
            // `preference` row itself was created with, which core preferences never need since they are
            // always in the catalogue
            shippedDefault: Preference::DEFAULTS[$name][0] ?? ($row['default_value'] ?? null),
            systemValue: $isSecret ? null : $systemValue,
            choices: $choices,
            editable: $editable,
            isSecret: $isSecret,
            secretIsSet: $isSecret && $value !== '',
            // a plugin knows its own settings better than the shipped catalogue does
            help: $pluginHelp ?? $this->helpCatalog->find($name),
            warning: $warning,
            numberFallback: ($fallbackName === null) ? null : ((($held[$fallbackName] ?? '') === '') ? null : $held[$fallbackName]),
        );
    }

    /**
     * The help a plugin writes for its own preferences, keyed by preference name
     *
     * Resolved once per request rather than per row: the subcategory carries the plugin name, and a plugin
     * answers for every preference it owns.
     *
     * @param list<array{name: string, category: string, subcategory: ?string, value: mixed}> $rows
     * @return array<string, PreferenceHelp>
     */
    private function pluginHelp(array $rows): array
    {
        $plugins = [];
        $helps   = [];
        foreach ($rows as $row) {
            if ($row['category'] !== self::PLUGIN_CATEGORY || $row['subcategory'] === null) {
                continue;
            }

            $owner = $row['subcategory'];
            if (!array_key_exists($owner, $plugins)) {
                $loaded          = new Plugin($owner)->_plugin;
                $plugins[$owner] = ($loaded instanceof PluginPreferenceHelpInterface) ? $loaded : null;
            }

            if (!$plugins[$owner] instanceof PluginPreferenceHelpInterface) {
                continue;
            }

            // a secret is blanked before it reaches a template, and this seam must not undo that
            $value = (Preference::isSecretName($row['name']) || $row['value'] === null) ? null : (string) $row['value'];
            $text  = $plugins[$owner]->getPreferenceHelp($row['name'], $value);
            if ($text !== null && $text !== '') {
                $helps[$row['name']] = new PreferenceHelp($text);
            }
        }

        return $helps;
    }

    /**
     * @return array<array-key, string> the values an account holds when it has changed nothing
     */
    private function systemValues(): array
    {
        $values = [];
        foreach ($this->userRepository->getPreferenceRows(User::INTERNAL_SYSTEM_USER_ID, null, false) as $row) {
            $values[$row['name']] = (string) ($row['value'] ?? '');
        }

        return $values;
    }
}
