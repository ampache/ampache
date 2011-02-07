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
// | module.audio.mpc.php                                                 |
// | Module for analyzing Musepack/MPEG+ Audio files                      |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.audio.mpc.php,v 1.3 2006/11/02 10:48:01 ah Exp $



class getid3_mpc8 extends getid3_handler
{

    public function Analyze() {
        $getid3 = $this->getid3;

		$getid3->info['fileformat']               = 'mpc';
		$getid3->info['audio']['dataformat']      = 'mpc';
		$getid3->info['audio']['bitrate_mode']    = 'vbr';
		$getid3->info['audio']['lossless']        = false;

		fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
		$MPCheaderData = fread($getid3->fp, 4);
		$getid3->info['mpc']['header']['preamble'] = substr($MPCheaderData, 0, 4); // should be 'MPCK' (SV8) or 'MP+' (SV7), otherwise possible stream data (SV4-SV6)
		if (preg_match('#^MPCK#', $getid3->info['mpc']['header']['preamble'])) {

			// this is SV8
			// http://trac.musepack.net/trac/wiki/SV8Specification

			$thisfile_mpc_header = &$getid3->info['mpc']['header'];

			$keyNameSize            = 2;
			$maxHandledPacketLength = 9; // specs say: "n*8; 0 < n < 10"

			$offset = ftell($getid3->fp);
			while ($offset < $getid3->info['avdataend']) {
				$thisPacket = array();
				$thisPacket['offset'] = $offset;
				$packet_offset = 0;

				// Size is a variable-size field, could be 1-4 bytes (possibly more?)
				// read enough data in and figure out the exact size later
				$MPCheaderData = fread($getid3->fp, $keyNameSize + $maxHandledPacketLength);
				$packet_offset += $keyNameSize;
				$thisPacket['key']      = substr($MPCheaderData, 0, $keyNameSize);
				$thisPacket['key_name'] = $this->MPCsv8PacketName($thisPacket['key']);
				if ($thisPacket['key'] == $thisPacket['key_name']) {
					$getid3->info['error'][] = 'Found unexpected key value "'.$thisPacket['key'].'" at offset '.$thisPacket['offset'];
					return false;
				}
				$packetLength = 0;
				$thisPacket['packet_size'] = $this->SV8variableLengthInteger(substr($MPCheaderData, $keyNameSize), $packetLength); // includes keyname and packet_size field
				if ($thisPacket['packet_size'] === false) {
					$getid3->info['error'][] = 'Did not find expected packet length within '.$maxHandledPacketLength.' bytes at offset '.($thisPacket['offset'] + $keyNameSize);
					return false;
				}
				$packet_offset += $packetLength;
				$offset += $thisPacket['packet_size'];

				switch ($thisPacket['key']) {
					case 'SH': // Stream Header
						$moreBytesToRead = $thisPacket['packet_size'] - $keyNameSize - $maxHandledPacketLength;
						if ($moreBytesToRead > 0) {
							$MPCheaderData .= fread($getid3->fp, $moreBytesToRead);
						}
						$thisPacket['crc']               =       getid3_lib::BigEndian2Int(substr($MPCheaderData, $packet_offset, 4));
						$packet_offset += 4;
						$thisPacket['stream_version']    =       getid3_lib::BigEndian2Int(substr($MPCheaderData, $packet_offset, 1));
						$packet_offset += 1;

						$packetLength = 0;
						$thisPacket['sample_count']      = $this->SV8variableLengthInteger(substr($MPCheaderData, $packet_offset, $maxHandledPacketLength), $packetLength);
						$packet_offset += $packetLength;

						$packetLength = 0;
						$thisPacket['beginning_silence'] = $this->SV8variableLengthInteger(substr($MPCheaderData, $packet_offset, $maxHandledPacketLength), $packetLength);
						$packet_offset += $packetLength;

						$otherUsefulData                 =       getid3_lib::BigEndian2Int(substr($MPCheaderData, $packet_offset, 2));
						$packet_offset += 2;
						$thisPacket['sample_frequency_raw'] =        (($otherUsefulData & 0xE000) >> 13);
						$thisPacket['max_bands_used']       =        (($otherUsefulData & 0x1F00) >>  8);
						$thisPacket['channels']             =        (($otherUsefulData & 0x00F0) >>  4) + 1;
						$thisPacket['ms_used']              = (bool) (($otherUsefulData & 0x0008) >>  3);
						$thisPacket['audio_block_frames']   =        (($otherUsefulData & 0x0007) >>  0);
						$thisPacket['sample_frequency']     = $this->MPCfrequencyLookup($thisPacket['sample_frequency_raw']);

						$thisfile_mpc_header['mid_side_stereo']      = $thisPacket['ms_used'];
						$thisfile_mpc_header['sample_rate']          = $thisPacket['sample_frequency'];
						$thisfile_mpc_header['samples']              = $thisPacket['sample_count'];
						$thisfile_mpc_header['stream_version_major'] = $thisPacket['stream_version'];

						$getid3->info['audio']['channels']    = $thisPacket['channels'];
						$getid3->info['audio']['sample_rate'] = $thisPacket['sample_frequency'];
						$getid3->info['playtime_seconds'] = $thisPacket['sample_count'] / $thisPacket['sample_frequency'];
						$getid3->info['audio']['bitrate'] = (($getid3->info['avdataend'] - $getid3->info['avdataoffset']) * 8) / $getid3->info['playtime_seconds'];
						break;

					case 'RG': // Replay Gain
						$moreBytesToRead = $thisPacket['packet_size'] - $keyNameSize - $maxHandledPacketLength;
						if ($moreBytesToRead > 0) {
							$MPCheaderData .= fread($getid3->fp, $moreBytesToRead);
						}
						$thisPacket['replaygain_version']     =       getid3_lib::BigEndian2Int(substr($MPCheaderData, $packet_offset, 1));
						$packet_offset += 1;
						$thisPacket['replaygain_title_gain']  =       getid3_lib::BigEndian2Int(substr($MPCheaderData, $packet_offset, 2));
						$packet_offset += 2;
						$thisPacket['replaygain_title_peak']  =       getid3_lib::BigEndian2Int(substr($MPCheaderData, $packet_offset, 2));
						$packet_offset += 2;
						$thisPacket['replaygain_album_gain']  =       getid3_lib::BigEndian2Int(substr($MPCheaderData, $packet_offset, 2));
						$packet_offset += 2;
						$thisPacket['replaygain_album_peak']  =       getid3_lib::BigEndian2Int(substr($MPCheaderData, $packet_offset, 2));
						$packet_offset += 2;

						if ($thisPacket['replaygain_title_gain']) { $getid3->info['replay_gain']['title']['gain'] = $thisPacket['replaygain_title_gain']; }
						if ($thisPacket['replaygain_title_peak']) { $getid3->info['replay_gain']['title']['peak'] = $thisPacket['replaygain_title_peak']; }
						if ($thisPacket['replaygain_album_gain']) { $getid3->info['replay_gain']['album']['gain'] = $thisPacket['replaygain_album_gain']; }
						if ($thisPacket['replaygain_album_peak']) { $getid3->info['replay_gain']['album']['peak'] = $thisPacket['replaygain_album_peak']; }
						break;

					case 'EI': // Encoder Info
						$moreBytesToRead = $thisPacket['packet_size'] - $keyNameSize - $maxHandledPacketLength;
						if ($moreBytesToRead > 0) {
							$MPCheaderData .= fread($getid3->fp, $moreBytesToRead);
						}
						$profile_pns                 = getid3_lib::BigEndian2Int(substr($MPCheaderData, $packet_offset, 1));
						$packet_offset += 1;
						$quality_int =                   (($profile_pns & 0xF0) >> 4);
						$quality_dec =                   (($profile_pns & 0x0E) >> 3);
						$thisPacket['quality'] = (float) $quality_int + ($quality_dec / 8);
						$thisPacket['pns_tool'] = (bool) (($profile_pns & 0x01) >> 0);
						$thisPacket['version_major'] = getid3_lib::BigEndian2Int(substr($MPCheaderData, $packet_offset, 1));
						$packet_offset += 1;
						$thisPacket['version_minor'] = getid3_lib::BigEndian2Int(substr($MPCheaderData, $packet_offset, 1));
						$packet_offset += 1;
						$thisPacket['version_build'] = getid3_lib::BigEndian2Int(substr($MPCheaderData, $packet_offset, 1));
						$packet_offset += 1;
						$thisPacket['version'] = $thisPacket['version_major'].'.'.$thisPacket['version_minor'].'.'.$thisPacket['version_build'];

						$getid3->info['audio']['encoder'] = 'MPC v'.$thisPacket['version'].' ('.(($thisPacket['version_minor'] % 2) ? 'unstable' : 'stable').')';
						$thisfile_mpc_header['encoder_version'] = $getid3->info['audio']['encoder'];
						//$thisfile_mpc_header['quality']         = (float) ($thisPacket['quality'] / 1.5875); // values can range from 0.000 to 15.875, mapped to qualities of 0.0 to 10.0
						$thisfile_mpc_header['quality']         = (float) ($thisPacket['quality'] - 5); // values can range from 0.000 to 15.875, of which 0..4 are "reserved/experimental", and 5..15 are mapped to qualities of 0.0 to 10.0
						break;

					case 'SO': // Seek Table Offset
						$packetLength = 0;
						$thisPacket['seek_table_offset'] = $thisPacket['offset'] + $this->SV8variableLengthInteger(substr($MPCheaderData, $packet_offset, $maxHandledPacketLength), $packetLength);
						$packet_offset += $packetLength;
						break;

					case 'ST': // Seek Table
					case 'SE': // Stream End
					case 'AP': // Audio Data
						// nothing useful here, just skip this packet
						$thisPacket = array();
						break;

					default:
						$getid3->info['error'][] = 'Found unhandled key type "'.$thisPacket['key'].'" at offset '.$thisPacket['offset'];
						return false;
						break;
				}
				if (!empty($thisPacket)) {
					$getid3->info['mpc']['packets'][] = $thisPacket;
				}
				fseek($getid3->fp, $offset);
			}
			$thisfile_mpc_header['size'] = $offset;
			return true;

		} else {

			$getid3->info['error'][] = 'Expecting "MP+" or "MPCK" at offset '.$getid3->info['avdataoffset'].', found "'.substr($MPCheaderData, 0, 4).'"';
			unset($getid3->info['fileformat']);
			unset($getid3->info['mpc']);
			return false;

		}
		return false;
    }



