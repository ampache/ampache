#!/bin/bash
#
# vim:set softtabstop=4 shiftwidth=4 expandtab:
#
# Copyright Ampache.org, 2001-2024
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

PATH=$PATH:/bin:/usr/bin:/usr/local/bin

# gettext package test
if ! which xgettext &>/dev/null ; then
    echo "Xgettext was not found. Do you need to install gettext?"
    exit 1;
fi

[[ $OLANG ]] || OLANG=$(echo $LANG | sed 's/\..*//;')
potfile='messages.pot'
tdstxt='translatable-database-strings.txt'
xhtmltxt='untranslated-strings.txt'

##############################################################

usage() {
    echo ""
    echo -e "\033[32m usage: $0 [-h|--help][-g|--get][-gu|--getutds][-i|--init][-m|--merge][-f|--format][-a|--all][-au|--allutds]\033[0m"
    echo ""
    echo -e "[-g|--get]\t\t Creates the messages.pot file from translation strings within the source code."
    echo -e "[-gu|--getutds]\t\t Generates the Pot file from translation strings within the source code\n\t\t\t and (re)generates 'translatable-database-strings.txt' from the preference strings in\n\t\t\t the source code (Preference::translate_db() and Preference::DEFAULTS). No database needed."
    echo -e "[-i|--init]\t\t Creates a new language catalog and its directory structure."
    echo -e "[-m|--merge]\t\t Merges the messages.pot into the language catalogs and shows obsolet translations."
    echo -e "[-ma|--mergeall]\t Same as -m but for all translations."
    echo -e "[-f|--format]\t\t Compiles the .mo file for its related .po file."
    echo -e "[-fa|--formatall]\t Same as -f but for all translations."
    echo -e "[-ro|--rmobsolete]\t Delete obsolete/orphaned entries from source-translation file and compiles it."
    echo -e "[-roa|--rmobsoleteall]\t Same as -ro but for all translations."
    echo -e "[-a|--all]\t\t Does all except --init and --utds."
    echo -e "[-au|--allutds]\t\t Does all except --init"
    echo -e "[-h|--help]\t\t Shows this help screen."
    echo ""
    echo -e "\033[32m If you encounter any bugs, please report them on Transifex (https://www.transifex.com/projects/p/ampache/)\033[0m"
    echo -e "\033[32m See also: https://github.com/ampache/ampache/blob/develop/locale/base/TRANSLATIONS.md\033[0m"
    echo ""
    exit 1
}

##############################################################

# Generate/overwrite messages.pot file from Source-Strings
generate_pot() {
    echo "Generating/updating pot-file"
    xgettext    --no-location \
                --from-code=UTF-8 \
                --add-comment=HINT: \
                --msgid-bugs-address="https://www.transifex.com/projects/p/ampache/" \
                -L php \
                --keyword=T_ --keyword=nT_:1,2 \
                -o $potfile \
                $(find ../../ -type f \( -name "*.php" -o -name "*.inc" \) -not -path "../../config/*" -not -path "../../docs/*" -not -path "../../public/lib/components/*" -not -path "../../vendor/*" -not -path "../../tests/*" | sort)
    if [[ $? -eq 0 ]]; then
        echo -e "\033[32m Pot file creation succeeded. Adding 'translatable-database-strings.txt\033[0m"
        cat $tdstxt >> $potfile
        echo -e "\033[32m Pot file creation succeeded. Adding 'untranslated-strings.txt\033[0m"
        cat $xhtmltxt >> $potfile
        echo -e "\n\033[32m Done, you are able now to use the messages.pot for further translation tasks.\033[0m"
    else
        echo -e "\033[31m Error\033[0m: Pot file creation has failed!"
    fi
}

# Add preference msgid blocks to the tds file, skipping strings already extracted into the pot
# (from a literal T_() in the source) so the final catalog has no duplicate msgids.
# $1 = reference comment, remaining input read from stdin (one raw string per line).
add_db_entries() {
    comment="$1"
    while IFS= read -r line; do
        [ -z "$line" ] && continue
        escaped=$(printf '%s' "$line" | sed 's/\\/\\\\/g; s/"/\\"/g')
        if ! grep -qF "msgid \"$escaped\"" $potfile $tdstxt; then
            printf '\n#: %s\nmsgid "%s"\nmsgstr ""\n' "$comment" "$escaped" >> $tdstxt
        fi
    done
}

