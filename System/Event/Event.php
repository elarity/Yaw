<?php
namespace System\Event;
class Event implements EventInterface{
 
  private $_eventConfig = null; 

  private $_eventBase = null; 

  private $_events = [];

  public function __construct(){
    $this->_eventConfig = new \EventConfig(); 
    $this->_eventBase = new \EventBase( $this->_eventConfig ); 
  }

  /*
   * @desc : 
   */
  public function add( $fd, $flag, $func, $args = [] ){
    switch( $flag ){
      default:
        $flag = \Event::READ == $flag ? \Event::READ | \Event::PERSIST : \Event::WRITE | \Event::PERSIST ;
        $event = new \Event( $this->_eventBase, $fd, \Event::READ | \Event::PERSIST, $func, $fd );
        $event->add();
 	      $this->_events[ intval( $fd ) ][ $flag ] = $event;
    }
  }

  /*
   * @desc : 
   */
	public function del( $fd, $flag ){
    unset( $this->_events[ intval( $fd ) ] );
	}

  /*
   * @desc : 
   */
  public function loop(){
    //return $this->_eventBase->loop(); 
    $this->_eventBase->loop(); 
  }

  public function getEventBase(){
    return $this->_eventBase;
  }

}
