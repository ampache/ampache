#!/bin/sh


msgmerge ./messages.po ../de_DE/LC_MESSAGES/messages.po --output-file=../de_DE/LC_MESSAGES/messages.po
msgmerge ./messages.po ../es_ES/LC_MESSAGES/messages.po --output-file=../es_ES/LC_MESSAGES/messages.po
msgmerge ./messages.po ../en_GB/LC_MESSAGES/messages.po --output-file=../en_GB/LC_MESSAGES/messages.po
msgmerge ./messages.po ../fr_FR/LC_MESSAGES/messages.po --output-file=../fr_FR/LC_MESSAGES/messages.po
msgmerge ./messages.po ../it_IT/LC_MESSAGES/messages.po --output-file=../it_IT/LC_MESSAGES/messages.po
msgmerge ./messages.po ../nl_NL/LC_MESSAGES/messages.po --output-file=../nl_NL/LC_MESSAGES/messages.po
msgmerge ./messages.po ../tr_TR/LC_MESSAGES/messages.po --output-file=../tr_TR/LC_MESSAGES/messages.po
msgmerge ./messages.po ../zh_CN/LC_MESSAGES/messages.po --output-file=../zh_CN/LC_MESSAGES/messages.po
