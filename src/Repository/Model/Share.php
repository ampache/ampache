<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=0);

namespace Ampache\Repository\Model;

use Ampache\Config\AmpConfig;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Repository\ShareRepositoryInterface;

final class Share extends database_object implements ShareInterface
{
    public const ALLOWED_SHARE_TYPES = ['album', 'song', 'playlist', 'video'];

    protected const DB_TABLENAME = 'share';

    /** @var array<string, mixed> */
    private array $data;

    /** @var Video|Song|Album|Playlist|null  */
    private ?database_object $object = null;

    private ShareRepositoryInterface $shareRepository;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ShareRepositoryInterface $shareRepository,
        ModelFactoryInterface $modelFactory,
        int $id
    ) {
        /* Get the information from the db */
        $this->data            = $this->get_info($id);
        $this->shareRepository = $shareRepository;
        $this->modelFactory    = $modelFactory;
    }

    public function getId(): int
    {
        return (int) ($this->data['id'] ?? 0);
    }

    public function getPublicUrl(): string
    {
        return (string) ($this->data['public_url'] ?? '');
    }

    public function getAllowStream(): int
    {
        return (int) $this->data['allow_stream'] ?? 0;
    }

    public function getAllowDownload(): int
    {
        return (int) $this->data['allow_download'] ?? 0;
    }

    public function getCreationDate(): int
    {
        return (int) ($this->data['creation_date'] ?? 0);
    }

    public function getLastvisitDate(): int
    {
        return (int) ($this->data['lastvisit_date'] ?? 0);
    }

    public function getExpireDays(): int
    {
        return (int) ($this->data['expire_days'] ?? 0);
    }

    public function getMaxCounter(): int
    {
        return (int) ($this->data['max_counter'] ?? 0);
    }

    public function getCounter(): int
    {
        return (int) ($this->data['counter'] ?? 0);
    }

    public function getSecret(): string
    {
        return (string) ($this->data['secret'] ?? '');
    }

    public function getDescription(): string
    {
        return (string) ($this->data['description'] ?? '');
    }

    public function getObjectId(): int
    {
        return (int) ($this->data['object_id'] ?? 0);
    }

    public function getObjectType(): string
    {
        return (string) ($this->data['object_type'] ?? '');
    }

    public function getUserId(): int
    {
        return (int) ($this->data['user'] ?? 0);
    }

    public static function get_url(int $share_id, string $secret): string
    {
        $url = AmpConfig::get('web_path') . '/share.php?id=' . $share_id;
        if (!empty($secret)) {
            $url .= '&secret=' . $secret;
        }

        return $url;
    }

    private function getObject()
    {
        if ($this->object === null) {
            $this->object = $this->modelFactory->mapObjectType($this->getObjectType(), $this->getObjectId());
            $this->object->format();
        }

        return $this->object;
    }

    public function getObjectUrl(): string
    {
        return $this->getObject()->f_link;
    }

    public function getObjectName(): string
    {
        return $this->getObject()->get_fullname();
    }

    public function getUserName(): string
    {
        $user = new User($this->getUserId());
        $user->format();

        return $user->f_name;
    }

    public function getLastVisitDateFormatted(): string
    {
        return $this->getLastvisitDate() > 0 ? get_datetime($this->getLastvisitDate()) : '';
    }

    public function getCreationDateFormatted(): string
    {
        return get_datetime($this->getCreationDate());
    }

    /**
     * @param array{max_counter: int, expire: int, allow_stream: int, allow_download: int, description?: string} $data
     */
    public function update(array $data, User $user): int
    {
        $this->data['max_counter']    = (int) $data['max_counter'];
        $this->data['expire_days']    = (int) $data['expire'];
        $this->data['allow_stream']   = (int) ($data['allow_stream'] == 1);
        $this->data['allow_download'] = (int) ($data['allow_download'] == 1);
        $this->data['description']    = $data['description'] ?? $this->getDescription();

        $userId = null;
        if (!$user->has_access('75')) {
            $userId = $user->getId();
        }

        $this->shareRepository->update(
            $this,
            $this->getMaxCounter(),
            $this->getExpireDays(),
            $this->getAllowStream() ? 1 : 0,
            $this->getAllowDownload() ? 1 : 0,
            $this->getDescription(),
            $userId
        );

        return $this->getId();
    }

    public function is_valid(string $secret, string $action): bool
    {
        if (!$this->getId()) {
            debug_event(self::class, 'Access Denied: Invalid share.', 3);

            return false;
        }

        if (!AmpConfig::get('share')) {
            debug_event(self::class, 'Access Denied: share feature disabled.', 3);

            return false;
        }

        if ($this->getExpireDays() > 0 && ($this->getCreationDate() + ($this->getExpireDays() * 86400)) < time()) {
            debug_event(self::class, 'Access Denied: share expired.', 3);

            return false;
        }

        if ($this->getMaxCounter() > 0 && $this->getCounter() >= $this->getMaxCounter()) {
            debug_event(self::class, 'Access Denied: max counter reached.', 3);

            return false;
        }

        if (!empty($this->getSecret()) && $secret != $this->getSecret()) {
            debug_event(self::class, 'Access Denied: secret requires to access share ' . $this->getId() . '.', 3);

            return false;
        }

        if ($action == 'download' && (!AmpConfig::get('download') || !$this->getAllowDownload())) {
            debug_event(self::class, 'Access Denied: download unauthorized.', 3);

            return false;
        }

        if ($action == 'stream' && !$this->getAllowStream()) {
            debug_event(self::class, 'Access Denied: stream unauthorized.', 3);

            return false;
        }

        return true;
    }

    public function create_fake_playlist(): Stream_Playlist
    {
        $playlist = new Stream_Playlist(-1);
        $medias   = array();

        switch ($this->getObjectType()) {
            case 'album':
            case 'playlist':
                $songs  = $this->getObject()->get_medias('song');
                foreach ($songs as $song) {
                    $medias[] = $song;
                }
                break;
            default:
                $medias[] = array(
                    'object_type' => $this->getObjectType(),
                    'object_id' => $this->getObjectId(),
                );
                break;
        }

        $playlist->add($medias, '&share_id=' . $this->getId() . '&share_secret=' . $this->getSecret());

        return $playlist;
    }

    public function get_user_owner(): int
    {
        return $this->getUserId();
    }
}
