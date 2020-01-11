<?php
namespace Yaw;
use Yaw\Event\Libevent as Libevent;
use Yaw\Event\Select;
use Yaw\Connection\Tcp;

class Core {

    const STATUS_RELOAD  = 'reload';
    const STATUS_RUNNING = 'running';

    public $onReload = null;
    public $onStart  = null;

    /*
     * Yaw配置
     */
    public static $a_config = array(
        'daemonize'  => false,
        'worker_num' => 4,
        'log_file'   => './Log',
        'master_pid_file' => './master_process.pid',
        'worker_pid_file' => './worker_process.pid',
    );

    /*
     * Master进程PID
     */
    private static $i_master_pid = 0;

    /*
     * Worker-PID数组
     */
    private static $a_worker_pid = array();

    /*
     * @desc : action动作
     */
    private static $s_action_status = '';

    /*
     * @desc : map
     */
    private static $a_instance_map = array();

    /*
     * @desc : listen_socket
     * */
    public static $i_listen_socket = null;

    /*
     * @desc : event-loop
     * */
    public static $o_event_loop = null;

    /*
     * @desc : 当前实例的worker子进程map
     */
    private static $a_worker_map = array();

    public function __construct() {
        $s_object_hash_id = spl_object_hash( $this );
        self::$a_instance_map[ $s_object_hash_id ] = $this;
        self::$a_worker_map[ $s_object_hash_id ]   = array();
    }

    /*
     * @desc : 启动服务.
     */
    public static function start() {
        self::check_env();
        self::parse_command();
        self::daemonize();
        self::create_socket();
        self::fork_all_workers();
        self::install_signal_handler();
        // 主进程陷入监听子进程状态
        self::monitor();
    }

    /*
     * @desc : 检查基础环境
     */
    public static function check_env() {
        $s_os_name = strtolower( php_uname( "s" ) );
        if ( 'linux' !== $s_os_name ) {
            exit( "目前只支持linux系统".PHP_EOL );
        }
        if ( !extension_loaded( 'pcntl' ) ) {
            exit( "缺少pcntl扩展".PHP_EOL );
        }
        if ( !extension_loaded( 'posix' ) ) {
            exit( "缺少posix扩展".PHP_EOL );
        }
    }

    /*
     * @desc : 解析命令行中的命令
     */
    public static function parse_command() {
        global $argc, $argv;
        if ( $argc != 2 && $argc != 3 ) {
            self::usage();
            exit();
        }
        /*
         Array(
           [0] => Index.php
           [1] => start  // action
           [2] => -d     // action option
         )
         */
        $a_valid_action  = array( 'start', 'stop', 'reload', 'restart', 'status' );
        $s_action        = $argv[ 1 ];
        $s_action_option = isset( $argv[ 2 ] ) ? $argv[ 2 ] : '' ;
        if ( !in_array( $s_action, $a_valid_action ) ) {
            self::usage();
            exit();
        }
        switch ( $s_action ) {
            case 'start':
                if ( '-d' === $s_action_option ) {
                    self::$a_config['daemonize'] = true;
                }
                break;
            // stop直接利用kill指令强行杀死所有进程
            case 'stop':
                $i_master_pid = file_get_contents( self::$a_config['master_pid_file'] );
                $s_worker_pid = file_get_contents( self::$a_config['worker_pid_file'] );
                $a_worker_pid = json_decode( $s_worker_pid, true );
                posix_kill( $i_master_pid, SIGKILL );
                foreach ( $a_worker_pid as $i_pid_item ) {
                    posix_kill( $i_pid_item, SIGKILL );
                }
                @unlink( self::$a_config['master_pid_file'] );
                @unlink( self::$a_config['worker_pid_file'] );
                exit;
                break;
            // reload需要逐渐杀死worker进程并再逐个重新拉起worker进程
            case 'reload':
                // graceful 优雅reload
                //if ( '-g' === $s_action_option ) {
                //$i_master_pid = file_get_contents( self::$a_config['master_pid_file'] );
                //posix_kill( $i_master_pid, SIGUSR1 );
                //}
                // 向master进程发送SIGUSR2
                $i_master_pid = file_get_contents( self::$a_config['master_pid_file'] );
                posix_kill( $i_master_pid, SIGUSR2 );
                exit;
                break;
            case 'restart':
                exit('等待开发...'.PHP_EOL);
                exit;
                break;
            case 'status':
                exit('等待开发...'.PHP_EOL);
                exit;
                break;
        }
    }