    public static function MPCprofileNameLookup($profileid) {

        static $lookup = array (
            0  => 'no profile',
            1  => 'Experimental',
            2  => 'unused',
            3  => 'unused',
            4  => 'unused',
            5  => 'below Telephone (q = 0.0)',
            6  => 'below Telephone (q = 1.0)',
            7  => 'Telephone (q = 2.0)',
            8  => 'Thumb (q = 3.0)',
            9  => 'Radio (q = 4.0)',
            10 => 'Standard (q = 5.0)',
            11 => 'Extreme (q = 6.0)',
            12 => 'Insane (q = 7.0)',
            13 => 'BrainDead (q = 8.0)',
            14 => 'above BrainDead (q = 9.0)',
            15 => 'above BrainDead (q = 10.0)'
        );
        return (isset($lookup[$profileid]) ? $lookup[$profileid] : 'invalid');
    }



    public static function MPCfrequencyLookup($frequencyid) {

        static $lookup = array (
            0 => 44100,
            1 => 48000,
            2 => 37800,
            3 => 32000
        );
        return (isset($lookup[$frequencyid]) ? $lookup[$frequencyid] : 'invalid');
    }



    public static function MPCpeakDBLookup($int_value) {

        if ($int_value > 0) {
            return ((log10($int_value) / log10(2)) - 15) * 6;
        }
        return false;
    }



