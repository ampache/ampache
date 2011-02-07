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
// | module.audio-video.riff.php                                          |
// | module for analyzing RIFF files:                                     |
// |    Wave, AVI, AIFF/AIFC, (MP3,AC3)/RIFF, Wavpack3, 8SVX              |
// | dependencies: module.audio.mp3.php (optional)                        |
// |               module.audio.ac3.php (optional)                        |
// |               module.audio.dts.php (optional)                        |
// |               module.audio-video.mpeg.php (optional)                 |
// +----------------------------------------------------------------------+
//
// $Id: module.audio-video.riff.php,v 1.10 2006/12/03 20:13:17 ah Exp $



class getid3_riff extends getid3_handler
{

	private $endian_function = 'LittleEndian2Int';


	public function Analyze() {

		$getid3 = $this->getid3;

		$getid3->info['riff']['raw'] = array ();
		$info_riff             = &$getid3->info['riff'];
		$info_riff_raw         = &$info_riff['raw'];
		$info_audio            = &$getid3->info['audio'];
		$info_video            = &$getid3->info['video'];
		$info_avdataoffset     = &$getid3->info['avdataoffset'];
		$info_avdataend        = &$getid3->info['avdataend'];
		$info_audio_dataformat = &$info_audio['dataformat'];
		$info_riff_audio       = &$info_riff['audio'];
		$info_riff_video       = &$info_riff['video'];

		$original['avdataend'] = $info_avdataend;

		$this->fseek($info_avdataoffset, SEEK_SET);
		$riff_header   = $this->fread(12);

		$riff_sub_type = substr($riff_header, 8, 4);

		switch (substr($riff_header, 0, 4)) {

			case 'FORM':
				$getid3->info['fileformat'] = 'aiff';
				$this->endian_function      = 'BigEndian2Int';
				$riff_header_size           = getid3_lib::BigEndian2Int(substr($riff_header, 4, 4));
				$info_riff[$riff_sub_type]  = $this->ParseRIFF($info_avdataoffset + 12, $info_avdataoffset + $riff_header_size);
				$info_riff['header_size']   = $riff_header_size;
				break;


			case 'RIFF':
			case 'SDSS':  // SDSS is identical to RIFF, just renamed. Used by SmartSound QuickTracks (www.smartsound.com)
			case 'RMP3':  // RMP3 is identical to RIFF, just renamed. Used by [unknown program] when creating RIFF-MP3s

				if ($riff_sub_type == 'RMP3') {
					$riff_sub_type = 'WAVE';
				}

				$getid3->info['fileformat'] = 'riff';
				$this->endian_function      = 'LittleEndian2Int';
				$riff_header_size           = getid3_lib::LittleEndian2Int(substr($riff_header, 4, 4));
				$info_riff[$riff_sub_type]  = $this->ParseRIFF($info_avdataoffset + 12, $info_avdataoffset + $riff_header_size);
				$info_riff['header_size']   = $riff_header_size;
				if ($riff_sub_type == 'WAVE') {
					$info_riff_wave = &$info_riff['WAVE'];
				}
				break;


			default:
				throw new getid3_exception('Cannot parse RIFF (this is maybe not a RIFF / WAV / AVI file?) - expecting "FORM|RIFF|SDSS|RMP3" found "'.$riff_sub_type.'" instead');
		}

		$endian_function = $this->endian_function;

		$stream_index = 0;
		switch ($riff_sub_type) {

			case 'WAVE':

				if (empty($info_audio['bitrate_mode'])) {
					$info_audio['bitrate_mode'] = 'cbr';
				}

				if (empty($info_audio_dataformat)) {
					$info_audio_dataformat = 'wav';
				}

				if (isset($info_riff_wave['data'][0]['offset'])) {
					$info_avdataoffset = $info_riff_wave['data'][0]['offset'] + 8;
					$info_avdataend    = $info_avdataoffset + $info_riff_wave['data'][0]['size'];
				}

				if (isset($info_riff_wave['fmt '][0]['data'])) {

					$info_riff_audio[$stream_index] = getid3_riff::RIFFparseWAVEFORMATex($info_riff_wave['fmt '][0]['data']);
					$info_audio['wformattag'] = $info_riff_audio[$stream_index]['raw']['wFormatTag'];
					$info_riff_raw['fmt '] = $info_riff_audio[$stream_index]['raw'];
					unset($info_riff_audio[$stream_index]['raw']);
					$info_audio['streams'][$stream_index] = $info_riff_audio[$stream_index];

					$info_audio = getid3_riff::array_merge_noclobber($info_audio, $info_riff_audio[$stream_index]);
					if (substr($info_audio['codec'], 0, strlen('unknown: 0x')) == 'unknown: 0x') {
						$getid3->warning('Audio codec = '.$info_audio['codec']);
					}
					$info_audio['bitrate'] = $info_riff_audio[$stream_index]['bitrate'];

					$getid3->info['playtime_seconds'] = (float)((($info_avdataend - $info_avdataoffset) * 8) / $info_audio['bitrate']);

					$info_audio['lossless'] = false;

					if (isset($info_riff_wave['data'][0]['offset']) && isset($info_riff_raw['fmt ']['wFormatTag'])) {

						switch ($info_riff_raw['fmt ']['wFormatTag']) {

							case 0x0001:  // PCM
								$info_audio['lossless'] = true;
								break;

							case 0x2000:  // AC-3
								$info_audio_dataformat = 'ac3';
								break;

							default:
								// do nothing
								break;

						}
					}

					$info_audio['streams'][$stream_index]['wformattag']   = $info_audio['wformattag'];
					$info_audio['streams'][$stream_index]['bitrate_mode'] = $info_audio['bitrate_mode'];
					$info_audio['streams'][$stream_index]['lossless']     = $info_audio['lossless'];
					$info_audio['streams'][$stream_index]['dataformat']   = $info_audio_dataformat;
				}


				if (isset($info_riff_wave['rgad'][0]['data'])) {

					// shortcuts
					$rgadData = &$info_riff_wave['rgad'][0]['data'];
					$info_riff_raw['rgad']    = array ('track'=>array(), 'album'=>array());
					$info_riff_raw_rgad       = &$info_riff_raw['rgad'];
					$info_riff_raw_rgad_track = &$info_riff_raw_rgad['track'];
					$info_riff_raw_rgad_album = &$info_riff_raw_rgad['album'];

					$info_riff_raw_rgad['fPeakAmplitude']      = getid3_riff::BigEndian2Float(strrev(substr($rgadData, 0, 4)));   // LittleEndian2Float()
					$info_riff_raw_rgad['nRadioRgAdjust']      = getid3_lib::$endian_function(substr($rgadData, 4, 2));
					$info_riff_raw_rgad['nAudiophileRgAdjust'] = getid3_lib::$endian_function(substr($rgadData, 6, 2));

					$n_track_rg_adjust_bit_string              = str_pad(decbin($info_riff_raw_rgad['nRadioRgAdjust']),      16, '0', STR_PAD_LEFT);
					$n_album_rg_adjust_bit_string              = str_pad(decbin($info_riff_raw_rgad['nAudiophileRgAdjust']), 16, '0', STR_PAD_LEFT);

					$info_riff_raw_rgad_track['name']          = bindec(substr($n_track_rg_adjust_bit_string, 0, 3));
					$info_riff_raw_rgad_track['originator']    = bindec(substr($n_track_rg_adjust_bit_string, 3, 3));
					$info_riff_raw_rgad_track['signbit']       = bindec($n_track_rg_adjust_bit_string[6]);
					$info_riff_raw_rgad_track['adjustment']    = bindec(substr($n_track_rg_adjust_bit_string, 7, 9));
					$info_riff_raw_rgad_album['name']          = bindec(substr($n_album_rg_adjust_bit_string, 0, 3));
					$info_riff_raw_rgad_album['originator']    = bindec(substr($n_album_rg_adjust_bit_string, 3, 3));
					$info_riff_raw_rgad_album['signbit']       = bindec($n_album_rg_adjust_bit_string[6]);
					$info_riff_raw_rgad_album['adjustment']    = bindec(substr($n_album_rg_adjust_bit_string, 7, 9));

					$info_riff['rgad']['peakamplitude'] = $info_riff_raw_rgad['fPeakAmplitude'];
					if (($info_riff_raw_rgad_track['name'] != 0) && ($info_riff_raw_rgad_track['originator'] != 0)) {
						$info_riff['rgad']['track']['name']       = getid3_lib_replaygain::NameLookup($info_riff_raw_rgad_track['name']);
						$info_riff['rgad']['track']['originator'] = getid3_lib_replaygain::OriginatorLookup($info_riff_raw_rgad_track['originator']);
						$info_riff['rgad']['track']['adjustment'] = getid3_lib_replaygain::AdjustmentLookup($info_riff_raw_rgad_track['adjustment'], $info_riff_raw_rgad_track['signbit']);
					}

					if (($info_riff_raw_rgad_album['name'] != 0) && ($info_riff_raw_rgad_album['originator'] != 0)) {
						$info_riff['rgad']['album']['name']       = getid3_lib_replaygain::NameLookup($info_riff_raw_rgad_album['name']);
						$info_riff['rgad']['album']['originator'] = getid3_lib_replaygain::OriginatorLookup($info_riff_raw_rgad_album['originator']);
						$info_riff['rgad']['album']['adjustment'] = getid3_lib_replaygain::AdjustmentLookup($info_riff_raw_rgad_album['adjustment'], $info_riff_raw_rgad_album['signbit']);
					}
				}

				if (isset($info_riff_wave['fact'][0]['data'])) {

					$info_riff_raw['fact']['NumberOfSamples'] = getid3_lib::$endian_function(substr($info_riff_wave['fact'][0]['data'], 0, 4));

					// This should be a good way of calculating exact playtime, but some sample files have had incorrect number of samples, so cannot use this method
					// if (!empty($info_riff_raw['fmt ']['nSamplesPerSec'])) {
					//     $getid3->info['playtime_seconds'] = (float)$info_riff_raw['fact']['NumberOfSamples'] / $info_riff_raw['fmt ']['nSamplesPerSec'];
					// }
				}


				if (!empty($info_riff_raw['fmt ']['nAvgBytesPerSec'])) {
					$info_audio['bitrate'] = (int)$info_riff_raw['fmt ']['nAvgBytesPerSec'] * 8;
				}

				if (isset($info_riff_wave['bext'][0]['data'])) {

					$info_riff_wave_bext_0 = &$info_riff_wave['bext'][0];

					getid3_lib::ReadSequence('LittleEndian2Int', $info_riff_wave_bext_0, $info_riff_wave_bext_0['data'], 0,
						array (
							'title'          => -256,
							'author'         =>  -32,
							'reference'      =>  -32,
							'origin_date'    =>  -10,
							'origin_time'    =>   -8,
							'time_reference' =>    8,
							'bwf_version'    =>    1,
							'reserved'       =>  254
						)
					);

					foreach (array ('title', 'author', 'reference') as $key) {
						$info_riff_wave_bext_0[$key] = trim($info_riff_wave_bext_0[$key]);
					}

					$info_riff_wave_bext_0['coding_history'] = explode("\r\n", trim(substr($info_riff_wave_bext_0['data'], 601)));

					$info_riff_wave_bext_0['origin_date_unix'] = gmmktime(substr($info_riff_wave_bext_0['origin_time'], 0, 2),
																		  substr($info_riff_wave_bext_0['origin_time'], 3, 2),
																		  substr($info_riff_wave_bext_0['origin_time'], 6, 2),
																		  substr($info_riff_wave_bext_0['origin_date'], 5, 2),
																		  substr($info_riff_wave_bext_0['origin_date'], 8, 2),
																		  substr($info_riff_wave_bext_0['origin_date'], 0, 4));

					$info_riff['comments']['author'][] = $info_riff_wave_bext_0['author'];
					$info_riff['comments']['title'][]  = $info_riff_wave_bext_0['title'];
				}

				if (isset($info_riff_wave['MEXT'][0]['data'])) {

					$info_riff_wave_mext_0 = &$info_riff_wave['MEXT'][0];

					$info_riff_wave_mext_0['raw']['sound_information']      = getid3_lib::LittleEndian2Int(substr($info_riff_wave_mext_0['data'], 0, 2));
					$info_riff_wave_mext_0['flags']['homogenous']           = (bool)($info_riff_wave_mext_0['raw']['sound_information'] & 0x0001);
					if ($info_riff_wave_mext_0['flags']['homogenous']) {
						$info_riff_wave_mext_0['flags']['padding']          = ($info_riff_wave_mext_0['raw']['sound_information'] & 0x0002) ? false : true;
						$info_riff_wave_mext_0['flags']['22_or_44']         = (bool)($info_riff_wave_mext_0['raw']['sound_information'] & 0x0004);
						$info_riff_wave_mext_0['flags']['free_format']      = (bool)($info_riff_wave_mext_0['raw']['sound_information'] & 0x0008);

						$info_riff_wave_mext_0['nominal_frame_size']        = getid3_lib::LittleEndian2Int(substr($info_riff_wave_mext_0['data'], 2, 2));
					}
					$info_riff_wave_mext_0['anciliary_data_length']         = getid3_lib::LittleEndian2Int(substr($info_riff_wave_mext_0['data'], 6, 2));
					$info_riff_wave_mext_0['raw']['anciliary_data_def']     = getid3_lib::LittleEndian2Int(substr($info_riff_wave_mext_0['data'], 8, 2));
					$info_riff_wave_mext_0['flags']['anciliary_data_left']  = (bool)($info_riff_wave_mext_0['raw']['anciliary_data_def'] & 0x0001);
					$info_riff_wave_mext_0['flags']['anciliary_data_free']  = (bool)($info_riff_wave_mext_0['raw']['anciliary_data_def'] & 0x0002);
					$info_riff_wave_mext_0['flags']['anciliary_data_right'] = (bool)($info_riff_wave_mext_0['raw']['anciliary_data_def'] & 0x0004);
				}

				if (isset($info_riff_wave['cart'][0]['data'])) {

					$info_riff_wave_cart_0 = &$info_riff_wave['cart'][0];

					getid3_lib::ReadSequence('LittleEndian2Int', $info_riff_wave_cart_0, $info_riff_wave_cart_0['data'], 0,
						array (
							'version'              => -4,
							'title'                => -64,
							'artist'               => -64,
							'cut_id'               => -64,
							'client_id'            => -64,
							'category'             => -64,
							'classification'       => -64,
							'out_cue'              => -64,
							'start_date'           => -10,
							'start_time'           => -8,
							'end_date'             => -10,
							'end_time'             => -8,
							'producer_app_id'      => -64,
							'producer_app_version' => -64,
							'user_defined_text'    => -64,
						)
					);

					foreach (array ('artist', 'cut_id', 'client_id', 'category', 'classification', 'out_cue', 'start_date', 'start_time', 'end_date', 'end_time', 'producer_app_id', 'producer_app_version', 'user_defined_text') as $key) {
						$info_riff_wave_cart_0[$key] = trim($info_riff_wave_cart_0[$key]);
					}

					$info_riff_wave_cart_0['zero_db_reference'] = getid3_lib::LittleEndian2Int(substr($info_riff_wave_cart_0['data'], 680, 4), true);

					for ($i = 0; $i < 8; $i++) {
						$info_riff_wave_cart_0['post_time'][$i]['usage_fourcc'] =                              substr($info_riff_wave_cart_0['data'], 684 + ($i * 8), 4);
						$info_riff_wave_cart_0['post_time'][$i]['timer_value']  = getid3_lib::LittleEndian2Int(substr($info_riff_wave_cart_0['data'], 684 + ($i * 8) + 4, 4));
					}
					$info_riff_wave_cart_0['url']              =                 trim(substr($info_riff_wave_cart_0['data'],  748, 1024));
					$info_riff_wave_cart_0['tag_text']         = explode("\r\n", trim(substr($info_riff_wave_cart_0['data'], 1772)));

					$info_riff['comments']['artist'][] = $info_riff_wave_cart_0['artist'];
					$info_riff['comments']['title'][]  = $info_riff_wave_cart_0['title'];
				}


				if (isset($info_riff_wave['SNDM'][0]['data'])) {
					// SoundMiner metadata

					// shortcuts
					$info_riff_wave_SNDM_0      = &$info_riff_wave['SNDM'][0];
					$info_riff_wave_SNDM_0_data = &$info_riff_wave_SNDM_0['data'];
					$SNDM_startoffset = 0;
					$SNDM_endoffset   = $info_riff_wave_SNDM_0['size'];

					while ($SNDM_startoffset < $SNDM_endoffset) {
						$SNDM_thisTagOffset = 0;
						$SNDM_thisTagSize      = getid3_lib::BigEndian2Int(substr($info_riff_wave_SNDM_0_data, $SNDM_startoffset + $SNDM_thisTagOffset, 4));
						$SNDM_thisTagOffset += 4;
						$SNDM_thisTagKey       =                           substr($info_riff_wave_SNDM_0_data, $SNDM_startoffset + $SNDM_thisTagOffset, 4);
						$SNDM_thisTagOffset += 4;
						$SNDM_thisTagDataSize  = getid3_lib::BigEndian2Int(substr($info_riff_wave_SNDM_0_data, $SNDM_startoffset + $SNDM_thisTagOffset, 2));
						$SNDM_thisTagOffset += 2;
						$SNDM_thisTagDataFlags = getid3_lib::BigEndian2Int(substr($info_riff_wave_SNDM_0_data, $SNDM_startoffset + $SNDM_thisTagOffset, 2));
						$SNDM_thisTagOffset += 2;
						$SNDM_thisTagDataText =                            substr($info_riff_wave_SNDM_0_data, $SNDM_startoffset + $SNDM_thisTagOffset, $SNDM_thisTagDataSize);
						$SNDM_thisTagOffset += $SNDM_thisTagDataSize;

						if ($SNDM_thisTagSize != (4 + 4 + 2 + 2 + $SNDM_thisTagDataSize)) {
							$getid3->warning('RIFF.WAVE.SNDM.data contains tag not expected length (expected: '.$SNDM_thisTagSize.', found: '.(4 + 4 + 2 + 2 + $SNDM_thisTagDataSize).') at offset '.$SNDM_startoffset.' (file offset '.($info_riff_wave_SNDM_0['offset'] + $SNDM_startoffset).')');
							break;
						} elseif ($SNDM_thisTagSize <= 0) {
							$getid3->warning('RIFF.WAVE.SNDM.data contains zero-size tag at offset '.$SNDM_startoffset.' (file offset '.($info_riff_wave_SNDM_0['offset'] + $SNDM_startoffset).')');
							break;
						}
						$SNDM_startoffset += $SNDM_thisTagSize;

						$info_riff_wave_SNDM_0['parsed_raw'][$SNDM_thisTagKey] = $SNDM_thisTagDataText;
						$parsedkey = $this->RIFFwaveSNDMtagLookup($SNDM_thisTagKey);
						if ($parsedkey) {
							$info_riff_wave_SNDM_0['parsed'][$parsedkey] = $SNDM_thisTagDataText;
						} else {
							$getid3->warning('RIFF.WAVE.SNDM contains unknown tag "'.$SNDM_thisTagKey.'" at offset '.$SNDM_startoffset.' (file offset '.($info_riff_wave_SNDM_0['offset'] + $SNDM_startoffset).')');
						}
					}

					$tagmapping = array(
						'tracktitle'=>'title',
						'category'  =>'genre',
						'cdtitle'   =>'album',
						'tracktitle'=>'title',
					);
					foreach ($tagmapping as $fromkey => $tokey) {
						if (isset($info_riff_wave_SNDM_0['parsed'][$fromkey])) {
							$info_riff['comments'][$tokey][] = $info_riff_wave_SNDM_0['parsed'][$fromkey];
						}
					}
				}


				if (!isset($info_audio['bitrate']) && isset($info_riff_audio[$stream_index]['bitrate'])) {
					$info_audio['bitrate'] = $info_riff_audio[$stream_index]['bitrate'];
					$getid3->info['playtime_seconds'] = (float)((($info_avdataend - $info_avdataoffset) * 8) / $info_audio['bitrate']);
				}

				if (@$getid3->info['wavpack']) {

					if (!$this->data_string_flag)  {

						$info_audio_dataformat      = 'wavpack';
						$info_audio['bitrate_mode'] = 'vbr';
						$info_audio['encoder']      = 'WavPack v'.$getid3->info['wavpack']['version'];

						// Reset to the way it was - RIFF parsing will have messed this up
						$info_avdataend        = $original['avdataend'];
						$info_audio['bitrate'] = (($info_avdataend - $info_avdataoffset) * 8) / $getid3->info['playtime_seconds'];

						$this->fseek($info_avdataoffset - 44, SEEK_SET);
						$riff_data = $this->fread(44);
						$orignal_riff_header_size = getid3_lib::LittleEndian2Int(substr($riff_data,  4, 4)) +  8;
						$orignal_riff_data_size   = getid3_lib::LittleEndian2Int(substr($riff_data, 40, 4)) + 44;

						if ($orignal_riff_header_size > $orignal_riff_data_size) {
							$info_avdataend -= ($orignal_riff_header_size - $orignal_riff_data_size);
							$this->fseek($info_avdataend, SEEK_SET);
							$riff_data .= $this->fread($orignal_riff_header_size - $orignal_riff_data_size);
						}

						// move the data chunk after all other chunks (if any)
						// so that the RIFF parser doesn't see EOF when trying
						// to skip over the data chunk
						$riff_data = substr($riff_data, 0, 36).substr($riff_data, 44).substr($riff_data, 36, 8);

						// Save audio info key
						$saved_info_audio = $info_audio;

						// Analyze riff_data
						$this->AnalyzeString($riff_data);

						// Restore info key
						$info_audio = $saved_info_audio;
					}
				}

				if (isset($info_riff_raw['fmt ']['wFormatTag'])) {

					switch ($info_riff_raw['fmt ']['wFormatTag']) {

						case 0x08AE: // ClearJump LiteWave
							$info_audio['bitrate_mode'] = 'vbr';
							$info_audio_dataformat      = 'litewave';

							//typedef struct tagSLwFormat {
							//  WORD    m_wCompFormat;     // low byte defines compression method, high byte is compression flags
							//  DWORD   m_dwScale;         // scale factor for lossy compression
							//  DWORD   m_dwBlockSize;     // number of samples in encoded blocks
							//  WORD    m_wQuality;        // alias for the scale factor
							//  WORD    m_wMarkDistance;   // distance between marks in bytes
							//  WORD    m_wReserved;
							//
							//  //following paramters are ignored if CF_FILESRC is not set
							//  DWORD   m_dwOrgSize;       // original file size in bytes
							//  WORD    m_bFactExists;     // indicates if 'fact' chunk exists in the original file
							//  DWORD   m_dwRiffChunkSize; // riff chunk size in the original file
							//
							//  PCMWAVEFORMAT m_OrgWf;     // original wave format
							// }SLwFormat, *PSLwFormat;

							$info_riff['litewave']['raw'] = array ();
							$info_riff_litewave           = &$info_riff['litewave'];
							$info_riff_litewave_raw       = &$info_riff_litewave['raw'];

							getid3_lib::ReadSequence('LittleEndian2Int', $info_riff_litewave_raw, $info_riff_wave['fmt '][0]['data'], 18,
								array (
									'compression_method' => 1,
									'compression_flags'  => 1,
									'm_dwScale'          => 4,
									'm_dwBlockSize'      => 4,
									'm_wQuality'         => 2,
									'm_wMarkDistance'    => 2,
									'm_wReserved'        => 2,
									'm_dwOrgSize'        => 4,
									'm_bFactExists'      => 2,
									'm_dwRiffChunkSize'  => 4
								)
							);

							//$info_riff_litewave['quality_factor'] = intval(round((2000 - $info_riff_litewave_raw['m_dwScale']) / 20));
							$info_riff_litewave['quality_factor'] = $info_riff_litewave_raw['m_wQuality'];

							$info_riff_litewave['flags']['raw_source']    = ($info_riff_litewave_raw['compression_flags'] & 0x01) ? false : true;
							$info_riff_litewave['flags']['vbr_blocksize'] = ($info_riff_litewave_raw['compression_flags'] & 0x02) ? false : true;
							$info_riff_litewave['flags']['seekpoints']    = (bool)($info_riff_litewave_raw['compression_flags'] & 0x04);

							$info_audio['lossless']        = (($info_riff_litewave_raw['m_wQuality'] == 100) ? true : false);
							$info_audio['encoder_options'] = '-q'.$info_riff_litewave['quality_factor'];
							break;
					}
				}

				if ($info_avdataend > $getid3->info['filesize']) {

					switch (@$info_audio_dataformat) {

						case 'wavpack': // WavPack
						case 'lpac':    // LPAC
						case 'ofr':     // OptimFROG
						case 'ofs':     // OptimFROG DualStream
							// lossless compressed audio formats that keep original RIFF headers - skip warning
							break;


						case 'litewave':
							if (($info_avdataend - $getid3->info['filesize']) == 1) {
								// LiteWave appears to incorrectly *not* pad actual output file
								// to nearest WORD boundary so may appear to be short by one
								// byte, in which case - skip warning
							} else {
								// Short by more than one byte, throw warning
								$getid3->warning('Probably truncated file - expecting '.$info_riff[$riff_sub_type]['data'][0]['size'].' bytes of data, only found '.($getid3->info['filesize'] - $info_avdataoffset).' (short by '.($info_riff[$riff_sub_type]['data'][0]['size'] - ($getid3->info['filesize'] - $info_avdataoffset)).' bytes)');
							}
							break;


						default:
							if ((($info_avdataend - $getid3->info['filesize']) == 1) && (($info_riff[$riff_sub_type]['data'][0]['size'] % 2) == 0) && ((($getid3->info['filesize'] - $info_avdataoffset) % 2) == 1)) {
								// output file appears to be incorrectly *not* padded to nearest WORD boundary
								// Output less severe warning
								$getid3->warning('File should probably be padded to nearest WORD boundary, but it is not (expecting '.$info_riff[$riff_sub_type]['data'][0]['size'].' bytes of data, only found '.($getid3->info['filesize'] - $info_avdataoffset).' therefore short by '.($info_riff[$riff_sub_type]['data'][0]['size'] - ($getid3->info['filesize'] - $info_avdataoffset)).' bytes)');
								$info_avdataend = $getid3->info['filesize'];
								break;

							}
							// Short by more than one byte, throw warning
							$getid3->warning('Probably truncated file - expecting '.$info_riff[$riff_sub_type]['data'][0]['size'].' bytes of data, only found '.($getid3->info['filesize'] - $info_avdataoffset).' (short by '.($info_riff[$riff_sub_type]['data'][0]['size'] - ($getid3->info['filesize'] - $info_avdataoffset)).' bytes)');
							$info_avdataend = $getid3->info['filesize'];
							break;
					}
				}

				if (!empty($getid3->info['mpeg']['audio']['LAME']['audio_bytes'])) {
					if ((($info_avdataend - $info_avdataoffset) - $getid3->info['mpeg']['audio']['LAME']['audio_bytes']) == 1) {
						$info_avdataend--;
						$getid3->warning('Extra null byte at end of MP3 data assumed to be RIFF padding and therefore ignored');
					}
				}

				if (@$info_audio_dataformat == 'ac3') {
					unset($info_audio['bits_per_sample']);
					if (!empty($getid3->info['ac3']['bitrate']) && ($getid3->info['ac3']['bitrate'] != $info_audio['bitrate'])) {
						$info_audio['bitrate'] = $getid3->info['ac3']['bitrate'];
					}
				}
				break;


			case 'AVI ':
				$info_video['bitrate_mode'] = 'vbr'; // maybe not, but probably
				$info_video['dataformat']   = 'avi';
				$getid3->info['mime_type']  = 'video/avi';

				if (isset($info_riff[$riff_sub_type]['movi']['offset'])) {
					$info_avdataoffset = $info_riff[$riff_sub_type]['movi']['offset'] + 8;
					$info_avdataend    = $info_avdataoffset + $info_riff[$riff_sub_type]['movi']['size'];
					if ($info_avdataend > $getid3->info['filesize']) {
						$getid3->warning('Probably truncated file - expecting '.$info_riff[$riff_sub_type]['movi']['size'].' bytes of data, only found '.($getid3->info['filesize'] - $info_avdataoffset).' (short by '.($info_riff[$riff_sub_type]['movi']['size'] - ($getid3->info['filesize'] - $info_avdataoffset)).' bytes)');
						$info_avdataend = $getid3->info['filesize'];
					}
				}

				if (isset($info_riff['AVI ']['hdrl']['avih'][$stream_index]['data'])) {
					$avihData = $info_riff['AVI ']['hdrl']['avih'][$stream_index]['data'];

					$info_riff_raw['avih'] = array ();
					$info_riff_raw_avih = &$info_riff_raw['avih'];

					getid3_lib::ReadSequence($this->endian_function, $info_riff_raw_avih, $avihData, 0,
						array (
							'dwMicroSecPerFrame'    => 4, // frame display rate (or 0L)
							'dwMaxBytesPerSec'      => 4, // max. transfer rate
							'dwPaddingGranularity'  => 4, // pad to multiples of this size; normally 2K.
							'dwFlags'               => 4, // the ever-present flags
							'dwTotalFrames'         => 4, // # frames in file
							'dwInitialFrames'       => 4,
							'dwStreams'             => 4,
							'dwSuggestedBufferSize' => 4,
							'dwWidth'               => 4,
							'dwHeight'              => 4,
							'dwScale'               => 4,
							'dwRate'                => 4,
							'dwStart'               => 4,
							'dwLength'              => 4
						)
					);

					$info_riff_raw_avih['flags']['hasindex']     = (bool)($info_riff_raw_avih['dwFlags'] & 0x00000010);
					$info_riff_raw_avih['flags']['mustuseindex'] = (bool)($info_riff_raw_avih['dwFlags'] & 0x00000020);
					$info_riff_raw_avih['flags']['interleaved']  = (bool)($info_riff_raw_avih['dwFlags'] & 0x00000100);
					$info_riff_raw_avih['flags']['trustcktype']  = (bool)($info_riff_raw_avih['dwFlags'] & 0x00000800);
					$info_riff_raw_avih['flags']['capturedfile'] = (bool)($info_riff_raw_avih['dwFlags'] & 0x00010000);
					$info_riff_raw_avih['flags']['copyrighted']  = (bool)($info_riff_raw_avih['dwFlags'] & 0x00020010);

					$info_riff_video[$stream_index] = array ();
					$info_riff_video_current = &$info_riff_video[$stream_index];

					if ($info_riff_raw_avih['dwWidth'] > 0) {
						$info_riff_video_current['frame_width'] = $info_riff_raw_avih['dwWidth'];
						$info_video['resolution_x']             = $info_riff_video_current['frame_width'];
					}

					if ($info_riff_raw_avih['dwHeight'] > 0) {
						$info_riff_video_current['frame_height'] = $info_riff_raw_avih['dwHeight'];
						$info_video['resolution_y']              = $info_riff_video_current['frame_height'];
					}

					if ($info_riff_raw_avih['dwTotalFrames'] > 0) {
						$info_riff_video_current['total_frames'] = $info_riff_raw_avih['dwTotalFrames'];
						$info_video['total_frames']              = $info_riff_video_current['total_frames'];
					}

					$info_riff_video_current['frame_rate'] = round(1000000 / $info_riff_raw_avih['dwMicroSecPerFrame'], 3);
					$info_video['frame_rate'] = $info_riff_video_current['frame_rate'];
				}

				if (isset($info_riff['AVI ']['hdrl']['strl']['strh'][0]['data'])) {
					if (is_array($info_riff['AVI ']['hdrl']['strl']['strh'])) {
						for ($i = 0; $i < count($info_riff['AVI ']['hdrl']['strl']['strh']); $i++) {
							if (isset($info_riff['AVI ']['hdrl']['strl']['strh'][$i]['data'])) {
								$strh_data = $info_riff['AVI ']['hdrl']['strl']['strh'][$i]['data'];
								$strh_fcc_type = substr($strh_data,  0, 4);

								if (isset($info_riff['AVI ']['hdrl']['strl']['strf'][$i]['data'])) {
									$strf_data = $info_riff['AVI ']['hdrl']['strl']['strf'][$i]['data'];

									// shortcut
									$info_riff_raw_strf_strh_fcc_type_stream_index = &$info_riff_raw['strf'][$strh_fcc_type][$stream_index];

									switch ($strh_fcc_type) {
										case 'auds':
											$info_audio['bitrate_mode'] = 'cbr';
											$info_audio_dataformat      = 'wav';
											if (isset($info_riff_audio) && is_array($info_riff_audio)) {
												$stream_index = count($info_riff_audio);
											}

											$info_riff_audio[$stream_index] = getid3_riff::RIFFparseWAVEFORMATex($strf_data);
											$info_audio['wformattag'] = $info_riff_audio[$stream_index]['raw']['wFormatTag'];

											// shortcut
											$info_audio['streams'][$stream_index] = $info_riff_audio[$stream_index];
											$info_audio_streams_currentstream = &$info_audio['streams'][$stream_index];

											if (@$info_audio_streams_currentstream['bits_per_sample'] === 0) {
												unset($info_audio_streams_currentstream['bits_per_sample']);
											}
											$info_audio_streams_currentstream['wformattag'] = $info_audio_streams_currentstream['raw']['wFormatTag'];
											unset($info_audio_streams_currentstream['raw']);

											// shortcut
											$info_riff_raw['strf'][$strh_fcc_type][$stream_index] = $info_riff_audio[$stream_index]['raw'];

											unset($info_riff_audio[$stream_index]['raw']);
											$info_audio = getid3_riff::array_merge_noclobber($info_audio, $info_riff_audio[$stream_index]);

											$info_audio['lossless'] = false;
											switch ($info_riff_raw_strf_strh_fcc_type_stream_index['wFormatTag']) {

												case 0x0001:  // PCM
													$info_audio_dataformat  = 'wav';
													$info_audio['lossless'] = true;
													break;

												case 0x0050: // MPEG Layer 2 or Layer 1
													$info_audio_dataformat = 'mp2'; // Assume Layer-2
													break;

												case 0x0055: // MPEG Layer 3
													$info_audio_dataformat = 'mp3';
													break;

												case 0x00FF: // AAC
													$info_audio_dataformat = 'aac';
													break;

												case 0x0161: // Windows Media v7 / v8 / v9
												case 0x0162: // Windows Media Professional v9
												case 0x0163: // Windows Media Lossess v9
													$info_audio_dataformat = 'wma';
													break;

												case 0x2000: // AC-3
													$info_audio_dataformat = 'ac3';
													break;

												case 0x2001: // DTS
													$info_audio_dataformat = 'dts';
													break;

												default:
													$info_audio_dataformat = 'wav';
													break;
											}
											$info_audio_streams_currentstream['dataformat']   = $info_audio_dataformat;
											$info_audio_streams_currentstream['lossless']     = $info_audio['lossless'];
											$info_audio_streams_currentstream['bitrate_mode'] = $info_audio['bitrate_mode'];
											break;


										case 'iavs':
										case 'vids':
											// shortcut
											$info_riff_raw['strh'][$i]  = array ();
											$info_riff_raw_strh_current = &$info_riff_raw['strh'][$i];

											getid3_lib::ReadSequence($this->endian_function, $info_riff_raw_strh_current, $strh_data, 0,
												array (
													'fccType'               => -4, // same as $strh_fcc_type;
													'fccHandler'            => -4,
													'dwFlags'               => 4, // Contains AVITF_* flags
													'wPriority'             => 2,
													'wLanguage'             => 2,
													'dwInitialFrames'       => 4,
													'dwScale'               => 4,
													'dwRate'                => 4,
													'dwStart'               => 4,
													'dwLength'              => 4,
													'dwSuggestedBufferSize' => 4,
													'dwQuality'             => 4,
													'dwSampleSize'          => 4,
													'rcFrame'               => 4
												)
											);

											$info_riff_video_current['codec'] = getid3_riff::RIFFfourccLookup($info_riff_raw_strh_current['fccHandler']);
											$info_video['fourcc']             = $info_riff_raw_strh_current['fccHandler'];

											if (!$info_riff_video_current['codec'] && isset($info_riff_raw_strf_strh_fcc_type_stream_index['fourcc']) && getid3_riff::RIFFfourccLookup($info_riff_raw_strf_strh_fcc_type_stream_index['fourcc'])) {
												$info_riff_video_current['codec'] = getid3_riff::RIFFfourccLookup($info_riff_raw_strf_strh_fcc_type_stream_index['fourcc']);
												$info_video['fourcc']             = $info_riff_raw_strf_strh_fcc_type_stream_index['fourcc'];
											}

											$info_video['codec']              = $info_riff_video_current['codec'];
											$info_video['pixel_aspect_ratio'] = (float)1;

											switch ($info_riff_raw_strh_current['fccHandler']) {

												case 'HFYU': // Huffman Lossless Codec
												case 'IRAW': // Intel YUV Uncompressed
												case 'YUY2': // Uncompressed YUV 4:2:2
													$info_video['lossless'] = true;
													break;

												default:
													$info_video['lossless'] = false;
													break;
											}

											switch ($strh_fcc_type) {

												case 'vids':
													getid3_lib::ReadSequence($this->endian_function, $info_riff_raw_strf_strh_fcc_type_stream_index, $strf_data, 0,
														array (
															'biSize'          => 4, // number of bytes required by the BITMAPINFOHEADER structure
															'biWidth'         => 4, // width of the bitmap in pixels
															'biHeight'        => 4, // height of the bitmap in pixels. If biHeight is positive, the bitmap is a 'bottom-up' DIB and its origin is the lower left corner. If biHeight is negative, the bitmap is a 'top-down' DIB and its origin is the upper left corner
															'biPlanes'        => 2, // number of color planes on the target device. In most cases this value must be set to 1
															'biBitCount'      => 2, // Specifies the number of bits per pixels
															'fourcc'          => -4, //
															'biSizeImage'     => 4, // size of the bitmap data section of the image (the actual pixel data, excluding BITMAPINFOHEADER and RGBQUAD structures)
															'biXPelsPerMeter' => 4, // horizontal resolution, in pixels per metre, of the target device
															'biYPelsPerMeter' => 4, // vertical resolution, in pixels per metre, of the target device
															'biClrUsed'       => 4, // actual number of color indices in the color table used by the bitmap. If this value is zero, the bitmap uses the maximum number of colors corresponding to the value of the biBitCount member for the compression mode specified by biCompression
															'biClrImportant'  => 4 // number of color indices that are considered important for displaying the bitmap. If this value is zero, all colors are important
														)
													);

													$info_video['bits_per_sample'] = $info_riff_raw_strf_strh_fcc_type_stream_index['biBitCount'];

													if ($info_riff_video_current['codec'] == 'DV') {
														$info_riff_video_current['dv_type'] = 2;
													}
													break;

												case 'iavs':
													$info_riff_video_current['dv_type'] = 1;
													break;
											}
											break;

										default:
											$getid3->warning('Unhandled fccType for stream ('.$i.'): "'.$strh_fcc_type.'"');
											break;

									}
								}
							}

							if (isset($info_riff_raw_strf_strh_fcc_type_stream_index['fourcc']) && getid3_riff::RIFFfourccLookup($info_riff_raw_strf_strh_fcc_type_stream_index['fourcc'])) {

								$info_riff_video_current['codec'] = getid3_riff::RIFFfourccLookup($info_riff_raw_strf_strh_fcc_type_stream_index['fourcc']);
								$info_video['codec']              = $info_riff_video_current['codec'];
								$info_video['fourcc']             = $info_riff_raw_strf_strh_fcc_type_stream_index['fourcc'];

								switch ($info_riff_raw_strf_strh_fcc_type_stream_index['fourcc']) {

									case 'HFYU': // Huffman Lossless Codec
									case 'IRAW': // Intel YUV Uncompressed
									case 'YUY2': // Uncompressed YUV 4:2:2
										$info_video['lossless']        = true;
										$info_video['bits_per_sample'] = 24;
										break;

									default:
										$info_video['lossless']        = false;
										$info_video['bits_per_sample'] = 24;
										break;
								}

							}
						}
					}
				}
				break;


			case 'CDDA':
				$info_audio['bitrate_mode'] = 'cbr';
				$info_audio_dataformat      = 'cda';
				$info_audio['lossless']     = true;
				unset($getid3->info['mime_type']);

				$info_avdataoffset = 44;

				if (isset($info_riff['CDDA']['fmt '][0]['data'])) {

					$info_riff_cdda_fmt_0 = &$info_riff['CDDA']['fmt '][0];

					getid3_lib::ReadSequence($this->endian_function, $info_riff_cdda_fmt_0, $info_riff_cdda_fmt_0['data'], 0,
						array (
							'unknown1'           => 2,
							'track_num'          => 2,
							'disc_id'            => 4,
							'start_offset_frame' => 4,
							'playtime_frames'    => 4,
							'unknown6'           => 4,
							'unknown7'           => 4
						)
					);

					$info_riff_cdda_fmt_0['start_offset_seconds'] = (float)$info_riff_cdda_fmt_0['start_offset_frame'] / 75;
					$info_riff_cdda_fmt_0['playtime_seconds']     = (float)$info_riff_cdda_fmt_0['playtime_frames'] / 75;
					$getid3->info['comments']['track']            = $info_riff_cdda_fmt_0['track_num'];
					$getid3->info['playtime_seconds']             = $info_riff_cdda_fmt_0['playtime_seconds'];

					// hardcoded data for CD-audio
					$info_audio['sample_rate']     = 44100;
					$info_audio['channels']        = 2;
					$info_audio['bits_per_sample'] = 16;
					$info_audio['bitrate']         = $info_audio['sample_rate'] * $info_audio['channels'] * $info_audio['bits_per_sample'];
					$info_audio['bitrate_mode']    = 'cbr';
				}
				break;


			case 'AIFF':
			case 'AIFC':
				$info_audio['bitrate_mode'] = 'cbr';
				$info_audio_dataformat      = 'aiff';
				$info_audio['lossless']     = true;
				$getid3->info['mime_type']      = 'audio/x-aiff';

				if (isset($info_riff[$riff_sub_type]['SSND'][0]['offset'])) {
					$info_avdataoffset = $info_riff[$riff_sub_type]['SSND'][0]['offset'] + 8;
					$info_avdataend    = $info_avdataoffset + $info_riff[$riff_sub_type]['SSND'][0]['size'];
					if ($info_avdataend > $getid3->info['filesize']) {
						if (($info_avdataend == ($getid3->info['filesize'] + 1)) && (($getid3->info['filesize'] % 2) == 1)) {
							// structures rounded to 2-byte boundary, but dumb encoders
							// forget to pad end of file to make this actually work
						} else {
							$getid3->warning('Probable truncated AIFF file: expecting '.$info_riff[$riff_sub_type]['SSND'][0]['size'].' bytes of audio data, only '.($getid3->info['filesize'] - $info_avdataoffset).' bytes found');
						}
						$info_avdataend = $getid3->info['filesize'];
					}
				}

				if (isset($info_riff[$riff_sub_type]['COMM'][0]['data'])) {

					// shortcut
					$info_riff_RIFFsubtype_COMM_0_data = &$info_riff[$riff_sub_type]['COMM'][0]['data'];

					$info_riff_audio['channels']         = getid3_lib::BigEndianSyncSafe2Int(substr($info_riff_RIFFsubtype_COMM_0_data,  0,  2));
					$info_riff_audio['total_samples']    = getid3_lib::BigEndian2Int(        substr($info_riff_RIFFsubtype_COMM_0_data,  2,  4));
					$info_riff_audio['bits_per_sample']  = getid3_lib::BigEndianSyncSafe2Int(substr($info_riff_RIFFsubtype_COMM_0_data,  6,  2));
					$info_riff_audio['sample_rate']      = (int)getid3_riff::BigEndian2Float(substr($info_riff_RIFFsubtype_COMM_0_data,  8, 10));

					if ($info_riff[$riff_sub_type]['COMM'][0]['size'] > 18) {
						$info_riff_audio['codec_fourcc'] =                           substr($info_riff_RIFFsubtype_COMM_0_data, 18,  4);
						$codec_name_size                 = getid3_lib::BigEndian2Int(substr($info_riff_RIFFsubtype_COMM_0_data, 22,  1));
						$info_riff_audio['codec_name']   =                           substr($info_riff_RIFFsubtype_COMM_0_data, 23,  $codec_name_size);

						switch ($info_riff_audio['codec_name']) {

							case 'NONE':
								$info_audio['codec']    = 'Pulse Code Modulation (PCM)';
								$info_audio['lossless'] = true;
								break;

							case '':
								switch ($info_riff_audio['codec_fourcc']) {

									// http://developer.apple.com/qa/snd/snd07.html
									case 'sowt':
										$info_riff_audio['codec_name'] = 'Two\'s Compliment Little-Endian PCM';
										$info_audio['lossless'] = true;
										break;

									case 'twos':
										$info_riff_audio['codec_name'] = 'Two\'s Compliment Big-Endian PCM';
										$info_audio['lossless'] = true;
										break;

									default:
										break;
								}
								break;

							default:
								$info_audio['codec']    = $info_riff_audio['codec_name'];
								$info_audio['lossless'] = false;
								break;
						}
					}

					$info_audio['channels'] = $info_riff_audio['channels'];

					if ($info_riff_audio['bits_per_sample'] > 0) {
						$info_audio['bits_per_sample'] = $info_riff_audio['bits_per_sample'];
					}

					$info_audio['sample_rate']        = $info_riff_audio['sample_rate'];
					$getid3->info['playtime_seconds'] = $info_riff_audio['total_samples'] / $info_audio['sample_rate'];
				}

				if (isset($info_riff[$riff_sub_type]['COMT'])) {

					$comment_count = getid3_lib::BigEndian2Int(substr($info_riff[$riff_sub_type]['COMT'][0]['data'], 0, 2));
					$offset = 2;

					for ($i = 0; $i < $comment_count; $i++) {

						$getid3->info['comments_raw'][$i]['timestamp'] = getid3_lib::BigEndian2Int(        substr($info_riff[$riff_sub_type]['COMT'][0]['data'], $offset, 4));
						$offset += 4;

						$getid3->info['comments_raw'][$i]['marker_id'] = getid3_lib::BigEndianSyncSafe2Int(substr($info_riff[$riff_sub_type]['COMT'][0]['data'], $offset, 2));
						$offset += 2;

						$comment_length                                = getid3_lib::BigEndian2Int(        substr($info_riff[$riff_sub_type]['COMT'][0]['data'], $offset, 2));
						$offset += 2;

						$getid3->info['comments_raw'][$i]['comment']   =                                   substr($info_riff[$riff_sub_type]['COMT'][0]['data'], $offset, $comment_length);
						$offset += $comment_length;

						$getid3->info['comments_raw'][$i]['timestamp_unix'] = getid3_riff::DateMac2Unix($getid3->info['comments_raw'][$i]['timestamp']);
						$info_riff['comments']['comment'][] = $getid3->info['comments_raw'][$i]['comment'];
					}
				}

				foreach (array ('NAME'=>'title', 'author'=>'artist', '(c) '=>'copyright', 'ANNO'=>'comment') as $key => $value) {
					if (isset($info_riff[$riff_sub_type][$key][0]['data'])) {
						$info_riff['comments'][$value][] = $info_riff[$riff_sub_type][$key][0]['data'];
					}
				}
				break;


			case '8SVX':
				$info_audio['bitrate_mode']    = 'cbr';
				$info_audio_dataformat         = '8svx';
				$info_audio['bits_per_sample'] = 8;
				$info_audio['channels']        = 1; // overridden below, if need be
				$getid3->info['mime_type']     = 'audio/x-aiff';

				if (isset($info_riff[$riff_sub_type]['BODY'][0]['offset'])) {
					$info_avdataoffset = $info_riff[$riff_sub_type]['BODY'][0]['offset'] + 8;
					$info_avdataend    = $info_avdataoffset + $info_riff[$riff_sub_type]['BODY'][0]['size'];
					if ($info_avdataend > $getid3->info['filesize']) {
						$getid3->warning('Probable truncated AIFF file: expecting '.$info_riff[$riff_sub_type]['BODY'][0]['size'].' bytes of audio data, only '.($getid3->info['filesize'] - $info_avdataoffset).' bytes found');
					}
				}

				if (isset($info_riff[$riff_sub_type]['VHDR'][0]['offset'])) {
					// shortcut
					$info_riff_riff_sub_type_vhdr_0 = &$info_riff[$riff_sub_type]['VHDR'][0];

					getid3_lib::ReadSequence('BigEndian2Int', $info_riff_riff_sub_type_vhdr_0, $info_riff_riff_sub_type_vhdr_0['data'], 0,
						array (
							'oneShotHiSamples'  => 4,
							'repeatHiSamples'   => 4,
							'samplesPerHiCycle' => 4,
							'samplesPerSec'     => 2,
							'ctOctave'          => 1,
							'sCompression'      => 1,
							'Volume'            => -4
						)
					);

					$info_riff_riff_sub_type_vhdr_0['Volume'] = getid3_riff::FixedPoint16_16($info_riff_riff_sub_type_vhdr_0['Volume']);

					$info_audio['sample_rate'] = $info_riff_riff_sub_type_vhdr_0['samplesPerSec'];

					switch ($info_riff_riff_sub_type_vhdr_0['sCompression']) {
						case 0:
							$info_audio['codec']    = 'Pulse Code Modulation (PCM)';
							$info_audio['lossless'] = true;
							$actual_bits_per_sample = 8;
							break;

						case 1:
							$info_audio['codec']    = 'Fibonacci-delta encoding';
							$info_audio['lossless'] = false;
							$actual_bits_per_sample = 4;
							break;

						default:
							$getid3->warning('Unexpected sCompression value in 8SVX.VHDR chunk - expecting 0 or 1, found "'.sCompression.'"');
							break;
					}
				}

				if (isset($info_riff[$riff_sub_type]['CHAN'][0]['data'])) {
					$ChannelsIndex = getid3_lib::BigEndian2Int(substr($info_riff[$riff_sub_type]['CHAN'][0]['data'], 0, 4));
					switch ($ChannelsIndex) {
						case 6: // Stereo
							$info_audio['channels'] = 2;
							break;

						case 2: // Left channel only
						case 4: // Right channel only
							$info_audio['channels'] = 1;
							break;

						default:
							$getid3->warning('Unexpected value in 8SVX.CHAN chunk - expecting 2 or 4 or 6, found "'.$ChannelsIndex.'"');
							break;
					}

				}

				foreach (array ('NAME'=>'title', 'author'=>'artist', '(c) '=>'copyright', 'ANNO'=>'comment') as $key => $value) {
					if (isset($info_riff[$riff_sub_type][$key][0]['data'])) {
						$info_riff['comments'][$value][] = $info_riff[$riff_sub_type][$key][0]['data'];
					}
				}

				$info_audio['bitrate'] = $info_audio['sample_rate'] * $actual_bits_per_sample * $info_audio['channels'];
				if (!empty($info_audio['bitrate'])) {
					$getid3->info['playtime_seconds'] = ($info_avdataend - $info_avdataoffset) / ($info_audio['bitrate'] / 8);
				}
				break;


			case 'CDXA':

				$getid3->info['mime_type'] = 'video/mpeg';
				if (!empty($info_riff['CDXA']['data'][0]['size'])) {
					$GETID3_ERRORARRAY = &$getid3->info['warning'];

					if (!$getid3->include_module_optional('audio-video.mpeg')) {
						$getid3->warning('MPEG skipped because mpeg module is missing.');
					}

					else {

						// Clone getid3 - messing with offsets - better safe than sorry
						$clone = clone $getid3;

						// Analyse
						$mpeg = new getid3_mpeg($clone);
						$mpeg->Analyze();

						// Import from clone and destroy
						$getid3->info['audio']   = $clone->info['audio'];
						$getid3->info['video']   = $clone->info['video'];
						$getid3->info['mpeg']    = $clone->info['mpeg'];
						$getid3->info['warning'] = $clone->info['warning'];

						unset($clone);
					}
				}

				break;


			default:
				throw new getid3_exception('Unknown RIFF type: expecting one of (WAVE|RMP3|AVI |CDDA|AIFF|AIFC|8SVX|CDXA), found "'.$riff_sub_type.'" instead');
		}


		if (@$info_riff_raw['fmt ']['wFormatTag'] == 1) {

			// http://www.mega-nerd.com/erikd/Blog/Windiots/dts.html
			$this->fseek($getid3->info['avdataoffset'], SEEK_SET);
			$bytes4 = $this->fread(4);

			// DTSWAV
			if (preg_match('/^\xFF\x1F\x00\xE8/s', $bytes4)) {
				$info_audio_dataformat = 'dts';
			}

			// DTS, but this probably shouldn't happen
			elseif (preg_match('/^\x7F\xFF\x80\x01/s', $bytes4)) {
				$info_audio_dataformat = 'dts';
			}
		}

		if (@is_array($info_riff_wave['DISP'])) {
			$info_riff['comments']['title'][] = trim(substr($info_riff_wave['DISP'][count($info_riff_wave['DISP']) - 1]['data'], 4));
		}

		if (@is_array($info_riff_wave['INFO'])) {
			getid3_riff::RIFFCommentsParse($info_riff_wave['INFO'], $info_riff['comments']);
		}

		if (isset($info_riff_wave['INFO']) && is_array($info_riff_wave['INFO'])) {

			foreach (array ('IARL' => 'archivallocation', 'IART' => 'artist', 'ICDS' => 'costumedesigner', 'ICMS' => 'commissionedby', 'ICMT' => 'comment', 'ICNT' => 'country', 'ICOP' => 'copyright', 'ICRD' => 'creationdate', 'IDIM' => 'dimensions', 'IDIT' => 'digitizationdate', 'IDPI' => 'resolution', 'IDST' => 'distributor', 'IEDT' => 'editor', 'IENG' => 'engineers', 'IFRM' => 'accountofparts', 'IGNR' => 'genre', 'IKEY' => 'keywords', 'ILGT' => 'lightness', 'ILNG' => 'language', 'IMED' => 'orignalmedium', 'IMUS' => 'composer', 'INAM' => 'title', 'IPDS' => 'productiondesigner', 'IPLT' => 'palette', 'IPRD' => 'product', 'IPRO' => 'producer', 'IPRT' => 'part', 'IRTD' => 'rating', 'ISBJ' => 'subject', 'ISFT' => 'software', 'ISGN' => 'secondarygenre', 'ISHP' => 'sharpness', 'ISRC' => 'sourcesupplier', 'ISRF' => 'digitizationsource', 'ISTD' => 'productionstudio', 'ISTR' => 'starring', 'ITCH' => 'encoded_by', 'IWEB' => 'url', 'IWRI' => 'writer') as $key => $value) {
				if (isset($info_riff_wave['INFO'][$key])) {
					foreach ($info_riff_wave['INFO'][$key] as $comment_id => $comment_data) {
						if (trim($comment_data['data']) != '') {
							$info_riff['comments'][$value][] = trim($comment_data['data']);
						}
					}
				}
			}
		}

		if (empty($info_audio['encoder']) && !empty($getid3->info['mpeg']['audio']['LAME']['short_version'])) {
			$info_audio['encoder'] = $getid3->info['mpeg']['audio']['LAME']['short_version'];
		}

		if (!isset($getid3->info['playtime_seconds'])) {
			$getid3->info['playtime_seconds'] = 0;
		}

		if (isset($info_riff_raw['avih']['dwTotalFrames']) && isset($info_riff_raw['avih']['dwMicroSecPerFrame'])) {
			$getid3->info['playtime_seconds'] = $info_riff_raw['avih']['dwTotalFrames'] * ($info_riff_raw['avih']['dwMicroSecPerFrame'] / 1000000);
		}

		if ($getid3->info['playtime_seconds'] > 0) {
			if (isset($info_riff_audio) && isset($info_riff_video)) {

				if (!isset($getid3->info['bitrate'])) {
					$getid3->info['bitrate'] = ((($info_avdataend - $info_avdataoffset) / $getid3->info['playtime_seconds']) * 8);
				}

			} elseif (isset($info_riff_audio) && !isset($info_riff_video)) {

				if (!isset($info_audio['bitrate'])) {
					$info_audio['bitrate'] = ((($info_avdataend - $info_avdataoffset) / $getid3->info['playtime_seconds']) * 8);
				}

			} elseif (!isset($info_riff_audio) && isset($info_riff_video)) {

				if (!isset($info_video['bitrate'])) {
					$info_video['bitrate'] = ((($info_avdataend - $info_avdataoffset) / $getid3->info['playtime_seconds']) * 8);
				}

			}
		}


		if (isset($info_riff_video) && isset($info_audio['bitrate']) && ($info_audio['bitrate'] > 0) && ($getid3->info['playtime_seconds'] > 0)) {

			$getid3->info['bitrate'] = ((($info_avdataend - $info_avdataoffset) / $getid3->info['playtime_seconds']) * 8);
			$info_audio['bitrate'] = 0;
			$info_video['bitrate'] = $getid3->info['bitrate'];
			foreach ($info_riff_audio as $channelnumber => $audioinfoarray) {
				$info_video['bitrate'] -= $audioinfoarray['bitrate'];
				$info_audio['bitrate'] += $audioinfoarray['bitrate'];
			}
			if ($info_video['bitrate'] <= 0) {
				unset($info_video['bitrate']);
			}
			if ($info_audio['bitrate'] <= 0) {
				unset($info_audio['bitrate']);
			}
		}

		if (isset($getid3->info['mpeg']['audio'])) {
			$info_audio_dataformat      = 'mp'.$getid3->info['mpeg']['audio']['layer'];
			$info_audio['sample_rate']  = $getid3->info['mpeg']['audio']['sample_rate'];
			$info_audio['channels']     = $getid3->info['mpeg']['audio']['channels'];
			$info_audio['bitrate']      = $getid3->info['mpeg']['audio']['bitrate'];
			$info_audio['bitrate_mode'] = strtolower($getid3->info['mpeg']['audio']['bitrate_mode']);

			if (!empty($getid3->info['mpeg']['audio']['codec'])) {
				$info_audio['codec'] = $getid3->info['mpeg']['audio']['codec'].' '.$info_audio['codec'];
			}

			if (!empty($info_audio['streams'])) {
				foreach ($info_audio['streams'] as $streamnumber => $streamdata) {
					if ($streamdata['dataformat'] == $info_audio_dataformat) {
						$info_audio['streams'][$streamnumber]['sample_rate']  = $info_audio['sample_rate'];
						$info_audio['streams'][$streamnumber]['channels']     = $info_audio['channels'];
						$info_audio['streams'][$streamnumber]['bitrate']      = $info_audio['bitrate'];
						$info_audio['streams'][$streamnumber]['bitrate_mode'] = $info_audio['bitrate_mode'];
						$info_audio['streams'][$streamnumber]['codec']        = $info_audio['codec'];
					}
				}
			}
			$info_audio['encoder_options'] = getid3_mp3::GuessEncoderOptions($getid3->info);
		}


		if (!empty($info_riff_raw['fmt ']['wBitsPerSample']) && ($info_riff_raw['fmt ']['wBitsPerSample'] > 0)) {
			switch ($info_audio_dataformat) {
				case 'ac3':
					// ignore bits_per_sample
					break;

				default:
					$info_audio['bits_per_sample'] = $info_riff_raw['fmt ']['wBitsPerSample'];
					break;
			}
		}


		if (empty($info_riff_raw)) {
			unset($info_riff['raw']);
		}
		if (empty($info_riff_audio)) {
			unset($info_riff['audio']);
		}
		if (empty($info_riff_video)) {
			unset($info_riff['video']);
		}
		if (empty($info_audio_dataformat)) {
			unset($info_audio['dataformat']);
		}
		if (empty($getid3->info['audio'])) {
			unset($getid3->info['audio']);
		}
		if (empty($info_video)) {
			unset($getid3->info['video']);
		}

		return true;
	}



