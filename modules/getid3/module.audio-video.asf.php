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
// | module.audio-video.php                                               |
// | Module for analyzing Microsoft ASF, WMA and WMV files.               |
// | dependencies: module.audio-video.riff.php                            |
// +----------------------------------------------------------------------+
//
// $Id: module.audio-video.asf.php,v 1.7 2006/12/01 22:39:48 ah Exp $

        
        
class getid3_asf extends getid3_handler
{

    const Extended_Stream_Properties_Object   = '14E6A5CB-C672-4332-8399-A96952065B5A';
    const Padding_Object                      = '1806D474-CADF-4509-A4BA-9AABCB96AAE8';
    const Payload_Ext_Syst_Pixel_Aspect_Ratio = '1B1EE554-F9EA-4BC8-821A-376B74E4C4B8';
    const Script_Command_Object               = '1EFB1A30-0B62-11D0-A39B-00A0C90348F6';
    const No_Error_Correction                 = '20FB5700-5B55-11CF-A8FD-00805F5C442B';
    const Content_Branding_Object             = '2211B3FA-BD23-11D2-B4B7-00A0C955FC6E';
    const Content_Encryption_Object           = '2211B3FB-BD23-11D2-B4B7-00A0C955FC6E';
    const Digital_Signature_Object            = '2211B3FC-BD23-11D2-B4B7-00A0C955FC6E';
    const Extended_Content_Encryption_Object  = '298AE614-2622-4C17-B935-DAE07EE9289C';
    const Simple_Index_Object                 = '33000890-E5B1-11CF-89F4-00A0C90349CB';
    const Degradable_JPEG_Media               = '35907DE0-E415-11CF-A917-00805F5C442B';
    const Payload_Extension_System_Timecode   = '399595EC-8667-4E2D-8FDB-98814CE76C1E';
    const Binary_Media                        = '3AFB65E2-47EF-40F2-AC2C-70A90D71D343';
    const Timecode_Index_Object               = '3CB73FD0-0C4A-4803-953D-EDF7B6228F0C';
    const Metadata_Library_Object             = '44231C94-9498-49D1-A141-1D134E457054';
    const Reserved_3                          = '4B1ACBE3-100B-11D0-A39B-00A0C90348F6';
    const Reserved_4                          = '4CFEDB20-75F6-11CF-9C0F-00A0C90349CB';
    const Command_Media                       = '59DACFC0-59E6-11D0-A3AC-00A0C90348F6';
    const Header_Extension_Object             = '5FBF03B5-A92E-11CF-8EE3-00C00C205365';
    const Media_Object_Index_Parameters_Obj   = '6B203BAD-3F11-4E84-ACA8-D7613DE2CFA7';
    const Header_Object                       = '75B22630-668E-11CF-A6D9-00AA0062CE6C';
    const Content_Description_Object          = '75B22633-668E-11CF-A6D9-00AA0062CE6C';
    const Error_Correction_Object             = '75B22635-668E-11CF-A6D9-00AA0062CE6C';
    const Data_Object                         = '75B22636-668E-11CF-A6D9-00AA0062CE6C';
    const Web_Stream_Media_Subtype            = '776257D4-C627-41CB-8F81-7AC7FF1C40CC';
    const Stream_Bitrate_Properties_Object    = '7BF875CE-468D-11D1-8D82-006097C9A2B2';
    const Language_List_Object                = '7C4346A9-EFE0-4BFC-B229-393EDE415C85';
    const Codec_List_Object                   = '86D15240-311D-11D0-A3A4-00A0C90348F6';
    const Reserved_2                          = '86D15241-311D-11D0-A3A4-00A0C90348F6';
    const File_Properties_Object              = '8CABDCA1-A947-11CF-8EE4-00C00C205365';
    const File_Transfer_Media                 = '91BD222C-F21C-497A-8B6D-5AA86BFC0185';
    const Old_RTP_Extension_Data              = '96800C63-4C94-11D1-837B-0080C7A37F95';
    const Advanced_Mutual_Exclusion_Object    = 'A08649CF-4775-4670-8A16-6E35357566CD';
    const Bandwidth_Sharing_Object            = 'A69609E6-517B-11D2-B6AF-00C04FD908E9';
    const Reserved_1                          = 'ABD3D211-A9BA-11CF-8EE6-00C00C205365';
    const Bandwidth_Sharing_Exclusive         = 'AF6060AA-5197-11D2-B6AF-00C04FD908E9';
    const Bandwidth_Sharing_Partial           = 'AF6060AB-5197-11D2-B6AF-00C04FD908E9';
    const JFIF_Media                          = 'B61BE100-5B4E-11CF-A8FD-00805F5C442B';
    const Stream_Properties_Object            = 'B7DC0791-A9B7-11CF-8EE6-00C00C205365';
    const Video_Media                         = 'BC19EFC0-5B4D-11CF-A8FD-00805F5C442B';
    const Audio_Spread                        = 'BFC3CD50-618F-11CF-8BB2-00AA00B4E220';
    const Metadata_Object                     = 'C5F8CBEA-5BAF-4877-8467-AA8C44FA4CCA';
    const Payload_Ext_Syst_Sample_Duration    = 'C6BD9450-867F-4907-83A3-C77921B733AD';
    const Group_Mutual_Exclusion_Object       = 'D1465A40-5A79-4338-B71B-E36B8FD6C249';
    const Extended_Content_Description_Object = 'D2D0A440-E307-11D2-97F0-00A0C95EA850';
    const Stream_Prioritization_Object        = 'D4FED15B-88D3-454F-81F0-ED5C45999E24';
    const Payload_Ext_System_Content_Type     = 'D590DC20-07BC-436C-9CF7-F3BBFBF1A4DC';
    const Old_File_Properties_Object          = 'D6E229D0-35DA-11D1-9034-00A0C90349BE';
    const Old_ASF_Header_Object               = 'D6E229D1-35DA-11D1-9034-00A0C90349BE';
    const Old_ASF_Data_Object                 = 'D6E229D2-35DA-11D1-9034-00A0C90349BE';
    const Index_Object                        = 'D6E229D3-35DA-11D1-9034-00A0C90349BE';
    const Old_Stream_Properties_Object        = 'D6E229D4-35DA-11D1-9034-00A0C90349BE';
    const Old_Content_Description_Object      = 'D6E229D5-35DA-11D1-9034-00A0C90349BE';
    const Old_Script_Command_Object           = 'D6E229D6-35DA-11D1-9034-00A0C90349BE';
    const Old_Marker_Object                   = 'D6E229D7-35DA-11D1-9034-00A0C90349BE';
    const Old_Component_Download_Object       = 'D6E229D8-35DA-11D1-9034-00A0C90349BE';
    const Old_Stream_Group_Object             = 'D6E229D9-35DA-11D1-9034-00A0C90349BE';
    const Old_Scalable_Object                 = 'D6E229DA-35DA-11D1-9034-00A0C90349BE';
    const Old_Prioritization_Object           = 'D6E229DB-35DA-11D1-9034-00A0C90349BE';
    const Bitrate_Mutual_Exclusion_Object     = 'D6E229DC-35DA-11D1-9034-00A0C90349BE';
    const Old_Inter_Media_Dependency_Object   = 'D6E229DD-35DA-11D1-9034-00A0C90349BE';
    const Old_Rating_Object                   = 'D6E229DE-35DA-11D1-9034-00A0C90349BE';
    const Index_Parameters_Object             = 'D6E229DF-35DA-11D1-9034-00A0C90349BE';
    const Old_Color_Table_Object              = 'D6E229E0-35DA-11D1-9034-00A0C90349BE';
    const Old_Language_List_Object            = 'D6E229E1-35DA-11D1-9034-00A0C90349BE';
    const Old_Audio_Media                     = 'D6E229E2-35DA-11D1-9034-00A0C90349BE';
    const Old_Video_Media                     = 'D6E229E3-35DA-11D1-9034-00A0C90349BE';
    const Old_Image_Media                     = 'D6E229E4-35DA-11D1-9034-00A0C90349BE';
    const Old_Timecode_Media                  = 'D6E229E5-35DA-11D1-9034-00A0C90349BE';
    const Old_Text_Media                      = 'D6E229E6-35DA-11D1-9034-00A0C90349BE';
    const Old_MIDI_Media                      = 'D6E229E7-35DA-11D1-9034-00A0C90349BE';
    const Old_Command_Media                   = 'D6E229E8-35DA-11D1-9034-00A0C90349BE';
    const Old_No_Error_Concealment            = 'D6E229EA-35DA-11D1-9034-00A0C90349BE';
    const Old_Scrambled_Audio                 = 'D6E229EB-35DA-11D1-9034-00A0C90349BE';
    const Old_No_Color_Table                  = 'D6E229EC-35DA-11D1-9034-00A0C90349BE';
    const Old_SMPTE_Time                      = 'D6E229ED-35DA-11D1-9034-00A0C90349BE';
    const Old_ASCII_Text                      = 'D6E229EE-35DA-11D1-9034-00A0C90349BE';
    const Old_Unicode_Text                    = 'D6E229EF-35DA-11D1-9034-00A0C90349BE';
    const Old_HTML_Text                       = 'D6E229F0-35DA-11D1-9034-00A0C90349BE';
    const Old_URL_Command                     = 'D6E229F1-35DA-11D1-9034-00A0C90349BE';
    const Old_Filename_Command                = 'D6E229F2-35DA-11D1-9034-00A0C90349BE';
    const Old_ACM_Codec                       = 'D6E229F3-35DA-11D1-9034-00A0C90349BE';
    const Old_VCM_Codec                       = 'D6E229F4-35DA-11D1-9034-00A0C90349BE';
    const Old_QuickTime_Codec                 = 'D6E229F5-35DA-11D1-9034-00A0C90349BE';
    const Old_DirectShow_Transform_Filter     = 'D6E229F6-35DA-11D1-9034-00A0C90349BE';
    const Old_DirectShow_Rendering_Filter     = 'D6E229F7-35DA-11D1-9034-00A0C90349BE';
    const Old_No_Enhancement                  = 'D6E229F8-35DA-11D1-9034-00A0C90349BE';
    const Old_Unknown_Enhancement_Type        = 'D6E229F9-35DA-11D1-9034-00A0C90349BE';
    const Old_Temporal_Enhancement            = 'D6E229FA-35DA-11D1-9034-00A0C90349BE';
    const Old_Spatial_Enhancement             = 'D6E229FB-35DA-11D1-9034-00A0C90349BE';
    const Old_Quality_Enhancement             = 'D6E229FC-35DA-11D1-9034-00A0C90349BE';
    const Old_Number_of_Channels_Enhancement  = 'D6E229FD-35DA-11D1-9034-00A0C90349BE';
    const Old_Frequency_Response_Enhancement  = 'D6E229FE-35DA-11D1-9034-00A0C90349BE';
    const Old_Media_Object                    = 'D6E229FF-35DA-11D1-9034-00A0C90349BE';
    const Mutex_Language                      = 'D6E22A00-35DA-11D1-9034-00A0C90349BE';
    const Mutex_Bitrate                       = 'D6E22A01-35DA-11D1-9034-00A0C90349BE';
    const Mutex_Unknown                       = 'D6E22A02-35DA-11D1-9034-00A0C90349BE';
    const Old_ASF_Placeholder_Object          = 'D6E22A0E-35DA-11D1-9034-00A0C90349BE';
    const Old_Data_Unit_Extension_Object      = 'D6E22A0F-35DA-11D1-9034-00A0C90349BE';
    const Web_Stream_Format                   = 'DA1E6B13-8359-4050-B398-388E965BF00C';
    const Payload_Ext_System_File_Name        = 'E165EC0E-19ED-45D7-B4A7-25CBD1E28E9B';
    const Marker_Object                       = 'F487CD01-A951-11CF-8EE6-00C00C205365';
    const Timecode_Index_Parameters_Object    = 'F55E496D-9797-4B5D-8C8B-604DFE9BFB24';
    const Audio_Media                         = 'F8699E40-5B4D-11CF-A8FD-00805F5C442B';
    const Media_Object_Index_Object           = 'FEB103F8-12AD-4C64-840F-2A1D2F7AD48C';
    const Alt_Extended_Content_Encryption_Obj = 'FF889EF1-ADEE-40DA-9E71-98704BB928CE';



