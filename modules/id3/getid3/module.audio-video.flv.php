<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
//                                                             //
//  FLV module by Seth Kaufman <seth@whirl-i-gig.com>          //
//  * version 0.1 (26 June 2005)                               //
//  minor modifications by James Heinrich <info@getid3.org>    //
//  * version 0.1.1 (15 July 2005)                             //
/////////////////////////////////////////////////////////////////
//                                                             //
// module.audio-video.flv.php                                  //
// module for analyzing Shockwave Flash Video files            //
// dependencies: NONE                                          //
//                                                            ///
/////////////////////////////////////////////////////////////////


class getid3_flv
{

	function getid3_flv(&$fd, &$ThisFileInfo, $ReturnAllTagData=false) {
		fseek($fd, $ThisFileInfo['avdataoffset'], SEEK_SET);

		$FLVfileData = fread($fd, $ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']);

		$FLVmagic = substr($FLVfileData, 0, 3);
		if ($FLVmagic != 'FLV') {
			$ThisFileInfo['error'][] = 'Expecting "FLV" at offset '.$ThisFileInfo['avdataoffset'].', found "'.$ThisFileInfo['flv']['header']['signature'].'"';
			unset($ThisFileInfo['flv']);
			unset($ThisFileInfo['fileformat']);
			return false;
		}
		$ThisFileInfo['flv']['header']['signature'] = $FLVmagic;
		$ThisFileInfo['flv']['header']['version']   = ord($FLVfileData{3});
		$ThisFileInfo['fileformat'] = 'flv';

		$TypeFlags = ord($FLVfileData{4});
		$ThisFileInfo['flv']['header']['hasAudio'] = (bool) ($TypeFlags & 4);
		$ThisFileInfo['flv']['header']['hasVideo'] = (bool) ($TypeFlags & 1);

		$FrameSizeDataLength = getid3_lib::BigEndian2Int(substr($FLVfileData, 5, 4));

		// FLV tags
		$CurrentOffset = $FrameSizeDataLength;
		$FLVdataLength = strlen($FLVfileData);

		$Duration = 0;

		$SoundFormat = null;
		$VideoFormat = null;
		while ($CurrentOffset < $FLVdataLength) {
			// previous tag size
			$PreviousTagLength = getid3_lib::BigEndian2Int(substr($FLVfileData, $CurrentOffset, 4));
			$CurrentOffset += 4;

			$TagType = ord(substr($FLVfileData, $CurrentOffset, 1));
			$DataLength = getid3_lib::BigEndian2Int(substr($FLVfileData, $CurrentOffset + 1, 3));
			$Timestamp  = getid3_lib::BigEndian2Int(substr($FLVfileData, $CurrentOffset + 4, 3));

			switch ($TagType) {
				case 8:
					if (is_null($SoundFormat)) {
						$SoundInfo = ord(substr($FLVfileData, $CurrentOffset + 11, 1));
						$SoundFormat = $SoundInfo & 0x07;
						$ThisFileInfo['flv']['audio']['audioFormat']     = $SoundFormat;
						$ThisFileInfo['flv']['audio']['audioRate']       = ($SoundInfo & 0x30) / 0x10;
						$ThisFileInfo['flv']['audio']['audioSampleSize'] = ($SoundInfo & 0x40) / 0x40;
						$ThisFileInfo['flv']['audio']['audioType']       = ($SoundInfo & 0x80) / 0x80;
					}
					break;

				case 9:
					if (is_null($VideoFormat)) {
						$VideoInfo = ord(substr($FLVfileData, $CurrentOffset + 11, 1));
						$VideoFormat = $VideoInfo & 0x07;
						$ThisFileInfo['flv']['video']['videoCodec'] = $VideoFormat;

						$PictureSizeType = (getid3_lib::BigEndian2Int(substr($FLVfileData, $CurrentOffset + 15, 2))) >> 7;
						$PictureSizeType = $PictureSizeType & 0x0007;
						$ThisFileInfo['flv']['header']['videoSizeType'] = $PictureSizeType;
						switch ($PictureSizeType) {
							case 0:
								$PictureSizeEnc = getid3_lib::BigEndian2Int(substr($FLVfileData, $CurrentOffset + 16, 2));
								$PictureSizeEnc <<= 1;
								$ThisFileInfo['video']['resolution_x'] = ($PictureSizeEnc & 0xFF00) >> 8;
								$PictureSizeEnc = getid3_lib::BigEndian2Int(substr($FLVfileData, $CurrentOffset + 17, 2));
								$PictureSizeEnc <<= 1;
								$ThisFileInfo['video']['resolution_y'] = ($PictureSizeEnc & 0xFF00) >> 8;
								break;

							case 1:
								$PictureSizeEnc = getid3_lib::BigEndian2Int(substr($FLVfileData, $CurrentOffset + 16, 4));
								$PictureSizeEnc <<= 1;
								$ThisFileInfo['video']['resolution_x'] = ($PictureSizeEnc & 0xFFFF0000) >> 16;

								$PictureSizeEnc = getid3_lib::BigEndian2Int(substr($FLVfileData, $CurrentOffset + 18, 4));
								$PictureSizeEnc <<= 1;
								$ThisFileInfo['video']['resolution_y'] = ($PictureSizeEnc & 0xFFFF0000) >> 16;
								break;

							case 2:
								$ThisFileInfo['video']['resolution_x'] = 352;
								$ThisFileInfo['video']['resolution_y'] = 288;
								break;

							case 3:
								$ThisFileInfo['video']['resolution_x'] = 176;
								$ThisFileInfo['video']['resolution_y'] = 144;
								break;

							case 4:
								$ThisFileInfo['video']['resolution_x'] = 128;
								$ThisFileInfo['video']['resolution_y'] = 96;
								break;

							case 5:
								$ThisFileInfo['video']['resolution_x'] = 320;
								$ThisFileInfo['video']['resolution_y'] = 240;
								break;

							case 6:
								$ThisFileInfo['video']['resolution_x'] = 160;
								$ThisFileInfo['video']['resolution_y'] = 120;
								break;

							default:
								$ThisFileInfo['video']['resolution_x'] = 0;
								$ThisFileInfo['video']['resolution_y'] = 0;
								break;

						}
					}
					break;

				default:
					// noop
					break;
			}

			if ($Timestamp > $Duration) {
				$Duration = $Timestamp;
			}

			$CurrentOffset += ($DataLength + 11);
		}

		$ThisFileInfo['playtime_seconds'] = $Duration / 1000;
		$ThisFileInfo['bitrate'] = ($ThisFileInfo['avdataend'] - $ThisFileInfo['avdataoffset']) / $ThisFileInfo['playtime_seconds'];

		if ($ThisFileInfo['flv']['header']['hasAudio']) {
			$ThisFileInfo['audio']['codec']           =   $this->FLVaudioFormat($ThisFileInfo['flv']['audio']['audioFormat']);
			$ThisFileInfo['audio']['sample_rate']     =     $this->FLVaudioRate($ThisFileInfo['flv']['audio']['audioRate']);
			$ThisFileInfo['audio']['bits_per_sample'] = $this->FLVaudioBitDepth($ThisFileInfo['flv']['audio']['audioSampleSize']);

			$ThisFileInfo['audio']['channels']   = $ThisFileInfo['flv']['audio']['audioType'] + 1; // 0=mono,1=stereo
			$ThisFileInfo['audio']['lossless']   = ($ThisFileInfo['flv']['audio']['audioFormat'] ? false : true); // 0=uncompressed
			$ThisFileInfo['audio']['dataformat'] = 'flv';
		}
		if (@$ThisFileInfo['flv']['header']['hasVideo']) {
			$ThisFileInfo['video']['codec']      =   $this->FLVvideoCodec($ThisFileInfo['flv']['video']['videoCodec']);
			$ThisFileInfo['video']['dataformat'] = 'flv';
			$ThisFileInfo['video']['lossless']   = false;
		}

		return true;
	}


	function FLVaudioFormat($id) {
		$FLVaudioFormat = array(
			0 => 'uncompressed',
			1 => 'ADPCM',
			2 => 'mp3',
			5 => 'Nellymoser 8kHz mono',
			6 => 'Nellymoser',
		);
		return (@$FLVaudioFormat[$id] ? @$FLVaudioFormat[$id] : false);
	}

	function FLVaudioRate($id) {
		$FLVaudioRate = array(
			0 => 5500,
			1 => 11025,
			2 => 22050,
			3 => 44100,
		);
		return (@$FLVaudioRate[$id] ? @$FLVaudioRate[$id] : false);
	}

	function FLVaudioBitDepth($id) {
		$FLVaudioBitDepth = array(
			0 => 8,
			1 => 16,
		);
		return (@$FLVaudioBitDepth[$id] ? @$FLVaudioBitDepth[$id] : false);
	}

	function FLVvideoCodec($id) {
		$FLVaudioBitDepth = array(
			2 => 'Sorenson H.263',
			3 => 'Screen video',
		);
		return (@$FLVaudioBitDepth[$id] ? @$FLVaudioBitDepth[$id] : false);
	}

}

?>