<?php
// +----------------------------------------------------------------------+
// | PHP version 5                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2009 James Heinrich, Allan Hansen                 |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2 of the GPL license,         |
// | that is bundled with this package in the file license.txt and is     |
// | available through the world-wide-web at the following url:           |
// | http://www.gnu.org/copyleft/gpl.html                                 |
// +----------------------------------------------------------------------+
// | getID3() - http://getid3.sourceforge.net or http://www.getid3.org    |
// +----------------------------------------------------------------------+
// | Authors: James Heinrich <infoØgetid3*org>                            |
// |          Allan Hansen <ahØartemis*dk>                                |
// +----------------------------------------------------------------------+
// | module.lib.data-hash.php                                             |
// | getID3() library file.                                               |
// | dependencies: NONE.                                                  |
// +----------------------------------------------------------------------+
//
// $Id: module.lib.data_hash.php,v 1.5 2006/12/03 19:28:18 ah Exp $



class getid3_lib_data_hash
{

    private $getid3;


    // constructer - calculate md5/sha1 data
    public function __construct(getID3 $getid3, $algorithm) {

        $this->getid3 = $getid3;

        // Check algorithm
        if (!preg_match('/^(md5|sha1)$/', $algorithm)) {
            throw new getid3_exception('Unsupported algorithm, "'.$algorithm.'", in GetHashdata()');
        }


        //// Handle ogg vorbis files

        if ((@$getid3->info['fileformat'] == 'ogg') && (@$getid3->info['audio']['dataformat'] == 'vorbis')) {

            // We cannot get an identical md5_data value for Ogg files where the comments
            // span more than 1 Ogg page (compared to the same audio data with smaller
            // comments) using the normal getID3() method of MD5'ing the data between the
            // end of the comments and the end of the file (minus any trailing tags),
            // because the page sequence numbers of the pages that the audio data is on
            // do not match. Under normal circumstances, where comments are smaller than
            // the nominal 4-8kB page size, then this is not a problem, but if there are
            // very large comments, the only way around it is to strip off the comment
            // tags with vorbiscomment and MD5 that file.
            // This procedure must be applied to ALL Ogg files, not just the ones with
            // comments larger than 1 page, because the below method simply MD5's the
            // whole file with the comments stripped, not just the portion after the
            // comments block (which is the standard getID3() method.

            // The above-mentioned problem of comments spanning multiple pages and changing
            // page sequence numbers likely happens for OggSpeex and OggFLAC as well, but
            // currently vorbiscomment only works on OggVorbis files.

            if (preg_match('#(1|ON)#i', ini_get('safe_mode'))) {
                throw new getid3_exception('PHP running in Safe Mode - cannot make system call to vorbiscomment[.exe]  needed for '.$algorithm.'_data.');
            }

            if (!preg_match('/^Vorbiscomment /', `vorbiscomment --version 2>&1`)) {
                throw new getid3_exception('vorbiscomment[.exe] binary not found in path. UNIX: typically /usr/bin. Windows: typically c:\windows\system32.');
            }

            // Prevent user from aborting script
            $old_abort = ignore_user_abort(true);

            // Create empty file
            $empty = tempnam((function_exists('sys_get_temp_dir') ? sys_get_temp_dir() : ini_get('upload_tmp_dir')), 'getID3');
            touch($empty);

            // Use vorbiscomment to make temp file without comments
            $temp = tempnam((function_exists('sys_get_temp_dir') ? sys_get_temp_dir() : ini_get('upload_tmp_dir')), 'getID3');

            $command_line = 'vorbiscomment -w -c '.escapeshellarg($empty).' '.escapeshellarg(realpath($getid3->filename)).' '.escapeshellarg($temp).' 2>&1';

            // Error from vorbiscomment
            if ($vorbis_comment_error = `$command_line`) {
                throw new getid3_exception('System call to vorbiscomment[.exe] failed.');
            }

            // Get hash of newly created file
            $hash_function = $algorithm . '_file';
            $getid3->info[$algorithm.'_data'] = $hash_function($temp);

            // Clean up
            unlink($empty);
            unlink($temp);

            // Reset abort setting
            ignore_user_abort($old_abort);

            // Return success
            return true;
        }

        //// Handle other file formats

        // Get hash from part of file
        if (@$getid3->info['avdataoffset'] || (@$getid3->info['avdataend']  &&  @$getid3->info['avdataend'] < $getid3->info['filesize'])) {

            if (preg_match('#(1|ON)#i', ini_get('safe_mode'))) {
                $getid3->warning('PHP running in Safe Mode - backtick operator not available, using slower non-system-call '.$algorithm.' algorithm.');
                $hash_function = 'hash_file_partial_safe_mode';
            }
            else {
                $hash_function = 'hash_file_partial';
            }

            $getid3->info[$algorithm.'_data'] = $this->$hash_function($getid3->filename, $getid3->info['avdataoffset'], $getid3->info['avdataend'], $algorithm);
        }

        // Get hash from whole file - use built-in md5_file() and sha1_file()
        else {
            $hash_function = $algorithm . '_file';
            $getid3->info[$algorithm.'_data'] = $hash_function($getid3->filename);
        }
    }



    // Return md5/sha1sum for a file from starting position to absolute end position
    // Using windows system call
    private function hash_file_partial($file, $offset, $end, $algorithm) {

        // It seems that sha1sum.exe for Windows only works on physical files, does not accept piped data
        // Fall back to create-temp-file method:
        if ($algorithm == 'sha1'  &&  strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            return $this->hash_file_partial_safe_mode($file, $offset, $end, $algorithm);
        }

        // Check for presence of binaries and revert to safe mode if not found
        if (!`head --version`) {
            return $this->hash_file_partial_safe_mode($file, $offset, $end, $algorithm);
        }

        if (!`tail --version`) {
            return $this->hash_file_partial_safe_mode($file, $offset, $end, $algorithm);
        }

        if (!`${algorithm}sum --version`) {
            return $this->hash_file_partial_safe_mode($file, $offset, $end, $algorithm);
        }

        $size = $end - $offset;
        $command_line  = 'head -c'.$end.' '.escapeshellarg(realpath($file)).' | tail -c'.$size.' | '.$algorithm.'sum';
        return substr(`$command_line`, 0, $algorithm == 'md5' ? 32 : 40);
    }



    // Return md5/sha1sum for a file from starting position to absolute end position
    // Using slow safe_mode temp file
    private function hash_file_partial_safe_mode($file, $offset, $end, $algorithm) {

        // Attempt to create a temporary file in the system temp directory - invalid dirname should force to system temp dir
        if (($data_filename = tempnam((function_exists('sys_get_temp_dir') ? sys_get_temp_dir() : ini_get('upload_tmp_dir')), 'getID3')) === false) {
            throw new getid3_exception('Unable to create temporary file.');
        }

        // Init
        $result = false;

        // Copy parts of file
        if ($fp = @fopen($file, 'rb')) {

            if ($fp_data = @fopen($data_filename, 'wb')) {

                fseek($fp, $offset, SEEK_SET);
                $bytes_left_to_write = $end - $offset;
                while (($bytes_left_to_write > 0) && ($buffer = fread($fp, getid3::FREAD_BUFFER_SIZE))) {
                    $bytes_written = fwrite($fp_data, $buffer, $bytes_left_to_write);
                    $bytes_left_to_write -= $bytes_written;
                }
                fclose($fp_data);
                $hash_function = $algorithm . '_file';
                $result = $hash_function($data_filename);

            }
            fclose($fp);
        }
        unlink($data_filename);
        return $result;
    }

}

?>