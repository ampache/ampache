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
// | module.graphic.png.php                                               |
// | Module for analyzing PNG graphic files.                              |
// | dependencies: zlib support in PHP (optional)                         |
// +----------------------------------------------------------------------+
//
// $Id: module.graphic.png.php,v 1.4 2006/11/02 10:48:02 ah Exp $

        
        
class getid3_png extends getid3_handler
{

    public function Analyze() {

        $getid3 = $this->getid3;

        $getid3->info['png'] = array ();
        $info_png = &$getid3->info['png'];

        $getid3->info['fileformat']          = 'png';
        $getid3->info['video']['dataformat'] = 'png';
        $getid3->info['video']['lossless']   = false;

        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        $png_filedata = fread($getid3->fp, getid3::FREAD_BUFFER_SIZE);

        // Magic bytes  "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A"

        $offset = 8;

        while (((ftell($getid3->fp) - (strlen($png_filedata) - $offset)) < $getid3->info['filesize'])) {
            
            $chunk['data_length'] = getid3_lib::BigEndian2Int(substr($png_filedata, $offset, 4));
            $offset += 4;
            while (((strlen($png_filedata) - $offset) < ($chunk['data_length'] + 4)) && (ftell($getid3->fp) < $getid3->info['filesize'])) {
                $png_filedata .= fread($getid3->fp, getid3::FREAD_BUFFER_SIZE);
            }
            
            $chunk['type_text'] = substr($png_filedata, $offset, 4);
            $chunk['type_raw']  = getid3_lib::BigEndian2Int($chunk['type_text']);
            $offset += 4;
            
            $chunk['data'] = substr($png_filedata, $offset, $chunk['data_length']);
            $offset += $chunk['data_length'];
            
            $chunk['crc'] = getid3_lib::BigEndian2Int(substr($png_filedata, $offset, 4));
            $offset += 4;

            $chunk['flags']['ancilliary']   = (bool)($chunk['type_raw'] & 0x20000000);
            $chunk['flags']['private']      = (bool)($chunk['type_raw'] & 0x00200000);
            $chunk['flags']['reserved']     = (bool)($chunk['type_raw'] & 0x00002000);
            $chunk['flags']['safe_to_copy'] = (bool)($chunk['type_raw'] & 0x00000020);

            // shortcut
            $info_png[$chunk['type_text']] = array ();
            $info_png_chunk_type_text = &$info_png[$chunk['type_text']];

            switch ($chunk['type_text']) {

                case 'IHDR': // Image Header
                    $info_png_chunk_type_text['header'] = $chunk;
                    $info_png_chunk_type_text['width']  = getid3_lib::BigEndian2Int(substr($chunk['data'],  0, 4));
                    $info_png_chunk_type_text['height'] = getid3_lib::BigEndian2Int(substr($chunk['data'],  4, 4));
                    
                    getid3_lib::ReadSequence('BigEndian2Int', $info_png_chunk_type_text['raw'], $chunk['data'], 8, 
                        array (
                            'bit_depth'          => 1,
                            'color_type'         => 1,
                            'compression_method' => 1,
                            'filter_method'      => 1,
                            'interlace_method'   => 1
                        )
                    );

                    $info_png_chunk_type_text['compression_method_text']  = getid3_png::PNGcompressionMethodLookup($info_png_chunk_type_text['raw']['compression_method']);
                    $info_png_chunk_type_text['color_type']['palette']    = (bool)($info_png_chunk_type_text['raw']['color_type'] & 0x01);
                    $info_png_chunk_type_text['color_type']['true_color'] = (bool)($info_png_chunk_type_text['raw']['color_type'] & 0x02);
                    $info_png_chunk_type_text['color_type']['alpha']      = (bool)($info_png_chunk_type_text['raw']['color_type'] & 0x04);

                    $getid3->info['video']['resolution_x'] = $info_png_chunk_type_text['width'];
                    $getid3->info['video']['resolution_y'] = $info_png_chunk_type_text['height'];

                    $getid3->info['video']['bits_per_sample'] = getid3_png::IHDRcalculateBitsPerSample($info_png_chunk_type_text['raw']['color_type'], $info_png_chunk_type_text['raw']['bit_depth']);
                    break;


                case 'PLTE': // Palette
                    $info_png_chunk_type_text['header'] = $chunk;
                    $palette_offset = 0;
                    for ($i = 0; $i <= 255; $i++) {
                        $red   = @getid3_lib::BigEndian2Int($chunk['data']{$palette_offset++});
                        $green = @getid3_lib::BigEndian2Int($chunk['data']{$palette_offset++});
                        $blue  = @getid3_lib::BigEndian2Int($chunk['data']{$palette_offset++});
                        $info_png_chunk_type_text[$i] = (($red << 16) | ($green << 8) | ($blue));
                    }
                    break;


                case 'tRNS': // Transparency
                    $info_png_chunk_type_text['header'] = $chunk;
                    switch ($info_png['IHDR']['raw']['color_type']) {
                        case 0:
                            $info_png_chunk_type_text['transparent_color_gray']  = getid3_lib::BigEndian2Int(substr($chunk['data'], 0, 2));
                            break;

                        case 2:
                            $info_png_chunk_type_text['transparent_color_red']   = getid3_lib::BigEndian2Int(substr($chunk['data'], 0, 2));
                            $info_png_chunk_type_text['transparent_color_green'] = getid3_lib::BigEndian2Int(substr($chunk['data'], 2, 2));
                            $info_png_chunk_type_text['transparent_color_blue']  = getid3_lib::BigEndian2Int(substr($chunk['data'], 4, 2));
                            break;

                        case 3:
                            for ($i = 0; $i < strlen($chunk['data']); $i++) {
                                $info_png_chunk_type_text['palette_opacity'][$i] = getid3_lib::BigEndian2Int($chunk['data'][$i]);
                            }
                            break;

                        case 4:
                        case 6:
                            throw new getid3_exception('Invalid color_type in tRNS chunk: '.$info_png['IHDR']['raw']['color_type']);

                        default:
                            $getid3->warning('Unhandled color_type in tRNS chunk: '.$info_png['IHDR']['raw']['color_type']);
                            break;
                    }
                    break;


                case 'gAMA': // Image Gamma
                    $info_png_chunk_type_text['header'] = $chunk;
                    $info_png_chunk_type_text['gamma']  = getid3_lib::BigEndian2Int($chunk['data']) / 100000;
                    break;


                case 'cHRM': // Primary Chromaticities
                    $info_png_chunk_type_text['header']  = $chunk;
                    $info_png_chunk_type_text['white_x'] = getid3_lib::BigEndian2Int(substr($chunk['data'],  0, 4)) / 100000;
                    $info_png_chunk_type_text['white_y'] = getid3_lib::BigEndian2Int(substr($chunk['data'],  4, 4)) / 100000;
                    $info_png_chunk_type_text['red_y']   = getid3_lib::BigEndian2Int(substr($chunk['data'],  8, 4)) / 100000;
                    $info_png_chunk_type_text['red_y']   = getid3_lib::BigEndian2Int(substr($chunk['data'], 12, 4)) / 100000;
                    $info_png_chunk_type_text['green_y'] = getid3_lib::BigEndian2Int(substr($chunk['data'], 16, 4)) / 100000;
                    $info_png_chunk_type_text['green_y'] = getid3_lib::BigEndian2Int(substr($chunk['data'], 20, 4)) / 100000;
                    $info_png_chunk_type_text['blue_y']  = getid3_lib::BigEndian2Int(substr($chunk['data'], 24, 4)) / 100000;
                    $info_png_chunk_type_text['blue_y']  = getid3_lib::BigEndian2Int(substr($chunk['data'], 28, 4)) / 100000;
                    break;


                case 'sRGB': // Standard RGB Color Space
                    $info_png_chunk_type_text['header']                 = $chunk;
                    $info_png_chunk_type_text['reindering_intent']      = getid3_lib::BigEndian2Int($chunk['data']);
                    $info_png_chunk_type_text['reindering_intent_text'] = getid3_png::PNGsRGBintentLookup($info_png_chunk_type_text['reindering_intent']);
                    break;


                case 'iCCP': // Embedded ICC Profile
                    $info_png_chunk_type_text['header']                  = $chunk;
                    list($profilename, $compressiondata)                 = explode("\x00", $chunk['data'], 2);
                    $info_png_chunk_type_text['profile_name']            = $profilename;
                    $info_png_chunk_type_text['compression_method']      = getid3_lib::BigEndian2Int($compressiondata[0]);
                    $info_png_chunk_type_text['compression_profile']     = substr($compressiondata, 1);
                    $info_png_chunk_type_text['compression_method_text'] = getid3_png::PNGcompressionMethodLookup($info_png_chunk_type_text['compression_method']);
                    break;


                case 'tEXt': // Textual Data
                    $info_png_chunk_type_text['header']  = $chunk;
                    list($keyword, $text)                                = explode("\x00", $chunk['data'], 2);
                    $info_png_chunk_type_text['keyword'] = $keyword;
                    $info_png_chunk_type_text['text']    = $text;

                    $info_png['comments'][$info_png_chunk_type_text['keyword']][] = $info_png_chunk_type_text['text'];
                    break;


                case 'zTXt': // Compressed Textual Data
                    $info_png_chunk_type_text['header']                  = $chunk;
                    list($keyword, $otherdata)                           = explode("\x00", $chunk['data'], 2);
                    $info_png_chunk_type_text['keyword']                 = $keyword;
                    $info_png_chunk_type_text['compression_method']      = getid3_lib::BigEndian2Int(substr($otherdata, 0, 1));
                    $info_png_chunk_type_text['compressed_text']         = substr($otherdata, 1);
                    $info_png_chunk_type_text['compression_method_text'] = getid3_png::PNGcompressionMethodLookup($info_png_chunk_type_text['compression_method']);
                    
                    if ($info_png_chunk_type_text['compression_method'] != 0) {
                        // unknown compression method
                        break;
                    }
                    
                    if (function_exists('gzuncompress')) {
                        $info_png_chunk_type_text['text']  = gzuncompress($info_png_chunk_type_text['compressed_text']);
                    }
                    else {
                        if (!@$this->zlib_warning) {
                            $getid3->warning('PHP does not have --with-zlib support - cannot gzuncompress()');
                        }
                        $this->zlib_warning = true;
                    }
                    

                    if (isset($info_png_chunk_type_text['text'])) {
                        $info_png['comments'][$info_png_chunk_type_text['keyword']][] = $info_png_chunk_type_text['text'];
                    }
                    break;


                case 'iTXt': // International Textual Data
                    $info_png_chunk_type_text['header']                  = $chunk;
                    list($keyword, $otherdata)                           = explode("\x00", $chunk['data'], 2);
                    $info_png_chunk_type_text['keyword']                 = $keyword;
                    $info_png_chunk_type_text['compression']             = (bool)getid3_lib::BigEndian2Int(substr($otherdata, 0, 1));
                    $info_png_chunk_type_text['compression_method']      = getid3_lib::BigEndian2Int($otherdata[1]);
                    $info_png_chunk_type_text['compression_method_text'] = getid3_png::PNGcompressionMethodLookup($info_png_chunk_type_text['compression_method']);
                    list($languagetag, $translatedkeyword, $text)        = explode("\x00", substr($otherdata, 2), 3);
                    $info_png_chunk_type_text['language_tag']            = $languagetag;
                    $info_png_chunk_type_text['translated_keyword']      = $translatedkeyword;

                    if ($info_png_chunk_type_text['compression']) {

                        switch ($info_png_chunk_type_text['compression_method']) {
                            case 0:
                                if (function_exists('gzuncompress')) {
                                    $info_png_chunk_type_text['text'] = gzuncompress($text);
                                }
                                else {
                                    if (!@$this->zlib_warning) {
                                        $getid3->warning('PHP does not have --with-zlib support - cannot gzuncompress()');
                                    }
                                    $this->zlib_warning = true;
                                }
                                break;

                            default:
                                // unknown compression method
                                break;
                        }

                    } else {

                        $info_png_chunk_type_text['text']                = $text;

                    }

                    if (isset($info_png_chunk_type_text['text'])) {
                        $info_png['comments'][$info_png_chunk_type_text['keyword']][] = $info_png_chunk_type_text['text'];
                    }
                    break;


                case 'bKGD': // Background Color
                    $info_png_chunk_type_text['header']                   = $chunk;
                    switch ($info_png['IHDR']['raw']['color_type']) {
                        case 0:
                        case 4:
                            $info_png_chunk_type_text['background_gray']  = getid3_lib::BigEndian2Int($chunk['data']);
                            break;

                        case 2:
                        case 6:
                            $info_png_chunk_type_text['background_red']   = getid3_lib::BigEndian2Int(substr($chunk['data'], 0 * $info_png['IHDR']['raw']['bit_depth'], $info_png['IHDR']['raw']['bit_depth']));
                            $info_png_chunk_type_text['background_green'] = getid3_lib::BigEndian2Int(substr($chunk['data'], 1 * $info_png['IHDR']['raw']['bit_depth'], $info_png['IHDR']['raw']['bit_depth']));
                            $info_png_chunk_type_text['background_blue']  = getid3_lib::BigEndian2Int(substr($chunk['data'], 2 * $info_png['IHDR']['raw']['bit_depth'], $info_png['IHDR']['raw']['bit_depth']));
                            break;

                        case 3:
                            $info_png_chunk_type_text['background_index'] = getid3_lib::BigEndian2Int($chunk['data']);
                            break;

                        default:
                            break;
                    }
                    break;


                case 'pHYs': // Physical Pixel Dimensions
                    $info_png_chunk_type_text['header']                 = $chunk;
                    $info_png_chunk_type_text['pixels_per_unit_x']      = getid3_lib::BigEndian2Int(substr($chunk['data'], 0, 4));
                    $info_png_chunk_type_text['pixels_per_unit_y']      = getid3_lib::BigEndian2Int(substr($chunk['data'], 4, 4));
                    $info_png_chunk_type_text['unit_specifier']         = getid3_lib::BigEndian2Int(substr($chunk['data'], 8, 1));
                    $info_png_chunk_type_text['unit']                   = getid3_png::PNGpHYsUnitLookup($info_png_chunk_type_text['unit_specifier']);
                    break;


                case 'sBIT': // Significant Bits
                    $info_png_chunk_type_text['header'] = $chunk;
                    switch ($info_png['IHDR']['raw']['color_type']) {
                        case 0:
                            $info_png_chunk_type_text['significant_bits_gray']  = getid3_lib::BigEndian2Int(substr($chunk['data'], 0, 1));
                            break;

                        case 2:
                        case 3:
                            $info_png_chunk_type_text['significant_bits_red']   = getid3_lib::BigEndian2Int($chunk['data'][0]);
                            $info_png_chunk_type_text['significant_bits_green'] = getid3_lib::BigEndian2Int($chunk['data'][1]);
                            $info_png_chunk_type_text['significant_bits_blue']  = getid3_lib::BigEndian2Int($chunk['data'][2]);
                            break;                                                                                                                     
                                                                                                                                                       
                        case 4:                                                                                                                        
                            $info_png_chunk_type_text['significant_bits_gray']  = getid3_lib::BigEndian2Int($chunk['data'][0]);
                            $info_png_chunk_type_text['significant_bits_alpha'] = getid3_lib::BigEndian2Int($chunk['data'][1]);
                            break;                                                                                                                     
                                                                                                                                                       
                        case 6:                                                                                                                        
                            $info_png_chunk_type_text['significant_bits_red']   = getid3_lib::BigEndian2Int($chunk['data'][0]);
                            $info_png_chunk_type_text['significant_bits_green'] = getid3_lib::BigEndian2Int($chunk['data'][1]);
                            $info_png_chunk_type_text['significant_bits_blue']  = getid3_lib::BigEndian2Int($chunk['data'][2]);
                            $info_png_chunk_type_text['significant_bits_alpha'] = getid3_lib::BigEndian2Int($chunk['data'][3]);
                            break;

                        default:
                            break;
                    }
                    break;


                case 'sPLT': // Suggested Palette
                    $info_png_chunk_type_text['header'] = $chunk;
                    
                    list($palettename, $otherdata) = explode("\x00", $chunk['data'], 2);
                    $info_png_chunk_type_text['palette_name'] = $palettename;
                    
                    $info_png_chunk_type_text['sample_depth_bits']  = getid3_lib::BigEndian2Int($otherdata[0]);
                    $info_png_chunk_type_text['sample_depth_bytes'] = $info_png_chunk_type_text['sample_depth_bits'] / 8;
                    
                    $s_plt_offset = 1;
                    $paletteCounter = 0;
                    while ($s_plt_offset < strlen($otherdata)) {
                        
                        $info_png_chunk_type_text['red'][$paletteCounter] = getid3_lib::BigEndian2Int(substr($otherdata, $s_plt_offset, $info_png_chunk_type_text['sample_depth_bytes']));
                        $s_plt_offset += $info_png_chunk_type_text['sample_depth_bytes'];
                        
                        $info_png_chunk_type_text['green'][$paletteCounter] = getid3_lib::BigEndian2Int(substr($otherdata, $s_plt_offset, $info_png_chunk_type_text['sample_depth_bytes']));
                        $s_plt_offset += $info_png_chunk_type_text['sample_depth_bytes'];
                        
                        $info_png_chunk_type_text['blue'][$paletteCounter] = getid3_lib::BigEndian2Int(substr($otherdata, $s_plt_offset, $info_png_chunk_type_text['sample_depth_bytes']));
                        $s_plt_offset += $info_png_chunk_type_text['sample_depth_bytes'];
                        
                        $info_png_chunk_type_text['alpha'][$paletteCounter] = getid3_lib::BigEndian2Int(substr($otherdata, $s_plt_offset, $info_png_chunk_type_text['sample_depth_bytes']));
                        $s_plt_offset += $info_png_chunk_type_text['sample_depth_bytes'];
                        
                        $info_png_chunk_type_text['frequency'][$paletteCounter] = getid3_lib::BigEndian2Int(substr($otherdata, $s_plt_offset, 2));
                        $s_plt_offset += 2;
                        
                        $paletteCounter++;
                    }
                    break;


                case 'hIST': // Palette Histogram
                    $info_png_chunk_type_text['header'] = $chunk;
                    $h_ist_counter = 0;
                    while ($h_ist_counter < strlen($chunk['data'])) {
                        $info_png_chunk_type_text[$h_ist_counter] = getid3_lib::BigEndian2Int(substr($chunk['data'], $h_ist_counter / 2, 2));
                        $h_ist_counter += 2;
                    }
                    break;


                case 'tIME': // Image Last-Modification Time
                    $info_png_chunk_type_text['header'] = $chunk;
                    $info_png_chunk_type_text['year']   = getid3_lib::BigEndian2Int(substr($chunk['data'], 0, 2));
                    $info_png_chunk_type_text['month']  = getid3_lib::BigEndian2Int($chunk['data']{2});
                    $info_png_chunk_type_text['day']    = getid3_lib::BigEndian2Int($chunk['data']{3});
                    $info_png_chunk_type_text['hour']   = getid3_lib::BigEndian2Int($chunk['data']{4});
                    $info_png_chunk_type_text['minute'] = getid3_lib::BigEndian2Int($chunk['data']{5});
                    $info_png_chunk_type_text['second'] = getid3_lib::BigEndian2Int($chunk['data']{6});
                    $info_png_chunk_type_text['unix']   = gmmktime($info_png_chunk_type_text['hour'], $info_png_chunk_type_text['minute'], $info_png_chunk_type_text['second'], $info_png_chunk_type_text['month'], $info_png_chunk_type_text['day'], $info_png_chunk_type_text['year']);
                    break;


                case 'oFFs': // Image Offset
                    $info_png_chunk_type_text['header']         = $chunk;
                    $info_png_chunk_type_text['position_x']     = getid3_lib::BigEndian2Int(substr($chunk['data'], 0, 4), false, true);
                    $info_png_chunk_type_text['position_y']     = getid3_lib::BigEndian2Int(substr($chunk['data'], 4, 4), false, true);
                    $info_png_chunk_type_text['unit_specifier'] = getid3_lib::BigEndian2Int($chunk['data'][8]);
                    $info_png_chunk_type_text['unit']           = getid3_png::PNGoFFsUnitLookup($info_png_chunk_type_text['unit_specifier']);
                    break;


                case 'pCAL': // Calibration Of Pixel Values
                    $info_png_chunk_type_text['header']             = $chunk;
                    list($calibrationname, $otherdata)              = explode("\x00", $chunk['data'], 2);
                    $info_png_chunk_type_text['calibration_name']   = $calibrationname;
                    $info_png_chunk_type_text['original_zero']      = getid3_lib::BigEndian2Int(substr($chunk['data'], 0, 4), false, true);
                    $info_png_chunk_type_text['original_max']       = getid3_lib::BigEndian2Int(substr($chunk['data'], 4, 4), false, true);
                    $info_png_chunk_type_text['equation_type']      = getid3_lib::BigEndian2Int($chunk['data'][8]);
                    $info_png_chunk_type_text['equation_type_text'] = getid3_png::PNGpCALequationTypeLookup($info_png_chunk_type_text['equation_type']);
                    $info_png_chunk_type_text['parameter_count']    = getid3_lib::BigEndian2Int($chunk['data'][9]);
                    $info_png_chunk_type_text['parameters']         = explode("\x00", substr($chunk['data'], 10));
                    break;


                case 'sCAL': // Physical Scale Of Image Subject
                    $info_png_chunk_type_text['header']         = $chunk;
                    $info_png_chunk_type_text['unit_specifier'] = getid3_lib::BigEndian2Int(substr($chunk['data'], 0, 1));
                    $info_png_chunk_type_text['unit']           = getid3_png::PNGsCALUnitLookup($info_png_chunk_type_text['unit_specifier']);
                    list($info_png_chunk_type_text['pixel_width'], $info_png_chunk_type_text['pixel_height']) = explode("\x00", substr($chunk['data'], 1));
                    break;


                case 'gIFg': // GIF Graphic Control Extension
                    $gIFg_counter = 0;
                    if (isset($info_png_chunk_type_text) && is_array($info_png_chunk_type_text)) {
                        $gIFg_counter = count($info_png_chunk_type_text);
                    }
                    $info_png_chunk_type_text[$gIFg_counter]['header']          = $chunk;
                    $info_png_chunk_type_text[$gIFg_counter]['disposal_method'] = getid3_lib::BigEndian2Int($chunk['data'][0]);
                    $info_png_chunk_type_text[$gIFg_counter]['user_input_flag'] = getid3_lib::BigEndian2Int($chunk['data'][1]);
                    $info_png_chunk_type_text[$gIFg_counter]['delay_time']      = getid3_lib::BigEndian2Int($chunk['data'][2]);
                    break;


                case 'gIFx': // GIF Application Extension
                    $gIFx_counter = 0;
                    if (isset($info_png_chunk_type_text) && is_array($info_png_chunk_type_text)) {
                        $gIFx_counter = count($info_png_chunk_type_text);
                    }
                    $info_png_chunk_type_text[$gIFx_counter]['header']                 = $chunk;
                    $info_png_chunk_type_text[$gIFx_counter]['application_identifier'] = substr($chunk['data'],  0, 8);
                    $info_png_chunk_type_text[$gIFx_counter]['authentication_code']    = substr($chunk['data'],  8, 3);
                    $info_png_chunk_type_text[$gIFx_counter]['application_data']       = substr($chunk['data'], 11);
                    break;


                case 'IDAT': // Image Data
                    $idat_information_field_index = 0;
                    if (isset($info_png['IDAT']) && is_array($info_png['IDAT'])) {
                        $idat_information_field_index = count($info_png['IDAT']);
                    }
                    unset($chunk['data']);
                    $info_png_chunk_type_text[$idat_information_field_index]['header'] = $chunk;
                    break;


                case 'IEND': // Image Trailer
                    $info_png_chunk_type_text['header'] = $chunk;
                    break;


                default:
                    $info_png_chunk_type_text['header'] = $chunk;
                    $getid3->warning('Unhandled chunk type: '.$chunk['type_text']);
                    break;
            }
        }

        return true;
    }



