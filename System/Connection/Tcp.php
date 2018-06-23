<?php
namespace System\Connection;
use System\Component\EventEmitter; 
use System\Connection\Request;

class Tcp{

  private $protocol = null;

  public function __construct( $connectSocket ){

    // 获取协议类型 比如http tcp udp
    $this->protocol = \System\Core::$protocol;

    // 获取IO多路复用器 比如event select
    $eventLoop = \System\Core::$eventLoop;

    // 将连接socket设置为非阻塞
    socket_set_nonblock( $connectSocket );

    // 将连接socket加入到事件循环中,触发条件是一旦发现有读的可能性
    $eventLoop->add( $connectSocket, \Event::READ, array( $this, 'receive' ) );

  }

  /*
   * @desc : 接受数据,此乃回调函数，当收到数据时候
   */
  public function receive( $connectSocket ){

		// 查看是哪种协议 然后初始化协议解析器
    $protocol = ucfirst( $this->protocol );
    $protocolClass = "System\\Protocol\\".$protocol;
    $protocolParser = new $protocolClass;

		// 接受到的数据内容
    $rawData = socket_read( $connectSocket, 2048 ); 
		$request = $protocolParser->decode( $rawData );

		// 返回数据
		$msg = $protocolParser->encode();
		//$rs = socket_write( $connectSocket, $msg, strlen( $msg ) );
		//socket_close( $connectSocket );

    //
		$response = new Response( $connectSocket );

		// 执行回调函数
		$cb = EventEmitter::on( 'request', function(){} );
	  call_user_func_array( $cb, array( $request, $response ) );	

    // 获取IO多路复用器 比如event select
		// 将fd从数组中删除掉
    $eventLoop = \System\Core::$eventLoop;
    $eventLoop->del( $connectSocket, \Event::READ );

  }
  

}
