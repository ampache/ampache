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
// | module.misc.iso.php                                                  |
// | Module for analyzing ISO files                                       |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.misc.iso.php,v 1.3 2006/11/02 10:48:02 ah Exp $



class getid3_iso extends getid3_handler
{

    public function Analyze() {
        
        $getid3 = $this->getid3;
        
        $getid3->info['fileformat'] = 'iso';

        for ($i = 16; $i <= 19; $i++) {
            fseek($getid3->fp, 2048 * $i, SEEK_SET);
            $iso_header = fread($getid3->fp, 2048);
            if (substr($iso_header, 1, 5) == 'CD001') {
                switch (ord($iso_header{0})) {
                    case 1:
                        $getid3->info['iso']['primary_volume_descriptor']['offset'] = 2048 * $i;
                        $this->ParsePrimaryVolumeDescriptor($iso_header);
                        break;

                    case 2:
                        $getid3->info['iso']['supplementary_volume_descriptor']['offset'] = 2048 * $i;
                        $this->ParseSupplementaryVolumeDescriptor($iso_header);
                        break;

                    default:
                        // skip
                        break;
                }
            }
        }

        $this->ParsePathTable();

        $getid3->info['iso']['files'] = array ();
        foreach ($getid3->info['iso']['path_table']['directories'] as $directory_num => $directory_data) {
            $getid3->info['iso']['directories'][$directory_num] = $this->ParseDirectoryRecord($directory_data);
        }

        return true;
    }



    private function ParsePrimaryVolumeDescriptor(&$iso_header) {
        
        $getid3 = $this->getid3;
        
        // ISO integer values are stored *BOTH* Little-Endian AND Big-Endian format!!
        // ie 12345 == 0x3039  is stored as $39 $30 $30 $39 in a 4-byte field

        $getid3->info['iso']['primary_volume_descriptor']['raw'] = array ();
        $info_iso_primaryVD     = &$getid3->info['iso']['primary_volume_descriptor'];
        $info_iso_primaryVD_raw = &$info_iso_primaryVD['raw'];

        $info_iso_primaryVD_raw['volume_descriptor_type'] = getid3_lib::LittleEndian2Int(substr($iso_header,    0, 1));
        $info_iso_primaryVD_raw['standard_identifier']    = substr($iso_header,    1, 5);
        if ($info_iso_primaryVD_raw['standard_identifier'] != 'CD001') {
            throw new getid3_exception('Expected "CD001" at offset ('.($info_iso_primaryVD['offset'] + 1).'), found "'.$info_iso_primaryVD_raw['standard_identifier'].'" instead');
        }
        
        getid3_lib::ReadSequence('LittleEndian2Int', $info_iso_primaryVD_raw, $iso_header, 6, 
            array (
                'volume_descriptor_version'     => 1,
                'IGNORE-unused_1'               => 1,
                'system_identifier'             => -32,     // string
                'volume_identifier'             => -32,     // string
                'IGNORE-unused_2'               => 8,
                'volume_space_size'             => 4,
                'IGNORE-1'                      => 4,
                'IGNORE-unused_3'               => 32,
                'volume_set_size'               => 2,
                'IGNORE-2'                      => 2,
                'volume_sequence_number'        => 2,
                'IGNORE-3'                      => 2,
                'logical_block_size'            => 2,
                'IGNORE-4'                      => 2,
                'path_table_size'               => 4,
                'IGNORE-5'                      => 4,
                'path_table_l_location'         => 2,
                'IGNORE-6'                      => 2,
                'path_table_l_opt_location'     => 2,
                'IGNORE-7'                      => 2,
                'path_table_m_location'         => 2,
                'IGNORE-8'                      => 2,
                'path_table_m_opt_location'     => 2,
                'IGNORE-9'                      => 2,
                'root_directory_record'         => -34,     // string
                'volume_set_identifier'         => -128,    // string
                'publisher_identifier'          => -128,    // string
                'data_preparer_identifier'      => -128,    // string
                'application_identifier'        => -128,    // string
                'copyright_file_identifier'     => -37,     // string
                'abstract_file_identifier'      => -37,     // string
                'bibliographic_file_identifier' => -37,     // string
                'volume_creation_date_time'     => -17,     // string
                'volume_modification_date_time' => -17,     // string
                'volume_expiration_date_time'   => -17,     // string
                'volume_effective_date_time'    => -17,     // string
                'file_structure_version'        => 1,
                'IGNORE-unused_4'               => 1,
                'application_data'              => -512     // string
            )
        );

        $info_iso_primaryVD['system_identifier']             = trim($info_iso_primaryVD_raw['system_identifier']);
        $info_iso_primaryVD['volume_identifier']             = trim($info_iso_primaryVD_raw['volume_identifier']);
        $info_iso_primaryVD['volume_set_identifier']         = trim($info_iso_primaryVD_raw['volume_set_identifier']);
        $info_iso_primaryVD['publisher_identifier']          = trim($info_iso_primaryVD_raw['publisher_identifier']);
        $info_iso_primaryVD['data_preparer_identifier']      = trim($info_iso_primaryVD_raw['data_preparer_identifier']);
        $info_iso_primaryVD['application_identifier']        = trim($info_iso_primaryVD_raw['application_identifier']);
        $info_iso_primaryVD['copyright_file_identifier']     = trim($info_iso_primaryVD_raw['copyright_file_identifier']);
        $info_iso_primaryVD['abstract_file_identifier']      = trim($info_iso_primaryVD_raw['abstract_file_identifier']);
        $info_iso_primaryVD['bibliographic_file_identifier'] = trim($info_iso_primaryVD_raw['bibliographic_file_identifier']);
        
        $info_iso_primaryVD['volume_creation_date_time']     = getid3_iso::ISOtimeText2UNIXtime($info_iso_primaryVD_raw['volume_creation_date_time']);
        $info_iso_primaryVD['volume_modification_date_time'] = getid3_iso::ISOtimeText2UNIXtime($info_iso_primaryVD_raw['volume_modification_date_time']);
        $info_iso_primaryVD['volume_expiration_date_time']   = getid3_iso::ISOtimeText2UNIXtime($info_iso_primaryVD_raw['volume_expiration_date_time']);
        $info_iso_primaryVD['volume_effective_date_time']    = getid3_iso::ISOtimeText2UNIXtime($info_iso_primaryVD_raw['volume_effective_date_time']);

        if (($info_iso_primaryVD_raw['volume_space_size'] * 2048) > $getid3->info['filesize']) {
            throw new getid3_exception('Volume Space Size ('.($info_iso_primaryVD_raw['volume_space_size'] * 2048).' bytes) is larger than the file size ('.$getid3->info['filesize'].' bytes) (truncated file?)');
        }

        return true;
    }



