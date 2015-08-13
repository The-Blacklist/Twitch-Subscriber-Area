<?php
    error_reporting( 0 );
    require 'includes/main.php';
    require 'includes/check_install.php';

    if( $installFinished && !$installExists ) {
        require 'includes' . DIRECTORY_SEPARATOR . 'install_finish_db.php';
        if( isset( $_GET['logout'] ) ) {
            session_destroy();
            header( 'Location: ' . TSA_REDIRECTURL );
        }
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
        if( mysqli_num_rows( $fetchBLUser ) == 1 ) {
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
    } else {
        header( 'Location: ' . TSA_REDIRECTURL . '?dl_error=no_login' );
    }

    $fetchDownloadsDir = mysqli_fetch_array( mysqli_query( $con, "SELECT meta_value FROM " . TSA_DB_PREFIX . "settings WHERE meta_key='downloads_location' LIMIT 1;" ) );
    $downloadsDir = $fetchDownloadsDir['meta_value'];
    $fetchFiletypes = mysqli_fetch_array( mysqli_query( $con, "SELECT meta_value FROM " . TSA_DB_PREFIX . "settings WHERE meta_key='downloads_whitelist' LIMIT 1;" ) );
    $filetypes = json_decode( $fetchFiletypes['meta_value'], true );
    if( !empty( $_GET['id'] ) ) {
        $id = intval( $_GET['id'] );
        if( $id ) {
            $fetchDownload = mysqli_query( $con, "SELECT hash, filetype, original_file_name, size FROM " . TSA_DB_PREFIX . "downloads WHERE id='" . $id . "' LIMIT 1;" );
            if( mysqli_num_rows( $fetchDownload ) == 0 ) {
                header( 'Location: ' . TSA_REDIRECTURL . "?dl_error=no_exist" );
            } else {
                $fetchSubStreams = mysqli_fetch_array( mysqli_query( $con, "SELECT meta_value FROM " . TSA_DB_PREFIX . "settings WHERE meta_key='subscriber_streams';" ) );
                $getSubStreams = json_decode( $fetchSubStreams['meta_value'], true );
                $Twitch = new Decicus\Twitch( TSA_APIKEY, TSA_APISECRET, TSA_REDIRECTURL );
                if( !empty( $getSubStreams ) || $isMod || $isWhitelisted ) {
                    $isSubbed = false;
                    if( !$isMod || !$isWhitelisted ) {
                        foreach( $getSubStreams as $UID => $info ) {
                            $name = $info['name'];
                            if( $Twitch->isSubscribed( $at, $username, $name ) == 100 ) {
                                $isSubbed = true;
                                break;
                            }
                        }
                    }

                    if( $isSubbed || $isWhitelisted || $isMod ) {
                        $dlInfo = mysqli_fetch_array( $fetchDownload );
                        $fileLocation = $downloadsDir . DIRECTORY_SEPARATOR . $dlInfo['hash'] . "." . $dlInfo['filetype'];
                        if( file_exists( $fileLocation ) && isset( $filetypes[ $dlInfo[ 'filetype' ] ] ) ) {
                            header( 'Content-Description: File Transfer');
                            header( 'Content-Type: ' . $filetypes[ $dlInfo[ 'filetype' ] ] );
                            header( 'Content-Disposition: inline; filename=' . $dlInfo['original_file_name'] );
                            header( 'Expires: 0' );
                            header( 'Cache-Control: must-revalidate' );
                            header( 'Pragma: public' );
                            header( 'Content-Length: ' . $dlInfo['size'] );
                            flush();
                            readfile( $fileLocation );
                            exit();
                        } else {
                            header( 'Location: ' . TSA_REDIRECTURL . '?dl_error=not_found' );
                        }
                    } else {
                        header( 'Location: ' . TSA_REDIRECTURL . '?dl_error=no_sub' );
                    }
                } else {
                    header( 'Location: ' . TSA_REDIRECTURL . '?dl_error=no_streams' );
                }
            }
        } else {
            header( 'Location: ' . TSA_REDIRECTURL . '?dl_error=invalid_id' );
        }
    } else {
        header( 'Location: ' . TSA_REDIRECTURL . '?dl_error=empty_id' );
    }
?>
