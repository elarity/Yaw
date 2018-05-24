<?php

define( 'DS', DIRECTORY_SEPARATOR );
define( 'ROOT', __DIR__.DS );

function autoload( $className ){
  $ext = '.php';
  $className = str_replace( '\\', DS, $className );
  $fullFilePath = ROOT.$className.$ext;
  require_once( $fullFilePath );
}
spl_autoload_register( 'autoload' );

/*
$protocol = 'Http';
$protocolClass = "System\\Protocol\\".$protocol;
$protocolParser = new $protocolClass;
print_r( $protocolParser );
exit;
*/

// 启动一个http服务器
$server = new System\Http( '0.0.0.0', 9999 );
$server->set( array(
) );

$server->on( 'request', function(){
  echo "hello".PHP_EOL;
} );
$server->start();


/*
$server->on( 'connect', function(){

} );
$server->on( 'receive', function(){

} );
*/

