<?php
// +----------------------------------------------------------------------+
// | PHP version 5                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006 James Heinrich, Allan Hansen                 |
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
// | module.archive.zip.php                                               |
// | Module for analyzing pkZip files                                     |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.archive.zip.php,v 1.4 2006/11/02 10:48:00 ah Exp $



class getid3_zip extends getid3_handler
{

    public function Analyze() {

        $getid3 = $this->getid3;
        
        $getid3->info['zip'] = array ();
        $info_zip = &$getid3->info['zip'];
        
        $getid3->info['fileformat'] = 'zip';
        
        $info_zip['encoding'] = 'ISO-8859-1';
        $info_zip['files']    = array ();
        $info_zip['compressed_size'] = $info_zip['uncompressed_size'] = $info_zip['entries_count'] = 0;

        $eocd_search_data    = '';
        $eocd_search_counter = 0;
        while ($eocd_search_counter++ < 512) {

            fseek($getid3->fp, -128 * $eocd_search_counter, SEEK_END);
            $eocd_search_data = fread($getid3->fp, 128).$eocd_search_data;

            if (strstr($eocd_search_data, 'PK'."\x05\x06")) {

                $eocd_position = strpos($eocd_search_data, 'PK'."\x05\x06");
                fseek($getid3->fp, (-128 * $eocd_search_counter) + $eocd_position, SEEK_END);
                $info_zip['end_central_directory'] = $this->ZIPparseEndOfCentralDirectory();

                fseek($getid3->fp, $info_zip['end_central_directory']['directory_offset'], SEEK_SET);
                $info_zip['entries_count'] = 0;
                while ($central_directoryentry = $this->ZIPparseCentralDirectory($getid3->fp)) {
                    $info_zip['central_directory'][] = $central_directoryentry;
                    $info_zip['entries_count']++;
                    $info_zip['compressed_size']   += $central_directoryentry['compressed_size'];
                    $info_zip['uncompressed_size'] += $central_directoryentry['uncompressed_size'];

                    if ($central_directoryentry['uncompressed_size'] > 0) {
                        $info_zip['files'] = getid3_zip::array_merge_clobber($info_zip['files'], getid3_zip::CreateDeepArray($central_directoryentry['filename'], '/', $central_directoryentry['uncompressed_size']));
                    }
                }

                if ($info_zip['entries_count'] == 0) {
                    throw new getid3_exception('No Central Directory entries found (truncated file?)');
                }

                if (!empty($info_zip['end_central_directory']['comment'])) {
                    $info_zip['comments']['comment'][] = $info_zip['end_central_directory']['comment'];
                }

                if (isset($info_zip['central_directory'][0]['compression_method'])) {
                    $info_zip['compression_method'] = $info_zip['central_directory'][0]['compression_method'];
                }
                if (isset($info_zip['central_directory'][0]['flags']['compression_speed'])) {
                    $info_zip['compression_speed']  = $info_zip['central_directory'][0]['flags']['compression_speed'];
                }
                if (isset($info_zip['compression_method']) && ($info_zip['compression_method'] == 'store') && !isset($info_zip['compression_speed'])) {
                    $info_zip['compression_speed']  = 'store';
                }

                return true;
            }
        }

        if ($this->getZIPentriesFilepointer()) {

            // central directory couldn't be found and/or parsed
            // scan through actual file data entries, recover as much as possible from probable trucated file
            if (@$info_zip['compressed_size'] > ($getid3->info['filesize'] - 46 - 22)) {
                throw new getid3_exception('Warning: Truncated file! - Total compressed file sizes ('.$info_zip['compressed_size'].' bytes) is greater than filesize minus Central Directory and End Of Central Directory structures ('.($getid3->info['filesize'] - 46 - 22).' bytes)');
            }
            throw new getid3_exception('Cannot find End Of Central Directory - returned list of files in [zip][entries] array may not be complete');
        }
        
        //throw new getid3_exception('Cannot find End Of Central Directory (truncated file?)');
    }