    private function ParseSupplementaryVolumeDescriptor(&$iso_header) {
        
        $getid3 = $this->getid3;
        
        // ISO integer values are stored Both-Endian format!!
        // ie 12345 == 0x3039  is stored as $39 $30 $30 $39 in a 4-byte field

        $getid3->info['iso']['supplementary_volume_descriptor']['raw'] = array ();
        $info_iso_supplementaryVD     = &$getid3->info['iso']['supplementary_volume_descriptor'];
        $info_iso_supplementaryVD_raw = &$info_iso_supplementaryVD['raw'];

        $info_iso_supplementaryVD_raw['volume_descriptor_type'] = getid3_lib::LittleEndian2Int(substr($iso_header, 0, 1));
        $info_iso_supplementaryVD_raw['standard_identifier']    = substr($iso_header, 1, 5);
        if ($info_iso_supplementaryVD_raw['standard_identifier'] != 'CD001') {
            throw new getid3_exception('Expected "CD001" at offset ('.($info_iso_supplementaryVD['offset'] + 1).'), found "'.$info_iso_supplementaryVD_raw['standard_identifier'].'" instead');
        }

        getid3_lib::ReadSequence('LittleEndian2Int', $info_iso_supplementaryVD_raw, $iso_header, 6, 
            array (
                'volume_descriptor_version'     => 1,
                'IGNORE-unused_1'               => -1,
                'system_identifier'             => -32,
                'volume_identifier'             => -32,
                'IGNORE-unused_2'               => -8,
                'volume_space_size'             => 4,
                'IGNORE-1'                      => 4,
                'IGNORE-unused_3'               => -32,
                'volume_set_size'               => 2,
                'IGNORE-2'                      => 2,
                'volume_sequence_number'        => 2,
                'IGNORE-3'                      => 2,
                'logical_block_size'            => 2,
                'IGNORE-4'                      => 2,
                'path_table_size'               => 4,
                'IGNORE-5'                      => 4,
                'path_table_l_location'         => 2,
                'IGNORE-6'                      => 2,
                'path_table_l_opt_location'     => 2,
                'IGNORE-7'                      => 2,
                'path_table_m_location'         => 2,
                'IGNORE-8'                      => 2,
                'path_table_m_opt_location'     => 2,
                'IGNORE-9'                      => 2,
                'root_directory_record'         => -34,
                'volume_set_identifier'         => -128,
                'publisher_identifier'          => -128,
                'data_preparer_identifier'      => -128,
                'application_identifier'        => -128,
                'copyright_file_identifier'     => -37,
                'abstract_file_identifier'      => -37,
                'bibliographic_file_identifier' => -37,
                'volume_creation_date_time'     => -17,
                'volume_modification_date_time' => -17,
                'volume_expiration_date_time'   => -17,
                'volume_effective_date_time'    => -17,
                'file_structure_version'        => 1,
                'IGNORE-unused_4'               => 1,
                'application_data'              => -512
            )
        );

        $info_iso_supplementaryVD['system_identifier']              = trim($info_iso_supplementaryVD_raw['system_identifier']);
        $info_iso_supplementaryVD['volume_identifier']              = trim($info_iso_supplementaryVD_raw['volume_identifier']);
        $info_iso_supplementaryVD['volume_set_identifier']          = trim($info_iso_supplementaryVD_raw['volume_set_identifier']);
        $info_iso_supplementaryVD['publisher_identifier']           = trim($info_iso_supplementaryVD_raw['publisher_identifier']);
        $info_iso_supplementaryVD['data_preparer_identifier']       = trim($info_iso_supplementaryVD_raw['data_preparer_identifier']);
        $info_iso_supplementaryVD['application_identifier']         = trim($info_iso_supplementaryVD_raw['application_identifier']);
        $info_iso_supplementaryVD['copyright_file_identifier']      = trim($info_iso_supplementaryVD_raw['copyright_file_identifier']);
        $info_iso_supplementaryVD['abstract_file_identifier']       = trim($info_iso_supplementaryVD_raw['abstract_file_identifier']);
        $info_iso_supplementaryVD['bibliographic_file_identifier']  = trim($info_iso_supplementaryVD_raw['bibliographic_file_identifier']);
        
        $info_iso_supplementaryVD['volume_creation_date_time']      = getid3_iso::ISOtimeText2UNIXtime($info_iso_supplementaryVD_raw['volume_creation_date_time']);
        $info_iso_supplementaryVD['volume_modification_date_time']  = getid3_iso::ISOtimeText2UNIXtime($info_iso_supplementaryVD_raw['volume_modification_date_time']);
        $info_iso_supplementaryVD['volume_expiration_date_time']    = getid3_iso::ISOtimeText2UNIXtime($info_iso_supplementaryVD_raw['volume_expiration_date_time']);
        $info_iso_supplementaryVD['volume_effective_date_time']     = getid3_iso::ISOtimeText2UNIXtime($info_iso_supplementaryVD_raw['volume_effective_date_time']);

        if (($info_iso_supplementaryVD_raw['volume_space_size'] * $info_iso_supplementaryVD_raw['logical_block_size']) > $getid3->info['filesize']) {
            throw new getid3_exception('Volume Space Size ('.($info_iso_supplementaryVD_raw['volume_space_size'] * $info_iso_supplementaryVD_raw['logical_block_size']).' bytes) is larger than the file size ('.$getid3->info['filesize'].' bytes) (truncated file?)');
        }

        return true;
    }



