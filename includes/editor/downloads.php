<?php
    $fetchFiletypes = mysqli_query( $con, "SELECT meta_value FROM " . TSA_DB_PREFIX . "settings WHERE meta_key='downloads_whitelist' LIMIT 1;" );
    $fetchPosts = mysqli_query( $con, "SELECT id, title FROM " . TSA_DB_PREFIX . "posts;" );
    $fetchUploadDirectory = mysqli_fetch_array( mysqli_query( $con, "SELECT meta_value FROM " . TSA_DB_PREFIX . "settings WHERE meta_key='downloads_location' LIMIT 1;" ) );
    $uploadDirectory = $fetchUploadDirectory['meta_value'];
    $fetchDownloads = mysqli_query( $con, "SELECT id, post_id, hash, filetype, original_file_name, size FROM " . TSA_DB_PREFIX . "downloads;" );
    $filetypesArray = mysqli_fetch_array( $fetchFiletypes );
    $wlFiletypes = json_decode( $filetypesArray['meta_value'], true );
    if( !empty( $_POST['post_id'] ) && !empty( $_FILES['file_upload'] ) && !empty( $uploadDirectory ) ) {
        $fileInfo = $_FILES['file_upload'];
        $originalFileName = $fileInfo['name'];
        $filesize = $fileInfo['size'];
        $fileHash = sha1( sprintf( "%s_%s", time(), $originalFileName ) );
        $filePathinfo = pathinfo( $originalFileName );
        $fileType = $filePathinfo['extension'];
        $post_id = intval( $_POST['post_id'] );
        if( !isset( $wlFiletypes[ $fileType ] ) ) {
            ?>
            <div class="alert alert-danger">This filetype has not been whitelisted and is not allowed.</div>
            <?php
        } elseif( !$post_id || mysqli_num_rows( mysqli_query( $con, "SELECT title FROM " . TSA_DB_PREFIX . "posts WHERE id='" . $post_id . "' LIMIT 1;" ) ) == 0 ) {
            ?>
            <div class="alert alert-danger">This post is invalid or does not exist.</div>
            <?php
        } else {
            $origName = mysqli_real_escape_string( $con, $originalFileName );
            $insertDl = mysqli_query( $con, "INSERT INTO " . TSA_DB_PREFIX . "downloads( post_id, hash, filetype, original_file_name, size, date ) VALUES( '" . $post_id . "', '" . $fileHash . "', '" . $fileType . "', '" . $origName . "', '" . $filesize . "', '" . date( "Y-m-d H:i:s" ) . "' );" );
            if( move_uploaded_file( $fileInfo['tmp_name'], $uploadDirectory . DIRECTORY_SEPARATOR . $fileHash . "." . $fileType ) && $insertDl ) {
                ?>
                <div class="alert alert-success">Download has been successfully added.</div>
                <?php
            } else {
                $checkError = mysqli_error( $con );
                if( $checkError ) {
                    ?>
                    <div class="alert alert-danger">MySQL Error! <?php echo $checkError; ?></div>
                    <?php
                } else {
                    ?>
                    <div class="alert alert-danger">Error moving uploaded file to downloads directory!</div>
                    <?php
                }
            }
        }
    }

    if( !empty( $_POST['delete_dl_id'] ) ) {
        $deleteID = intval( $_POST['delete_dl_id'] );

        if( $deleteID ) {
            $checkDL = mysqli_query( $con, "SELECT post_id, hash, filetype, original_file_name FROM " . TSA_DB_PREFIX . "downloads WHERE id='" . $deleteID . "' LIMIT 1;" );
            if( mysqli_num_rows( $checkDL ) == 0 ) {
                ?>
                <div class="alert alert-danger">This file does not exist.</div>
                <?php
            } else {
                $dlInfo = mysqli_fetch_array( $checkDL );
                if( unlink( $uploadDirectory . DIRECTORY_SEPARATOR . $dlInfo['hash'] . "." . $dlInfo['filetype'] ) && mysqli_query( $con, "DELETE FROM " . TSA_DB_PREFIX . "downloads WHERE id='" . $deleteID . "' LIMIT 1;" ) ) {
                    ?>
                    <div class="alert alert-success"><?php echo $dlInfo['original_file_name']; ?> was successfully deleted.</div>
                    <?php
                } else {
                    $checkError = mysqli_error( $con );
                    if( $checkError ) {
                        ?>
                        <div class="alert alert-danger">MySQL Error: <?php echo $checkError; ?></div>
                        <?php
                    } else {
                        ?>
                        <div class="alert alert-danger">Unable to delete file from directory. Please make sure the web server has the correct permissions.</div>
                        <?php
                    }
                }
            }
        } else {
            ?>
            <div class="alert alert-danger">Invalid file ID.</div>
            <?php
        }
    }

    if( empty( $uploadDirectory ) ) {
        ?>
        <div class="alert alert-danger">Upload directory not specified! Please contact an admin and ask them to specify this in the admin panel or file uploading will not work.</div>
        <?php
    }

    $fetchDownloads = mysqli_query( $con, "SELECT id, post_id, hash, filetype, original_file_name, size FROM " . TSA_DB_PREFIX . "downloads;" ); // Update after addition
