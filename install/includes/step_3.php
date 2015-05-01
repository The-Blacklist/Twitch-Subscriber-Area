<h2>Installation - Step #3</h2>
<?php
    if( isset( $_POST['db_username'] ) && isset( $_POST['db_password'] ) && isset( $_POST['db_name'] ) && isset( $_POST['twitch_apikey'] ) && isset( $_POST['twitch_secret'] ) ) {
        $db_host = ( !isset( $_POST['db_host'] ) || $_POST['db_host'] == '' ? 'localhost' : $_POST['db_host'] );
        $db_user = $_POST['db_username'];
        $db_pass = $_POST['db_password'];
        $db_name = $_POST['db_name'];
        $twitch_api_key = $_POST['twitch_apikey'];
        $twitch_secret = $_POST['twitch_secret'];
        $twitch_redirect = ( !isset( $_POST['twitch_redirect'] ) || $_POST['twitch_redirect'] == '' ? $_SESSION['TSAURL'] : $_POST['twitch_redirect'] );
        $db_tblprefix = ( !isset( $_POST['db_tableprefix'] ) || str_replace( ' ', '', $_POST['db_tableprefix'] ) == '' ? 'tsa_' : str_replace( ' ', '', preg_replace( '([^A-Z,^0-9,^a-z,^_])', '', $_POST['db_tableprefix'] ) ) ); // Should work for making sure that table prefixes are MySQL-valid.
        $missing = false;
        $configFile = implode( DIRECTORY_SEPARATOR, array( '..', 'includes', 'config.php' ) );

        if( $db_user == "" || $db_pass == "" || $db_name == "" || $twitch_api_key == "" || $twitch_secret == "" ) { $missing = true; }
        if( $db_user == "" ) { echo '<div class="alert alert-danger">Missing MySQL username</div>'; }
        if( $db_pass == "" ) { echo '<div class="alert alert-danger">Missing MySQL password</div>'; }
        if( $db_name == "" ) { echo '<div class="alert alert-danger">Missing MySQL database name</div>'; }
        if( $twitch_api_key == "" ) { echo '<div class="alert alert-danger">Missing Twitch API key</div>'; }
        if( $twitch_secret == "" ) { echo '<div class="alert alert-danger">Missing Twitch API secret</div>'; }
        if( $missing ) { echo '<a href="install.php?step=2" class="btn btn-warning">Back to step #2</a>'; } else {
            $con = mysqli_connect( $db_host, $db_user, $db_pass, $db_name ) or die( 'Error connecting to database.' );
            if( !$con ) {
                echo '<div class="alert alert-danger">MySQL Error! <strong>' . mysqli_error( $con ) . '</strong></div>';
            } else {
                $dbConstants = array(
                    'TSA_DB_HOST' => $db_host,
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
                    $db_tblprefix = mysqli_real_escape_string( $con, $db_tblprefix );
                    $result = mysqli_query( $con, "CREATE TABLE " . $db_tblprefix . "posts( id int NOT NULL AUTO_INCREMENT, PRIMARY KEY(id), title varchar(255), body text);" );
                    if( $result ) {
                        echo '<div class="alert alert-success">Created "' . $db_tblprefix . 'posts" table.</div>';
                        $result = mysqli_query( $con, "CREATE TABLE " . $db_tblprefix . "settings( setting_id int NOT NULL AUTO_INCREMENT, PRIMARY KEY(setting_id), meta_key varchar(64) UNIQUE, meta_value mediumtext);" );
                        if( $result ) {
                            echo '<div class="alert alert-success">Created "' . $db_tblprefix . 'settings" table.</div>';
                            $result = mysqli_query( $con, "CREATE TABLE " . $db_tblprefix . "whitelist( id int NOT NULL AUTO_INCREMENT, PRIMARY KEY(id), name varchar(25), uid int UNIQUE );" )
                            if( $result ) {
                                echo '<div class="alert alert-success">Created "' . $db_tblprefix . 'whitelist" table.</div>';
                                $result = mysqli_query( $con, "CREATE TABLE " . TSA_DB_PREFIX . "blacklist( id int NOT NULL AUTO_INCREMENT, PRIMARY KEY(id), name varchar(25), uid int UNIQUE, reason mediumtext );" );
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
