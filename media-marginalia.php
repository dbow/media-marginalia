<?php

/*
Plugin Name: Media Marginalia
Plugin URI: https://github.com/dbow/media-marginalia
Description: Wordpress Plugin that provides an interface to create/read/update/delete multi-media annotations for a given media file.
Author: Danny Bowman
Version: 0.1
Author URI: http://www.d-bow.com
*/


# SET SOURCE FILE TO ANNOTATE.
function mm_get_source() {
  return '';
}

# SET GOOGLE MAPS API KEY.
function mm_get_gmaps_api_key() {
  return '';
}


# CREATE ANNOTATION CUSTOM POST TYPE.

add_action( 'init', 'mm_create_post_type' );

function mm_create_post_type() {
  register_post_type( 'mm_annotation',
    array(
      'labels' => array(
        'name' => __( 'Annotations' ),
        'singular_name' => __( 'Annotation' )
      ),
    'public' => true,
    'exclude_from_search' => true,
    'show_in_nav_menus' => false,
    # TODO(dbow): Add custom icon e.g. 'menu_icon' => '',
    'supports' => array('title', 'editor', 'author'),
    'taxonomies' => array('post_tag'),
    'rewrite' => array('slug' => 'annotations')
    )
  );
}


# CREATE META BOX.

add_action( 'add_meta_boxes', 'mm_create_meta_box' );

function mm_create_meta_box() {
  add_meta_box( 'annotation-meta-box', 'Annotation Meta Data', 'mm_meta_box_cb', 'mm_annotation', 'normal', 'high' );
}

function mm_meta_box_cb( $post ) {
  wp_nonce_field( 'mm_meta_box', 'mm_meta_box_nonce' );

  $values = get_post_custom( $post->ID );

  $shot = isset( $values['mm_annotation_shot'] ) ? esc_attr( $values['mm_annotation_shot'][0] ) : '';
  $timecode = isset( $values['mm_annotation_timecode'] ) ? esc_attr( $values['mm_annotation_timecode'][0] ) : '';
  $streetview = isset( $values['mm_annotation_streetview'] ) ? esc_attr( $values['mm_annotation_streetview'][0] ) : '';
  $x = isset( $values['mm_annotation_x'] ) ? esc_attr( $values['mm_annotation_x'][0] ) : '';
  $y = isset( $values['mm_annotation_y'] ) ? esc_attr( $values['mm_annotation_y'][0] ) : '';

  ?>
  <div>
      <label for="mm_annotation_shot">Shot</label>
      <select name="mm_annotation_shot" id="mm_annotation_shot">
        <?php if ($shot) { ?>
          <option value="<?php echo $shot; ?>" selected><?php echo $shot; ?></option>
        <?php } ?>
      </select>
  </div>
  <div>
      <video id="mm_annotation_source_player" src="<?php echo mm_get_source(); ?>" width="320" height="240" controls></video>
      <div id="mm_annotation_position_marker" style="width: 5px; height: 5px; border: 1px solid #00aeef; position: absolute; top: 0px; left: 0px;"></div>
  </div>
  <div>
      <label for="mm_annotation_timecode">Timecode</label>
      <input type="text" name="mm_annotation_timecode" id="mm_annotation_timecode" value="<?php echo $timecode; ?>" size="9" />
  </div>
  <div>
      <label>Screen Position</label>
      <span class="description">in percent from the top left [0 - 100].</span>
      <br />
      <label for="mm_annotation_x">X</label>
      <input type="text" name="mm_annotation_x" id="mm_annotation_x" value="<?php echo $x; ?>" size="1" />%
      <label for="mm_annotation_y" style="margin-left: 10px;">Y</label>
      <input type="text" name="mm_annotation_y" id="mm_annotation_y" value="<?php echo $y; ?>" size="1" />%
  </div>
  <div>
      <label>Location (Street View Image)</label>
      <br />
      <span class="description">e.g. https://www.google.com/maps/@40.709973,-73.950954,3a,75y,97.1h,96.71t/data=!3m4!1e1!3m2!1sVVz-u8IKFVY8DMJ1ZJHnQg!2e0</span>
      <img id="mm_location_img" style="display: block;"></img>
      <br />
      <label for="mm_annotation_streetview">URL</label>
      <input type="text" name="mm_annotation_streetview" id="mm_annotation_streetview" value="<?php echo $streetview; ?>" size="30" />
  </div>

  <?php
}


