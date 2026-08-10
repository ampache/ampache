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

namespace Ampache\Gui\Upload;

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use JsonException;
use Override;

/**
 * The upload page: a folder tree, the artist/album/license selects and the FilePond drop target.
 */
final class UploadView extends AbstractView
{
    public function __construct(
        private readonly string $webPath,
        private readonly string $ajaxFilesystemUrl,
        private readonly int $uploadMax,
    ) {}

    public function getAlbumId(): int
    {
        return (int) Core::get_request('album');
    }

    public function getAllowedTypes(): string
    {
        return str_replace('|', ', ', (string) AmpConfig::get('catalog_file_pattern'));
    }

    public function getArtistId(): int
    {
        return (int) Core::get_request('artist');
    }

    /**
     * FilePond's labels as a json literal, so a translation carrying a quote cannot break the script.
     *
     * @throws JsonException
     */
    public function getFilePondLabels(): string
    {
        return $this->toJs([
            'labelIdle' => T_('Drag & Drop your files or <span class="filepond--label-action"> Browse </span>'),
            'labelInvalidField' => T_('Field contains invalid files'),
            'labelFileWaitingForSize' => T_('Waiting for size'),
            'labelFileSizeNotAvailable' => T_('Size not available'),
            'labelFileLoading' => T_('Loading'),
            'labelFileLoadError' => T_('Error during load'),
            'labelFileProcessing' => T_('Uploading'),
            'labelFileProcessingComplete' => T_('Upload complete'),
            'labelFileProcessingAborted' => T_('Upload cancelled'),
            'labelFileProcessingError' => T_('Error during upload'),
            'labelFileProcessingRevertError' => T_('Error during revert'),
            'labelFileRemoveError' => T_('Error during remove'),
            'labelTapToCancel' => T_('tap to cancel'),
            'labelTapToRetry' => T_('tap to retry'),
            'labelTapToUndo' => T_('tap to undo'),
            'labelButtonRemoveItem' => T_('Remove'),
            'labelButtonAbortItemLoad' => T_('Abort'),
            'labelButtonRetryItemLoad' => T_('Retry'),
            'labelButtonAbortItemProcessing' => T_('Cancel'),
            'labelButtonUndoItemProcessing' => T_('Undo'),
            'labelButtonRetryItemProcessing' => T_('Retry'),
            'labelButtonProcessItem' => T_('Upload'),
            'labelMaxFileSizeExceeded' => T_('File is too large'),
            'labelMaxFileSize' => T_('Maximum file size is {filesize}'),
            'labelMaxTotalFileSizeExceeded' => T_('Maximum total size exceeded'),
            'labelMaxTotalFileSize' => T_('Maximum total file size is {filesize}'),
            'labelFileTypeNotAllowed' => T_('File of invalid type'),
            'fileValidateTypeLabelExpectedTypes' => T_('Expects {allButLastType} or {lastType}'),
            'imageValidateSizeLabelFormatError' => T_('Image type not supported'),
            'imageValidateSizeLabelImageSizeTooSmall' => T_('Image is too small'),
            'imageValidateSizeLabelImageSizeTooBig' => T_('Image is too big'),
            'imageValidateSizeLabelExpectedMinSize' => T_('Minimum size is {minWidth} × {minHeight}'),
            'imageValidateSizeLabelExpectedMaxSize' => T_('Maximum size is {maxWidth} × {maxHeight}'),
            'imageValidateSizeLabelImageResolutionTooLow' => T_('Resolution is too low'),
            'imageValidateSizeLabelImageResolutionTooHigh' => T_('Resolution is too high'),
            'imageValidateSizeLabelExpectedMinResolution' => T_('Minimum resolution is {minResolution}'),
            'imageValidateSizeLabelExpectedMaxResolution' => T_('Maximum resolution is {maxResolution}'),
        ]);
    }

    public function getFormattedUploadMax(): string
    {
        return Ui::format_bytes($this->uploadMax);
    }

    /**
     * @throws JsonException
     */
    public function getNewFolderLabelJs(): string
    {
        return $this->toJs(T_('New folder'));
    }

    /**
     * The filesystem ajax endpoint, as a json literal for the script that builds urls from it.
     *
     * @throws JsonException
     */
    public function getTreeSourceJs(): string
    {
        return $this->toJs($this->ajaxFilesystemUrl);
    }

    /**
     * @throws JsonException
     */
    public function getUploadUrlJs(): string
    {
        return $this->toJs($this->webPath . '/upload.php');
    }

    public function getWebPath(): string
    {
        return $this->webPath;
    }

    public function hasUploadMax(): bool
    {
        return $this->uploadMax > 0;
    }

    public function showLicensing(): bool
    {
        return (bool) AmpConfig::get('licensing');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('upload/upload.phtml');
    }

    /**
     * @throws JsonException
     */
    private function toJs(mixed $value): string
    {
        return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
    }
}
