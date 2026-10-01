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
use Ampache\Module\System\Preference;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;

/**
 * Builds the `PreferenceItem` list a preferences screen renders, for one subject.
 */
final readonly class PreferenceCollector
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PreferenceHelpCatalog $helpCatalog,
        private PluginPreferenceHelp $pluginHelp,
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

        $collected = [];
        foreach ($rows as $row) {
            $collected[$row['category']][] = $this->item(
                $row,
                $systemValues[$row['name']] ?? null,
                $this->choiceProvider->find($row['name'], $subject, $held),
                !$demoMode && $operator->access >= $row['level'],
                $this->prerequisites->find($row['name'], $held),
            );
        }

        return $collected;
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
     * Turns one `UserRepository::getPreferenceRows()` row into the item a screen renders
     *
     * @param array{name: string, description: string, category: string, subcategory: ?string, type: string, level: int, value: mixed} $row
     * @param ?string $systemValue the `user = -1` value, null when the subject is the system itself
     * @param ?array<array-key, string> $choices from `PreferenceChoiceProvider`
     */
    private function item(array $row, ?string $systemValue, ?array $choices, bool $editable, ?string $warning): PreferenceItem
    {
        $name     = $row['name'];
        $value    = (string) ($row['value'] ?? '');
        $isSecret = Preference::isSecretName($name);

        return new PreferenceItem(
            name: $name,
            description: $row['description'],
            type: PreferenceType::fromDatabase($row['type']),
            subcategory: ($row['subcategory'] === null || $row['subcategory'] === '') ? null : $row['subcategory'],
            level: $row['level'],
            // a secret is write-only: it must not reach a template that could echo it
            value: $isSecret ? '' : $value,
            shippedDefault: Preference::DEFAULTS[$name][0] ?? null,
            systemValue: $isSecret ? null : $systemValue,
            choices: $choices,
            editable: $editable,
            isSecret: $isSecret,
            secretIsSet: $isSecret && $value !== '',
            // a plugin knows its own settings better than the shipped catalogue does
            help: $this->pluginHelp->find($row) ?? $this->helpCatalog->find($name),
            warning: $warning,
        );
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
