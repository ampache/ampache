<?php

##########################################################################
# ZipStream - Streamed, dynamically generated zip archives.              #
# by Paul Duncan <pabs@pablotron.org>                                    #
#                                                                        #
# Copyright (C) 2007-2009 Paul Duncan <pabs@pablotron.org>               #
#                                                                        #
# Permission is hereby granted, free of charge, to any person obtaining  #
# a copy of this software and associated documentation files (the        #
# "Software"), to deal in the Software without restriction, including    #
# without limitation the rights to use, copy, modify, merge, publish,    #
# distribute, sublicense, and/or sell copies of the Software, and to     #
# permit persons to whom the Software is furnished to do so, subject to  #
# the following conditions:                                              #
#                                                                        #
# The above copyright notice and this permission notice shall be         #
# included in all copies or substantial portions of the of the Software. #
#                                                                        #
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,        #
# EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF     #
# MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. #
# IN NO EVENT SHALL THE AUTHORS BE LIABLE FOR ANY CLAIM, DAMAGES OR      #
# OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,  #
# ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR  #
# OTHER DEALINGS IN THE SOFTWARE.                                        #
##########################################################################

#
# ZipStream - Streamed, dynamically generated zip archives.
# by Paul Duncan <pabs@pablotron.org>
#
# Requirements:
#
# * PHP version 5.1.2 or newer.
#
# Usage:
#
# Streaming zip archives is a simple, three-step process:
#
# 1.  Create the zip stream:
#
#     $zip = new ZipStream('example.zip');
#
# 2.  Add one or more files to the archive:
#
#     # add first file
#     $data = file_get_contents('some_file.gif');
#     $zip->add_file('some_file.gif', $data);
#
#     # add second file
#     $data = file_get_contents('some_file.gif');
#     $zip->add_file('another_file.png', $data);
#
# 3.  Finish the zip stream:
#
#     $zip->finish();
#
# You can also add an archive comment, add comments to individual files,
# and adjust the timestamp of files.  See the API documentation for each
# method below for additional information.
#
# Example:
#
#   # create a new zip stream object
#   $zip = new ZipStream('some_files.zip');
#
#   # list of local files
#   $files = array('foo.txt', 'bar.jpg');
#
#   # read and add each file to the archive
#   foreach ($files as $path)
#     $zip->add_file($path, file_get_contents($path));
# 
#   # write archive footer to stream
#   $zip->finish();
#
class ZipStream {
  const VERSION = '0.2.2';

  var $opt = array(),
      $files = array(),
      $cdr_ofs = 0,
      $ofs = 0; 

  #
  # Create a new ZipStream object.
  #
  # Parameters:
  #
  #   $name - Name of output file (optional).
  #   $opt  - Hash of archive options (optional, see "Archive Options"
  #           below).
  #
  # Archive Options:
  #
  #   comment             - Comment for this archive.
  #   content_type        - HTTP Content-Type.  Defaults to 'application/x-zip'.
  #   content_disposition - HTTP Content-Disposition.  Defaults to 
  #                         'attachment; filename=\"FILENAME\"', where
  #                         FILENAME is the specified filename.
  #   large_file_size     - Size, in bytes, of the largest file to try
  #                         and load into memory (used by
  #                         add_file_from_path()).  Large files may also
  #                         be compressed differently; see the
  #                         'large_file_method' option.
  #   large_file_method   - How to handle large files.  Legal values are
  #                         'store' (the default), or 'deflate'.  Store
  #                         sends the file raw and is significantly
  #                         faster, while 'deflate' compresses the file
  #                         and is much, much slower.  Note that deflate
  #                         must compress the file twice and extremely
  #                         slow.
  #   send_http_headers   - Boolean indicating whether or not to send
  #                         the HTTP headers for this file.
  #
  # Note that content_type and content_disposition do nothing if you are
  # not sending HTTP headers.
  #
  # Large File Support:
  #
  # By default, the method add_file_from_path() will send send files
  # larger than 20 megabytes along raw rather than attempting to
  # compress them.  You can change both the maximum size and the
  # compression behavior using the large_file_* options above, with the
  # following caveats:
  #
  # * For "small" files (e.g. files smaller than large_file_size), the
  #   memory use can be up to twice that of the actual file.  In other
  #   words, adding a 10 megabyte file to the archive could potentially
  #   occupty 20 megabytes of memory.
  #
  # * Enabling compression on large files (e.g. files larger than
  #   large_file_size) is extremely slow, because ZipStream has to pass
  #   over the large file once to calculate header information, and then
  #   again to compress and send the actual data.
  #
  # Examples:
  #
  #   # create a new zip file named 'foo.zip'
  #   $zip = new ZipStream('foo.zip');
  #
  #   # create a new zip file named 'bar.zip' with a comment
  #   $zip = new ZipStream('bar.zip', array(
  #     'comment' => 'this is a comment for the zip file.',
  #   ));
  #
  # Notes:
  #
  # If you do not set a filename, then this library _DOES NOT_ send HTTP
  # headers by default.  This behavior is to allow software to send its
  # own headers (including the filename), and still use this library.
  #
  function __construct($name = null, $opt = array()) {
    # save options
    $this->opt = $opt;

    # set large file defaults: size = 20 megabytes, method = store
    if (!$this->opt['large_file_size'])
      $this->opt['large_file_size'] = 20 * 1024 * 1024;
    if (!$this->opt['large_file_method'])
      $this->opt['large_file_method'] = 'store';

    $this->output_name = $name;
    if ($name || $opt['send_http_headers'])
      $this->need_headers = true; 
  }