	public function ParseRIFF($start_offset, $max_offset) {

		$getid3 = $this->getid3;

		$info   = &$getid3->info;

		$endian_function = $this->endian_function;

		$max_offset = min($max_offset, $info['avdataend']);

		$riff_chunk = false;

		$this->fseek($start_offset, SEEK_SET);

		while ($this->ftell() < $max_offset) {

			$chunk_name = $this->fread(4);

			if (strlen($chunk_name) < 4) {
				throw new getid3_exception('Expecting chunk name at offset '.($this->ftell() - 4).' but found nothing. Aborting RIFF parsing.');
			}

			$chunk_size = getid3_lib::$endian_function($this->fread(4));

			if ($chunk_size == 0) {
				continue;
				throw new getid3_exception('Chunk size at offset '.($this->ftell() - 4).' is zero. Aborting RIFF parsing.');
			}

			if (($chunk_size % 2) != 0) {
				// all structures are packed on word boundaries
				$chunk_size++;
			}

			switch ($chunk_name) {

				case 'LIST':
					$list_name = $this->fread(4);

					switch ($list_name) {

						case 'movi':
						case 'rec ':
							$riff_chunk[$list_name]['offset'] = $this->ftell() - 4;
							$riff_chunk[$list_name]['size']   = $chunk_size;

							static $parsed_audio_stream = false;

							if (!$parsed_audio_stream) {
								$where_we_were = $this->ftell();
								$audio_chunk_header = $this->fread(12);
								$audio_chunk_stream_num  =                             substr($audio_chunk_header, 0, 2);
								$audio_chunk_stream_type =                             substr($audio_chunk_header, 2, 2);
								$audio_chunk_size       = getid3_lib::LittleEndian2Int(substr($audio_chunk_header, 4, 4));

								if ($audio_chunk_stream_type == 'wb') {
									$first_four_bytes = substr($audio_chunk_header, 8, 4);


									//// MPEG

									if (preg_match('/^\xFF[\xE2-\xE7\xF2-\xF7\xFA-\xFF][\x00-\xEB]/s', $first_four_bytes)) {

										if (!$getid3->include_module_optional('audio.mp3')) {
											$getid3->warning('MP3 skipped because mp3 module is missing.');
										}

										elseif (getid3_mp3::MPEGaudioHeaderBytesValid($first_four_bytes)) {

											// Clone getid3 - messing with offsets - better safe than sorry
											$clone = clone $getid3;
											$clone->info['avdataoffset'] = $this->ftell() - 4;
											$clone->info['avdataend']    = $this->ftell() + $audio_chunk_size;

											$mp3 = new getid3_mp3($clone);
											$mp3->AnalyzeMPEGaudioInfo();

											// Import from clone and destroy
											if (isset($clone->info['mpeg']['audio'])) {

												$info['mpeg']['audio'] = $clone->info['mpeg']['audio'];

												$info['audio']['dataformat']   = 'mp'.$info['mpeg']['audio']['layer'];
												$info['audio']['sample_rate']  = $info['mpeg']['audio']['sample_rate'];
												$info['audio']['channels']     = $info['mpeg']['audio']['channels'];
												$info['audio']['bitrate']      = $info['mpeg']['audio']['bitrate'];
												$info['audio']['bitrate_mode'] = strtolower($info['mpeg']['audio']['bitrate_mode']);
												$info['bitrate']               = $info['audio']['bitrate'];

												$getid3->warning($clone->warnings());
												unset($clone);
											}
										}
									}

									//// AC3-WAVE

									elseif (preg_match('/^\x0B\x77/s', $first_four_bytes)) {

										if (!$getid3->include_module_optional('audio.ac3')) {
											$getid3->warning('AC3 skipped because ac3 module is missing.');
										}

										else {

											// Clone getid3 - messing with offsets - better safe than sorry
											$clone = clone $getid3;
											$clone->info['avdataoffset'] = $this->ftell() - 4;
											$clone->info['avdataend']    = $this->ftell() + $audio_chunk_size;

											// Analyze clone by fp
											$ac3 = new getid3_ac3($clone);
											$ac3->Analyze();

											// Import from clone and destroy
											$info['audio'] = $clone->info['audio'];
											$info['ac3']   = $clone->info['ac3'];
											$getid3->warning($clone->warnings());
											unset($clone);
										}
									}
								}

								$parsed_audio_stream = true;
								$this->fseek($where_we_were, SEEK_SET);

							}
							$this->fseek($chunk_size - 4, SEEK_CUR);
							break;

						default:
							if (!isset($riff_chunk[$list_name])) {
								$riff_chunk[$list_name] = array ();
							}
							$list_chunk_parent    = $list_name;
							$list_chunk_max_offset = $this->ftell() - 4 + $chunk_size;
							if ($parsed_chunk = $this->ParseRIFF($this->ftell(), $this->ftell() + $chunk_size - 4)) {
								$riff_chunk[$list_name] = array_merge_recursive($riff_chunk[$list_name], $parsed_chunk);
							}
							break;
					}
					break;


				default:

					$this_index = 0;
					if (isset($riff_chunk[$chunk_name]) && is_array($riff_chunk[$chunk_name])) {
						$this_index = count($riff_chunk[$chunk_name]);
					}
					$riff_chunk[$chunk_name][$this_index]['offset'] = $this->ftell() - 8;
					$riff_chunk[$chunk_name][$this_index]['size']   = $chunk_size;
					switch ($chunk_name) {
						case 'data':
							$info['avdataoffset'] = $this->ftell();
							$info['avdataend']    = $info['avdataoffset'] + $chunk_size;

							$riff_data_chunk_contents_test = $this->fread(36);


							//// This is probably MP3 data

							if ((strlen($riff_data_chunk_contents_test) > 0) && preg_match('/^\xFF[\xE2-\xE7\xF2-\xF7\xFA-\xFF][\x00-\xEB]/s', substr($riff_data_chunk_contents_test, 0, 4))) {

								try {

									if (!$getid3->include_module_optional('audio.mp3')) {
										$getid3->warning('MP3 skipped because mp3 module is missing.');
									}


									// Clone getid3 - messing with offsets - better safe than sorry
									$clone = clone $getid3;

									if (getid3_mp3::MPEGaudioHeaderBytesValid(substr($riff_data_chunk_contents_test, 0, 4))) {

										$mp3 = new getid3_mp3($clone);
										$mp3->AnalyzeMPEGaudioInfo();

										// Import from clone and destroy
										if (isset($clone->info['mpeg']['audio'])) {

											$info['mpeg']['audio'] = $clone->info['mpeg']['audio'];

											$info['audio']['sample_rate']  = $info['mpeg']['audio']['sample_rate'];
											$info['audio']['channels']     = $info['mpeg']['audio']['channels'];
											$info['audio']['bitrate']      = $info['mpeg']['audio']['bitrate'];
											$info['audio']['bitrate_mode'] = strtolower($info['mpeg']['audio']['bitrate_mode']);
											$info['bitrate']               = $info['audio']['bitrate'];

											$getid3->warning($clone->warnings());
											unset($clone);
										}
									}
								}
								catch (Exception $e) {
									// do nothing - not MP3 data
								}
							}


							//// This is probably AC-3 data

							elseif ((strlen($riff_data_chunk_contents_test) > 0) && (substr($riff_data_chunk_contents_test, 0, 2) == "\x0B\x77")) {

								if (!$getid3->include_module_optional('audio.ac3')) {
									$getid3->warning('AC3 skipped because ac3 module is missing.');
								}

								else {

									// Clone getid3 - messing with offsets - better safe than sorry
									$clone = clone $getid3;
									$clone->info['avdataoffset'] = $riff_chunk[$chunk_name][$this_index]['offset'];
									$clone->info['avdataend']    = $clone->info['avdataoffset'] + $riff_chunk[$chunk_name][$this_index]['size'];

									// Analyze clone by fp
									$ac3 = new getid3_ac3($clone);
									$ac3->Analyze();

									// Import from clone and destroy
									$info['audio'] = $clone->info['audio'];
									$info['ac3']   = $clone->info['ac3'];
									$getid3->warning($clone->warnings());
									unset($clone);
								}
							}


							// Dolby Digital WAV
							// AC-3 content, but not encoded in same format as normal AC-3 file
							// For one thing, byte order is swapped

							elseif ((strlen($riff_data_chunk_contents_test) > 0) && (substr($riff_data_chunk_contents_test, 8, 2) == "\x77\x0B")) {

								if (!$getid3->include_module_optional('audio.ac3')) {
									$getid3->warning('AC3 skipped because ac3 module is missing.');
								}

								else {

									// Extract ac3 data to string
									$ac3_data = '';
									for ($i = 0; $i < 28; $i += 2) {
										// swap byte order
										$ac3_data .= substr($riff_data_chunk_contents_test, 8 + $i + 1, 1);
										$ac3_data .= substr($riff_data_chunk_contents_test, 8 + $i + 0, 1);
									}

									// Clone getid3 - messing with offsets - better safe than sorry
									$clone = clone $getid3;
									$clone->info['avdataoffset'] = 0;
									$clone->info['avdataend']    = 20;

									// Analyse clone by string
									$ac3 = new getid3_ac3($clone);
									$ac3->AnalyzeString($ac3_data);

									// Import from clone and destroy
									$info['audio'] = $clone->info['audio'];
									$info['ac3']   = $clone->info['ac3'];
									$getid3->warning($clone->warnings());
									unset($clone);
								}
							}


							if ((strlen($riff_data_chunk_contents_test) > 0) && (substr($riff_data_chunk_contents_test, 0, 4) == 'wvpk')) {

								// This is WavPack data
								$info['wavpack']['offset'] = $riff_chunk[$chunk_name][$this_index]['offset'];
								$info['wavpack']['size']   = getid3_lib::LittleEndian2Int(substr($riff_data_chunk_contents_test, 4, 4));
								$this->RIFFparseWavPackHeader(substr($riff_data_chunk_contents_test, 8, 28));

							} else {

								// This is some other kind of data (quite possibly just PCM)
								// do nothing special, just skip it

							}
							$this->fseek($riff_chunk[$chunk_name][$this_index]['offset'] + 8 + $chunk_size, SEEK_SET);
							break;

						case 'bext':
						case 'cart':
						case 'fmt ':
						case 'MEXT':
						case 'DISP':
							// always read data in
							$riff_chunk[$chunk_name][$this_index]['data'] = $this->fread($chunk_size);
							break;

						default:
							if (!empty($list_chunk_parent) && (($riff_chunk[$chunk_name][$this_index]['offset'] + $riff_chunk[$chunk_name][$this_index]['size']) <= $list_chunk_max_offset)) {
								$riff_chunk[$list_chunk_parent][$chunk_name][$this_index]['offset'] = $riff_chunk[$chunk_name][$this_index]['offset'];
								$riff_chunk[$list_chunk_parent][$chunk_name][$this_index]['size']   = $riff_chunk[$chunk_name][$this_index]['size'];
								unset($riff_chunk[$chunk_name][$this_index]['offset']);
								unset($riff_chunk[$chunk_name][$this_index]['size']);
								if (isset($riff_chunk[$chunk_name][$this_index]) && empty($riff_chunk[$chunk_name][$this_index])) {
									unset($riff_chunk[$chunk_name][$this_index]);
								}
								if (isset($riff_chunk[$chunk_name]) && empty($riff_chunk[$chunk_name])) {
									unset($riff_chunk[$chunk_name]);
								}
								$riff_chunk[$list_chunk_parent][$chunk_name][$this_index]['data'] = $this->fread($chunk_size);
							} elseif ($chunk_size < 2048) {
								// only read data in if smaller than 2kB
								$riff_chunk[$chunk_name][$this_index]['data'] = $this->fread($chunk_size);
							} else {
								$this->fseek($chunk_size, SEEK_CUR);
							}
							break;
					}
					break;

			}

		}

		return $riff_chunk;
	}



