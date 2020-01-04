<?php
namespace System\Event;
interface EventInterface{
  public function add( $fd, $flag, $func, $args );
  public function del( $fd, $flag );
  public function loop();
}
