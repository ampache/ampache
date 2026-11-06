<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

// Flags British/Australian spellings in comments, docblocks, strings and docs -- Ampache is written in US English.
// Mirrors syntax.php's style so it can be wired into `composer qa` the same way.

$root = dirname(__DIR__, 3);

$skipDirs  = ['vendor', 'node_modules', '.git', 'build', '.idea', 'locale', 'lib', '.claude'];
$skipFiles = ['AGENTS.md', 'us_english.php']; // AGENTS.md is a deliberate joke file; this file's own dictionary is data, not prose

// "file:line" entries that are real external data, not a spelling mistake -- never auto-fix these.
$allowlist = [
    // the actual name of this Creative Commons-adjacent license and its real artlibre.org URL path
    'src/Module/System/Update/Migration/V3/Migration370004.php:75' => true,
    // the real on-disk filename; renaming the link here without renaming the file would break it
    'README.md:176' => true,
    'README.md:203' => true,
];

$dictionary = [];

// -our -> -or (colour, favour, honour, ...)
$ourRoots = [
    'colour', 'favour', 'flavour', 'behaviour', 'honour', 'humour', 'labour', 'neighbour', 'rumour',
    'splendour', 'armour', 'endeavour', 'harbour', 'vapour', 'valour', 'rigour', 'tumour', 'odour',
    'parlour', 'demeanour', 'candour', 'clamour', 'fervour', 'savour', 'saviour', 'vigour', 'ardour',
    'discolour', 'misbehaviour',
];
$ourSuffixes = ['', 's', 'd', 'ed', 'ing', 'er', 'ers', 'ful', 'less', 'able', 'ably', 'hood', 'ite', 'ites', 'y', 'al', 'ally'];
foreach ($ourRoots as $ourRoot) {
    $usRoot = substr($ourRoot, 0, -3) . 'or';
    foreach ($ourSuffixes as $suffix) {
        $dictionary[$ourRoot . $suffix] = $usRoot . $suffix;
    }
}

// -ise/-isation -> -ize/-ization (organise, realise, recognise, ...)
$iseRoots = [
    'organise', 'realise', 'recognise', 'authorise', 'customise', 'synchronise', 'optimise', 'apologise',
    'capitalise', 'categorise', 'characterise', 'civilise', 'colonise', 'criticise', 'emphasise', 'energise',
    'familiarise', 'finalise', 'generalise', 'globalise', 'harmonise', 'hypothesise', 'idealise', 'immunise',
    'initialise', 'itemise', 'legalise', 'localise', 'materialise', 'maximise', 'memorise', 'minimise',
    'mobilise', 'modernise', 'neutralise', 'normalise', 'patronise', 'personalise', 'prioritise', 'privatise',
    'publicise', 'randomise', 'rationalise', 'revitalise', 'satirise', 'serialise', 'socialise', 'specialise',
    'stabilise', 'standardise', 'summarise', 'symbolise', 'sympathise', 'tantalise', 'utilise', 'visualise',
    'vocalise', 'deserialise',
];
foreach ($iseRoots as $iseRoot) {
    $stem   = substr($iseRoot, 0, -3); // drop the trailing "ise"
    $ukBase = $stem . 'ise';
    $usBase = $stem . 'ize';
    foreach (['', 'd', 's', 'r', 'rs'] as $suffix) {
        $dictionary[$ukBase . $suffix] = $usBase . $suffix;
    }
    $dictionary[$stem . 'ising']    = $stem . 'izing';
    $dictionary[$stem . 'isation']  = $stem . 'ization';
    $dictionary[$stem . 'isations'] = $stem . 'izations';
}

// -yse/-ysing -> -yze/-yzing (analyse, paralyse, catalyse)
foreach (['analyse', 'paralyse', 'catalyse'] as $yseRoot) {
    $stem = substr($yseRoot, 0, -3); // drop the trailing "yse"
    foreach (['', 'd', 's', 'r', 'rs'] as $suffix) {
        $dictionary[$stem . 'yse' . $suffix] = $stem . 'yze' . $suffix;
    }
    $dictionary[$stem . 'ysing'] = $stem . 'yzing';
}

// -ogue -> -og, matching this project's own convention (Catalog, edit dialog)
foreach (['catalogue', 'dialogue', 'analogue'] as $ogueRoot) {
    $usRoot                                       = substr($ogueRoot, 0, -2); // drop the trailing "ue"
    $dictionary[$ogueRoot]                        = $usRoot;
    $dictionary[$ogueRoot . 's']                  = $usRoot . 's';
    $dictionary[$ogueRoot . 'd']                  = $usRoot . 'ed';
    $dictionary[substr($ogueRoot, 0, -1) . 'ing'] = $usRoot . 'ing';
}

// -re -> -er
$dictionary += [
    'centre' => 'center', 'centres' => 'centers', 'centred' => 'centered', 'centring' => 'centering',
    'metre' => 'meter', 'metres' => 'meters',
    'litre' => 'liter', 'litres' => 'liters',
    'theatre' => 'theater', 'theatres' => 'theaters',
    'fibre' => 'fiber', 'fibres' => 'fibers',
    'calibre' => 'caliber', 'calibres' => 'calibers',
    'lustre' => 'luster',
    'sombre' => 'somber',
    'spectre' => 'specter', 'spectres' => 'specters',
    'manoeuvre' => 'maneuver', 'manoeuvres' => 'maneuvers', 'manoeuvred' => 'maneuvered', 'manoeuvring' => 'maneuvering',
];

