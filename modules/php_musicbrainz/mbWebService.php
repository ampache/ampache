<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
	interface IWebService {
	   function get ( $entity, $id, $include, $filter, $version = '1' );
	   function post( $entity, $id, $data, $version = '1' );
	}

	class mbWebService implements IWebService {
		private $host;
		private $port;
		private $pathPrefix;
		private $lastError;
		private $fSock;
		private $lastResponse = "";
		private $lastHeaders  = array();

		function mbWebService( $host="musicbrainz.org", $port=80, $pathPrefix="/ws" ) {
			$this->host	   = $host;
			$this->port	   = $port;
			$this->pathPrefix = $pathPrefix;
			$this->fSock	  = -1;
		}

		function connect() {
			$this->fSock = fsockopen( $this->host, $this->port, $errno, $this->lastError, 30 );

			if ( $this->fSock == false ) {
			  $this->fSock = -1;
			  return false;
			}

			return true;
		}

		function close() {
			if ( $this->fSock != -1 ) {
			  fclose($this->fSock);
			  $this->fSock = -1;
			  return true;
			}
			else {
			  $this->lastError = "Trying to close closed socket.";
			  return false;
			}
		}

		function parseHeaders( $string ) {
			$lines = explode( "\n", $string );
			$this->lastHeaders = array();

			foreach ( $lines as $key => $line ) {
				// Status line
				if ( $key == 0 ) {
					if ( !preg_match( "/^HTTP\/(\d+)\.(\d+) (\d+) .+$/", $line, $matches ) ) {
					  $this->lastHeader = array();
					  return false;
					}
					else {
					  $this->lastHeaders['HTTP_major_version'] = $matches[1];
					  $this->lastHeaders['HTTP_minor_version'] = $matches[2];
					  $this->lastHeaders['HTTP_status']		= $matches[3];
					}
				}
				// Empty line
				else if ( $line == "\r" ) {
				  $new_string = "";
				  for ( $i = $key+1; $i < sizeof($lines); $i++ )
					$new_string .= $lines[$i] . "\n";
				  return $new_string;
				}
				// Not a header
				else if ( !preg_match( "/^([^:]+): (.+)\r$/", $line, $matches ) ) {
				  $this->lastHeaders = array();
				  return false;
				}
				// A header
				else
				  $this->lastHeaders[$matches[1]] = $matches[2];
			}

			$this->lastHeaders = array();
			return false;
		}

		function getHeaders() {
			return $this->lastHeaders;
		}

		function sendRequest( $string, $post_data='' ) {
			if ( $this->fSock == -1 ) {
				$this->lastError = "Trying to write to closed socket.";
				return false;
			}

			fwrite( $this->fSock, $string . "\r\n" );
			fwrite( $this->fSock, "Host: " . $this->host . "\r\n"			 );
			fwrite( $this->fSock, "Accept: */*\r\n"						   );
			fwrite( $this->fSock, "User-Agent: phpMbQuery\r\n"				);
			//fwrite( $this->fSock, "Keep-Alive: 60\r\n"						);
			//fwrite( $this->fSock, "Connection: keep-alive\r\n"				);
			fwrite( $this->fSock, "Connection: close\r\n\r\n"				 );
			fwrite( $this->fSock, $post_data . "\r\n\r\n"					 );

			return true;
		}

		function getResponse() {
			if ( $this->fSock == -1 ) {
				$this->lastError = "Trying to read from closed socket.";
				return false;
			}

			$buffer = "";

			while ( !feof($this->fSock) )
			  $buffer .= fread( $this->fSock, 4096 );

			if ( !$this->parseHeaders($buffer) )
			  return $buffer;

			return $this->parseHeaders($buffer);
		}

		function get( $entity, $uid, $includes, $filters, $version="1" ) {
			$params = array();
			$params['type'] = "xml";

			if ( is_array($includes) ) {
				$inc_string = "";
				foreach ( $includes as $inc ) {
					if ( $inc_string != "" )
						$inc_string .= " ";
					$inc_string .= $inc;
				}
				if ( $inc_string != "" )
					$params['inc'] = $inc_string;
			}

			if ( is_array($filters) ) {
				foreach ( $filters as $filter => $value )
					$params[$filter] = $value;
			}

			$URI = $this->pathPrefix . "/" . $version . "/" . $entity . "/" . $uid . "?" . $this->build_query( $params );

			if ( $this->fSock == -1 && !$this->connect() )
			  return false;

			$this->sendRequest( "GET $URI HTTP/1.1" );
			$this->lastResponse = $this->getResponse();
			$this->close();

			if ( isset($this->lastHeaders['HTTP_status']) && $this->lastHeaders['HTTP_status'] != 200 )
			  return false;

			return $this->lastResponse;
		}

		function post( $entity, $id, $data, $version = '1' ) {
			$URI = $this->pathPrefix . '/' . $version . '/' . $entity . '/' . $id;
			if ( $this->fSock == -1 && !$this->connect() )
			  return false;

			$this->sendRequest( "POST $URI HTTP/1.1", $data );
			$this->lastResponse = $this->getResponse();
			$this->close();

			if ( isset($this->lastHeaders['HTTP_status']) && $this->lastHeaders['HTTP_status'] != 200 )
			  return false;

			return $this->lastResponse;
		}

		function build_query( $array ) {
			$first = true;
			$query_string = "";

			if ( !is_array($array) || sizeof($array) == 0 )
			  return "";

			foreach ( $array as $key => $value ) {
				$query_string .= ($first?"":"&") . "$key=" . urlencode($value);
				if ( $first )
					$first = false;
			}

			return $query_string;
		}
	}
?>
