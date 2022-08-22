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

# cli command run:updateCatalog
#
# Perform catalog actions for all files of a catalog. If no options are given, the defaults actions -ceag are assumed
#
# Usage: run:updateCatalog [OPTIONS...] [ARGUMENTS...]
#
# Arguments:
#   [catalogName]    Name of Catalogue (optional)
#   [catalogType]    Type of Catalogue (optional)
#
# Options:
#   [-a|--add]            Adds new media files to the database
#   [-g|--art]            Gathers media Art
#   [-c|--cleanup]        Removes missing files from the database
#   [-f|--find]           Find missing files and print a list of filenames
#   [-h|--help]           Show help
#   [-i|--import]         Adds new media files and imports playlist files
#   [-m|--memorylimit]    Temporarily deactivates PHP memory limit
#   [-o|--optimize]       Optimises database tables
#   [-u|--update]         Update local object metadata using external plugins
#   [-v|--verbosity]      Verbosity level
#   [-e|--verify]         Reads your files and updates the database to match changes
#   [-V|--version]        Show version

# music file extensions to look for
declare -a arr=("mp3" "mpc" "m4p" "m4a" "aac" "ogg" "oga" "wav" "aif" "aiff" "rm" "wma" "asf" "flac" "opus" "spx" "ra" "ape" "shn" "wv")

# monitor your media folder for updates
inotifywait -m -r --event close_write --event moved_to --event create --event delete --format '%w%f' /media |
    while read file; do
        for i in "${arr[@]}"
        do
            if [[ "$file" =~ .*$i$ ]]; then
                echo "$file"
                php /var/www/bin/cli run:updateCatalogFile -n music -f "$file" -cage
            fi
        done
    done

