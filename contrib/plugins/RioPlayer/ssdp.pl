#!/usr/bin/perl -T -w

#
# UDP server for auto-configuration of Dell Audio Radio.
#
# Listens on UDP port 21075 for SSDP requests and 
# replies to point the client box to the appropriate
# server.
#
# Typically put this is /etc/ssdp.pl and run it from
# /etc/rc.d/rc.local (or equiv).  This runs as a 
# daemon and consumes few resources.  The client box
# sends out a couple of requests when it powers up 
# and none otherwise.
#

use strict;
use POSIX;
use IO::Socket;

$ENV{PATH} = "/usr/bin:/bin:/usr/local/bin";
$ENV{BASH_ENV} = "/root/.bashrc";

sub mlog($) {
    my $msg = shift;
    system("logger -t SSDP \"$msg\"");
}

sub mdie($) {
    my $msg = shift;
    mlog $msg;
    exit 1;
}

sub sig_handle($) {
    mdie "SSDP server exit on signal";
}

# $mserve_ip must be a dotted-quad unless you modify the client
# NFS image to include /etc/resolv.conf.
#
my $ssdp_port   = 21075;
my $mserve_ip   = "10.60.60.16"; 		# web and NFS server IP address
my $mserve_port = "80";				# web server port

#
# The box makes two different requests.  One comes from the kernel
# during initial booting, the second comes from the player application
# after the second boot when the player starts.
#
# The respones are different for Linux.  If there is a port number
# on the first "linux" request then the client box will use that port
# for portmapper lookups, which is generally bad when talking to 
# another linux box.
#
# The second "player" response includes a port number that indicates
# the port number to use for HTTP music related requests.  I use
# port 81 and setup a virtual server in Apache to respond to music
# requests, but you may want to do this differently.
#
my $player_request = "^upnp:uuid:1D274DB0-F053-11d3-BF72-0050DA689B2F";
my $linux_request  = "^upnp:uuid:1D274DB1-F053-11d3-BF72-0050DA689B2F";

my (
    $pid,          # PID of server
    $server,       # Handle for server socket
    $him,          # peer making UDP request
    $datagram,     # Packet from client
);

#
# Cruft to become a daemon
#
$pid = fork;
exit if $pid;
mlog "SSDP server started";
mdie "Could not fork: $!" unless defined($pid);
POSIX::setsid() or mdie "Cannot start new session: $!";
$SIG{INT}  = \&sig_handle;
$SIG{TERM} = \&sig_handle;
$SIG{HUP}  = \&sig_handle;
$0 = "ssdp";

#
# Get a socket to be a UDP server
$server = IO::Socket::INET->new(LocalPort => $ssdp_port,
                                Proto     => "udp")
    or mdie "Couldn't be a udp server on port $ssdp_port : $@\n";

#
# Wait for requests and respond if appropriate
#
while ($him = $server->recv($datagram, 256, 0)) {
    my ($port, $iaddr) = sockaddr_in($server->peername);
    my $peer = inet_ntoa($iaddr);
    $datagram =~ s/\n//g;
    if ($datagram =~ $linux_request) {
	mlog "Linux request from $peer.";
        $server->send("http://$mserve_ip/descriptor.xml\n");         
    } 
    if ($datagram =~ $player_request) {
	mlog "Player request from $peer.";
        $server->send("http://$mserve_ip:$mserve_port/descriptor.xml\n"); 
    }
} 
