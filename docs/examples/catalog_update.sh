#!/bin/sh

### This is an example file that i use to keep my catalogs updated
### Comment and Uncomment the lines you want to use

### Set your ampache root folder
AMPACHEDIR="/var/www/ampache"

### What's the folder being updated
echo $AMPACHEDIR

### cd to the folder
cd $AMPACHEDIR

# Command run:updateCatalog, version 7.3.1
#
# Perform catalog actions for all files of a catalog. If no options are given, the defaults actions -ceag are assumed
#
# Usage: run:updateCatalog [OPTIONS...] [ARGUMENTS...]
#
# Arguments:
#   [catalogName]    Name of Catalog (optional)
#   [catalogType]    Type of Catalog (optional) [default: "local"]
#
# Options:
#   [-a|--add]            Adds new media files to the database [default: false]
#   [-g|--art]            Gathers media Art [default: false]
#   [-c|--cleanup]        Removes missing files from the database [default: false]
#   [-f|--find]           Find missing files and print a list of filenames [default: false]
#   [-t|--garbage]        Update table mapping, counts and delete garbage data [default: false]
#   [-h|--help]           Help
#   [-i|--import]         Adds new media files and imports playlist files [default: false]
#   [-l|--limit]          Item Limit (Verify) [default: 0]
#   [-m|--memorylimit]    Temporarily deactivates PHP memory limit [default: false]
#   [-o|--optimize]       Optimises database tables [default: false]
#   [-u|--update]         Update local object metadata using external plugins [default: false]
#   [-e|--verify]         Reads your files and updates the database to match changes [default: false]
#
# Legend: <required> [optional] variadic...
#
# Usage Examples:
#   run:updateCatalog some-catalog local   # Update the local catalog called `some-catalog`

### Clean missing and removed files
php bin/cli run:updateCatalog -c

### Verify 100 existing files
#php bin/cli run:updateCatalog -e -l 100

### Add new files and gather art
php bin/cli run:updateCatalog -ag

### Add new files in the podcast catalog
#php bin/cli run:updateCatalog podcast -a

### Run the transcode cache process
php bin/cli run:cacheProcess

### Update table mapping, counts and delete garbage data
php bin/cli run:updateCatalog -t

### Alternativey you can run them all from one command
#php bin/cli run:updateCatalog -caget
