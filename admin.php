<?php
    require 'includes/main.php';
    require 'includes/check_install.php';
    $page = 'admin';
    if( !isset( $_SESSION['isAdmin'] ) || $_SESSION['isAdmin'] == 0 ) { header( 'Location: ' . TSA_REDIRECTURL ); }

    if( $installFinished && !$installExists ) {
        require 'includes' . DIRECTORY_SEPARATOR . 'install_finish_db.php';
        // Verify the user is admin.
        $fetchAdmins = mysqli_fetch_array( mysqli_query( $con, "SELECT meta_value FROM " . TSA_DB_PREFIX . "settings WHERE meta_key='admins' LIMIT 1;" ) );
        $getAdmins = json_decode( $fetchAdmins['meta_value'], true );
        if( !isset( $getAdmins[ $_SESSION['user_id'] ] ) ) {
            $_SESSION['isAdmin'] = 0;
            header( 'Location: ' . TSA_REDIRECTURL ); // Redirect back to homepage, because at this point they should not have access.
            exit();
        }
    } else {
        $title = 'Twitch Subscriber Area';
    }

    $checkDLWhitelist = mysqli_query( $con, "SELECT meta_value FROM " . TSA_DB_PREFIX . "settings WHERE meta_key='downloads_whitelist' LIMIT 1;" );
    if( mysqli_num_rows( $checkDLWhitelist ) == 0 ) {
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
        $createDLWhitelist = mysqli_query( $con, "INSERT INTO " . TSA_DB_PREFIX . "settings( meta_key, meta_value ) VALUES( 'downloads_whitelist', '" . json_encode( $dlFileTypes ) . "' );");
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <title><?php echo $title; ?> - Admin</title>
        <?php include 'includes/head.php'; ?>
    </head>
    <body>
        <?php include 'includes/nav.php'; ?>
        <div class="container">
        <div class="page-header"><h1><?php echo $title; ?> - Admin</h1></div>
            <div class="jumbotron">
                <p class="text text-info">Welcome to the admin settings of <?php echo $title; ?>. Here you will be able to access site-wide settings.</p>
                <?php
                    $Twitch = new Decicus\Twitch( TSA_APIKEY, TSA_APISECRET, TSA_REDIRECTURL );
                    $pages = array(
                        'admins' => 'Modify site administrators (full access users).',
                        'moderators' => 'Modify site moderators (only access to add, edit or delete posts). Will naturally have access to see the posts as well.',
                        'title' => 'Change the title of this website.',
                        'description' => 'Modify the homepage description.',
                        'streamers' => 'Modify the list of partnered streamers supported on this site.',
                        'whitelist' => 'Users that have access to the subscriber area without being a subscriber or mod/admin.',
                        'blacklist' => 'Users that are blocked from having access to the subscriber area.',
                        'downloads' => 'Manage settings for downloads, such as whitelisted filetypes moderators can upload.'
                    );
                    $currentPage = "";
                    if( isset( $_GET['page'] ) && isset( $pages[ $_GET['page'] ] ) ) {
                        $currentPage = $_GET['page'];
                        require implode( DIRECTORY_SEPARATOR, array( 'includes', 'admin', $currentPage . '.php' ) );
                    }
                    ?>
                    <div class="container">
                        <div class="list-group">
                    <?php
                        foreach( $pages as $page => $desc ) {
                            $pageName = strtoupper( substr( $page, 0, 1 ) ) . substr( $page, 1 );
                    ?>
                            <a href="<?php echo TSA_REDIRECTURL; ?>/admin.php?page=<?php echo $page; ?>" class="list-group-item <?php echo ( $currentPage == $page ? 'active' : '' ); ?>"><strong><?php echo $pageName; ?></strong> &mdash; <?php echo $desc; ?></a>
                    <?php
                        }
                    ?>
                        </div>
                    </div>
                    <?php
                    mysqli_close( $con );
                ?>
                <br />
                <a href="<?php echo TSA_REDIRECTURL; ?>/?logout" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </body>
</html>