    /*
     * @desc : 创建socket服务
     * */
    public static function create_socket() {
        // 首先创建socket
        $i_host = '0.0.0.0';
        $i_port = 6666;
        $i_listen_socket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
        socket_set_option( $i_listen_socket, SOL_SOCKET, SO_REUSEPORT, 1 );
        socket_set_option( $i_listen_socket, SOL_SOCKET, SO_REUSEADDR, 1 );
        socket_set_option( $i_listen_socket, SOL_TCP, TCP_NODELAY, 1 );
        socket_set_nonblock( $i_listen_socket );
        socket_bind( $i_listen_socket, $i_host, $i_port );
        socket_listen( $i_listen_socket );
        self::$i_listen_socket = $i_listen_socket;
    }

    /*
     * @desc : 按照配置fork出子进程.
     */
    public static function fork_all_workers() {
        static::$i_master_pid = posix_getpid();
        $i_worker_num         = self::$a_config['worker_num'];
        // foreach所有实例
        foreach( self::$a_instance_map as $o_key => $o_instance ) {
            $i_exists_worker_num    = count( self::$a_worker_map[ $o_key ] );
            $i_need_fork_worker_num = $i_worker_num - $i_exists_worker_num;
            for ( $i = 1; $i <= $i_need_fork_worker_num; $i++ ) {
                $i_pid = pcntl_fork();
                if ( $i_pid < 0 ) {
                    exit( "fork err".PHP_EOL );
                } else if ( 0 == $i_pid ) {
                    cli_set_process_title( "Yaw Worker Process" );
                    // 其次创建event-loop对象
                    $o_event_loop       = new Libevent();
                    $o_event_loop->test = posix_getpid();
                    self::$o_event_loop = $o_event_loop;
                    // 每个子进程陷入事件循环
                    // 实际上每个子进程会自动继承父进程中创建的listen_socket
                    $f_callback = "\Yaw\Core::acceptConnect";
                    self::$o_event_loop->add( self::$i_listen_socket, Libevent::EV_READ, $f_callback );
                    self::$o_event_loop->loop();
                } else if ( 0 < $i_pid  ) {
                    // a_worker_pid
                    self::$a_worker_pid[ $i_pid ] = $i_pid;
                    // a_worker_map
                    self::$a_worker_map[ $o_key ][ $i_pid ] = $i_pid;
                }
            }
        }
        // 记录mater-pid和worker-pid
        @unlink( self::$a_config['master_pid_file'] );
        @unlink( self::$a_config['worker_pid_file'] );
        file_put_contents( self::$a_config['master_pid_file'], posix_getpid() );
        file_put_contents( self::$a_config['worker_pid_file'], json_encode( self::$a_worker_pid ) );
        cli_set_process_title( "Yaw Master Process" );
    }

    /*
     * @desc : accept系统调用
     * */
    public static function acceptConnect() {
        $o_tcp_conn = new Tcp();
        $o_tcp_conn->accept();
        //$r_client_socket = socket_accept( self::$i_listen_socket );
        //$ret = socket_recv( $r_client_socket, $recv_content, 2048, 0 );
        //echo $recv_content;
        //sleep(1);
    }

    /*
     * @desc : daemon化
     */
    public function daemonize() {
        if ( false === self::$a_config['daemonize'] ) {
            return;
        }
        umask( 0 );
        $i_pid = pcntl_fork();
        if ( $i_pid < 0 ) {
            exit( "fork err".PHP_EOL );
        }
        if ( $i_pid > 0 ) {
            exit();
        }
        if ( posix_setsid() < 0 ) {
            exit( "setsid err".PHP_EOL );
        }
        $i_pid = pcntl_fork();
        if ( $i_pid > 0 ) {
            exit();
        }
        //fclose( STDOUT );
    }

