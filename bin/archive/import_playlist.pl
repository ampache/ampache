#!/usr/bin/perl -w
#
# Imports playlists into ampache (from export_playlist.pl output)
#
# Fill in the site specific database connection parameters below before running
#

use DBI;
use Data::Dumper;

# Configure database connection parameters
my $db = "ampache3_1";	# database
my $user = "";		# database user
my $pw = "";		# database user password


if ($#ARGV < 0) {
	print "Usage: $0 <filename>\n";
	print "  Imports Ampache playlists from <filename>.\n";
	print "  The format of <filename> should match the output of export_playlist.pl.\n";
	exit;
}


open(IN, "$ARGV[0]") or die("Could not open '$ARGV[0]' for read - $!");

# Build DSNs
my $dsn = "dbi:mysql:database=$db;";

# Connect to database
my $dbh = DBI->connect($dsn, $user, $pw,
		{ RaiseError => 1, AutoCommit => 0 });


# Structure to contain playlists
my @playlists;

# Parse file
my $i = 0;
while($line = <IN>) {
	chomp $line;

	if ($line eq "") {
		# Blank line means new playlist
		$i++;
		next;
	}

	if ($line =~ /^ID: (.*)$/) {
		$playlists[$i]->{id} = $1;
	}

	if ($line =~ /^Name: (.*)$/) {
		$playlists[$i]->{name} = $1;
	}

	if ($line =~ /^Owner: (.*)$/) {
		$playlists[$i]->{owner} = $1;
	}

	if ($line =~ /^Type: (.*)$/) {
		$playlists[$i]->{type} = $1;
	}

	if ($line =~ /^File: (.*)$/) {
		push @{$playlists[$i]->{files}}, $1;
	}
}
close(IN);

# Prepare statements
my $sth = $dbh->prepare("SELECT id FROM user
			WHERE username = ?");
my $sth2 = $dbh->prepare("INSERT INTO playlist
			(name, owner, type)
			values (?, ?, ?)");
my $sth3 = $dbh->prepare("SELECT id FROM song
			WHERE file = ?");
my $sth4 = $dbh->prepare("INSERT INTO playlist_data
			(playlist, song, track)
			values (?, ?, ?)");

# Insert records into Ampache
my ($id,$name,$owner,$type,$file,$songid);
my $count = 0;
for ($i = 0; $i < $#playlists + 1; $i++) {
	$count++;

	$name = $playlists[$i]->{name};

	$sth->execute($playlists[$i]->{owner});
	$owner = 0 unless (($owner) = $sth->fetchrow_array);
	$sth->finish;

	$type = $playlists[$i]->{type};

	print "Importing playlist '$name'...\n";

	# Create base playlist entry
	$sth2->execute($name, $owner, $type);
	$id = $dbh->{mysql_insertid};

	# And add files to it
	while($file = pop(@{$playlists[$i]->{files}})) {
		$sth3->execute($file);
		next unless (($songid) = $sth3->fetchrow_array);
		$sth3->finish;

		$sth4->execute($id,$songid,0);
	}
}

print "Imported $count playlists.\n";

# Clean up
$dbh->disconnect;
close(IN);

