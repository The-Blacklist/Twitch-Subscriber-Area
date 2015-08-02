<h2>Installation - Step #5</h2>
<?php
    if( isset( $_POST['admin_username'] ) ) {
        $config = implode( DIRECTORY_SEPARATOR, array( '..', 'includes', 'config.php' ) );
        if( file_exists( $config ) && is_readable( $config ) ) {
            require $config;
            require implode( DIRECTORY_SEPARATOR, array( '..', 'includes', 'Twitch.php' ) );
            $twitch = new Decicus\Twitch( TSA_APIKEY, TSA_APISECRET, TSA_REDIRECTURL );
            $userID = $twitch->getUserID( $_POST['admin_username'] );
            if( $userID ) {
                $db_host_values = explode( ':', TSA_DB_HOST );
                if( intval( $db_host_values[ 1 ] ) ) {
                    $db_host = $db_host_values[ 0 ];
                    $db_port = intval( $db_host_values[ 1 ] );
                } else {
                    $db_host = TSA_DB_HOST;
                    $db_port = ini_get( "mysqli.default_port" );
                }
                $con = mysqli_connect( $db_host, TSA_DB_USER, TSA_DB_PASS, TSA_DB_NAME, $db_port ) or die( 'Error connecting to database.' );
                $adminUserInfo = array( $userID => array( 'name' => $_POST[ 'admin_username' ] ) );
                $admin = json_encode( $adminUserInfo ); // As there is no way to lookup user IDs in the Twitch API (for now), this will have to do.
                $query = "INSERT INTO " . TSA_DB_PREFIX . "settings( meta_key, meta_value ) VALUES( 'admins', '$admin' );"; // Setup admin array
                $query .= "INSERT INTO " . TSA_DB_PREFIX . "settings( meta_key, meta_value ) VALUES( 'moderators', '[]' );"; // Empty array for moderators (placeholder).
                $query .= "INSERT INTO " . TSA_DB_PREFIX . "settings( meta_key, meta_value ) VALUES( 'title', 'Twitch Subscriber Area' );"; // Default title
                $query .= "INSERT INTO " . TSA_DB_PREFIX . "settings( meta_key, meta_value ) VALUES( 'main_text', 'Welcome to Twitch Subscriber Area.\nIf you\'re admin, you can modify this text in the admin settings.' );"; // Default description
                $query .= "INSERT INTO " . TSA_DB_PREFIX . "settings( meta_key, meta_value ) VALUES( 'subscriber_streams', '[]' );"; // Empty array for partnered subscriber streams.
                $query .= "INSERT INTO " . TSA_DB_PREFIX . "posts( title, body ) VALUES( 'Post example #1', 'This is a sample post, which will be displayed for subscribed users.\nYou can create more of these or edit/delete this one as an admin or moderator in the page editor.' );"; // Sample post
                if( mysqli_multi_query( $con, $query ) ) {
                    ?>
                    <div class="alert alert-success">Admin status granted.</div>
                    <p text="text text-success">Clicking the button below will redirect you to the homepage. Please delete the "install" folder from the directory.</p>
                    <a href="<?php echo $_SESSION['TSAURL']; ?>" class="btn btn-success">Finish installation and redirect to homepage.</a>
                    <?php
                } else {
                    ?>
                    <div class="alert alert-danger"><?php echo mysqli_error( $con ); ?></div>
                    <?php
                }
                mysqli_close( $con );
            } else {
                ?>
                <div class="alert alert-danger">Error retrieving user (user most likely doesn't exist).</div>
                <?php
            }
        } else {
            ?>
            <div class="alert alert-danger">Configuration either doesn't exist or can't be read.</div>
            <?php
        }
    } else {
        ?>
        <div class="alert alert-danger">Missing parameter: Twitch admin username.</div>
        <?php
    }
?>
