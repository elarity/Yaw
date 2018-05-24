<?php
namespace System\Connection;
use System\Component\EventEmitter; 


class Tcp{

  private $protocol = null;

  public function __construct( $connectSocket ){

    // 获取协议类型 比如http tcp udp
    $this->protocol = \System\Core::$protocol;

    // 获取IO多路复用器 比如event select
    $eventLoop = \System\Core::$eventLoop;

    // 将连接socket设置为非阻塞
    socket_set_nonblock( $connectSocket );

    // 将连接socket加入到事件循环中
    $eventLoop->add( $connectSocket, \Event::READ, array( $this, 'receive' ) );

  }

  public function end(){} 

  /*
   * @desc : 接受数据
   */
  public function receive( $connectSocket ){
     $protocol = ucfirst( $this->protocol );
     $protocolClass = "System\\Protocol\\".$protocol;
     $protocolParser = new $protocolClass;
     print_r( $protocolParser );
     echo socket_read( $connectSocket, 2048 ); 
  }
  

}
