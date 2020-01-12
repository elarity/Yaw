<?php
namespace Yaw\Event;
use Yaw\Core;

class Select implements EventInterface {

    public $a_client    = array();
    public $a_read      = array();
    public $a_write     = array();
    public $a_exception = array();
    public $a_read_cb      = array();
    public $a_write_cb     = array();
    public $a_exception_cb = array();

    public function __construct() {}

    /*
     * @desc  : 添加一个事件
     * @param : socket fd
     * @param : event type, EV_READ EV_WRITE
     * @param : callback
     * */
    public function add( $r_fd, $i_event_type, $f_callback ) {
        $i_fd = intval( $r_fd );
        if ( false === in_array( $r_fd, $this->a_client ) ) {
            $this->a_client[] = $r_fd;
        }
        if ( self::EV_READ == $i_event_type ) {
            $this->a_read[] = $r_fd;
            $this->a_read_cb[ $i_fd ] = $f_callback;
        }
        if ( self::EV_WRITE == $i_event_type ) {
            $this->a_write[] = $r_fd;
            $this->a_write_cb[ $i_fd ] = $f_callback;
        }
        if ( self::EV_EXCEPTION == $i_event_type ) {
            $this->a_exception[] = $r_fd;
            $this->a_exception_cb[ $i_fd ] = $f_callback;
        }
    }

    /*
     * @desc : 删除一个事件
     * */
    public function del( $r_fd, $i_event_type ) {
        $i_fd  = intval( $r_fd );
        $i_key = array_search( $r_fd, $this->a_client );
        unset( $this->a_client[ $i_key ] );
        $i_key = array_search( $r_fd, $this->a_read );
        unset( $this->a_read[ $i_key ] );
        unset( $this->a_read_cb[ $i_fd ] );
        $i_key = array_search( $r_fd, $this->a_write );
        unset( $this->a_write[ $i_key ] );
        unset( $this->a_write_fd[ $i_fd ] );
        $i_key = array_search( $r_fd, $this->a_write );
        unset( $this->a_write[ $i_key ] );
        unset( $this->a_write_fd[ $i_fd ] );
    }

    /*
     * @desc : 陷入事件循环
     * */
    public function loop() {
        while ( true ) {
            sleep( 1 );
            $this->a_read = $this->a_client;
            $i_loop_ret = socket_select( $this->a_read, $this->a_write, $this->a_exception, NULL );
            print_r( $this->a_write );
            if ( $i_loop_ret <= 0 ) {
                continue;
            }
            // 判断listen-socket是否在可读序列中
            if ( in_array( Core::$i_listen_socket, $this->a_read ) ) {
                // 触发回调
                $f_callback = $this->a_read_cb[ intval( Core::$i_listen_socket ) ];
                call_user_func( $f_callback );
                // 手工清除一下listen_socket
                $i_key = array_search( Core::$i_listen_socket, $this->a_read );
                unset( $this->a_read[ $i_key ] );
            }
            // 遍历其他connection-socket
            foreach ( $this->a_read as $r_read_fd ) {
                $f_callback = $this->a_read_cb[ intval( $r_read_fd ) ];
                call_user_func( $f_callback );
            }
            // 遍历其他connection-socket
            foreach ( $this->a_write as $r_write_fd ) {
                $f_callback = $this->a_write_cb[ intval( $r_write_fd ) ];
                call_user_func( $f_callback );
            }
        }
    }
}