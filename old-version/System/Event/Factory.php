<?php
namespace System\Event;
class Factory{
  public static function create(){
    if( class_exists( 'EventBase' ) ){
      $eventLoop = new Event();
    }else{
      $eventLoop = 'Select';
    }
    return $eventLoop;
  }
}
