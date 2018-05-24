<?php
namespace System;
class Http extends Core{

  public function __construct( $host = '0.0.0.0', $port = 9999 ){

    parent::__construct( $protocol = 'http', $host, $port );

    // 为http服务器绑定request事件
    //$eventEmitter = self::$eventEmitter;
    //$eventEmitter->bind();  

  }

  //public function __call(  ){}

}