    private function ParsePathTable() {
        
        $getid3 = $this->getid3;
        
        if (!isset($getid3->info['iso']['supplementary_volume_descriptor']['raw']['path_table_l_location']) && !isset($getid3->info['iso']['primary_volume_descriptor']['raw']['path_table_l_location'])) {
            return false;
        }
        if (isset($getid3->info['iso']['supplementary_volume_descriptor']['raw']['path_table_l_location'])) {
            $path_table_location = $getid3->info['iso']['supplementary_volume_descriptor']['raw']['path_table_l_location'];
            $path_table_size     = $getid3->info['iso']['supplementary_volume_descriptor']['raw']['path_table_size'];
            $text_encoding       = 'UTF-16BE'; // Big-Endian Unicode
        } 
        else {
            $path_table_location = $getid3->info['iso']['primary_volume_descriptor']['raw']['path_table_l_location'];
            $path_table_size     = $getid3->info['iso']['primary_volume_descriptor']['raw']['path_table_size'];
            $text_encoding       = 'ISO-8859-1'; // Latin-1
        }

        if (($path_table_location * 2048) > $getid3->info['filesize']) {
            throw new getid3_exception('Path Table Location specifies an offset ('.($path_table_location * 2048).') beyond the end-of-file ('.$getid3->info['filesize'].')');
        }

        $getid3->info['iso']['path_table']['offset'] = $path_table_location * 2048;
        fseek($getid3->fp, $getid3->info['iso']['path_table']['offset'], SEEK_SET);
        $getid3->info['iso']['path_table']['raw'] = fread($getid3->fp, $path_table_size);

        $offset = 0;
        $pathcounter = 1;
        while ($offset < $path_table_size) {
            
            $getid3->info['iso']['path_table']['directories'][$pathcounter] = array ();
            $info_iso_pathtable_directories_current = &$getid3->info['iso']['path_table']['directories'][$pathcounter];

            getid3_lib::ReadSequence('LittleEndian2Int', $info_iso_pathtable_directories_current, $getid3->info['iso']['path_table']['raw'], $offset, 
                array (
                       'length'           => 1,
                    'extended_length'  => 1,
                    'location_logical' => 4,
                    'parent_directory' => 2,
                )
            );
            
            $info_iso_pathtable_directories_current['name'] = substr($getid3->info['iso']['path_table']['raw'], $offset+8, $info_iso_pathtable_directories_current['length']);
            
            $offset += 8 + $info_iso_pathtable_directories_current['length'] + ($info_iso_pathtable_directories_current['length'] % 2);

            $info_iso_pathtable_directories_current['name_ascii'] = $getid3->iconv($text_encoding, $getid3->encoding, $info_iso_pathtable_directories_current['name'], true);

            $info_iso_pathtable_directories_current['location_bytes'] = $info_iso_pathtable_directories_current['location_logical'] * 2048;
            if ($pathcounter == 1) {
                $info_iso_pathtable_directories_current['full_path'] = '/';
            }
            else {
                $info_iso_pathtable_directories_current['full_path'] = $getid3->info['iso']['path_table']['directories'][$info_iso_pathtable_directories_current['parent_directory']]['full_path'].$info_iso_pathtable_directories_current['name_ascii'].'/';
            }
            $full_path_array[] = $info_iso_pathtable_directories_current['full_path'];

            $pathcounter++;
        }

        return true;
    }



