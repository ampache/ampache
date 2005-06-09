#!/usr/bin/perl -w 

# Find and file away MP3's.  Run multiple times and will
#  ignore addition of duplicates in db (based on MD5 hash
#  of full file path.

package Local::Ampache;
#use File::Find;
use DBI;
#use strict;
use Data::Dumper;
use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %ampache);
require Exporter;

@ISA = qw(Exporter AutoLoader);
@EXPORT = qw(

);

my $TRUE = 1;
my $FALSE = 0;
$VERSION = '';


my %ampache = ();


sub new {
	my ($class, $path) = @_;


	open(CONFIG, "< $path/config/ampache.cfg")
        	or die "Could not find $path/config/ampache.cfg.  Is it readable by me?\n";

        my %config = ();

        while (<CONFIG>) {
                next if ($_ =~ /^#.*/); 

                if ( $_ =~ /(.*?)\s+=\s+(.*)/ ) {
                        $config{$1} = $2;
                }
        }

	my $name = $config{'local_db'};

        my $self = 
                {
                        _name         => $config{'local_db'},
                        _database     => $config{'local_db'},
                        _sth_cache    => {},
                        _connect      => {
                                dbd      => 'mysql',
                                host     => $config{'local_host'},
                                port     => '3306',
                                username => $config{'local_username'},
                                password => $config{'local_pass'} 
                        },
                _dbh       => '',
                _path   => $path,
                _config => \%config,
                _debug  => $FALSE
        };

        $VERSION = $config{'VERSION'};

	$Local::Ampache::ampache{$name} = bless ($self, $class);
	
	$self->{_dbh} = $self->dbh( $name );
	
	return $self;

} # End New Ampache Module

sub DESTROY {
	my ($self) = @_;
   
	foreach my $sth (values %{$self->{_sth_cache}}) {
		if (defined($sth)) { $sth->finish(); } 
	}
	
	if (defined($self->{_dbh}) and $self->{_dbh} ne "") {
		$self->{_dbh}->disconnect();
	}
}

sub get
{
    my ($class, $name) = @_;
    
    if (not $Local::Ampache::ampache{$name}) {
        $Local::Ampache::ampache{$name} = Local::Ampache->new($name);
    }
    return bless $Local::Ampache::ampache{$name}, $class;
}

sub dbh
{
    my ($self, $database) = @_;
    my $dbh = '';

    if($self->{_dbh} ) 
    {
        return $self->{_dbh};
    }
    else
    {
        my $connect_string = [ sprintf("dbi:%s:database=%s;host=%s;port=%s",
                                $self->{_connect}{dbd},
                                $self->{_database},
                                $self->{_connect}{host},
                                $self->{_connect}{port}),
                                $self->{_connect}{username},
                                $self->{_connect}{password} ];
        $dbh = DBI->connect( @{$connect_string}, 
                              {PrintError => 0,
                              RaiseError => 0,
                              AutoCommit => 1});

        if ( !$dbh ) 
        {
            die "Failed to connect to database.  Exiting.";
        }
    }

    return $dbh;
}

sub prepare_sth_cache {
        my ($self, $sql) = @_;

        # the call to dbh() forces a connection if one has dropped
        my $dbh = $self->dbh();
        return $dbh->prepare($sql);
}

sub get_table_where
{
    my ($self, $name, $where,$select) = @_;
    if (!$select) { $select = "*"; } 
    my ($sql, $sth);
    my $dbh = $self->dbh();
    $sql = qq{SELECT $select FROM $name $where};
    $sth = $dbh->prepare($sql);
    $sth->execute();

    my @table = ();
    while ( my $ary = $sth->fetchrow_hashref() ) 
    {
        push(@table, $ary); 
    }
    return (@table);
}

sub get_catalog_option
{
    my ($self, $catalog, $field) = @_;
    if(!$self->{_catalog}{$catalog}) {
        print "Loading catalog settings\n";
        my ($sql, $sth);
        $sql = qq{SELECT * FROM catalog WHERE path = '$catalog'};
        my $dbh = $self->dbh();
        $sth = $dbh->prepare($sql);
        $sth->execute();
        $self->{_catalog}{$catalog} = $sth->fetchrow_hashref();
    }
    return $self->{_catalog}->{$catalog}->{$field};
}

sub change_flags
{
    my ($self, $song, $oldflag, $newflag) = @_;
    my ($sql, $sth);
    my $dbh = $self->dbh();
    $sql = "UPDATE flagged SET type = '$newflag' WHERE song = '".$song->{'id'}."' AND type = '$oldflag'";
    $sth = $dbh->prepare($sql);
    $sth->execute();
}

 sub update_song
{
    my ($self, $filename, $song) = @_;
    my ($sql, $sth);
    my $dbh = $self->dbh();
    $filename =~ s/'/\\'/g;
    $filename =~ s/"/\\"/g;
    $filename =~ s/\Q%\E//g;
    $sql = "UPDATE song SET file = '$filename' WHERE id = '".$song->{'id'}."'";
    $sth = $dbh->prepare($sql);
    $sth->execute();
}   

sub get_song_info
{
    my ($self, $song) = @_;
    my ($sql, $sth);
    my $dbh = $self->dbh();
    if ( not $self->{_sth_cache}{get_song_info}) 
    {
        $self->{_sth_cache}{get_song_info} = $self->prepare_sth_cache(
            qq{SELECT catalog.path AS catalog,song.file,song.id,song.title,song.track,song.year,song.comment,album.name AS album, artist.name AS artist,genre FROM song,album,artist,catalog WHERE song.id = ? AND album.id = song.album AND artist.id = song.artist AND song.catalog = catalog.id});

    }
    $sth = $self->{_sth_cache}{get_song_info};
    $sth->execute($song);

    my @table = ();
    while ( my $ary = $sth->fetchrow_hashref() ) 
    {
        push(@table, $ary); 
    }
    return (@table);
}

#sub get_song_info
#{
#    my ($self, $song) = @_;
#
#    my ($sql, $sth);
#    my $dbh = $self->dbh();
#    if ( not $self->{_sth_cache}{song_info}{$song} ) 
#    {
#        $sql = qq{SELECT * FROM song WHERE id = $song};
#        $sth = $dbh->prepare($sql);
#        $self->{_sth_cache}{song_info}{$song} = $sth;
#    }
#
#    $sth = $self->{_sth_cache}{song_info}{$song};
#    $sth->execute();
#
#    my @song_info = $sth->fetchrow_hashref(); 
#    return (@song_info);
#}


1;
__END__
