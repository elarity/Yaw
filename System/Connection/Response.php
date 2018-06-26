<?php
namespace System\Connection;
//use System\Protocol\Http as Http;
use System\Protocol\Http as Http;

class Response{

	public $connectSocket = null;

	public function __construct( $connectSocket ){
		$this->connectSocket = $connectSocket;
	}

	/**/
	public function end( $content ){
		
    $content = Http::encode( $content );

		socket_write( $this->connectSocket, $content, strlen( $content ) );
		socket_close( $this->connectSocket );

	}

}
