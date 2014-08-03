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
    'taxonomies' => array('post_tag', 'category'),
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

  $start_timecode = isset( $values['mm_annotation_start_timecode'] ) ? esc_attr( $values['mm_annotation_start_timecode'][0] ) : '';
  $end_timecode = isset( $values['mm_annotation_end_timecode'] ) ? esc_attr( $values['mm_annotation_end_timecode'][0] ) : '';
  $x = isset( $values['mm_annotation_x'] ) ? esc_attr( $values['mm_annotation_x'][0] ) : '';
  $y = isset( $values['mm_annotation_y'] ) ? esc_attr( $values['mm_annotation_y'][0] ) : '';
  $streetview = isset( $values['mm_annotation_streetview'] ) ? esc_attr( $values['mm_annotation_streetview'][0] ) : '';

  ?>
  <div id="video_container" style="width: 320px; height: 240px; position: relative;">
      <video id="mm_annotation_source_player" style="width: 100%; height: 100%;" src="" width="320" height="240" controls></video>
      <div id="mm_annotation_position_marker" style="width: 5px; height: 5px; border: 1px solid #00aeef; position: absolute; top: 0px; left: 0px;"></div>
  </div>
  <div>
      <label for="mm_annotation_start_timecode">Start Timecode</label>
      <input type="text" name="mm_annotation_start_timecode" id="mm_annotation_start_timecode" value="<?php echo $start_timecode; ?>" size="9" />
      <button id="set-start-button">Use current timecode</button>
  </div>
  <div>
      <label for="mm_annotation_end_timecode">End Timecode</label>
      <input type="text" name="mm_annotation_end_timecode" id="mm_annotation_end_timecode" value="<?php echo $end_timecode; ?>" size="9" />
      <button id="set-end-button">Use current timecode</button>
  </div>
  <div id="timecode-button-message"></div>
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

  wp_enqueue_script('jquery-ui-slider');
  wp_enqueue_script('jquery-ui-draggable');
  wp_enqueue_style('jquery-ui',
                   '//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/themes/smoothness/jquery-ui.css',
                   false,
                   PLUGIN_VERSION,
                   false);

  ?>
  <style>
    .mejs-container .mejs-controls .ui-slider div {
      width: auto;
      height: auto;
    }
  </style>

  <script>

    jQuery(function() {

      // TIMECODE

      var startTimecodeElement = jQuery('#mm_annotation_start_timecode');
      var endTimecodeElement = jQuery('#mm_annotation_end_timecode');

      var videoPath = '<?php echo mm_get_source(); ?>';
      var video;
      function setupVideo() {
        video = new MediaElementPlayer('#mm_annotation_source_player', {
          alwaysShowControls: true,
          enableKeyboard: true,
          defaultVideoHeight: 240,
          defaultVideoWidth: 320,
          videoHeight: 240,
          videoWidth: 320,
          success: function (mediaElement, domObject) {
            mediaElement.addEventListener('loadeddata', function(e) {
              // If form already has timecode, set video to that point.
              if (startTimecodeElement.val()) {
                mediaElement.setCurrentTime(startTimecodeElement.val());
              }
              mediaElement.addEventListener('timeupdate', checkVideoPlaybackBounds, false);
              mediaElement.addEventListener('play', function() {
                var currentTime = video.media.currentTime;
                if (currentTime < start || currentTime >= end) {
                  video.setCurrentTime(start);
                }
              });
            }, false);
            mediaElement.addEventListener('loadedmetadata', setupTimestampSlider);
          },
          error: function (e) {
            try {
              console.log('Error! ' + e);
            } catch (e) {}
          }
        });

        var slider;
        var start;
        var end;
        var duration;

        function checkBounds(val, lower, upper) {
          if (lower !== undefined && val < lower) {
            val = lower;
          }
          if (upper !== undefined && val > upper) {
            val = upper;
          }
          return val;
        }

        function checkVideoPlaybackBounds() {
          var currentTime = video.media.currentTime;
          var target = checkBounds(currentTime, start, end);
          if (target !== currentTime) {
            video.pause();
            video.setCurrentTime(target);
          }
        }

        function setupTimestampSlider() {
          var totalTimeContainer = jQuery('.mejs-time-total');
          slider = jQuery('<div></div>');
          slider.css('width', '100%');
          slider.css('height', 'auto');
          totalTimeContainer.append(slider);

          start = parseFloat(startTimecodeElement.val()) || 0;
          end = parseFloat(endTimecodeElement.val()) || video.media.duration;
          duration = Math.round(video.media.duration * 10) / 10;

          function updateBounds(e, ui) {
            start = ui.values[0];
            end = ui.values[1];
          }

          slider.slider({
            range: true,
            min: 0,
            max: duration,
            step: 0.1,
            values: [start, end],
            slide: function(event, ui) {
              updateBounds(event, ui);
              startTimecodeElement.val(start);
              endTimecodeElement.val(end === duration ? '' : end);
            },
            change: updateBounds
          });
        }

        startTimecodeElement.on('change', function() {
          var $this = jQuery(this);
          var newVal = parseFloat($this.val());
          var check = checkBounds(newVal, 0, (end || duration) - slider.slider('option', 'step'));
          slider.slider('values', 0, check);
          video.setCurrentTime(check);
          if (check !== newVal) {
            $this.val(check);
          }
        });
        endTimecodeElement.on('change', function() {
          var $this = jQuery(this);
          var newVal = parseFloat($this.val());
          var check = checkBounds(newVal, (start || 0) + slider.slider('option', 'step'), duration);
          slider.slider('values', 1, check);
          video.setCurrentTime(check);
          if (check !== newVal) {
            $this.val(check);
          }
        });


        jQuery('#set-start-button').on('click', function(e) {
          var msg = '';
          if (video.media.paused) {
            var t = video.media.currentTime;
            var check = checkBounds(t, 0, (end || duration) - slider.slider('option', 'step'));
            slider.slider('values', 0, check);
            startTimecodeElement.val(check);
          } else {
            msg = 'You must be paused to use the current timestamp';
          }
          jQuery('#timecode-button-message').text(msg);
          e.preventDefault();
        });
        jQuery('#set-end-button').on('click', function(e) {
          var msg = '';
          if (video.media.paused) {
            var t = video.media.currentTime;
            var check = checkBounds(t, (start || 0) + slider.slider('option', 'step'), duration);
            slider.slider('values', 1, check);
            endTimecodeElement.val(check);
          } else {
            msg = 'You must be paused to use the current timestamp';
          }
          jQuery('#timecode-button-message').text(msg);
          e.preventDefault();
        });

      }


      // SHOT

      var shotCategory = '';

      jQuery('#categorychecklist').on('change', 'input', checkForShot);

      function checkForShot() {
        var categoryTest = /^\d{1,4}$/g; // Matches 1-4 digit strings
        jQuery('[type="checkbox"][name="post_category[]"]:checked').each(function() {
          var categoryVal = jQuery(this).val();
          if (categoryTest.test(categoryVal)) {
            // convert to 0-padded 4 digit string.
            categoryVal = '000' + categoryVal;
            shotCategory = categoryVal.substr(categoryVal.length - 4);
            shotChange();
            return false;
          }
        });
      }

      checkForShot();

      function shotChange() {
        if (!shotCategory) {
          return;
        }
        var newVideo = videoPath + shotCategory + '.mp4';
        if (!video) {
          jQuery('#mm_annotation_source_player').attr('src', newVideo);
          setupVideo();
        } else {
          video.setSrc(newVideo);
        }
      }


      // SCREEN POSITION
      // Highlights the X/Y coordinate over top of the source video.

      var xEl = jQuery('#mm_annotation_x');
      var yEl = jQuery('#mm_annotation_y');
      var posEl = jQuery('#mm_annotation_position_marker');

      function positionChange() {
        var x = xEl.val();
        var y = yEl.val();
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

      posEl.draggable({
        containment: '#video_container',
        stop: function() {
          var x = posEl.css('left').replace('px', '');
          var y = posEl.css('top').replace('px', '');
          xEl.val((x / video.width) * 100);
          yEl.val((y / video.height) * 100);
        }
      });

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
        var re = /www\.google\.com\/maps.*\/@([^\/]+)\//;
        var streetViewInfo = re.exec(url);
        if (!streetViewInfo) {
          return false;
        }
        streetViewInfo = streetViewInfo[1].split(',');
        streetViewParams.location = streetViewInfo[0] + ',' + streetViewInfo[1];
        streetViewParams.fov = parseInt(streetViewInfo[3].replace('y', ''), 10);
        streetViewParams.heading = parseFloat(streetViewInfo[4].replace('h', ''));
        streetViewParams.pitch = 90 - parseFloat(streetViewInfo[5].replace('t', ''));
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
  if ( isset( $_POST['mm_annotation_start_timecode'] ) ) {
    update_post_meta( $post_id, 'mm_annotation_start_timecode', esc_attr( $_POST['mm_annotation_start_timecode'] ) );
  }
  if ( isset( $_POST['mm_annotation_end_timecode'] ) ) {
    update_post_meta( $post_id, 'mm_annotation_end_timecode', esc_attr( $_POST['mm_annotation_end_timecode'] ) );
  }
  if ( isset( $_POST['mm_annotation_x'] ) ) {
    update_post_meta( $post_id, 'mm_annotation_x', esc_attr( $_POST['mm_annotation_x'] ) );
  }
  if ( isset( $_POST['mm_annotation_y'] ) ) {
    update_post_meta( $post_id, 'mm_annotation_y', esc_attr( $_POST['mm_annotation_y'] ) );
  }
  if ( isset( $_POST['mm_annotation_streetview'] ) ) {
    update_post_meta( $post_id, 'mm_annotation_streetview', esc_attr( $_POST['mm_annotation_streetview'] ) );
  }
}
