<?php

if (PHP_OS === 'Linux') {
    if (!file_exists('/usr/bin/metaflac')) {
        echo "\n\n\nMetaflac is required to  write tags to flac files\n\n\n.";
    }

    if (!file_exists('/usr/bin/vorbiscomment')) {
        echo "Vorbiscomment  is required to  write Vorbis  tags to ogg files";
    }
}