	private function RIFFparseWavPackHeader($wavpack3_chunk_data) {

		// typedef struct {
		//     char ckID [4];
		//     long ckSize;
		//     short version;
		//     short bits;                // added for version 2.00
		//     short flags, shift;        // added for version 3.00
		//     long total_samples, crc, crc2;
		//     char extension [4], extra_bc, extras [3];
		// } WavpackHeader;

		$this->getid3->info['wavpack'] = array ();
		$info_wavpack = &$this->getid3->info['wavpack'];

		$info_wavpack['version'] = getid3_lib::LittleEndian2Int(substr($wavpack3_chunk_data,  0, 2));

		if ($info_wavpack['version'] >= 2) {
			$info_wavpack['bits'] = getid3_lib::LittleEndian2Int(substr($wavpack3_chunk_data,  2, 2));
		}

		if ($info_wavpack['version'] >= 3) {

			getid3_lib::ReadSequence('LittleEndian2Int', $info_wavpack, $wavpack3_chunk_data,  4,
				array (
					'flags_raw'     => 2,
					'shift'         => 2,
					'total_samples' => 4,
					'crc1'          => 4,
					'crc2'          => 4,
					'extension'     => -4,
					'extra_bc'      => 1
				)
			);

			for ($i = 0; $i < 3; $i++) {
				$info_wavpack['extras'][] = getid3_lib::LittleEndian2Int($wavpack3_chunk_data{25 + $i});
			}

			$info_wavpack['flags'] = array ();
			$info_wavpack_flags = &$info_wavpack['flags'];

			$info_wavpack_flags['mono']                 = (bool)($info_wavpack['flags_raw'] & 0x000001);
			$info_wavpack_flags['fast_mode']            = (bool)($info_wavpack['flags_raw'] & 0x000002);
			$info_wavpack_flags['raw_mode']             = (bool)($info_wavpack['flags_raw'] & 0x000004);
			$info_wavpack_flags['calc_noise']           = (bool)($info_wavpack['flags_raw'] & 0x000008);
			$info_wavpack_flags['high_quality']         = (bool)($info_wavpack['flags_raw'] & 0x000010);
			$info_wavpack_flags['3_byte_samples']       = (bool)($info_wavpack['flags_raw'] & 0x000020);
			$info_wavpack_flags['over_20_bits']         = (bool)($info_wavpack['flags_raw'] & 0x000040);
			$info_wavpack_flags['use_wvc']              = (bool)($info_wavpack['flags_raw'] & 0x000080);
			$info_wavpack_flags['noiseshaping']         = (bool)($info_wavpack['flags_raw'] & 0x000100);
			$info_wavpack_flags['very_fast_mode']       = (bool)($info_wavpack['flags_raw'] & 0x000200);
			$info_wavpack_flags['new_high_quality']     = (bool)($info_wavpack['flags_raw'] & 0x000400);
			$info_wavpack_flags['cancel_extreme']       = (bool)($info_wavpack['flags_raw'] & 0x000800);
			$info_wavpack_flags['cross_decorrelation']  = (bool)($info_wavpack['flags_raw'] & 0x001000);
			$info_wavpack_flags['new_decorrelation']    = (bool)($info_wavpack['flags_raw'] & 0x002000);
			$info_wavpack_flags['joint_stereo']         = (bool)($info_wavpack['flags_raw'] & 0x004000);
			$info_wavpack_flags['extra_decorrelation']  = (bool)($info_wavpack['flags_raw'] & 0x008000);
			$info_wavpack_flags['override_noiseshape']  = (bool)($info_wavpack['flags_raw'] & 0x010000);
			$info_wavpack_flags['override_jointstereo'] = (bool)($info_wavpack['flags_raw'] & 0x020000);
			$info_wavpack_flags['copy_source_filetime'] = (bool)($info_wavpack['flags_raw'] & 0x040000);
			$info_wavpack_flags['create_exe']           = (bool)($info_wavpack['flags_raw'] & 0x080000);
		}

		return true;
	}



