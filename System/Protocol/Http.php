<?php
namespace System\Protocol;
use System\Connection\Request;

class Http{

  public $server = [];

  public $header = [];

  public $get = [];

  public $post = [];

  public $rawContent = [];

	public function end(){

	}

  public function decode( $rawData ){

		$server = [];
		$header = [];
		$get = [];
		$post = [];
		$rawContent = '';

		// 将原始数据使用 PHP_EOL 分割,目前我还不知道使用PHP_EOL会不会有什么问题
		$rawDataArr = explode( PHP_EOL, $rawData );

		// 请求行,或者 我个人称为 请求起始行
		$requestStartLine = $rawDataArr[ 0 ];
		unset( $rawDataArr[ 0 ] );
		list( $requestMethod, $requestPathInfo, $httpVersion ) = explode( ' ', $requestStartLine );

		$server['METHOD'] = trim( $requestMethod );
		$server['PATH_INFO'] = trim( $requestPathInfo );
		$server['HTTP_VERSION'] = trim( $httpVersion );

		// 首部，也就是header
		foreach( $rawDataArr as $item ){
			//if( '' != trim( $item ) ){
			if( false !== strpos( $item, ':' ) ){
				list( $key, $value ) = explode( ':', $item );
				$header[ strtoupper( trim( $key ) ) ] = trim( $value );
			}
		}

		if( 'GET' === $requestMethod ){
			//print_r( $header );
			//print_r( $server );
			// 
		}
		else if( 'POST' === $requestMethod ){
		  // 主体
	  	$requestBody = array_pop( $rawDataArr );
		}
      
	  $request = new Request();	
		$request->server = $server;
		$request->header = $header;
		$request->get = $get;
		$request->post = $post;
		$request->rawContent = $rawContent;

    return $request;

  }

  public function encode(){

		 $responseStartLine = "HTTP/1.1 200 OK".PHP_EOL;
	  
		 $body = json_encode( array( 'username' => 'elarity' ) );

     $header = "";
		 $header = $header."Content-Type: text/html".PHP_EOL;
		 $header = $header."Content-Length: ".strlen( $body ).PHP_EOL;
     $header = $header.PHP_EOL;

		 $content = $responseStartLine.$header.$body;

		 return $content;

  }  


}
