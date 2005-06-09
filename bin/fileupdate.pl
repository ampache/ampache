#!/usr/bin/perl -w

# Find and file away MP3's.  Run multiple times and will
#  ignore addition of duplicates in db (based on MD5 hash
#  of full file path.

use FindBin qw($Bin);
require "$Bin/init";

use Data::Dumper;
use Getopt::Long;

use vars qw($help $pretend $id3 $rename $sort $all $verbose);

Getopt::Long::Configure('bundling','no_ignore_case');
GetOptions
    ("h|help"        => \&usage,
     "p|pretend"        => \$pretend,
     "i|id3"         => \$id3,
     "r|rename"      => \$rename,
     "s|sort"        => \$sort,
     "a|all"         => \$all,
     "rename_all"    => \$rename_all,
     "sort_all"	     => \$sort_all,
     "v|verbose"     => \$verbose);
 
if ( !$help && !$all && !$id3 && !$rename && !$sort && !$rename_all && !$sort_all ) {
	usage();
}

if ($help) { 
	usage();
}

if($id3 or $all)
{
    my @flagged = $ampache->get_table_where("flagged","WHERE type = 'setid3'");

    foreach my $update(@flagged)
    {
        my @info = $ampache->get_song_info($update->{'song'});
        my $cmd = update_id3_tag($ampache,@info);
        if($rename or $all)
        { 
            if($verbose){ print "Marking for rename after id3\n"; }
            if(!$pretend){ $ampache->change_flags(@info,'setid3','ren'); }
        }
        else
        {
            if($sort or $all)
            {
                if($verbose){ print "Marking for sort after id3\n"; }
                if(!$pretend){ $ampache->change_flags(@info,'setid3','sort'); }
            }
            else
            {
                if($verbose){ print "Stopping after id3 update\n"; }
                if(!$pretend){ $ampache->change_flags(@info,'setid3','notify'); }
            }
        }
    }
}

if($rename or $all)
{
    my $filename = '';
    my @flagged = $ampache->get_table_where("flagged","WHERE type = 'ren'");
    foreach my $update (@flagged)
    {
        my @info = $ampache->get_song_info($update->{'song'});
        my $cmd = rename_file($ampache,\$filename,@info);
        if(!$pretend){ $ampache->update_song($cmd,@info); }
        if($sort or $all)
        {
            if($verbose){ print "Marking for sort after rename\n"; }
            if(!$pretend){ $ampache->change_flags(@info,'ren','sort'); }
        }
        else
        {
            if($verbose){ print "Updating filename in DB after rename\n"; }
            if(!$pretend){ $ampache->change_flags(@info,'ren','notify'); }
        }
    }
}

if ($rename_all) { 
	my $filename = '';
	my @flagged = $ampache->get_table_where("catalog,song","WHERE catalog.catalog_type='local' AND catalog.id=song.catalog","song.id AS song");
	foreach my $update (@flagged) {
		my @info = $ampache->get_song_info($update->{'song'});
		my $cmd = rename_file($ampache,\$filename,@info);
		if(!$pretend){ $ampache->update_song($cmd,@info); }
	} # End Foreach
} # End Rename All

if ($sort_all) { 
    my $filename = '';
    my @flagged = $ampache->get_table_where("catalog,song","WHERE catalog.catalog_type='local' AND catalog.id=song.catalog","song.id AS song");
    foreach my $update(@flagged)
    {
        my @info = $ampache->get_song_info($update->{'song'});
        my $cmd = sort_file($ampache,\$filename,@info);
        if(!$pretend){ $ampache->update_song($cmd,@info); }
    if($verbose){ print "Updating filename in DB after sort\n"; }
    if(!$pretend){ $ampache->change_flags(@info,'sort','notify'); }
    }
} # End Sort ALL


if($sort or $all)
{
    my $filename = '';
    my @flagged = $ampache->get_table_where("flagged","WHERE type = 'sort'");
    foreach my $update(@flagged)
    {
        my @info = $ampache->get_song_info($update->{'song'});
        my $cmd = sort_file($ampache,\$filename,@info);
        if(!$pretend){ $ampache->update_song($cmd,@info); }
    if($verbose){ print "Updating filename in DB after sort\n"; }
    if(!$pretend){ $ampache->change_flags(@info,'sort','notify'); }
    }
}

