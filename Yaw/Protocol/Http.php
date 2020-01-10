<?php
namespace Yaw\Protocol;
/*
POST /v1/user/login?version=1.12 HTTP/1.1
Content-Type: application/x-www-form-urlencoded
User-Agent: PostmanRuntime/7.20.1
Accept:
Cache-Control: no-cache
Postman-Token: 16387e44-6fb3-469f-819f-403ce5c760f5
Host: t.ti-node.com:6666
Accept-Encoding: gzip, deflate
Content-Length: 30
Connection: keep-alive

username=xiaoy&password=123456




"GET /v1/user/login?version=1.12 HTTP/1.1
User-Agent: PostmanRuntime/7.20.1
Accept:
Cache-Control: no-cache
Postman-Token: 8bc89e78-d92e-4b19-bca3-3296a1374909
Host: t.ti-node.com:6666
Accept-Encoding: gzip, deflate
Connection: keep-alive

"
*/

class Http {
    // 定义下目前支持的http方法们，目前只支持get和post
    private static $a_method = array( 'get', 'post' );
    public static function decode( $s_raw_http_content ) {
        $s_http_method      = '';
        $s_http_version     = '';
        $s_http_pathinfo    = '';
        $s_http_querystring = '';
        $a_http_post        = array();
        $a_http_get         = array();
        $a_http_header      = array();
        // 先通过两个 \r\n\r\n 把 请求行+请求头 与 请求体 分割开来.
        list( $s_http_line_and_header, $s_http_body ) = explode( "\r\n\r\n", $s_raw_http_content );
        // 再分解$s_http_line_and_header数组
        // 数组的第一个元素一定是 请求行
        // 数组剩余所有元素就是 请求头
        $a_http_line_header = explode( "\r\n", $s_http_line_and_header );
        $s_http_line = $a_http_line_header[ 0 ];
        unset( $a_http_line_header[ 0 ] );
        $a_http_raw_header = $a_http_line_header;
        // 好了，请求行 + 请求头数组 + 请求体 都有了
        // 先从请求行分解 method + pathinfo + querystring + http版本
        list( $s_http_method, $s_http_pathinfo_querystring, $s_http_version ) = explode( ' ', $s_http_line );
        if ( false === strpos( $s_http_pathinfo_querystring, "?" ) ) {
            $s_http_pathinfo = $s_http_pathinfo_querystring;
        } else {
            list( $s_http_pathinfo, $s_http_querystring ) = explode( '?', $s_http_pathinfo_querystring );
        }
        // 处理querystring为数组
        if ( '' != $s_http_querystring ) {
            $a_raw_http_get = explode( '&', $s_http_querystring );
            foreach( $a_raw_http_get as $s_http_get_item ) {
                if ( '' != trim( $s_http_get_item ) ) {
                    list( $s_get_key, $s_get_value ) = explode( '=', $s_http_get_item );
                    $a_http_get[ $s_get_key ] = $s_get_value;
                }
            }
        }
        // 处理$s_http_header
        foreach( $a_http_raw_header as $a_raw_http_header_key => $a_raw_http_header_item ) {
            if ( '' != trim( $a_raw_http_header_item ) ) {
                list( $s_http_header_key, $s_http_header_value ) = explode( ":", $a_raw_http_header_item );
                $a_http_header[ strtoupper( $s_http_header_key ) ] = $s_http_header_value;
            }
        }
        // 如果是post方法，处理post body
        if ( 'post' === strtolower( $s_http_method ) ) {
            $a_http_raw_post = explode( "&", $s_http_body );
            foreach( $a_http_raw_post as $s_http_raw_body_item ) {
                if ( '' != $s_http_raw_body_item ) {
                    list( $s_http_raw_body_key, $s_http_raw_body_value ) = explode( "=", $s_http_raw_body_item );
                    $a_http_post[ $s_http_raw_body_key ] = $s_http_raw_body_value;
                }
            }
        }
        $a_ret = array(
            'method'   => $s_http_method,
            'version'  => $s_http_version,
            'pathinfo' => $s_http_pathinfo,
            'post'     => $a_http_post,
            'get'      => $a_http_get,
            'header'   => $a_http_header,
        );
        return $a_ret;
    }


    public static function encode( $a_data ) {
        $s_data        = json_encode( $a_data );
        $s_http_line   = "HTTP/1.1 200 OK";
        $a_http_header = array(
            "Date"           => gmdate( "M d Y H:i:s", time() ),
            "Content-Type"   => "application/json",
            "Content-Length" => strlen( $s_data ),
        );
        $s_http_header = '';
        foreach( $a_http_header as $s_http_header_key => $s_http_header_item ) {
            $_s_header_line = $s_http_header_key.': '.$s_http_header_item;
            $s_http_header  = $s_http_header.$_s_header_line."\r\n";
        }
        $s_ret = $s_http_line."\r\n".$s_http_header."\r\n".$s_data;
        return $s_ret;
    }
}