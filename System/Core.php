<?php
namespace System;
use System\Component\EventEmitter;
use System\Event\Factory;
use System\Connection\Tcp;

abstract class Core{

  /*
   * @desc : 监听socket资源句柄
   */
  protected static $listenSocket = null;

  /*
   * @desc : 事件监听器
   */
  public static $eventLoop = null;

  /*
   * @desc : 默认配置选项
   */
  protected static $setting = array(
    'reactor_num' => 1,
    'worker_num' => 4,
    'task_worker_num' => 0,
  );

  public static $protocol = '';
  protected static $host = '0.0.0.0';
  protected static $port = 9999;

  /*
   *
   */
  protected static $eventEmitter = null;

  /*
   * @desc : 初始化core
   */
  public function __construct( $protocol, $host = '0.0.0.0', $port = 9999 ){
    self::$protocol = $protocol;
    self::$host = $host;
    self::$port = $port;

    // 获取事件监听器
    self::$eventLoop = Factory::create(); 

    self::$eventEmitter = new eventEmitter();

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
        $eventLoop = self::$eventLoop;
		
        //每个reactor进程都进入 事件循环
        $eventLoop->add( $listenSocket, \Event::READ, array( '\System\Core', 'acceptTcpConnect' ) );
 	$eventLoop->loop();
   	   	
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

  /*
   * @desc : 创建监听socket
   */
  private static function createListenSocket(){
    $listenSocket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
    socket_bind( $listenSocket, self::$host, self::$port );
    socket_listen( $listenSocket );
    socket_set_nonblock( $listenSocket );
		socket_set_option( $listenSocket, SOL_SOCKET, SO_REUSEADDR, 1 );
    self::$listenSocket = $listenSocket;
  } 

  /*
   * @desc : 
   */
  public static function acceptTcpConnect( $listenSocket ){
    // 由于监听socket是非阻塞的，所以此处accept的这样处理比较优雅，用@直接抑制错误也可以，但是比较丑陋
    if( ( $connectSocket = socket_accept( $listenSocket ) ) != false ){

      //$eventEmitter = self::$eventEmitter;
      //$eventEmitter->on( 'request', function(){} );
  
      $tcp = new Tcp( $connectSocket );
      
      // socket_read是阻塞式的
      //$content = socket_read( $connectSocket, 4096 );
      //$msg = "helloworld";
      //socket_write( $connectSocket, $msg, strlen( $msg ) );

    }
  }

  public function on( $method, \Closure $closure ){
    $eventEmitter = self::$eventEmitter;
    $eventEmitter->on( $method, $closure );
  } 

  /*
   *
   */
  public function __call( $method, $argument ){
    //echo $method.PHP_EOL;
    //print_r( $argument );
  }
}