    public static function MPCencoderVersionLookup($encoder_version) {

        //Encoder version * 100  (106 = 1.06)
        //EncoderVersion % 10 == 0        Release (1.0)
        //EncoderVersion %  2 == 0        Beta (1.06)
        //EncoderVersion %  2 == 1        Alpha (1.05a...z)

        if ($encoder_version == 0) {
            // very old version, not known exactly which
            return 'Buschmann v1.7.0-v1.7.9 or Klemm v0.90-v1.05';
        }

        if (($encoder_version % 10) == 0) {

            // release version
            return number_format($encoder_version / 100, 2);

        } elseif (($encoder_version % 2) == 0) {

            // beta version
            return number_format($encoder_version / 100, 2).' beta';

        }

        // alpha version
        return number_format($encoder_version / 100, 2).' alpha';
    }



	public static function SV8variableLengthInteger($data, &$packetLength, $maxHandledPacketLength=9) {
		$packet_size = 0;
		for ($packetLength = 1; $packetLength <= $maxHandledPacketLength; $packetLength++) {
			// variable-length size field:
			//  bits, big-endian
			//  0xxx xxxx                                           - value 0 to  2^7-1
			//  1xxx xxxx  0xxx xxxx                                - value 0 to 2^14-1
			//  1xxx xxxx  1xxx xxxx  0xxx xxxx                     - value 0 to 2^21-1
			//  1xxx xxxx  1xxx xxxx  1xxx xxxx  0xxx xxxx          - value 0 to 2^28-1
			//  ...
			$thisbyte = ord(substr($data, ($packetLength - 1), 1));
			// look through bytes until find a byte with MSB==0
			$packet_size = ($packet_size << 7);
			$packet_size = ($packet_size | ($thisbyte & 0x7F));
			if (($thisbyte & 0x80) === 0) {
				break;
			}
			if ($packetLength >= $maxHandledPacketLength) {
				return false;
			}
		}
		return $packet_size;
	}



	public static function MPCsv8PacketName($packetKey) {
		static $MPCsv8PacketName = array();
		if (empty($MPCsv8PacketName)) {
			$MPCsv8PacketName = array(
				'AP' => 'Audio Packet',
				'CT' => 'Chapter Tag',
				'EI' => 'Encoder Info',
				'RG' => 'Replay Gain',
				'SE' => 'Stream End',
				'SH' => 'Stream Header',
				'SO' => 'Seek Table Offset',
				'ST' => 'Seek Table',
			);
		}
		return (isset($MPCsv8PacketName[$packetKey]) ? $MPCsv8PacketName[$packetKey] : $packetKey);
	}

}


?>