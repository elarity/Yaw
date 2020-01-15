<?php
define( "DS", DIRECTORY_SEPARATOR );
define( "ROOT", __DIR__ );

spl_autoload_register( function( $s_class_name ) {
    $s_path = str_replace( "\\", "/", $s_class_name );
    $s_file = ROOT.DS.$s_path.'.php';
    require_once $s_file;
} );

$o_core = new Yaw\Core();
$o_core->onReload = function() {
    //echo "on-reload".PHP_EOL;
};
$o_core->onMessage = function( $r_connection, $m_data ) {
    //echo "on-message".PHP_EOL;
    //print_r( $m_data );
    //print_r( $r_connection );
    $r_connection->send( "hahahahhaha" );
};

Yaw\Core::start();
