<?php
    error_reporting( 0 );
    require 'includes/main.php';
    require 'includes/check_install.php';
    $page = 'index';

    if( $installFinished && !$installExists ) {
        require 'includes' . DIRECTORY_SEPARATOR . 'install_finish_db.php';
        if( isset( $_GET['logout'] ) ) {
            session_destroy();
            header( 'Location: ' . TSA_REDIRECTURL );
        }
    } else {
        $title = 'Twitch Subscriber Area';
    }
    
    if( $installFinished ) {
        $Twitch = new Decicus\Twitch( TSA_APIKEY, TSA_APISECRET, TSA_REDIRECTURL );
        $authenticateURL = $Twitch->authenticateURL( array( 'user_read', 'user_subscriptions' ) );
    }
    
    if( isset( $_SESSION['access_token'] ) ) {
        $at = $_SESSION['access_token'];
        $username = $_SESSION['username'];
        $displayName = $_SESSION['display_name'];
        $userID = $_SESSION['user_id'];
        $fetchAdmins = mysqli_fetch_array( mysqli_query( $con, "SELECT meta_value FROM " . TSA_DB_PREFIX . "settings WHERE meta_key='admins' LIMIT 1;" ) );
        $getAdmins = json_decode( $fetchAdmins['meta_value'], true );
        $fetchMods = mysqli_fetch_array( mysqli_query( $con, "SELECT meta_value FROM " . TSA_DB_PREFIX . "settings WHERE meta_key='moderators' LIMIT 1;" ) );
        $getMods = json_decode( $fetchMods['meta_value'], true );

        $fetchWLUser = mysqli_query( $con, "SELECT name FROM " . TSA_DB_PREFIX . "whitelist WHERE uid='$userID' LIMIT 1;" );
        $_SESSION['whitelisted'] = 0;
        $isWhitelisted = false;
        if( mysqli_num_rows( $fetchWLUser ) == 1 ) {
            $_SESSION['whitelisted'] = 1;
            $isWhitelisted = true;
        }

        $fetchBLUser = mysqli_query( $con, "SELECT name, reason FROM " . TSA_DB_PREFIX . "blacklist WHERE uid='$userID' LIMIT 1;" );
        $_SESSION['blacklisted'] = 0;
        $blacklistReason = '';
        $isBlacklisted = false;
        if( mysqli_num_rows( $fetchWLUser ) == 1 ) {
            $fetchBLData = mysqli_fetch_array( $fetchBLUser );
            $_SESSION['blacklisted'] = 1;
            $blacklistReason = $fetchBLData['reason'];
            $isBlacklisted = true;
        }

        if( isset( $getAdmins[ $userID ] ) ) {
            $_SESSION['isAdmin'] = 1;
            $_SESSION['isMod'] = 1; // Admins are automatically "moderators" too.
            $isMod = true;
            $isAdmin = true;
        } elseif( isset( $getMods[ $userID ] ) ) {
            $_SESSION['isAdmin'] = 0;
            $_SESSION['isMod'] = 1;
            $isMod = true;
            $isAdmin = false;
        } else {
            $_SESSION['isAdmin'] = 0;
            $isAdmin = false;
            if( in_array( $userID, $getMods ) ) {
                $_SESSION['isMod'] = 1;
                $isMod = true;
            } else {
                $_SESSION['isMod'] = 0;
                $isMod = false;
            }
        }
    } elseif( isset( $_GET['code'] ) ) {
        $code = $_GET['code'];
        $at = $Twitch->getAccessToken( $code );
        $username = $Twitch->getName( $at );
        $displayName = $Twitch->getDisplayName( $at );
        $userID = $Twitch->getUserIDFromAT( $at );
        if( $at && $username && $displayName && $userID ) {
            $_SESSION['access_token'] = $at;
            $_SESSION['username'] = $username;
            $_SESSION['display_name'] = $displayName;
            $_SESSION['user_id'] = $userID;
            header( 'Location: ' . TSA_REDIRECTURL );
        } else {
            header( 'Location: ' . TSA_REDIRECTURL . '?invalid' );
        }
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <title><?php echo $title; ?> - Home</title>
        <?php include 'includes/head.php'; ?>
    </head>
    <body>
        <?php include 'includes/nav.php'; ?>
        <div class="container">
                <?php
                if( $installFinished ) {
                    if( $installExists ) {
                ?>
                <div class="alert alert-danger">Installation appears to be finished and install directory still exists, please delete this directory (preferred) or rename it.</div>
                <?php
                    } else {
                ?>
                    <div class="page-header"><h1><?php echo $title; ?> - Home</h1></div>
                    <div class="jumbotron">
                        <p class="text text-info"><?php echo nl2br( $main_text ); ?></p>
                        <?php
                            if( isset( $_GET['invalid'] ) ) {
                                ?>
                                <div class="alert alert-danger">Invalid authorization code. Please <a href="<?php echo $authenticateURL; ?>" class="alert-link">re-authenticate</a>.</div>
                                <?php
                            }
                            
                            if( isset( $_SESSION['access_token'] ) ) {
                                ?>
                                <div class="alert alert-success">Welcome <span class="bold"><?php echo $displayName; ?></span>. You are successfully logged in and fully authenticated.</div>
                                <?php
                                $fetchSubStreams = mysqli_fetch_array( mysqli_query( $con, "SELECT meta_value FROM " . TSA_DB_PREFIX . "settings WHERE meta_key='subscriber_streams';" ) );
                                $getSubStreams = json_decode( $fetchSubStreams['meta_value'], true );
                                if( !empty( $getSubStreams ) || $isAdmin || $isMod ) {
                                    $streamCount = count( $getSubStreams );
                                    if( $isAdmin ) {
                                        ?>
                                        <div class="alert alert-info">You are an <span class="bold">admin</span>. This means you can edit site settings and modify posts displayed on this page via the <a href="<?php echo TSA_REDIRECTURL; ?>/admin.php" class="alert-link">admin page</a> and the <a href="<?php echo TSA_REDIRECTURL; ?>/editor.php" class="alert-link">page editor</a>.</div>
                                        <?php
                                        if( empty( $getSubStreams ) ) {
                                            ?>
                                            <div class="alert alert-danger">There are no streamers with the subscription program stored in the database. Please add this via the <a href="<?php echo TSA_REDIRECTURL; ?>/admin.php" class="alert-link">admin page</a>.</div>
                                            <?php
                                        }
                                    } elseif( $isMod ) {
                                        ?>
                                        <div class="alert alert-info">You are a <span class="bold">moderator</span>. This means you can modify site posts displayed on this page via the <a href="<?php echo TSA_REDIRECTURL; ?>/editor.php" class="alert-link">editor</a>.</div>
                                        <?php
                                    }
                                    $isSubbed = false;
                                    $atError = NULL;
                                    foreach( $getSubStreams as $UID => $info ) {
                                        $name = $info[ 'name' ];
                                        if( $Twitch->isSubscribed( $at, $username, $name ) == 100 ) {
                                            $isSubbed = true;
                                            break;
                                        } elseif( $Twitch->isSubscribed( $at, $username, $name ) == 401 ) {
                                            $atError = '<div class="alert alert-danger">There was an error retrieving subscriber status, please <a href="' . TSA_REDIRECTURL . '/?logout" class="alert-link">logout</a> and connect with Twitch again.</div>';
                                        }
                                    }
                                    $firstStreamerKey = array_keys( $getSubStreams );
                                    $firstStreamer = $getSubStreams[ $firstStreamerKey[ 0 ] ][ 'name' ];
                                    if( $isSubbed || $isMod || $isWhitelisted && !$isBlacklisted ) {
                                        if( $isSubbed ) {
                                            ?>
                                            <div class="alert alert-success">You are subscribed to <?php echo ( $streamCount == 1 ? $firstStreamer : 'one or more streamers in the list' ); ?> and will now have access to the subscriber posts.</div>
                                            <?php
                                        }
                                        $fetchPosts = mysqli_query( $con, "SELECT id, title, body FROM " . TSA_DB_PREFIX . "posts;" );
                                        $hasPosts = false;
                                        while( $row = mysqli_fetch_array( $fetchPosts ) ) {
                                            $hasPosts = true;
                                            $postID = $row['id'];
                                            $postTitle = stripslashes( $row['title'] );
                                            $postText = stripslashes( nl2br( $row['body'] ) );
                                            ?>
                                            <div class="panel panel-primary">
                                                <div class="panel-heading"><?php echo $postTitle; ?></div>
                                                <div class="panel-body"><?php echo $postText; ?></div>
                                            </div>
                                            <?php
                                        }
                                        if( !$hasPosts ) {
                                            ?>
                                            <div class="alert alert-warning">There are no posts :(</div>
                                            <?php
                                        }
                                    } elseif( $isBlacklisted ) {
                                        ?>
                                        <div class="panel panel-danger">
                                            <div class="panel-heading">You have been blacklisted from using this subscriber area &mdash; Reason:</div>
                                            <div class="panel-body"><?php echo nl2br( $blacklistReason ); ?></div>
                                        </div>
                                        <?php
                                    } else {
                                        if( $atError ) {
                                            echo $atError;
                                        } else {
                                            $noSubMessage = array();
                                            if( $streamCount == 1 ) {
                                                $noSubMessage['u'] = $firstStreamer;
                                                $noSubMessage['msg'] = '.';
                                            } else {
                                                $noSubMessage['u'] = 'any of the streamers in the list';
                                                $noSubMessage['msg'] = ' to at least one of them.';
                                            }
                                            ?>
                                            <div class="alert alert-warning">You are not subscribed to <?php echo $noSubMessage['u']; ?> and will not get access unless you subscribe<?php echo $noSubMessage['msg']; ?></div>
                                            <div class="list-group">
                                            <?php
                                            foreach( $getSubStreams as $UID => $info ) {
                                                $name = $info[ 'name' ];
                                                ?>
                                                <a href="http://www.twitch.tv/<?php echo $name; ?>" class="list-group-item list-group-item-success">Subscribe to <?php echo $Twitch->getDisplayNameNoAT( $name ); ?></a>
                                                <?php
                                            }
                                            ?>
                                            </div>
                                            <?php
                                        }
                                    }
                                } else {
                                    ?>
                                    <div class="alert alert-danger">This website currently does not contain any streamers with a subscription program. Please contact the owners to fix this issue.</div>
                                    <?php
                                }
                                ?>
                                <a href="<?php echo TSA_REDIRECTURL; ?>/?logout" class="btn btn-danger">Logout</a>
                                <?php
                            } else {
                                ?>
                                <a href="<?php echo $authenticateURL ?>"><img src="images/twitch_connect.png" alt="Connect with Twitch" /></a>
                                <?php
                            }
                        ?>
                    </div>
                <?php
                    mysqli_close( $con );
                    }
                } else {
                ?>
                    <div class="alert alert-danger">Please go through the installation script: <a href="./install">Installation script</a></div>
                <?php
                }
                ?>
        </div>
    </body>
</html>