    /*
     * @desc : 安装信号处理器 master process
     */
    public static function install_signal_handler() {
        //$b_ret = pcntl_async_signals( true );
        $f_signal_handler = '\Yaw\Core::signal_handler';
        //pcntl_signal( SIGCHLD, $f_signal_handler, false );
        //pcntl_signal( SIGUSR1, $f_signal_handler, false );
        pcntl_signal( SIGUSR2, $f_signal_handler, false );
    }

    /*
     * @desc : 响应各种信号 master process
     */
    public static function signal_handler( $i_signo ) {
        switch ( $i_signo ) {
            // 清理僵尸进程
            case SIGCHLD:
                //foreach ( self::$a_worker_pid as $i_worker_pid ) {
                //$i_exit_pid = pcntl_wait( $i_status, WNOHANG | WUNTRACED );
                //self::write_log( "{$i_exit_pid}号子进程已结束，主进程回收" );
                //}
                break;
            // reload all worker
            case SIGUSR2:
                self::write_log( "reload action" );
                self::reload();
                break;
        }
    }

    /*
     * @desc : 主进程陷入monitor子进程循环中.
     */
    public static function monitor() {
        while ( true ) {
            // 判断PHP版本.大于7.1可以用pcntl_async_signal()了
            // 小于7.1依然用pcntl_signal_dispatch()
            if ( function_exists( 'pcntl_async_signals' ) ) {
                pcntl_async_signals( true );
                $i_pid = pcntl_wait( $i_status, WUNTRACED );
            }
            else {
                pcntl_signal_dispatch();
                $i_pid = pcntl_wait( $i_status, WUNTRACED );
                pcntl_signal_dispatch();
            }
            if ( $i_pid > 0 ) {
                // 如果当前action指令为reload的话，回收了老进程的同时记得还要fork出来..
                if ( self::STATUS_RELOAD === self::$s_action_status ) {
                    //echo ' func monitor : status='.self::$s_action_status.' | pid ='.$i_pid.PHP_EOL;
                    //sleep( 1 );
                    self::fork_all_workers();
                    //self::reload();
                }
            }
        }
    }

    /*
     * @desc : reload
     */
    public static function reload() {
        self::$s_action_status = "reload";
        $i_master_pid = static::$i_master_pid;
        // master进程中...
        if ( $i_master_pid == posix_getpid() ) {
            // master进程是不会reload的，reload的只有worker进程
            // 获取所有instance
            foreach( self::$a_instance_map as $s_object_hash_key => $o_instance ) {
                // 获取instance的所有子进程pid
                $a_worker_pid = self::$a_worker_map[ $s_object_hash_key ];
                foreach( $a_worker_pid as $i_worker_pid ) {
                    // 触发回调
                    if ( null != $o_instance->onReload ) {
                        call_user_func( $o_instance->onReload, $o_instance );
                    }
                    unset( self::$a_worker_pid[ $i_worker_pid ] );
                    // 同时将该worker的pid从a_worker_pid数组中清除
                    unset( self::$a_worker_map[ $s_object_hash_key ][ $i_worker_pid ] );
                    posix_kill( $i_worker_pid, SIGTERM );
                }
            }
        }
        // child进程中...
        //else {
        // 触发worker的onReload回调
        //foreach( self::$a_instance_map as $o_instance ) {
        //if ( null != $o_instance->onReload ) {
        //call_user_func( $o_instance->onReload, $o_instance );
        //}
        //}
        //}
    }

    /*
     * @desc : 记录系统log
     */
    public static function write_log( $s_content ) {
        if ( !is_dir( self::$a_config['log_file'] ) ) {
            mkdir( self::$a_config['log_file'], 0755 );
        }
        $s_log_file = self::$a_config['log_file'].DS.date("Ymd").'.log';
        $s_date     = date( "Y-m-d H:i:s" );
        $s_content  = $s_date.' | '.$s_content.PHP_EOL;
        file_put_contents( $s_log_file, $s_content, FILE_APPEND );
    }

    /*
     * @desc : 使用方法
     */
    public static function usage() {
        echo PHP_EOL;
        echo '-------------------------------'.PHP_EOL;
        echo "1、php index.php start".PHP_EOL;
        echo "2、php index.php start -d".PHP_EOL;
        echo "3、php index.php stop".PHP_EOL;
        echo "3、php index.php reload".PHP_EOL;
        echo '-------------------------------'.PHP_EOL;
        echo PHP_EOL;
    }

}
