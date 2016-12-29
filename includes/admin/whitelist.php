<?php
    $checkWhitelist = mysqli_query( $con, "SHOW TABLES LIKE '" . TSA_DB_PREFIX . "whitelist';" );
    if( mysqli_num_rows( $checkWhitelist ) == 0 ) {
        // For v1.0 support
        $createWhitelist = mysqli_query( $con, "CREATE TABLE " . TSA_DB_PREFIX . "whitelist( id int NOT NULL AUTO_INCREMENT, PRIMARY KEY(id), name varchar(25), uid int UNIQUE );" );
        if( !$createWhitelist ) {
            ?>
            <div class="alert alert-danger">Unable to create <?php echo TSA_DB_PREFIX; ?>whitelist in the database!</div>
            <?php
            exit();
        }
    }

    if( !empty( $_POST['addWhitelist'] ) ) {
        $newWLName = mysqli_real_escape_string( $con, $_POST['addWhitelist'] );
        $newWLUID = $Twitch->getUserID( $newWLName );
        if( $newWLUID ) {
            $checkWL = mysqli_query( $con, "SELECT name FROM " . TSA_DB_PREFIX . "whitelist WHERE uid='$newWLUID' LIMIT 1;" );
            if( mysqli_num_rows( $checkWL ) == 0 ) {
                $insertWL = mysqli_query( $con, "INSERT INTO " . TSA_DB_PREFIX . "whitelist( name, uid ) VALUES( '$newWLName', '$newWLUID' );" );
                if( !$insertWL ) {
                    ?>
                    <div class="alert alert-danger">MySQL error: <?php echo mysqli_error( $con ); ?></div>
                    <?php
                } else {
                    ?>
                    <div class="alert alert-success">Success! <strong><?php echo $newWLName; ?></strong> has been successfully added to the whitelist.</div>
                    <?php
                }
            } else {
                ?>
                <div class="alert alert-warning">User is already whitelisted.</div>
                <?php
            }
        } else {
            ?>
            <div class="alert alert-warning">Error retrieving user (user most likely doesn't exist).</div>
            <?php
        }
    }

    if( !empty( $_POST['delWhitelist'] ) ) {
        $delWLUID = intval( $_POST['delWhitelist'] );
        $checkDelWL = mysqli_fetch_array( mysqli_query( $con, "SELECT name FROM " . TSA_DB_PREFIX . "whitelist WHERE uid='$delWLUID' LIMIT 1;" ) );
        if( $checkDelWL ) {
            $delWLName = $checkDelWL['name'];
            $delWLUser = mysqli_query( $con, "DELETE FROM " . TSA_DB_PREFIX . "whitelist WHERE uid='$delWLUID';" );
            if( !$delWLUser ) {
                ?>
                <div class="alert alert-danger">MySQL error: <?php echo mysqli_error( $con ); ?></div>
                <?php
            } else {
                ?>
                <div class="alert alert-success">Success! <strong><?php echo $delWLName; ?></strong> has been successfully deleted from the whitelist.</div>
                <?php
            }
        } else {
            ?>
            <div class="alert alert-warning">User is not whitelisted.</div>
            <?php
        }
    }
    $getWhitelist = mysqli_query( $con, "SELECT name,uid FROM " . TSA_DB_PREFIX . "whitelist;" );
?>
<div class="panel panel-success">
    <div class="panel-heading">Add whitelisted user</div>
    <div class="panel-body">
        <form method="post" action="admin.php?page=whitelist">
            <div class="form-group">
                <label for="addWhitelist">Twitch username:</label>
                <input type="text" name="addWhitelist" id="addWhitelist" class="form-control" reqired="" />
            </div>
            <button type="submit" class="btn btn-success">Add to whitelist</button>
        </form>
    </div>
</div>

<div class="panel panel-danger">
    <div class="panel-heading">Remove whitelisted user</div>
    <div class="panel-body">
        <form method="post" action="admin.php?page=whitelist">
            <div class="form-group">
                <label for="delWhitelist">Twitch username:</label>
                <select name="delWhitelist" id="delWhitelist" class="form-control" required="">
                    <option>Select user</option>
                    <?php
                        while( $row = mysqli_fetch_array( $getWhitelist ) ) {
                            ?>
                            <option value="<?php echo $row['uid']; ?>"><?php echo $row['name']; ?></option>
                            <?php
                        }
                    ?>
                </select>
            </div>
            <button type="submit" class="btn btn-danger" onclick='return confirm( "Are you sure you want to remove this user from the whitelist?" );' )>Remove from whitelist</button>
        </form>
    </div>
</div>