# # # # #
# subs
# # # # # # #

# %A = album name
# %a = artist name
# %C = catalog path (for the specified song)
# %c = comment
# %g = genre
# %y = year
# %T = track number
# %t = song title
#
# %filename I use for filename

sub get_catalog_setting
{ 
    my ($self,$catalog,$setting) = @_;
    #bless $self;
    my $cmd = $self->get_catalog_option($catalog,$setting);
    return $cmd;
}

sub update_id3_tag 
{
    my ($self,$song) = @_;
    my $id3set = get_catalog_setting($self,$song->{'catalog'},'id3_set_command');
    $id3set =~ s/\Q%A\E/$song->{'album'}/g;
    $id3set =~ s/\Q%a\E/$song->{'artist'}/g;
    $id3set =~ s/\Q%C\E/$song->{'catalog'}/g;
    $id3set =~ s/\Q%c\E/$song->{'comment'}/g;
    if(($song->{'genre'} * 1) < 255){$id3set =~ s/\Q%g\E/$song->{'genre'}/g;}
    else{$id3set =~ s/ -g %g//g;}
    $id3set =~ s/\Q%T\E/$song->{'track'}/g;
    $id3set =~ s/\Q%t\E/$song->{'title'}/g;
    $id3set =~ s/\Q%y\E/$song->{'year'}/g;
    $id3set =~ s/\Q%filename\E//g;
    # $id3set =~ s/([\'\"])/\\$1/g;
    my $filename = $song->{'file'};
    my $id3tag_command = "$id3set \"$filename\"";
    return do_call($id3tag_command);
}

sub rename_file 
{
    my ($self,$filename,$song) = @_;
    my $ren_pattern = get_catalog_setting($self,$song->{'catalog'},'rename_pattern');
    #my $sort_pattern = get_catalog_setting($self,$song->{'catalog'},'sort_pattern'); 
    my $basedir;
    if( $song->{'file'} =~ m/^(.*)\/.*?$/ )
    {
        $basedir = $1;
    }
    else{ die "Could not determine base directory for $song->{'file'}\n"; }

    # We want to pad track numbers with leading zeros:
    if($song->{'track'} < 10)
    {
        $song->{'track'} = "0".$song->{'track'};
    }

    # we need to clean title,album,artist,comment,genre,track, and year
    $song->{'title'} =~ s/[\/]/-/g;
    $song->{'album'} =~ s/[\/]/-/g;
    $song->{'artist'} =~ s/[\/]/-/g;
    $song->{'comment'} =~ s/[\/]/-/g;
    $song->{'genre'} =~ s/[\/]/-/g;
    $song->{'track'} =~ s/[\/]/-/g;
    $song->{'year'} =~ s/[\/]/-/g;

    $ren_pattern =~ s/\Q%A\E/$song->{'album'}/g;
    $ren_pattern =~ s/\Q%a\E/$song->{'artist'}/g;
    $ren_pattern =~ s/\Q%C\E/$song->{'catalog'}/g;
    $ren_pattern =~ s/\Q%c\E/$song->{'comment'}/g;
    $ren_pattern =~ s/\Q%g\E/$song->{'genre'}/g;
    $ren_pattern =~ s/\Q%T\E/$song->{'track'}/g;
    $ren_pattern =~ s/\Q%t\E/$song->{'title'}/g;
    $ren_pattern =~ s/\Q%y\E/$song->{'year'}/g;
    $ren_pattern =~ s/\Q%filename\E/$song->{'file'}/g;
    my $oldfilename = $song->{'file'};
    my $newfilename = $basedir . "/" . $ren_pattern;
    # result is backslashes in filename
    # $newfilename =~ s/([\'\"])/\\$1/g;

	print "\tNew: $newfilename -- OLD: $oldfilename\n";

    if(! -e "$newfilename")
    {
        my $ren_command = "mv \"$oldfilename\" \"$newfilename\"";
        $filename = $newfilename;
        do_call($ren_command);
        return $filename;
    }
    else
    {
        print STDERR "File exists: $newfilename\n";
        $filename = $oldfilename;
        return $filename;
    }
}

sub sort_file 
{
    my ($self, $filename, $song) = @_;
    my $basename;
    my $basedir;
    if( $song->{'file'} =~ m/^(.*)\/(.*?)$/ )
    {
        $basename = $2;
        $basedir = $1
    }
    else{ die "Could not determine base name for $song->{'file'}\n"; }

    # we need to clean title,album,artist,comment,genre,track, and year
    $song->{'title'} =~ s/[\/]/-/g;
    $song->{'album'} =~ s/[\/]/-/g;
    $song->{'artist'} =~ s/[\/]/-/g;
    $song->{'comment'} =~ s/[\/]/-/g;
    $song->{'genre'} =~ s/[\/]/-/g;
    $song->{'track'} =~ s/[\/]/-/g;
    $song->{'year'} =~ s/[\/]/-/g;

    my $location = get_catalog_setting($self,$song->{'catalog'},'sort_pattern');
    $location =~ s/\Q%A\E/$song->{'album'}/g;
    $location =~ s/\Q%a\E/$song->{'artist'}/g;
    $location =~ s/\Q%C\E/$song->{'catalog'}/g;
    $location =~ s/\Q%c\E/$song->{'comment'}/g;
    $location =~ s/\Q%g\E/$song->{'genre'}/g;
    $location =~ s/\Q%T\E/$song->{'track'}/g;
    $location =~ s/\Q%t\E/$song->{'title'}/g;
    $location =~ s/\Q%y\E/$song->{'year'}/g;
    # result is wrong paths
    # $location =~ s/([\'\"])/\\$1/g;

    create($location);

    # The basename is calculated so we can see if the file already exists
    if(! -e "$location/$basename")
    {
        my $cmd = "/bin/mv \"".$song->{'file'}."\" \"$location\"";
        my $ret = do_call($cmd);
        if(empty_dir($basedir))
        {
            print "Removing empty directory $basedir\n";
            $cmd = "/bin/rmdir \"$basedir\"";
            do_call($cmd);
        }
        $filename = $location."/".$basename;
        return $filename;
    }
    else 
    {
        print STDERR "File exists: $location/$basename\n";
        $filename = $song->{'file'};
        return $filename;
    }
}

sub usage 
{
    my $usage = qq{
    fileupdate [--id3|--rename|--rename_all|--sort|--sort_all|--all] [--help] [--pretend] [--verbose]
        --pretend   	Display command taken, without actually doing anything.
	
        --id3       	Update id3 tags for all files flagged with 'id3'
	
        --rename    	Rename files flagged with 'rename'
	
	--rename_all	Renames all files based on id3 info
	
        --sort      	Sort files flagged with 'sort'
	
	--sort_all	Sort all files based on id3 info
	
        --all		Performs id3 update, rename, and sort
                    		for all files flagged with 'id3'
        --verbose	Shows detailed information about what's happening.

        --help		This message
    };
    die $usage;
}

sub do_call 
{
    my @cmd = @_;
    my $return = 0;

    if($verbose && !$pretend){ print "@cmd\n";}
    if($pretend){ print "@cmd\n"; }
    else
    { 
        $return = system @cmd; 
    }
    return $return;
}

sub create 
{
    my ($path) = @_;
    if(! -e $path)
    {
        return do_call("mkdir","-p",$path);
    }
    return 1;
}

# empty_dir borrowed from Tom Phoenix (rootbeer@teleport.com)
# posted in comp.lang.perl.misc on 3/21/97

sub empty_dir ($) 
{
    local(*DIR, $_);
    return unless opendir DIR, $_[0];
    while (defined($_ = readdir DIR)) {
        next if /^\.\.?$/;
        closedir DIR;
        return 0;
    }
    closedir DIR;
    1;
}
1;
