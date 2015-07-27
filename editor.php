<?php
    require 'includes/main.php';
    require 'includes/check_install.php';
    $page = 'editor';
    if( !isset( $_SESSION['isMod'] ) || $_SESSION['isMod'] == 0 ) { header( 'Location: ' . TSA_REDIRECTURL ); }

    if( $installFinished && !$installExists ) {
        require 'includes' . DIRECTORY_SEPARATOR . 'install_finish_db.php';
        // Verify the user is a moderator/admin.
        $fetchAdmins = mysqli_fetch_array( mysqli_query( $con, "SELECT meta_value FROM " . TSA_DB_PREFIX . "settings WHERE meta_key='admins' LIMIT 1;" ) );
        $getAdmins = json_decode( $fetchAdmins['meta_value'], true );
        $fetchMods = mysqli_fetch_array( mysqli_query( $con, "SELECT meta_value FROM " . TSA_DB_PREFIX . "settings WHERE meta_key='moderators' LIMIT 1;" ) );
        $getMods = json_decode( $fetchMods['meta_value'], true );
        $userID = $_SESSION['user_id'];
        if( !isset( $getAdmins[ $userID ] ) && !isset( $getMods[ $userID ] ) ) {
            $_SESSION['isMod'] = 0;
            header( 'Location: ' . TSA_REDIRECTURL ); // Redirect back to homepage, because at this point they should not have access.
            exit();
        }
    } else {
        $title = 'Twitch Subscriber Area';
    }

    // For <v1.2 support
    $checkDownloads = mysqli_query( $con, "SHOW TABLES LIKE '" . TSA_DB_PREFIX . "downloads';" );
    if( mysqli_num_rows( checkDownloads ) == 0 ) {
        $createDownload = mysqli_query( $con, "CREATE TABLE " . TSA_DB_PREFIX . "downloads( id int NOT NULL AUTO_INCREMENT, PRIMARY KEY(id), post_id int(11), hash char(40), original_file_name varchar(255), filename varchar(255), size int(11), date date );" );
        if( !$createDownload ) {
            ?>
            <div class="alert alert-danger">Unable to create <?php echo TSA_DB_PREFIX; ?>downloads in the database!</div>
            <?php
            exit();
        }
    }

    $checkDLWhitelist = mysqli_query( $con, "SELECT meta_value FROM " . TSA_DB_PREFIX . "settings WHERE meta_key='download_whitelist' LIMIT 1;" );
    if( mysqli_num_rows( $checkDLWhitelist ) == 0 ) {
        $dlFileTypes = array(
            'png' => 'image/png',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
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
            'webm' => 'video/webm';
        );
        $createDLWhitelist = mysqli_query( $con, "INSERT INTO " . TSA_DB_PREFIX . "settings( meta_key, meta_value ) VALUES( 'download_whitelist', '" . json_encode( $dlFileTypes ) . "' );");
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <title><?php echo $title; ?> - Editor</title>
        <?php include 'includes/head.php'; ?>
    </head>
    <body>
        <?php include 'includes/nav.php'; ?>
        <div class="container">
        <div class="page-header"><h1><?php echo $title; ?> - Editor</h1></div>
            <div class="jumbotron">
                <p class="text text-info">This is the editor panel of <?php echo $title; ?>. The posts shown on the homepage can be edited here by moderators or admins.</p>
                <?php
                    if( isset( $_GET['add'] ) ) {
                        if( isset( $_POST['addPostBody'] ) && isset( $_POST['addPostTitle'] ) ) {
                            $addPostTitle = mysqli_real_escape_string( $con, $_POST['addPostTitle'] );
                            $addPostBody = mysqli_real_escape_string( $con, $_POST['addPostBody'] );
                            if( $addPostTitle != "" && $addPostBody != "" ) {
                                $insertPost = mysqli_query( $con, "INSERT INTO " . TSA_DB_PREFIX . "posts( title, body ) VALUES( '" . $addPostTitle . "', '" . $addPostBody . "' );" );
                                if( $insertPost ) {
                                    ?>
                                    <div class="alert alert-success">Post "<?php echo stripslashes( $addPostTitle ); ?>" has been added!</div>
                                    <?php
                                } else {
                                    ?>
                                    <div class="alert alert-danger">Error! - <?php echo mysqli_error( $con ); ?></div>
                                    <?php
                                }
                            } else {
                                ?>
                                <div class="alert alert-warning">Both title and main text needs to be filled out.</div>
                                <a href="<?php echo TSA_REDIRECTURL; ?>/editor.php?add" class="btn btn-info">Back to "add post"</a><br /><br />
                                <?php
                            }
                        } else {
                            ?>
                            <div class="panel panel-success">
                                <div class="panel-heading">Add post:</div>
                                <div class="panel-body">
                                    <form method="post" action="<?php echo TSA_REDIRECTURL; ?>/editor.php?add">
                                        <div class="form-group">
                                            <label for="postTitle">Post title:</label>
                                            <input type="text" class="form-control" name="addPostTitle" id="postTitle" placeholder="Title" required="" />
                                        </div>
                                        <div class="form-group">
                                            <label for="postBody">Post body (main text):</label>
                                            <textarea class="form-control" name="addPostBody" id="postBody" rows="10" cols="50" placeholder="Main text" required=""></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-success">Add post!</button>
                                    </form>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                        <a href="<?php echo TSA_REDIRECTURL; ?>/editor.php" class="btn btn-info">Back to editor page</a><br />
                        <?php
                    } elseif( isset( $_GET['edit'] ) ) {
                        $editID = intval( preg_replace( '([\D])', '', $_GET['edit'] ) );
                        $editPost = mysqli_query( $con, "SELECT title, body FROM " . TSA_DB_PREFIX . "posts WHERE id='" . $editID . "' LIMIT 1;" );
                        if( mysqli_num_rows( $editPost ) == 0 ) {
                            ?>
                            <div class="alert alert-danger">Post does not exist.</div>
                            <?php
                        } else {
                            $postInfo = mysqli_fetch_array( $editPost );
                            if( isset( $_POST['editPostTitle'] ) && isset( $_POST['editPostBody'] ) ) {
                                $editPostTitle = mysqli_real_escape_string( $con, $_POST['editPostTitle'] );
                                $editPostBody = mysqli_real_escape_string( $con, $_POST['editPostBody'] );
                                if( mysqli_query( $con, "UPDATE " . TSA_DB_PREFIX . "posts SET title='" . $editPostTitle . "', body='" . $editPostBody . "' WHERE id='" . $editID ."';" ) ) {
                                    ?>
                                    <div class="alert alert-success">Post "<?php echo stripslashes( $editPostTitle ); ?>" has been edited.</div>
                                    <?php
                                } else {
                                    ?>
                                    <div class="alert alert-danger">Error! - <?php echo mysqli_error( $con ); ?></div>
                                    <?php
                                }
                            } else {
                                ?>
                                <div class="panel panel-warning">
                                    <div class="panel-heading">Currently editing: "<strong><?php echo $postInfo['title']; ?></strong>"</div>
                                    <div class="panel-body">
                                        <form method="post" action="<?php echo TSA_REDIRECTURL; ?>/editor.php?edit=<?php echo $editID; ?>">
                                            <div class="form-group">
                                                <label for="postTitle">Post title:</label>
                                                <input type="text" class="form-control" name="editPostTitle" id="postTitle" value="<?php echo $postInfo['title']; ?>" required="" />
                                            </div>
                                            <div class="form-group">
                                                <label for="postBody">Post body (main text):</label>
                                                <textarea class="form-control" name="editPostBody" id="postBody" rows="10" cols="50" required=""><?php echo $postInfo['body']; ?></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-success">Edit post!</button>
                                        </form>
                                    </div>
                                </div>
                                <?php
                            }
                        }
                        ?>
                        <br />
                        <a href="<?php echo TSA_REDIRECTURL; ?>/editor.php" class="btn btn-info">Back to editor page</a><br />
                        <?php
                    } elseif( isset( $_GET['delete'] ) ) {
                        $delID = intval( preg_replace( '([\D])', '', $_GET['delete'] ) );
                        $delPost = mysqli_query( $con, "SELECT title, body FROM " . TSA_DB_PREFIX . "posts WHERE id='" . $delID . "' LIMIT 1;" );
                        if( mysqli_num_rows( $delPost ) == 0 ) {
                            ?>
                            <div class="alert alert-danger">Post does not exist.</div>
                            <?php
                        } else {
                            $postInfo = mysqli_fetch_array( $delPost );
                            $postTitle = $postInfo['title'];
                            if( mysqli_query( $con, "DELETE FROM " . TSA_DB_PREFIX . "posts WHERE id='" . $delID . "';" ) ) {
                                ?>
                                <div class="alert alert-success">"<strong><?php echo $postTitle; ?></strong>" has been deleted.</div>
                                <?php
                            } else {
                                ?>
                                <div class="alert alert-danger">Error! - <?php echo mysqli_error( $con ); ?></div>
                                <?php
                            }
                        }
                        ?>
                        <a href="<?php echo TSA_REDIRECTURL; ?>/editor.php" class="btn btn-info">Back to editor page</a><br />
                        <?php
                    } else {
                        $allPosts = mysqli_query( $con, "SELECT id, title, body FROM " . TSA_DB_PREFIX . "posts;" );
                        $hasPosts = false;
                        while( $row = mysqli_fetch_array( $allPosts ) ) {
                            $hasPosts = true;
                            $postID = $row['id'];
                            $postTitle = $row['title'];
                            $postBody = $row['body'];
                            ?>
                            <div class="panel panel-primary">
                                <div class="panel-heading"><h3 class="panel-title"><?php echo stripslashes( $postTitle ); ?></h3></div>
                                <div class="panel-body"><?php echo stripslashes( nl2br( $postBody ) ); ?></div>
                                <div class="panel-footer">
                                    <a href="<?php echo TSA_REDIRECTURL; ?>/editor.php?edit=<?php echo $postID; ?>" class="btn btn-warning">Edit</a>
                                    <a href="<?php echo TSA_REDIRECTURL; ?>/editor.php?delete=<?php echo $postID; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this post? This will also remove any downloads attached to this post.');">Delete</a>
                                </div>
                            </div>
                            <?php
                        }
                        if( !$hasPosts ) {
                            ?>
                            <div class="alert alert-warning">There are no posts :(</div>
                            <?php
                        }
                        ?>
                        <a href="<?php echo TSA_REDIRECTURL; ?>/editor.php?add" class="btn btn-success">Add post</a><br />
                        <?php
                    }
                    mysqli_close( $con );
                ?>
                <br />
                <a href="<?php echo TSA_REDIRECTURL; ?>/?logout" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </body>
</html>
