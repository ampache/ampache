#!/usr/bin/perl -w
#
# Migrates users from Ampache v3.0 to Ampache v3.1.
#
# Fill in the site specific database connection parameters below before running
#

use DBI;

# Configure database connection parameters
my $db_old = "ampache";		# old database
my $db_new = "ampache3_1";	# new database
my $user_old = "";		# old database user
my $user_new = "";		# new database user
my $pw_old = "!";		# old database user password
my $pw_new = "!";		# new database user password


# Build DSNs
my $dsn_new = "dbi:mysql:database=$db_new;";
my $dsn_old = "dbi:mysql:database=$db_old;";


# Connect to old and new databases
my $dbh_new = DBI->connect($dsn_new, $user_new, $pw_new,
		{ RaiseError => 1, AutoCommit => 0 });

my $dbh_old = DBI->connect($dsn_old, $user_old, $pw_old,
		{ RaiseError => 1, AutoCommit => 0 });


# Prepare select and insert statements
my $sth = $dbh_old->prepare("SELECT username, fullname, email, password, access
				FROM user");

my $sth_update = $dbh_new->prepare("INSERT INTO user
				(username, fullname, email, password, access, offset_limit)
				VALUES (?, ?, ?, ?, ?, 50)");


# Execute select and loop through results
$sth->execute();
my ($f1,$f2,$f3,$f4,$f5);
my $count = 0;
while(($f1,$f2,$f3,$f4,$f5) = $sth->fetchrow_array) {
	$sth_update->execute($f1,$f2,$f3,$f4,$f5);
	$count++;
}

print "Migrated $count users.\n";


# Clean up
$dbh_old->disconnect;
$dbh_new->disconnect;

