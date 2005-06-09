use ExtUtils::MakeMaker;
# See lib/ExtUtils/MakeMaker.pm for details of how to influence
# the contents of the Makefile that is written.
WriteMakefile(
    'NAME'	=> 'Local::Ampache',
    'VERSION_FROM' => 'Ampache.pm', # finds $VERSION
);
