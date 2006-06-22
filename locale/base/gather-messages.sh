#!/bin/sh

find ../../ -name *.php > /tmp/filelist
find ../../ -name *.inc >> /tmp/filelist

xgettext -f /tmp/filelist -L PHP -o ./messages.po
