<?php
    // Credit: https://secure.php.net/manual/en/function.filesize.php#106569
    function HFFilesize( $bytes, $decimals = 2 ) {
        $sz = 'BKMGTP';
        $factor = floor( ( strlen( $bytes ) - 1 ) / 3 );
        return sprintf( "%.{$decimals}f", $bytes / pow( 1024, $factor ) ) . @$sz[$factor];
    }
?>
