<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
// See readme.txt for more details                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio.dts.php                                        //
// module for analyzing DTS Audio files                        //
// dependencies: NONE                                          //
//                                                             //
/////////////////////////////////////////////////////////////////


class getid3_dts extends getid3_handler
{

	function Analyze() {
		$info = &$this->getid3->info;

		// Specs taken from "DTS Coherent Acoustics;Core and Extensions,  ETSI TS 102 114 V1.2.1 (2002-12)"
		// (http://pda.etsi.org/pda/queryform.asp)
		// With thanks to Gambit <macteam@users.sourceforge.net> http://mac.sourceforge.net/atl/

		$info['fileformat'] = 'dts';

		fseek($this->getid3->fp, $info['avdataoffset'], SEEK_SET);
		$DTSheader = fread($this->getid3->fp, 16);

		$info['dts']['raw']['magic'] = substr($DTSheader, 0, 4);
		$magic = "\x7F\xFE\x80\x01";
		if ($info['dts']['raw']['magic'] != $magic) {
			$info['error'][] = 'Expecting "'.getid3_lib::PrintHexBytes($magic).'" at offset '.$info['avdataoffset'].', found "'.getid3_lib::PrintHexBytes($info['dts']['raw']['magic']).'"';
			unset($info['fileformat']);
			unset($info['dts']);
			return false;
		}

		$fhBS = getid3_lib::BigEndian2Bin(substr($DTSheader,  4,  12));
		$bsOffset = 0;
		$info['dts']['raw']['frame_type']             =        bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
		$info['dts']['raw']['deficit_samples']        =        bindec(substr($fhBS, $bsOffset,  5)); $bsOffset +=  5;
		$info['dts']['flags']['crc_present']          = (bool) bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
		$info['dts']['raw']['pcm_sample_blocks']      =        bindec(substr($fhBS, $bsOffset,  7)); $bsOffset +=  7;
		$info['dts']['raw']['frame_byte_size']        =        bindec(substr($fhBS, $bsOffset, 14)); $bsOffset += 14;
		$info['dts']['raw']['channel_arrangement']    =        bindec(substr($fhBS, $bsOffset,  6)); $bsOffset +=  6;
		$info['dts']['raw']['sample_frequency']       =        bindec(substr($fhBS, $bsOffset,  4)); $bsOffset +=  4;
		$info['dts']['raw']['bitrate']                =        bindec(substr($fhBS, $bsOffset,  5)); $bsOffset +=  5;
		$info['dts']['flags']['embedded_downmix']     = (bool) bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
		$info['dts']['flags']['dynamicrange']         = (bool) bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
		$info['dts']['flags']['timestamp']            = (bool) bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
		$info['dts']['flags']['auxdata']              = (bool) bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
		$info['dts']['flags']['hdcd']                 = (bool) bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
		$info['dts']['raw']['extension_audio']        =        bindec(substr($fhBS, $bsOffset,  3)); $bsOffset +=  3;
		$info['dts']['flags']['extended_coding']      = (bool) bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
		$info['dts']['flags']['audio_sync_insertion'] = (bool) bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
		$info['dts']['raw']['lfe_effects']            =        bindec(substr($fhBS, $bsOffset,  2)); $bsOffset +=  2;
		$info['dts']['flags']['predictor_history']    = (bool) bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
		if ($info['dts']['flags']['crc_present']) {
			$info['dts']['raw']['crc16']              =        bindec(substr($fhBS, $bsOffset, 16)); $bsOffset += 16;
		}
		$info['dts']['flags']['mri_perfect_reconst']  = (bool) bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
		$info['dts']['raw']['encoder_soft_version']   =        bindec(substr($fhBS, $bsOffset,  4)); $bsOffset +=  4;
		$info['dts']['raw']['copy_history']           =        bindec(substr($fhBS, $bsOffset,  2)); $bsOffset +=  2;
		$info['dts']['raw']['bits_per_sample']        =        bindec(substr($fhBS, $bsOffset,  2)); $bsOffset +=  2;
		$info['dts']['flags']['surround_es']          = (bool) bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
		$info['dts']['flags']['front_sum_diff']       = (bool) bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
		$info['dts']['flags']['surround_sum_diff']    = (bool) bindec(substr($fhBS, $bsOffset,  1)); $bsOffset +=  1;
		$info['dts']['raw']['dialog_normalization']   =        bindec(substr($fhBS, $bsOffset,  4)); $bsOffset +=  4;


		$info['dts']['bitrate']              = $this->DTSbitrateLookup($info['dts']['raw']['bitrate']);
		$info['dts']['bits_per_sample']      = $this->DTSbitPerSampleLookup($info['dts']['raw']['bits_per_sample']);
		$info['dts']['sample_rate']          = $this->DTSsampleRateLookup($info['dts']['raw']['sample_frequency']);
		$info['dts']['dialog_normalization'] = $this->DTSdialogNormalization($info['dts']['raw']['dialog_normalization'], $info['dts']['raw']['encoder_soft_version']);
		$info['dts']['flags']['lossless']    = (($info['dts']['raw']['bitrate'] == 31) ? true  : false);
		$info['dts']['bitrate_mode']         = (($info['dts']['raw']['bitrate'] == 30) ? 'vbr' : 'cbr');
		$info['dts']['channels']             = $this->DTSnumChannelsLookup($info['dts']['raw']['channel_arrangement']);
		$info['dts']['channel_arrangement']  = $this->DTSchannelArrangementLookup($info['dts']['raw']['channel_arrangement']);

		$info['audio']['dataformat']          = 'dts';
		$info['audio']['lossless']            = $info['dts']['flags']['lossless'];
		$info['audio']['bitrate_mode']        = $info['dts']['bitrate_mode'];
		$info['audio']['bits_per_sample']     = $info['dts']['bits_per_sample'];
		$info['audio']['sample_rate']         = $info['dts']['sample_rate'];
		$info['audio']['channels']            = $info['dts']['channels'];
		$info['audio']['bitrate']             = $info['dts']['bitrate'];
		if (isset($info['avdataend'])) {
			$info['playtime_seconds']         = ($info['avdataend'] - $info['avdataoffset']) / ($info['dts']['bitrate'] / 8);
		}

		return true;
	}

