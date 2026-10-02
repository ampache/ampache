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
use Ampache\Repository\Model\UpdateInfoEnum;
use Ampache\Repository\Model\User;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Override;

/**
 * Builds the JSON of a configuration, stamped with the version and schema that produced it
 */
final readonly class PreferenceExporter implements PreferenceExporterInterface
{
    public const int FORMAT_VERSION = 1;

    public function __construct(
        private PreferenceCollector $collector,
        private ConfigContainerInterface $configContainer,
        private UpdateInfoRepositoryInterface $updateInfoRepository,
    ) {}

    #[Override]
    public function export(PreferenceSubject $subject, User $operator): array
    {
        $values  = [];
        $secrets = [];
        foreach ($this->collector->collect($subject, $operator) as $items) {
            foreach ($items as $item) {
                // a secret is write-only everywhere else; an export is not the place to finally reveal it
                if ($item->isSecret) {
                    $secrets[] = $item->name;

                    continue;
                }

                $values[$item->name] = $item->value;
            }
        }

        ksort($values);
        sort($secrets);

        return [
            'format' => 'ampache-preferences',
            'format_version' => self::FORMAT_VERSION,
            'exported_at' => gmdate('c'),
            'ampache_version' => (string) $this->configContainer->get('version'),
            'schema_version' => $this->updateInfoRepository->getValueByKey(UpdateInfoEnum::DB_VERSION),
            'site' => [
                'title' => (string) $this->configContainer->get('site_title'),
                'url' => $this->configContainer->getWebPath(),
            ],
            'subject' => [
                'kind' => $this->kind($subject),
                'user_id' => $subject->userId,
                'username' => $subject->isServer ? null : $subject->label,
            ],
            'exported_by' => [
                'user_id' => $operator->getId(),
                'username' => (string) $operator->username,
            ],
            'omitted_secrets' => $secrets,
            'preferences' => $values,
        ];
    }

    #[Override]
    public function fileName(PreferenceSubject $subject): string
    {
        return sprintf(
            'ampache-preferences_%s_%d_v%s_%s.json',
            $this->slug($subject->isServer ? 'server' : $subject->label),
            $subject->userId,
            $this->slug((string) $this->configContainer->get('version')),
            gmdate('Y-m-d_H-i-s')
        );
    }

    private function kind(PreferenceSubject $subject): string
    {
        return match (true) {
            $subject->isServer => 'server',
            $subject->isSelf => 'own-account',
            default => 'account',
        };
    }

    /**
     * Anything a file system or a Content-Disposition header could choke on becomes a dash.
     */
    private function slug(string $value): string
    {
        $slug = preg_replace('/[^A-Za-z0-9._-]+/', '-', $value) ?? '';

        return trim($slug, '-') ?: 'unnamed';
    }
}
