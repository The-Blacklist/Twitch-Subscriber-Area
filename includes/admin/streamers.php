<?php
    $fetchStreamers = mysqli_fetch_array( mysqli_query( $con, "SELECT meta_value FROM " . TSA_DB_PREFIX . "settings WHERE meta_key='subscriber_streams' LIMIT 1;" ) );
    $getStreamers = json_decode( $fetchStreamers['meta_value'], true );
    if( !empty( $_POST['addStream'] ) ) {
        $addStreamName = mysqli_real_escape_string( $con, $_POST['addStream'] );
        $addStreamUID = $Twitch->getUserID( $_POST['addStream'] );
        if( $addStreamUID ) {
            $streamPartner = $Twitch->isAffiliateOrPartner( $_POST['addStream'] );
            if( !isset( $getStreamers[ $addStreamUID ] ) ) {
                if( $streamPartner ) {
                    $getStreamers[ $addStreamUID ] = array( 'name' => $addStreamName );
                    $newStreamsArray = json_encode( $getStreamers );
                    $updateStreams = "UPDATE " . TSA_DB_PREFIX . "settings SET meta_value='" . $newStreamsArray . "' WHERE meta_key='subscriber_streams';";
                    if( !mysqli_query( $con, $updateStreams ) ) {
                        ?>
                        <div class="alert alert-danger">MySQL error: <?php echo mysqli_error( $con ); ?></div>
                        <?php
                    } else {
                        ?>
                        <div class="alert alert-success">Success! <strong><?php echo $addStreamName; ?></strong> has been successfully added as a streamer.</div>
                        <?php
                    }
                } elseif( $streamPartner === NULL ) {
                    // API likes to go down sometimes...
                    ?>
                    <div class="alert alert-danger">The Twitch API seems to be struggling. Cannot verify if this user is partnered/affiliated.</div>
                    <?php
                } else {
                    ?>
                    <div class="alert alert-warning">This user is not a partnered/affiliated streamer. You can only add partnered/affiliated streamers to this website.</div>
                    <?php
                }
            } else {
                ?>
                <div class="alert alert-warning">User is already listed as a partnered/affiliated streamer.</div>
                <?php
            }
        } else {
            ?>
            <div class="alert alert-danger">Error retrieving user (user most likely doesn't exist).</div>
            <?php
        }
    }

    if( !empty( $_POST['delStream'] ) ) {
        $delStreamUID = $_POST['delStream'];
        if( isset( $getStreamers[ $delStreamUID ] ) ) {
            $delStreamName = $getStreamers[ $delStreamUID ][ 'name' ];
            unset( $getStreamers[ $delStreamUID ] );
            $delStreamArray = json_encode( $getStreamers );
            $delStreamQuery = "UPDATE " . TSA_DB_PREFIX . "settings SET meta_value='" . $delStreamArray . "' WHERE meta_key='subscriber_streams';";
            if( !mysqli_query( $con, $delStreamQuery ) ) {
                ?>
                <div class="alert alert-danger">MySQL error: <?php echo mysqli_error( $con ); ?></div>
                <?php
            } else {
                ?>
                <div class="alert alert-success">Success! <strong><?php echo $delStreamName; ?></strong> has been successfully removed as a streamer.</div>
                <?php
            }
        } else {
            ?>
            <div class="alert alert-warning">This streamer is not listed.</div>
            <?php
        }
    }
?>
<div class="panel panel-success">
    <div class="panel-heading">Add partnered/affiliated streamer</div>
    <div class="panel-body">
        <form method="post" action="admin.php?page=streamers">
            <div class="form-group">
                <label for="addStream">Twitch username:</label>
                <input type="text" name="addStream" id="addStream" class="form-control" required="" />
            </div>
            <button type="submit" class="btn btn-success">Add streamer</button>
        </form>
    </div>
</div>

<div class="panel panel-danger">
    <div class="panel-heading">Remove partnered/affiliated streamer</div>
    <div class="panel-body">
        <form method="post" action="admin.php?page=streamers">
            <div class="form-group">
                <label for="delStream">Twitch username:</label>
                <select name="delStream" id="delStream" class="form-control">
                    <option>Select streamer</option>
                    <?php
                        foreach( $getStreamers as $UID => $info ) {
                            ?>
                            <option value="<?php echo $UID; ?>"><?php echo $info['name']; ?></option>
                            <?php
                        }
                    ?>
                </select>
            </div>
            <button type="submit" class="btn btn-danger" onclick='return confirm( "Are you sure you want to remove this streamer?" );'>Remove streamer</button>
        </form>
    </div>
</div>
