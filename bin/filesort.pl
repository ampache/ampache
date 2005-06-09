#!/usr/bin/perl -w
#
# Sorts your MP3s into directories based on the sort pattern specified 
# in ampache

use FindBind qw($Bin);
require "$Bin/init";

use Data::Dumper;
use Getopt::Long;

Getopt::Long::Configure('bundling','no_ignore_case');
GetOptions
	("h|help"	=> \$usage,
	 "t|test"	=> \$pretend,
	 "a|all"	=> \$all,
	 "s|sort"	=> \$sort,
	 "c|clean"	=> \$clean,
	 "v|verbose"	=> \$verbose);

if ($help) {
	usage();
}


#
# Pull in Data from Ampache
#