  #
  # add_file - add a file to the archive
  #
  # Parameters:
  #   
  #  $name - path of file in archive (including directory).
  #  $data - contents of file
  #  $opt  - Hash of options for file (optional, see "File Options"
  #          below).  
  #
  # File Options: 
  #  time     - Last-modified timestamp (seconds since the epoch) of
  #             this file.  Defaults to the current time.
  #  comment  - Comment related to this file.
  #
  # Examples:
  #
  #   # add a file named 'foo.txt'
  #   $data = file_get_contents('foo.txt');
  #   $zip->add_file('foo.txt', $data);
  # 
  #   # add a file named 'bar.jpg' with a comment and a last-modified
  #   # time of two hours ago
  #   $data = file_get_contents('bar.jpg');
  #   $zip->add_file('bar.jpg', $data, array(
  #     'time'    => time() - 2 * 3600,
  #     'comment' => 'this is a comment about bar.jpg',
  #   ));
  # 
  function add_file($name, $data, $opt = array()) {
    # compress data
    $zdata = gzdeflate($data);

    # calculate header attributes
    $crc  = crc32($data);
    $zlen = strlen($zdata);
    $len  = strlen($data);
    $meth = 0x08;

    # send file header
    $this->add_file_header($name, $opt, $meth, $crc, $zlen, $len);

    # print data
    $this->send($zdata);
  }

  #
  # add_file_from_path - add a file at path to the archive.
  #
  # Note that large files may be compresed differently than smaller
  # files; see the "Large File Support" section above for more
  # information.
  #
  # Parameters:
  #   
  #  $name - name of file in archive (including directory path).
  #  $path - path to file on disk (note: paths should be encoded using
  #          UNIX-style forward slashes -- e.g '/path/to/some/file').
  #  $opt  - Hash of options for file (optional, see "File Options"
  #          below).  
  #
  # File Options: 
  #  time     - Last-modified timestamp (seconds since the epoch) of
  #             this file.  Defaults to the current time.
  #  comment  - Comment related to this file.
  #
  # Examples:
  #
  #   # add a file named 'foo.txt' from the local file '/tmp/foo.txt'
  #   $zip->add_file_from_path('foo.txt', '/tmp/foo.txt');
  # 
  #   # add a file named 'bigfile.rar' from the local file
  #   # '/usr/share/bigfile.rar' with a comment and a last-modified
  #   # time of two hours ago
  #   $path = '/usr/share/bigfile.rar';
  #   $zip->add_file_from_path('bigfile.rar', $path, array(
  #     'time'    => time() - 2 * 3600,
  #     'comment' => 'this is a comment about bar.jpg',
  #   ));
  # 
  function add_file_from_path($name, $path, $opt = array()) {
    if ($this->is_large_file($path)) {
      # file is too large to be read into memory; add progressively
      $this->add_large_file($name, $path, $opt);
    } else {
      # file is small enough to read into memory; read file contents and
      # handle with add_file()
      $data = file_get_contents($path);
      $this->add_file($name, $data, $opt);
    }
  }

  #
  # finish - Write zip footer to stream.
  #
  # Example:
  #
  #   # add a list of files to the archive
  #   $files = array('foo.txt', 'bar.jpg');
  #   foreach ($files as $path)
  #     $zip->add_file($path, file_get_contents($path));
  # 
  #   # write footer to stream
  #   $zip->finish();
  # 
  function finish() {
    # add trailing cdr record
    $this->add_cdr($this->opt);
    $this->clear();
  }

