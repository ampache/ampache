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
// | module.audio.midi.php                                                |
// | Module for analyzing midi audio files                                |
// | dependencies: NONE                                                   |
// +----------------------------------------------------------------------+
//
// $Id: module.audio.midi.php,v 1.5 2006/11/02 10:48:01 ah Exp $



class getid3_midi extends getid3_handler
{

    public function Analyze() {

        $getid3 = $this->getid3;

        $getid3->info['midi']['raw'] = array ();
        $info_midi     = &$getid3->info['midi'];
        $info_midi_raw = &$info_midi['raw'];

        $getid3->info['fileformat']          = 'midi';
        $getid3->info['audio']['dataformat'] = 'midi';

        fseek($getid3->fp, $getid3->info['avdataoffset'], SEEK_SET);
        $midi_data = fread($getid3->fp, getid3::FREAD_BUFFER_SIZE);

        // Magic bytes: 'MThd'

        getid3_lib::ReadSequence('BigEndian2Int', $info_midi_raw, $midi_data, 4,
            array (
                'headersize'    => 4,
                'fileformat'    => 2,
                'tracks'        => 2,
                'ticksperqnote' => 2
            )
        );

        $offset = 14;

        for ($i = 0; $i < $info_midi_raw['tracks']; $i++) {

            if ((strlen($midi_data) - $offset) < 8) {
                $midi_data .= fread($getid3->fp, getid3::FREAD_BUFFER_SIZE);
            }

            $track_id = substr($midi_data, $offset, 4);
            $offset += 4;

            if ($track_id != 'MTrk') {
                throw new getid3_exception('Expecting "MTrk" at '.$offset.', found '.$track_id.' instead');
            }

            $track_size = getid3_lib::BigEndian2Int(substr($midi_data, $offset, 4));
            $offset += 4;

            $track_data_array[$i] = substr($midi_data, $offset, $track_size);
            $offset += $track_size;
        }

        if (!isset($track_data_array) || !is_array($track_data_array)) {
            throw new getid3_exception('Cannot find MIDI track information');
        }


        $info_midi['totalticks']          = 0;
        $getid3->info['playtime_seconds'] = 0;
        $current_ms_per_beat              = 500000; // 120 beats per minute;  60,000,000 microseconds per minute -> 500,000 microseconds per beat
        $current_beats_per_min            = 120;    // 120 beats per minute;  60,000,000 microseconds per minute -> 500,000 microseconds per beat
        $ms_per_quarter_note_after        = array ();

        foreach ($track_data_array as $track_number => $track_data) {

            $events_offset = $last_issued_midi_command = $last_issued_midi_channel = $cumulative_delta_time = $ticks_at_current_bpm = 0;

            while ($events_offset < strlen($track_data)) {

                $event_id = 0;
                if (isset($midi_events[$track_number]) && is_array($midi_events[$track_number])) {
                    $event_id = count($midi_events[$track_number]);
                }
                $delta_time = 0;
                for ($i = 0; $i < 4; $i++) {
                    $delta_time_byte = ord($track_data{$events_offset++});
                    $delta_time = ($delta_time << 7) + ($delta_time_byte & 0x7F);
                    if ($delta_time_byte & 0x80) {
                        // another byte follows
                    } else {
                        break;
                    }
                }

                $cumulative_delta_time += $delta_time;
                $ticks_at_current_bpm  += $delta_time;

                $midi_events[$track_number][$event_id]['deltatime'] = $delta_time;

                $midi_event_channel                                 = ord($track_data{$events_offset++});

                // OK, normal event - MIDI command has MSB set
                if ($midi_event_channel & 0x80) {
                    $last_issued_midi_command = $midi_event_channel >> 4;
                    $last_issued_midi_channel = $midi_event_channel & 0x0F;
                }

                // Running event - assume last command
                else {
                    $events_offset--;
                }

                $midi_events[$track_number][$event_id]['eventid'] = $last_issued_midi_command;
                $midi_events[$track_number][$event_id]['channel'] = $last_issued_midi_channel;

                switch ($midi_events[$track_number][$event_id]['eventid']) {

                    case 0x8:       // Note off (key is released)
                    case 0x9:       // Note on (key is pressed)
                    case 0xA:       // Key after-touch

                        //$notenumber = ord($track_data{$events_offset++});
                        //$velocity   = ord($track_data{$events_offset++});
                        $events_offset += 2;
                        break;


                    case 0xB:       // Control Change

                        //$controllernum = ord($track_data{$events_offset++});
                        //$newvalue      = ord($track_data{$events_offset++});
                        $events_offset += 2;
                        break;


                    case 0xC:       // Program (patch) change

                        $new_program_num = ord($track_data{$events_offset++});

                        $info_midi_raw['track'][$track_number]['instrumentid'] = $new_program_num;
                        $info_midi_raw['track'][$track_number]['instrument']   = $track_number == 10 ? getid3_midi::GeneralMIDIpercussionLookup($new_program_num) : getid3_midi::GeneralMIDIinstrumentLookup($new_program_num);
                        break;


                    case 0xD:       // Channel after-touch

                        //$channelnumber = ord($track_data{$events_offset++});
                        break;


                    case 0xE:       // Pitch wheel change (2000H is normal or no change)

                        //$changeLSB = ord($track_data{$events_offset++});
                        //$changeMSB = ord($track_data{$events_offset++});
                        //$pitchwheelchange = (($changeMSB & 0x7F) << 7) & ($changeLSB & 0x7F);
                        $events_offset += 2;
                        break;


                    case 0xF:

                        if ($midi_events[$track_number][$event_id]['channel'] == 0xF) {

                            $meta_event_command = ord($track_data{$events_offset++});
                            $meta_event_length  = ord($track_data{$events_offset++});
                            $meta_event_data    = substr($track_data, $events_offset, $meta_event_length);
                            $events_offset += $meta_event_length;

                            switch ($meta_event_command) {

                                case 0x00: // Set track sequence number

                                    //$track_sequence_number = getid3_lib::BigEndian2Int(substr($meta_event_data, 0, $meta_event_length));
                                    //$info_midi_raw['events'][$track_number][$event_id]['seqno'] = $track_sequence_number;
                                    break;


                                case 0x01: // Text: generic

                                    $text_generic = substr($meta_event_data, 0, $meta_event_length);
                                    //$info_midi_raw['events'][$track_number][$event_id]['text'] = $text_generic;
                                    $info_midi['comments']['comment'][] = $text_generic;
                                    break;


                                case 0x02: // Text: copyright

                                    $text_copyright = substr($meta_event_data, 0, $meta_event_length);
                                    //$info_midi_raw['events'][$track_number][$event_id]['copyright'] = $text_copyright;
                                    $info_midi['comments']['copyright'][] = $text_copyright;
                                    break;


                                case 0x03: // Text: track name

                                    $text_trackname = substr($meta_event_data, 0, $meta_event_length);
                                    $info_midi_raw['track'][$track_number]['name'] = $text_trackname;
                                    break;


                                case 0x04: // Text: track instrument name

                                    //$text_instrument = substr($meta_event_data, 0, $meta_event_length);
                                    //$info_midi_raw['events'][$track_number][$event_id]['instrument'] = $text_instrument;
                                    break;


                                case 0x05: // Text: lyrics

                                    $text_lyrics = substr($meta_event_data, 0, $meta_event_length);
                                    //$info_midi_raw['events'][$track_number][$event_id]['lyrics'] = $text_lyrics;
                                    if (!isset($info_midi['lyrics'])) {
                                        $info_midi['lyrics'] = '';
                                    }
                                    $info_midi['lyrics'] .= $text_lyrics . "\n";
                                    break;


                                case 0x06: // Text: marker

                                    //$text_marker = substr($meta_event_data, 0, $meta_event_length);
                                    //$info_midi_raw['events'][$track_number][$event_id]['marker'] = $text_marker;
                                    break;


                                case 0x07: // Text: cue point

                                    //$text_cuepoint = substr($meta_event_data, 0, $meta_event_length);
                                    //$info_midi_raw['events'][$track_number][$event_id]['cuepoint'] = $text_cuepoint;
                                    break;


                                case 0x2F: // End Of Track

                                    //$info_midi_raw['events'][$track_number][$event_id]['EOT'] = $cumulative_delta_time;
                                    break;


                                case 0x51: // Tempo: microseconds / quarter note

                                    $current_ms_per_beat = getid3_lib::BigEndian2Int(substr($meta_event_data, 0, $meta_event_length));
                                    $info_midi_raw['events'][$track_number][$cumulative_delta_time]['us_qnote'] = $current_ms_per_beat;
                                    $current_beats_per_min = (1000000 / $current_ms_per_beat) * 60;
                                    $ms_per_quarter_note_after[$cumulative_delta_time] = $current_ms_per_beat;
                                    $ticks_at_current_bpm = 0;
                                    break;


                                case 0x58: // Time signature
                                    $timesig_numerator   = getid3_lib::BigEndian2Int($meta_event_data[0]);
                                    $timesig_denominator = pow(2, getid3_lib::BigEndian2Int($meta_event_data[1])); // $02 -> x/4, $03 -> x/8, etc
                                    //$timesig_32inqnote   = getid3_lib::BigEndian2Int($meta_event_data[2]);         // number of 32nd notes to the quarter note
                                    //$info_midi_raw['events'][$track_number][$event_id]['timesig_32inqnote']   = $timesig_32inqnote;
                                    //$info_midi_raw['events'][$track_number][$event_id]['timesig_numerator']   = $timesig_numerator;
                                    //$info_midi_raw['events'][$track_number][$event_id]['timesig_denominator'] = $timesig_denominator;
                                    //$info_midi_raw['events'][$track_number][$event_id]['timesig_text']        = $timesig_numerator.'/'.$timesig_denominator;
                                    $info_midi['timesignature'][] = $timesig_numerator.'/'.$timesig_denominator;
                                    break;


                                case 0x59: // Keysignature

                                    $keysig_sharpsflats = getid3_lib::BigEndian2Int($meta_event_data{0});
                                    if ($keysig_sharpsflats & 0x80) {
                                        // (-7 -> 7 flats, 0 ->key of C, 7 -> 7 sharps)
                                        $keysig_sharpsflats -= 256;
                                    }

                                    $keysig_majorminor  = getid3_lib::BigEndian2Int($meta_event_data{1}); // 0 -> major, 1 -> minor
                                    $keysigs = array (-7=>'Cb', -6=>'Gb', -5=>'Db', -4=>'Ab', -3=>'Eb', -2=>'Bb', -1=>'F', 0=>'C', 1=>'G', 2=>'D', 3=>'A', 4=>'E', 5=>'B', 6=>'F#', 7=>'C#');
                                    //$info_midi_raw['events'][$track_number][$event_id]['keysig_sharps'] = (($keysig_sharpsflats > 0) ? abs($keysig_sharpsflats) : 0);
                                    //$info_midi_raw['events'][$track_number][$event_id]['keysig_flats']  = (($keysig_sharpsflats < 0) ? abs($keysig_sharpsflats) : 0);
                                    //$info_midi_raw['events'][$track_number][$event_id]['keysig_minor']  = (bool)$keysig_majorminor;
                                    //$info_midi_raw['events'][$track_number][$event_id]['keysig_text']   = $keysigs[$keysig_sharpsflats].' '.($info_midi_raw['events'][$track_number][$event_id]['keysig_minor'] ? 'minor' : 'major');

                                    // $keysigs[$keysig_sharpsflats] gets an int key (correct) - $keysigs["$keysig_sharpsflats"] gets a string key (incorrect)
                                    $info_midi['keysignature'][] = $keysigs[$keysig_sharpsflats].' '.((bool)$keysig_majorminor ? 'minor' : 'major');
                                    break;


                                case 0x7F: // Sequencer specific information

                                    $custom_data = substr($meta_event_data, 0, $meta_event_length);
                                    break;


                                default:

                                    $getid3->warning('Unhandled META Event Command: '.$meta_event_command);
                            }
                        }
                        break;


                    default:
                        $getid3->warning('Unhandled MIDI Event ID: '.$midi_events[$track_number][$event_id]['eventid']);
                }
            }

            if (($track_number > 0) || (count($track_data_array) == 1)) {
                $info_midi['totalticks'] = max($info_midi['totalticks'], $cumulative_delta_time);
            }
        }

        $previous_tick_offset = null;

        ksort($ms_per_quarter_note_after);
        foreach ($ms_per_quarter_note_after as $tick_offset => $ms_per_beat) {

            if (is_null($previous_tick_offset)) {
                $prev_ms_per_beat     = $ms_per_beat;
                $previous_tick_offset = $tick_offset;
                continue;
            }

            if ($info_midi['totalticks'] > $tick_offset) {
                $getid3->info['playtime_seconds'] += (($tick_offset - $previous_tick_offset) / $info_midi_raw['ticksperqnote']) * ($prev_ms_per_beat / 1000000);

                $prev_ms_per_beat     = $ms_per_beat;
                $previous_tick_offset = $tick_offset;
            }
        }

        if ($info_midi['totalticks'] > $previous_tick_offset) {
            $getid3->info['playtime_seconds'] += (($info_midi['totalticks'] - $previous_tick_offset) / $info_midi_raw['ticksperqnote']) * ($ms_per_beat / 1000000);
        }

        if (@$getid3->info['playtime_seconds'] > 0) {
            $getid3->info['bitrate'] = (($getid3->info['avdataend'] - $getid3->info['avdataoffset']) * 8) / $getid3->info['playtime_seconds'];
        }

        if (!empty($info_midi['lyrics'])) {
            $info_midi['comments']['lyrics'][] = $info_midi['lyrics'];
        }

        return true;
    }