# ADD ANNOTATION JAVASCRIPT TO ADMIN PAGE.

add_action('admin_head','mm_add_custom_scripts');

function mm_add_custom_scripts() {
  // Only run this script on new and edit pages for annotations.
  global $post_type;
  if( $post_type !== 'mm_annotation' ) {
    return;
  }
  global $pagenow;
  if ( $pagenow !== 'post-new.php' && $pagenow !== 'post.php' ) {
    return;
  }

  ?>
  <script>

    jQuery(function() {

      // TIMECODE

      var timecodeElement = jQuery('#mm_annotation_timecode');

      var video = new MediaElement('mm_annotation_source_player', {
        alwaysShowControls: true,
        enableKeyboard: true,
        success: function (mediaElement, domObject) {
          mediaElement.addEventListener('loadeddata', function(e) {
            // If form already has timecode, set video to that point.
            if (timecodeElement.val()) {
              mediaElement.setCurrentTime(timecodeElement.val());
            }
            // Update form field anytime video changes.
            mediaElement.addEventListener('timeupdate', function(e) {
              var val = parseInt(timecodeElement.val(), 10);
              if (val !== mediaElement.currentTime) {
                timecodeElement.val(mediaElement.currentTime);
              }
            }, false);
          }, false);
        },
        error: function (e) {
          try {
            console.log('Error! ' + e);
          } catch (e) {}
        }
      });

      // Update video anytime form field changes.
      timecodeElement.on('change', function() {
        var val = parseInt(timecodeElement.val(), 10);
        if (video && video.currentTime !== val) {
          video.setCurrentTime(val);
        }
      });


      // SHOT

      // Get shots from google spreadsheet
      var SHOTS = [];

      var requestDataFromSpreadsheet = function() {
        var key = '10fRfd40j_bdieQuqDrUxMoS4hFvGX3Fdb98YdC9af9A',
            url = '//spreadsheets.google.com/feeds/cells/' +
            key + '/od6/public/basic?alt=json-in-script&callback=_handleShotData';
        // Via JSONP.
        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = url;
        jQuery(document.body).append(script);
      };

      window._handleShotData = function(data) {
        data = data.feed && data.feed.entry;
        if (!data || !data.length) {
          return;
        }

        // Parse response.
        var contents,
            cell,
            row,
            column,
            value;

        for (var i = 0, len = data.length; i < len; i++) {
          contents = data[i];
          cell = contents.title.$t; // e.g. "A1" or "C3".
          row = parseInt(cell.replace(/[a-zA-Z]/g, ''), 10); // Replace letters to get row e.g. 1 or 3.
          // Only handle rows other than header.
          if (row > 1) {
            column = cell.replace(/[0-9]/g, ''); // Replace numbers to get column e.g. "A" or "C".
            value = contents.content.$t; // Get value of cell.
            if (column === 'A') {
              // Put shot #s in SHOTS array.
              SHOTS.push(value);
            }
          }
        }

        // Add options to shot select element.
        var shotSelect = jQuery('#mm_annotation_shot');
        var selectedShot = shotSelect.val();
        jQuery.each(SHOTS, function(i, shot) {
          // If option is already selected, it will be in the select box already.
          if (shot === selectedShot) {
            return;
          }
          var shotOption = jQuery('<option></option>');
          shotSelect.append(shotOption.val(shot).text(shot));
        });
      };

      requestDataFromSpreadsheet();


      // SCREEN POSITION
      // Highlights the X/Y coordinate over top of the source video.

      var xEl = jQuery('#mm_annotation_x');
      var yEl = jQuery('#mm_annotation_y');
      var posEl = jQuery('#mm_annotation_position_marker');

      function positionChange() {
        var x = xEl.val();
        var y = yEl.val();
        console.log(x, y);
        if (x && y) {
          if (x >= 0 && x <= 100 && y >= 0 && y <= 100) {
            posEl.css('top', (y / 100) * video.height + 'px');
            posEl.css('left', (x / 100) * video.width + 'px');
          }
        }
      }

      positionChange();

      // Any time either changes, update image.
      xEl.on('change', positionChange);
      yEl.on('change', positionChange);


      // LATITUDE / LONGITUDE

      // Google Street View API Integration
      // Shows Street View Image of current lat/long coordinates.
      // https://developers.google.com/maps/documentation/streetview/

      var STREET_VIEW_HOST = '//maps.googleapis.com/maps/api/streetview?';
      var apiKey = '<?php echo mm_get_gmaps_api_key(); ?>';
      var protocol = apiKey ? 'https:' : ''; // Must be https with API key.

      // Typical street view URL:
      // https://www.google.com/maps/@40.709973,-73.950954,3a,75y,97.1h,96.71t/data=!3m4!1e1!3m2!1sVVz-u8IKFVY8DMJ1ZJHnQg!2e0
      // /@[latitude],[longitude],[unknown],[fov]y,[heading]h,[pitch + 90]t/

      // Map of param key to value.
      var streetViewParams = {
        size: '200x200',
        sensor: 'false'
      };

      function parseStreetViewUrl(url) {
        var re = /www\.google\.com\/maps\/@([^\/]+)\//;
        var streetViewInfo = re.exec(url);
        if (!streetViewInfo) {
          return false;
        }
        streetViewInfo = streetViewInfo[1].split(',');
        streetViewParams.location = streetViewInfo[0] + ',' + streetViewInfo[1];
        streetViewParams.fov = parseInt(streetViewInfo[3].replace('y', ''), 10);
        streetViewParams.heading = parseInt(streetViewInfo[4].replace('h', ''), 10);
        streetViewParams.pitch = 90 - parseInt(streetViewInfo[5].replace('t', ''), 10);
        return true;
      }

      var streetViewUrl = jQuery('#mm_annotation_streetview');
      var locationImage = jQuery('#mm_location_img');

      function locationChange() {
        var url = streetViewUrl.val();

        // Show the Street View Image for the given URL.
        if (url && parseStreetViewUrl(url)) {
          // Assemble URL.
          var params = [];
          for (var param in streetViewParams) {
            params.push(param + '=' + streetViewParams[param]);
          }
          if (apiKey) {
            params.push('key=' + apiKey);
          }
          // Set image SRC to street view URL and show image.
          locationImage.attr('src',
              protocol + STREET_VIEW_HOST + params.join('&')).show();

        // If there isn't a valid URL, hide the image.
        } else {
          locationImage.hide();
        }
      }

      // Update image based on any current lat/long inputs.
      locationChange();

      // Any time either changes, update image.
      streetViewUrl.on('change', locationChange);
    });

  </script>
  <?php
}