  ###################
  # PRIVATE METHODS #
  ###################

  #
  # Create and send zip header for this file.
  #
  private function add_file_header($name, $opt, $meth, $crc, $zlen, $len) {
    # strip leading slashes from file name
    # (fixes bug in windows archive viewer)
    $name = preg_replace('/^\\/+/', '', $name);

    # calculate name length
    $nlen = strlen($name);

    # create dos timestamp
    $opt['time'] = $opt['time'] ? $opt['time'] : time();
    $dts = $this->dostime($opt['time']);

    # build file header
    $fields = array(            # (from V.A of APPNOTE.TXT)
      array('V', 0x04034b50),     # local file header signature
      array('v', (6 << 8) + 3),   # version needed to extract
      array('v', 0x00),           # general purpose bit flag
      array('v', $meth),          # compresion method (deflate or store)
      array('V', $dts),           # dos timestamp
      array('V', $crc),           # crc32 of data
      array('V', $zlen),          # compressed data length
      array('V', $len),           # uncompressed data length
      array('v', $nlen),          # filename length
      array('v', 0),              # extra data len
    );

    # pack fields and calculate "total" length
    $ret = $this->pack_fields($fields);
    $cdr_len = strlen($ret) + $nlen + $zlen;

    # print header and filename
    $this->send($ret . $name);

    # add to central directory record and increment offset
    $this->add_to_cdr($name, $opt, $meth, $crc, $zlen, $len, $cdr_len);
  }

  #
  # Add a large file from the given path.
  #
  private function add_large_file($name, $path, $opt = array()) {
    $st = stat($path);
    $block_size = 1048576; # process in 1 megabyte chunks
    $algo = 'crc32b';

    # calculate header attributes
    $zlen = $len = $st['size'];

    $meth_str = $this->opt['large_file_method'];
    if ($meth_str == 'store') {
      # store method
      $meth = 0x00;
      $crc  = unpack('V', hash_file($algo, $path, true));
      $crc = $crc[1];
    } elseif ($meth_str == 'deflate') {
      # deflate method
      $meth = 0x08;

      # open file, calculate crc and compressed file length
      $fh = fopen($path, 'rb');
      $hash_ctx = hash_init($algo);
      $zlen = 0;

      # read each block, update crc and zlen
      while ($data = fgets($fh, $block_size)) {
        hash_update($hash_ctx, $data);
        $data = gzdeflate($data);
        $zlen += strlen($data);
      }

      # close file and finalize crc
      fclose($fh);
      $crc = unpack('V', hash_final($hash_ctx, true));
      $crc = $crc[1];
    } else {
      die("unknown large_file_method: $meth_str");
    }

    # send file header
    $this->add_file_header($name, $opt, $meth, $crc, $zlen, $len);

    # open input file
    $fh = fopen($path, 'rb');

    # send file blocks
    while ($data = fgets($fh, $block_size)) {
      if ($meth_str == 'deflate') 
        $data = gzdeflate($data);

      # send data
      $this->send($data);
    }

    # close input file
    fclose($fh);
  }

  #
  # Is this file larger than large_file_size?
  #
  function is_large_file($path) {
    $st = stat($path);
    return ($this->opt['large_file_size'] > 0) && 
           ($st['size'] > $this->opt['large_file_size']);
  }

  #
  # Save file attributes for trailing CDR record.
  #
  private function add_to_cdr($name, $opt, $meth, $crc, $zlen, $len, $rec_len) {
    $this->files[] = array($name, $opt, $meth, $crc, $zlen, $len, $this->ofs);
    $this->ofs += $rec_len;
  }

