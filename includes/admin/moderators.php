<?php
    $fetchMods = mysqli_fetch_array( mysqli_query( $con, "SELECT meta_value FROM " . TSA_DB_PREFIX . "settings WHERE meta_key='moderators' LIMIT 1;" ) );
    $getMods = json_decode( $fetchMods['meta_value'], true );
    if( !empty( $_POST['addMod'] ) ) {
        $newModName = mysqli_real_escape_string( $con, $_POST['addMod'] );
        $newModUID = $Twitch->getUserID( $newModName );
        if( $newModUID ) {
            if( !isset( $getMods[ $newModUID ] ) ) {
                $getMods[ $newModUID ] = array( 'name' => $newModName );
                $newModArray = json_encode( $getMods );
                $addModQuery = "UPDATE " . TSA_DB_PREFIX . "settings SET meta_value='" . $newModArray . "' WHERE meta_key='moderators';";
                if( !mysqli_query( $con, $addModQuery ) ) {
                    ?>
                    <div class="alert alert-danger">MySQL error: <?php echo mysqli_error( $con ); ?></div>
                    <?php
                } else {
                    ?>
                    <div class="alert alert-success">Success! <strong><?php echo $newModName; ?></strong> has been successfully added as a moderator.</div>
                    <?php
                }
            } else {
                ?>
                <div class="alert alert-warning">User already exists as a moderator.</div>
                <?php
            }
        } else {
            ?>
            <div class="alert alert-danger">Error retrieving user (user most likely doesn't exist).</div>
            <?php
        }
    }

    if( !empty( $_POST['delMod'] ) ) {
        $delModUID = $_POST['delMod'];
        if( isset( $getMods[ $delModUID ] ) ) {
            $delModName = $getMods[ $delModUID ][ 'name' ];
            unset( $getMods[ $delModUID ] );
            $delModArray = json_encode( $getMods );
            $delModQuery = "UPDATE " . TSA_DB_PREFIX . "settings SET meta_value='" . $delModArray . "' WHERE meta_key='moderators';";
            if( !mysqli_query( $con, $delModQuery ) ) {
                ?>
                <div class="alert alert-danger">MySQL error: <?php echo mysqli_error( $con ); ?></div>
                <?php
            } else {
                ?>
                <div class="alert alert-success">Success! <strong><?php echo $delModName; ?></strong> has been successfully removed as a moderator.</div>
                <?php
            }
        } else {
            ?>
            <div class="alert alert-danger">User is not a moderator.</div>
            <?php
        }
    }
?>
<div class="panel panel-success">
    <div class="panel-heading">Add moderator</div>
    <div class="panel-body">
        <form method="post" action="admin.php?page=moderators">
            <div class="form-group">
                <label for="addMod">Username:</label>
                <input type="text" name="addMod" id="addMod" class="form-control" required="" />
            </div>
            <button type="submit" class="btn btn-success">Add moderator</button>
        </form>
    </div>
</div>

<div class="panel panel-danger">
    <div class="panel-heading">Remove moderator</div>
    <div class="panel-body">
        <form method="post" action="admin.php?page=moderators">
            <div class="form-group">
                <label for="delMod">Username:</label>
                <select name="delMod" id="delMod" class="form-control">
                    <option>Select moderator</option>
                    <?php
                        foreach( $getMods as $UID => $info ) {
                            ?>
                            <option value="<?php echo $UID; ?>"><?php echo $info['name']; ?></option>
                            <?php
                        }
                    ?>
                </select>
            </div>
            <button type="submit" class="btn btn-danger" onclick='return confirm( "Are you sure you want to remove this moderator?" );' )>Remove moderator</button>
        </form>
    </div>
</div>
