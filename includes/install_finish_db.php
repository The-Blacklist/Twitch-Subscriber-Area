<?php
    $db_host_values = explode( ':', TSA_DB_HOST );
    if( intval( $db_host_values[ 1 ] ) ) {
        $db_port = intval( $db_host_values[ 1 ] );
    } else {
        $db_port = ini_get( "mysqli.default_port" );
    }
    $con = mysqli_connect( TSA_DB_HOST, TSA_DB_USER, TSA_DB_PASS, TSA_DB_NAME, $db_port );
    if( !$con ) {
        echo 'MySQL error - TSA cannot initialize: ' . mysqli_error( $con );
        exit();
    }
    $fetchTitle = mysqli_fetch_array( mysqli_query( $con, "SELECT meta_value FROM " . TSA_DB_PREFIX . "settings WHERE meta_key='title';" ) );
    $title = stripslashes( $fetchTitle['meta_value'] );
    $fetchMainText = mysqli_fetch_array( mysqli_query( $con, "SELECT meta_value FROM " . TSA_DB_PREFIX . "settings WHERE meta_key='main_text';" ) );
    $main_text = stripslashes( $fetchMainText['meta_value'] );
    if( !$title || !$main_text ) {
        echo 'MySQL error - TSA cannot initialize: ' . mysqli_error( $con );
        exit();
    }
?>