	public function AnalyzeString(&$string) {

		// Rewrite header_size in header
		$new_header_size = getid3_lib::LittleEndian2String(strlen($string), 4);
		for ($i = 0; $i < 4; $i++) {
			$string{$i + 4} = $new_header_size{$i};
		}

		return parent::AnalyzeString($string);
	}



	public static function RIFFparseWAVEFORMATex($wave_format_ex_data) {

		$wave_format_ex['raw'] = array ();
		$wave_format_ex_raw    = &$wave_format_ex['raw'];

		getid3_lib::ReadSequence('LittleEndian2Int', $wave_format_ex_raw, $wave_format_ex_data, 0,
			array (
				'wFormatTag'      => 2,
				'nChannels'       => 2,
				'nSamplesPerSec'  => 4,
				'nAvgBytesPerSec' => 4,
				'nBlockAlign'     => 2,
				'wBitsPerSample'  => 2
			)
		);

		if (strlen($wave_format_ex_data) > 16) {
			$wave_format_ex_raw['cbSize'] = getid3_lib::LittleEndian2Int(substr($wave_format_ex_data, 16, 2));
		}

		$wave_format_ex['codec']           = getid3_riff::RIFFwFormatTagLookup($wave_format_ex_raw['wFormatTag']);
		$wave_format_ex['channels']        = $wave_format_ex_raw['nChannels'];
		$wave_format_ex['sample_rate']     = $wave_format_ex_raw['nSamplesPerSec'];
		$wave_format_ex['bitrate']         = $wave_format_ex_raw['nAvgBytesPerSec'] * 8;
		if (@$wave_format_ex_raw['wBitsPerSample']) {
			$wave_format_ex['bits_per_sample'] = $wave_format_ex_raw['wBitsPerSample'];
		}

		return $wave_format_ex;
	}