    private function getZIPHeaderFilepointerTopDown() {
        
        // shortcut
        $getid3 = $this->getid3;
        
        $getid3->info['fileformat'] = 'zip';
        
        $getid3->info['zip'] = array ();
        $info_zip['compressed_size'] = $info_zip['uncompressed_size'] = $info_zip['entries_count'] = 0;
        
        rewind($getid3->fp);
        while ($fileentry = $this->ZIPparseLocalFileHeader()) {
            $info_zip['entries'][] = $fileentry;
            $info_zip['entries_count']++;
        }
        if ($info_zip['entries_count'] == 0) {
            throw new getid3_exception('No Local File Header entries found');
        }

        $info_zip['entries_count']  = 0;
        while ($central_directoryentry = $this->ZIPparseCentralDirectory($getid3->fp)) {
            $info_zip['central_directory'][] = $central_directoryentry;
            $info_zip['entries_count']++;
            $info_zip['compressed_size']   += $central_directoryentry['compressed_size'];
            $info_zip['uncompressed_size'] += $central_directoryentry['uncompressed_size'];
        }
        if ($info_zip['entries_count'] == 0) {
            throw new getid3_exception('No Central Directory entries found (truncated file?)');
        }

        if ($eocd = $this->ZIPparseEndOfCentralDirectory()) {
            $info_zip['end_central_directory'] = $eocd;
        } else {
            throw new getid3_exception('No End Of Central Directory entry found (truncated file?)');
        }

        if (!@$info_zip['end_central_directory']['comment']) {
            $info_zip['comments']['comment'][] = $info_zip['end_central_directory']['comment'];
        }

        return true;
    }



    private function getZIPentriesFilepointer() {
        
        // shortcut
        $getid3 = $this->getid3;
        
        $getid3->info['zip'] = array ();
        $info_zip['compressed_size'] = $info_zip['uncompressed_size'] = $info_zip['entries_count'] = 0;

        rewind($getid3->fp);
        while ($fileentry = $this->ZIPparseLocalFileHeader($getid3->fp)) {
            $info_zip['entries'][] = $fileentry;
            $info_zip['entries_count']++;
            $info_zip['compressed_size']   += $fileentry['compressed_size'];
            $info_zip['uncompressed_size'] += $fileentry['uncompressed_size'];
        }
        if ($info_zip['entries_count'] == 0) {
            throw new getid3_exception('No Local File Header entries found');
        }

        return true;
    }



