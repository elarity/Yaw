<?php
namespace System\Protocol;
use System\Connection\Request;

class Http{

  public $server = [];

  public $header = [];

  public $get = [];

  public $post = [];

  public $rawContent = [];

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
		list( $requestMethod, $requestUri, $httpVersion ) = explode( ' ', $requestStartLine );
		// 初始化到系统常量中
		$server['METHOD'] = trim( $requestMethod );
    // 在get方法中，可能存在如下方式 http://www.x.com/api?username=pangzi
		if( false !== strpos( $requestUri, '?' ) ){
			list( $pathInfo, $queryString ) = explode( '?', $requestUri );
		} else {
		  $pathInfo = $pathInfo;	
		  $queryString = '';
		}
		$server['PATH_INFO'] = trim( $pathInfo );
		$server['QUERY_STRING'] = trim( $queryString );
		$server['HTTP_VERSION'] = trim( $httpVersion );

		// 首部，也就是header
		foreach( $rawDataArr as $item ){
			//if( '' != trim( $item ) ){
			if( false !== strpos( $item, ':' ) ){
				list( $key, $value ) = explode( ':', $item );
				$header[ strtoupper( trim( $key ) ) ] = trim( $value );
			}
		}

		// 主体 body，当然了在GET情况下直接忽略body体中的数据，但是需要解析query string
		if( '' !== $queryString ){
		  // username=elarity&password=123454&option=rem 
			$getArr = explode( '&', $queryString );
			/*
			[0] => username=elarity
      [1] => password=123454
      [2] => option=rem
      */
			foreach( $getArr as $getDataItem ){
				list( $queryKey, $queryValue ) = explode( '=', $getDataItem );
				$get[ $queryKey ] = $queryValue;
			}
		}

		// 在POST方法下，收集body信息，但是不能忽略queryString
		if( 'POST' === $requestMethod ){
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