    public static function PNGsRGBintentLookup($sRGB) {
        
        static $lookup = array (
            0 => 'Perceptual',
            1 => 'Relative colorimetric',
            2 => 'Saturation',
            3 => 'Absolute colorimetric'
        );
        return (isset($lookup[$sRGB]) ? $lookup[$sRGB] : 'invalid');
    }



    public static function PNGcompressionMethodLookup($compression_method) {
    
        return ($compression_method == 0 ?  'deflate/inflate' : 'invalid');
    }

    
    
    public static function PNGpHYsUnitLookup($unit_id) {
    
        static $lookup = array (
            0 => 'unknown',
            1 => 'meter'
        );
        return (isset($lookup[$unit_id]) ? $lookup[$unit_id] : 'invalid');
    }

    
    
    public static function PNGoFFsUnitLookup($unit_id) {
    
        static $lookup = array (
            0 => 'pixel',
            1 => 'micrometer'
        );
        return (isset($lookup[$unit_id]) ? $lookup[$unit_id] : 'invalid');
    }

    
    
    public static function PNGpCALequationTypeLookup($equation_type) {
        
        static $lookup = array (
            0 => 'Linear mapping',
            1 => 'Base-e exponential mapping',
            2 => 'Arbitrary-base exponential mapping',
            3 => 'Hyperbolic mapping'
        );
        return (isset($lookup[$equation_type]) ? $lookup[$equation_type] : 'invalid');
    }



    public static function PNGsCALUnitLookup($unit_id) {

        static $lookup = array (
            0 => 'meter',
            1 => 'radian'
        );
        return (isset($lookup[$unit_id]) ? $lookup[$unit_id] : 'invalid');
    }

    
    
    public static function IHDRcalculateBitsPerSample($color_type, $bit_depth) {
    
        switch ($color_type) {
            case 0: // Each pixel is a grayscale sample.
                return $bit_depth;

            case 2: // Each pixel is an R,G,B triple
                return 3 * $bit_depth;

            case 3: // Each pixel is a palette index; a PLTE chunk must appear.
                return $bit_depth;

            case 4: // Each pixel is a grayscale sample, followed by an alpha sample.
                return 2 * $bit_depth;

            case 6: // Each pixel is an R,G,B triple, followed by an alpha sample.
                return 4 * $bit_depth;
        }
        return false;
    }

}


?>