# HANDLE META BOX DATA.

add_action( 'save_post', 'mm_meta_box_save' );

function mm_meta_box_save( $post_id ) {
  // Post autosave should not overwrite metabox.
  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
    return;
  }

  // Check if nonce is set.
  if ( !isset( $_POST['mm_meta_box_nonce'] ) ) {
    return;
  }

  // and verify that the nonce is valid.
  if ( !wp_verify_nonce( $_POST['mm_meta_box_nonce'], 'mm_meta_box' ) ) {
    return;
  }

  // Verify current user has edit_post permissions.
  if ( !current_user_can( 'edit_post' ) ) {
    return;
  }

  // Actually save data.
  if ( isset( $_POST['mm_annotation_timecode'] ) ) {
    update_post_meta( $post_id, 'mm_annotation_timecode', esc_attr( $_POST['mm_annotation_timecode'] ) );
  }
  if ( isset( $_POST['mm_annotation_shot'] ) ) {
    update_post_meta( $post_id, 'mm_annotation_shot', esc_attr( $_POST['mm_annotation_shot'] ) );
  }
  if ( isset( $_POST['mm_annotation_streetview'] ) ) {
    update_post_meta( $post_id, 'mm_annotation_streetview', esc_attr( $_POST['mm_annotation_streetview'] ) );
  }
  if ( isset( $_POST['mm_annotation_x'] ) ) {
    update_post_meta( $post_id, 'mm_annotation_x', esc_attr( $_POST['mm_annotation_x'] ) );
  }
  if ( isset( $_POST['mm_annotation_y'] ) ) {
    update_post_meta( $post_id, 'mm_annotation_y', esc_attr( $_POST['mm_annotation_y'] ) );
  }
}