    public function Analyze() {

        $getid3 = $this->getid3;
        
        $getid3->include_module('audio-video.riff');

        !isset($getid3->info['audio']) and $getid3->info['audio'] = array ();
        !isset($getid3->info['video']) and $getid3->info['video'] = array ();
        $getid3->info['asf']['comments'] = $getid3->info['asf']['header_object'] = array ();
            
        $info_audio             = &$getid3->info['audio'];
        $info_video             = &$getid3->info['video'];
        $info_asf               = &$getid3->info['asf'];
        $info_asf_comments      = &$info_asf['comments'];
        $info_asf_header_object = &$info_asf['header_object'];

        // ASF structure:
        // * Header Object [required]
        //   * File Properties Object [required]   (global file attributes)
        //   * Stream Properties Object [required] (defines media stream & characteristics)
        //   * Header Extension Object [required]  (additional functionality)
        //   * Content Description Object          (bibliographic information)
        //   * Script Command Object               (commands for during playback)
        //   * Marker Object                       (named jumped points within the file)
        // * Data Object [required]
        //   * Data Packets
        // * Index Object

        // Header Object: (mandatory, one only)
        // Field Name                   Field Type   Size (bits)
        // Object ID                    GUID         128             // GUID for header object - getid3_asf::Header_Object
        // Object Size                  QWORD        64              // size of header object, including 30 bytes of Header Object header
        // Number of Header Objects     DWORD        32              // number of objects in header object
        // Reserved1                    BYTE         8               // hardcoded: 0x01
        // Reserved2                    BYTE         8               // hardcoded: 0x02

        $getid3->info['fileformat'] = 'asf';

        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        $header_object_data = fread($getid3->fp, 30);

        $info_asf_header_object['objectid_guid'] = getid3_asf::BytestringToGUID(substr($header_object_data, 0, 16));
        
        if ($info_asf_header_object['objectid_guid'] != getid3_asf::Header_Object) {
            throw new getid3_exception('ASF header GUID {'.$info_asf_header_object['objectid_guid'].'} does not match expected "getid3_asf::Header_Object" GUID {'.getid3_asf::Header_Object.'}');
        }
        
        getid3_lib::ReadSequence('LittleEndian2Int', $info_asf_header_object, $header_object_data, 16,
            array (
                'objectsize'    => 8,
                'headerobjects' => 4,
                'reserved1'     => 1,
                'reserved2'     => 1
            )
        );

        $asf_header_data = fread($getid3->fp, $info_asf_header_object['objectsize'] - 30);
        $offset = 0;

        for ($header_objects_counter = 0; $header_objects_counter < $info_asf_header_object['headerobjects']; $header_objects_counter++) {
            
            $next_object_guid = substr($asf_header_data, $offset, 16);
            $offset += 16;
            
            $next_object_size = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 8));
            $offset += 8;
            
            $next_object_guidtext = getid3_asf::BytestringToGUID($next_object_guid);
            
