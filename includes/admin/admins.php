<?php
    if( !empty( $_POST['addAdmin'] ) ) {
        $newAdminName = mysqli_real_escape_string( $con, $_POST['addAdmin'] );
        $newAdminUID = $Twitch->getUserID( $newAdminName );
        if( $newAdminUID ) {
            if( !isset( $getAdmins[ $newAdminUID ] ) ) {
                $getAdmins[ $newAdminUID ] = array( 'name' => $newAdminName );
                $newAdminArray = json_encode( $getAdmins );
                $addAdminQuery = "UPDATE " . TSA_DB_PREFIX . "settings SET meta_value='" . $newAdminArray . "' WHERE meta_key='admins';";
                if( !mysqli_query( $con, $addAdminQuery ) ) {
                    ?>
                    <div class="alert alert-danger">MySQL error: <?php echo mysqli_error( $con ); ?></div>
                    <?php
                } else {
                    ?>
                    <div class="alert alert-success">Success! <strong><?php echo $newAdminName; ?></strong> has been successfully added as an admin.</div>
                    <?php
                }
            } else {
                ?>
                <div class="alert alert-warning">User already exists as an admin.</div>
                <?php
            }
        } else {
            ?>
            <div class="alert alert-danger">Error retrieving user (user most likely doesn't exist).</div>
            <?php
        }
    }

    if( !empty( $_POST['delAdmin'] ) ) {
        $delAdminUID = $_POST['delAdmin'];
        if( count( $getAdmins ) > 1 ) {
            if( isset( $getAdmins[ $delAdminUID ] ) ) {
                $delAdminName = $getAdmins[ $delAdminUID ][ 'name' ];
                unset( $getAdmins[ $delAdminUID ] );
                $delAdminArray = json_encode( $getAdmins );
                $delAdminQuery = "UPDATE " . TSA_DB_PREFIX . "settings SET meta_value='" . $delAdminArray . "' WHERE meta_key='admins';";
                if( !mysqli_query( $con, $delAdminQuery ) ) {
                    ?>
                    <div class="alert alert-danger">MySQL error: <?php echo mysqli_error( $con ); ?></div>
                    <?php
                } else {
                    ?>
                    <div class="alert alert-success">Success! <strong><?php echo $delAdminName; ?></strong> has been successfully removed as an admin.</div>
                    <?php
                }
            } else {
                ?>
                <div class="alert alert-danger">User is not an admin.</div>
                <?php
            }
        } else {
            ?>
            <div class="alert alert-danger">Since you are the only admin, deleting yourself is not allowed.</div>
            <?php
        }
    }
?>
<div class="panel panel-success">
    <div class="panel-heading">Add admin</div>
    <div class="panel-body">
        <form method="post" action="admin.php?page=admins">
            <div class="form-group">
                <label for="addAdmin">Username:</label>
                <input type="text" name="addAdmin" id="addAdmin" class="form-control" required="" />
            </div>
            <button type="submit" class="btn btn-success">Add admin</button>
        </form>
    </div>
</div>

<div class="panel panel-danger">
    <div class="panel-heading">Remove admin</div>
    <div class="panel-body">
        <form method="post" action="admin.php?page=admins">
            <div class="form-group">
                <label for="delAdmin">Username:</label>
                <select name="delAdmin" id="delAdmin" class="form-control">
                    <option>Select admin</option>
                    <?php
                        foreach( $getAdmins as $UID => $info ) {
                            ?>
                            <option value="<?php echo $UID; ?>"><?php echo $info['name']; ?></option>
                            <?php
                        }
                    ?>
                </select>
            </div>
            <button type="submit" class="btn btn-danger" onclick='return confirm( "Are you sure you want to remove this admin?" );' )>Remove admin</button>
        </form>
    </div>
</div>