	public static function RIFFwaveSNDMtagLookup($tagshortname) {
		static $lookup = array(
			'©kwd' => 'keywords',
			'©BPM' => 'bpm',
			'©trt' => 'tracktitle',
			'©des' => 'description',
			'©gen' => 'category',
			'©fin' => 'featuredinstrument',
			'©LID' => 'longid',
			'©bex' => 'bwdescription',
			'©pub' => 'publisher',
			'©cdt' => 'cdtitle',
			'©alb' => 'library',
			'©com' => 'composer',
		);
		return @$lookup[$tagshortname];
	}


	public static function RIFFwFormatTagLookup($w_format_tag) {

		static $lookup = array (
			0x0000 => 'Microsoft Unknown Wave Format',
			0x0001 => 'Pulse Code Modulation (PCM)',
			0x0002 => 'Microsoft ADPCM',
			0x0003 => 'IEEE Float',
			0x0004 => 'Compaq Computer VSELP',
			0x0005 => 'IBM CVSD',
			0x0006 => 'Microsoft A-Law',
			0x0007 => 'Microsoft mu-Law',
			0x0008 => 'Microsoft DTS',
			0x0010 => 'OKI ADPCM',
			0x0011 => 'Intel DVI/IMA ADPCM',
			0x0012 => 'Videologic MediaSpace ADPCM',
			0x0013 => 'Sierra Semiconductor ADPCM',
			0x0014 => 'Antex Electronics G.723 ADPCM',
			0x0015 => 'DSP Solutions DigiSTD',
			0x0016 => 'DSP Solutions DigiFIX',
			0x0017 => 'Dialogic OKI ADPCM',
			0x0018 => 'MediaVision ADPCM',
			0x0019 => 'Hewlett-Packard CU',
			0x0020 => 'Yamaha ADPCM',
			0x0021 => 'Speech Compression Sonarc',
			0x0022 => 'DSP Group TrueSpeech',
			0x0023 => 'Echo Speech EchoSC1',
			0x0024 => 'Audiofile AF36',
			0x0025 => 'Audio Processing Technology APTX',
			0x0026 => 'AudioFile AF10',
			0x0027 => 'Prosody 1612',
			0x0028 => 'LRC',
			0x0030 => 'Dolby AC2',
			0x0031 => 'Microsoft GSM 6.10',
			0x0032 => 'MSNAudio',
			0x0033 => 'Antex Electronics ADPCME',
			0x0034 => 'Control Resources VQLPC',
			0x0035 => 'DSP Solutions DigiREAL',
			0x0036 => 'DSP Solutions DigiADPCM',
			0x0037 => 'Control Resources CR10',
			0x0038 => 'Natural MicroSystems VBXADPCM',
			0x0039 => 'Crystal Semiconductor IMA ADPCM',
			0x003A => 'EchoSC3',
			0x003B => 'Rockwell ADPCM',
			0x003C => 'Rockwell Digit LK',
			0x003D => 'Xebec',
			0x0040 => 'Antex Electronics G.721 ADPCM',
			0x0041 => 'G.728 CELP',
			0x0042 => 'MSG723',
			0x0050 => 'MPEG Layer-2 or Layer-1',
			0x0052 => 'RT24',
			0x0053 => 'PAC',
			0x0055 => 'MPEG Layer-3',
			0x0059 => 'Lucent G.723',
			0x0060 => 'Cirrus',
			0x0061 => 'ESPCM',
			0x0062 => 'Voxware',
			0x0063 => 'Canopus Atrac',
			0x0064 => 'G.726 ADPCM',
			0x0065 => 'G.722 ADPCM',
			0x0066 => 'DSAT',
			0x0067 => 'DSAT Display',
			0x0069 => 'Voxware Byte Aligned',
			0x0070 => 'Voxware AC8',
			0x0071 => 'Voxware AC10',
			0x0072 => 'Voxware AC16',
			0x0073 => 'Voxware AC20',
			0x0074 => 'Voxware MetaVoice',
			0x0075 => 'Voxware MetaSound',
			0x0076 => 'Voxware RT29HW',
			0x0077 => 'Voxware VR12',
			0x0078 => 'Voxware VR18',
			0x0079 => 'Voxware TQ40',
			0x0080 => 'Softsound',
			0x0081 => 'Voxware TQ60',
			0x0082 => 'MSRT24',
			0x0083 => 'G.729A',
			0x0084 => 'MVI MV12',
			0x0085 => 'DF G.726',
			0x0086 => 'DF GSM610',
			0x0088 => 'ISIAudio',
			0x0089 => 'Onlive',
			0x0091 => 'SBC24',
			0x0092 => 'Dolby AC3 SPDIF',
			0x0093 => 'MediaSonic G.723',
			0x0094 => 'Aculab PLC    Prosody 8kbps',
			0x0097 => 'ZyXEL ADPCM',
			0x0098 => 'Philips LPCBB',
			0x0099 => 'Packed',
			0x00FF => 'AAC',
			0x0100 => 'Rhetorex ADPCM',
			0x0101 => 'IBM mu-law',
			0x0102 => 'IBM A-law',
			0x0103 => 'IBM AVC Adaptive Differential Pulse Code Modulation (ADPCM)',
			0x0111 => 'Vivo G.723',
			0x0112 => 'Vivo Siren',
			0x0123 => 'Digital G.723',
			0x0125 => 'Sanyo LD ADPCM',
			0x0130 => 'Sipro Lab Telecom ACELP NET',
			0x0131 => 'Sipro Lab Telecom ACELP 4800',
			0x0132 => 'Sipro Lab Telecom ACELP 8V3',
			0x0133 => 'Sipro Lab Telecom G.729',
			0x0134 => 'Sipro Lab Telecom G.729A',
			0x0135 => 'Sipro Lab Telecom Kelvin',
			0x0140 => 'Windows Media Video V8',
			0x0150 => 'Qualcomm PureVoice',
			0x0151 => 'Qualcomm HalfRate',
			0x0155 => 'Ring Zero Systems TUB GSM',
			0x0160 => 'Microsoft Audio 1',
			0x0161 => 'Windows Media Audio V7 / V8 / V9',
			0x0162 => 'Windows Media Audio Professional V9',
			0x0163 => 'Windows Media Audio Lossless V9',
			0x0200 => 'Creative Labs ADPCM',
			0x0202 => 'Creative Labs Fastspeech8',
			0x0203 => 'Creative Labs Fastspeech10',
			0x0210 => 'UHER Informatic GmbH ADPCM',
			0x0220 => 'Quarterdeck',
			0x0230 => 'I-link Worldwide VC',
			0x0240 => 'Aureal RAW Sport',
			0x0250 => 'Interactive Products HSX',
			0x0251 => 'Interactive Products RPELP',
			0x0260 => 'Consistent Software CS2',
			0x0270 => 'Sony SCX',
			0x0300 => 'Fujitsu FM Towns Snd',
			0x0400 => 'BTV Digital',
			0x0401 => 'Intel Music Coder',
			0x0450 => 'QDesign Music',
			0x0680 => 'VME VMPCM',
			0x0681 => 'AT&T Labs TPC',
			0x08AE => 'ClearJump LiteWave',
			0x1000 => 'Olivetti GSM',
			0x1001 => 'Olivetti ADPCM',
			0x1002 => 'Olivetti CELP',
			0x1003 => 'Olivetti SBC',
			0x1004 => 'Olivetti OPR',
			0x1100 => 'Lernout & Hauspie Codec (0x1100)',
			0x1101 => 'Lernout & Hauspie CELP Codec (0x1101)',
			0x1102 => 'Lernout & Hauspie SBC Codec (0x1102)',
			0x1103 => 'Lernout & Hauspie SBC Codec (0x1103)',
			0x1104 => 'Lernout & Hauspie SBC Codec (0x1104)',
			0x1400 => 'Norris',
			0x1401 => 'AT&T ISIAudio',
			0x1500 => 'Soundspace Music Compression',
			0x181C => 'VoxWare RT24 Speech',
			0x1FC4 => 'NCT Soft ALF2CD (www.nctsoft.com)',
			0x2000 => 'Dolby AC3',
			0x2001 => 'Dolby DTS',
			0x2002 => 'WAVE_FORMAT_14_4',
			0x2003 => 'WAVE_FORMAT_28_8',
			0x2004 => 'WAVE_FORMAT_COOK',
			0x2005 => 'WAVE_FORMAT_DNET',
			0x674F => 'Ogg Vorbis 1',
			0x6750 => 'Ogg Vorbis 2',
			0x6751 => 'Ogg Vorbis 3',
			0x676F => 'Ogg Vorbis 1+',
			0x6770 => 'Ogg Vorbis 2+',
			0x6771 => 'Ogg Vorbis 3+',
			0x7A21 => 'GSM-AMR (CBR, no SID)',
			0x7A22 => 'GSM-AMR (VBR, including SID)',
			0xFFFE => 'WAVE_FORMAT_EXTENSIBLE',
			0xFFFF => 'WAVE_FORMAT_DEVELOPMENT'
		);

		return @$lookup[$w_format_tag];
	}