    private function ParseDirectoryRecord($directory_data) {
        
        $getid3 = $this->getid3;
        
        $text_encoding = isset($getid3->info['iso']['supplementary_volume_descriptor']) ? 'UTF-16BE' : 'ISO-8859-1'; 

        fseek($getid3->fp, $directory_data['location_bytes'], SEEK_SET);
        $directory_record_data = fread($getid3->fp, 1);
        
        while (ord($directory_record_data{0}) > 33) {

            $directory_record_data .= fread($getid3->fp, ord($directory_record_data{0}) - 1);
            
            $this_directory_record = array ();
            $this_directory_record['raw'] = array ();
            $this_directory_record_raw = &$this_directory_record['raw'];
            
            getid3_lib::ReadSequence('LittleEndian2Int', $this_directory_record_raw, $directory_record_data, 0, 
                array (
                    'length'                    => 1,
                    'extended_attribute_length' => 1,
                    'offset_logical'            => 4,
                    'IGNORE-1'                  => 4,
                    'filesize'                  => 4,
                    'IGNORE-2'                  => 4,
                    'recording_date_time'       => -7,
                    'file_flags'                => 1,
                    'file_unit_size'            => 1,
                    'interleave_gap_size'       => 1,
                    'volume_sequence_number'    => 2,
                    'IGNORE-3'                  => 2,
                    'file_identifier_length'    => 1,
                )
            );

            $this_directory_record_raw['file_identifier'] = substr($directory_record_data, 33, $this_directory_record_raw['file_identifier_length']);
            
            $this_directory_record['file_identifier_ascii']     = $getid3->iconv($text_encoding, $getid3->encoding, $this_directory_record_raw['file_identifier'], true);
            $this_directory_record['filesize']                  = $this_directory_record_raw['filesize'];
            $this_directory_record['offset_bytes']              = $this_directory_record_raw['offset_logical'] * 2048;
            $this_directory_record['file_flags']['hidden']      = (bool)($this_directory_record_raw['file_flags'] & 0x01);
            $this_directory_record['file_flags']['directory']   = (bool)($this_directory_record_raw['file_flags'] & 0x02);
            $this_directory_record['file_flags']['associated']  = (bool)($this_directory_record_raw['file_flags'] & 0x04);
            $this_directory_record['file_flags']['extended']    = (bool)($this_directory_record_raw['file_flags'] & 0x08);
            $this_directory_record['file_flags']['permissions'] = (bool)($this_directory_record_raw['file_flags'] & 0x10);
            $this_directory_record['file_flags']['multiple']    = (bool)($this_directory_record_raw['file_flags'] & 0x80);
            $this_directory_record['recording_timestamp']       = getid3_iso::ISOtime2UNIXtime($this_directory_record_raw['recording_date_time']);

            if ($this_directory_record['file_flags']['directory']) {
                $this_directory_record['filename'] = $directory_data['full_path'];
            }
            else {
                $this_directory_record['filename'] = $directory_data['full_path'].getid3_iso::ISOstripFilenameVersion($this_directory_record['file_identifier_ascii']);
                $getid3->info['iso']['files'] = getid3_iso::array_merge_clobber($getid3->info['iso']['files'], getid3_iso::CreateDeepArray($this_directory_record['filename'], '/', $this_directory_record['filesize']));
            }
            
            $directory_record[]    = $this_directory_record;
            $directory_record_data = fread($getid3->fp, 1);
        }
        
        return $directory_record;
    }