    private function ZIPparseLocalFileHeader() {
        
        // shortcut
        $getid3 = $this->getid3;

        $local_file_header['offset'] = ftell($getid3->fp);
        
        $zip_local_file_header = fread($getid3->fp, 30);

        $local_file_header['raw']['signature'] = getid3_lib::LittleEndian2Int(substr($zip_local_file_header,  0, 4));
        
        // Invalid Local File Header Signature
        if ($local_file_header['raw']['signature'] != 0x04034B50) {
            fseek($getid3->fp, $local_file_header['offset'], SEEK_SET); // seek back to where filepointer originally was so it can be handled properly
            return false;
        }
        
        getid3_lib::ReadSequence('LittleEndian2Int', $local_file_header['raw'], $zip_local_file_header,  4, 
            array (
                'extract_version'    => 2, 
                'general_flags'      => 2, 
                'compression_method' => 2, 
                'last_mod_file_time' => 2, 
                'last_mod_file_date' => 2, 
                'crc_32'             => 2, 
                'compressed_size'    => 2, 
                'uncompressed_size'  => 2, 
                'filename_length'    => 2, 
                'extra_field_length' => 2
            )
        );        

        $local_file_header['extract_version']         = sprintf('%1.1f', $local_file_header['raw']['extract_version'] / 10);
        $local_file_header['host_os']                 = $this->ZIPversionOSLookup(($local_file_header['raw']['extract_version'] & 0xFF00) >> 8);
        $local_file_header['compression_method']      = $this->ZIPcompressionMethodLookup($local_file_header['raw']['compression_method']);
        $local_file_header['compressed_size']         = $local_file_header['raw']['compressed_size'];
        $local_file_header['uncompressed_size']       = $local_file_header['raw']['uncompressed_size'];
        $local_file_header['flags']                   = $this->ZIPparseGeneralPurposeFlags($local_file_header['raw']['general_flags'], $local_file_header['raw']['compression_method']);
        $local_file_header['last_modified_timestamp'] = $this->DOStime2UNIXtime($local_file_header['raw']['last_mod_file_date'], $local_file_header['raw']['last_mod_file_time']);

        $filename_extra_field_length = $local_file_header['raw']['filename_length'] + $local_file_header['raw']['extra_field_length'];
        if ($filename_extra_field_length > 0) {
            $zip_local_file_header .= fread($getid3->fp, $filename_extra_field_length);

            if ($local_file_header['raw']['filename_length'] > 0) {
                $local_file_header['filename'] = substr($zip_local_file_header, 30, $local_file_header['raw']['filename_length']);
            }
            if ($local_file_header['raw']['extra_field_length'] > 0) {
                $local_file_header['raw']['extra_field_data'] = substr($zip_local_file_header, 30 + $local_file_header['raw']['filename_length'], $local_file_header['raw']['extra_field_length']);
            }
        }

        $local_file_header['data_offset'] = ftell($getid3->fp);
        fseek($getid3->fp, $local_file_header['raw']['compressed_size'], SEEK_CUR);

        if ($local_file_header['flags']['data_descriptor_used']) {
            $data_descriptor = fread($getid3->fp, 12);
            
            getid3_lib::ReadSequence('LittleEndian2Int', $local_file_header['data_descriptor'], $data_descriptor, 0, 
                array (
                'crc_32'            => 4,
                'compressed_size'   => 4,
                'uncompressed_size' => 4 
                )
            );
        }

        return $local_file_header;
    }



    private function ZIPparseCentralDirectory() {
        
        // shortcut
        $getid3 = $this->getid3;

        $central_directory['offset'] = ftell($getid3->fp);

        $zip_central_directory = fread($getid3->fp, 46);

        $central_directory['raw']['signature']  = getid3_lib::LittleEndian2Int(substr($zip_central_directory,  0, 4));
        
        // invalid Central Directory Signature
        if ($central_directory['raw']['signature'] != 0x02014B50) {
            fseek($getid3->fp, $central_directory['offset'], SEEK_SET); // seek back to where filepointer originally was so it can be handled properly
            return false;
        }
        
        getid3_lib::ReadSequence('LittleEndian2Int', $central_directory['raw'], $zip_central_directory,  4, 
            array (
                'create_version'       => 2,
                'extract_version'      => 2,
                'general_flags'        => 2,
                'compression_method'   => 2,
                'last_mod_file_time'   => 2,
                'last_mod_file_date'   => 2,
                'crc_32'               => 4,
                'compressed_size'      => 4,
                'uncompressed_size'    => 4,
                'filename_length'      => 2,
                'extra_field_length'   => 2,
                'file_comment_length'  => 2,
                'disk_number_start'    => 2,
                'internal_file_attrib' => 2,
                'external_file_attrib' => 4,
                'local_header_offset'  => 4
            )
        );
        
        $central_directory['entry_offset']            = $central_directory['raw']['local_header_offset'];
        $central_directory['create_version']          = sprintf('%1.1f', $central_directory['raw']['create_version'] / 10);
        $central_directory['extract_version']         = sprintf('%1.1f', $central_directory['raw']['extract_version'] / 10);
        $central_directory['host_os']                 = $this->ZIPversionOSLookup(($central_directory['raw']['extract_version'] & 0xFF00) >> 8);
        $central_directory['compression_method']      = $this->ZIPcompressionMethodLookup($central_directory['raw']['compression_method']);
        $central_directory['compressed_size']         = $central_directory['raw']['compressed_size'];
        $central_directory['uncompressed_size']       = $central_directory['raw']['uncompressed_size'];
        $central_directory['flags']                   = $this->ZIPparseGeneralPurposeFlags($central_directory['raw']['general_flags'], $central_directory['raw']['compression_method']);
        $central_directory['last_modified_timestamp'] = $this->DOStime2UNIXtime($central_directory['raw']['last_mod_file_date'], $central_directory['raw']['last_mod_file_time']);

        $filename_extra_field_comment_length = $central_directory['raw']['filename_length'] + $central_directory['raw']['extra_field_length'] + $central_directory['raw']['file_comment_length'];
        if ($filename_extra_field_comment_length > 0) {
            $filename_extra_field_comment = fread($getid3->fp, $filename_extra_field_comment_length);

            if ($central_directory['raw']['filename_length'] > 0) {
                $central_directory['filename']= substr($filename_extra_field_comment, 0, $central_directory['raw']['filename_length']);
            }
            if ($central_directory['raw']['extra_field_length'] > 0) {
                $central_directory['raw']['extra_field_data'] = substr($filename_extra_field_comment, $central_directory['raw']['filename_length'], $central_directory['raw']['extra_field_length']);
            }
            if ($central_directory['raw']['file_comment_length'] > 0) {
                $central_directory['file_comment'] = substr($filename_extra_field_comment, $central_directory['raw']['filename_length'] + $central_directory['raw']['extra_field_length'], $central_directory['raw']['file_comment_length']);
            }
        }

        return $central_directory;
    }

    
    
