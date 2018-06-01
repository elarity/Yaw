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

		// 查看是哪种协议 然后初始化协议解析器
    $protocol = ucfirst( $this->protocol );
    $protocolClass = "System\\Protocol\\".$protocol;
    $protocolParser = new $protocolClass;


		// 接受到的数据内容
    $rawData = socket_read( $connectSocket, 2048 ); 
		$protocolParser->decode( $rawData );


		$msg = 'msg';
		socket_write( $connectSocket, $msg, strlen( $msg ) );
		socket_close( $connectSocket );
    // 获取IO多路复用器 比如event select
		// 将fd从数组中删除掉
    $eventLoop = \System\Core::$eventLoop;
    $eventLoop->del( $connectSocket, \Event::READ );

  }
  

}
