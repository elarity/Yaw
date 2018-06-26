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
    'reactorNum' => 4,
    'workerNum' => 1,
    'daemon' => false,
  );

  public static $protocol = '';
  protected static $host = '0.0.0.0';
  protected static $port = 9999;

  /*
   *
   */
  //protected static $eventEmitter = null;

  /*
   * @desc : 初始化core
   */
  public function __construct( $protocol, $host = '0.0.0.0', $port = 9999 ){
    self::$protocol = $protocol;
    self::$host = $host;
    self::$port = $port;

    // 获取事件监听器
    self::$eventLoop = Factory::create(); 

    //self::$eventEmitter = new eventEmitter();
		//global $argv;
		//print_r( $argv );

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

  /**/
  private static function forkReactorProcess(){
    // 创建消息队列
		$msgQueueKey = ftok( ROOT, 'a' );
		$msgQueue = msg_get_queue( $msgQueueKey, 0666 );

    // master进程 
    cli_set_process_title( 'Yaw Master Process' );
    //echo 'master : '.posix_getpid().PHP_EOL;
		// 将master进程pid写入到pid文件中
	  file_put_contents( ROOT."Run".DS."master.pid", posix_getpid() );	
    //reactor进程，由master进程fork出来
    for( $i = 1; $i <= self::$setting['reactorNum']; $i++ ){
      $pid = pcntl_fork();
      if( $pid == 0 ){
        cli_set_process_title( 'Yaw Reactor Process' );
        $listenSocket = self::$listenSocket; 
        $eventLoop = self::$eventLoop;
        //每个reactor进程都进入 事件循环
        $eventLoop->add( $listenSocket, \Event::READ, array( '\System\Core', 'acceptTcpConnect' ) );
 	      $eventLoop->loop();
			}
			else if( $pid > 0 ){
				$reactorPidArr[] = $pid;
			}
			else if( $pid < 0 ){
        throw new \Exception( 'pcntl_fork error.'.PHP_EOL );
      }
    }
		// 将reactor进程的pid写入到pid文件
	  file_put_contents( ROOT."Run".DS."reactor.pid", json_encode( $reactorPidArr ) );	


    //worker进程 由master进程fork出来
    for( $i = 1; $i <= self::$setting['workerNum']; $i++ ){
      $pid = pcntl_fork();
      if( $pid == 0 ){
        cli_set_process_title( 'Yaw Worker Process' );
				// 使worker进程进入无限循环中，阻塞在消息队列读取上
				while( true ){
					//sleep( 1 );
				  //msg_receive( $msgQueue, 0, $msgtype, 8192, $message, false, MSG_IPC_NOWAIT );	
				  msg_receive( $msgQueue, 0, $msgtype, 8192, $message );	
					echo $message.PHP_EOL;
					//msg_remove_queue( $msgQueue );
				}
			}
			else if( $pid > 0 ){
				//echo posix_getpid().PHP_EOL;
				// 依然是master进程内
				$workerPidArr[] = $pid;
			}
			else if( $pid < 0 ){
        throw new \Exception( 'pcntl_fork error.'.PHP_EOL );
      }
		}
		// 将worker进程的pid写入到pid文件
	  file_put_contents( ROOT."Run".DS."worker.pid", json_encode( $workerPidArr ) );	

  }

  public function start(){
		//self::displayUi();
		self::parseCommand();
    //self::daemonize();
    self::createListenSocket();
    self::forkReactorProcess(); 
    // master进程
    while( true ){
      sleep( 1 );
    }
  }

	/*
	 * @desc : parse the command line arguments
	 */
	private static function parseCommand(){
		
    global $argv;
		if( count( $argv ) <= 1 ){
      self::useageUi();
			exit;
		}
    // argv结构是0=>index.php 1=>start 
		switch( $argv[ 1 ] ){
		  case 'start':
				self::displayUi();
				if( isset( $argv[ 2 ] ) && '-d' == $argv[ 2 ] ){
					self::$setting['daemon'] = true;   
				}
				break;
		  case 'reload':
				break;
		  case 'stop':
				break;
			default:
				self::useageUi();
	  		exit;
				break;
		}

	}

	private static function displayUi(){
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

	private static function useageUi(){
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
    if( ( $connectSocket = socket_accept( $listenSocket ) ) != false ){

      //$eventEmitter = self::$eventEmitter;
      //$eventEmitter->on( 'request', function(){} );
			//echo intval( $connectSocket ).PHP_EOL;

      // 创建消息队列
		  $msgQueueKey = ftok( ROOT, 'a' );
		  $msgQueue = msg_get_queue( $msgQueueKey, 0666 );
			$rs=msg_send( $msgQueue, 1, json_encode( [ 'pass' => time(), ] ) );

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
