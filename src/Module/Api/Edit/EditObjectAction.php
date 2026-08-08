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

namespace Ampache\Module\Api\Edit;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\ShareRepositoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

final class EditObjectAction extends AbstractEditAction
{
    public const string REQUEST_KEY = 'edit_object';

    protected LoggerInterface $logger;
    private LabelRepositoryInterface $labelRepository;
    private ResponseFactoryInterface $responseFactory;
    private StreamFactoryInterface $streamFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        LabelRepositoryInterface $labelRepository,
        LibraryItemLoaderInterface $libraryItemLoader,
        LoggerInterface $logger,
        ResponseFactoryInterface $responseFactory,
        ShareRepositoryInterface $shareRepository,
        BrowseFactoryInterface $browseFactory,
        StreamFactoryInterface $streamFactory,
    ) {
        parent::__construct($configContainer, $libraryItemLoader, $logger, $shareRepository, $browseFactory);
        $this->labelRepository = $labelRepository;
        $this->logger          = $logger;
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
    }

    protected function handle(
        ServerRequestInterface $request,
        GuiGatekeeperInterface $gatekeeper,
        string $object_type,
        library_item|Share $libitem,
        int $object_id,
        ?Browse $browse = null,
    ): ?ResponseInterface {
        $data = (array) $request->getParsedBody();
        if (!isset($data['id'])) {
            return null;
        }

        $user   = $gatekeeper->getUser();
        $userId = ($user)
            ? $user->getId()
            : null;

        $data = $this->scrub($data);
        $this->logger->debug(
            'edit_object: {' . $object_type . '} {' . $object_id . '}',
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );
        if (
            $libitem->get_user_owner() === $userId
            && AmpConfig::get('upload_allow_edit')
            && !$gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
        ) {
            // an uploader editing their own item may not re-file it, so the ownership and parent keys are dropped
            // TODO: improve this uniqueness check
            unset($data['user'], $data['artist'], $data['artist_name'], $data['album'], $data['album_name']);
            if (isset($data['edit_tags'])) {
                $data['edit_tags'] = Tag::clean_to_existing($data['edit_tags']);
            }
            if (isset($data['edit_labels'])) {
                $data['edit_labels'] = $this->clean_to_existing($data['edit_labels']);
            }
            // Check mbid and *_mbid match as it is used as identifier
            if (isset($data['mbid']) && isset($libitem->mbid)) {
                $data['mbid'] = $libitem->mbid;
            }
            if (isset($data['mbid_group']) && isset($libitem->mbid_group)) {
                $data['mbid_group'] = $libitem->mbid_group;
            }
        }

        /**
         * @todo updating must be separated by item type - this is ugly as hell
         */
        if ($libitem instanceof Share && $user !== null) {
            $libitem->update($data, $user);
        } elseif ($libitem instanceof Podcast) {
            $feedUrl = $data['feed'] ?? '';

            if (filter_var($feedUrl, FILTER_VALIDATE_URL)) {
                $libitem->setTitle($data['title'] ?? $libitem->getTitle())
                    ->setFeedUrl($feedUrl)
                    ->setWebsite(filter_var(urldecode($data['website']), FILTER_VALIDATE_URL) ?: $libitem->getWebsite())
                    ->setDescription($data['description'] ?? $libitem->getDescription())
                    ->setLanguage($data['language'] ?? $libitem->getLanguage())
                    ->setGenerator($data['generator'] ?? $libitem->getGenerator())
                    ->setCopyright($data['copyright'] ?? $libitem->getCopyright())
                    ->save();
            }
        } else {
            $libitem->update($data);
        }

        // the dialog reads the id back out of the response body to know which row it has to reload afterwards
        return $this->createOutputResponse(
            $this->readString($data, 'xoutput') ?: $this->readString($request->getQueryParams(), 'xoutput'),
            xoutput_from_array(['id' => $object_id])
        );
    }

    /**
     * clean_to_existing
     * Clean label list to existing label list only
     * @param string|string[] $labels
     * @return string[]|string
     */
    private function clean_to_existing(array|string $labels): array|string
    {
        $array = (is_array($labels)) ? $labels : preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', $labels);
        $ret   = [];
        if ($array !== false) {
            foreach ($array as $label) {
                $label = trim((string) $label);
                if (!empty($label)) {
                    if ($this->labelRepository->lookup($label) > 0) {
                        $ret[] = $label;
                    }
                }
            }
        }

        return (is_array($labels)
            ? $ret
            : implode(",", $ret));
    }

    /**
     * Builds the ajax response, mirroring the header set `xoutput_headers()` writes for the same payload.
     */
    private function createOutputResponse(string $format, string $body): ResponseInterface
    {
        $charset  = AmpConfig::get('site_charset', 'UTF-8');
        $response = $this->responseFactory->createResponse()
            ->withHeader('Expires', 'Tuesday, 27 Mar 1984 05:00:00 GMT')
            ->withHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->withHeader('Pragma', 'no-cache');

        $response = ($format === '' || $format === 'xml')
            ? $response
                ->withHeader('Content-Type', 'text/xml; charset=' . $charset)
                ->withHeader('Content-Disposition', 'attachment; filename=ajax.xml')
            : $response->withHeader('Content-Type', 'application/json; charset=' . $charset);

        return $response->withBody($this->streamFactory->createStream($body));
    }

    /**
     * @param array<array-key, mixed> $source
     */
    private function readString(array $source, string $key): string
    {
        $value = $source[$key] ?? '';

        return (is_string($value)) ? $value : '';
    }

    /**
     * Recursively strips markup out of a posted value tree, leaving what the models are willing to store.
     *
     * @param array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    private function scrub(array $data): array
    {
        foreach ($data as $key => $value) {
            $data[$key] = (is_array($value))
                ? $this->scrub($value)
                : unhtmlentities((string) scrub_in((string) $value));
        }

        return $data;
    }
}
