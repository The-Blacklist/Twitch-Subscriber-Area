<?php
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