    public static function ISOstripFilenameVersion($iso_filename) {

        // convert 'filename.ext;1' to 'filename.ext'
        if (!strstr($iso_filename, ';')) {
            return $iso_filename;
        }
        return substr($iso_filename, 0, strpos($iso_filename, ';'));
    }



    public static function ISOtimeText2UNIXtime($iso_time) {

        if (!(int)substr($iso_time, 0, 4)) {
            return false;
        }
        
        return gmmktime((int)substr($iso_time, 8, 2), (int)substr($iso_time, 10, 2), (int)substr($iso_time, 12, 2), (int)substr($iso_time, 4, 2), (int)substr($iso_time, 6, 2), (int)substr($iso_time, 0, 4));
    }
    
    

    public static function ISOtime2UNIXtime($iso_time) {
    
        // Represented by seven bytes:
        // 1: Number of years since 1900
        // 2: Month of the year from 1 to 12
        // 3: Day of the Month from 1 to 31
        // 4: Hour of the day from 0 to 23
        // 5: Minute of the hour from 0 to 59
        // 6: second of the minute from 0 to 59
        // 7: Offset from Greenwich Mean Time in number of 15 minute intervals from -48 (West) to +52 (East)

        return gmmktime(ord($iso_time[3]), ord($iso_time[4]), ord($iso_time[5]), ord($iso_time[1]), ord($iso_time[2]), ord($iso_time[0]) + 1900);
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
                $newarray[$key] = getid3_iso::array_merge_clobber($newarray[$key], $val);
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
            return array (substr($array_path, 0, $pos) => getid3_iso::CreateDeepArray(substr($array_path, $pos + 1), $separator, $value));
        }
        
        return array ($array_path => $value);
    }

}

?>