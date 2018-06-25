<?php
namespace System\Protocol;
use System\Connection\Request;

class Http{

  public function decode( $rawData ){

		$server = [];
		$header = [];
		$get = [];
		$post = [];
		$rawContent = '';

		// 将原始数据使用 PHP_EOL 分割,目前我还不知道使用PHP_EOL会不会有什么问题
		// 所以，还得再分才行，在用"\r\n"将 请求行 和 请求头 分开
		/*
			0 => request-line\r\n
			     request-header
      1 => request-body
		 */
		$rawDataArr = explode( "\r\n\r\n", $rawData, 2 );

		// 取出请求体
		$rawRequestBody = trim( $rawDataArr[ 1 ] );
		
		// 取出 请求行 和 请求头
    $requestHeader = explode( "\r\n", $rawDataArr[ 0 ] );
	  $requestStartLine = $requestHeader[ 0 ]; 	
		unset( $requestHeader[ 0 ] );

		// 请求行,或者 我个人称为 请求起始行 request line
		list( $requestMethod, $requestUri, $httpVersion ) = explode( ' ', $requestStartLine );
		// 初始化到系统常量中
		$server['METHOD'] = trim( $requestMethod );
    // 在get方法中，可能存在如下方式 http://www.x.com/api?username=pangzi
		if( false !== strpos( $requestUri, '?' ) ){
			list( $pathInfo, $queryString ) = explode( '?', $requestUri );
		} else {
		  $pathInfo = '';
		  $queryString = '';
		}
		$server['PATH_INFO'] = trim( $pathInfo );
		$server['QUERY_STRING'] = trim( $queryString );
		$server['HTTP_VERSION'] = trim( $httpVersion );
    
		// 首部，也就是http header
		foreach( $requestHeader as $item ){
			if( false !== strpos( $item, ':' ) ){
				list( $key, $value ) = explode( ':', $item );
				$key = strtoupper( $key );
				switch( $key ){
				  case 'CONTENT-TYPE':
						if( !preg_match( '/boundary="?(\S+)"?/', $value, $match ) ){
						  $header[ $key ] = trim( $value );
						} else {
							//print_r( $match );
						  $header[ $key ] = 'multipart/form-data';
							$boundary = '--'.trim( $match[ 1 ] );
							//echo $boundary.PHP_EOL;
						}
					  break;
					default:
				    $header[ strtoupper( trim( $key ) ) ] = trim( $value );
					  break;
				}
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
			// 判断 content-type 
			if( 'application/x-www-form-urlencoded' == $header['CONTENT-TYPE'] ){
				// 数据样式案例： user=etc&password=12345
				if( '' != $rawRequestBody ){
				  $postKv = explode( '&', $rawRequestBody );
				  foreach( $postKv as $_item ){
						list( $postKey, $postValue ) = explode( '=', $_item );
						$post[ trim( $postKey ) ] = trim( $postValue );
				  }	
				}
			}else if( 'multipart/form-data' == $header['CONTENT-TYPE'] ){
				// form-data 中的数据是这样的
				//print_r( explode( $boundary, $rawRequestBody ) );



			}
		}
      
	  $request = new Request();	
		$request->server = $server;
		$request->header = $header;
		$request->get = $get;
		$request->post = $post;
		$request->rawContent = $rawRequestBody;
		//print_r( $request );

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