?>
<div class="panel panel-success">
    <div class="panel-heading">Add a download</div>
    <div class="panel-body">
        <form method="post" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="post_id">Post to attach to:</label>
                <select id="post_id" name="post_id" required="" class="form-control" required="">
                    <option selected="">Please select a post</option>
                    <?php
                        while( $row = mysqli_fetch_array( $fetchPosts ) ) {
                            ?>
                            <option value="<?php echo $row[ 'id' ]; ?>"><?php echo stripslashes( $row['title'] ); ?></option>
                            <?php
                        }
                    ?>
                </select>
                <span class="help-block">This is the post that the downloadable file will be "attached" to.</span>
            </div>

            <div class="form-group">
                <label for="file_upload">File to upload:</label>
                <input type="file" name="file_upload" id="file_upload" class="form-control" required="" />
                <span class="help-block"><strong>Allowed filetypes: </strong><?php echo implode( ', ', array_keys( $wlFiletypes ) ); ?></span>
            </div>

            <button type="submit" class="btn btn-success">Add file</button>
        </form>
    </div>
</div>

<div class="panel panel-danger">
    <div class="panel-heading">Remove a download</div>
    <div class="panel-body">
        <form method="post" action="">
            <div class="form-group">
                <label for="delete_dl_id">Filename:</label>
                <select name="delete_dl_id" id="delete_dl_id" class="form-control">
                    <option selected="">Please select file to delete</option>
                    <?php
                        while( $row = mysqli_fetch_array( $fetchDownloads ) ) {
                            $id = $row['id'];
                            $post_id = $row['post_id'];
                            $hash = $row['hash'];
                            $fileType = $row['filetype'];
                            $fileName = $row['original_file_name'];
                            $size = $row['size'];
                            if( !isset( $postTiles[ $post_id ] ) ) {
                                $fetchingTitle = mysqli_fetch_array( mysqli_query( $con, "SELECT title FROM " . TSA_DB_PREFIX . "posts WHERE id='" . $post_id . "' LIMIT 1;" ) );
                                $postTitles[ $post_id ] = $fetchingTitle['title'];
                            }
                            ?>
                            <option value="<?php echo $id; ?>"><?php echo $fileName; ?> (<?php echo $postTitles[ $post_id ]; ?>)</option>
                            <?php
                        }
                    ?>
                </select>
            </div>

            <button class="btn btn-danger" type="submit" onclick="return confirm('Are you sure you want to delete this download? It will also delete the stored file.');">Delete download</button>
        </form>
    </div>
</div>
