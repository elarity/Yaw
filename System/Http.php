<?php
namespace System;
use System\Component\EventEmitter;

class Http extends Core{

	private $callback = array(
		'request',
	);

  public function __construct( $host = '0.0.0.0', $port = 9999 ){

    parent::__construct( $protocol = 'http', $host, $port );

    // 为http服务器绑定request事件
    //$eventEmitter = self::$eventEmitter;
    //$eventEmitter->bind();  

  }

  public function on( $method, \Closure $closure ){
		//if(  ){
		//}
		//echo $method.PHP_EOL;
    //$eventEmitter = self::$eventEmitter;
    eventEmitter::on( $method, $closure );
    //print_r( eventEmitter::on( $method, $closure ) );
  } 

}
