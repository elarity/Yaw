<?php
namespace System;
use System\Event\Factory;
use System\Connection\Tcp;
use System\Component\EventEmitter; 
use System\Connection\Request;
use System\Connection\Response;
use System\Protocol\Http as Http;

abstract class Core{

  /*
   * @desc : 监听socket资源句柄
   */
  protected static $listenSocket = null;

  /*
   * @desc : 事件监听器
   */
  public static $eventLoop  = null;

  /*
   * @desc : 默认配置选项
   */
  protected static $setting = array(
    'reactorNum' => 4,
    'workerNum' => 16,
    'daemon' => false,
  );

  public    static $protocol = '';
  protected static $host     = '0.0.0.0';
  protected static $port     = 9999;

  private static $reactorPids = array();
  private static $workerPids  = array(); 

  /*
   *
   */
  //protected static $eventEmitter = null;

	public static $msgQueue = null;

  /*
   * @desc : 初始化core
   */
  public function __construct( $protocol, $host = '0.0.0.0', $port = 9999 ){
    self::$protocol = $protocol;
    self::$host     = $host;
    self::$port     = $port;

    // 获取事件监听器
    self::$eventLoop = Factory::create(); 

    //self::$eventEmitter = new eventEmitter();
		//global $argv;
		//print_r( $argv );
    // 创建消息队列
		$msgQueueKey = ftok( ROOT, 'a' );
		self::$msgQueue = msg_get_queue( $msgQueueKey, 0666 );

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
    $pid = pcntl_fork();  
    if( $pid > 0 ){
      // parent process
      exit();
    } else if( $pid < 0 ){
      throw new \Exception( 'pcntl_fork error.'.PHP_EOL );
    }
    if( !posix_setsid() ){
      throw new \Exception( 'pcntl_posix error.'.PHP_EOL );
    }
    // 再次fork，是一种传统，有人说在System V中二次fork能够100%保证子进程会失去控制终端控制权
    $pid = pcntl_fork();
    if( $pid > 0 ){
      // parent process
      exit();
    }else if( $pid < 0 ){
      throw new \Exception( 'pcntl_fork error.'.PHP_EOL );
    }
  }

