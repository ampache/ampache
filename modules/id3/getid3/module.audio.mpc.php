<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio.mpc.php                                        //
// module for analyzing Musepack/MPEG+ Audio files             //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_mpc
{

	function getid3_mpc(&$fd, &$ThisFileInfo) {
		// http://www.uni-jena.de/~pfk/mpp/sv8/header.html

		$ThisFileInfo['mpc']['header'] = array();
		$thisfile_mpc_header           = &$ThisFileInfo['mpc']['header'];

		$ThisFileInfo['fileformat']               = 'mpc';
		$ThisFileInfo['audio']['dataformat']      = 'mpc';
		$ThisFileInfo['audio']['bitrate_mode']    = 'vbr';
		$ThisFileInfo['audio']['channels']        = 2;  // the format appears to be hardcoded for stereo only
		$ThisFileInfo['audio']['lossless']        = false;

		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);

		$thisfile_mpc_header['size'] = 28;
		$MPCheaderData = fread($fd, $thisfile_mpc_header['size']);
		$offset = 0;

		if (substr($MPCheaderData, $offset, 3) == 'MP+') {

			// great, this is SV7+
			$thisfile_mpc_header['raw']['preamble'] = substr($MPCheaderData, $offset, 3); // should be 'MP+'
			$offset += 3;

		} elseif (preg_match('/^[\x00\x01\x10\x11\x40\x41\x50\x51\x80\x81\x90\x91\xC0\xC1\xD0\xD1][\x20-37][\x00\x20\x40\x60\x80\xA0\xC0\xE0]/s', substr($MPCheaderData, 0, 4))) {

			// this is SV4 - SV6, handle seperately
            $thisfile_mpc_header['size'] = 8;

            // add size of file header to avdataoffset - calc bitrate correctly + MD5 data
		    $ThisFileInfo['avdataoffset'] += $thisfile_mpc_header['size'];

			// Most of this code adapted from Jurgen Faul's MPEGplus source code - thanks Jurgen! :)
			$HeaderDWORD[0] = getid3_lib::LittleEndian2Int(substr($MPCheaderData, 0, 4));
			$HeaderDWORD[1] = getid3_lib::LittleEndian2Int(substr($MPCheaderData, 4, 4));


			// DDDD DDDD  CCCC CCCC  BBBB BBBB  AAAA AAAA
			// aaaa aaaa  abcd dddd  dddd deee  eeff ffff
			//
			// a = bitrate       = anything
			// b = IS            = anything
			// c = MS            = anything
			// d = streamversion = 0000000004 or 0000000005 or 0000000006
			// e = maxband       = anything
			// f = blocksize     = 000001 for SV5+, anything(?) for SV4

			$thisfile_mpc_header['target_bitrate']       =        (($HeaderDWORD[0] & 0xFF800000) >> 23);
			$thisfile_mpc_header['intensity_stereo']     = (bool) (($HeaderDWORD[0] & 0x00400000) >> 22);
			$thisfile_mpc_header['mid-side_stereo']      = (bool) (($HeaderDWORD[0] & 0x00200000) >> 21);
			$thisfile_mpc_header['stream_major_version'] =         ($HeaderDWORD[0] & 0x001FF800) >> 11;
			$thisfile_mpc_header['stream_minor_version'] = 0; // no sub-version numbers before SV7
			$thisfile_mpc_header['max_band']             =         ($HeaderDWORD[0] & 0x000007C0) >>  6;  // related to lowpass frequency, not sure how it translates exactly
			$thisfile_mpc_header['block_size']           =         ($HeaderDWORD[0] & 0x0000003F);

			switch ($thisfile_mpc_header['stream_major_version']) {
				case 4:
					$thisfile_mpc_header['frame_count'] = ($HeaderDWORD[1] >> 16);
					break;

				case 5:
				case 6:
					$thisfile_mpc_header['frame_count'] =  $HeaderDWORD[1];
					break;

				default:
					$ThisFileInfo['error'] = 'Expecting 4, 5 or 6 in version field, found '.$thisfile_mpc_header['stream_major_version'].' instead';
					unset($ThisFileInfo['mpc']);
					return false;
					break;
			}

			if (($thisfile_mpc_header['stream_major_version'] > 4) && ($thisfile_mpc_header['block_size'] != 1)) {
				$ThisFileInfo['warning'][] = 'Block size expected to be 1, actual value found: '.$thisfile_mpc_header['block_size'];
			}

			$thisfile_mpc_header['sample_rate']   = 44100; // AB: used by all files up to SV7
			$ThisFileInfo['audio']['sample_rate'] = $thisfile_mpc_header['sample_rate'];
			$thisfile_mpc_header['samples']       = $thisfile_mpc_header['frame_count'] * 1152 * $ThisFileInfo['audio']['channels'];

			if ($thisfile_mpc_header['target_bitrate'] == 0) {
				$ThisFileInfo['audio']['bitrate_mode'] = 'vbr';
			} else {
				$ThisFileInfo['audio']['bitrate_mode'] = 'cbr';
			}

			$ThisFileInfo['mpc']['bitrate']   = ($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) * 8 * 44100 / $thisfile_mpc_header['frame_count'] / 1152;
			$ThisFileInfo['audio']['bitrate'] = $ThisFileInfo['mpc']['bitrate'];
			$ThisFileInfo['audio']['encoder'] = 'SV'.$thisfile_mpc_header['stream_major_version'];

			return true;

		} else {

			$ThisFileInfo['error'][] = 'Expecting "MP+" at offset '.$ThisFileInfo['avdataoffset'].', found "'.substr($MPCheaderData, $offset, 3).'"';
			unset($ThisFileInfo['fileformat']);
			unset($ThisFileInfo['mpc']);
			return false;

		}

		// Continue with SV7+ handling
		$StreamVersionByte                           = getid3_lib::LittleEndian2Int(substr($MPCheaderData, $offset, 1));
		$offset += 1;
		$thisfile_mpc_header['stream_major_version'] = ($StreamVersionByte & 0x0F);
		$thisfile_mpc_header['stream_minor_version'] = ($StreamVersionByte & 0xF0) >> 4;
		$thisfile_mpc_header['frame_count']          = getid3_lib::LittleEndian2Int(substr($MPCheaderData, $offset, 4));
		$offset += 4;

		switch ($thisfile_mpc_header['stream_major_version']) {
			case 7:
				//$ThisFileInfo['fileformat'] = 'SV7';
				break;

			default:
				$ThisFileInfo['error'][] = 'Only Musepack SV7 supported';
				return false;
		}

		$FlagsDWORD1                                   = getid3_lib::LittleEndian2Int(substr($MPCheaderData, $offset, 4));
		$offset += 4;
		$thisfile_mpc_header['intensity_stereo']       = (bool) (($FlagsDWORD1 & 0x80000000) >> 31);
		$thisfile_mpc_header['mid_side_stereo']        = (bool) (($FlagsDWORD1 & 0x40000000) >> 30);
		$thisfile_mpc_header['max_subband']            =         ($FlagsDWORD1 & 0x3F000000) >> 24;
		$thisfile_mpc_header['raw']['profile']         =         ($FlagsDWORD1 & 0x00F00000) >> 20;
		$thisfile_mpc_header['begin_loud']             = (bool) (($FlagsDWORD1 & 0x00080000) >> 19);
		$thisfile_mpc_header['end_loud']               = (bool) (($FlagsDWORD1 & 0x00040000) >> 18);
		$thisfile_mpc_header['raw']['sample_rate']     =         ($FlagsDWORD1 & 0x00030000) >> 16;
		$thisfile_mpc_header['max_level']              =         ($FlagsDWORD1 & 0x0000FFFF);

		$thisfile_mpc_header['raw']['title_peak']      = getid3_lib::LittleEndian2Int(substr($MPCheaderData, $offset, 2));
		$offset += 2;
		$thisfile_mpc_header['raw']['title_gain']      = getid3_lib::LittleEndian2Int(substr($MPCheaderData, $offset, 2), true);
		$offset += 2;

		$thisfile_mpc_header['raw']['album_peak']      = getid3_lib::LittleEndian2Int(substr($MPCheaderData, $offset, 2));
		$offset += 2;
		$thisfile_mpc_header['raw']['album_gain']      = getid3_lib::LittleEndian2Int(substr($MPCheaderData, $offset, 2), true);
		$offset += 2;

		$FlagsDWORD2                                   = getid3_lib::LittleEndian2Int(substr($MPCheaderData, $offset, 4));
		$offset += 4;
		$thisfile_mpc_header['true_gapless']           = (bool) (($FlagsDWORD2 & 0x80000000) >> 31);
		$thisfile_mpc_header['last_frame_length']      =         ($FlagsDWORD2 & 0x7FF00000) >> 20;


		$thisfile_mpc_header['raw']['not_sure_what']   = getid3_lib::LittleEndian2Int(substr($MPCheaderData, $offset, 3));
		$offset += 3;
		$thisfile_mpc_header['raw']['encoder_version'] = getid3_lib::LittleEndian2Int(substr($MPCheaderData, $offset, 1));
		$offset += 1;

		$thisfile_mpc_header['profile']     = $this->MPCprofileNameLookup($thisfile_mpc_header['raw']['profile']);
		$thisfile_mpc_header['sample_rate'] = $this->MPCfrequencyLookup($thisfile_mpc_header['raw']['sample_rate']);
		if ($thisfile_mpc_header['sample_rate'] == 0) {
			$ThisFileInfo['error'][] = 'Corrupt MPC file: frequency == zero';
			return false;
		}
		$ThisFileInfo['audio']['sample_rate'] = $thisfile_mpc_header['sample_rate'];
		$thisfile_mpc_header['samples']       = ((($thisfile_mpc_header['frame_count'] - 1) * 1152) + $thisfile_mpc_header['last_frame_length']) * $ThisFileInfo['audio']['channels'];

		$ThisFileInfo['playtime_seconds']     = ($thisfile_mpc_header['samples'] / $ThisFileInfo['audio']['channels']) / $ThisFileInfo['audio']['sample_rate'];
		if ($ThisFileInfo['playtime_seconds'] == 0) {
			$ThisFileInfo['error'][] = 'Corrupt MPC file: playtime_seconds == zero';
			return false;
		}

		// add size of file header to avdataoffset - calc bitrate correctly + MD5 data
		$ThisFileInfo['avdataoffset'] += $thisfile_mpc_header['size'];

		$ThisFileInfo['audio']['bitrate'] = (($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) * 8) / $ThisFileInfo['playtime_seconds'];

		$thisfile_mpc_header['title_peak']        = $thisfile_mpc_header['raw']['title_peak'];
		$thisfile_mpc_header['title_peak_db']     = $this->MPCpeakDBLookup($thisfile_mpc_header['title_peak']);
		if ($thisfile_mpc_header['raw']['title_gain'] < 0) {
			$thisfile_mpc_header['title_gain_db'] = (float) (32768 + $thisfile_mpc_header['raw']['title_gain']) / -100;
		} else {
			$thisfile_mpc_header['title_gain_db'] = (float) $thisfile_mpc_header['raw']['title_gain'] / 100;
		}

		$thisfile_mpc_header['album_peak']        = $thisfile_mpc_header['raw']['album_peak'];
		$thisfile_mpc_header['album_peak_db']     = $this->MPCpeakDBLookup($thisfile_mpc_header['album_peak']);
		if ($thisfile_mpc_header['raw']['album_gain'] < 0) {
			$thisfile_mpc_header['album_gain_db'] = (float) (32768 + $thisfile_mpc_header['raw']['album_gain']) / -100;
		} else {
			$thisfile_mpc_header['album_gain_db'] = (float) $thisfile_mpc_header['raw']['album_gain'] / 100;;
		}
		$thisfile_mpc_header['encoder_version']   = $this->MPCencoderVersionLookup($thisfile_mpc_header['raw']['encoder_version']);

		$ThisFileInfo['replay_gain']['track']['adjustment'] = $thisfile_mpc_header['title_gain_db'];
		$ThisFileInfo['replay_gain']['album']['adjustment'] = $thisfile_mpc_header['album_gain_db'];

		if ($thisfile_mpc_header['title_peak'] > 0) {
			$ThisFileInfo['replay_gain']['track']['peak'] = $thisfile_mpc_header['title_peak'];
		} elseif (round($thisfile_mpc_header['max_level'] * 1.18) > 0) {
			$ThisFileInfo['replay_gain']['track']['peak'] = getid3_lib::CastAsInt(round($thisfile_mpc_header['max_level'] * 1.18)); // why? I don't know - see mppdec.c
		}
		if ($thisfile_mpc_header['album_peak'] > 0) {
			$ThisFileInfo['replay_gain']['album']['peak'] = $thisfile_mpc_header['album_peak'];
		}

		//$ThisFileInfo['audio']['encoder'] = 'SV'.$thisfile_mpc_header['stream_major_version'].'.'.$thisfile_mpc_header['stream_minor_version'].', '.$thisfile_mpc_header['encoder_version'];
		$ThisFileInfo['audio']['encoder'] = $thisfile_mpc_header['encoder_version'];
		$ThisFileInfo['audio']['encoder_options'] = $thisfile_mpc_header['profile'];

		return true;
	}

	function MPCprofileNameLookup($profileid) {
		static $MPCprofileNameLookup = array(
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
		return (isset($MPCprofileNameLookup[$profileid]) ? $MPCprofileNameLookup[$profileid] : 'invalid');
	}

	function MPCfrequencyLookup($frequencyid) {
		static $MPCfrequencyLookup = array(
			0 => 44100,
			1 => 48000,
			2 => 37800,
			3 => 32000
		);
		return (isset($MPCfrequencyLookup[$frequencyid]) ? $MPCfrequencyLookup[$frequencyid] : 'invalid');
	}

	function MPCpeakDBLookup($intvalue) {
		if ($intvalue > 0) {
			return ((log10($intvalue) / log10(2)) - 15) * 6;
		}
		return false;
	}

	function MPCencoderVersionLookup($encoderversion) {
		//Encoder version * 100  (106 = 1.06)
		//EncoderVersion % 10 == 0        Release (1.0)
		//EncoderVersion %  2 == 0        Beta (1.06)
		//EncoderVersion %  2 == 1        Alpha (1.05a...z)

		if ($encoderversion == 0) {
			// very old version, not known exactly which
			return 'Buschmann v1.7.0-v1.7.9 or Klemm v0.90-v1.05';
		}

		if (($encoderversion % 10) == 0) {

			// release version
			return number_format($encoderversion / 100, 2);

		} elseif (($encoderversion % 2) == 0) {

			// beta version
			return number_format($encoderversion / 100, 2).' beta';

		}

		// alpha version
		return number_format($encoderversion / 100, 2).' alpha';
	}

}


?>