	function DTSbitrateLookup($index) {
		$DTSbitrateLookup = array(
			0  => 32000,
			1  => 56000,
			2  => 64000,
			3  => 96000,
			4  => 112000,
			5  => 128000,
			6  => 192000,
			7  => 224000,
			8  => 256000,
			9  => 320000,
			10 => 384000,
			11 => 448000,
			12 => 512000,
			13 => 576000,
			14 => 640000,
			15 => 768000,
			16 => 960000,
			17 => 1024000,
			18 => 1152000,
			19 => 1280000,
			20 => 1344000,
			21 => 1408000,
			22 => 1411200,
			23 => 1472000,
			24 => 1536000,
			25 => 1920000,
			26 => 2048000,
			27 => 3072000,
			28 => 3840000,
			29 => 'open',
			30 => 'variable',
			31 => 'lossless'
		);
		return (isset($DTSbitrateLookup[$index]) ? $DTSbitrateLookup[$index] : false);
	}

	function DTSsampleRateLookup($index) {
		$DTSsampleRateLookup = array(
			0  => 'invalid',
			1  => 8000,
			2  => 16000,
			3  => 32000,
			4  => 'invalid',
			5  => 'invalid',
			6  => 11025,
			7  => 22050,
			8  => 44100,
			9  => 'invalid',
			10 => 'invalid',
			11 => 12000,
			12 => 24000,
			13 => 48000,
			14 => 'invalid',
			15 => 'invalid'
		);
		return (isset($DTSsampleRateLookup[$index]) ? $DTSsampleRateLookup[$index] : false);
	}

	function DTSbitPerSampleLookup($index) {
		$DTSbitPerSampleLookup = array(
			0  => 16,
			1  => 20,
			2  => 24,
			3  => 24,
		);
		return (isset($DTSbitPerSampleLookup[$index]) ? $DTSbitPerSampleLookup[$index] : false);
	}

	function DTSnumChannelsLookup($index) {
		switch ($index) {
			case 0:
				return 1;
				break;
			case 1:
			case 2:
			case 3:
			case 4:
				return 2;
				break;
			case 5:
			case 6:
				return 3;
				break;
			case 7:
			case 8:
				return 4;
				break;
			case 9:
				return 5;
				break;
			case 10:
			case 11:
			case 12:
				return 6;
				break;
			case 13:
				return 7;
				break;
			case 14:
			case 15:
				return 8;
				break;
		}
		return false;
	}

	function DTSchannelArrangementLookup($index) {
		$DTSchannelArrangementLookup = array(
			0  => 'A',
			1  => 'A + B (dual mono)',
			2  => 'L + R (stereo)',
			3  => '(L+R) + (L-R) (sum-difference)',
			4  => 'LT + RT (left and right total)',
			5  => 'C + L + R',
			6  => 'L + R + S',
			7  => 'C + L + R + S',
			8  => 'L + R + SL + SR',
			9  => 'C + L + R + SL + SR',
			10 => 'CL + CR + L + R + SL + SR',
			11 => 'C + L + R+ LR + RR + OV',
			12 => 'CF + CR + LF + RF + LR + RR',
			13 => 'CL + C + CR + L + R + SL + SR',
			14 => 'CL + CR + L + R + SL1 + SL2 + SR1 + SR2',
			15 => 'CL + C+ CR + L + R + SL + S + SR',
		);
		return (isset($DTSchannelArrangementLookup[$index]) ? $DTSchannelArrangementLookup[$index] : 'user-defined');
	}

	function DTSdialogNormalization($index, $version) {
		switch ($version) {
			case 7:
				return 0 - $index;
				break;
			case 6:
				return 0 - 16 - $index;
				break;
		}
		return false;
	}

}


?>