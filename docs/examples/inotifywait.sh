#!/bin/bash

# inotifywait event types:
#    access        file or directory contents were read
#    modify        file or directory contents were written
#    attrib        file or directory attributes changed
#    close_write   file or directory closed, after being opened in writable mode
#    close_nowrite file or directory closed, after being opened in read-only mode
#    close         file or directory closed, regardless of read/write mode
#    open          file or directory opened
#    moved_to      file or directory moved to watched directory
#    moved_from    file or directory moved from watched directory
#    move          file or directory moved to or from watched directory
#    create        file or directory created within watched directory
#    delete        file or directory deleted within watched directory
#    delete_self   file or directory was deleted
#    unmount       file system containing file or directory unmounted

# catalog_update.inc options:
#    -h help
#    -n Catalog Name
#    -f File Path
#    -c Clean File
#    -v Verify File
#    -a Add File
#    -g Gather Art

# music file extensions to look for
declare -a arr=("mp3" "mpc" "m4p" "m4a" "aac" "ogg" "oga" "wav" "aif" "aiff" "rm" "wma" "asf" "flac" "opus" "spx" "ra" "ape" "shn" "wv")

# monitor your media folder for updates
inotifywait -m -r --event modify --event moved_to --event create --event delete --format '%w%f' /media |
    while read file; do
        for i in "${arr[@]}"
        do
            if [[ "$file" =~ .*$i$ ]]; then
                echo "$file"
                php /var/www/bin/update_file.inc -n music -f "$file" -cavg
            fi
        done
    done

