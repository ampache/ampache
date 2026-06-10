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
 */

namespace Ampache\Module\System\Update;

use Ampache\Module\System\Update\Migration\MigrationInterface;
use Ampache\Module\System\Update\Migration\V3\Migration360001;
use Ampache\Module\System\Update\Migration\V3\Migration360002;
use Ampache\Module\System\Update\Migration\V3\Migration360003;
use Ampache\Module\System\Update\Migration\V3\Migration360004;
use Ampache\Module\System\Update\Migration\V3\Migration360005;
use Ampache\Module\System\Update\Migration\V3\Migration360006;
use Ampache\Module\System\Update\Migration\V3\Migration360008;
use Ampache\Module\System\Update\Migration\V3\Migration360009;
use Ampache\Module\System\Update\Migration\V3\Migration360010;
use Ampache\Module\System\Update\Migration\V3\Migration360011;
use Ampache\Module\System\Update\Migration\V3\Migration360012;
use Ampache\Module\System\Update\Migration\V3\Migration360013;
use Ampache\Module\System\Update\Migration\V3\Migration360014;
use Ampache\Module\System\Update\Migration\V3\Migration360015;
use Ampache\Module\System\Update\Migration\V3\Migration360016;
use Ampache\Module\System\Update\Migration\V3\Migration360017;
use Ampache\Module\System\Update\Migration\V3\Migration360018;
use Ampache\Module\System\Update\Migration\V3\Migration360019;
use Ampache\Module\System\Update\Migration\V3\Migration360020;
use Ampache\Module\System\Update\Migration\V3\Migration360021;
use Ampache\Module\System\Update\Migration\V3\Migration360022;
use Ampache\Module\System\Update\Migration\V3\Migration360023;
use Ampache\Module\System\Update\Migration\V3\Migration360024;
use Ampache\Module\System\Update\Migration\V3\Migration360025;
use Ampache\Module\System\Update\Migration\V3\Migration360026;
use Ampache\Module\System\Update\Migration\V3\Migration360027;
use Ampache\Module\System\Update\Migration\V3\Migration360028;
use Ampache\Module\System\Update\Migration\V3\Migration360029;
use Ampache\Module\System\Update\Migration\V3\Migration360030;
use Ampache\Module\System\Update\Migration\V3\Migration360031;
use Ampache\Module\System\Update\Migration\V3\Migration360032;
use Ampache\Module\System\Update\Migration\V3\Migration360033;
use Ampache\Module\System\Update\Migration\V3\Migration360034;
use Ampache\Module\System\Update\Migration\V3\Migration360035;
use Ampache\Module\System\Update\Migration\V3\Migration360036;
use Ampache\Module\System\Update\Migration\V3\Migration360037;
use Ampache\Module\System\Update\Migration\V3\Migration360038;
use Ampache\Module\System\Update\Migration\V3\Migration360039;
use Ampache\Module\System\Update\Migration\V3\Migration360041;
use Ampache\Module\System\Update\Migration\V3\Migration360042;
use Ampache\Module\System\Update\Migration\V3\Migration360043;
use Ampache\Module\System\Update\Migration\V3\Migration360044;
use Ampache\Module\System\Update\Migration\V3\Migration360045;
use Ampache\Module\System\Update\Migration\V3\Migration360046;
use Ampache\Module\System\Update\Migration\V3\Migration360047;
use Ampache\Module\System\Update\Migration\V3\Migration360048;
use Ampache\Module\System\Update\Migration\V3\Migration360049;
use Ampache\Module\System\Update\Migration\V3\Migration360050;
use Ampache\Module\System\Update\Migration\V3\Migration370001;
use Ampache\Module\System\Update\Migration\V3\Migration370002;
use Ampache\Module\System\Update\Migration\V3\Migration370003;
use Ampache\Module\System\Update\Migration\V3\Migration370004;
use Ampache\Module\System\Update\Migration\V3\Migration370005;
use Ampache\Module\System\Update\Migration\V3\Migration370006;
use Ampache\Module\System\Update\Migration\V3\Migration370007;
use Ampache\Module\System\Update\Migration\V3\Migration370008;
use Ampache\Module\System\Update\Migration\V3\Migration370009;
use Ampache\Module\System\Update\Migration\V3\Migration370010;
use Ampache\Module\System\Update\Migration\V3\Migration370011;
use Ampache\Module\System\Update\Migration\V3\Migration370012;
use Ampache\Module\System\Update\Migration\V3\Migration370013;
use Ampache\Module\System\Update\Migration\V3\Migration370014;
use Ampache\Module\System\Update\Migration\V3\Migration370015;
use Ampache\Module\System\Update\Migration\V3\Migration370016;
use Ampache\Module\System\Update\Migration\V3\Migration370017;
use Ampache\Module\System\Update\Migration\V3\Migration370018;
use Ampache\Module\System\Update\Migration\V3\Migration370019;
use Ampache\Module\System\Update\Migration\V3\Migration370020;
use Ampache\Module\System\Update\Migration\V3\Migration370021;
use Ampache\Module\System\Update\Migration\V3\Migration370022;
use Ampache\Module\System\Update\Migration\V3\Migration370023;
use Ampache\Module\System\Update\Migration\V3\Migration370024;
use Ampache\Module\System\Update\Migration\V3\Migration370025;
use Ampache\Module\System\Update\Migration\V3\Migration370026;
use Ampache\Module\System\Update\Migration\V3\Migration370027;
use Ampache\Module\System\Update\Migration\V3\Migration370028;
use Ampache\Module\System\Update\Migration\V3\Migration370029;
use Ampache\Module\System\Update\Migration\V3\Migration370030;
use Ampache\Module\System\Update\Migration\V3\Migration370031;
use Ampache\Module\System\Update\Migration\V3\Migration370032;
use Ampache\Module\System\Update\Migration\V3\Migration370033;
use Ampache\Module\System\Update\Migration\V3\Migration370034;
use Ampache\Module\System\Update\Migration\V3\Migration370035;
use Ampache\Module\System\Update\Migration\V3\Migration370036;
use Ampache\Module\System\Update\Migration\V3\Migration370037;
use Ampache\Module\System\Update\Migration\V3\Migration370038;
use Ampache\Module\System\Update\Migration\V3\Migration370039;
use Ampache\Module\System\Update\Migration\V3\Migration370040;
use Ampache\Module\System\Update\Migration\V3\Migration370041;
use Ampache\Module\System\Update\Migration\V3\Migration380001;
use Ampache\Module\System\Update\Migration\V3\Migration380002;
use Ampache\Module\System\Update\Migration\V3\Migration380003;
use Ampache\Module\System\Update\Migration\V3\Migration380004;
use Ampache\Module\System\Update\Migration\V3\Migration380005;
use Ampache\Module\System\Update\Migration\V3\Migration380006;
use Ampache\Module\System\Update\Migration\V3\Migration380007;
use Ampache\Module\System\Update\Migration\V3\Migration380008;
use Ampache\Module\System\Update\Migration\V3\Migration380009;
use Ampache\Module\System\Update\Migration\V3\Migration380010;
use Ampache\Module\System\Update\Migration\V3\Migration380011;
use Ampache\Module\System\Update\Migration\V3\Migration380012;
use Ampache\Module\System\Update\Migration\V4\Migration400000;
use Ampache\Module\System\Update\Migration\V4\Migration400001;
use Ampache\Module\System\Update\Migration\V4\Migration400002;
use Ampache\Module\System\Update\Migration\V4\Migration400003;
use Ampache\Module\System\Update\Migration\V4\Migration400004;
use Ampache\Module\System\Update\Migration\V4\Migration400005;
use Ampache\Module\System\Update\Migration\V4\Migration400006;
use Ampache\Module\System\Update\Migration\V4\Migration400007;
use Ampache\Module\System\Update\Migration\V4\Migration400008;
use Ampache\Module\System\Update\Migration\V4\Migration400009;
use Ampache\Module\System\Update\Migration\V4\Migration400010;
use Ampache\Module\System\Update\Migration\V4\Migration400011;
use Ampache\Module\System\Update\Migration\V4\Migration400012;
use Ampache\Module\System\Update\Migration\V4\Migration400013;
use Ampache\Module\System\Update\Migration\V4\Migration400014;
use Ampache\Module\System\Update\Migration\V4\Migration400015;
use Ampache\Module\System\Update\Migration\V4\Migration400016;
use Ampache\Module\System\Update\Migration\V4\Migration400018;
use Ampache\Module\System\Update\Migration\V4\Migration400019;
use Ampache\Module\System\Update\Migration\V4\Migration400020;
use Ampache\Module\System\Update\Migration\V4\Migration400021;
use Ampache\Module\System\Update\Migration\V4\Migration400022;
use Ampache\Module\System\Update\Migration\V4\Migration400023;
use Ampache\Module\System\Update\Migration\V4\Migration400024;
use Ampache\Module\System\Update\Migration\V5\Migration500000;
use Ampache\Module\System\Update\Migration\V5\Migration500001;
use Ampache\Module\System\Update\Migration\V5\Migration500002;
use Ampache\Module\System\Update\Migration\V5\Migration500003;
use Ampache\Module\System\Update\Migration\V5\Migration500004;
use Ampache\Module\System\Update\Migration\V5\Migration500005;
use Ampache\Module\System\Update\Migration\V5\Migration500006;
use Ampache\Module\System\Update\Migration\V5\Migration500007;
use Ampache\Module\System\Update\Migration\V5\Migration500008;
use Ampache\Module\System\Update\Migration\V5\Migration500009;
use Ampache\Module\System\Update\Migration\V5\Migration500010;
use Ampache\Module\System\Update\Migration\V5\Migration500011;
use Ampache\Module\System\Update\Migration\V5\Migration500012;
use Ampache\Module\System\Update\Migration\V5\Migration500013;
use Ampache\Module\System\Update\Migration\V5\Migration500014;
use Ampache\Module\System\Update\Migration\V5\Migration500015;
use Ampache\Module\System\Update\Migration\V5\Migration510000;
use Ampache\Module\System\Update\Migration\V5\Migration510001;
use Ampache\Module\System\Update\Migration\V5\Migration510003;
use Ampache\Module\System\Update\Migration\V5\Migration510004;
use Ampache\Module\System\Update\Migration\V5\Migration510005;
use Ampache\Module\System\Update\Migration\V5\Migration520000;
use Ampache\Module\System\Update\Migration\V5\Migration520001;
use Ampache\Module\System\Update\Migration\V5\Migration520002;
use Ampache\Module\System\Update\Migration\V5\Migration520003;
use Ampache\Module\System\Update\Migration\V5\Migration520004;
use Ampache\Module\System\Update\Migration\V5\Migration520005;
use Ampache\Module\System\Update\Migration\V5\Migration530000;
use Ampache\Module\System\Update\Migration\V5\Migration530001;
use Ampache\Module\System\Update\Migration\V5\Migration530002;
use Ampache\Module\System\Update\Migration\V5\Migration530003;
use Ampache\Module\System\Update\Migration\V5\Migration530004;
use Ampache\Module\System\Update\Migration\V5\Migration530005;
use Ampache\Module\System\Update\Migration\V5\Migration530006;
use Ampache\Module\System\Update\Migration\V5\Migration530007;
use Ampache\Module\System\Update\Migration\V5\Migration530008;
use Ampache\Module\System\Update\Migration\V5\Migration530009;
use Ampache\Module\System\Update\Migration\V5\Migration530010;
use Ampache\Module\System\Update\Migration\V5\Migration530011;
use Ampache\Module\System\Update\Migration\V5\Migration530012;
use Ampache\Module\System\Update\Migration\V5\Migration530013;
use Ampache\Module\System\Update\Migration\V5\Migration530014;
use Ampache\Module\System\Update\Migration\V5\Migration530015;
use Ampache\Module\System\Update\Migration\V5\Migration530016;
use Ampache\Module\System\Update\Migration\V5\Migration540000;
use Ampache\Module\System\Update\Migration\V5\Migration540001;
use Ampache\Module\System\Update\Migration\V5\Migration540002;
use Ampache\Module\System\Update\Migration\V5\Migration550001;
use Ampache\Module\System\Update\Migration\V5\Migration550002;
use Ampache\Module\System\Update\Migration\V5\Migration550003;
use Ampache\Module\System\Update\Migration\V5\Migration550004;
use Ampache\Module\System\Update\Migration\V5\Migration550005;
use Ampache\Module\System\Update\Migration\V6\Migration600001;
use Ampache\Module\System\Update\Migration\V6\Migration600002;
use Ampache\Module\System\Update\Migration\V6\Migration600003;
use Ampache\Module\System\Update\Migration\V6\Migration600004;
use Ampache\Module\System\Update\Migration\V6\Migration600005;
use Ampache\Module\System\Update\Migration\V6\Migration600006;
use Ampache\Module\System\Update\Migration\V6\Migration600007;
use Ampache\Module\System\Update\Migration\V6\Migration600008;
use Ampache\Module\System\Update\Migration\V6\Migration600009;
use Ampache\Module\System\Update\Migration\V6\Migration600010;
use Ampache\Module\System\Update\Migration\V6\Migration600011;
use Ampache\Module\System\Update\Migration\V6\Migration600012;
use Ampache\Module\System\Update\Migration\V6\Migration600013;
use Ampache\Module\System\Update\Migration\V6\Migration600014;
use Ampache\Module\System\Update\Migration\V6\Migration600015;
use Ampache\Module\System\Update\Migration\V6\Migration600016;
use Ampache\Module\System\Update\Migration\V6\Migration600018;
use Ampache\Module\System\Update\Migration\V6\Migration600019;
use Ampache\Module\System\Update\Migration\V6\Migration600020;
use Ampache\Module\System\Update\Migration\V6\Migration600021;
use Ampache\Module\System\Update\Migration\V6\Migration600022;
use Ampache\Module\System\Update\Migration\V6\Migration600023;
use Ampache\Module\System\Update\Migration\V6\Migration600024;
use Ampache\Module\System\Update\Migration\V6\Migration600025;
use Ampache\Module\System\Update\Migration\V6\Migration600026;
use Ampache\Module\System\Update\Migration\V6\Migration600027;
use Ampache\Module\System\Update\Migration\V6\Migration600028;
use Ampache\Module\System\Update\Migration\V6\Migration600032;
use Ampache\Module\System\Update\Migration\V6\Migration600033;
use Ampache\Module\System\Update\Migration\V6\Migration600034;
use Ampache\Module\System\Update\Migration\V6\Migration600035;
use Ampache\Module\System\Update\Migration\V6\Migration600036;
use Ampache\Module\System\Update\Migration\V6\Migration600037;
use Ampache\Module\System\Update\Migration\V6\Migration600038;
use Ampache\Module\System\Update\Migration\V6\Migration600039;
use Ampache\Module\System\Update\Migration\V6\Migration600040;
use Ampache\Module\System\Update\Migration\V6\Migration600041;
use Ampache\Module\System\Update\Migration\V6\Migration600042;
use Ampache\Module\System\Update\Migration\V6\Migration600043;
use Ampache\Module\System\Update\Migration\V6\Migration600044;
use Ampache\Module\System\Update\Migration\V6\Migration600045;
use Ampache\Module\System\Update\Migration\V6\Migration600046;
use Ampache\Module\System\Update\Migration\V6\Migration600047;
use Ampache\Module\System\Update\Migration\V6\Migration600048;
use Ampache\Module\System\Update\Migration\V6\Migration600049;
use Ampache\Module\System\Update\Migration\V6\Migration600050;
use Ampache\Module\System\Update\Migration\V6\Migration600051;
use Ampache\Module\System\Update\Migration\V6\Migration600052;
use Ampache\Module\System\Update\Migration\V6\Migration600053;
use Ampache\Module\System\Update\Migration\V6\Migration600054;
use Ampache\Module\System\Update\Migration\V6\Migration600055;
use Ampache\Module\System\Update\Migration\V6\Migration600056;
use Ampache\Module\System\Update\Migration\V6\Migration600057;
use Ampache\Module\System\Update\Migration\V6\Migration600058;
use Ampache\Module\System\Update\Migration\V6\Migration600059;
use Ampache\Module\System\Update\Migration\V6\Migration600060;
use Ampache\Module\System\Update\Migration\V6\Migration600061;
use Ampache\Module\System\Update\Migration\V6\Migration600062;
use Ampache\Module\System\Update\Migration\V6\Migration600063;
use Ampache\Module\System\Update\Migration\V6\Migration600064;
use Ampache\Module\System\Update\Migration\V6\Migration600065;
use Ampache\Module\System\Update\Migration\V6\Migration600066;
use Ampache\Module\System\Update\Migration\V6\Migration600067;
use Ampache\Module\System\Update\Migration\V6\Migration600068;
use Ampache\Module\System\Update\Migration\V6\Migration600069;
use Ampache\Module\System\Update\Migration\V6\Migration600070;
use Ampache\Module\System\Update\Migration\V6\Migration600071;
use Ampache\Module\System\Update\Migration\V7\Migration700001;
use Ampache\Module\System\Update\Migration\V7\Migration700002;
use Ampache\Module\System\Update\Migration\V7\Migration700003;
use Ampache\Module\System\Update\Migration\V7\Migration700004;
use Ampache\Module\System\Update\Migration\V7\Migration700005;
use Ampache\Module\System\Update\Migration\V7\Migration700007;
use Ampache\Module\System\Update\Migration\V7\Migration700008;
use Ampache\Module\System\Update\Migration\V7\Migration700009;
use Ampache\Module\System\Update\Migration\V7\Migration700010;
use Ampache\Module\System\Update\Migration\V7\Migration700011;
use Ampache\Module\System\Update\Migration\V7\Migration700012;
use Ampache\Module\System\Update\Migration\V7\Migration700013;
use Ampache\Module\System\Update\Migration\V7\Migration700014;
use Ampache\Module\System\Update\Migration\V7\Migration700015;
use Ampache\Module\System\Update\Migration\V7\Migration700016;
use Ampache\Module\System\Update\Migration\V7\Migration700018;
use Ampache\Module\System\Update\Migration\V7\Migration700019;
use Ampache\Module\System\Update\Migration\V7\Migration700020;
use Ampache\Module\System\Update\Migration\V7\Migration700021;
use Ampache\Module\System\Update\Migration\V7\Migration700022;
use Ampache\Module\System\Update\Migration\V7\Migration700023;
use Ampache\Module\System\Update\Migration\V7\Migration700024;
use Ampache\Module\System\Update\Migration\V7\Migration700025;
use Ampache\Module\System\Update\Migration\V7\Migration700026;
use Ampache\Module\System\Update\Migration\V7\Migration700027;
use Ampache\Module\System\Update\Migration\V7\Migration700028;
use Ampache\Module\System\Update\Migration\V7\Migration700029;
use Ampache\Module\System\Update\Migration\V7\Migration701001;
use Ampache\Module\System\Update\Migration\V7\Migration701002;
use Ampache\Module\System\Update\Migration\V7\Migration702001;
use Ampache\Module\System\Update\Migration\V7\Migration702002;
use Ampache\Module\System\Update\Migration\V7\Migration710001;
use Ampache\Module\System\Update\Migration\V7\Migration710002;
use Ampache\Module\System\Update\Migration\V7\Migration710003;
use Ampache\Module\System\Update\Migration\V7\Migration710004;
use Ampache\Module\System\Update\Migration\V7\Migration710005;
use Ampache\Module\System\Update\Migration\V7\Migration710006;
use Ampache\Module\System\Update\Migration\V7\Migration720001;
use Ampache\Module\System\Update\Migration\V7\Migration721001;
use Ampache\Module\System\Update\Migration\V7\Migration740001;
use Ampache\Module\System\Update\Migration\V7\Migration750001;
use Ampache\Module\System\Update\Migration\V7\Migration750002;
use Ampache\Module\System\Update\Migration\V7\Migration750003;
use Ampache\Module\System\Update\Migration\V7\Migration750004;
use Ampache\Module\System\Update\Migration\V7\Migration750006;
use Ampache\Module\System\Update\Migration\V7\Migration750007;
use Ampache\Module\System\Update\Migration\V7\Migration750008;
use Ampache\Module\System\Update\Migration\V7\Migration750009;
use Ampache\Module\System\Update\Migration\V7\Migration750010;
use Ampache\Module\System\Update\Migration\V7\Migration751001;
use Ampache\Module\System\Update\Migration\V7\Migration752001;
use Ampache\Module\System\Update\Migration\V7\Migration760001;
use Ampache\Module\System\Update\Migration\V7\Migration770001;
use Ampache\Module\System\Update\Migration\V7\Migration773001;
use Ampache\Module\System\Update\Migration\V7\Migration780001;
use Ampache\Module\System\Update\Migration\V7\Migration780003;
use Ampache\Module\System\Update\Migration\V7\Migration780004;
use Ampache\Module\System\Update\Migration\V7\Migration790001;
use Ampache\Module\System\Update\Migration\V7\Migration793001;
use Ampache\Module\System\Update\Migration\V7\Migration794001;
use Ampache\Module\System\Update\Migration\V7\Migration794002;
use Ampache\Module\System\Update\Migration\V7\Migration794004;
use Ampache\Module\System\Update\Migration\V8\Migration800000;
use Ampache\Module\System\Update\Migration\V8\Migration800001;
use Ampache\Module\System\Update\Migration\V8\Migration800002;
use Ampache\Module\System\Update\Migration\V8\Migration800003;
use Generator;

