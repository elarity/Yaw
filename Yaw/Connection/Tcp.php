<?php
namespace Yaw\Connection;
use Yaw\Core;
use Yaw\Protocol\Http;
use Yaw\Event\EventInterface;
use Yaw\Event\Libevent;
use Yaw\Event\Select;

class Tcp {

    private $r_connection_socket = null;

    /*
     * @desc : accept一个链接，实际上也只有基于tcp的协议会有这个动作，udp直接飞过来
     * */
    public function accept() {
        //echo json_encode( Core::$o_event_loop->a_event ).PHP_EOL;
        // accept链接
        $r_connection_socket = socket_accept( Core::$i_listen_socket );
        //echo posix_getpid()." | ".strval( $r_connection_socket ).PHP_EOL;
        //echo Core::$o_event_loop->test.'|'.strval( $r_connection_socket ).PHP_EOL;
        if ( !$r_connection_socket ) {
            return;
        }
        $this->r_connection_socket = $r_connection_socket;
        Core::$o_event_loop->add( $this->r_connection_socket, EventInterface::EV_READ, array( $this, "read" ) );
    }

    /*
     * @desc : 从connection中读取数据
     * */
    public function read() {
        // 读取数据
        socket_recv( $this->r_connection_socket, $s_recv_content, 2048, 0 );
        //echo "收到：{$s_recv_content}";
        // 设置ev_write
        Core::$o_event_loop->add( $this->r_connection_socket, EventInterface::EV_WRITE, array( $this, "write" ) );
        // 解析http协议
        $a_http_decode_content = Http::decode( $s_recv_content );
        // 触发onMessage回调
        $o_yaw_instance = Core::$o_instance;
        call_user_func( $o_yaw_instance->onMessage, $this->r_connection_socket, $a_http_decode_content );
    }

    /*
     * @desc : 向connection中写入内容
     * */
    public function write() {
        //echo "发送 pid:".Core::$o_event_loop->test." socket:".$this->r_connection_socket.PHP_EOL;
        $ret = Http::encode( array(
            'pid' => Core::$o_event_loop->test,
        ) );
        socket_write( $this->r_connection_socket, $ret, strlen( $ret ) );
        Core::$o_event_loop->del( $this->r_connection_socket, EventInterface::EV_ALL );
        //sleep(1);
        //socket_write( $this->r_connection_socket, "hi", strlen( "hi" ) );
        //echo json_encode( Core::$o_event_loop->a_event ).PHP_EOL;
        //unset( Core::$o_event_loop->a_event[ intval( $this->r_connection_socket ) ] );
        //socket_close( $this->r_connection_socket );
    }

}