  /**/
  private static function forkReactorProcess() {
    // master进程 
    cli_set_process_title( 'Yaw Master Process' );
    //reactor进程，由master进程fork出来
    for ( $i = 1; $i <= self::$setting['reactorNum']; $i++ ) {
      $pid = pcntl_fork();
      if ( 0 == $pid ) {
        cli_set_process_title( 'Yaw Reactor Process' );
        $listenSocket = self::$listenSocket; 
        $eventLoop    = self::$eventLoop;
        //每个reactor进程都进入 事件循环
        $eventLoop->add( $listenSocket, \Event::READ, array( '\System\Core', 'acceptTcpConnect' ) );
 	      $eventLoop->loop();
			}
			else if ( $pid > 0 ) {
				$reactorPidArr[] = $pid;
			}
			else if ( $pid < 0 ) {
        throw new \Exception( 'pcntl_fork error.'.PHP_EOL );
      }
    }
    //worker进程 由master进程fork出来
    for ( $i = 1; $i <= self::$setting['workerNum']; $i++ ) {
      $pid = pcntl_fork();
      if ( 0 == $pid ) {
        cli_set_process_title( 'Yaw Worker Process' );
				// 使worker进程进入无限循环中，阻塞在消息队列读取上
				while ( true ) {
				  msg_receive( self::$msgQueue, 0, $msgtype, 8192, $message );	
					// 删除消息队列 别手贱
					//msg_remove_queue( $msgQueue );
					if ( 'http' == self::$protocol ) {
					  $request  = Http::decode( $message );
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
			}
			else if ( $pid > 0 ) {
				// 依然是master进程内
				$workerPidArr[] = $pid;
			}
			else if ( $pid < 0 ) {
        throw new \Exception( 'pcntl_fork error.'.PHP_EOL );
      }
		}
    // 将reactor和worker的pids数组保存到静态变量中
    self::$reactorPids = $reactorPidArr;
    self::$workerPids  = $workerPidArr;
    //print_r( self::$reactorPids );
    //print_r( self::$workerPids );
    if ( true == self::$setting['daemon'] ) {
		  // 将master进程pid写入到pid文件中
	    file_put_contents( ROOT."Run".DS."master.pid", posix_getpid() );	
		  // 将reactor进程的pid写入到pid文件
	    file_put_contents( ROOT."Run".DS."reactor.pid", json_encode( $reactorPidArr ) );	
		  // 将worker进程的pid写入到pid文件
	    file_put_contents( ROOT."Run".DS."worker.pid", json_encode( $workerPidArr ) );	
    }

  }

  public function start(){
		self::parseCommand();
    self::init();
		//self::displayUi();
    if ( true == self::$setting['daemon'] ) {
      self::daemonize();
    }
    self::createListenSocket();
    self::forkReactorProcess(); 
    // master进程进入无限循环，监控reactor和worker进程
    while( true ){
      sleep( 1 );
      // 循环reactor pids，避免僵尸进程 
      foreach ( self::$reactorPids as $_reactorKey => $_reactorPid ) {
        $iWaitRet = pcntl_waitpid( $_reactorPid, $status, WNOHANG); 
        if ( $iWaitRet > 0 ) {
          unset( self::$reactorPids[ $_reactorKey ] );
        }
      }
      // 循环worker pids，避免僵尸进程 
      foreach ( self::$workerPids as $_workerKey => $_workerPid ) {
        $iWaitRet = pcntl_waitpid( $_workerPid, $status, WNOHANG); 
        if ( $iWaitRet > 0 ) {
          unset( self::$workerPids[ $_workerKey ] );
        }
      }
    }
  }

	/*
	 * @desc : parse the command line arguments
	 */
	private static function parseCommand() {
    global $argv;
		if ( count( $argv ) <= 1 ) {
      self::useageUi();
			exit;
		}
    // argv结构是 : 0=>index.php , 1=>start 
		switch ( $argv[ 1 ] ) {
		  case 'start':
				self::displayUi();
				if ( isset( $argv[ 2 ] ) && '-d' == $argv[ 2 ] ) {
					self::$setting['daemon'] = true;   
				}
				break;
		  case 'reload':
        exit( "暂时未能支持，马上加入".PHP_EOL );
				break;
		  case 'stop':
        // 结束worker进程
        $workerPids = json_decode( file_get_contents( ROOT."Run".DS."worker.pid" ), true );
        foreach ( $workerPids as $_workerKey => $_workerPid ) {
          posix_kill( $_workerPid, SIGKILL );
        } 
        @unlink( ROOT."Run".DS."worker.pid" );
        // 结束reactor进程
        $reactorPids = json_decode( file_get_contents( ROOT."Run".DS."reactor.pid" ), true );
        foreach ( $reactorPids as $_reactorKey => $_reactorPid ) {
          posix_kill( $_reactorPid, SIGKILL );
        } 
        @unlink( ROOT."Run".DS."reactor.pid" );
        // master自杀
        $masterPid = file_get_contents( ROOT."Run".DS."master.pid" );
        posix_kill( $masterPid, SIGKILL );
        @unlink( ROOT."Run".DS."master.pid" );
        exit; 
				break;
			default:
				self::useageUi();
	  		exit;
				break;
		}
	}

	private static function displayUi() {
    echo PHP_EOL.PHP_EOL.PHP_EOL;
    echo "--------------------------------------------------------------".PHP_EOL;
		echo "|                                                            |".PHP_EOL;
		echo "|                                                            |".PHP_EOL;
    echo "|            ||    ||      /\        ||          ||          |".PHP_EOL;
    echo "|             ||  ||      /  \        ||        ||           |".PHP_EOL;
    echo "|               ||       /====\        |   ||   |            |".PHP_EOL;
    echo "|               ||      /      \       |  |  |  |            |".PHP_EOL;
    echo "|               ||     /        \       ||    ||             |".PHP_EOL;
		echo "|                                                            |".PHP_EOL;
		echo "|                                                            |".PHP_EOL;
    echo "--------------------------------------------------------------".PHP_EOL;
	}

	private static function useageUi() {
    echo PHP_EOL.PHP_EOL.PHP_EOL;
    echo "--------------------------------------------------------------".PHP_EOL;
		echo "|                                                            |".PHP_EOL;
		echo "|                                                            |".PHP_EOL;
    echo "|            ||    ||      /\        ||          ||          |".PHP_EOL;
    echo "|             ||  ||      /  \        ||        ||           |".PHP_EOL;
    echo "|               ||       /====\        |   ||   |            |".PHP_EOL;
    echo "|               ||      /      \       |  |  |  |            |".PHP_EOL;
    echo "|               ||     /        \       ||    ||             |".PHP_EOL;
		echo "|                                                            |".PHP_EOL;
		echo "|                                                            |".PHP_EOL;
    echo "--------------------------------------------------------------".PHP_EOL;
    echo 'USAGE: php index.php commond'.PHP_EOL;
    echo '1. start,以debug模式开启服务，此时服务不会以daemon形式运行'.PHP_EOL;
    echo '2. start -d,以daemon模式开启服务'.PHP_EOL;
    echo '3. status,查看服务器的状态'.PHP_EOL;
    echo '4. stop,停止服务器'.PHP_EOL;
    echo '5. reload,热加载所有业务代码'.PHP_EOL.PHP_EOL.PHP_EOL.PHP_EOL;		
	}

  private static function init() {
    // 创建Run目录
    if ( !is_dir( ROOT."Run" ) ) {
      mkdir( ROOT."Run" );
    }
  }

  /*
   * @desc : 创建监听socket
   */
  private static function createListenSocket(){
    $listenSocket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
		// 要在bind之前执行，这句是为了解决刚停服后，再次启动时候出现 address already in use 的异常
		socket_set_option( $listenSocket, SOL_SOCKET, SO_REUSEADDR, 1 );
    socket_bind( $listenSocket, self::$host, self::$port );
    socket_listen( $listenSocket );
    socket_set_nonblock( $listenSocket );
    self::$listenSocket = $listenSocket;
  } 

  /*
   * @desc : 
   */
  public static function acceptTcpConnect( $listenSocket ){
    // 由于监听socket是非阻塞的，所以此处accept的这样处理比较优雅，用@直接抑制错误也可以，但是比较丑陋
    if ( ( $connectSocket = socket_accept( $listenSocket ) ) != false ) {
      //$eventEmitter = self::$eventEmitter;
      //$eventEmitter->on( 'request', function(){} );
			//echo intval( $connectSocket ).PHP_EOL;

      // 创建消息队列
			//$rs = msg_send( self::$msgQueue, 1, json_encode( [ 'pass' => time(), ] ) );

      $tcp = new Tcp( $connectSocket );

		  //$msg = 'msg';
		  //var_dump( socket_write( $connectSocket, $msg, strlen( $msg ) ) );
		  //socket_close( $connectSocket );
      // socket_read是阻塞式的
      //$content = socket_read( $connectSocket, 4096 );
      //$msg = "helloworld";
      //socket_write( $connectSocket, $msg, strlen( $msg ) );

    }
  }

	/*
  public function on( $method, \Closure $closure ){
    $eventEmitter = self::$eventEmitter;
    $eventEmitter->on( $method, $closure );
  } 
	 */

  /*
   *
   */
  //public function __call( $method, $argument ){
    //echo $method.PHP_EOL;
    //print_r( $argument );
  //}
}
