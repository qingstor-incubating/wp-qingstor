  <div class="wrap">
      <h1><?php _e('QingStor Settings', 'wp-qingstor'); ?></h1>
      <h1 class="nav-tab-wrapper">
          <a href="javascript:void(0)" class="nav-tab" onclick="tabs_switch(event, 'basic')" id="tab-title-basic"><?php _e('Bucket Settings', 'wp-qingstor'); ?></a>
          <a href="javascript:void(0)" class="nav-tab" onclick="tabs_switch(event, 'upload')" id="tab-title-upload"><?php _e('Upload Settings', 'wp-qingstor'); ?></a>
      </h1>
      <form method="POST" action="">
          <div id="tab-basic" class="div-tab">
              <?php
                if ($qingstor_error != QS_REQUEST_OK) {
                    qingstor_display_message('errors', $qingstor_error);
                } else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                    if ($_REQUEST['upload_uploads']) {
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
                              <label for="access">ACCESS KEY ID</label>
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
                              <p class="description"><?php _e('Media files will be uploaded to the Bucket.', 'wp-qingstor'); ?></p>
                          </td>
                      </tr>
                      <tr>
                          <th>
                              <label for="set_policy"><?php _e('Automaticlly Set Policy', 'wp-qingstor'); ?></label>
                          </th>
                          <td>
                              <input class="checkbox" type="checkbox" id="set_policy" name="set_policy" value="true" <?php echo $qingstor_set_policy ? "checked='checked'" : ''; ?>><?php _e('(Will set Acl as public read. If not necessary, do not change it.)', 'wp-qingstor'); ?>
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
                      </tbody>
                  </table>
              </div>
          </div>
          <script>
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
          <p class="submit">
              <input id="submit" class="button button-primary" name="submit" value="<?php _e('Save Changes', 'wp-qingstor'); ?>" type="submit">
          </p>
      </form>
  </div>