    private function ZIPparseEndOfCentralDirectory() {
        
        // shortcut             
        $getid3 = $this->getid3;
    
        $end_of_central_directory['offset'] = ftell($getid3->fp);

        $zip_end_of_central_directory = fread($getid3->fp, 22);

        $end_of_central_directory['signature'] = getid3_lib::LittleEndian2Int(substr($zip_end_of_central_directory,  0, 4));
        
        // invalid End Of Central Directory Signature
        if ($end_of_central_directory['signature'] != 0x06054B50) {
            fseek($getid3->fp, $end_of_central_directory['offset'], SEEK_SET); // seek back to where filepointer originally was so it can be handled properly
            return false;
        }
        
        getid3_lib::ReadSequence('LittleEndian2Int', $end_of_central_directory, $zip_end_of_central_directory,  4, 
            array (
                'disk_number_current'         => 2,
                'disk_number_start_directory' => 2,
                'directory_entries_this_disk' => 2,
                'directory_entries_total'     => 2,
                'directory_size'              => 4,
                'directory_offset'            => 4,
                'comment_length'              => 2
            )
        );
        
        if ($end_of_central_directory['comment_length'] > 0) {
            $end_of_central_directory['comment'] = fread($getid3->fp, $end_of_central_directory['comment_length']);
        }

        return $end_of_central_directory;
    }
    


    public static function ZIPparseGeneralPurposeFlags($flag_bytes, $compression_method) {

        $parsed_flags['encrypted'] = (bool)($flag_bytes & 0x0001);

        switch ($compression_method) {
            case 6:
                $parsed_flags['dictionary_size']    = (($flag_bytes & 0x0002) ? 8192 : 4096);
                $parsed_flags['shannon_fano_trees'] = (($flag_bytes & 0x0004) ? 3    : 2);
                break;

            case 8:
            case 9:
                switch (($flag_bytes & 0x0006) >> 1) {
                    case 0:
                        $parsed_flags['compression_speed'] = 'normal';
                        break;
                    case 1:
                        $parsed_flags['compression_speed'] = 'maximum';
                        break;
                    case 2:
                        $parsed_flags['compression_speed'] = 'fast';
                        break;
                    case 3:
                        $parsed_flags['compression_speed'] = 'superfast';
                        break;
                }
                break;
        }
        $parsed_flags['data_descriptor_used'] = (bool)($flag_bytes & 0x0008);

        return $parsed_flags;
    }



