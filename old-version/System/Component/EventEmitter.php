<?php
namespace System\Component;
/*
 * A PHP event library
 * 看起来就像jQuery
 * 用起来也像phper二流子码jQuery
 */
class EventEmitter{
 
  /*
   * 事件=》回调函数 绑定对应数组
   */
  private static $events = [];
  
  /*
   * 一次性事件回调 
   */
  private static $once_events = [];

  /*
   * @desc : 将一个事件 和 回调函数绑定起来, 一个事件可以绑定多个不同的回调函数
   * @param : event, string字符串，事件名称
   * @param : callback, Closure类型，也就是php匿名函数
   */
  public static function bind( $event, \Closure $callback ){
		self::$events[ $event ][] = $callback;    
  }

  /*
   * @desc : 将一个事件 和 回调函数绑定起来, 一个事件可以绑定多个不同的回调函数
             但是，只会触发一次，之后便不再生效
   * @param : event, string字符串，事件名称
   * @param : callback, Closure类型，也就是php匿名函数
   */
  public static function once_bind( $event, \Closure $callback ){
		self::$once_events[ $event ][] = $callback;    
  }

  
  public static function on( $event, $callback ){
    if( isset( self::$once_events[ $event ] ) ){
      $callback = self::$once_events[ $event ];
			return $callback;
    } else {
			self::$once_events[ $event ] = $callback;
    }
  }

  /*
   * @desc : 触发一个事件
   * @param : event, string字符串，事件名称
   * @param : args, 混合类型
   */
  public static function trigger( $event, array $args = [] ){
    // 查看永久事件
    if( isset( self::$events[ $event ] ) ){
      $callbacks = self::$events[ $event ];
      foreach( $callbacks as $callback_item ){
        call_user_func( $callback_item, $args );
      }
    } 
    // 查看一次性事件 
    if( isset( self::$once_events[ $event ] ) ){
      $callbacks = self::$once_events[ $event ];
      unset( self::$once_events[ $event ] );
      foreach( $callbacks as $callback_item ){
        call_user_func( $callback_item, $args );
      }
    } 
  }

  /*
   * @desc : 删除一个事件监听
   * @param : event, string字符串，事件名称 
   */
  public static function detach( $event ){
    unset( self::$events[ $event ] );
    unset( self::$once_events[ $event ] );
  }

  /*
   * @desc : 获取所有事件
   * @param : void
   */
  public static function get_all_event(){
    return array(
      'events' => self::$events,
      'once_events' => self::$once_events,
    );
  }
 
  /*
   * @desc : 清空所有事件
   * @param : void
   */
  public static function truncate(){
		self::$events = [];
		self::$once_events = [];
  }

}

/*
EventEmitter::bind( 'user.login', function( array $args ){
  echo 'user login first callback'.PHP_EOL;
} );
EventEmitter::bind( 'user.login', function( array $args ){
  echo 'user login second callback'.PHP_EOL;
  print_r( $args );
} );
EventEmitter::trigger( 'user.login', array(
  'username' => 'etc',
  'password' => md5('123452'),
) );
EventEmitter::detach('user.login');
EventEmitter::trigger( 'user.login', array(
  'username' => 'etc',
  'password' => md5('123452'),
) );

EventEmitter::once_bind( 'user.login', function(){
  echo "once bind user.login".PHP_EOL;
} );
EventEmitter::trigger( 'user.login' );
EventEmitter::trigger( 'user.login' );
*/

//Event::truncate();
//print_r( Event::get_all_event() );
