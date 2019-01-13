<?php
/*
 * 检查必须的基础运行环境
 */
function checkEnv(){
	if( 'cli' !== php_sapi_name() ){
		throw new Exception('服务需要运行在php cli环境中');
	}
	if( !extension_loaded('sockets') ){
		throw new Exception('缺少sockets扩展');
	}
	if( !extension_loaded('sysvmsg') ){
		throw new Exception('缺少sysvmsg扩展');
	}
	if( !extension_loaded('event') ){
		throw new Exception('缺少event扩展');
	}
}
checkEnv();

define( 'DS', DIRECTORY_SEPARATOR );
define( 'ROOT', __DIR__.DS );

function autoload( $className ){
  $ext = '.php';
  $className = str_replace( '\\', DS, $className );
  $fullFilePath = ROOT.$className.$ext;
  require_once( $fullFilePath );
}
spl_autoload_register( 'autoload' );

// 启动一个http服务器
$server = new System\Http( '0.0.0.0', 6666 );
$server->set( array(
) );

$server->on( 'request', function( $request, $response ) {
  $response->end('hello world'.PHP_EOL);
} );
$server->start();


/*
$server->on( 'connect', function(){

} );
$server->on( 'receive', function(){

} );
*/

