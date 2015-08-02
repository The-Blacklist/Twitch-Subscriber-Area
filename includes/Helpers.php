<?php
    // Credit: https://secure.php.net/manual/en/function.filesize.php#106569
    function HFFilesize( $bytes, $decimals = 2 ) {
        $sz = 'BKMGTP';
        $factor = floor( ( strlen( $bytes ) - 1 ) / 3 );
        return sprintf( "%.{$decimals}f", $bytes / pow( 1024, $factor ) ) . @$sz[$factor];
    }

    // Credit: https://secure.php.net/manual/en/function.ini-get.php#96996
    function returnBytes($size_str)
    {
        switch (substr ($size_str, -1))
        {
            case 'M': case 'm': return (int)$size_str * 1048576;
            case 'K': case 'k': return (int)$size_str * 1024;
            case 'G': case 'g': return (int)$size_str * 1073741824;
            default: return $size_str;
        }
    }

?>
