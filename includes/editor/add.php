<?php
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