	public static function RIFFfourccLookup($four_cc) {

		static $lookup = array (
			'swot' => 'http://developer.apple.com/qa/snd/snd07.html',
			'____' => 'No Codec (____)',
			'_BIT' => 'BI_BITFIELDS (Raw RGB)',
			'_JPG' => 'JPEG compressed',
			'_PNG' => 'PNG compressed W3C/ISO/IEC (RFC-2083)',
			'_RAW' => 'Full Frames (Uncompressed)',
			'_RGB' => 'Raw RGB Bitmap',
			'_RL4' => 'RLE 4bpp RGB',
			'_RL8' => 'RLE 8bpp RGB',
			'3IV1' => '3ivx MPEG-4 v1',
			'3IV2' => '3ivx MPEG-4 v2',
			'3IVX' => '3ivx MPEG-4',
			'AASC' => 'Autodesk Animator',
			'ABYR' => 'Kensington ?ABYR?',
			'AEMI' => 'Array Microsystems VideoONE MPEG1-I Capture',
			'AFLC' => 'Autodesk Animator FLC',
			'AFLI' => 'Autodesk Animator FLI',
			'AMPG' => 'Array Microsystems VideoONE MPEG',
			'ANIM' => 'Intel RDX (ANIM)',
			'AP41' => 'AngelPotion Definitive',
			'ASV1' => 'Asus Video v1',
			'ASV2' => 'Asus Video v2',
			'ASVX' => 'Asus Video 2.0 (audio)',
			'AUR2' => 'AuraVision Aura 2 Codec - YUV 4:2:2',
			'AURA' => 'AuraVision Aura 1 Codec - YUV 4:1:1',
			'AVDJ' => 'Independent JPEG Group\'s codec (AVDJ)',
			'AVRN' => 'Independent JPEG Group\'s codec (AVRN)',
			'AYUV' => '4:4:4 YUV (AYUV)',
			'AZPR' => 'Quicktime Apple Video (AZPR)',
			'BGR ' => 'Raw RGB32',
			'BLZ0' => 'FFmpeg MPEG-4',
			'BTVC' => 'Conexant Composite Video',
			'BINK' => 'RAD Game Tools Bink Video',
			'BT20' => 'Conexant Prosumer Video',
			'BTCV' => 'Conexant Composite Video Codec',
			'BW10' => 'Data Translation Broadway MPEG Capture',
			'CC12' => 'Intel YUV12',
			'CDVC' => 'Canopus DV',
			'CFCC' => 'Digital Processing Systems DPS Perception',
			'CGDI' => 'Microsoft Office 97 Camcorder Video',
			'CHAM' => 'Winnov Caviara Champagne',
			'CJPG' => 'Creative WebCam JPEG',
			'CLJR' => 'Cirrus Logic YUV 4:1:1',
			'CMYK' => 'Common Data Format in Printing (Colorgraph)',
			'CPLA' => 'Weitek 4:2:0 YUV Planar',
			'CRAM' => 'Microsoft Video 1 (CRAM)',
			'cvid' => 'Radius Cinepak',
			'CVID' => 'Radius Cinepak',
			'CWLT' => 'Microsoft Color WLT DIB',
			'CYUV' => 'Creative Labs YUV',
			'CYUY' => 'ATI YUV',
			'D261' => 'H.261',
			'D263' => 'H.263',
			'DIB ' => 'Device Independent Bitmap',
			'DIV1' => 'FFmpeg OpenDivX',
			'DIV2' => 'Microsoft MPEG-4 v1/v2',
			'DIV3' => 'DivX ;-) MPEG-4 v3.x Low-Motion',
			'DIV4' => 'DivX ;-) MPEG-4 v3.x Fast-Motion',
			'DIV5' => 'DivX MPEG-4 v5.x',
			'DIV6' => 'DivX ;-) (MS MPEG-4 v3.x)',
			'DIVX' => 'DivX MPEG-4 v4 (OpenDivX / Project Mayo)',
			'divx' => 'DivX MPEG-4',
			'DMB1' => 'Matrox Rainbow Runner hardware MJPEG',
			'DMB2' => 'Paradigm MJPEG',
			'DSVD' => '?DSVD?',
			'DUCK' => 'Duck TrueMotion 1.0',
			'DPS0' => 'DPS/Leitch Reality Motion JPEG',
			'DPSC' => 'DPS/Leitch PAR Motion JPEG',
			'DV25' => 'Matrox DVCPRO codec',
			'DV50' => 'Matrox DVCPRO50 codec',
			'DVC ' => 'IEC 61834 and SMPTE 314M (DVC/DV Video)',
			'DVCP' => 'IEC 61834 and SMPTE 314M (DVC/DV Video)',
			'DVHD' => 'IEC Standard DV 1125 lines @ 30fps / 1250 lines @ 25fps',
			'DVMA' => 'Darim Vision DVMPEG (dummy for MPEG compressor) (www.darvision.com)',
			'DVSL' => 'IEC Standard DV compressed in SD (SDL)',
			'DVAN' => '?DVAN?',
			'DVE2' => 'InSoft DVE-2 Videoconferencing',
			'dvsd' => 'IEC 61834 and SMPTE 314M DVC/DV Video',
			'DVSD' => 'IEC 61834 and SMPTE 314M DVC/DV Video',
			'DVX1' => 'Lucent DVX1000SP Video Decoder',
			'DVX2' => 'Lucent DVX2000S Video Decoder',
			'DVX3' => 'Lucent DVX3000S Video Decoder',
			'DX50' => 'DivX v5',
			'DXT1' => 'Microsoft DirectX Compressed Texture (DXT1)',
			'DXT2' => 'Microsoft DirectX Compressed Texture (DXT2)',
			'DXT3' => 'Microsoft DirectX Compressed Texture (DXT3)',
			'DXT4' => 'Microsoft DirectX Compressed Texture (DXT4)',
			'DXT5' => 'Microsoft DirectX Compressed Texture (DXT5)',
			'DXTC' => 'Microsoft DirectX Compressed Texture (DXTC)',
			'DXTn' => 'Microsoft DirectX Compressed Texture (DXTn)',
			'EM2V' => 'Etymonix MPEG-2 I-frame (www.etymonix.com)',
			'EKQ0' => 'Elsa ?EKQ0?',
			'ELK0' => 'Elsa ?ELK0?',
			'ESCP' => 'Eidos Escape',
			'ETV1' => 'eTreppid Video ETV1',
			'ETV2' => 'eTreppid Video ETV2',
			'ETVC' => 'eTreppid Video ETVC',
			'FLIC' => 'Autodesk FLI/FLC Animation',
			'FRWT' => 'Darim Vision Forward Motion JPEG (www.darvision.com)',
			'FRWU' => 'Darim Vision Forward Uncompressed (www.darvision.com)',
			'FLJP' => 'D-Vision Field Encoded Motion JPEG',
			'FRWA' => 'SoftLab-Nsk Forward Motion JPEG w/ alpha channel',
			'FRWD' => 'SoftLab-Nsk Forward Motion JPEG',
			'FVF1' => 'Iterated Systems Fractal Video Frame',
			'GLZW' => 'Motion LZW (gabest@freemail.hu)',
			'GPEG' => 'Motion JPEG (gabest@freemail.hu)',
			'GWLT' => 'Microsoft Greyscale WLT DIB',
			'H260' => 'Intel ITU H.260 Videoconferencing',
			'H261' => 'Intel ITU H.261 Videoconferencing',
			'H262' => 'Intel ITU H.262 Videoconferencing',
			'H263' => 'Intel ITU H.263 Videoconferencing',
			'H264' => 'Intel ITU H.264 Videoconferencing',
			'H265' => 'Intel ITU H.265 Videoconferencing',
			'H266' => 'Intel ITU H.266 Videoconferencing',
			'H267' => 'Intel ITU H.267 Videoconferencing',
			'H268' => 'Intel ITU H.268 Videoconferencing',
			'H269' => 'Intel ITU H.269 Videoconferencing',
			'HFYU' => 'Huffman Lossless Codec',
			'HMCR' => 'Rendition Motion Compensation Format (HMCR)',
			'HMRR' => 'Rendition Motion Compensation Format (HMRR)',
			'I263' => 'FFmpeg I263 decoder',
			'IF09' => 'Indeo YVU9 ("YVU9 with additional delta-frame info after the U plane")',
			'IUYV' => 'Interlaced version of UYVY (www.leadtools.com)',
			'IY41' => 'Interlaced version of Y41P (www.leadtools.com)',
			'IYU1' => '12 bit format used in mode 2 of the IEEE 1394 Digital Camera 1.04 spec    IEEE standard',
			'IYU2' => '24 bit format used in mode 2 of the IEEE 1394 Digital Camera 1.04 spec    IEEE standard',
			'IYUV' => 'Planar YUV format (8-bpp Y plane, followed by 8-bpp 2×2 U and V planes)',
			'i263' => 'Intel ITU H.263 Videoconferencing (i263)',
			'I420' => 'Intel Indeo 4',
			'IAN ' => 'Intel Indeo 4 (RDX)',
			'ICLB' => 'InSoft CellB Videoconferencing',
			'IGOR' => 'Power DVD',
			'IJPG' => 'Intergraph JPEG',
			'ILVC' => 'Intel Layered Video',
			'ILVR' => 'ITU-T H.263+',
			'IPDV' => 'I-O Data Device Giga AVI DV Codec',
			'IR21' => 'Intel Indeo 2.1',
			'IRAW' => 'Intel YUV Uncompressed',
			'IV30' => 'Intel Indeo 3.0',
			'IV31' => 'Intel Indeo 3.1',
			'IV32' => 'Ligos Indeo 3.2',
			'IV33' => 'Ligos Indeo 3.3',
			'IV34' => 'Ligos Indeo 3.4',
			'IV35' => 'Ligos Indeo 3.5',
			'IV36' => 'Ligos Indeo 3.6',
			'IV37' => 'Ligos Indeo 3.7',
			'IV38' => 'Ligos Indeo 3.8',
			'IV39' => 'Ligos Indeo 3.9',
			'IV40' => 'Ligos Indeo Interactive 4.0',
			'IV41' => 'Ligos Indeo Interactive 4.1',
			'IV42' => 'Ligos Indeo Interactive 4.2',
			'IV43' => 'Ligos Indeo Interactive 4.3',
			'IV44' => 'Ligos Indeo Interactive 4.4',
			'IV45' => 'Ligos Indeo Interactive 4.5',
			'IV46' => 'Ligos Indeo Interactive 4.6',
			'IV47' => 'Ligos Indeo Interactive 4.7',
			'IV48' => 'Ligos Indeo Interactive 4.8',
			'IV49' => 'Ligos Indeo Interactive 4.9',
			'IV50' => 'Ligos Indeo Interactive 5.0',
			'JBYR' => 'Kensington ?JBYR?',
			'JPEG' => 'Still Image JPEG DIB',
			'JPGL' => 'Pegasus Lossless Motion JPEG',
			'KMVC' => 'Team17 Software Karl Morton\'s Video Codec',
			'LSVM' => 'Vianet Lighting Strike Vmail (Streaming) (www.vianet.com)',
			'LEAD' => 'LEAD Video Codec',
			'Ljpg' => 'LEAD MJPEG Codec',
			'MDVD' => 'Alex MicroDVD Video (hacked MS MPEG-4) (www.tiasoft.de)',
			'MJPA' => 'Morgan Motion JPEG (MJPA) (www.morgan-multimedia.com)',
			'MJPB' => 'Morgan Motion JPEG (MJPB) (www.morgan-multimedia.com)',
			'MMES' => 'Matrox MPEG-2 I-frame',
			'MP2v' => 'Microsoft S-Mpeg 4 version 1 (MP2v)',
			'MP42' => 'Microsoft S-Mpeg 4 version 2 (MP42)',
			'MP43' => 'Microsoft S-Mpeg 4 version 3 (MP43)',
			'MP4S' => 'Microsoft S-Mpeg 4 version 3 (MP4S)',
			'MP4V' => 'FFmpeg MPEG-4',
			'MPG1' => 'FFmpeg MPEG 1/2',
			'MPG2' => 'FFmpeg MPEG 1/2',
			'MPG3' => 'FFmpeg DivX ;-) (MS MPEG-4 v3)',
			'MPG4' => 'Microsoft MPEG-4',
			'MPGI' => 'Sigma Designs MPEG',
			'MPNG' => 'PNG images decoder',
			'MSS1' => 'Microsoft Windows Screen Video',
			'MSZH' => 'LCL (Lossless Codec Library) (www.geocities.co.jp/Playtown-Denei/2837/LRC.htm)',
			'M261' => 'Microsoft H.261',
			'M263' => 'Microsoft H.263',
			'M4S2' => 'Microsoft Fully Compliant MPEG-4 v2 simple profile (M4S2)',
			'm4s2' => 'Microsoft Fully Compliant MPEG-4 v2 simple profile (m4s2)',
			'MC12' => 'ATI Motion Compensation Format (MC12)',
			'MCAM' => 'ATI Motion Compensation Format (MCAM)',
			'MJ2C' => 'Morgan Multimedia Motion JPEG2000',
			'mJPG' => 'IBM Motion JPEG w/ Huffman Tables',
			'MJPG' => 'Microsoft Motion JPEG DIB',
			'MP42' => 'Microsoft MPEG-4 (low-motion)',
			'MP43' => 'Microsoft MPEG-4 (fast-motion)',
			'MP4S' => 'Microsoft MPEG-4 (MP4S)',
			'mp4s' => 'Microsoft MPEG-4 (mp4s)',
			'MPEG' => 'Chromatic Research MPEG-1 Video I-Frame',
			'MPG4' => 'Microsoft MPEG-4 Video High Speed Compressor',
			'MPGI' => 'Sigma Designs MPEG',
			'MRCA' => 'FAST Multimedia Martin Regen Codec',
			'MRLE' => 'Microsoft Run Length Encoding',
			'MSVC' => 'Microsoft Video 1',
			'MTX1' => 'Matrox ?MTX1?',
			'MTX2' => 'Matrox ?MTX2?',
			'MTX3' => 'Matrox ?MTX3?',
			'MTX4' => 'Matrox ?MTX4?',
			'MTX5' => 'Matrox ?MTX5?',
			'MTX6' => 'Matrox ?MTX6?',
			'MTX7' => 'Matrox ?MTX7?',
			'MTX8' => 'Matrox ?MTX8?',
			'MTX9' => 'Matrox ?MTX9?',
			'MV12' => 'Motion Pixels Codec (old)',
			'MWV1' => 'Aware Motion Wavelets',
			'nAVI' => 'SMR Codec (hack of Microsoft MPEG-4) (IRC #shadowrealm)',
			'NT00' => 'NewTek LightWave HDTV YUV w/ Alpha (www.newtek.com)',
			'NUV1' => 'NuppelVideo',
			'NTN1' => 'Nogatech Video Compression 1',
			'NVS0' => 'nVidia GeForce Texture (NVS0)',
			'NVS1' => 'nVidia GeForce Texture (NVS1)',
			'NVS2' => 'nVidia GeForce Texture (NVS2)',
			'NVS3' => 'nVidia GeForce Texture (NVS3)',
			'NVS4' => 'nVidia GeForce Texture (NVS4)',
			'NVS5' => 'nVidia GeForce Texture (NVS5)',
			'NVT0' => 'nVidia GeForce Texture (NVT0)',
			'NVT1' => 'nVidia GeForce Texture (NVT1)',
			'NVT2' => 'nVidia GeForce Texture (NVT2)',
			'NVT3' => 'nVidia GeForce Texture (NVT3)',
			'NVT4' => 'nVidia GeForce Texture (NVT4)',
			'NVT5' => 'nVidia GeForce Texture (NVT5)',
			'PIXL' => 'MiroXL, Pinnacle PCTV',
			'PDVC' => 'I-O Data Device Digital Video Capture DV codec',
			'PGVV' => 'Radius Video Vision',
			'PHMO' => 'IBM Photomotion',
			'PIM1' => 'MPEG Realtime (Pinnacle Cards)',
			'PIM2' => 'Pegasus Imaging ?PIM2?',
			'PIMJ' => 'Pegasus Imaging Lossless JPEG',
			'PVEZ' => 'Horizons Technology PowerEZ',
			'PVMM' => 'PacketVideo Corporation MPEG-4',
			'PVW2' => 'Pegasus Imaging Wavelet Compression',
			'Q1.0' => 'Q-Team\'s QPEG 1.0 (www.q-team.de)',
			'Q1.1' => 'Q-Team\'s QPEG 1.1 (www.q-team.de)',
			'QPEG' => 'Q-Team QPEG 1.0',
			'qpeq' => 'Q-Team QPEG 1.1',
			'RGB ' => 'Raw BGR32',
			'RGBA' => 'Raw RGB w/ Alpha',
			'RMP4' => 'REALmagic MPEG-4 (unauthorized XVID copy) (www.sigmadesigns.com)',
			'ROQV' => 'Id RoQ File Video Decoder',
			'RPZA' => 'Quicktime Apple Video (RPZA)',
			'RUD0' => 'Rududu video codec (http://rududu.ifrance.com/rududu/)',
			'RV10' => 'RealVideo 1.0 (aka RealVideo 5.0)',
			'RV13' => 'RealVideo 1.0 (RV13)',
			'RV20' => 'RealVideo G2',
			'RV30' => 'RealVideo 8',
			'RV40' => 'RealVideo 9',
			'RGBT' => 'Raw RGB w/ Transparency',
			'RLE ' => 'Microsoft Run Length Encoder',
			'RLE4' => 'Run Length Encoded (4bpp, 16-color)',
			'RLE8' => 'Run Length Encoded (8bpp, 256-color)',
			'RT21' => 'Intel Indeo RealTime Video 2.1',
			'rv20' => 'RealVideo G2',
			'rv30' => 'RealVideo 8',
			'RVX ' => 'Intel RDX (RVX )',
			'SMC ' => 'Apple Graphics (SMC )',
			'SP54' => 'Logitech Sunplus Sp54 Codec for Mustek GSmart Mini 2',
			'SPIG' => 'Radius Spigot',
			'SVQ3' => 'Sorenson Video 3 (Apple Quicktime 5)',
			's422' => 'Tekram VideoCap C210 YUV 4:2:2',
			'SDCC' => 'Sun Communication Digital Camera Codec',
			'SFMC' => 'CrystalNet Surface Fitting Method',
			'SMSC' => 'Radius SMSC',
			'SMSD' => 'Radius SMSD',
			'smsv' => 'WorldConnect Wavelet Video',
			'SPIG' => 'Radius Spigot',
			'SPLC' => 'Splash Studios ACM Audio Codec (www.splashstudios.net)',
			'SQZ2' => 'Microsoft VXTreme Video Codec V2',
			'STVA' => 'ST Microelectronics CMOS Imager Data (Bayer)',
			'STVB' => 'ST Microelectronics CMOS Imager Data (Nudged Bayer)',
			'STVC' => 'ST Microelectronics CMOS Imager Data (Bunched)',
			'STVX' => 'ST Microelectronics CMOS Imager Data (Extended CODEC Data Format)',
			'STVY' => 'ST Microelectronics CMOS Imager Data (Extended CODEC Data Format with Correction Data)',
			'SV10' => 'Sorenson Video R1',
			'SVQ1' => 'Sorenson Video',
			'T420' => 'Toshiba YUV 4:2:0',
			'TM2A' => 'Duck TrueMotion Archiver 2.0 (www.duck.com)',
			'TVJP' => 'Pinnacle/Truevision Targa 2000 board (TVJP)',
			'TVMJ' => 'Pinnacle/Truevision Targa 2000 board (TVMJ)',
			'TY0N' => 'Tecomac Low-Bit Rate Codec (www.tecomac.com)',
			'TY2C' => 'Trident Decompression Driver',
			'TLMS' => 'TeraLogic Motion Intraframe Codec (TLMS)',
			'TLST' => 'TeraLogic Motion Intraframe Codec (TLST)',
			'TM20' => 'Duck TrueMotion 2.0',
			'TM2X' => 'Duck TrueMotion 2X',
			'TMIC' => 'TeraLogic Motion Intraframe Codec (TMIC)',
			'TMOT' => 'Horizons Technology TrueMotion S',
			'tmot' => 'Horizons TrueMotion Video Compression',
			'TR20' => 'Duck TrueMotion RealTime 2.0',
			'TSCC' => 'TechSmith Screen Capture Codec',
			'TV10' => 'Tecomac Low-Bit Rate Codec',
			'TY2N' => 'Trident ?TY2N?',
			'U263' => 'UB Video H.263/H.263+/H.263++ Decoder',
			'UMP4' => 'UB Video MPEG 4 (www.ubvideo.com)',
			'UYNV' => 'Nvidia UYVY packed 4:2:2',
			'UYVP' => 'Evans & Sutherland YCbCr 4:2:2 extended precision',
			'UCOD' => 'eMajix.com ClearVideo',
			'ULTI' => 'IBM Ultimotion',
			'UYVY' => 'UYVY packed 4:2:2',
			'V261' => 'Lucent VX2000S',
			'VIFP' => 'VFAPI Reader Codec (www.yks.ne.jp/~hori/)',
			'VIV1' => 'FFmpeg H263+ decoder',
			'VIV2' => 'Vivo H.263',
			'VQC2' => 'Vector-quantised codec 2 (research) http://eprints.ecs.soton.ac.uk/archive/00001310/01/VTC97-js.pdf)',
			'VTLP' => 'Alaris VideoGramPiX',
			'VYU9' => 'ATI YUV (VYU9)',
			'VYUY' => 'ATI YUV (VYUY)',
			'V261' => 'Lucent VX2000S',
			'V422' => 'Vitec Multimedia 24-bit YUV 4:2:2 Format',
			'V655' => 'Vitec Multimedia 16-bit YUV 4:2:2 Format',
			'VCR1' => 'ATI Video Codec 1',
			'VCR2' => 'ATI Video Codec 2',
			'VCR3' => 'ATI VCR 3.0',
			'VCR4' => 'ATI VCR 4.0',
			'VCR5' => 'ATI VCR 5.0',
			'VCR6' => 'ATI VCR 6.0',
			'VCR7' => 'ATI VCR 7.0',
			'VCR8' => 'ATI VCR 8.0',
			'VCR9' => 'ATI VCR 9.0',
			'VDCT' => 'Vitec Multimedia Video Maker Pro DIB',
			'VDOM' => 'VDOnet VDOWave',
			'VDOW' => 'VDOnet VDOLive (H.263)',
			'VDTZ' => 'Darim Vison VideoTizer YUV',
			'VGPX' => 'Alaris VideoGramPiX',
			'VIDS' => 'Vitec Multimedia YUV 4:2:2 CCIR 601 for V422',
			'VIVO' => 'Vivo H.263 v2.00',
			'vivo' => 'Vivo H.263',
			'VIXL' => 'Miro/Pinnacle Video XL',
			'VLV1' => 'VideoLogic/PURE Digital Videologic Capture',
			'VP30' => 'On2 VP3.0',
			'VP31' => 'On2 VP3.1',
			'VX1K' => 'Lucent VX1000S Video Codec',
			'VX2K' => 'Lucent VX2000S Video Codec',
			'VXSP' => 'Lucent VX1000SP Video Codec',
			'WBVC' => 'Winbond W9960',
			'WHAM' => 'Microsoft Video 1 (WHAM)',
			'WINX' => 'Winnov Software Compression',
			'WJPG' => 'AverMedia Winbond JPEG',
			'WMV1' => 'Windows Media Video V7',
			'WMV2' => 'Windows Media Video V8',
			'WMV3' => 'Windows Media Video V9',
			'WNV1' => 'Winnov Hardware Compression',
			'XYZP' => 'Extended PAL format XYZ palette (www.riff.org)',
			'x263' => 'Xirlink H.263',
			'XLV0' => 'NetXL Video Decoder',
			'XMPG' => 'Xing MPEG (I-Frame only)',
			'XVID' => 'XviD MPEG-4 (www.xvid.org)',
			'XXAN' => '?XXAN?',
			'YU92' => 'Intel YUV (YU92)',
			'YUNV' => 'Nvidia Uncompressed YUV 4:2:2',
			'YUVP' => 'Extended PAL format YUV palette (www.riff.org)',
			'Y211' => 'YUV 2:1:1 Packed',
			'Y411' => 'YUV 4:1:1 Packed',
			'Y41B' => 'Weitek YUV 4:1:1 Planar',
			'Y41P' => 'Brooktree PC1 YUV 4:1:1 Packed',
			'Y41T' => 'Brooktree PC1 YUV 4:1:1 with transparency',
			'Y42B' => 'Weitek YUV 4:2:2 Planar',
			'Y42T' => 'Brooktree UYUV 4:2:2 with transparency',
			'Y422' => 'ADS Technologies Copy of UYVY used in Pyro WebCam firewire camera',
			'Y800' => 'Simple, single Y plane for monochrome images',
			'Y8  ' => 'Grayscale video',
			'YC12' => 'Intel YUV 12 codec',
			'YUV8' => 'Winnov Caviar YUV8',
			'YUV9' => 'Intel YUV9',
			'YUY2' => 'Uncompressed YUV 4:2:2',
			'YUYV' => 'Canopus YUV',
			'YV12' => 'YVU12 Planar',
			'YVU9' => 'Intel YVU9 Planar (8-bpp Y plane, followed by 8-bpp 4x4 U and V planes)',
			'YVYU' => 'YVYU 4:2:2 Packed',
			'ZLIB' => 'Lossless Codec Library zlib compression (www.geocities.co.jp/Playtown-Denei/2837/LRC.htm)',
			'ZPEG' => 'Metheus Video Zipper'
		);

		return @$lookup[$four_cc];
	}