  #
  # Send CDR record for specified file.
  #
  private function add_cdr_file($args) {
    list ($name, $opt, $meth, $crc, $zlen, $len, $ofs) = $args;

    # get attributes
    $comment = $opt['comment'] ? $opt['comment'] : '';

    # get dos timestamp
    $dts = $this->dostime($opt['time']);

    $fields = array(                  # (from V,F of APPNOTE.TXT)
      array('V', 0x02014b50),           # central file header signature
      array('v', (6 << 8) + 3),         # version made by
      array('v', (6 << 8) + 3),         # version needed to extract
      array('v', 0x00),                 # general purpose bit flag
      array('v', $meth),                # compresion method (deflate or store)
      array('V', $dts),                 # dos timestamp
      array('V', $crc),                 # crc32 of data
      array('V', $zlen),                # compressed data length
      array('V', $len),                 # uncompressed data length
      array('v', strlen($name)),        # filename length
      array('v', 0),                    # extra data len
      array('v', strlen($comment)),     # file comment length
      array('v', 0),                    # disk number start
      array('v', 0),                    # internal file attributes
      array('V', 32),                   # external file attributes
      array('V', $ofs),                 # relative offset of local header
    );

    # pack fields, then append name and comment
    $ret = $this->pack_fields($fields) . $name . $comment;

    $this->send($ret);

    # increment cdr offset
    $this->cdr_ofs += strlen($ret);
  }

  #
  # Send CDR EOF (Central Directory Record End-of-File) record.
  #
  private function add_cdr_eof($opt = null) {
    $num = count($this->files);
    $cdr_len = $this->cdr_ofs;
    $cdr_ofs = $this->ofs;

    # grab comment (if specified)
    $comment = '';
    if ($opt && $opt['comment'])
      $comment = $opt['comment'];

    $fields = array(                # (from V,F of APPNOTE.TXT)
      array('V', 0x06054b50),         # end of central file header signature
      array('v', 0x00),               # this disk number
      array('v', 0x00),               # number of disk with cdr
      array('v', $num),               # number of entries in the cdr on this disk
      array('v', $num),               # number of entries in the cdr
      array('V', $cdr_len),           # cdr size
      array('V', $cdr_ofs),           # cdr ofs
      array('v', strlen($comment)),   # zip file comment length
    );

    $ret = $this->pack_fields($fields) . $comment;
    $this->send($ret);
  }

  #
  # Add CDR (Central Directory Record) footer.
  #
  private function add_cdr($opt = null) {
    foreach ($this->files as $file)
      $this->add_cdr_file($file);
    $this->add_cdr_eof($opt);
  }

  #
  # Clear all internal variables.  Note that the stream object is not
  # usable after this.
  #
  function clear() {
    $this->files = array();
    $this->ofs = 0;
    $this->cdr_ofs = 0;
    $this->opt = array();
  }

  ###########################
  # PRIVATE UTILITY METHODS #
  ###########################

  #
  # Send HTTP headers for this stream.
  #
  private function send_http_headers() {
    # grab options
    $opt = $this->opt;
    
    # grab content type from options
    $content_type = 'application/x-zip';
    if ($opt['content_type'])
      $content_type = $this->opt['content_type'];

    # grab content disposition 
    $disposition = 'attachment';
    if ($opt['content_disposition'])
      $disposition = $opt['content_disposition'];

    if ($this->output_name) 
      $disposition .= "; filename=\"{$this->output_name}\"";

    $headers = array(
      'Content-Type'              => $content_type,
      'Content-Disposition'       => $disposition,
      'Pragma'                    => 'public',
      'Cache-Control'             => 'public, must-revalidate',
      'Content-Transfer-Encoding' => 'binary',
    );

    foreach ($headers as $key => $val)
      header("$key: $val");
  }

  #
  # Send string, sending HTTP headers if necessary.
  #
  private function send($str) {
    if ($this->need_headers)
      $this->send_http_headers();
    $this->need_headers = false;

    echo $str;
  }

  #
  # Convert a UNIX timestamp to a DOS timestamp.
  #
  function dostime($when = 0) {
    # get date array for timestamp
    $d = getdate($when);

    # set lower-bound on dates
    if ($d['year'] < 1980) {
      $d = array('year' => 1980, 'mon' => 1, 'mday' => 1, 
                 'hours' => 0, 'minutes' => 0, 'seconds' => 0);
    }

    # remove extra years from 1980
    $d['year'] -= 1980;

    # return date string
    return ($d['year'] << 25) | ($d['mon'] << 21) | ($d['mday'] << 16) |
           ($d['hours'] << 11) | ($d['minutes'] << 5) | ($d['seconds'] >> 1);
  }

  #
  # Create a format string and argument list for pack(), then call
  # pack() and return the result.
  #
  function pack_fields($fields) {
    list ($fmt, $args) = array('', array());

    # populate format string and argument list
    foreach ($fields as $field) {
      $fmt .= $field[0];
      $args[] = $field[1];
    }

    # prepend format string to argument list
    array_unshift($args, $fmt);

    # build output string from header and compressed data
    return call_user_func_array('pack', $args);
  }
};

?>
