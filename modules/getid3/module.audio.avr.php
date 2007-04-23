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
// | module.audio.avr.php                                                 |
// | Module for analyzing AVR audio files                                 |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.audio.avr.php,v 1.2 2006/11/02 10:48:01 ah Exp $

        
        
class getid3_avr extends getid3_handler
{

    public function Analyze() {

        $getid3 = $this->getid3;
        
        // http://cui.unige.ch/OSG/info/AudioFormats/ap11.html
        // http://www.btinternet.com/~AnthonyJ/Atari/programming/avr_format.html
        // offset    type    length    name        comments
        // ---------------------------------------------------------------------
        // 0    char    4    ID        format ID == "2BIT"
        // 4    char    8    name        sample name (unused space filled with 0)
        // 12    short    1    mono/stereo    0=mono, -1 (0xFFFF)=stereo
        //                     With stereo, samples are alternated,
        //                     the first voice is the left :
        //                     (LRLRLRLRLRLRLRLRLR...)
        // 14    short    1    resolution    8, 12 or 16 (bits)
        // 16    short    1    signed or not    0=unsigned, -1 (0xFFFF)=signed
        // 18    short    1    loop or not    0=no loop, -1 (0xFFFF)=loop on
        // 20    short    1    MIDI note    0xFFnn, where 0 <= nn <= 127
        //                     0xFFFF means "no MIDI note defined"
        // 22    byte    1    Replay speed    Frequence in the Replay software
        //                     0=5.485 Khz, 1=8.084 Khz, 2=10.971 Khz,
        //                     3=16.168 Khz, 4=21.942 Khz, 5=32.336 Khz
        //                     6=43.885 Khz, 7=47.261 Khz
        //                     -1 (0xFF)=no defined Frequence
        // 23    byte    3    sample rate    in Hertz
        // 26    long    1    size in bytes (2 * bytes in stereo)
        // 30    long    1    loop begin    0 for no loop
        // 34    long    1    loop size    equal to 'size' for no loop
        // 38  short   2   Reserved, MIDI keyboard split */
        // 40  short   2   Reserved, sample compression */
        // 42  short   2   Reserved */
        // 44  char   20;  Additional filename space, used if (name[7] != 0)
        // 64    byte    64    user data
        // 128    bytes    ?    sample data    (12 bits samples are coded on 16 bits:
        //                     0000 xxxx xxxx xxxx)
        // ---------------------------------------------------------------------

        // Note that all values are in motorola (big-endian) format, and that long is
        // assumed to be 4 bytes, and short 2 bytes.
        // When reading the samples, you should handle both signed and unsigned data,
        // and be prepared to convert 16->8 bit, or mono->stereo if needed. To convert
        // 8-bit data between signed/unsigned just add 127 to the sample values.
        // Simularly for 16-bit data you should add 32769


        // Magic bytes: '2BIT'

        $getid3->info['avr'] = array ();
        $info_avr = &$getid3->info['avr'];
        
        $getid3->info['fileformat'] = 'avr';
        $info_avr['raw']['magic']   = '2BIT';

        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        $avr_header = fread($getid3->fp, 128);

        $getid3->info['avdataoffset'] += 128;

        $info_avr['sample_name']        = rtrim(substr($avr_header,  4,  8));
        
        $info_avr['raw']['mono']        = getid3_lib::BigEndian2Int(substr($avr_header, 12,  2));
        $info_avr['bits_per_sample']    = getid3_lib::BigEndian2Int(substr($avr_header, 14,  2));
        $info_avr['raw']['signed']      = getid3_lib::BigEndian2Int(substr($avr_header, 16,  2));
        $info_avr['raw']['loop']        = getid3_lib::BigEndian2Int(substr($avr_header, 18,  2));
        $info_avr['raw']['midi']        = getid3_lib::BigEndian2Int(substr($avr_header, 20,  2));
        $info_avr['raw']['replay_freq'] = getid3_lib::BigEndian2Int(substr($avr_header, 22,  1));
        $info_avr['sample_rate']        = getid3_lib::BigEndian2Int(substr($avr_header, 23,  3));
        $info_avr['sample_length']      = getid3_lib::BigEndian2Int(substr($avr_header, 26,  4));
        $info_avr['loop_start']         = getid3_lib::BigEndian2Int(substr($avr_header, 30,  4));
        $info_avr['loop_end']           = getid3_lib::BigEndian2Int(substr($avr_header, 34,  4));
        $info_avr['midi_split']         = getid3_lib::BigEndian2Int(substr($avr_header, 38,  2));
        $info_avr['sample_compression'] = getid3_lib::BigEndian2Int(substr($avr_header, 40,  2));
        $info_avr['reserved']           = getid3_lib::BigEndian2Int(substr($avr_header, 42,  2));
        $info_avr['sample_name_extra']  = rtrim(substr($avr_header, 44, 20));
        $info_avr['comment']            = rtrim(substr($avr_header, 64, 64));

        $info_avr['flags']['stereo']    = (($info_avr['raw']['mono']   == 0) ? false : true);
        $info_avr['flags']['signed']    = (($info_avr['raw']['signed'] == 0) ? false : true);
        $info_avr['flags']['loop']      = (($info_avr['raw']['loop']   == 0) ? false : true);

        $info_avr['midi_notes'] = array ();
        if (($info_avr['raw']['midi'] & 0xFF00) != 0xFF00) {
            $info_avr['midi_notes'][] = ($info_avr['raw']['midi'] & 0xFF00) >> 8;
        }
        if (($info_avr['raw']['midi'] & 0x00FF) != 0x00FF) {
            $info_avr['midi_notes'][] = ($info_avr['raw']['midi'] & 0x00FF);
        }

        if (($getid3->info['avdataend'] - $getid3->info['avdataoffset']) != ($info_avr['sample_length'] * (($info_avr['bits_per_sample'] == 8) ? 1 : 2))) {
            $getid3->warning('Probable truncated file: expecting '.($info_avr['sample_length'] * (($info_avr['bits_per_sample'] == 8) ? 1 : 2)).' bytes of audio data, found '.($getid3->info['avdataend'] - $getid3->info['avdataoffset']));
        }

        $getid3->info['audio']['dataformat']      = 'avr';
        $getid3->info['audio']['lossless']        = true;
        $getid3->info['audio']['bitrate_mode']    = 'cbr';
        $getid3->info['audio']['bits_per_sample'] = $info_avr['bits_per_sample'];
        $getid3->info['audio']['sample_rate']     = $info_avr['sample_rate'];
        $getid3->info['audio']['channels']        = ($info_avr['flags']['stereo'] ? 2 : 1);
        $getid3->info['playtime_seconds']         = ($info_avr['sample_length'] / $getid3->info['audio']['channels']) / $info_avr['sample_rate'];
        $getid3->info['audio']['bitrate']         = ($info_avr['sample_length'] * (($info_avr['bits_per_sample'] == 8) ? 8 : 16)) / $getid3->info['playtime_seconds'];

        return true;
    }
}


?>