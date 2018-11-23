<?php

return [
    
    'transcode_cmd' => "",
    
    //'transcode_cmd' => "",
    
    'transcode_input' => "-i %FILE%",
 
    'transcode_cmd_mid' => "timidity -Or -o – %FILE% |  -f s16le -i pipe:0",
    
    'encode_args_mp3' => "-vn -b:a %BITRATE%K -c:a libmp3lame -f mp3 pipe:1",
    
    'encode_args_ogg' => "-vn -b:a %BITRATE%K -c:a libvorbis -f ogg pipe:1",
    
    'encode_args_opus' => "-vn -b:a %BITRATE%K -c:a libopus -compression_level 10 -vsync 2 -f ogg pipe:1",
    
    'encode_args_m4a' => "-vn -b:a %BITRATE%K -c:a libfdk_aac -f adts pipe:1",
    
    'encode_args_wav' => "-vn -b:a %BITRATE%K -c:a pcm_s16le -f wav pipe:1",
    
    'encode_args_flv' => "-b:a %BITRATE%K -ar 44100 -ac 2 -v 0 -f flv -c:v libx264 -preset superfast -threads 0 pipe:1",
    
    'encode_args_webm' => "-q %QUALITY% -f webm -c:v libvpx -maxrate %MAXBITRATE%k -preset superfast -threads 0 pipe:1",

    'encode_args_ts' => "-q %QUALITY% -s %RESOLUTION% -f mpegts -c:v libx264 -c:a libmp3lame -maxrate %MAXBITRATE%k -preset superfast -threads 0 pipe:1",
    
    // Encoding arguments to retrieve an image from a single frame
    'encode_get_image' => "-ss %TIME% -f image2 -vframes 1 pipe:1",
        
    // Encoding argument to encrust subtitle
    'encode_srt' => "-vf \"subtitles=\'%SRTFILE%\'",
        
    // Encode segment frame argument
    'encode_ss_frame' => "-ss %TIME%",
        
    // Encode segment duration argument
    'encode_ss_duration' => "-t %DURATION%",
        
];
