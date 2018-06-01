<?php
namespace System\Protocol;
use System\Connection\Response;

class Http{

  //private $

  public function decode( $rawData ){

		$server = [];
		$header = [];
		$get = [];
		$post = [];
		$rawContent = '';

		echo $rawData.PHP_EOL;

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
			if( '' != trim( $item ) ){
				list( $key, $value ) = explode( ':', $item );
				$header[ strtoupper( trim( $key ) ) ] = trim( $value );
			}
		}

		if( 'GET' === $requestMethod ){
			print_r( $header );
			print_r( $server );
			// 
		}
		else if( 'POST' === $requestMethod ){
		  // 主体
	  	$requestBody = array_pop( $rawDataArr );
		}
      
	  $response = new Response();	
		$response->server = $server;
		$response->header = $header;
		$response->get = $get;
		$response->post = $post;
		$response->rawContent = $rawContent;

    return $response;
  }

  public function encode(){
  }  


}