    public static function GeneralMIDIinstrumentLookup($instrument_id) {

        static $lookup = array (

              0 => 'Acoustic Grand',
              1 => 'Bright Acoustic',
              2 => 'Electric Grand',
              3 => 'Honky-Tonk',
              4 => 'Electric Piano 1',
              5 => 'Electric Piano 2',
              6 => 'Harpsichord',
              7 => 'Clavier',
              8 => 'Celesta',
              9 => 'Glockenspiel',
             10 => 'Music Box',
             11 => 'Vibraphone',
             12 => 'Marimba',
             13 => 'Xylophone',
             14 => 'Tubular Bells',
             15 => 'Dulcimer',
             16 => 'Drawbar Organ',
             17 => 'Percussive Organ',
             18 => 'Rock Organ',
             19 => 'Church Organ',
             20 => 'Reed Organ',
             21 => 'Accordian',
             22 => 'Harmonica',
             23 => 'Tango Accordian',
             24 => 'Acoustic Guitar (nylon)',
             25 => 'Acoustic Guitar (steel)',
             26 => 'Electric Guitar (jazz)',
             27 => 'Electric Guitar (clean)',
             28 => 'Electric Guitar (muted)',
             29 => 'Overdriven Guitar',
             30 => 'Distortion Guitar',
             31 => 'Guitar Harmonics',
             32 => 'Acoustic Bass',
             33 => 'Electric Bass (finger)',
             34 => 'Electric Bass (pick)',
             35 => 'Fretless Bass',
             36 => 'Slap Bass 1',
             37 => 'Slap Bass 2',
             38 => 'Synth Bass 1',
             39 => 'Synth Bass 2',
             40 => 'Violin',
             41 => 'Viola',
             42 => 'Cello',
             43 => 'Contrabass',
             44 => 'Tremolo Strings',
             45 => 'Pizzicato Strings',
             46 => 'Orchestral Strings',
             47 => 'Timpani',
             48 => 'String Ensemble 1',
             49 => 'String Ensemble 2',
             50 => 'SynthStrings 1',
             51 => 'SynthStrings 2',
             52 => 'Choir Aahs',
             53 => 'Voice Oohs',
             54 => 'Synth Voice',
             55 => 'Orchestra Hit',
             56 => 'Trumpet',
             57 => 'Trombone',
             58 => 'Tuba',
             59 => 'Muted Trumpet',
             60 => 'French Horn',
             61 => 'Brass Section',
             62 => 'SynthBrass 1',
             63 => 'SynthBrass 2',
             64 => 'Soprano Sax',
             65 => 'Alto Sax',
             66 => 'Tenor Sax',
             67 => 'Baritone Sax',
             68 => 'Oboe',
             69 => 'English Horn',
             70 => 'Bassoon',
             71 => 'Clarinet',
             72 => 'Piccolo',
             73 => 'Flute',
             74 => 'Recorder',
             75 => 'Pan Flute',
             76 => 'Blown Bottle',
             77 => 'Shakuhachi',
             78 => 'Whistle',
             79 => 'Ocarina',
             80 => 'Lead 1 (square)',
             81 => 'Lead 2 (sawtooth)',
             82 => 'Lead 3 (calliope)',
             83 => 'Lead 4 (chiff)',
             84 => 'Lead 5 (charang)',
             85 => 'Lead 6 (voice)',
             86 => 'Lead 7 (fifths)',
             87 => 'Lead 8 (bass + lead)',
             88 => 'Pad 1 (new age)',
             89 => 'Pad 2 (warm)',
             90 => 'Pad 3 (polysynth)',
             91 => 'Pad 4 (choir)',
             92 => 'Pad 5 (bowed)',
             93 => 'Pad 6 (metallic)',
             94 => 'Pad 7 (halo)',
             95 => 'Pad 8 (sweep)',
             96 => 'FX 1 (rain)',
             97 => 'FX 2 (soundtrack)',
             98 => 'FX 3 (crystal)',
             99 => 'FX 4 (atmosphere)',
            100 => 'FX 5 (brightness)',
            101 => 'FX 6 (goblins)',
            102 => 'FX 7 (echoes)',
            103 => 'FX 8 (sci-fi)',
            104 => 'Sitar',
            105 => 'Banjo',
            106 => 'Shamisen',
            107 => 'Koto',
            108 => 'Kalimba',
            109 => 'Bagpipe',
            110 => 'Fiddle',
            111 => 'Shanai',
            112 => 'Tinkle Bell',
            113 => 'Agogo',
            114 => 'Steel Drums',
            115 => 'Woodblock',
            116 => 'Taiko Drum',
            117 => 'Melodic Tom',
            118 => 'Synth Drum',
            119 => 'Reverse Cymbal',
            120 => 'Guitar Fret Noise',
            121 => 'Breath Noise',
            122 => 'Seashore',
            123 => 'Bird Tweet',
            124 => 'Telephone Ring',
            125 => 'Helicopter',
            126 => 'Applause',
            127 => 'Gunshot'
        );

        return @$lookup[$instrument_id];
    }