	public static function RIFFcommentsParse(&$riff_info_aray, &$comments_target_array) {

		static $lookup = array(
			'IARL' => 'archivallocation',
			'IART' => 'artist',
			'ICDS' => 'costumedesigner',
			'ICMS' => 'commissionedby',
			'ICMT' => 'comment',
			'ICNT' => 'country',
			'ICOP' => 'copyright',
			'ICRD' => 'creationdate',
			'IDIM' => 'dimensions',
			'IDIT' => 'digitizationdate',
			'IDPI' => 'resolution',
			'IDST' => 'distributor',
			'IEDT' => 'editor',
			'IENG' => 'engineers',
			'IFRM' => 'accountofparts',
			'IGNR' => 'genre',
			'IKEY' => 'keywords',
			'ILGT' => 'lightness',
			'ILNG' => 'language',
			'IMED' => 'orignalmedium',
			'IMUS' => 'composer',
			'INAM' => 'title',
			'IPDS' => 'productiondesigner',
			'IPLT' => 'palette',
			'IPRD' => 'product',
			'IPRO' => 'producer',
			'IPRT' => 'part',
			'IRTD' => 'rating',
			'ISBJ' => 'subject',
			'ISFT' => 'software',
			'ISGN' => 'secondarygenre',
			'ISHP' => 'sharpness',
			'ISRC' => 'sourcesupplier',
			'ISRF' => 'digitizationsource',
			'ISTD' => 'productionstudio',
			'ISTR' => 'starring',
			'ITCH' => 'encoded_by',
			'IWEB' => 'url',
			'IWRI' => 'writer'
		);

		foreach ($lookup as $key => $value) {
			if (isset($riff_info_aray[$key])) {
				foreach ($riff_info_aray[$key] as $comment_id => $comment_data) {
					if (trim($comment_data['data']) != '') {
						@$comments_target_array[$value][] = trim($comment_data['data']);
					}
				}
			}
		}
		return true;
	}



