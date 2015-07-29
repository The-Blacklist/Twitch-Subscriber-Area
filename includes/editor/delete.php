<?php
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
