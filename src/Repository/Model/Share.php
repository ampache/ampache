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
use Ampache\Module\Playback\PlaybackFactoryInterface;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Repository\ShareRepositoryInterface;

final class Share extends database_object implements ShareInterface
{
    /** @var string[] */
    public const ALLOWED_SHARE_TYPES = ['album', 'song', 'playlist', 'video'];

    /** @var null|array<string, mixed> */
    private ?array $dbData = null;

    /** @var Video|Song|Album|Playlist|null  */
    private ?database_object $object = null;

    private ShareRepositoryInterface $shareRepository;

    private ModelFactoryInterface $modelFactory;

    private PlaybackFactoryInterface $playbackFactory;

    private int $id;

    public function __construct(
        ShareRepositoryInterface $shareRepository,
        ModelFactoryInterface $modelFactory,
        PlaybackFactoryInterface $playbackFactory,
        int $id
    ) {
        $this->shareRepository = $shareRepository;
        $this->modelFactory    = $modelFactory;
        $this->playbackFactory = $playbackFactory;
        $this->id              = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPublicUrl(): string
    {
        return (string) ($this->getDbData()['public_url'] ?? '');
    }

    public function getAllowStream(): int
    {
        return (int) ($this->getDbData()['allow_stream'] ?? 0);
    }

    public function getAllowDownload(): int
    {
        return (int) ($this->getDbData()['allow_download'] ?? 0);
    }

    public function getCreationDate(): int
    {
        return (int) ($this->getDbData()['creation_date'] ?? 0);
    }

    public function getLastvisitDate(): int
    {
        return (int) ($this->getDbData()['lastvisit_date'] ?? 0);
    }

    public function getExpireDays(): int
    {
        return (int) ($this->getDbData()['expire_days'] ?? 0);
    }

    public function getMaxCounter(): int
    {
        return (int) ($this->getDbData()['max_counter'] ?? 0);
    }

    public function getCounter(): int
    {
        return (int) ($this->getDbData()['counter'] ?? 0);
    }

    public function getSecret(): string
    {
        return (string) ($this->getDbData()['secret'] ?? '');
    }

    public function getDescription(): string
    {
        return (string) ($this->getDbData()['description'] ?? '');
    }

    public function getObjectId(): int
    {
        return (int) ($this->getDbData()['object_id'] ?? 0);
    }

    public function getObjectType(): string
    {
        return (string) ($this->getDbData()['object_type'] ?? '');
    }

    public function getUserId(): int
    {
        return (int) ($this->getDbData()['user'] ?? 0);
    }

    public static function get_url(int $share_id, string $secret): string
    {
        $url = AmpConfig::get('web_path') . '/share.php?id=' . $share_id;
        if (!empty($secret)) {
            $url .= '&secret=' . $secret;
        }

        return $url;
    }

    public function getObject(): playable_item
    {
        if ($this->object === null) {
            $this->object = $this->modelFactory->mapObjectType($this->getObjectType(), $this->getObjectId());
            $this->object->format();
        }

        return $this->object;
    }

    public function getObjectUrl(): string
    {
        $object = $this->getObject();

        if (property_exists($object, 'f_link')) {
            return $object->f_link;
        } else {
            return $this->getObject()->getLinkFormatted();
        }
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
     * @param array{
     *  max_counter: int,
     *  expire: int,
     *  allow_stream: int,
     *  allow_download: int,
     *  description?: string,
     *  user_id?: int
     * } $data
     */
    public function update(array $data): int
    {
        $this->shareRepository->update(
            $this,
            (int) $data['max_counter'],
            (int) $data['expire'],
            $data['allow_stream'] ? 1 : 0,
            $data['allow_download'] ? 1 : 0,
            $data['description'] ?? $this->getDescription(),
            $data['user_id'] ?? null
        );

        return $this->getId();
    }

    public function create_fake_playlist(): Stream_Playlist
    {
        $playlist = $this->playbackFactory->createStreamPlaylist('-1');
        $medias   = [];

        switch ($this->getObjectType()) {
            case 'album':
            case 'playlist':
                $songs  = $this->getObject()->get_medias('song');
                foreach ($songs as $song) {
                    $medias[] = $song;
                }
                break;
            default:
                $medias[] = [
                    'object_type' => $this->getObjectType(),
                    'object_id' => $this->getObjectId(),
                ];
                break;
        }

        $playlist->add($medias,
            sprintf(
                '&share_id=%d&share_secret=%s',
                $this->getId(),
                $this->getSecret()
            )
        );

        return $playlist;
    }

    public function get_user_owner(): int
    {
        return $this->getUserId();
    }

    /**
     * @return array<string, int|string>
     */
    private function getDbData(): array
    {
        if ($this->dbData === null) {
            $this->dbData = $this->shareRepository->getDbData($this->getId());
        }

        return $this->dbData;
    }
}
