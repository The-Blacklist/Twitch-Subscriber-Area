<h2>Installation - Step #2</h2>
<form method="post" action="install.php">
    <div class="form-group">
        <label for="db_host">MySQL Host (Default: localhost):</label>
        <input type="text" class="form-control" id="db_host" name="db_host" placeholder="localhost" />
    </div>
    <div class="form-group">
        <label for="db_username">MySQL Username:</label>
        <input type="text" class="form-control" id="db_username" name="db_username" placeholder="root" />
    </div>
    <div class="form-group">
        <label for="db_password">MySQL Password:</label>
        <input type="password" class="form-control" id="db_password" name="db_password" />
    </div>
    <div class="form-group">
        <label for="db_name">MySQL Database Name:</label>
        <input type="text" class="form-control" id="db_name" name="db_name" placeholder="tsa" />
    </div>
    <div class="form-group">
        <label for="db_tableprefix">MySQL Table Prefix (Default: tsa_):</label>
        <input type="text" class="form-control" id="db_tableprefix" name="db_tableprefix" placeholder="tsa_" />
    </div>
    <div class="form-group">
        <label for="twitch_apikey">Twitch API key:</label>
        <input type="text" class="form-control" id="twitch_apikey" name="twitch_apikey" />
    </div>
    <div class="form-group">
        <label for="twitch_secret">Twitch API secret:</label>
        <input type="password" class="form-control" id="twitch_secret" name="twitch_secret" />
    </div>
    <div class="form-group">
        <label for="twitch_redirect">Twitch redirect URL (Default: <?php echo $_SESSION['TSAURL']; ?> - Only modify if you're certain about what you're doing and <strong>make sure this is exactly the same as in your Twitch developer application settings</strong>:</label>
        <input type="text" class="form-control" id="twitch_redirect" name="twitch_redirect" placeholder="<?php echo $_SESSION['TSAURL']; ?>" />
    </div>
    <div class="form-group">
        <label for="downloads_location">Folder location to place downloadable files for subscribers:</label>
        <input type="text" class="form-control" name="downloads_location" id="downloads_location" placeholder="totally_hidden_files/" />
        <p class="help-block">Please make sure this location is writable. If the folder doesn't exist, TSA will attempt to create it. It is recommended to keep this folder away from the web directory or difficult to find.</p>
    </div>
    <button class="btn btn-success">Continue to step #3</button>
</form>
