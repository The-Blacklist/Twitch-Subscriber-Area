<h2>Installation - Step #3</h2>
<?php
    if( isset( $_POST['db_username'] ) && isset( $_POST['db_password'] ) && isset( $_POST['db_name'] ) && isset( $_POST['twitch_apikey'] ) && isset( $_POST['twitch_secret'] ) && isset( $_POST['downloads_location'] ) ) {
        $pre_db_host = ( !isset( $_POST['db_host'] ) || $_POST['db_host'] == '' ? 'localhost' : $_POST['db_host'] );
        $db_host_values = explode( ':', $pre_db_host );
        if( isset( $db_host_values[ 1 ] ) && intval( $db_host_values[ 1 ] ) ) {
            $db_host = $db_host_values[ 0 ];
            $db_port = intval( $db_host_values[ 1 ] );
        } else {
            $db_host = $pre_db_host;
            $db_port = ini_get( "mysqli.default_port" );
        }
        $db_user = $_POST['db_username'];
        $db_pass = $_POST['db_password'];
        $db_name = $_POST['db_name'];
        $twitch_api_key = $_POST['twitch_apikey'];
        $twitch_secret = $_POST['twitch_secret'];
        $downloads_location = $_POST['downloads_location'];
        $twitch_redirect = ( !isset( $_POST['twitch_redirect'] ) || $_POST['twitch_redirect'] == '' ? $_SESSION['TSAURL'] : $_POST['twitch_redirect'] );
        $db_tblprefix = ( !isset( $_POST['db_tableprefix'] ) || str_replace( ' ', '', $_POST['db_tableprefix'] ) == '' ? 'tsa_' : str_replace( ' ', '', preg_replace( '([^A-Z,^0-9,^a-z,^_])', '', $_POST['db_tableprefix'] ) ) ); // Should work for making sure that table prefixes are MySQL-valid.
        $missing = false;
        $configFile = implode( DIRECTORY_SEPARATOR, array( 'includes', 'config.php' ) );

        if( empty( $db_user ) || empty( $db_pass ) || empty( $db_name ) || empty( $twitch_api_key ) || empty( $twitch_secret ) || empty( $downloads_location ) ) { $missing = true; }
        if( empty( $db_user ) ) { echo '<div class="alert alert-danger">Missing MySQL username</div>'; }
        if( empty( $db_pass ) ) { echo '<div class="alert alert-danger">Missing MySQL password</div>'; }
        if( empty( $db_name ) ) { echo '<div class="alert alert-danger">Missing MySQL database name</div>'; }
        if( empty( $twitch_api_key ) ) { echo '<div class="alert alert-danger">Missing Twitch API key</div>'; }
        if( empty( $twitch_secret ) ) { echo '<div class="alert alert-danger">Missing Twitch API secret</div>'; }
        if( empty( $downloads_location ) ) { echo '<div class="alert alert-danger">Missing folder location of subscriber downloads</div>'; }
        if( $missing ) { echo '<a href="install.php?step=2" class="btn btn-warning">Back to step #2</a>'; } else {
            $con = mysqli_connect( $db_host, $db_user, $db_pass, $db_name, $db_port ) or die( 'Error connecting to database.' );
            if( !$con ) {
                echo '<div class="alert alert-danger">MySQL Error! <strong>' . mysqli_error( $con ) . '</strong></div>';
            } else {
                $dbConstants = array(
                    'TSA_DB_HOST' => $pre_db_host,
                    'TSA_DB_USER' => $db_user,
                    'TSA_DB_PASS' => $db_pass,
                    'TSA_DB_NAME' => $db_name,
                    'TSA_DB_PREFIX' => $db_tblprefix
                );
                $twitchConstants = array(
                    'TSA_APIKEY' => $twitch_api_key,
                    'TSA_APISECRET' => $twitch_secret,
                    'TSA_REDIRECTURL' => $twitch_redirect
                );
                chdir( '..' ); // Go up one directory for writing files.
                if( is_writeable( $configFile ) ) {
                    $config = "<?php \n";
                    $config .= "    // MySQL database information\n";
                    foreach( $dbConstants as $const => $value ) {
                        $config .= "    define( '" . $const . "', '" . $value . "' );\n";
                    }
                    $config .= "\n    // Twitch API stuff\n";
                    foreach( $twitchConstants as $const => $value ) {
                        $config .= "    define( '" . $const . "', '" . $value . "' );\n";
                    }
                    $confWrite = fopen( $configFile, 'w' ) or die( 'Cannot open configuration file. Please make sure the web server user has the correct permissions to \'includes/config.php\'.' );
                    fwrite( $confWrite, $config, strlen( $config ) );
                    fclose( $confWrite );

                    if( file_exists( $downloads_location ) ) {
                        if( is_dir( $downloads_location ) ) {
                            if( !is_writeable( $downloads_location ) ) {
                                echo '<div class="alert alert-danger">' . $downloads_location . ' is not writable.</div>';
                                exit();
                            }
                        } else {
                            echo '<div class="alert alert-danger">' . $downloads_location . ' is not a folder.</div>';
                            exit();
                        }
                    } else {
                        if( !mkdir( $downloads_location, 0777, true ) ) {
                            echo '<div class="alert alert-danger">Unable to create folder for downloadable files at: ' . $downloads_location . '</div>';
                            exit();
                        }
                    }

                    $writeDlIndex = fopen( $downloads_location . DIRECTORY_SEPARATOR . 'index.php', 'w' ) or die( 'Unable to write file inside ' . $downloads_location . '. Please make sure the web server user has the correct permissions.' );
                    fwrite( $writeDlIndex, "<?php // Nothing to see here ?>" );
                    fclose( $writeDlIndex );

                    $writeDlHtaccess = fopen( $downloads_location . DIRECTORY_SEPARATOR . '.htaccess', 'w' ) or die( 'Unable to write file inside ' . $downloads_location . '. Please make sure the web server user has the correct permissions.' );
                    fwrite( $writeDlHtaccess, "Deny from all" );
                    fclose( $writeDlHtaccess );

                    $db_tblprefix = mysqli_real_escape_string( $con, $db_tblprefix );
                    // I am so sorry for making this the spaghetti it is...
                    $result = mysqli_query( $con, "CREATE TABLE " . $db_tblprefix . "posts( id int NOT NULL AUTO_INCREMENT, PRIMARY KEY(id), title varchar(255), body text);" );
                    if( $result ) {
                        echo '<div class="alert alert-success">Created "' . $db_tblprefix . 'posts" table.</div>';
                        $result = mysqli_query( $con, "CREATE TABLE " . $db_tblprefix . "settings( setting_id int NOT NULL AUTO_INCREMENT, PRIMARY KEY(setting_id), meta_key varchar(64) UNIQUE, meta_value mediumtext);" );
                        if( $result ) {
                            echo '<div class="alert alert-success">Created "' . $db_tblprefix . 'settings" table.</div>';
                            $result = mysqli_query( $con, "CREATE TABLE " . $db_tblprefix . "whitelist( id int NOT NULL AUTO_INCREMENT, PRIMARY KEY(id), name varchar(25), uid int UNIQUE );" );
                            if( $result ) {
                                echo '<div class="alert alert-success">Created "' . $db_tblprefix . 'whitelist" table.</div>';
                                $result = mysqli_query( $con, "CREATE TABLE " . $db_tblprefix . "blacklist( id int NOT NULL AUTO_INCREMENT, PRIMARY KEY(id), name varchar(25), uid int UNIQUE, reason mediumtext );" );
                                if( $result ) {
                                    echo '<div class="alert alert-success">Created "' . $db_tblprefix . 'blacklist" table.</div>';
                                    $result = mysqli_query( $con, "CREATE TABLE " . $db_tblprefix . "downloads( id int NOT NULL AUTO_INCREMENT, PRIMARY KEY(id), post_id int(11), hash char(40), filetype varchar(255), original_file_name varchar(255), size int(11), date date );" );
                                    if( $result ) {
                                        echo '<div class="alert alert-success">Created "' . $db_tblprefix . 'downloads" table.</div>';
                                        $dlFileTypes = array(
                                            'png' => 'image/png',
                                            'jpeg' => 'image/jpeg',
                                            'jpg' => 'image/jpeg',
                                            'gif' => 'image/gif',
                                            'bmp' => 'image/bmp',
                                            'pdf' => 'application/pdf',
                                            'zip' => 'application/octet-stream',
                                            'rar' => 'application/octet-stream',
                                            'doc' => 'application/msword',
                                            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                                            'txt' => 'text/plain',
                                            'log' => 'text/plain',
                                            'mp3' => 'audio/mpeg',
                                            'wav' => 'audio/wav',
                                            'ogg' => 'audio/ogg',
                                            'm4v' => 'video/mp4',
                                            'mp4' => 'video/mp4',
                                            'webm' => 'video/webm'
                                        );
                                        $downloads_location = mysqli_real_escape_string( $con, $downloads_location );
                                        $insertDlFiletypes = "INSERT INTO " . $db_tblprefix . "settings( meta_key, meta_value ) VALUES( 'downloads_whitelist', '" . json_encode( $dlFileTypes ) . "' );";
                                        $insertDlPlaceholder = "INSERT INTO " . $db_tblprefix . "settings( meta_key, meta_value ) VALUES( 'downloads_location', '" . $downloads_location . "' );";
                                        $result = mysqli_multi_query( $con, $insertDlFiletypes . $insertDlPlaceholder );
                                        if( $result ) {
                                            ?>
                                            <form method="get" action="install.php"><input type="hidden" name="step" value="4" /><button class="btn btn-success">Continue to step #4    </button></form>
                                            <?php
                                        } else {
                                            echo '<div class="alert alert-danger">' . mysqli_error( $con ) . '</div>';
                                        }
                                    } else {
                                        echo '<div class="alert alert-danger">' . mysqli_error( $con ) . '</div>';
                                    }
                                } else {
                                    echo '<div class="alert alert-danger">' . mysqli_error( $con ) . '</div>';
                                }
                            } else {
                                echo '<div class="alert alert-danger">' . mysqli_error( $con ) . '</div>';
                            }
                        } else {
                            echo '<div class="alert alert-danger">' . mysqli_error( $con ) . '</div>';
                        }
                    } else {
                        echo '<div class="alert alert-danger">' . mysqli_error( $con ) . '</div>';
                    }
                } else {
                    ?>
                    <div class="alert alert-danger">Unable to write to configuration file 'includes/config.php'.</div>
                    <?php
                }
            }
            mysqli_close( $con );
        }
    } else {
        ?>
        <div class="alert alert-danger">Missing parameter(s).</div>
        <?php
    }
?>