            switch ($next_object_guidtext) {

                case getid3_asf::File_Properties_Object:
                
                    // File Properties Object: (mandatory, one only)
                    // Field Name                   Field Type   Size (bits)
                    // Object ID                    GUID         128             // GUID for file properties object - getid3_asf::File_Properties_Object
                    // Object Size                  QWORD        64              // size of file properties object, including 104 bytes of File Properties Object header
                    // File ID                      GUID         128             // unique ID - identical to File ID in Data Object
                    // File Size                    QWORD        64              // entire file in bytes. Invalid if Broadcast Flag == 1
                    // Creation Date                QWORD        64              // date & time of file creation. Maybe invalid if Broadcast Flag == 1
                    // Data Packets Count           QWORD        64              // number of data packets in Data Object. Invalid if Broadcast Flag == 1
                    // Play Duration                QWORD        64              // playtime, in 100-nanosecond units. Invalid if Broadcast Flag == 1
                    // Send Duration                QWORD        64              // time needed to send file, in 100-nanosecond units. Players can ignore this value. Invalid if Broadcast Flag == 1
                    // Preroll                      QWORD        64              // time to buffer data before starting to play file, in 1-millisecond units. If <> 0, PlayDuration and PresentationTime have been offset by this amount
                    // Flags                        DWORD        32              //
                    // * Broadcast Flag             bits         1  (0x01)       // file is currently being written, some header values are invalid
                    // * Seekable Flag              bits         1  (0x02)       // is file seekable
                    // * Reserved                   bits         30 (0xFFFFFFFC) // reserved - set to zero
                    // Minimum Data Packet Size     DWORD        32              // in bytes. should be same as Maximum Data Packet Size. Invalid if Broadcast Flag == 1
                    // Maximum Data Packet Size     DWORD        32              // in bytes. should be same as Minimum Data Packet Size. Invalid if Broadcast Flag == 1
                    // Maximum Bitrate              DWORD        32              // maximum instantaneous bitrate in bits per second for entire file, including all data streams and ASF overhead

                    $info_asf['file_properties_object'] = array ();
                    $info_asf_file_properties_object = &$info_asf['file_properties_object'];

                    $info_asf_file_properties_object['objectid_guid']      = $next_object_guidtext;
                    $info_asf_file_properties_object['objectsize']         = $next_object_size;
                    
                    $info_asf_file_properties_object['fileid_guid']        = getid3_asf::BytestringToGUID(substr($asf_header_data, $offset, 16));
                    $offset += 16;

                    getid3_lib::ReadSequence('LittleEndian2Int', $info_asf_file_properties_object, $asf_header_data, $offset,
                        array (
                            'filesize'        => 8,
                            'creation_date'   => 8,
                            'data_packets'    => 8,
                            'play_duration'   => 8,
                            'send_duration'   => 8,
                            'preroll'         => 8,
                            'flags_raw'       => 4,
                            'min_packet_size' => 4,
                            'max_packet_size' => 4,
                            'max_bitrate'     => 4
                        )
                    );
                    
                    $offset += 64   ;
                    
                    $info_asf_file_properties_object['creation_date_unix'] = getid3_asf::FiletimeToUNIXtime($info_asf_file_properties_object['creation_date']);
                    $info_asf_file_properties_object['flags']['broadcast'] = (bool)($info_asf_file_properties_object['flags_raw'] & 0x0001);
                    $info_asf_file_properties_object['flags']['seekable']  = (bool)($info_asf_file_properties_object['flags_raw'] & 0x0002);

                    $getid3->info['playtime_seconds'] = ($info_asf_file_properties_object['play_duration'] / 10000000) - ($info_asf_file_properties_object['preroll'] / 1000);
                    $getid3->info['bitrate']          = ($info_asf_file_properties_object['filesize'] * 8) / $getid3->info['playtime_seconds'];
                    break;


                case getid3_asf::Stream_Properties_Object:
                    
                    // Stream Properties Object: (mandatory, one per media stream)
                    // Field Name                   Field Type   Size (bits)
                    // Object ID                    GUID         128             // GUID for stream properties object - getid3_asf::Stream_Properties_Object
                    // Object Size                  QWORD        64              // size of stream properties object, including 78 bytes of Stream Properties Object header
                    // Stream Type                  GUID         128             // getid3_asf::Audio_Media, getid3_asf::Video_Media or getid3_asf::Command_Media
                    // Error Correction Type        GUID         128             // getid3_asf::Audio_Spread for audio-only streams, getid3_asf::No_Error_Correction for other stream types
                    // Time Offset                  QWORD        64              // 100-nanosecond units. typically zero. added to all timestamps of samples in the stream
                    // Type-Specific Data Length    DWORD        32              // number of bytes for Type-Specific Data field
                    // Error Correction Data Length DWORD        32              // number of bytes for Error Correction Data field
                    // Flags                        WORD         16              //
                    // * Stream Number              bits         7 (0x007F)      // number of this stream.  1 <= valid <= 127
                    // * Reserved                   bits         8 (0x7F80)      // reserved - set to zero
                    // * Encrypted Content Flag     bits         1 (0x8000)      // stream contents encrypted if set
                    // Reserved                     DWORD        32              // reserved - set to zero
                    // Type-Specific Data           BYTESTREAM   variable        // type-specific format data, depending on value of Stream Type
                    // Error Correction Data        BYTESTREAM   variable        // error-correction-specific format data, depending on value of Error Correct Type

                    // There is one getid3_asf::Stream_Properties_Object for each stream (audio, video) but the
                    // stream number isn't known until halfway through decoding the structure, hence it
                    // it is decoded to a temporary variable and then stuck in the appropriate index later

                    $stream_properties_object_data['objectid_guid']      = $next_object_guidtext;
                    $stream_properties_object_data['objectsize']         = $next_object_size;
                    
                    getid3_lib::ReadSequence('LittleEndian2Int', $stream_properties_object_data, $asf_header_data, $offset,
                        array (
                            'stream_type'        => -16,
                            'error_correct_type' => -16,
                            'time_offset'        => 8,
                            'type_data_length'   => 4,
                            'error_data_length'  => 4,
                            'flags_raw'          => 2
                        )
                    );

                    $stream_properties_stream_number                     =        $stream_properties_object_data['flags_raw'] & 0x007F;
                    $stream_properties_object_data['flags']['encrypted'] = (bool)($stream_properties_object_data['flags_raw'] & 0x8000);

                    $stream_properties_object_data['stream_type_guid']   = getid3_asf::BytestringToGUID($stream_properties_object_data['stream_type']);
                    $stream_properties_object_data['error_correct_guid'] = getid3_asf::BytestringToGUID($stream_properties_object_data['error_correct_type']);

                    $offset += 54; // 50 bytes + 4 bytes reserved - DWORD
                    
                    $stream_properties_object_data['type_specific_data'] = substr($asf_header_data, $offset, $stream_properties_object_data['type_data_length']);
                    $offset += $stream_properties_object_data['type_data_length'];
                    
                    $stream_properties_object_data['error_correct_data'] = substr($asf_header_data, $offset, $stream_properties_object_data['error_data_length']);
                    $offset += $stream_properties_object_data['error_data_length'];

                    switch ($stream_properties_object_data['stream_type_guid']) {

                        case getid3_asf::Audio_Media:
                    
                            $info_audio['dataformat']   = (@$info_audio['dataformat']   ? $info_audio['dataformat']   : 'asf');
                            $info_audio['bitrate_mode'] = (@$info_audio['bitrate_mode'] ? $info_audio['bitrate_mode'] : 'cbr');

                            $audiodata = getid3_riff::RIFFparseWAVEFORMATex(substr($stream_properties_object_data['type_specific_data'], 0, 16));
                            unset($audiodata['raw']);
                            $info_audio = getid3_riff::array_merge_noclobber($audiodata, $info_audio);
                            break;


                        case getid3_asf::Video_Media:

                            $info_video['dataformat']   = (@$info_video['dataformat']   ? $info_video['dataformat']   : 'asf');
                            $info_video['bitrate_mode'] = (@$info_video['bitrate_mode'] ? $info_video['bitrate_mode'] : 'cbr');
                            break;


                        /* does nothing but eat memory
                        case getid3_asf::Command_Media:
                        default:
                            // do nothing
                            break;
                        */
                    }

                    $info_asf['stream_properties_object'][$stream_properties_stream_number] = $stream_properties_object_data;
                    unset($stream_properties_object_data); // clear for next stream, if any
                    break;
                    

                case getid3_asf::Header_Extension_Object:
                    
                    // Header Extension Object: (mandatory, one only)
                    // Field Name                   Field Type   Size (bits)
                    // Object ID                    GUID         128             // GUID for Header Extension object - getid3_asf::Header_Extension_Object
                    // Object Size                  QWORD        64              // size of Header Extension object, including 46 bytes of Header Extension Object header
                    // Reserved Field 1             GUID         128             // hardcoded: getid3_asf::Reserved_1
                    // Reserved Field 2             WORD         16              // hardcoded: 0x00000006
                    // Header Extension Data Size   DWORD        32              // in bytes. valid: 0, or > 24. equals object size minus 46
                    // Header Extension Data        BYTESTREAM   variable        // array of zero or more extended header objects

                    $info_asf['header_extension_object'] = array ();
                    $info_asf_header_extension_object    = &$info_asf['header_extension_object'];

                    $info_asf_header_extension_object['objectid_guid']   = $next_object_guidtext;
                    $info_asf_header_extension_object['objectsize']      = $next_object_size;
                    $info_asf_header_extension_object['reserved_1_guid'] = getid3_asf::BytestringToGUID(substr($asf_header_data, $offset, 16));
                    $offset += 16;
                    
                    if ($info_asf_header_extension_object['reserved_1_guid'] != getid3_asf::Reserved_1) {
                        $getid3->warning('header_extension_object.reserved_1 GUID ('.$info_asf_header_extension_object['reserved_1_guid'].') does not match expected "getid3_asf::Reserved_1" GUID ('.getid3_asf::Reserved_1.')');
                        break;
                    }
                    
                    $info_asf_header_extension_object['reserved_2'] = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 2));
                    $offset += 2;
                    
                    if ($info_asf_header_extension_object['reserved_2'] != 6) {
                        $getid3->warning('header_extension_object.reserved_2 ('.getid3_lib::PrintHexBytes($info_asf_header_extension_object['reserved_2']).') does not match expected value of "6"');
                        break;
                    }
                    
                    $info_asf_header_extension_object['extension_data_size'] = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 4));
                    $offset += 4;
                    
                    $info_asf_header_extension_object['extension_data'] = substr($asf_header_data, $offset, $info_asf_header_extension_object['extension_data_size']);
                    $offset += $info_asf_header_extension_object['extension_data_size'];
                    break;
                    

                case getid3_asf::Codec_List_Object:
                    
                    // Codec List Object: (optional, one only)
                    // Field Name                   Field Type   Size (bits)
                    // Object ID                    GUID         128             // GUID for Codec List object - getid3_asf::Codec_List_Object
                    // Object Size                  QWORD        64              // size of Codec List object, including 44 bytes of Codec List Object header
                    // Reserved                     GUID         128             // hardcoded: 86D15241-311D-11D0-A3A4-00A0C90348F6
                    // Codec Entries Count          DWORD        32              // number of entries in Codec Entries array
                    // Codec Entries                array of:    variable        //
                    // * Type                       WORD         16              // 0x0001 = Video Codec, 0x0002 = Audio Codec, 0xFFFF = Unknown Codec
                    // * Codec Name Length          WORD         16              // number of Unicode characters stored in the Codec Name field
                    // * Codec Name                 WCHAR        variable        // array of Unicode characters - name of codec used to create the content
                    // * Codec Description Length   WORD         16              // number of Unicode characters stored in the Codec Description field
                    // * Codec Description          WCHAR        variable        // array of Unicode characters - description of format used to create the content
                    // * Codec Information Length   WORD         16              // number of Unicode characters stored in the Codec Information field
                    // * Codec Information          BYTESTREAM   variable        // opaque array of information bytes about the codec used to create the content

                    $info_asf['codec_list_object'] = array ();
                    $info_asf_codec_list_object      = &$info_asf['codec_list_object'];

                    $info_asf_codec_list_object['objectid_guid'] = $next_object_guidtext;
                    $info_asf_codec_list_object['objectsize']    = $next_object_size;
                    
                    $info_asf_codec_list_object['reserved_guid'] = getid3_asf::BytestringToGUID(substr($asf_header_data, $offset, 16));
                    $offset += 16;
                    
                    if ($info_asf_codec_list_object['reserved_guid'] != '86D15241-311D-11D0-A3A4-00A0C90348F6') {
                        $getid3->warning('codec_list_object.reserved GUID {'.$info_asf_codec_list_object['reserved_guid'].'} does not match expected "getid3_asf::Reserved_1" GUID {86D15241-311D-11D0-A3A4-00A0C90348F6}');
                        break;
                    }
                    
                    $info_asf_codec_list_object['codec_entries_count'] = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 4));
                    $offset += 4;
                    
                    for ($codec_entry_counter = 0; $codec_entry_counter < $info_asf_codec_list_object['codec_entries_count']; $codec_entry_counter++) {
                        
                        $info_asf_codec_list_object['codec_entries'][$codec_entry_counter] = array ();
                        $info_asf_codec_list_object_codecentries_current = &$info_asf_codec_list_object['codec_entries'][$codec_entry_counter];

                        $info_asf_codec_list_object_codecentries_current['type_raw'] = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 2));
                        $offset += 2;
                        
                        $info_asf_codec_list_object_codecentries_current['type'] = getid3_asf::ASFCodecListObjectTypeLookup($info_asf_codec_list_object_codecentries_current['type_raw']);

                        $codec_name_length = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 2)) * 2; // 2 bytes per character
                        $offset += 2;
                        
                        $info_asf_codec_list_object_codecentries_current['name'] = substr($asf_header_data, $offset, $codec_name_length);
                        $offset += $codec_name_length;

                        $codec_description_length = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 2)) * 2; // 2 bytes per character
                        $offset += 2;
                        
                        $info_asf_codec_list_object_codecentries_current['description'] = substr($asf_header_data, $offset, $codec_description_length);
                        $offset += $codec_description_length;

                        $codec_information_length = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 2));
                        $offset += 2;
                        
                        $info_asf_codec_list_object_codecentries_current['information'] = substr($asf_header_data, $offset, $codec_information_length);
                        $offset += $codec_information_length;

                        if ($info_asf_codec_list_object_codecentries_current['type_raw'] == 2) {
                            
                            // audio codec
                            if (strpos($info_asf_codec_list_object_codecentries_current['description'], ',') === false) {
                                throw new getid3_exception('[asf][codec_list_object][codec_entries]['.$codec_entry_counter.'][description] expected to contain comma-seperated list of parameters: "'.$info_asf_codec_list_object_codecentries_current['description'].'"');
                            }
                            list($audio_codec_bitrate, $audio_codec_frequency, $audio_codec_channels) = explode(',', $this->TrimConvert($info_asf_codec_list_object_codecentries_current['description']));
                            $info_audio['codec'] = $this->TrimConvert($info_asf_codec_list_object_codecentries_current['name']);

                            if (!isset($info_audio['bitrate']) && strstr($audio_codec_bitrate, 'kbps')) {
                                $info_audio['bitrate'] = (int)(trim(str_replace('kbps', '', $audio_codec_bitrate)) * 1000);
                            }

                            if (!isset($info_video['bitrate']) && isset($info_audio['bitrate']) && isset($info_asf['file_properties_object']['max_bitrate']) && ($info_asf_codec_list_object['codec_entries_count'] > 1)) {
                                $info_video['bitrate'] = $info_asf['file_properties_object']['max_bitrate'] - $info_audio['bitrate'];
                            }
                            
                            if (!@$info_video['bitrate'] && @$info_audio['bitrate'] && @$getid3->info['bitrate']) {
								$info_video['bitrate'] = $getid3->info['bitrate'] - $info_audio['bitrate'];
							}

                            $audio_codec_frequency = (int)trim(str_replace('kHz', '', $audio_codec_frequency));

                            static $sample_rate_lookup = array (
                                8  =>  8000,    8000 =>  8000,
                                11 => 11025,   11025 => 11025,
                                12 => 12000,   12000 => 12000,
                                16 => 16000,   16000 => 16000,
                                22 => 22050,   22050 => 22050,
                                24 => 24000,   24000 => 24000,
                                32 => 32000,   32000 => 32000,
                                44 => 44100,   44100 => 44100,
                                48 => 48000,   48000 => 48000,
                            );

                            $info_audio['sample_rate'] = @$sample_rate_lookup[$audio_codec_frequency];

                            if (!$info_audio['sample_rate']) {
                                $getid3->warning('unknown frequency: "'.$audio_codec_frequency.'" ('.$this->TrimConvert($info_asf_codec_list_object_codecentries_current['description']).')');
                                break;
                            }

                            if (!isset($info_audio['channels'])) {
                                if (strstr($audio_codec_channels, 'stereo')) {
                                    $info_audio['channels'] = 2;
                                } elseif (strstr($audio_codec_channels, 'mono')) {
                                    $info_audio['channels'] = 1;
                                }
                            }
                        }
                    }
                    break;


                case getid3_asf::Script_Command_Object:

                    // Script Command Object: (optional, one only)
                    // Field Name                   Field Type   Size (bits)
                    // Object ID                    GUID         128             // GUID for Script Command object - getid3_asf::Script_Command_Object
                    // Object Size                  QWORD        64              // size of Script Command object, including 44 bytes of Script Command Object header
                    // Reserved                     GUID         128             // hardcoded: 4B1ACBE3-100B-11D0-A39B-00A0C90348F6
                    // Commands Count               WORD         16              // number of Commands structures in the Script Commands Objects
                    // Command Types Count          WORD         16              // number of Command Types structures in the Script Commands Objects
                    // Command Types                array of:    variable        //
                    // * Command Type Name Length   WORD         16              // number of Unicode characters for Command Type Name
                    // * Command Type Name          WCHAR        variable        // array of Unicode characters - name of a type of command
                    // Commands                     array of:    variable        //
                    // * Presentation Time          DWORD        32              // presentation time of that command, in milliseconds
                    // * Type Index                 WORD         16              // type of this command, as a zero-based index into the array of Command Types of this object
                    // * Command Name Length        WORD         16              // number of Unicode characters for Command Name
                    // * Command Name               WCHAR        variable        // array of Unicode characters - name of this command

                    // shortcut
                    $info_asf['script_command_object'] = array ();
                    $info_asf_script_command_object    = &$info_asf['script_command_object'];

                    $info_asf_script_command_object['objectid_guid'] = $next_object_guidtext;
                    $info_asf_script_command_object['objectsize']    = $next_object_size;
                    $info_asf_script_command_object['reserved_guid'] = getid3_asf::BytestringToGUID(substr($asf_header_data, $offset, 16));
                    $offset += 16;
                    
                    if ($info_asf_script_command_object['reserved_guid'] != '4B1ACBE3-100B-11D0-A39B-00A0C90348F6') {
                        $getid3->warning('script_command_object.reserved GUID {'.$info_asf_script_command_object['reserved_guid'].'} does not match expected GUID {4B1ACBE3-100B-11D0-A39B-00A0C90348F6}');
                        break;
                    }
                    
                    $info_asf_script_command_object['commands_count']       = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 2));
                    $offset += 2;
                    
                    $info_asf_script_command_object['command_types_count']  = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 2));
                    $offset += 2;
                    
                    for ($command_types_counter = 0; $command_types_counter < $info_asf_script_command_object['command_types_count']; $command_types_counter++) {
                        
                        $command_type_name_length = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 2)) * 2; // 2 bytes per character
                        $offset += 2;
                        
                        $info_asf_script_command_object['command_types'][$command_types_counter]['name'] = substr($asf_header_data, $offset, $command_type_name_length);
                        $offset += $command_type_name_length;
                    }
                    
                    for ($commands_counter = 0; $commands_counter < $info_asf_script_command_object['commands_count']; $commands_counter++) {
                        
                        $info_asf_script_command_object['commands'][$commands_counter]['presentation_time'] = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 4));
                        $offset += 4;
                        
                        $info_asf_script_command_object['commands'][$commands_counter]['type_index']        = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 2));
                        $offset += 2;

                        $command_type_name_length                                                           = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 2)) * 2; // 2 bytes per character
                        $offset += 2;
                        
                        $info_asf_script_command_object['commands'][$commands_counter]['name']              = substr($asf_header_data, $offset, $command_type_name_length);
                        $offset += $command_type_name_length;
                    }
                    break;


                case getid3_asf::Marker_Object:

                    // Marker Object: (optional, one only)
                    // Field Name                   Field Type   Size (bits)
                    // Object ID                    GUID         128             // GUID for Marker object - getid3_asf::Marker_Object
                    // Object Size                  QWORD        64              // size of Marker object, including 48 bytes of Marker Object header
                    // Reserved                     GUID         128             // hardcoded: 4CFEDB20-75F6-11CF-9C0F-00A0C90349CB
                    // Markers Count                DWORD        32              // number of Marker structures in Marker Object
                    // Reserved                     WORD         16              // hardcoded: 0x0000
                    // Name Length                  WORD         16              // number of bytes in the Name field
                    // Name                         WCHAR        variable        // name of the Marker Object
                    // Markers                      array of:    variable        //
                    // * Offset                     QWORD        64              // byte offset into Data Object
                    // * Presentation Time          QWORD        64              // in 100-nanosecond units
                    // * Entry Length               WORD         16              // length in bytes of (Send Time + Flags + Marker Description Length + Marker Description + Padding)
                    // * Send Time                  DWORD        32              // in milliseconds
                    // * Flags                      DWORD        32              // hardcoded: 0x00000000
                    // * Marker Description Length  DWORD        32              // number of bytes in Marker Description field
                    // * Marker Description         WCHAR        variable        // array of Unicode characters - description of marker entry
                    // * Padding                    BYTESTREAM   variable        // optional padding bytes

                    $info_asf['marker_object'] = array ();
                    $info_asf_marker_object    = &$info_asf['marker_object'];

                    $info_asf_marker_object['objectid_guid'] = $next_object_guidtext;
                    $info_asf_marker_object['objectsize']    = $next_object_size;
                    $info_asf_marker_object['reserved_guid'] = getid3_asf::BytestringToGUID(substr($asf_header_data, $offset, 16));
                    $offset += 16;                           
                    
                    if ($info_asf_marker_object['reserved_guid'] != '4CFEDB20-75F6-11CF-9C0F-00A0C90349CB') {
                        $getid3->warning('marker_object.reserved GUID {'.$info_asf_marker_object['reserved_guid'].'} does not match expected GUID {4CFEDB20-75F6-11CF-9C0F-00A0C90349CB}');
                        break;
                    }
                    
                    $info_asf_marker_object['markers_count'] = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 4));
                    $offset += 4;
                    
                    $info_asf_marker_object['reserved_2']    = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 2));
                    $offset += 2;
                    
                    if ($info_asf_marker_object['reserved_2'] != 0) {
                        $getid3->warning('marker_object.reserved_2 ('.getid3_lib::PrintHexBytes($info_asf_marker_object['reserved_2']).') does not match expected value of "0"');
                        break;
                    }
                    
                    $info_asf_marker_object['name_length']   = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 2));
                    $offset += 2;
                    
                    $info_asf_marker_object['name'] = substr($asf_header_data, $offset, $info_asf_marker_object['name_length']);
                    $offset += $info_asf_marker_object['name_length'];
                    
                    for ($markers_counter = 0; $markers_counter < $info_asf_marker_object['markers_count']; $markers_counter++) {
                        
                        getid3_lib::ReadSequence('LittleEndian2Int', $info_asf_marker_object['markers'][$markers_counter], $asf_header_data, $offset,
                            array (
                                'offset'                    => 8,
                                'presentation_time'         => 8,
                                'entry_length'              => 2,
                                'send_time'                 => 4,
                                'flags'                     => 4,
                                'marker_description_length' => 4
                            )
                        );
                        $offset += 30;
                        
                        $info_asf_marker_object['markers'][$markers_counter]['marker_description'] = substr($asf_header_data, $offset, $info_asf_marker_object['markers'][$markers_counter]['marker_description_length']);
                        $offset += $info_asf_marker_object['markers'][$markers_counter]['marker_description_length'];
                        
                        $padding_length = $info_asf_marker_object['markers'][$markers_counter]['entry_length'] - 4 -  4 - 4 - $info_asf_marker_object['markers'][$markers_counter]['marker_description_length'];
                        if ($padding_length > 0) {
                            $info_asf_marker_object['markers'][$markers_counter]['padding'] = substr($asf_header_data, $offset, $padding_length);
                            $offset += $padding_length;
                        }
                    }
                    break;
                    

                case getid3_asf::Bitrate_Mutual_Exclusion_Object:
                    
                    // Bitrate Mutual Exclusion Object: (optional)
                    // Field Name                   Field Type   Size (bits)
                    // Object ID                    GUID         128             // GUID for Bitrate Mutual Exclusion object - getid3_asf::Bitrate_Mutual_Exclusion_Object
                    // Object Size                  QWORD        64              // size of Bitrate Mutual Exclusion object, including 42 bytes of Bitrate Mutual Exclusion Object header
                    // Exlusion Type                GUID         128             // nature of mutual exclusion relationship. one of: (getid3_asf::Mutex_Bitrate, getid3_asf::Mutex_Unknown)
                    // Stream Numbers Count         WORD         16              // number of video streams
                    // Stream Numbers               WORD         variable        // array of mutually exclusive video stream numbers. 1 <= valid <= 127

                    // shortcut
                    $info_asf['bitrate_mutual_exclusion_object'] = array ();
                    $info_asf_bitrate_mutual_exclusion_object    = &$info_asf['bitrate_mutual_exclusion_object'];

                    $info_asf_bitrate_mutual_exclusion_object['objectid_guid'] = $next_object_guidtext;
                    $info_asf_bitrate_mutual_exclusion_object['objectsize']    = $next_object_size;
                    $info_asf_bitrate_mutual_exclusion_object['reserved_guid'] = getid3_asf::BytestringToGUID(substr($asf_header_data, $offset, 16));
                    $offset += 16;
                    
                    if ($info_asf_bitrate_mutual_exclusion_object['reserved_guid'] != getid3_asf::Mutex_Bitrate  &&  $info_asf_bitrate_mutual_exclusion_object['reserved_guid'] != getid3_asf::Mutex_Unknown) {
                        $getid3->warning('bitrate_mutual_exclusion_object.reserved GUID {'.$info_asf_bitrate_mutual_exclusion_object['reserved_guid'].'} does not match expected "getid3_asf::Mutex_Bitrate" GUID {'.getid3_asf::Mutex_Bitrate.'} or  "getid3_asf::Mutex_Unknown" GUID {'.getid3_asf::Mutex_Unknown.'}');
                        break;
                    }
                    
                    $info_asf_bitrate_mutual_exclusion_object['stream_numbers_count'] = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 2));
                    $offset += 2;
                    
                    for ($stream_number_counter = 0; $stream_number_counter < $info_asf_bitrate_mutual_exclusion_object['stream_numbers_count']; $stream_number_counter++) {
                        $info_asf_bitrate_mutual_exclusion_object['stream_numbers'][$stream_number_counter] = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 2));
                        $offset += 2;
                    }
                    break;


                case getid3_asf::Error_Correction_Object:

                    // Error Correction Object: (optional, one only)
                    // Field Name                   Field Type   Size (bits)
                    // Object ID                    GUID         128             // GUID for Error Correction object - getid3_asf::Error_Correction_Object
                    // Object Size                  QWORD        64              // size of Error Correction object, including 44 bytes of Error Correction Object header
                    // Error Correction Type        GUID         128             // type of error correction. one of: (getid3_asf::No_Error_Correction, getid3_asf::Audio_Spread)
                    // Error Correction Data Length DWORD        32              // number of bytes in Error Correction Data field
                    // Error Correction Data        BYTESTREAM   variable        // structure depends on value of Error Correction Type field

                    $info_asf['error_correction_object'] = array ();
                    $info_asf_error_correction_object      = &$info_asf['error_correction_object'];

                    $info_asf_error_correction_object['objectid_guid']         = $next_object_guidtext;
                    $info_asf_error_correction_object['objectsize']            = $next_object_size;
                    $info_asf_error_correction_object['error_correction_type'] = substr($asf_header_data, $offset, 16);
                    $offset += 16;
                    
                    $info_asf_error_correction_object['error_correction_guid']        = getid3_asf::BytestringToGUID($info_asf_error_correction_object['error_correction_type']);
                    $info_asf_error_correction_object['error_correction_data_length'] = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 4));
                    $offset += 4;
                    
                    switch ($info_asf_error_correction_object['error_correction_type_guid']) {
                    
                        case getid3_asf::No_Error_Correction:
                    
                            // should be no data, but just in case there is, skip to the end of the field
                            $offset += $info_asf_error_correction_object['error_correction_data_length'];
                            break;


                        case getid3_asf::Audio_Spread:

                            // Field Name                   Field Type   Size (bits)
                            // Span                         BYTE         8               // number of packets over which audio will be spread.
                            // Virtual Packet Length        WORD         16              // size of largest audio payload found in audio stream
                            // Virtual Chunk Length         WORD         16              // size of largest audio payload found in audio stream
                            // Silence Data Length          WORD         16              // number of bytes in Silence Data field
                            // Silence Data                 BYTESTREAM   variable        // hardcoded: 0x00 * (Silence Data Length) bytes

                            getid3_lib::ReadSequence('LittleEndian2Int', $info_asf_error_correction_object, $asf_header_data, $offset, 
                                array (
                                    'span'                  => 1,
                                    'virtual_packet_length' => 2,
                                    'virtual_chunk_length'  => 2,
                                    'silence_data_length'   => 2
                                )
                            );
                            $offset += 7;
                            
                            $info_asf_error_correction_object['silence_data'] = substr($asf_header_data, $offset, $info_asf_error_correction_object['silence_data_length']);
                            $offset += $info_asf_error_correction_object['silence_data_length'];
                            break;

                        default:
                            $getid3->warning('error_correction_object.error_correction_type GUID {'.$info_asf_error_correction_object['reserved_guid'].'} does not match expected "getid3_asf::No_Error_Correction" GUID {'.getid3_asf::No_Error_Correction.'} or  "getid3_asf::Audio_Spread" GUID {'.getid3_asf::Audio_Spread.'}');
                            break;
                    }

                    break;


                case getid3_asf::Content_Description_Object:

                    // Content Description Object: (optional, one only)
                    // Field Name                   Field Type   Size (bits)
                    // Object ID                    GUID         128             // GUID for Content Description object - getid3_asf::Content_Description_Object
                    // Object Size                  QWORD        64              // size of Content Description object, including 34 bytes of Content Description Object header
                    // Title Length                 WORD         16              // number of bytes in Title field
                    // Author Length                WORD         16              // number of bytes in Author field
                    // Copyright Length             WORD         16              // number of bytes in Copyright field
                    // Description Length           WORD         16              // number of bytes in Description field
                    // Rating Length                WORD         16              // number of bytes in Rating field
                    // Title                        WCHAR        16              // array of Unicode characters - Title
                    // Author                       WCHAR        16              // array of Unicode characters - Author
                    // Copyright                    WCHAR        16              // array of Unicode characters - Copyright
                    // Description                  WCHAR        16              // array of Unicode characters - Description
                    // Rating                       WCHAR        16              // array of Unicode characters - Rating

                    $info_asf['content_description_object'] = array ();
                    $info_asf_content_description_object      = &$info_asf['content_description_object'];

                    $info_asf_content_description_object['objectid_guid'] = $next_object_guidtext;
                    $info_asf_content_description_object['objectsize']    = $next_object_size;
                    
                    getid3_lib::ReadSequence('LittleEndian2Int', $info_asf_content_description_object, $asf_header_data, $offset, 
                        array (
                            'title_length'       => 2,
                            'author_length'      => 2,
                            'copyright_length'   => 2,
                            'description_length' => 2,
                            'rating_length'      => 2
                        )
                    );
                    $offset += 10;
                    
                    $info_asf_content_description_object['title']       = substr($asf_header_data, $offset, $info_asf_content_description_object['title_length']);
                    $offset += $info_asf_content_description_object['title_length'];
                    
                    $info_asf_content_description_object['author']      = substr($asf_header_data, $offset, $info_asf_content_description_object['author_length']);
                    $offset += $info_asf_content_description_object['author_length'];
                    
                    $info_asf_content_description_object['copyright']   = substr($asf_header_data, $offset, $info_asf_content_description_object['copyright_length']);
                    $offset += $info_asf_content_description_object['copyright_length'];
                    
                    $info_asf_content_description_object['description'] = substr($asf_header_data, $offset, $info_asf_content_description_object['description_length']);
                    $offset += $info_asf_content_description_object['description_length'];
                    
                    $info_asf_content_description_object['rating']      = substr($asf_header_data, $offset, $info_asf_content_description_object['rating_length']);
                    $offset += $info_asf_content_description_object['rating_length'];

                    foreach (array ('title'=>'title', 'author'=>'artist', 'copyright'=>'copyright', 'description'=>'comment', 'rating'=>'rating') as $key_to_copy_from => $key_to_copy_to) {
                        if (!empty($info_asf_content_description_object[$key_to_copy_from])) {
                            $info_asf_comments[$key_to_copy_to][] = getid3_asf::TrimTerm($info_asf_content_description_object[$key_to_copy_from]);
                        }
                    }
                    break;


                case getid3_asf::Extended_Content_Description_Object:

                    // Extended Content Description Object: (optional, one only)
                    // Field Name                   Field Type   Size (bits)
                    // Object ID                    GUID         128             // GUID for Extended Content Description object - getid3_asf::Extended_Content_Description_Object
                    // Object Size                  QWORD        64              // size of ExtendedContent Description object, including 26 bytes of Extended Content Description Object header
                    // Content Descriptors Count    WORD         16              // number of entries in Content Descriptors list
                    // Content Descriptors          array of:    variable        //
                    // * Descriptor Name Length     WORD         16              // size in bytes of Descriptor Name field
                    // * Descriptor Name            WCHAR        variable        // array of Unicode characters - Descriptor Name
                    // * Descriptor Value Data Type WORD         16              // Lookup array:
                                                                                    // 0x0000 = Unicode String (variable length)
                                                                                    // 0x0001 = BYTE array     (variable length)
                                                                                    // 0x0002 = BOOL           (DWORD, 32 bits)
                                                                                    // 0x0003 = DWORD          (DWORD, 32 bits)
                                                                                    // 0x0004 = QWORD          (QWORD, 64 bits)
                                                                                    // 0x0005 = WORD           (WORD,  16 bits)
                    // * Descriptor Value Length    WORD         16              // number of bytes stored in Descriptor Value field
                    // * Descriptor Value           variable     variable        // value for Content Descriptor

                    $info_asf['extended_content_description_object'] = array ();
                    $info_asf_extended_content_description_object       = &$info_asf['extended_content_description_object'];

                    $info_asf_extended_content_description_object['objectid_guid']             = $next_object_guidtext;
                    $info_asf_extended_content_description_object['objectsize']                = $next_object_size;
                    $info_asf_extended_content_description_object['content_descriptors_count'] = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 2));
                    $offset += 2;
                    
                    for ($extended_content_descriptors_counter = 0; $extended_content_descriptors_counter < $info_asf_extended_content_description_object['content_descriptors_count']; $extended_content_descriptors_counter++) {
                        
                        $info_asf_extended_content_description_object['content_descriptors'][$extended_content_descriptors_counter] = array ();
                        $info_asf_extended_content_description_object_content_descriptor_current                                  = &$info_asf_extended_content_description_object['content_descriptors'][$extended_content_descriptors_counter];

                        $info_asf_extended_content_description_object_content_descriptor_current['base_offset']  = $offset + 30;
                        $info_asf_extended_content_description_object_content_descriptor_current['name_length']  = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 2));
                        $offset += 2;
                        
                        $info_asf_extended_content_description_object_content_descriptor_current['name']         = substr($asf_header_data, $offset, $info_asf_extended_content_description_object_content_descriptor_current['name_length']);
                        $offset += $info_asf_extended_content_description_object_content_descriptor_current['name_length'];
                        
                        $info_asf_extended_content_description_object_content_descriptor_current['value_type']   = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 2));
                        $offset += 2;
                        
                        $info_asf_extended_content_description_object_content_descriptor_current['value_length'] = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 2));
                        $offset += 2;
                        
                        $info_asf_extended_content_description_object_content_descriptor_current['value']        = substr($asf_header_data, $offset, $info_asf_extended_content_description_object_content_descriptor_current['value_length']);
                        $offset += $info_asf_extended_content_description_object_content_descriptor_current['value_length'];
                        
                        switch ($info_asf_extended_content_description_object_content_descriptor_current['value_type']) {
                            
                            case 0x0000: // Unicode string
                                break;

                            case 0x0001: // BYTE array
                                // do nothing
                                break;

                            case 0x0002: // BOOL
                                $info_asf_extended_content_description_object_content_descriptor_current['value'] = (bool)getid3_lib::LittleEndian2Int($info_asf_extended_content_description_object_content_descriptor_current['value']);
                                break;

                            case 0x0003: // DWORD
                            case 0x0004: // QWORD
                            case 0x0005: // WORD
                                $info_asf_extended_content_description_object_content_descriptor_current['value'] = getid3_lib::LittleEndian2Int($info_asf_extended_content_description_object_content_descriptor_current['value']);
                                break;

                            default:
                                $getid3->warning('extended_content_description.content_descriptors.'.$extended_content_descriptors_counter.'.value_type is invalid ('.$info_asf_extended_content_description_object_content_descriptor_current['value_type'].')');
                                break;
                        }
                        
                        switch ($this->TrimConvert(strtolower($info_asf_extended_content_description_object_content_descriptor_current['name']))) {

                            case 'wm/albumartist':
                            case 'artist':
                                $info_asf_comments['artist'] = array (getid3_asf::TrimTerm($info_asf_extended_content_description_object_content_descriptor_current['value']));
                                break;


                            case 'wm/albumtitle':
                            case 'album':
                                $info_asf_comments['album']  = array (getid3_asf::TrimTerm($info_asf_extended_content_description_object_content_descriptor_current['value']));
                                break;


                            case 'wm/genre':
                            case 'genre':
                                $genre = getid3_asf::TrimTerm($info_asf_extended_content_description_object_content_descriptor_current['value']);
                                $info_asf_comments['genre'] = array ($genre);
                                break;


                            case 'wm/tracknumber':
                            case 'tracknumber':
                                $info_asf_comments['track'] = array (intval(getid3_asf::TrimTerm($info_asf_extended_content_description_object_content_descriptor_current['value'])));
                                break;


                            case 'wm/track':
                                if (empty($info_asf_comments['track'])) {
                                    $info_asf_comments['track'] = array (1 + $this->TrimConvert($info_asf_extended_content_description_object_content_descriptor_current['value']));
                                }
                                break;


                            case 'wm/year':
                            case 'year':
                            case 'date':
                                $info_asf_comments['year'] = array ( getid3_asf::TrimTerm($info_asf_extended_content_description_object_content_descriptor_current['value']));
                                break;
                                
                                
                            case 'wm/lyrics':
                            case 'lyrics':
                                $info_asf_comments['lyrics'] = array ( getid3_asf::TrimTerm($info_asf_extended_content_description_object_content_descriptor_current['value']));
  	                            break;
                            
                            
                            case 'isvbr':
                                if ($info_asf_extended_content_description_object_content_descriptor_current['value']) {
                                    $info_audio['bitrate_mode'] = 'vbr';
                                    $info_video['bitrate_mode'] = 'vbr';
                                }
                                break;


                            case 'id3':
								
								// id3v2 parsing might not be enabled
								if (class_exists('getid3_id3v2')) {
									
									// Clone getid3 
                                    $clone = clone $getid3;
                                    
                                    // Analyse clone by string
                                    $id3v2 = new getid3_id3v2($clone);
                                    $id3v2->AnalyzeString($info_asf_extended_content_description_object_content_descriptor_current['value']);
                                    
                                    // Import from clone and destroy
                                    $getid3->info['id3v2'] = $clone->info['id3v2'];
                                    $getid3->warnings($clone->warnings());
                                    unset($clone);
								}
								break;


                            case 'wm/encodingtime':
                                $info_asf_extended_content_description_object_content_descriptor_current['encoding_time_unix'] = getid3_asf::FiletimeToUNIXtime($info_asf_extended_content_description_object_content_descriptor_current['value']);
                                $info_asf_comments['encoding_time_unix'] = array ($info_asf_extended_content_description_object_content_descriptor_current['encoding_time_unix']);
                                break;


                            case 'wm/picture':
                                
                                //typedef struct _WMPicture{
                                //  LPWSTR  pwszMIMEType;
                                //  BYTE  bPictureType;
                                //  LPWSTR  pwszDescription;
                                //  DWORD  dwDataLen;
                                //  BYTE*  pbData;
                                //} WM_PICTURE;

                                $info_asf_extended_content_description_object_content_descriptor_current['image_type_id'] = getid3_lib::LittleEndian2Int($info_asf_extended_content_description_object_content_descriptor_current['value']{0});
                                $info_asf_extended_content_description_object_content_descriptor_current['image_type']    = getid3_asf::WMpictureTypeLookup($info_asf_extended_content_description_object_content_descriptor_current['image_type_id']);
                                $info_asf_extended_content_description_object_content_descriptor_current['image_size']    = getid3_lib::LittleEndian2Int(substr($info_asf_extended_content_description_object_content_descriptor_current['value'], 1, 4));
                                $info_asf_extended_content_description_object_content_descriptor_current['image_mime']   = '';
                                
                                $wm_picture_offset = 5;
                                
                                do {
                                    $next_byte_pair = substr($info_asf_extended_content_description_object_content_descriptor_current['value'], $wm_picture_offset, 2);
                                    $wm_picture_offset += 2;
                                    $info_asf_extended_content_description_object_content_descriptor_current['image_mime'] .= $next_byte_pair;
                                } while ($next_byte_pair !== "\x00\x00");

                                $info_asf_extended_content_description_object_content_descriptor_current['image_description'] = '';
                                
                                do {
                                    $next_byte_pair = substr($info_asf_extended_content_description_object_content_descriptor_current['value'], $wm_picture_offset, 2);
                                    $wm_picture_offset += 2;
                                    $info_asf_extended_content_description_object_content_descriptor_current['image_description'] .= $next_byte_pair;
                                } while ($next_byte_pair !== "\x00\x00");

                                $info_asf_extended_content_description_object_content_descriptor_current['dataoffset'] = $wm_picture_offset;
                                $info_asf_extended_content_description_object_content_descriptor_current['data']       = substr($info_asf_extended_content_description_object_content_descriptor_current['value'], $wm_picture_offset);
                                unset($info_asf_extended_content_description_object_content_descriptor_current['value']);
                                break;

                            default:
                                switch ($info_asf_extended_content_description_object_content_descriptor_current['value_type']) {
                                    case 0: // Unicode string
                                        if (substr($this->TrimConvert($info_asf_extended_content_description_object_content_descriptor_current['name']), 0, 3) == 'WM/') {
                                            $info_asf_comments[str_replace('wm/', '', strtolower($this->TrimConvert($info_asf_extended_content_description_object_content_descriptor_current['name'])))] = array (getid3_asf::TrimTerm($info_asf_extended_content_description_object_content_descriptor_current['value']));
                                        }
                                        break;

                                    case 1:
                                        break;
                                }
                                break;
                        }

                    }
                    break;


                case getid3_asf::Stream_Bitrate_Properties_Object:

                    // Stream Bitrate Properties Object: (optional, one only)
                    // Field Name                   Field Type   Size (bits)
                    // Object ID                    GUID         128             // GUID for Stream Bitrate Properties object - getid3_asf::Stream_Bitrate_Properties_Object
                    // Object Size                  QWORD        64              // size of Extended Content Description object, including 26 bytes of Stream Bitrate Properties Object header
                    // Bitrate Records Count        WORD         16              // number of records in Bitrate Records
                    // Bitrate Records              array of:    variable        //
                    // * Flags                      WORD         16              //
                    // * * Stream Number            bits         7  (0x007F)     // number of this stream
                    // * * Reserved                 bits         9  (0xFF80)     // hardcoded: 0
                    // * Average Bitrate            DWORD        32              // in bits per second

                    // shortcut
                    $info_asf['stream_bitrate_properties_object'] = array ();
                    $info_asf_stream_bitrate_properties_object = &$info_asf['stream_bitrate_properties_object'];

                    $info_asf_stream_bitrate_properties_object['objectid_guid']         = $next_object_guidtext;
                    $info_asf_stream_bitrate_properties_object['objectsize']            = $next_object_size;
                    $info_asf_stream_bitrate_properties_object['bitrate_records_count'] = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 2));
                    $offset += 2;
                    
                    for ($bitrate_records_counter = 0; $bitrate_records_counter < $info_asf_stream_bitrate_properties_object['bitrate_records_count']; $bitrate_records_counter++) {
                    
                        $info_asf_stream_bitrate_properties_object['bitrate_records'][$bitrate_records_counter]['flags_raw'] = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 2));
                        $offset += 2;
                        
                        $info_asf_stream_bitrate_properties_object['bitrate_records'][$bitrate_records_counter]['flags']['stream_number'] = $info_asf_stream_bitrate_properties_object['bitrate_records'][$bitrate_records_counter]['flags_raw'] & 0x007F;
                        
                        $info_asf_stream_bitrate_properties_object['bitrate_records'][$bitrate_records_counter]['bitrate'] = getid3_lib::LittleEndian2Int(substr($asf_header_data, $offset, 4));
                        $offset += 4;
                    }
                    break;


                case getid3_asf::Padding_Object:

                    // Padding Object: (optional)
                    // Field Name                   Field Type   Size (bits)
                    // Object ID                    GUID         128             // GUID for Padding object - getid3_asf::Padding_Object
                    // Object Size                  QWORD        64              // size of Padding object, including 24 bytes of ASF Padding Object header
                    // Padding Data                 BYTESTREAM   variable        // ignore

                    // shortcut
                    $info_asf['padding_object'] = array ();
                    $info_asf_paddingobject     = &$info_asf['padding_object'];

                    $info_asf_paddingobject['objectid_guid']  = $next_object_guidtext;
                    $info_asf_paddingobject['objectsize']     = $next_object_size;
                    $info_asf_paddingobject['padding_length'] = $info_asf_paddingobject['objectsize'] - 16 - 8;
                    $info_asf_paddingobject['padding']        = substr($asf_header_data, $offset, $info_asf_paddingobject['padding_length']);
                    $offset += ($next_object_size - 16 - 8);
                    break;


                case getid3_asf::Extended_Content_Encryption_Object:
                case getid3_asf::Content_Encryption_Object:

                    // WMA DRM - just ignore
                    $offset += ($next_object_size - 16 - 8);
                    break;


                default:

                    // Implementations shall ignore any standard or non-standard object that they do not know how to handle.
                    if (getid3_asf::GUIDname($next_object_guidtext)) {
                        $getid3->warning('unhandled GUID "'.getid3_asf::GUIDname($next_object_guidtext).'" {'.$next_object_guidtext.'} in ASF header at offset '.($offset - 16 - 8));
                    } else {
                        $getid3->warning('unknown GUID {'.$next_object_guidtext.'} in ASF header at offset '.($offset - 16 - 8));
                    }
                    $offset += ($next_object_size - 16 - 8);
                    break;
            }
        }
        
        if (isset($info_asf_stream_bitrate_properties['bitrate_records_count'])) {
            $asf_bitrate_audio = 0;
            $asf_bitrate_video = 0;

            for ($bitrate_records_counter = 0; $bitrate_records_counter < $info_asf_stream_bitrate_properties['bitrate_records_count']; $bitrate_records_counter++) {
                if (isset($info_asf_codec_list_object['codec_entries'][$bitrate_records_counter])) {
                    switch ($info_asf_codec_list_object['codec_entries'][$bitrate_records_counter]['type_raw']) {
                        
                        case 1:
                            $asf_bitrate_video += $info_asf_stream_bitrate_properties['bitrate_records'][$bitrate_records_counter]['bitrate'];
                            break;
                            
                        case 2:
                            $asf_bitrate_audio += $info_asf_stream_bitrate_properties['bitrate_records'][$bitrate_records_counter]['bitrate'];
                            break;
                    }
                }
            }
            if ($asf_bitrate_audio > 0) {
                $info_audio['bitrate'] = $asf_bitrate_audio;
            }
            if ($asf_bitrate_video > 0) {
                $info_video['bitrate'] = $asf_bitrate_video;
            }
        }
        
        if (isset($info_asf['stream_properties_object']) && is_array($info_asf['stream_properties_object'])) {
            
            $info_audio['bitrate'] = 0;
            $info_video['bitrate'] = 0;

            foreach ($info_asf['stream_properties_object'] as $stream_number => $stream_data) {
                
                switch ($stream_data['stream_type_guid']) {
                    
                    case getid3_asf::Audio_Media:
                    
                        // Field Name                   Field Type   Size (bits)
                        // Codec ID / Format Tag        WORD         16              // unique ID of audio codec - defined as wFormatTag field of WAVEFORMATEX structure
                        // Number of Channels           WORD         16              // number of channels of audio - defined as nChannels field of WAVEFORMATEX structure
                        // Samples Per Second           DWORD        32              // in Hertz - defined as nSamplesPerSec field of WAVEFORMATEX structure
                        // Average number of Bytes/sec  DWORD        32              // bytes/sec of audio stream  - defined as nAvgBytesPerSec field of WAVEFORMATEX structure
                        // Block Alignment              WORD         16              // block size in bytes of audio codec - defined as nBlockAlign field of WAVEFORMATEX structure
                        // Bits per sample              WORD         16              // bits per sample of mono data. set to zero for variable bitrate codecs. defined as wBitsPerSample field of WAVEFORMATEX structure
                        // Codec Specific Data Size     WORD         16              // size in bytes of Codec Specific Data buffer - defined as cbSize field of WAVEFORMATEX structure
                        // Codec Specific Data          BYTESTREAM   variable        // array of codec-specific data bytes

                        // shortcut
                        $info_asf['audio_media'][$stream_number] = array ();
                        $info_asf_audio_media_current_stream     = &$info_asf['audio_media'][$stream_number];

                        $audio_media_offset = 0;

                        $info_asf_audio_media_current_stream = getid3_riff::RIFFparseWAVEFORMATex(substr($stream_data['type_specific_data'], $audio_media_offset, 16));

                        $audio_media_offset += 16;

                        $info_audio['lossless'] = false;
                        switch ($info_asf_audio_media_current_stream['raw']['wFormatTag']) {
                            case 0x0001: // PCM
                            case 0x0163: // WMA9 Lossless
                                $info_audio['lossless'] = true;
                                break;
                        }

                        if (!empty($info_asf['stream_bitrate_properties_object']['bitrate_records'])) {
							foreach ($info_asf['stream_bitrate_properties_object']['bitrate_records'] as $data_array) {
								if (@$data_array['flags']['stream_number'] == $stream_number) {
									$info_asf_audio_media_current_stream['bitrate'] = $data_array['bitrate'];
									$info_audio['bitrate'] += $data_array['bitrate'];
									break;
								}
							}
						} else {
							if (@$info_asf_audio_media_current_stream['bytes_sec']) {
								$info_audio['bitrate'] += $info_asf_audio_media_current_stream['bytes_sec'] * 8;
							} elseif (@$info_asf_audio_media_current_stream['bitrate']) {
								$info_audio['bitrate'] += $info_asf_audio_media_current_stream['bitrate'];
							}
						}
                        
                        $info_audio['streams'][$stream_number]                  = $info_asf_audio_media_current_stream;
                        $info_audio['streams'][$stream_number]['wformattag']    = $info_asf_audio_media_current_stream['raw']['wFormatTag'];
                        $info_audio['streams'][$stream_number]['lossless']      = $info_audio['lossless'];
                        $info_audio['streams'][$stream_number]['bitrate']       = $info_audio['bitrate'];
                        unset($info_audio['streams'][$stream_number]['raw']);

                        $info_asf_audio_media_current_stream['codec_data_size'] = getid3_lib::LittleEndian2Int(substr($stream_data['type_specific_data'], $audio_media_offset, 2));
                        $audio_media_offset += 2;
                        
                        $info_asf_audio_media_current_stream['codec_data']      = substr($stream_data['type_specific_data'], $audio_media_offset, $info_asf_audio_media_current_stream['codec_data_size']);
                        $audio_media_offset += $info_asf_audio_media_current_stream['codec_data_size'];
                        break;


                    case getid3_asf::Video_Media:

                        // Field Name                   Field Type   Size (bits)
                        // Encoded Image Width          DWORD        32              // width of image in pixels
                        // Encoded Image Height         DWORD        32              // height of image in pixels
                        // Reserved Flags               BYTE         8               // hardcoded: 0x02
                        // Format Data Size             WORD         16              // size of Format Data field in bytes
                        // Format Data                  array of:    variable        //
                        // * Format Data Size           DWORD        32              // number of bytes in Format Data field, in bytes - defined as biSize field of BITMAPINFOHEADER structure
                        // * Image Width                LONG         32              // width of encoded image in pixels - defined as biWidth field of BITMAPINFOHEADER structure
                        // * Image Height               LONG         32              // height of encoded image in pixels - defined as biHeight field of BITMAPINFOHEADER structure
                        // * Reserved                   WORD         16              // hardcoded: 0x0001 - defined as biPlanes field of BITMAPINFOHEADER structure
                        // * Bits Per Pixel Count       WORD         16              // bits per pixel - defined as biBitCount field of BITMAPINFOHEADER structure
                        // * Compression ID             FOURCC       32              // fourcc of video codec - defined as biCompression field of BITMAPINFOHEADER structure
                        // * Image Size                 DWORD        32              // image size in bytes - defined as biSizeImage field of BITMAPINFOHEADER structure
                        // * Horizontal Pixels / Meter  DWORD        32              // horizontal resolution of target device in pixels per meter - defined as biXPelsPerMeter field of BITMAPINFOHEADER structure
                        // * Vertical Pixels / Meter    DWORD        32              // vertical resolution of target device in pixels per meter - defined as biYPelsPerMeter field of BITMAPINFOHEADER structure
                        // * Colors Used Count          DWORD        32              // number of color indexes in the color table that are actually used - defined as biClrUsed field of BITMAPINFOHEADER structure
                        // * Important Colors Count     DWORD        32              // number of color index required for displaying bitmap. if zero, all colors are required. defined as biClrImportant field of BITMAPINFOHEADER structure
                        // * Codec Specific Data        BYTESTREAM   variable        // array of codec-specific data bytes

                        $info_asf['video_media'][$stream_number] = array ();
                        $info_asf_video_media_current_stream     = &$info_asf['video_media'][$stream_number];

                        getid3_lib::ReadSequence('LittleEndian2Int', $info_asf_video_media_current_stream, $stream_data['type_specific_data'], 0, 
                            array (
                                'image_width'     => 4,
                                'image_height'    => 4,
                                'flags'           => 1,
                                'format_data_size'=> 2
                            )
                        );
                        
                        getid3_lib::ReadSequence('LittleEndian2Int', $info_asf_video_media_current_stream['format_data'], $stream_data['type_specific_data'], 11, 
                            array (
                                'format_data_size' => 4,
                                'image_width'      => 4,
                                'image_height'     => 4,
                                'reserved'         => 2,
                                'bits_per_pixel'   => 2,
                                'codec_fourcc'     => -4,
                                'image_size'       => 4,
                                'horizontal_pels'  => 4,
                                'vertical_pels'    => 4,
                                'colors_used'      => 4,
                                'colors_important' => 4
                            )
                        );
                            
                        $info_asf_video_media_current_stream['format_data']['codec_data'] = substr($stream_data['type_specific_data'], 51);
                        
                        if (!empty($info_asf['stream_bitrate_properties_object']['bitrate_records'])) {
                            foreach ($info_asf['stream_bitrate_properties_object']['bitrate_records'] as $data_array) {
                                if (@$data_array['flags']['stream_number'] == $stream_number) {
                                    $info_asf_video_media_current_stream['bitrate']   = $data_array['bitrate'];
                                    $info_video['streams'][$stream_number]['bitrate'] = $data_array['bitrate'];
                                    $info_video['bitrate'] += $data_array['bitrate'];
                                    
                                    break;
                                }
                            }
                        }
                        
                        $info_asf_video_media_current_stream['format_data']['codec'] = getid3_riff::RIFFfourccLookup($info_asf_video_media_current_stream['format_data']['codec_fourcc']);

                        $info_video['streams'][$stream_number]['fourcc']          = $info_asf_video_media_current_stream['format_data']['codec_fourcc'];
                        $info_video['streams'][$stream_number]['codec']           = $info_asf_video_media_current_stream['format_data']['codec'];
                        $info_video['streams'][$stream_number]['resolution_x']    = $info_asf_video_media_current_stream['image_width'];
                        $info_video['streams'][$stream_number]['resolution_y']    = $info_asf_video_media_current_stream['image_height'];
                        $info_video['streams'][$stream_number]['bits_per_sample'] = $info_asf_video_media_current_stream['format_data']['bits_per_pixel'];
                        break;

                    default:
                        break;
                }
            }
        }

        while (ftell($getid3->fp) < $getid3->info['avdataend']) {
            
            $next_object_data_header = fread($getid3->fp, 24);
            $offset = 0;
            
            $next_object_guid = substr($next_object_data_header, 0, 16);
            $offset += 16;
            
            $next_object_guidtext = getid3_asf::BytestringToGUID($next_object_guid);
            $next_object_size     = getid3_lib::LittleEndian2Int(substr($next_object_data_header, $offset, 8));
            $offset += 8;

            switch ($next_object_guidtext) {
                
                case getid3_asf::Data_Object:
                
                    // Data Object: (mandatory, one only)
                    // Field Name                       Field Type   Size (bits)
                    // Object ID                        GUID         128             // GUID for Data object - getid3_asf::Data_Object
                    // Object Size                      QWORD        64              // size of Data object, including 50 bytes of Data Object header. may be 0 if FilePropertiesObject.BroadcastFlag == 1
                    // File ID                          GUID         128             // unique identifier. identical to File ID field in Header Object
                    // Total Data Packets               QWORD        64              // number of Data Packet entries in Data Object. invalid if FilePropertiesObject.BroadcastFlag == 1
                    // Reserved                         WORD         16              // hardcoded: 0x0101

                    // shortcut
                    $info_asf['data_object'] = array ();
                    $info_asf_data_object    = &$info_asf['data_object'];

                    $data_object_data = $next_object_data_header.fread($getid3->fp, 50 - 24);
                    $offset = 24;

                    $info_asf_data_object['objectid_guid']      = $next_object_guidtext;
                    $info_asf_data_object['objectsize']         = $next_object_size;

                    $info_asf_data_object['fileid_guid']        = getid3_asf::BytestringToGUID(substr($data_object_data, $offset, 16));
                    $offset += 16;
                    
                    $info_asf_data_object['total_data_packets'] = getid3_lib::LittleEndian2Int(substr($data_object_data, $offset, 8));
                    $offset += 8;
                    
                    $info_asf_data_object['reserved']           = getid3_lib::LittleEndian2Int(substr($data_object_data, $offset, 2));
                    $offset += 2;
                    
                    if ($info_asf_data_object['reserved'] != 0x0101) {
                        $getid3->warning('data_object.reserved ('.getid3_lib::PrintHexBytes($info_asf_data_object['reserved']).') does not match expected value of "0x0101"');
                        break;
                    }

                    // Data Packets                     array of:    variable        //
                    // * Error Correction Flags         BYTE         8               //
                    // * * Error Correction Data Length bits         4               // if Error Correction Length Type == 00, size of Error Correction Data in bytes, else hardcoded: 0000
                    // * * Opaque Data Present          bits         1               //
                    // * * Error Correction Length Type bits         2               // number of bits for size of the error correction data. hardcoded: 00
                    // * * Error Correction Present     bits         1               // If set, use Opaque Data Packet structure, else use Payload structure
                    // * Error Correction Data

                    $getid3->info['avdataoffset'] = ftell($getid3->fp);
                    fseek($getid3->fp, ($info_asf_data_object['objectsize'] - 50), SEEK_CUR); // skip actual audio/video data
                    $getid3->info['avdataend'] = ftell($getid3->fp);
                    break;


                case getid3_asf::Simple_Index_Object:

                    // Simple Index Object: (optional, recommended, one per video stream)
                    // Field Name                       Field Type   Size (bits)
                    // Object ID                        GUID         128             // GUID for Simple Index object - getid3_asf::Data_Object
                    // Object Size                      QWORD        64              // size of Simple Index object, including 56 bytes of Simple Index Object header
                    // File ID                          GUID         128             // unique identifier. may be zero or identical to File ID field in Data Object and Header Object
                    // Index Entry Time Interval        QWORD        64              // interval between index entries in 100-nanosecond units
                    // Maximum Packet Count             DWORD        32              // maximum packet count for all index entries
                    // Index Entries Count              DWORD        32              // number of Index Entries structures
                    // Index Entries                    array of:    variable        //
                    // * Packet Number                  DWORD        32              // number of the Data Packet associated with this index entry
                    // * Packet Count                   WORD         16              // number of Data Packets to sent at this index entry

                    // shortcut
                    $info_asf['simple_index_object'] = array ();
                    $info_asf_simple_index_object    = &$info_asf['simple_index_object'];

                    $info_asf_simple_index_object['objectid_guid'] = $next_object_guidtext;
                    $info_asf_simple_index_object['objectsize']    = $next_object_size;

                    $simple_index_object_data = $next_object_data_header.fread($getid3->fp, 56 - 24);
                    
                    $info_asf_simple_index_object['fileid_guid'] = getid3_asf::BytestringToGUID(substr($simple_index_object_data, 24, 16));

                    getid3_lib::ReadSequence('LittleEndian2Int', $info_asf_simple_index_object, $simple_index_object_data, 40,
                        array (
                            'index_entry_time_interval' => 8,
                            'maximum_packet_count'      => 4,
                            'index_entries_count'       => 4
                        )
                    );
                    
                    $offset = 56;

                    $index_entries_data = $simple_index_object_data.fread($getid3->fp, 6 * $info_asf_simple_index_object['index_entries_count']);
                    for ($index_entries_counter = 0; $index_entries_counter < $info_asf_simple_index_object['index_entries_count']; $index_entries_counter++) {
                    
                        $info_asf_simple_index_object['index_entries'][$index_entries_counter]['packet_number'] = getid3_lib::LittleEndian2Int(substr($index_entries_data, $offset, 4));
                        $offset += 4;
                    
                        $info_asf_simple_index_object['index_entries'][$index_entries_counter]['packet_count']  = getid3_lib::LittleEndian2Int(substr($index_entries_data, $offset, 4));
                        $offset += 2;
                    }
                    break;


                case getid3_asf::Index_Object:

                    // 6.2 ASF top-level Index Object (optional but recommended when appropriate, 0 or 1)
                    // Field Name                       Field Type   Size (bits)
                    // Object ID                        GUID         128             // GUID for the Index Object - getid3_asf::Index_Object
                    // Object Size                      QWORD        64              // Specifies the size, in bytes, of the Index Object, including at least 34 bytes of Index Object header
                    // Index Entry Time Interval        DWORD        32              // Specifies the time interval between each index entry in ms.
                    // Index Specifiers Count           WORD         16              // Specifies the number of Index Specifiers structures in this Index Object.
                    // Index Blocks Count               DWORD        32              // Specifies the number of Index Blocks structures in this Index Object.

                    // Index Entry Time Interval        DWORD        32              // Specifies the time interval between index entries in milliseconds.  This value cannot be 0.
                    // Index Specifiers Count           WORD         16              // Specifies the number of entries in the Index Specifiers list.  Valid values are 1 and greater.
                    // Index Specifiers                 array of:    varies          //
                    // * Stream Number                  WORD         16              // Specifies the stream number that the Index Specifiers refer to. Valid values are between 1 and 127.
                    // * Index Type                     WORD         16              // Specifies Index Type values as follows:
                                                                                     //   1 = Nearest Past Data Packet - indexes point to the data packet whose presentation time is closest to the index entry time.
                                                                                     //   2 = Nearest Past Media Object - indexes point to the closest data packet containing an entire object or first fragment of an object.
                                                                                     //   3 = Nearest Past Cleanpoint. - indexes point to the closest data packet containing an entire object (or first fragment of an object) that has the Cleanpoint Flag set.
                                                                                     //   Nearest Past Cleanpoint is the most common type of index.
                    // Index Entry Count                DWORD        32              // Specifies the number of Index Entries in the block.
                    // * Block Positions                QWORD        varies          // Specifies a list of byte offsets of the beginnings of the blocks relative to the beginning of the first Data Packet (i.e., the beginning of the Data Object + 50 bytes). The number of entries in this list is specified by the value of the Index Specifiers Count field. The order of those byte offsets is tied to the order in which Index Specifiers are listed.
                    // * Index Entries                  array of:    varies          //
                    // * * Offsets                      DWORD        varies          // An offset value of 0xffffffff indicates an invalid offset value

                    // shortcut
                    $info_asf['asf_index_object'] = array ();
                    $info_asf_asf_index_object      = &$info_asf['asf_index_object'];

                    $asf_index_object_data = $next_object_data_header.fread($getid3->fp, 34 - 24);

                    $info_asf_asf_index_object['objectid_guid'] = $next_object_guidtext;
                    $info_asf_asf_index_object['objectsize']    = $next_object_size;

                    getid3_lib::ReadSequence('LittleEndian2Int', $info_asf_asf_index_object, $asf_index_object_data, 24, 
                        array (
                            'entry_time_interval'    =>4,
                            'index_specifiers_count' =>2,
                            'index_blocks_count'     =>4
                        )
                    );

                    $offset = 34;

                    $asf_index_object_data .= fread($getid3->fp, 4 * $info_asf_asf_index_object['index_specifiers_count']);
                    
                    for ($index_specifiers_counter = 0; $index_specifiers_counter < $info_asf_asf_index_object['index_specifiers_count']; $index_specifiers_counter++) {
                    
                        $index_specifier_stream_number = getid3_lib::LittleEndian2Int(substr($asf_index_object_data, $offset, 2));
                        $offset += 2;
                        
                        $info_asf_asf_index_object['index_specifiers'][$index_specifiers_counter]['stream_number']   = $index_specifier_stream_number;
                        
                        $info_asf_asf_index_object['index_specifiers'][$index_specifiers_counter]['index_type']      = getid3_lib::LittleEndian2Int(substr($asf_index_object_data, $offset, 2));
                        $offset += 2;
                        
                        $info_asf_asf_index_object['index_specifiers'][$index_specifiers_counter]['index_type_text'] = getid3_asf::ASFIndexObjectIndexTypeLookup($info_asf_asf_index_object['index_specifiers'][$index_specifiers_counter]['index_type']);
                    }

                    $asf_index_object_data .= fread($getid3->fp, 4);
                    $info_asf_asf_index_object['index_entry_count'] = getid3_lib::LittleEndian2Int(substr($asf_index_object_data, $offset, 4));
                    $offset += 4;

                    $asf_index_object_data .= fread($getid3->fp, 8 * $info_asf_asf_index_object['index_specifiers_count']);
                    
                    for ($index_specifiers_counter = 0; $index_specifiers_counter < $info_asf_asf_index_object['index_specifiers_count']; $index_specifiers_counter++) {
                        $info_asf_asf_index_object['block_positions'][$index_specifiers_counter] = getid3_lib::LittleEndian2Int(substr($asf_index_object_data, $offset, 8));
                        $offset += 8;
                    }

                    $asf_index_object_data .= fread($getid3->fp, 4 * $info_asf_asf_index_object['index_specifiers_count'] * $info_asf_asf_index_object['index_entry_count']);
                    
                    for ($index_entry_counter = 0; $index_entry_counter < $info_asf_asf_index_object['index_entry_count']; $index_entry_counter++) {
                        for ($index_specifiers_counter = 0; $index_specifiers_counter < $info_asf_asf_index_object['index_specifiers_count']; $index_specifiers_counter++) {
                            $info_asf_asf_index_object['offsets'][$index_specifiers_counter][$index_entry_counter] = getid3_lib::LittleEndian2Int(substr($asf_index_object_data, $offset, 4));
                            $offset += 4;
                        }
                    }
                    break;


                default:
                    
                    // Implementations shall ignore any standard or non-standard object that they do not know how to handle.
                    if (getid3_asf::GUIDname($next_object_guidtext)) {
                        $getid3->warning('unhandled GUID "'.getid3_asf::GUIDname($next_object_guidtext).'" {'.$next_object_guidtext.'} in ASF body at offset '.($offset - 16 - 8));
                    } else {
                        $getid3->warning('unknown GUID {'.$next_object_guidtext.'} in ASF body at offset '.(ftell($getid3->fp) - 16 - 8));
                    }
                    fseek($getid3->fp, ($next_object_size - 16 - 8), SEEK_CUR);
                    break;
            }
        }

        if (isset($info_asf_codec_list_object['codec_entries']) && is_array($info_asf_codec_list_object['codec_entries'])) {
            foreach ($info_asf_codec_list_object['codec_entries'] as $stream_number => $stream_data) {
                switch ($stream_data['information']) {
                    case 'WMV1':
                    case 'WMV2':
                    case 'WMV3':
                    case 'MSS1':
                    case 'MSS2':
                    case 'WMVA':
                    case 'WVC1':
                    case 'WMVP':
                    case 'WVP2': 
                        $info_video['dataformat'] = 'wmv';
                        $getid3->info['mime_type']    = 'video/x-ms-wmv';
                        break;

                    case 'MP42':
                    case 'MP43':
                    case 'MP4S':
                    case 'mp4s':
                        $info_video['dataformat'] = 'asf';
                        $getid3->info['mime_type']    = 'video/x-ms-asf';
                        break;

                    default:
                        switch ($stream_data['type_raw']) {
                            case 1:
                                if (strstr($this->TrimConvert($stream_data['name']), 'Windows Media')) {
                                    $info_video['dataformat'] = 'wmv';
                                    if ($getid3->info['mime_type'] == 'video/x-ms-asf') {
                                        $getid3->info['mime_type'] = 'video/x-ms-wmv';
                                    }
                                }
                                break;

                            case 2:
                                if (strstr($this->TrimConvert($stream_data['name']), 'Windows Media')) {
                                    $info_audio['dataformat'] = 'wma';
                                    if ($getid3->info['mime_type'] == 'video/x-ms-asf') {
                                        $getid3->info['mime_type'] = 'audio/x-ms-wma';
                                    }
                                }
                                break;

                        }
                        break;
                }
            }
        }

        switch (@$info_audio['codec']) {
            case 'MPEG Layer-3':
                $info_audio['dataformat'] = 'mp3';
                break;

            default:
                break;
        }

        if (isset($info_asf_codec_list_object['codec_entries'])) {
            foreach ($info_asf_codec_list_object['codec_entries'] as $stream_number => $stream_data) {
                switch ($stream_data['type_raw']) {

                    case 1: // video
                        $info_video['encoder'] = $this->TrimConvert($info_asf_codec_list_object['codec_entries'][$stream_number]['name']);
                        break;

                    case 2: // audio
                        $info_audio['encoder']         = $this->TrimConvert($info_asf_codec_list_object['codec_entries'][$stream_number]['name']);
                        $info_audio['encoder_options'] = $this->TrimConvert($info_asf_codec_list_object['codec_entries'][0]['description']);
                        $info_audio['codec']           = $info_audio['encoder'];
                        break;

                    default:
                        $getid3->warning('Unknown streamtype: [codec_list_object][codec_entries]['.$stream_number.'][type_raw] == '.$stream_data['type_raw']);
                        break;

                }
            }
        }

        if (isset($getid3->info['audio'])) {
            $info_audio['lossless']           = (isset($info_audio['lossless'])           ? $info_audio['lossless']           : false);
            $info_audio['dataformat']         = (!empty($info_audio['dataformat'])        ? $info_audio['dataformat']         : 'asf');
        }
        
        if (!empty($info_video['dataformat'])) {
            $info_video['lossless']           = (isset($info_audio['lossless'])           ? $info_audio['lossless']           : false);
            $info_video['pixel_aspect_ratio'] = (isset($info_audio['pixel_aspect_ratio']) ? $info_audio['pixel_aspect_ratio'] : (float)1);
            $info_video['dataformat']         = (!empty($info_video['dataformat'])        ? $info_video['dataformat']         : 'asf');
        }
        
        $getid3->info['bitrate'] = @$info_audio['bitrate'] + @$info_video['bitrate'];
              
        if (empty($info_audio)) {
            unset($getid3->info['audio']);
        }
        
        if (empty($info_video)) {
            unset($getid3->info['video']);
        }
        
        return true;
    }


  
    // Remove terminator 00 00 and convert UNICODE to Latin-1
    private function TrimConvert($string) {
        
        // remove terminator, only if present (it should be, but...)
        if (substr($string, strlen($string) - 2, 2) == "\x00\x00") {
            $string = substr($string, 0, strlen($string) - 2);
        }

        // convert
        return trim($this->getid3->iconv('UTF-16LE', 'ISO-8859-1', $string), ' ');
    }



    private function WMpictureTypeLookup($wm_picture_type) {
        
        static $lookup = array (
            0x03 => 'Front Cover',
            0x04 => 'Back Cover',
            0x00 => 'User Defined',
            0x05 => 'Leaflet Page',
            0x06 => 'Media Label',
            0x07 => 'Lead Artist',
            0x08 => 'Artist',
            0x09 => 'Conductor',
            0x0A => 'Band',
            0x0B => 'Composer',
            0x0C => 'Lyricist',
            0x0D => 'Recording Location',
            0x0E => 'During Recording',
            0x0F => 'During Performance',
            0x10 => 'Video Screen Capture',
            0x12 => 'Illustration',
            0x13 => 'Band Logotype',
            0x14 => 'Publisher Logotype'
        );
        
        return isset($lookup[$wm_picture_type]) ? $this->getid3->iconv('ISO-8859-1', 'UTF-16LE', $lookup[$wm_picture_type]) : '';
    }



    public static function ASFCodecListObjectTypeLookup($codec_list_type) {
        
        static $lookup = array (
            0x0001 => 'Video Codec',
            0x0002 => 'Audio Codec',
            0xFFFF => 'Unknown Codec'
        );

        return (isset($lookup[$codec_list_type]) ? $lookup[$codec_list_type] : 'Invalid Codec Type');
    }



    public static function GUIDname($guid_string) {
        
        static $lookup = array (
            getid3_asf::Extended_Stream_Properties_Object   => 'Extended_Stream_Properties_Object',   
            getid3_asf::Padding_Object                      => 'Padding_Object',                      
            getid3_asf::Payload_Ext_Syst_Pixel_Aspect_Ratio => 'Payload_Ext_Syst_Pixel_Aspect_Ratio', 
            getid3_asf::Script_Command_Object               => 'Script_Command_Object',               
            getid3_asf::No_Error_Correction                 => 'No_Error_Correction',                 
            getid3_asf::Content_Branding_Object             => 'Content_Branding_Object',             
            getid3_asf::Content_Encryption_Object           => 'Content_Encryption_Object',           
            getid3_asf::Digital_Signature_Object            => 'Digital_Signature_Object',            
            getid3_asf::Extended_Content_Encryption_Object  => 'Extended_Content_Encryption_Object',  
            getid3_asf::Simple_Index_Object                 => 'Simple_Index_Object',                 
            getid3_asf::Degradable_JPEG_Media               => 'Degradable_JPEG_Media',               
            getid3_asf::Payload_Extension_System_Timecode   => 'Payload_Extension_System_Timecode',   
            getid3_asf::Binary_Media                        => 'Binary_Media',                        
            getid3_asf::Timecode_Index_Object               => 'Timecode_Index_Object',               
            getid3_asf::Metadata_Library_Object             => 'Metadata_Library_Object',             
            getid3_asf::Reserved_3                          => 'Reserved_3',                          
            getid3_asf::Reserved_4                          => 'Reserved_4',                          
            getid3_asf::Command_Media                       => 'Command_Media',                       
            getid3_asf::Header_Extension_Object             => 'Header_Extension_Object',             
            getid3_asf::Media_Object_Index_Parameters_Obj   => 'Media_Object_Index_Parameters_Obj',   
            getid3_asf::Header_Object                       => 'Header_Object',                       
            getid3_asf::Content_Description_Object          => 'Content_Description_Object',          
            getid3_asf::Error_Correction_Object             => 'Error_Correction_Object',             
            getid3_asf::Data_Object                         => 'Data_Object',                         
            getid3_asf::Web_Stream_Media_Subtype            => 'Web_Stream_Media_Subtype',            
            getid3_asf::Stream_Bitrate_Properties_Object    => 'Stream_Bitrate_Properties_Object',    
            getid3_asf::Language_List_Object                => 'Language_List_Object',                
            getid3_asf::Codec_List_Object                   => 'Codec_List_Object',                   
            getid3_asf::Reserved_2                          => 'Reserved_2',                          
            getid3_asf::File_Properties_Object              => 'File_Properties_Object',              
            getid3_asf::File_Transfer_Media                 => 'File_Transfer_Media',                 
            getid3_asf::Old_RTP_Extension_Data              => 'Old_RTP_Extension_Data',              
            getid3_asf::Advanced_Mutual_Exclusion_Object    => 'Advanced_Mutual_Exclusion_Object',    
            getid3_asf::Bandwidth_Sharing_Object            => 'Bandwidth_Sharing_Object',            
            getid3_asf::Reserved_1                          => 'Reserved_1',                          
            getid3_asf::Bandwidth_Sharing_Exclusive         => 'Bandwidth_Sharing_Exclusive',         
            getid3_asf::Bandwidth_Sharing_Partial           => 'Bandwidth_Sharing_Partial',           
            getid3_asf::JFIF_Media                          => 'JFIF_Media',                          
            getid3_asf::Stream_Properties_Object            => 'Stream_Properties_Object',            
            getid3_asf::Video_Media                         => 'Video_Media',                         
            getid3_asf::Audio_Spread                        => 'Audio_Spread',                        
            getid3_asf::Metadata_Object                     => 'Metadata_Object',                     
            getid3_asf::Payload_Ext_Syst_Sample_Duration    => 'Payload_Ext_Syst_Sample_Duration',    
            getid3_asf::Group_Mutual_Exclusion_Object       => 'Group_Mutual_Exclusion_Object',       
            getid3_asf::Extended_Content_Description_Object => 'Extended_Content_Description_Object', 
            getid3_asf::Stream_Prioritization_Object        => 'Stream_Prioritization_Object',        
            getid3_asf::Payload_Ext_System_Content_Type     => 'Payload_Ext_System_Content_Type',     
            getid3_asf::Old_File_Properties_Object          => 'Old_File_Properties_Object',          
            getid3_asf::Old_ASF_Header_Object               => 'Old_ASF_Header_Object',               
            getid3_asf::Old_ASF_Data_Object                 => 'Old_ASF_Data_Object',                 
            getid3_asf::Index_Object                        => 'Index_Object',                        
            getid3_asf::Old_Stream_Properties_Object        => 'Old_Stream_Properties_Object',        
            getid3_asf::Old_Content_Description_Object      => 'Old_Content_Description_Object',      
            getid3_asf::Old_Script_Command_Object           => 'Old_Script_Command_Object',           
            getid3_asf::Old_Marker_Object                   => 'Old_Marker_Object',                   
            getid3_asf::Old_Component_Download_Object       => 'Old_Component_Download_Object',       
            getid3_asf::Old_Stream_Group_Object             => 'Old_Stream_Group_Object',             
            getid3_asf::Old_Scalable_Object                 => 'Old_Scalable_Object',                 
            getid3_asf::Old_Prioritization_Object           => 'Old_Prioritization_Object',           
            getid3_asf::Bitrate_Mutual_Exclusion_Object     => 'Bitrate_Mutual_Exclusion_Object',     
            getid3_asf::Old_Inter_Media_Dependency_Object   => 'Old_Inter_Media_Dependency_Object',   
            getid3_asf::Old_Rating_Object                   => 'Old_Rating_Object',                   
            getid3_asf::Index_Parameters_Object             => 'Index_Parameters_Object',             
            getid3_asf::Old_Color_Table_Object              => 'Old_Color_Table_Object',              
            getid3_asf::Old_Language_List_Object            => 'Old_Language_List_Object',            
            getid3_asf::Old_Audio_Media                     => 'Old_Audio_Media',                     
            getid3_asf::Old_Video_Media                     => 'Old_Video_Media',                     
            getid3_asf::Old_Image_Media                     => 'Old_Image_Media',                     
            getid3_asf::Old_Timecode_Media                  => 'Old_Timecode_Media',                  
            getid3_asf::Old_Text_Media                      => 'Old_Text_Media',                      
            getid3_asf::Old_MIDI_Media                      => 'Old_MIDI_Media',                      
            getid3_asf::Old_Command_Media                   => 'Old_Command_Media',                   
            getid3_asf::Old_No_Error_Concealment            => 'Old_No_Error_Concealment',            
            getid3_asf::Old_Scrambled_Audio                 => 'Old_Scrambled_Audio',                 
            getid3_asf::Old_No_Color_Table                  => 'Old_No_Color_Table',                  
            getid3_asf::Old_SMPTE_Time                      => 'Old_SMPTE_Time',                      
            getid3_asf::Old_ASCII_Text                      => 'Old_ASCII_Text',                      
            getid3_asf::Old_Unicode_Text                    => 'Old_Unicode_Text',                    
            getid3_asf::Old_HTML_Text                       => 'Old_HTML_Text',                       
            getid3_asf::Old_URL_Command                     => 'Old_URL_Command',                     
            getid3_asf::Old_Filename_Command                => 'Old_Filename_Command',                
            getid3_asf::Old_ACM_Codec                       => 'Old_ACM_Codec',                       
            getid3_asf::Old_VCM_Codec                       => 'Old_VCM_Codec',                       
            getid3_asf::Old_QuickTime_Codec                 => 'Old_QuickTime_Codec',                 
            getid3_asf::Old_DirectShow_Transform_Filter     => 'Old_DirectShow_Transform_Filter',     
            getid3_asf::Old_DirectShow_Rendering_Filter     => 'Old_DirectShow_Rendering_Filter',     
            getid3_asf::Old_No_Enhancement                  => 'Old_No_Enhancement',                  
            getid3_asf::Old_Unknown_Enhancement_Type        => 'Old_Unknown_Enhancement_Type',        
            getid3_asf::Old_Temporal_Enhancement            => 'Old_Temporal_Enhancement',            
            getid3_asf::Old_Spatial_Enhancement             => 'Old_Spatial_Enhancement',             
            getid3_asf::Old_Quality_Enhancement             => 'Old_Quality_Enhancement',             
            getid3_asf::Old_Number_of_Channels_Enhancement  => 'Old_Number_of_Channels_Enhancement',  
            getid3_asf::Old_Frequency_Response_Enhancement  => 'Old_Frequency_Response_Enhancement',  
            getid3_asf::Old_Media_Object                    => 'Old_Media_Object',                    
            getid3_asf::Mutex_Language                      => 'Mutex_Language',                      
            getid3_asf::Mutex_Bitrate                       => 'Mutex_Bitrate',                       
            getid3_asf::Mutex_Unknown                       => 'Mutex_Unknown',                       
            getid3_asf::Old_ASF_Placeholder_Object          => 'Old_ASF_Placeholder_Object',          
            getid3_asf::Old_Data_Unit_Extension_Object      => 'Old_Data_Unit_Extension_Object',      
            getid3_asf::Web_Stream_Format                   => 'Web_Stream_Format',                   
            getid3_asf::Payload_Ext_System_File_Name        => 'Payload_Ext_System_File_Name',        
            getid3_asf::Marker_Object                       => 'Marker_Object',                       
            getid3_asf::Timecode_Index_Parameters_Object    => 'Timecode_Index_Parameters_Object',    
            getid3_asf::Audio_Media                         => 'Audio_Media',                         
            getid3_asf::Media_Object_Index_Object           => 'Media_Object_Index_Object',           
            getid3_asf::Alt_Extended_Content_Encryption_Obj => 'Alt_Extended_Content_Encryption_Obj'  
        );
        
        return @$lookup[$guid_string];
    }



    public static function ASFIndexObjectIndexTypeLookup($id) {
        
        static $lookup = array (
            1 => 'Nearest Past Data Packet',
            2 => 'Nearest Past Media Object',
            3 => 'Nearest Past Cleanpoint'
        );

        return (isset($lookup[$id]) ? $lookup[$id] : 'invalid');
    }



    public static function GUIDtoBytestring($guid_string) {
        
        // Microsoft defines these 16-byte (128-bit) GUIDs in the strangest way:
        // first 4 bytes are in little-endian order
        // next 2 bytes are appended in little-endian order
        // next 2 bytes are appended in little-endian order
        // next 2 bytes are appended in big-endian order
        // next 6 bytes are appended in big-endian order

        // AaBbCcDd-EeFf-GgHh-IiJj-KkLlMmNnOoPp is stored as this 16-byte string:
        // $Dd $Cc $Bb $Aa $Ff $Ee $Hh $Gg $Ii $Jj $Kk $Ll $Mm $Nn $Oo $Pp

        $hex_byte_char_string  = chr(hexdec(substr($guid_string,  6, 2)));
        $hex_byte_char_string .= chr(hexdec(substr($guid_string,  4, 2)));
        $hex_byte_char_string .= chr(hexdec(substr($guid_string,  2, 2)));
        $hex_byte_char_string .= chr(hexdec(substr($guid_string,  0, 2)));

        $hex_byte_char_string .= chr(hexdec(substr($guid_string, 11, 2)));
        $hex_byte_char_string .= chr(hexdec(substr($guid_string,  9, 2)));

        $hex_byte_char_string .= chr(hexdec(substr($guid_string, 16, 2)));
        $hex_byte_char_string .= chr(hexdec(substr($guid_string, 14, 2)));

        $hex_byte_char_string .= chr(hexdec(substr($guid_string, 19, 2)));
        $hex_byte_char_string .= chr(hexdec(substr($guid_string, 21, 2)));

        $hex_byte_char_string .= chr(hexdec(substr($guid_string, 24, 2)));
        $hex_byte_char_string .= chr(hexdec(substr($guid_string, 26, 2)));
        $hex_byte_char_string .= chr(hexdec(substr($guid_string, 28, 2)));
        $hex_byte_char_string .= chr(hexdec(substr($guid_string, 30, 2)));
        $hex_byte_char_string .= chr(hexdec(substr($guid_string, 32, 2)));
        $hex_byte_char_string .= chr(hexdec(substr($guid_string, 34, 2)));

        return $hex_byte_char_string;
    }



    public static function BytestringToGUID($byte_string) {
        
        $guid_string  = str_pad(dechex(ord($byte_string{3})),  2, '0', STR_PAD_LEFT);
        $guid_string .= str_pad(dechex(ord($byte_string{2})),  2, '0', STR_PAD_LEFT);
        $guid_string .= str_pad(dechex(ord($byte_string{1})),  2, '0', STR_PAD_LEFT);
        $guid_string .= str_pad(dechex(ord($byte_string{0})),  2, '0', STR_PAD_LEFT);
        $guid_string .= '-';
        $guid_string .= str_pad(dechex(ord($byte_string{5})),  2, '0', STR_PAD_LEFT);
        $guid_string .= str_pad(dechex(ord($byte_string{4})),  2, '0', STR_PAD_LEFT);
        $guid_string .= '-';
        $guid_string .= str_pad(dechex(ord($byte_string{7})),  2, '0', STR_PAD_LEFT);
        $guid_string .= str_pad(dechex(ord($byte_string{6})),  2, '0', STR_PAD_LEFT);
        $guid_string .= '-';
        $guid_string .= str_pad(dechex(ord($byte_string{8})),  2, '0', STR_PAD_LEFT);
        $guid_string .= str_pad(dechex(ord($byte_string{9})),  2, '0', STR_PAD_LEFT);
        $guid_string .= '-';
        $guid_string .= str_pad(dechex(ord($byte_string{10})), 2, '0', STR_PAD_LEFT);
        $guid_string .= str_pad(dechex(ord($byte_string{11})), 2, '0', STR_PAD_LEFT);
        $guid_string .= str_pad(dechex(ord($byte_string{12})), 2, '0', STR_PAD_LEFT);
        $guid_string .= str_pad(dechex(ord($byte_string{13})), 2, '0', STR_PAD_LEFT);
        $guid_string .= str_pad(dechex(ord($byte_string{14})), 2, '0', STR_PAD_LEFT);
        $guid_string .= str_pad(dechex(ord($byte_string{15})), 2, '0', STR_PAD_LEFT);

        return strtoupper($guid_string);
    }



    public static function FiletimeToUNIXtime($file_time, $round=true) {
        
        // FILETIME is a 64-bit unsigned integer representing
        // the number of 100-nanosecond intervals since January 1, 1601
        // UNIX timestamp is number of seconds since January 1, 1970
        // 116444736000000000 = 10000000 * 60 * 60 * 24 * 365 * 369 + 89 leap days
        
        $time = ($file_time - 116444736000000000) / 10000000;
        
        if ($round) {
            return intval(round($time));
        }
        
        return $time;
    }



    public static function TrimTerm($string) {

        // remove terminator, only if present (it should be, but...)
        if (substr($string, -2) == "\x00\x00") {
            $string = substr($string, 0, -2);
        }
        return $string;
    }


}


?>