<?php
namespace Yaw\Event;
interface EventInterface {

    const EV_ALL   = 0;
    const EV_READ  = 1;
    const EV_WRITE = 2;
    const EV_EXCEPTION = 3;

    public function add( $r_fd, $i_event_type, $f_callback );

    public function del( $r_fd, $i_event_type );

    public function loop();

}