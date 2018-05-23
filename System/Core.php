<?php
namespace System;
use System\Component\EventEmitter;
use System\Event\Factory;

abstract class Core{

  /*
   * @desc : 监听socket资源句柄
   */
  protected static $listenSocket = null;

  /*
   * @desc : 事件监听器
   */
  protected static $eventLoop = null;

  protected static $setting = array(
    'reactor_num' => 2,
    'worker_num' => 4,
    'task_worker_num' => 0,
  );

  /*
   * @desc : 初始化core
   */
  public function __construct(){
    self::$eventLoop = Factory::create(); 
  }

  /*
   *
   */
  public function set( array $settting ){
        
  }
  /*
   *
   */
  private static function daemonize(){
    umask( 0 );
    //echo '1 : '.posix_getpid().PHP_EOL;
    $pid = pcntl_fork();  
    if( $pid > 0 ){
      // parent process
      exit();
    } else if( $pid < 0 ){
      throw new \Exception( 'pcntl_fork error.'.PHP_EOL );
    }
    //echo '2 : '.posix_getpid().PHP_EOL;
    if( !posix_setsid() ){
      throw new \Exception( 'pcntl_posix error.'.PHP_EOL );
    }
    // 再次fork，是一种传统，有人说在System V中二次fork能够100%保证子进程会失去控制终端控制权
    //echo '3 : '.posix_getpid().PHP_EOL;
    $pid = pcntl_fork();
    if( $pid > 0 ){
      // parent process
      exit();
    }else if( $pid < 0 ){
      throw new \Exception( 'pcntl_fork error.'.PHP_EOL );
    }
    //echo '4 : '.posix_getpid().PHP_EOL;
  }
  private static function forkReactorProcess(){
    // master进程 
    cli_set_process_title( 'Yaw Master Process' );
    //echo 'master : '.posix_getpid().PHP_EOL;
    //reactor进程，由master进程fork出来
    for( $i = 1; $i <= self::$setting['reactor_num']; $i++ ){
      $pid = pcntl_fork();
      if( $pid == 0 ){
        cli_set_process_title( 'Yaw Reactor Process' );
        $listenSocket = self::$listenSocket; 
        //每个reactor进程都进入accpet
	while( true ){
	  if( ( $connectSocket = socket_accept( $listenSocket ) ) != false ){
    	    $msg = "helloworld";
	    socket_write( $connectSocket, $msg, strlen( $msg ) );
   	    socket_close( $connectSocket );
	  }  
	}
      }else if( 0 > $pid ){
        throw new \Exception( 'pcntl_fork error.'.PHP_EOL );
      }
    }
    //worker进程 由master进程fork出来
  }
  public function start(){
    //self::daemonize();
    self::createListenSocket();
    self::forkReactorProcess(); 
    // master进程
    while( true ){
      sleep( 1 );
    }
  }
  private static function createListenSocket(){
    $listenSocket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
    socket_bind( $listenSocket, '0.0.0.0', 9999 );
    socket_listen( $listenSocket );
    //socket_set_nonblock( $listenSocket );
    self::$listenSocket = $listenSocket;
  } 
  public function on( $method, \Closure $closure ){
  } 
  /*
   *
   */
  public function __call( $method, $argument ){
    //echo $method.PHP_EOL;
    //print_r( $argument );
  }
}