	public static function array_merge_noclobber($array1, $array2) {
		if (!is_array($array1) || !is_array($array2)) {
			return false;
		}
		$new_array = $array1;
		foreach ($array2 as $key => $val) {
			if (is_array($val) && isset($new_array[$key]) && is_array($new_array[$key])) {
				$new_array[$key] = getid3_riff::array_merge_noclobber($new_array[$key], $val);
			} elseif (!isset($new_array[$key])) {
				$new_array[$key] = $val;
			}
		}
		return $new_array;
	}



	public static function DateMac2Unix($mac_date) {

		// Macintosh timestamp: seconds since 00:00h January 1, 1904
		// UNIX timestamp:      seconds since 00:00h January 1, 1970
		return (int)($mac_date - 2082844800);
	}



	public static function FixedPoint16_16($raw_data) {

		return getid3_lib::BigEndian2Int(substr($raw_data, 0, 2)) + (float)(getid3_lib::BigEndian2Int(substr($raw_data, 2, 2)) / 65536);  // pow(2, 16) = 65536
	}



   	function BigEndian2Float($byte_word) {

		// ANSI/IEEE Standard 754-1985, Standard for Binary Floating Point Arithmetic
		// http://www.psc.edu/general/software/packages/ieee/ieee.html
		// http://www.scri.fsu.edu/~jac/MAD3401/Backgrnd/ieee.html

		$bit_word = getid3_lib::BigEndian2Bin($byte_word);
		$sign_bit = $bit_word{0};

		switch (strlen($byte_word) * 8) {
			case 32:
				$exponent_bits = 8;
				$fraction_bits = 23;
				break;

			case 64:
				$exponent_bits = 11;
				$fraction_bits = 52;
				break;

			case 80:
				// 80-bit Apple SANE format
				// http://www.mactech.com/articles/mactech/Vol.06/06.01/SANENormalized/
				$exponent_string = substr($bit_word, 1, 15);
				$is_normalized   = intval($bit_word{16});
				$fraction_string = substr($bit_word, 17, 63);
				$exponent = pow(2, bindec($exponent_string) - 16383);
				$fraction = $is_normalized + bindec($fraction_string) / bindec('1'.str_repeat('0', strlen($fraction_string)));
				$float_value = $exponent * $fraction;
				if ($sign_bit == '1') {
					$float_value *= -1;
				}
				return $float_value;
				break;

			default:
				return false;
				break;
		}
		$exponent_string = substr($bit_word, 1, $exponent_bits);
		$fraction_string = substr($bit_word, $exponent_bits + 1, $fraction_bits);
		$exponent = bindec($exponent_string);
		$fraction = bindec($fraction_string);

		if (($exponent == (pow(2, $exponent_bits) - 1)) && ($fraction != 0)) {
			// Not a Number
			$float_value = false;
		} elseif (($exponent == (pow(2, $exponent_bits) - 1)) && ($fraction == 0)) {
			if ($sign_bit == '1') {
				$float_value = '-infinity';
			} else {
				$float_value = '+infinity';
			}
		} elseif (($exponent == 0) && ($fraction == 0)) {
			if ($sign_bit == '1') {
				$float_value = -0;
			} else {
				$float_value = 0;
			}
			$float_value = ($sign_bit ? 0 : -0);
		} elseif (($exponent == 0) && ($fraction != 0)) {
			// These are 'unnormalized' values
			$float_value = pow(2, (-1 * (pow(2, $exponent_bits - 1) - 2))) * bindec($fraction_string) / bindec('1'.str_repeat('0', strlen($fraction_string)));
			if ($sign_bit == '1') {
				$float_value *= -1;
			}
		} elseif ($exponent != 0) {
			$float_value = pow(2, ($exponent - (pow(2, $exponent_bits - 1) - 1))) * (1 + bindec($fraction_string) / bindec('1'.str_repeat('0', strlen($fraction_string))));
			if ($sign_bit == '1') {
				$float_value *= -1;
			}
		}
		return (float) $float_value;
	}
}

?>