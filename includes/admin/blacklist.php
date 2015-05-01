<?php
    $checkBlacklist = mysqli_query( $con, "SHOW TABLES LIKE '" . TSA_DB_PREFIX . "blacklist';" );
    if( mysqli_num_rows( $checkBlacklist ) == 0 ) {
        // For v1.0 support
        $createBlacklist = mysqli_query( $con, "CREATE TABLE " . TSA_DB_PREFIX . "blacklist( id int NOT NULL AUTO_INCREMENT, PRIMARY KEY(id), name varchar(25), uid int UNIQUE, reason mediumtext );" );
        if( !$createBlacklist ) {
            ?>
            <div class="alert alert-danger">Unable to create <?php echo TSA_DB_PREFIX; ?>blacklist in the database!</div>
            <?php
            exit();
        }
    }

    if( !empty( $_POST['addBlacklist'] ) && !empty( $_POST['addBlacklistReason'] ) ) {
        $newBLName = mysqli_real_escape_string( $con, $_POST['addBlacklist'] );
        $newBLReason = mysqli_real_escape_string( $con, $_POST['addBlacklistReason'] );
        $newBLUID = $Twitch->getUserID( $newBLName );
        if( $newBLUID ) {
            $checkBL = mysqli_query( $con, "SELECT name, reason FROM " . TSA_DB_PREFIX . "blacklist WHERE uid='$newBLUID' LIMIT 1;" );
            if( mysqli_num_rows( $checkBL ) == 0 ) {
                $insertBL = mysqli_query( $con, "INSERT INTO " . TSA_DB_PREFIX . "blacklist( name, uid, reason ) VALUES( '$newBLName', '$newBLUID', '$newBLReason' );" );
                if( !$insertBL ) {
                    ?>
                    <div class="alert alert-danger">MySQL error: <?php echo mysqli_error( $con ); ?></div>
                    <?php
                } else {
                    ?>
                    <div class="alert alert-success">Success! <strong><?php echo $newBLName; ?></strong> has been successfully added to the blacklist.</div>
                    <?php
                }
            } else {
                $oldBLInfo = mysqli_fetch_array( $checkBL );
                ?>
                <div class="alert alert-warning"><?php echo $newBLName; ?> is already blacklisted with the following reason:</div>
                <div class="panel panel-warning">
                    <div class="panel-body"><?php echo $oldBLInfo['reason']; ?></div>
                </div>
                <?php
            }
        } else {
            ?>
            <div class="alert alert-warning">Error retrieving user (user most likely doesn't exist).</div>
            <?php
        }
    }

    if( !empty( $_POST['delBlacklist'] ) ) {
        $delBLUID = intval( $_POST['delBlacklist'] );
        $checkDelBL = mysqli_fetch_array( mysqli_query( $con, "SELECT name, reason FROM " . TSA_DB_PREFIX . "blacklist WHERE uid='$delBLUID' LIMIT 1;" ) );
        if( $checkDelBL ) {
            $delBLName = $checkDelBL['name'];
            $delBLUser = mysqli_query( $con, "DELETE FROM " . TSA_DB_PREFIX . "blacklist WHERE uid='$delBLUID';" );
            if( !$delBLUser ) {
                ?>
                <div class="alert alert-danger">MySQL error: <?php echo mysqli_error( $con ); ?></div>
                <?php
            } else {
                ?>
                <div class="alert alert-success">Success! <strong><?php echo $delBLName; ?></strong> has been successfully deleted from the blacklist.</div>
                <?php
            }
        } else {
            ?>
            <div class="alert alert-warning">User is not blacklisted.</div>
            <?php
        }
    }

    $getBlacklist = mysqli_query( $con, "SELECT name, reason, uid FROM " . TSA_DB_PREFIX . "blacklist;" );
?>

<div class="panel panel-success">
    <div class="panel-heading">Add blacklisted user</div>
    <div class="panel-body">
        <form method="post" action="admin.php?page=blacklist">
            <div class="form-group">
                <label for="addBlacklist">Twitch username:</label>
                <input type="text" name="addBlacklist" id="addBlacklist" class="form-control" required="" />
            </div>
            <div class="form-group">
                <label for="addBlacklistReason">Blacklist reason:</label>
                <textarea class="form-control" name="addBlacklistReason" id="addBlacklistReason" rows="10" cols="50" placeholder="Reason to be shown why the user is blacklisted (blacklisted user can see this after authenticating)" required=""></textarea>
            </div>
            <button type="submit" class="btn btn-success">Add blacklisted user</button>
        </form>
    </div>
</div>

<div class="alert alert-danger">Blacklisted users:</div>
<?php
    if( mysqli_num_rows( $getBlacklist ) == 0 ) {
        ?>
        <div class="alert alert-warning">There are no blacklisted users.</div>
        <?php
    } else {
        while( $row = mysqli_fetch_array( $getBlacklist ) ) {
            ?>
            <div class="panel panel-danger">
                <div class="panel-heading">Username: <strong><?php echo $row['name']; ?></strong> &mdash; User ID: <?php echo $row['uid']; ?></div>
                <div class="panel-body">
                    <?php echo nl2br( $row['reason'] ); ?>
                </div>
                <div class="panel-footer">
                    <form method="post" action="admin.php?page=blacklist">
                        <input type="hidden" name="delBlacklist" value="<?php echo $row['uid']; ?>" />
                        <button type="submit" class="btn btn-danger">Remove from blacklist</button>
                    </form>
                </div>
            </div>
            <?php
        }
    }
?>