// irregular nouns/verbs and doubled-consonant inflections
$dictionary += [
    'licence' => 'license', 'licences' => 'licenses', 'licenced' => 'licensed', 'licencing' => 'licensing',
    'defence' => 'defense', 'defences' => 'defenses',
    'offence' => 'offense', 'offences' => 'offenses',
    'pretence' => 'pretense',
    'practise' => 'practice', 'practised' => 'practiced', 'practising' => 'practicing',
    'judgement' => 'judgment', 'judgements' => 'judgments',
    'acknowledgement' => 'acknowledgment', 'acknowledgements' => 'acknowledgments',
    'abridgement' => 'abridgment',
    'aluminium' => 'aluminum',
    'artefact' => 'artifact', 'artefacts' => 'artifacts',
    'grey' => 'gray', 'greyscale' => 'grayscale', 'greyed' => 'grayed',
    'cosy' => 'cozy', 'cosier' => 'cozier', 'cosiest' => 'coziest',
    'pyjamas' => 'pajamas',
    'tyre' => 'tire', 'tyres' => 'tires',
    'kerb' => 'curb', 'kerbs' => 'curbs',
    'draught' => 'draft', 'draughts' => 'drafts',
    'plough' => 'plow', 'ploughed' => 'plowed', 'ploughing' => 'plowing',
    'mould' => 'mold', 'moulds' => 'molds', 'moulded' => 'molded', 'moulding' => 'molding',
    'smoulder' => 'smolder', 'smouldering' => 'smoldering',
    'storey' => 'story', 'storeys' => 'stories',
    'sceptic' => 'skeptic', 'sceptics' => 'skeptics', 'sceptical' => 'skeptical', 'scepticism' => 'skepticism',
    'jewellery' => 'jewelry',
    'programme' => 'program', 'programmes' => 'programs',
    'woollen' => 'woolen',
    'travelled' => 'traveled', 'travelling' => 'traveling', 'traveller' => 'traveler', 'travellers' => 'travelers',
    'cancelled' => 'canceled', 'cancelling' => 'canceling',
    'modelled' => 'modeled', 'modelling' => 'modeling',
    'labelled' => 'labeled', 'labelling' => 'labeling', 'labeller' => 'labeler',
    'signalled' => 'signaled', 'signalling' => 'signaling',
    'levelled' => 'leveled', 'levelling' => 'leveling',
    'fuelled' => 'fueled', 'fuelling' => 'fueling',
    'marvelled' => 'marveled', 'marvelling' => 'marveling',
    'equalled' => 'equaled', 'equalling' => 'equaling',
    'fulfil' => 'fulfill', 'fulfils' => 'fulfills', 'fulfilment' => 'fulfillment',
    'enrol' => 'enroll', 'enrols' => 'enrolls', 'enrolment' => 'enrollment',
    'instalment' => 'installment', 'instalments' => 'installments',
    'skilful' => 'skillful', 'wilful' => 'willful',
    'whilst' => 'while',
    'amongst' => 'among',
    'learnt' => 'learned',
    'spelt' => 'spelled',
    'dreamt' => 'dreamed',
    'spoilt' => 'spoiled',
    'spilt' => 'spilled',
];

function matchCase(string $original, string $replacement): string
{
    if ($original === strtoupper($original)) {
        return strtoupper($replacement);
    }
    if ($original[0] === strtoupper($original[0])) {
        return ucfirst($replacement);
    }

    return $replacement;
}

echo "\033[1;34mChecking for non-US English spelling\033[0m\n";

$pattern = '/\b(' . implode('|', array_map(
    static fn (string $word): string => preg_quote($word, '/'),
    array_keys($dictionary)
)) . ')\b/i';

$directory = new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS);
$filter    = new RecursiveCallbackFilterIterator(
    $directory,
    static function (SplFileInfo $current) use ($skipDirs, $skipFiles): bool {
        if ($current->isDir()) {
            return !in_array($current->getFilename(), $skipDirs, true);
        }
        if (in_array($current->getFilename(), $skipFiles, true)) {
            return false;
        }

        $name = strtolower($current->getFilename());
        foreach (['.php', '.phtml', '.md'] as $extension) {
            // .dist templates (e.g. ampache.cfg.php.dist) carry their own prose comments too
            if (str_ends_with($name, $extension) || str_ends_with($name, $extension . '.dist')) {
                return true;
            }
        }

        return false;
    }
);

$findings = [];
foreach (new RecursiveIteratorIterator($filter) as $file) {
    /** @var SplFileInfo $file */
    $path  = $file->getPathname();
    $lines = @file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        continue;
    }

    foreach ($lines as $lineNumber => $line) {
        if (preg_match_all($pattern, $line, $matches, PREG_OFFSET_CAPTURE) === 0) {
            continue;
        }

        foreach ($matches[1] as [$word, $offset]) {
            $suggestion = $dictionary[strtolower($word)] ?? null;
            if ($suggestion === null) {
                continue;
            }

            $relative = ltrim(str_replace($root, '', $path), '/');
            if (isset($allowlist[$relative . ':' . ($lineNumber + 1)])) {
                continue;
            }

            $findings[] = sprintf(
                '%s:%d:%d: "%s" -> "%s"',
                $relative,
                $lineNumber + 1,
                $offset + 1,
                $word,
                matchCase($word, $suggestion)
            );
        }
    }
}

if ($findings !== []) {
    fwrite(STDERR, "\033[0;31mFound non-US spelling\033[0m\n");
    fwrite(STDERR, implode("\n", $findings) . "\n");
    exit(1);
}

echo "\033[1;32mSpelling is OK\033[0m\n";
exit(0);
