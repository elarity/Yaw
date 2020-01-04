<?php
namespace System\Protocol;
use System\Connection\Request;

class Http{

	private static $method = array(
		'GET', 'POST'
	);

	/*
	 * @desc : 解析收到的http数据 目前只支持post 和 get两个方法
	 */
  public static function decode( $rawData ){

	  $request = new Request();	

		$server = [];
		$header = [];
		$get = [];
		$post = [];
		$files = [];
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
		if( !in_array( $requestMethod, self::$method ) ){
      return $request;
		}
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
				$rawFormData = explode( $boundary, $rawRequestBody );
				if( '' == $rawFormData[ 0 ] ){
          unset( $rawFormData[ 0 ] );
				}
				foreach( $rawFormData as $rawFormDataItem ){
					$rawFormDataItem = trim( $rawFormDataItem );
					// rawFormDataItem 数据实例
					// 文件类型
					// Content-Disposition: form-data; name="testfile"; filename="111111111111111111.png"
          // Content-Type: image/png
          //
					// �PNG
					// !����4���VA��|��};pJ�&\�nome-ׄk�5��pM�&\��>GIׄk�5��pM�&\���O q��IEND�B`�
          // 普通类型
					// Content-Disposition: form-data; name="username"
          //
					// elarity
					// 如果是文件上传数据
					if( false !== strpos( $rawFormDataItem, "filename" ) ){
					  $rawFormDataArr = explode( "\r\n", $rawFormDataItem );
						$fileKey = '';
						foreach( $rawFormDataArr as $__key => $__item ){
						  if( '' !== trim( $__item ) ){
								if( preg_match('/name="(.*?)"; filename="(.*?)"$/', $__item, $match ) ){
									$fileKey = $match[ 1 ];
									$files[ $fileKey ]['name'] = $match[ 2 ];
								} else if( false !== strpos( strtolower( $__item ), "content-type" ) ) {
									list( $contentTypeKey, $contentTypeValue ) = explode( ":", $__item );
									$files[ $fileKey ]['type'] = trim( $contentTypeValue );
								} else {
                  $files[ $fileKey ]['data'] = empty( $files[ $fileKey ]['data'] ) ? '' : $files[ $fileKey ]['data'] ;
									$files[ $fileKey ]['data'] .= $__item;
								}
					  	}
						}
					} 
					// 如果是表单数据
					else{
					  $rawFormDataArr = explode( "\r\n", $rawFormDataItem );
						if( 3 === count( $rawFormDataArr ) ){
						  preg_match( '/name="(.*?)"/', $rawFormDataArr[ 0 ], $match );
							$post[ $match[ 1 ] ] = $rawFormDataArr[ 2 ];
						}
					}
			  } 

			}
		}

		//print_r( $post );
		//print_r( $files );
		//echo PHP_EOL.PHP_EOL.PHP_EOL;

		$request->server = $server;
		$request->header = $header;
		$request->get = $get;
		$request->post = $post;
		$request->files = $files;
		$request->rawContent = $rawRequestBody;

    return $request;

  }


  /*
	 * @desc : 编码http数据 并返回
	 */
  public static function encode( $data ){

		$responseStartLine = "HTTP/1.1 200 OK\r\n";
	  
		$body = json_encode( array( 'username' => 'elarity' ) );

    $header = "";
		$header = $header."Server: Yaw Http Server"."\r\n";
		$header = $header."Content-Type: text/html\r\n";
		$header = $header."Content-Length: ".strlen( $body )."\r\n";
		$header = $header."Date: ".date("Y-m-d H:i:s")."\r\n";
    $header = $header."\r\n";
     
		$content = $responseStartLine.$header.$body;
		return $content;

  }  

	/*
	 * @desc : 设置header头部
	 */
	public static function setHeader( array $headerArr ){
	}

}