/**
 * Defines all available versions
 */
final class Versions
{
    public const int MAXIMUM_UPDATABLE_VERSION = 800003; // AMPACHE_VERSION (db_version)

    /** @var array<int, class-string<MigrationInterface>> List of available migrations */
    private static array $versions = [
        360001 => Migration360001::class,
        360002 => Migration360002::class,
        360003 => Migration360003::class,
        360004 => Migration360004::class,
        360005 => Migration360005::class,
        360006 => Migration360006::class,
        360008 => Migration360008::class,
        360009 => Migration360009::class,
        360010 => Migration360010::class,
        360011 => Migration360011::class,
        360012 => Migration360012::class,
        360013 => Migration360013::class,
        360014 => Migration360014::class,
        360015 => Migration360015::class,
        360016 => Migration360016::class,
        360017 => Migration360017::class,
        360018 => Migration360018::class,
        360019 => Migration360019::class,
        360020 => Migration360020::class,
        360021 => Migration360021::class,
        360022 => Migration360022::class,
        360023 => Migration360023::class,
        360024 => Migration360024::class,
        360025 => Migration360025::class,
        360026 => Migration360026::class,
        360027 => Migration360027::class,
        360028 => Migration360028::class,
        360029 => Migration360029::class,
        360030 => Migration360030::class,
        360031 => Migration360031::class,
        360032 => Migration360032::class,
        360033 => Migration360033::class,
        360034 => Migration360034::class,
        360035 => Migration360035::class,
        360036 => Migration360036::class,
        360037 => Migration360037::class,
        360038 => Migration360038::class,
        360039 => Migration360039::class,
        360041 => Migration360041::class,
        360042 => Migration360042::class,
        360043 => Migration360043::class,
        360044 => Migration360044::class,
        360045 => Migration360045::class,
        360046 => Migration360046::class,
        360047 => Migration360047::class,
        360048 => Migration360048::class,
        360049 => Migration360049::class,
        360050 => Migration360050::class,
        370001 => Migration370001::class,
        370002 => Migration370002::class,
        370003 => Migration370003::class,
        370004 => Migration370004::class,
        370005 => Migration370005::class,
        370006 => Migration370006::class,
        370007 => Migration370007::class,
        370008 => Migration370008::class,
        370009 => Migration370009::class,
        370010 => Migration370010::class,
        370011 => Migration370011::class,
        370012 => Migration370012::class,
        370013 => Migration370013::class,
        370014 => Migration370014::class,
        370015 => Migration370015::class,
        370016 => Migration370016::class,
        370017 => Migration370017::class,
        370018 => Migration370018::class,
        370019 => Migration370019::class,
        370020 => Migration370020::class,
        370021 => Migration370021::class,
        370022 => Migration370022::class,
        370023 => Migration370023::class,
        370024 => Migration370024::class,
        370025 => Migration370025::class,
        370026 => Migration370026::class,
        370027 => Migration370027::class,
        370028 => Migration370028::class,
        370029 => Migration370029::class,
        370030 => Migration370030::class,
        370031 => Migration370031::class,
        370032 => Migration370032::class,
        370033 => Migration370033::class,
        370034 => Migration370034::class,
        370035 => Migration370035::class,
        370036 => Migration370036::class,
        370037 => Migration370037::class,
        370038 => Migration370038::class,
        370039 => Migration370039::class,
        370040 => Migration370040::class,
        370041 => Migration370041::class,
        380001 => Migration380001::class,
        380002 => Migration380002::class,
        380003 => Migration380003::class,
        380004 => Migration380004::class,
        380005 => Migration380005::class,
        380006 => Migration380006::class,
        380007 => Migration380007::class,
        380008 => Migration380008::class,
        380009 => Migration380009::class,
        380010 => Migration380010::class,
        380011 => Migration380011::class,
        380012 => Migration380012::class,
        400000 => Migration400000::class,
        400001 => Migration400001::class,
        400002 => Migration400002::class,
        400003 => Migration400003::class,
        400004 => Migration400004::class,
        400005 => Migration400005::class,
        400006 => Migration400006::class,
        400007 => Migration400007::class,
        400008 => Migration400008::class,
        400009 => Migration400009::class,
        400010 => Migration400010::class,
        400011 => Migration400011::class,
        400012 => Migration400012::class,
        400013 => Migration400013::class,
        400014 => Migration400014::class,
        400015 => Migration400015::class,
        400016 => Migration400016::class,
        400018 => Migration400018::class,
        400019 => Migration400019::class,
        400020 => Migration400020::class,
        400021 => Migration400021::class,
        400022 => Migration400022::class,
        400023 => Migration400023::class,
        400024 => Migration400024::class,
        500000 => Migration500000::class,
        500001 => Migration500001::class,
        500002 => Migration500002::class,
        500003 => Migration500003::class,
        500004 => Migration500004::class,
        500005 => Migration500005::class,
        500006 => Migration500006::class,
        500007 => Migration500007::class,
        500008 => Migration500008::class,
        500009 => Migration500009::class,
        500010 => Migration500010::class,
        500011 => Migration500011::class,
        500012 => Migration500012::class,
        500013 => Migration500013::class,
        500014 => Migration500014::class,
        500015 => Migration500015::class,
        510000 => Migration510000::class,
        510001 => Migration510001::class,
        510003 => Migration510003::class,
        510004 => Migration510004::class,
        510005 => Migration510005::class,
        520000 => Migration520000::class,
        520001 => Migration520001::class,
        520002 => Migration520002::class,
        520003 => Migration520003::class,
        520004 => Migration520004::class,
        520005 => Migration520005::class,
        530000 => Migration530000::class,
        530001 => Migration530001::class,
        530002 => Migration530002::class,
        530003 => Migration530003::class,
        530004 => Migration530004::class,
        530005 => Migration530005::class,
        530006 => Migration530006::class,
        530007 => Migration530007::class,
        530008 => Migration530008::class,
        530009 => Migration530009::class,
        530010 => Migration530010::class,
        530011 => Migration530011::class,
        530012 => Migration530012::class,
        530013 => Migration530013::class,
        530014 => Migration530014::class,
        530015 => Migration530015::class,
        530016 => Migration530016::class,
        540000 => Migration540000::class,
        540001 => Migration540001::class,
        540002 => Migration540002::class,
        550001 => Migration550001::class,
        550002 => Migration550002::class,
        550003 => Migration550003::class,
        550004 => Migration550004::class,
        550005 => Migration550005::class,
        600001 => Migration600001::class,
        600002 => Migration600002::class,
        600003 => Migration600003::class,
        600004 => Migration600004::class,
        600005 => Migration600005::class,
        600006 => Migration600006::class,
        600007 => Migration600007::class,
        600008 => Migration600008::class,
        600009 => Migration600009::class,
        600010 => Migration600010::class,
        600011 => Migration600011::class,
        600012 => Migration600012::class,
        600013 => Migration600013::class,
        600014 => Migration600014::class,
        600015 => Migration600015::class,
        600016 => Migration600016::class,
        600018 => Migration600018::class,
        600019 => Migration600019::class,
        600020 => Migration600020::class,
        600021 => Migration600021::class,
        600022 => Migration600022::class,
        600023 => Migration600023::class,
        600024 => Migration600024::class,
        600025 => Migration600025::class,
        600026 => Migration600026::class,
        600027 => Migration600027::class,
        600028 => Migration600028::class,
        600032 => Migration600032::class,
        600033 => Migration600033::class,
        600034 => Migration600034::class,
        600035 => Migration600035::class,
        600036 => Migration600036::class,
        600037 => Migration600037::class,
        600038 => Migration600038::class,
        600039 => Migration600039::class,
        600040 => Migration600040::class,
        600041 => Migration600041::class,
        600042 => Migration600042::class,
        600043 => Migration600043::class,
        600044 => Migration600044::class,
        600045 => Migration600045::class,
        600046 => Migration600046::class,
        600047 => Migration600047::class,
        600048 => Migration600048::class,
        600049 => Migration600049::class,
        600050 => Migration600050::class,
        600051 => Migration600051::class,
        600052 => Migration600052::class,
        600053 => Migration600053::class,
        600054 => Migration600054::class,
        600055 => Migration600055::class,
        600056 => Migration600056::class,
        600057 => Migration600057::class,
        600058 => Migration600058::class,
        600059 => Migration600059::class,
        600060 => Migration600060::class,
        600061 => Migration600061::class,
        600062 => Migration600062::class,
        600063 => Migration600063::class,
        600064 => Migration600064::class,
        600065 => Migration600065::class,
        600066 => Migration600066::class,
        600067 => Migration600067::class,
        600068 => Migration600068::class,
        600069 => Migration600069::class,
        600070 => Migration600070::class,
        600071 => Migration600071::class,
        700001 => Migration700001::class,
        700002 => Migration700002::class,
        700003 => Migration700003::class,
        700004 => Migration700004::class,
        700005 => Migration700005::class,
        700007 => Migration700007::class,
        700008 => Migration700008::class,
        700009 => Migration700009::class,
        700010 => Migration700010::class,
        700011 => Migration700011::class,
        700012 => Migration700012::class,
        700013 => Migration700013::class,
        700014 => Migration700014::class,
        700015 => Migration700015::class,
        700016 => Migration700016::class,
        700018 => Migration700018::class,
        700019 => Migration700019::class,
        700020 => Migration700020::class,
        700021 => Migration700021::class,
        700022 => Migration700022::class,
        700023 => Migration700023::class,
        700024 => Migration700024::class,
        700025 => Migration700025::class,
        700026 => Migration700026::class,
        700027 => Migration700027::class,
        700028 => Migration700028::class,
        700029 => Migration700029::class,
        701001 => Migration701001::class,
        701002 => Migration701002::class,
        702001 => Migration702001::class,
        702002 => Migration702002::class,
        710001 => Migration710001::class,
        710002 => Migration710002::class,
        710003 => Migration710003::class,
        710004 => Migration710004::class,
        710005 => Migration710005::class,
        710006 => Migration710006::class,
        720001 => Migration720001::class,
        721001 => Migration721001::class,
        740001 => Migration740001::class,
        750001 => Migration750001::class,
        750002 => Migration750002::class,
        750003 => Migration750003::class,
        750004 => Migration750004::class,
        750006 => Migration750006::class,
        750007 => Migration750007::class,
        750008 => Migration750008::class,
        750009 => Migration750009::class,
        750010 => Migration750010::class,
        751001 => Migration751001::class,
        752001 => Migration752001::class,
        760001 => Migration760001::class,
        770001 => Migration770001::class,
        773001 => Migration773001::class,
        780001 => Migration780001::class,
        780003 => Migration780003::class,
        780004 => Migration780004::class,
        790001 => Migration790001::class,
        793001 => Migration793001::class,
        794001 => Migration794001::class,
        794002 => Migration794002::class,
        794004 => Migration794004::class,
        800000 => Migration800000::class,
        800001 => Migration800001::class,
        800002 => Migration800002::class,
        800003 => Migration800003::class,
    ];

    /**
     * Yields all migration having a more recent version than the given one
     *
     * @return Generator<int, class-string<MigrationInterface>>
     */
    public static function getPendingMigrations(int $currentVersion): Generator
    {
        foreach (self::$versions as $version => $migrationClass) {
            if ($version > $currentVersion) {
                yield $version => $migrationClass;
            }
        }
    }
}
