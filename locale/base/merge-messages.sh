#!/bin/sh
#
# Copyright (c) Ampache.org
# All rights reserved.
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License v2
# as published by the Free Software Foundation.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
#


msgmerge -N ../de_DE/LC_MESSAGES/messages.po ./messages.po --output-file=../de_DE/LC_MESSAGES/messages.po
msgfmt -o ../de_DE/LC_MESSAGES/messages.mo ../de_DE/LC_MESSAGES/messages.po
msgmerge -N ../ca_CA/LC_MESSAGES/messages.po ./messages.po --output-file=../ca_CA/LC_MESSAGES/messages.po
msgfmt -o ../ca_CA/LC_MESSAGES/messages.mo ../ca_CA/LC_MESSAGES/messages.po
msgmerge -N ../el_GR/LC_MESSAGES/messages.po ./messages.po --output-file=../el_GR/LC_MESSAGES/messages.po
msgfmt -o ../el_GR/LC_MESSAGES/messages.mo ../el_GR/LC_MESSAGES/messages.po
msgmerge -N ../es_ES/LC_MESSAGES/messages.po ./messages.po --output-file=../es_ES/LC_MESSAGES/messages.po
msgfmt -o ../es_ES/LC_MESSAGES/messages.mo ../es_ES/LC_MESSAGES/messages.po
msgmerge -N ../en_GB/LC_MESSAGES/messages.po ./messages.po --output-file=../en_GB/LC_MESSAGES/messages.po
msgfmt -o ../en_GB/LC_MESSAGES/messages.mo ../en_GB/LC_MESSAGES/messages.po
msgmerge -N ../fr_FR/LC_MESSAGES/messages.po ./messages.po --output-file=../fr_FR/LC_MESSAGES/messages.po
msgfmt -o ../fr_FR/LC_MESSAGES/messages.mo ../fr_FR/LC_MESSAGES/messages.po
msgmerge -N ../it_IT/LC_MESSAGES/messages.po ./messages.po --output-file=../it_IT/LC_MESSAGES/messages.po
msgfmt -o ../it_IT/LC_MESSAGES/messages.mo ../it_IT/LC_MESSAGES/messages.po
msgmerge -N ../nl_NL/LC_MESSAGES/messages.po ./messages.po --output-file=../nl_NL/LC_MESSAGES/messages.po
msgfmt -o ../nl_NL/LC_MESSAGES/messages.mo ../nl_NL/LC_MESSAGES/messages.po
msgmerge -N ../tr_TR/LC_MESSAGES/messages.po ./messages.po --output-file=../tr_TR/LC_MESSAGES/messages.po
msgfmt -o ../tr_TR/LC_MESSAGES/messages.mo ../tr_TR/LC_MESSAGES/messages.po
msgmerge -N ../zh_CN/LC_MESSAGES/messages.po ./messages.po --output-file=../zh_CN/LC_MESSAGES/messages.po
msgfmt -o ../zh_CN/LC_MESSAGES/messages.mo ../zh_CN/LC_MESSAGES/messages.po
msgmerge -N ../ru_RU/LC_MESSAGES/messages.po ./messages.po --output-file=../ru_RU/LC_MESSAGES/messages.po
msgfmt -o ../ru_RU/LC_MESSAGES/messages.mo ../ru_RU/LC_MESSAGES/messages.po
