<?php
$delID = intval( preg_replace( '([\D])', '', $_GET['delete'] ) );
$delPost = mysqli_query( $con, "SELECT title, body FROM " . TSA_DB_PREFIX . "posts WHERE id='" . $delID . "' LIMIT 1;" );
$fetchDlDirectory = mysqli_fetch_array( mysqli_query( $con, "SELECT meta_value FROM " . TSA_DB_PREFIX . "settings WHERE meta_key='downloads_location' LIMIT 1;" ) );
$dlDir = $fetchDlDirectory['meta_value'];
$deleteFailed = false;
if( mysqli_num_rows( $delPost ) == 0 ) {
    ?>
    <div class="alert alert-danger">Post does not exist.</div>
    <?php
} else {
    $postInfo = mysqli_fetch_array( $delPost );
    $postTitle = $postInfo['title'];
    if( mysqli_query( $con, "DELETE FROM " . TSA_DB_PREFIX . "posts WHERE id='" . $delID . "';" ) ) {
        $fetchDownloads = mysqli_query( $con, "SELECT hash, filetype FROM " . TSA_DB_PREFIX . "downloads WHERE post_id='" . $delID . "';" );
        if( $fetchDownloads ) {
            while( $row = mysqli_fetch_array( $fetchDownloads ) ) {
                $hash = $row['hash'];
                $type = $row['filetype'];
                if( !unlink( $dlDir . DIRECTORY_SEPARATOR . $hash . "." . $type ) ) {
                    $deleteFailed = true;
                }
            }
            $deletePostDownloads = mysqli_query( $con, "DELETE FROM " . TSA_DB_PREFIX . "downloads WHERE post_id='" . $delID . "';" );
            if( !$deleteFailed && $deletePostDownloads ) {
                ?>
                <div class="alert alert-success">"<strong><?php echo $postTitle; ?></strong>" has been deleted.</div>
                <?php
            } else {
                if( mysqli_error( $con ) ) {
                    ?>
                    <div class="alert alert-danger">MySQL Error: <?php echo mysqli_error( $con ); ?></div>
                    <?php
                } else {
                    ?>
                    <div class="alert alert-danger">Error deleting file(s).</div>
                    <?php
                }
            }
        }
    } else {
        ?>
        <div class="alert alert-danger">Error! - <?php echo mysqli_error( $con ); ?></div>
        <?php
    }
}
?>
<a href="<?php echo TSA_REDIRECTURL; ?>/editor.php" class="btn btn-info">Back to editor page</a><br />
<?php