    public static function ZIPversionOSLookup($index) {
        
        static $lookup = array (
            0  => 'MS-DOS and OS/2 (FAT / VFAT / FAT32 file systems)',
            1  => 'Amiga',
            2  => 'OpenVMS',
            3  => 'Unix',
            4  => 'VM/CMS',
            5  => 'Atari ST',
            6  => 'OS/2 H.P.F.S.',
            7  => 'Macintosh',
            8  => 'Z-System',
            9  => 'CP/M',
            10 => 'Windows NTFS',
            11 => 'MVS',
            12 => 'VSE',
            13 => 'Acorn Risc',
            14 => 'VFAT',
            15 => 'Alternate MVS',
            16 => 'BeOS',
            17 => 'Tandem'
        );
        return (isset($lookup[$index]) ? $lookup[$index] : '[unknown]');
    }



    public static function ZIPcompressionMethodLookup($index) {

        static $lookup = array (
            0  => 'store',
            1  => 'shrink',
            2  => 'reduce-1',
            3  => 'reduce-2',
            4  => 'reduce-3',
            5  => 'reduce-4',
            6  => 'implode',
            7  => 'tokenize',
            8  => 'deflate',
            9  => 'deflate64',
            10 => 'PKWARE Date Compression Library Imploding'
        );
        return (isset($lookup[$index]) ? $lookup[$index] : '[unknown]');
    }



    public static function DOStime2UNIXtime($DOSdate, $DOStime) {

        /*
        // wFatDate
        // Specifies the MS-DOS date. The date is a packed 16-bit value with the following format:
        // Bits      Contents
        // 0-4    Day of the month (1-31)
        // 5-8    Month (1 = January, 2 = February, and so on)
        // 9-15   Year offset from 1980 (add 1980 to get actual year)

        $UNIXday    =  ($DOSdate & 0x001F);
        $UNIXmonth  = (($DOSdate & 0x01E0) >> 5);
        $UNIXyear   = (($DOSdate & 0xFE00) >> 9) + 1980;

        // wFatTime
        // Specifies the MS-DOS time. The time is a packed 16-bit value with the following format:
        // Bits   Contents
        // 0-4    Second divided by 2
        // 5-10   Minute (0-59)
        // 11-15  Hour (0-23 on a 24-hour clock)

        $UNIXsecond =  ($DOStime & 0x001F) * 2;
        $UNIXminute = (($DOStime & 0x07E0) >> 5);
        $UNIXhour   = (($DOStime & 0xF800) >> 11);

        return gmmktime($UNIXhour, $UNIXminute, $UNIXsecond, $UNIXmonth, $UNIXday, $UNIXyear);
        */
        return gmmktime(($DOStime & 0xF800) >> 11, ($DOStime & 0x07E0) >> 5, ($DOStime & 0x001F) * 2, ($DOSdate & 0x01E0) >> 5, $DOSdate & 0x001F, (($DOSdate & 0xFE00) >> 9) + 1980);
    }
    
    
    
    public static function array_merge_clobber($array1, $array2) {

        // written by kcØhireability*com
        // taken from http://www.php.net/manual/en/function.array-merge-recursive.php
        
        if (!is_array($array1) || !is_array($array2)) {
            return false;
        }
        
        $newarray = $array1;
        foreach ($array2 as $key => $val) {
            if (is_array($val) && isset($newarray[$key]) && is_array($newarray[$key])) {
                $newarray[$key] = getid3_zip::array_merge_clobber($newarray[$key], $val);
            } else {
                $newarray[$key] = $val;
            }
        }
        return $newarray;
    }
    
    
    
    public static function CreateDeepArray($array_path, $separator, $value) {

        // assigns $value to a nested array path:
        //   $foo = getid3_lib::CreateDeepArray('/path/to/my', '/', 'file.txt')
        // is the same as:
        //   $foo = array ('path'=>array('to'=>'array('my'=>array('file.txt'))));
        // or
        //   $foo['path']['to']['my'] = 'file.txt';
        
        while ($array_path{0} == $separator) {
            $array_path = substr($array_path, 1);
        }
        if (($pos = strpos($array_path, $separator)) !== false) {
            return array (substr($array_path, 0, $pos) => getid3_zip::CreateDeepArray(substr($array_path, $pos + 1), $separator, $value));
        }
        
        return array ($array_path => $value);
    }

}


?>