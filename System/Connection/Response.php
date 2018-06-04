<?php
namespace System\Connection;

class Response{

	public $connectSocket = null;

  public $server = [];

  public $header = [];

  public $get = [];

  public $post = [];

  public $rawContent = [];

	public function __construct( $connectSocket ){
		$this->connectSocket = $connectSocket;
	}

	/**/
	public function end( $content ){
		
		socket_write( $this->connectSocket, $content, strlen( $content ) );
		socket_close( $this->connectSocket );

	}

}