    public static function GeneralMIDIpercussionLookup($instrument_id) {

        static $lookup = array (

            35 => 'Acoustic Bass Drum',
            36 => 'Bass Drum 1',
            37 => 'Side Stick',
            38 => 'Acoustic Snare',
            39 => 'Hand Clap',
            40 => 'Electric Snare',
            41 => 'Low Floor Tom',
            42 => 'Closed Hi-Hat',
            43 => 'High Floor Tom',
            44 => 'Pedal Hi-Hat',
            45 => 'Low Tom',
            46 => 'Open Hi-Hat',
            47 => 'Low-Mid Tom',
            48 => 'Hi-Mid Tom',
            49 => 'Crash Cymbal 1',
            50 => 'High Tom',
            51 => 'Ride Cymbal 1',
            52 => 'Chinese Cymbal',
            53 => 'Ride Bell',
            54 => 'Tambourine',
            55 => 'Splash Cymbal',
            56 => 'Cowbell',
            57 => 'Crash Cymbal 2',
            59 => 'Ride Cymbal 2',
            60 => 'Hi Bongo',
            61 => 'Low Bongo',
            62 => 'Mute Hi Conga',
            63 => 'Open Hi Conga',
            64 => 'Low Conga',
            65 => 'High Timbale',
            66 => 'Low Timbale',
            67 => 'High Agogo',
            68 => 'Low Agogo',
            69 => 'Cabasa',
            70 => 'Maracas',
            71 => 'Short Whistle',
            72 => 'Long Whistle',
            73 => 'Short Guiro',
            74 => 'Long Guiro',
            75 => 'Claves',
            76 => 'Hi Wood Block',
            77 => 'Low Wood Block',
            78 => 'Mute Cuica',
            79 => 'Open Cuica',
            80 => 'Mute Triangle',
            81 => 'Open Triangle'
        );

        return @$lookup[$instrument_id];
    }


}


?>