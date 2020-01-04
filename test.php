<?php
$host = '0.0.0.0';
$port = 9999;
// 创建一个tcp socket
$listen_socket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
// 将socket bind到IP：port上
socket_bind( $listen_socket, $host, $port );
// 开始监听socket
socket_listen( $listen_socket );
while( true ){
  // 所以你不用担心while循环会将机器拖垮，不会的 
  $connection_socket = socket_accept( $listen_socket );
  // 从客户端读取信息
  //$len = socket_recv( $connection_socket, $content, 6, MSG_WAITALL );
  $total_len = 8;
  $recv_len  = 0;
  $recv_content = '';
  $len = socket_recv( $connection_socket, $content, $total_len, MSG_DONTWAIT );
  while ( $recv_len < $total_len ) {
    $len = socket_recv( $connection_socket, $content, ( $total_len - $recv_len ), MSG_DONTWAIT );
    $recv_len = $recv_len + $len;
    echo $recv_len.':'.$total_len.PHP_EOL;
    if ( $recv_len > 0 ) {
      $recv_content = $recv_content.$content;
    }
  }
  echo "从客户端获取：{$recv_content}"; 
  // 向客户端发送一个helloworld
  $msg = "helloworld\r\n";
  socket_write( $connection_socket, $msg, strlen( $msg ) );
  socket_close( $connection_socket );
}
socket_close( $listen_socket );
