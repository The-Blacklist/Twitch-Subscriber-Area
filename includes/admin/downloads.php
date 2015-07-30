<?php
    $fetchFiletypes = mysqli_fetch_array( mysqli_query( $con, "SELECT meta_value FROM " . TSA_DB_PREFIX . "settings WHERE meta_key='downloads_whitelist' LIMIT 1;" ) );
    $filetypes = json_decode( $fetchFiletypes['meta_value'], true );

    if( !empty( $_POST['add_filetype'] ) ) {
        $add_filetype = $_POST['add_filetype'];
        $mimeType = ( !empty( $_POST['add_mimetype'] ) ? $_POST['add_mimetype'] : 'application/octet-stream' );
        if( isset( $filetypes[ $add_filetype ] ) ) {
            ?>
            <div class="alert alert-warning">This filetype already exists with the MIME-type: <?php echo $filetypes[ $add_filetype ]; ?></div>
            <?php
        } else {
            $filetypes[ $add_filetype ] = $mimeType;
            $filetypes = json_encode( $filetypes );
            $updateFiletypes = mysqli_query( $con, "UPDATE " . TSA_DB_PREFIX . "settings SET meta_value='" . $filetypes . "' WHERE meta_key='downloads_whitelist';" );
            if( $updateFiletypes ) {
                ?>
                <div class="alert alert-success"><?php echo $add_filetype; ?> has successfully been added as a filetype.</div>
                <?php
            } else {
                ?>
                <div class="alert alert-danger">MySQL Error! <?php echo mysqli_error( $con ); ?></div>
                <?php
            }
        }
    } elseif( !empty( $_POST['del_filetype'] ) ) {
        $del_filetype = $_POST['del_filetype'];
        if( isset( $filetypes[ $del_filetype ] ) ) {
            unset( $filetypes[ $del_filetype ] );
            $filetypes = json_encode( $filetypes );
            $updateFiletypes = mysqli_query( $con, "UPDATE " . TSA_DB_PREFIX . "settings SET meta_value='" . $filetypes . "' WHERE meta_key='downloads_whitelist';" );
            if( $updateFiletypes ) {
                ?>
                <div class="alert alert-success"><?php echo $del_filetype; ?> has successfully been removed as a filetype.</div>
                <?php
            } else {
                ?>
                <div class="alert alert-danger">MySQL Error! <?php echo mysqli_error( $con ); ?></div>
                <?php
            }
        } else {
            ?>
            <div class="alert alert-warning">This filetype does not exist.</div>
            <?php
        }
    }
?>
<div class="panel panel-success">
    <div class="panel-heading">Add whitelisted filetype</div>
    <div class="panel-body">
        <form method="post" action="admin.php?page=downloads">
            <div class="form-group">
                <label for="add_filetype">File extension:</label>
                <input type="text" name="add_filetype" id="add_filetype" class="form-control" required="" />
                <p class="help-text">Do not include the period in front of the file extension.</p>
            </div>

            <div class="form-group">
                <label for="add_mimetype">MIME type:</label>
                <input type="text" name="add_mimetype" id="add_mimetype" class="form-control" placeholder="Optional" />
                <p class="help-text">This is an optional parameter that allows you to specify a specific <a href="https://en.wikipedia.org/wiki/MIME" target="_blank">MIME type</a> for certain filetypes.</p>
            </div>

            <button type="submit" class="btn btn-success">Add filetype</button>
        </form>
    </div>
</div>

<div class="panel panel-danger">
    <div class="panel-heading">Remove whitelisted filetype</div>
    <div class="panel-body">
        <form method="post" action="admin.php?page=downloads">
            <div class="form-group">
                <label for="del_filetype">File extension:</label>
                <select name="del_filetype" class="form-control" id="del_filetype" required="">
                    <option selected="">Select a filetype</option>
                    <?php
                        foreach( $filetypes as $ext => $mime ) {
                            ?>
                            <option value="<?php echo $ext; ?>"><?php echo $mime; ?></option>
                            <?php
                        }
                    ?>
                </select>
            </div>
            <button type="submit" class="btn btn-danger" onclick='confirm( "Are you sure you want to remove this filetyper?" );' )>Remove filetype</button>
        </form>
    </div>
</div>
