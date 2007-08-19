#!/bin/sh
#
# Copyright (c) 2001 - 2007 Ampache.org
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
msgmerge -N ../es_ES/LC_MESSAGES/messages.po ./messages.po --output-file=../es_ES/LC_MESSAGES/messages.po
msgmerge -N ../en_GB/LC_MESSAGES/messages.po ./messages.po --output-file=../en_GB/LC_MESSAGES/messages.po
msgmerge -N ../fr_FR/LC_MESSAGES/messages.po ./messages.po --output-file=../fr_FR/LC_MESSAGES/messages.po
msgmerge -N ../it_IT/LC_MESSAGES/messages.po ./messages.po --output-file=../it_IT/LC_MESSAGES/messages.po
msgmerge -N ../nl_NL/LC_MESSAGES/messages.po ./messages.po --output-file=../nl_NL/LC_MESSAGES/messages.po
msgmerge -N ../tr_TR/LC_MESSAGES/messages.po ./messages.po --output-file=../tr_TR/LC_MESSAGES/messages.po
msgmerge -N ../zh_CN/LC_MESSAGES/messages.po ./messages.po --output-file=../zh_CN/LC_MESSAGES/messages.po
msgmerge -N ../ru_RU/LC_MESSAGES/messages.po ./messages.po --output-file=../ru_RU/LC_MESSAGES/messages.po
msgmerge -N ../is_IS/LC_MESSAGES/messages.po ./messages.po --output-file=../is_IS/LC_MESSAGES/messages.po
