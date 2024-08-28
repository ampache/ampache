<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Util\FileSystem;

use Ahc\Cli\IO\Interactor;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;

final class FileNameConverter implements FileNameConverterInterface
{
    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer
    ) {
        $this->configContainer = $configContainer;
    }

    public function convert(
        Interactor $interactor,
        string $source_encoding,
        bool $force = false
    ): void {
        $sql        = "SELECT `id` FROM `catalog` WHERE `catalog_type`='local'";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $catalog = Catalog::create_from_id($row['id']);
            if ($catalog === null) {
                break;
            }
            /* HINT: %1 Catalog Name, %2 Catalog Path */
            $interactor->info(
                sprintf(T_('Checking %1$s (%2$s)'), $catalog->name, $catalog->get_path()),
                true
            );
            $this->charset_directory_correct($interactor, $catalog->get_path(), $force);
        }

        $interactor->ok(
            T_('Finished checking file names for valid characters'),
            true
        );
    }

    /**
     * This function calls its self recursively
     * and corrects all of the non-matching filenames
     * it looks at the i_am_crazy var and if not set prompts for change
     */
    private function charset_directory_correct(
        Interactor $interactor,
        string $path,
        bool $force
    ): bool {
        /* @var string $source_encoding */
        $source_encoding = iconv_get_encoding('output_encoding');

        // Correctly detect the slash we need to use here
        if (strstr($path, "/")) {
            $slash_type = '/';
        } else {
            $slash_type = '\\';
        }

        /* Open up the directory */
        $handle = opendir($path);

        if (!is_resource($handle)) {
            $interactor->error(
                sprintf(T_('There was an error trying to open "%s"'), $path),
                true
            );

            return false;
        }

        if (!chdir($path)) {
            $interactor->error(
                sprintf(T_('There was an error trying to chdir to "%s"'), $path),
                true
            );

            return false;
        }

        $siteCharset = $this->configContainer->get('site_charset');

        while (false !== ($file = readdir($handle))) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            $full_file = $path . $slash_type . $file;

            if (is_dir($full_file)) {
                $this->charset_directory_correct($interactor, $full_file, $force);
                continue;
            }

            $verify_filename = iconv($siteCharset, $siteCharset . '//IGNORE', $full_file);
            if (!$verify_filename) {
                continue;
            }

            if (strcmp($full_file, $verify_filename) != 0) {
                $translated_filename = iconv($source_encoding, $siteCharset . '//TRANSLIT', $full_file);
                if (!$translated_filename) {
                    continue;
                }

                // Make sure the extension stayed the same
                if (substr($translated_filename, strlen($translated_filename) - 3, 3) != substr($full_file, strlen($full_file) - 3, 3)) {
                    $interactor->warn(
                        T_('Translation failure, stripping non-valid characters'),
                        true
                    );

                    $translated_filename = iconv($source_encoding, $siteCharset . '//IGNORE', $full_file);
                }

                $interactor->info(
                    sprintf(T_('Attempting to Transcode to "%s"'), $siteCharset),
                    true
                );
                $interactor->info(
                    '--------------------------------------------------------------------------------------------',
                    true
                );
                $interactor->info(
                    sprintf(T_('OLD: "%s" has invalid chars'), $full_file),
                    true
                );
                $interactor->info(
                    sprintf(T_('NEW: %s'), $translated_filename),
                    true
                );
                $interactor->info(
                    '--------------------------------------------------------------------------------------------',
                    true
                );
                if ($force === false) {
                    $input = $interactor->confirm(
                        T_('Rename file (y/n)'),
                        'n'
                    );
                    if ($input === true) {
                        $this->charset_rename_file($interactor, $full_file, $translated_filename);
                    } else {
                        $interactor->eol();
                        $interactor->warn(
                            T_('Not renaming...'),
                            true
                        );
                    }
                } else {
                    $this->charset_rename_file($interactor, $full_file, $translated_filename);
                }
            }
        }

        return true;
    }

    /**
     * This just takes a source / dest and does the renaming
     *
     * @param Interactor $interactor
     * @param string $full_file
     * @param string $translated_filename
     * @return bool
     */
    private function charset_rename_file(
        Interactor $interactor,
        string $full_file,
        string $translated_filename
    ): bool {

        // First break out the base directory name and make sure it exists
        // in case our crap char is in the directory
        $directory = dirname($translated_filename);
        $data      = preg_split("/[\/\\\]/", $directory);
        $path      = '';

        foreach ($data as $dir) {
            $dir = $this->charset_clean_name($dir);
            $path .= '/' . $dir;

            if (!is_dir($path)) {
                $interactor->info(
                    printf(T_('Making directory: %s'), $path),
                    true
                );
                $results_mkdir = mkdir($path);
                if (!$results_mkdir) {
                    /* HINT: filename (File path) */
                    $interactor->error(
                        sprintf(T_('There was an error trying to create "%s": Move failed, stopping'), $path),
                        true
                    );

                    return false;
                }
            } // if the dir doesn't exist
        } // end foreach

        // Now to copy the file
        $results_copy = copy($full_file, $translated_filename);

        if (!$results_copy) {
            $interactor->error(
                T_('File copy failed. Not deleting source file'),
                true
            );

            return false;
        }

        $old_sum = Core::get_filesize($full_file);
        $new_sum = Core::get_filesize($translated_filename);

        if ($old_sum != $new_sum || $new_sum == 0) {
            $interactor->error(
                sprintf(T_('Size comparison failed. Not deleting "%s"'), $full_file),
                true
            );

            return false;
        }

        if (!unlink($full_file)) {
            $interactor->error(
                sprintf(T_('There was an error trying to delete "%s"'), $full_file),
                true
            );

            return false;
        }

        $interactor->ok(
            T_('File moved...'),
            true
        );
        $interactor->eol();

        return true;
    }

    /**
     * We have to have some special rules here
     * This is run on every individual element of the search
     * Before it is put together, this removes / and \ and also
     * once I figure it out, it'll clean other stuff
     * @param string $string
     * @return string|string[]|null
     */
    private function charset_clean_name(string $string)
    {
        /* First remove any / or \ chars */
        $string_1 = preg_replace('/[\/\\\]/', '-', $string);
        $string_2 = str_replace(':', ' ', $string_1);

        return preg_replace('/[\!\:\*]/', '_', $string_2);
    }
}
