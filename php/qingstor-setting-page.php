<div class="wrap">
    <h1><?php _e('QingStor Settings', 'wp-qingstor'); ?></h1>
    <h1 class="nav-tab-wrapper">
        <a href="javascript:void(0)" class="nav-tab" onclick="tabs_switch(event, 'basic')" id="tab-title-basic"><?php _e('Bucket Settings', 'wp-qingstor'); ?></a>
        <a href="javascript:void(0)" class="nav-tab" onclick="tabs_switch(event, 'upload')" id="tab-title-upload"><?php _e('Upload Settings', 'wp-qingstor'); ?></a>
        <a href="javascript:void(0)" class="nav-tab" onclick="tabs_switch(event, 'backup')" id="tab-title-backup"><?php _e('Backup Settings', 'wp-qingstor'); ?></a>
    </h1>
    <form method="POST" action="">
        <div id="tab-basic" class="div-tab">
            <?php
            if ($qingstor_error != QS_REQUEST_OK) {
                qingstor_display_message('errors', $qingstor_error);
            } else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                if ($_REQUEST['once_backup']) {
                    qingstor_display_message('once_backup');
                } else if ($_REQUEST['upload_uploads']) {
                    qingstor_display_message('upload_uploads');
                } else {
                    qingstor_display_message('settings');
                }
            }
            ?>
            <p>*<?php _e('The following items need to be created at ', 'wp-qingstor'); ?><a target="_blank" href="https://console.qingcloud.com/access_keys/"><?php _e('QingCloud Console', 'wp-qingstor'); ?></a><?php _e('.', 'wp-qingstor'); ?></p>
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row">
                        <label  for="access">ACCESS KEY ID</label>
                    </th>
                    <td>
                        <input id="access" class="type-text regular-text" name="access_key" type="text" value="<?php echo $qingstor_access; ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="secret">SECRET ACCESS KEY</label>
                    </th>
                    <td>
                        <input id="secret" class="type-text regular-text" name="secret_key" type="text" value="<?php echo $qingstor_secret; ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bucket">Bucket <?php _e('Name', 'wp-qingstor'); ?></label>
                    </th>
                    <td>
                        <input id="bucket" class="type-text regular-text" name="bucket_name" type="text" value="<?php echo $qingstor_bucket; ?>">
                        <p class="description"><?php _e('All backups and Media files will be uploaded to the Bucket.', 'wp-qingstor'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="set_policy"><?php _e('Automaticlly Set Policy', 'wp-qingstor'); ?></label>
                    </th>
                    <td>
                        <input class="checkbox" type="checkbox" id="set_policy" name="set_policy" value="true" <?php echo $qingstor_set_policy ? "checked='checked'" : ''; ?>><?php _e('(Will set Acl as public read and set Bucket Policy as deny all users to get object from backup prefix. If not necessary, do not change it.)', 'wp-qingstor'); ?>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <div id="tab-upload" class="div-tab">
            <h2><?php _e('Operations', 'wp-qingstor'); ?></h2>
            <input id="upload_uploads" class="button button-primary" name="upload_uploads" value="<?php _e('Sync wp-content/uploads/ to Bucket', 'wp-qingstor'); ?>" type="submit">
            <h2><?php _e('Settings', 'wp-qingstor'); ?></h2>
            <div>
                <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row">
                            <label for="upload"><?php _e('File Types', 'wp-qingstor'); ?></label>
                        </th>
                        <td>
                            <input id="upload" class="type-text regular-text" type="text" name="upload_types" value="<?php echo $qingstor_upload_types; ?>">
                            <p class="description"><?php _e('File suffixes to upload.', 'wp-qingstor'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="upload_prefix"><?php _e('Upload Prefix', 'wp-qingstor'); ?></label>
                        </th>
                        <td>
                            <input id="upload_prefix" class="type-text regular-text" name="upload_prefix" type="text" value="<?php echo $qingstor_upload_prefix; ?>">
                            <p class="description"><?php _e('Media Files will be uploaded to the directory.', 'wp-qingstor'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bucket_url">Bucket URL</label>
                        </th>
                        <td>
                            <input id="bucket_url" class="type-text regular-text" name="bucket_url" type="text" value="<?php echo $qingstor_bucket_url; ?>">
                            <p class="description"><?php _e('Bucket URL. If there is a CDN, please fill in according to the actual situation. (Should add http:// or https://)', 'wp-qingstor'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="replace_url"><?php _e('Automaticlly Replace the Media Files ', 'wp-qingstor'); ?>URL</label>
                        </th>
                        <td>
                            <input class="checkbox" type="checkbox" id="replace_url" name="replace_url" value="true" <?php echo $qingstor_replace_url ? "checked='checked'" : ''; ?>>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div id="tab-backup" class="div-tab">
            <h2><?php _e('Operations', 'wp-qingstor'); ?></h2>
            <input id="once_backup" class="button button-primary" name="once_backup" value="<?php _e('Backup Now', 'wp-qingstor'); ?>" type="submit">
            <h2><?php _e('Settings', 'wp-qingstor'); ?></h2>
            <table class="form-table">
                <tbody>
                <tr>
                    <th>
                        <label for="backup_prefix"><?php _e('Backup Prefix', 'wp-qingstor'); ?></label>
                    </th>
                    <td>
                        <input id="backup_prefix" name="backup_prefix" type="text" class="text regular-text" value="<?php echo $qingstor_backup_prefix; ?>">
                        <p class="description"><?php _e('Backups will be stored in the directory.', 'wp-qingstor'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="schedule_type"><?php _e('Scheduled Backup', 'wp-qingstor'); ?></label>
                    </th>
                    <td>
                        <select id="schedule_type" name="schedule_recurrence[schedule_type]">
                            <option onclick="schedule_type_switch(0, 0, 0)" value="manually"><?php _e('Manually Only', 'wp-qingstor'); ?></option>
                            <option onclick="schedule_type_switch(0, 0, 0)" value="hourly"><?php _e('Once Hourly', 'wp-qingstor'); ?></option>
                            <option onclick="schedule_type_switch(0, 0, 1)" value="twicedaily"><?php _e('Twice Daily', 'wp-qingstor'); ?></option>
                            <option onclick="schedule_type_switch(0, 0, 1)" value="daily"><?php _e('Once Daily', 'wp-qingstor'); ?></option>
                            <option onclick="schedule_type_switch(1, 0, 1)" value="weekly"><?php _e('Once Weekly', 'wp-qingstor'); ?></option>
                            <option onclick="schedule_type_switch(1, 0, 1)" value="fortnightly"><?php _e('Once Every Two Weeks', 'wp-qingstor'); ?></option>
                            <option onclick="schedule_type_switch(0, 1, 1)" value="monthly"><?php _e('Once Monthly', 'wp-qingstor'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr id="start_day" style="display: table-row">
                    <th scope="row">
                        <label for="start_day_of_week"><?php _e('Start Day', 'wp-qingstor'); ?></label>
                    </th>
                    <td>
                        <select id="start_day_of_week" name="schedule_recurrence[start_day_of_week]">
                            <option value="monday"><?php _e('Monday', 'wp-qingstor'); ?></option>
                            <option value="tuesday"><?php _e('Tuesday', 'wp-qingstor'); ?></option>
                            <option value="wednesday"><?php _e('Wednesday', 'wp-qingstor'); ?></option>
                            <option value="thursday"><?php _e('Thursday', 'wp-qingstor'); ?></option>
                            <option value="friday"><?php _e('Friday', 'wp-qingstor'); ?></option>
                            <option value="saturday"><?php _e('Saturday', 'wp-qingstor'); ?></option>
                            <option value="sunday"><?php _e('Sunday', 'wp-qingstor'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr id="start_date" style="display: none">
                    <th scope="row">
                        <label for="start_day_of_month"><?php _e('Start Day of Month', 'wp-qingstor'); ?></label>
                    </th>
                    <td>
                        <input id="start_day_of_month" min="1" max="31" step="1" value="<?php echo $qingstor_recurrence['start_day_of_month']; ?>" name="schedule_recurrence[start_day_of_month]" type="number">
                    </td>
                </tr>
                <tr id="start_time" style="display: table-row">
                    <th scope="row">
                        <label for="start_hours"><?php _e('Start Time', 'wp-qingstor'); ?></label>
                    </th>
                    <td>
                        <span class="field-group">
                            <label for="">
                                <input id="start_hours" min="0" max="23" step="1" value="<?php echo $qingstor_recurrence['start_hours']; ?>" name="schedule_recurrence[start_hours]" type="number">
                                <?php _e('Hours', 'wp-qingstor'); ?>
                            </label>
                            <label for="start_minutes">
                                <input id="start_minutes" min="0" max="59" step="1" value="<?php echo $qingstor_recurrence['start_minutes']; ?>" name="schedule_recurrence[start_minutes]" type="number">
                                <?php _e('Minutes', 'wp-qingstor'); ?>
                            </label>
                        </span>
                        <p class="description"><?php _e('24-hour format.', 'wp-qingstor'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="backup_num"><?php _e('Number of backups to store', 'wp-qingstor'); ?></label>
                    </th>
                    <td>
                        <input type="number" min="1"  max="1000" step="1" id="backup_num" value="<?php echo $qingstor_nbackup; ?>" name="backup_num">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <input type="checkbox" id="sendmail" name="sendmail" value="sendmail">
                        <label for="mailaddr"><?php _e('Email to ', 'wp-qingstor'); ?></label>
                    </th>
                    <td>
                        <input type="email" name="mailaddr" id="mailaddr" value="<?php echo $qingstor_mail; ?>">
                        <p class="description"><?php _e('If it cannot send a mail, check your PHP mail settings.', 'wp-qingstor'); ?></p>
                    </td>
                </tr>
                </tbody>
            </table>
            <script>
                function schedule_type_switch(day, date, time) {
                    document.getElementById('start_day').style.display = day ? 'table-row' : 'none';
                    document.getElementById('start_date').style.display = date ? 'table-row' : 'none';
                    document.getElementById('start_time').style.display = time ? 'table-row' : 'none';
                }
                var select = document.getElementById('schedule_type');
                var str = "<?php echo $qingstor_recurrence['schedule_type']; ?>";
                for (var i = 0; i < select.options.length; i++) {
                    if (select.options[i].value == str) {
                        select.options[i].selected = true;
                        break;
                    }
                }
                select.options[select.selectedIndex].click();
                function tabs_switch(evt, tabname) {
                    var i, tabcontent, tablinks;
                    tabcontent = document.getElementsByClassName("div-tab");
                    for (i = 0; i < tabcontent.length; i++) {
                        tabcontent[i].style.display = "none";
                    }
                    tablinks = document.getElementsByClassName("nav-tab");
                    for (i = 0; i < tablinks.length; i++) {
                        tablinks[i].className = tablinks[i].className.replace(" nav-tab-active", "");
                    }
                    document.getElementById("tab-" + tabname).style.display = "block";
                    evt.currentTarget.className += " nav-tab-active";
                }
                document.getElementById("tab-title-basic").click();
            </script>
        </div>
        <p class="submit">
            <input id="submit" class="button button-primary" name="submit" value="<?php _e('Save Changes', 'wp-qingstor'); ?>" type="submit">
        </p>
    </form>
</div>
