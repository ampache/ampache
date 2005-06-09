#!/usr/bin/perl -w
#
# Exports playlists from ampache
#
# Fill in the site specific database connection parameters below before running
#

use DBI;

# Configure database connection parameters
my $db = "ampache";	# database
my $user = "";		# database user
my $pw = "";		# database user password


if ($#ARGV < 0) {
	print "Usage: $0 <filename>\n";
	print "  Exports Ampache playlists to <filename>.\n";
	exit;
}


open(OUT, "> $ARGV[0]") or die("Could not open '$ARGV[0]' for write - $!");

# Build DSNs
my $dsn = "dbi:mysql:database=$db;";

# Connect to database
my $dbh= DBI->connect($dsn, $user, $pw,
		{ RaiseError => 1, AutoCommit => 0 });


# Prepare statements
my $sth = $dbh->prepare("SELECT id, name, owner, type FROM playlist");
my $sth2 = $dbh->prepare("SELECT username FROM user
			WHERE id = ?");
my $sth3 = $dbh->prepare("SELECT song.file
			FROM playlist_data, song
			WHERE playlist_data.playlist = ?
			AND playlist_data.song = song.id");

# Execute select and loop through results
$sth->execute();
my $count = 0;
my ($id,$name,$owner,$type,$date,$file,$track);
while(($id,$name,$owner,$type) = $sth->fetchrow_array) {
	if ($count > 0) {
		# Use a blank line as a separator between playlists
		print OUT "\n";
	}

	$count++;

	if ($owner =~ /^\d+$/) {
		# Fetch username instead of id for owner
		$sth2->execute($owner);
		$owner = "unknown" unless (($owner) = $sth2->fetchrow_array);
		$sth2->finish;
	}

	# Date is not present in old ampache's
	$date = 0 if (! defined($date));

	print OUT "Name: $name\n";
	print OUT "Owner: $owner\n";
	print OUT "Type: $type\n";

	# Grab songs for this playlist
	$sth3->execute($id);
	while(($file) = $sth3->fetchrow_array) {
		print OUT "File: $file\n";
	}
}

print "Exported $count playlists.\n";

# Clean up
$dbh->disconnect;
close(OUT);