# Generate/overwrite messages.pot from Source-Strings and preference strings read from the source code.
# Descriptions come from Preference::translate_db(); subcategories from Preference::DEFAULTS. No database required.
generate_pot_utds() {
    echo ""
    echo "Generating/updating pot-file"
    echo ""
    xgettext    --no-location \
                --from-code=UTF-8 \
                --add-comment=HINT: \
                --msgid-bugs-address="https://www.transifex.com/projects/p/ampache/" \
                -L php \
                --keyword=T_ --keyword=nT_:1,2 \
                -o $potfile \
                $(find ../../ -type f \( -name "*.php" -o -name "*.inc" \) -not -path "../../config/*" -not -path "../../docs/*" -not -path "../../public/lib/components/*" -not -path "../../vendor/*" -not -path "../../tests/*" | sort)
    if [[ $? -ne 0 ]]; then
        echo -e "\033[31m Error\033[0m: Pot file creation has failed!"
        return 1
    fi

    preffile='../../src/Module/System/Preference.php'
    if [[ ! -f "$preffile" ]]; then
        echo -e "\033[31m Error\033[0m: $preffile not found, the preference strings cannot be gathered."
        return 1
    fi

    tmpdir=$(mktemp -d)

    echo -e "\033[32m Pot creation/update successful\033[0m\n"
    echo -e "Gathering preference strings from the source code (no database required)\n"

    # Descriptions: the values of the $pref_array map inside Preference::translate_db()
    awk '/public static function translate_db/{f=1}
         f && /\$pref_array = \[/{g=1; next}
         g && /^[[:space:]]*\];/{exit}
         g' "$preffile" \
      | sed -n "s/^[[:space:]]*'[a-z0-9_]*' => \(.*\),[[:space:]]*\$/\1/p" \
      | sed 's/^.\(.*\).$/\1/' \
      | awk '!seen[$0]++' > "$tmpdir/desc.txt"

    # Subcategories: the last element of each Preference::DEFAULTS row, which set_defaults() inserts.
    # Title-case them to match rendering - the template calls T_(ucwords($subcategory)).
    awk '/public const array DEFAULTS = \[/{f=1; next}
         f && /^[[:space:]]*\];/{exit}
         f' "$preffile" \
      | sed -E "s/.*, ('[^']*'|null)\],[[:space:]]*\$/\1/" \
      | grep -v '^null$' | tr -d "'" \
      | perl -pe 's/(?:^|(?<=\s))([a-z])/\u$1/g' | sort -u > "$tmpdir/subcat.txt"

    # Both lists are read out of the source with awk, so a rename in Preference.php shows up here as an
    # empty file rather than an error. Stop instead of overwriting $tdstxt with nothing.
    for list in desc subcat; do
        if [[ ! -s "$tmpdir/$list.txt" ]]; then
            echo -e "\033[31m Error\033[0m: no $list strings found in $preffile, leaving $tdstxt alone."
            echo -e " The extraction in this script has to be updated to match the class."
            rm -rf "$tmpdir"
            return 1
        fi
    done

    echo "Deleting old $tdstxt"
    rm -f $tdstxt
    {
        printf ' #######################################################################\n\n'
        printf ' # This file lists all translatable strings from the Ampache preference table\n'
        printf ' # (descriptions and subcategories). It is generated from the source code by\n'
        printf " # './gather-messages.sh [-gu|--getutds]' - descriptions come from\n"
        printf ' # Preference::translate_db() and subcategories from Preference::DEFAULTS,\n'
        printf ' # so a live database is NOT required. Do not edit it by hand; re-run the script.\n\n'
        printf ' #######################################################################\n'
    } > $tdstxt

    add_db_entries "Ampache preference description" < "$tmpdir/desc.txt"
    echo "Done for preference description"
    add_db_entries "Ampache preference subcategory" < "$tmpdir/subcat.txt"
    echo "Done for subcategory"

    rm -rf "$tmpdir"

    echo -e "\033[32m Pot file creation succeeded. Adding 'untranslated-strings.txt\033[0m"
    cat $xhtmltxt >> $potfile
    echo -e "\033[32m Pot file creation succeeded. Adding 'translatable-database-strings.txt\033[0m"
    cat $tdstxt >> $potfile
    echo -e "\n\033[32m Done, you are able now to use the messages.pot for further translation tasks.\033[0m"
}

# Merge old and new gathered translations
do_msgmerge() {
    source=$potfile
    target="../$1/LC_MESSAGES/messages.po"
    echo "Merging $source into $target"
    msgmerge --update --backup=off $target $source
    echo "Obsolete messages in $target: " $(grep '^#~' $target | wc -l)
}

# Compiling translation files (create the messages.mo files)
do_msgfmt() {
    source="../$1/LC_MESSAGES/messages.po"
    target="../$1/LC_MESSAGES/messages.mo"
    echo "Creating $target from $source"
    msgfmt --verbose --check $source -o $target
}

# Kill obsolete translation strings from translation (.po) files and format/compile them
rm_obsolete() {
    source="../$1/LC_MESSAGES/messages.po"
    echo "Delete obsolete Entries in $source"
    msgattrib --no-obsolete $source -o $source
}

##############################################################

if [[ $# -eq 0 ]]; then
    usage
fi

case $1 in
    '-a'|'--all')
        generate_pot
        for i in $(ls ../ | grep -v base); do
            do_msgmerge $i
            rm_obsolete $i
            do_msgfmt $i
        done
    ;;
    '-au'|'--allutds')
        generate_pot_utds
        for i in $(ls ../ | grep -v base); do
            do_msgmerge $i
            rm_obsolete $i
            do_msgfmt $i
        done
    ;;
    '-g'|'--get')
        generate_pot
    ;;
    '-gu'|'--getutds')
        generate_pot_utds
    ;;
    '-i'|'--init'|'init')
        outdir="../$OLANG/LC_MESSAGES"
        [[ -d $outdir ]] || mkdir -p $outdir
        msginit -l $LANG -i $potfile -o $outdir/messages.po
    ;;
    '-f'|'--format'|'format')
        do_msgfmt $OLANG
    ;;
    '-fa'|'--formatall')
        for i in $(ls ../ | grep -v base); do
            do_msgfmt $i
        done
    ;;
    '-ro'|'--rmobsolete')
            rm_obsolete $OLANG
            do_msgfmt $OLANG
    ;;
    '-roa'|'--rmobsoleteall')
        for i in $(ls ../ | grep -v base); do
            rm_obsolete $i
            do_msgfmt $i
        done
    ;;
    '-m'|'--merge'|'merge')
        do_msgmerge $OLANG
    ;;
    '-ma'|'--mergeall')
        for i in $(ls ../ | grep -v base); do
            do_msgmerge $i
        done
    ;;
    '-h'|'--help'|'help'|'*')
        usage
    ;;
esac
