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

	$category = isset( $values['mm_annotation_category'] ) ? esc_attr( $values['mm_annotation_category'][0] ) : '';
	$timecode = isset( $values['mm_annotation_timecode'] ) ? esc_attr( $values['mm_annotation_timecode'][0] ) : '';
	$shot = isset( $values['mm_annotation_shot'] ) ? esc_attr( $values['mm_annotation_shot'][0] ) : '';
	$lat = isset( $values['mm_annotation_lat'] ) ? esc_attr( $values['mm_annotation_lat'][0] ) : '';
	$long = isset( $values['mm_annotation_long'] ) ? esc_attr( $values['mm_annotation_long'][0] ) : '';
	$x = isset( $values['mm_annotation_x'] ) ? esc_attr( $values['mm_annotation_x'][0] ) : '';
	$y = isset( $values['mm_annotation_y'] ) ? esc_attr( $values['mm_annotation_y'][0] ) : '';

  ?>
	<p>
			<label for="mm_annotation_category">Category</label>
			<select name="mm_annotation_category" id="mm_annotation_category">
					<option value="story" <?php selected( $category, 'story' ); ?>>Story</option>
					<option value="place" <?php selected( $category, 'place' ); ?>>Place</option>
			</select>
	</p>
  <p>
			<video id="mm_annotation_source_player" src="<?php echo mm_get_source(); ?>" width="320" height="240" controls></video>
			<br />
      <label for="mm_annotation_timecode">Timecode</label>
      <input type="text" name="mm_annotation_timecode" id="mm_annotation_timecode" value="<?php echo $timecode; ?>" size="9" />
  </p>
	<p>
			<label for="mm_annotation_shot">Shot</label>
			<input type="text" name="mm_annotation_shot" id="mm_annotation_shot" value="<?php echo $shot; ?>" />
	</p>
	<p>
			<label>Location</label>
			<span class="description">e.g. 40.710007,-73.950643</span>
			<img id="mm_location_img" style="display: block;"></img>
			<br />
			<label for="mm_annotation_lat">Latitude</label>
			<input type="text" name="mm_annotation_lat" id="mm_annotation_lat" value="<?php echo $lat; ?>" size="8" />
			<label for="mm_annotation_long">Longitude</label>
			<input type="text" name="mm_annotation_long" id="mm_annotation_long" value="<?php echo $long; ?>" size="8" />
	</p>
	<p>
			<label>Screen Position</label>
			<span class="description">in pixels from the top left.</span>
			<br />
			<label for="mm_annotation_x">X</label>
			<input type="text" name="mm_annotation_x" id="mm_annotation_x" value="<?php echo $x; ?>" size="5" />
			<label for="mm_annotation_y">Y</label>
			<input type="text" name="mm_annotation_y" id="mm_annotation_y" value="<?php echo $y; ?>" size="5" />
	</p>

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


			// LATITUDE / LONGITUDE

			// Google Street View API Integration
			// Shows Street View Image of current lat/long coordinates.
			// https://developers.google.com/maps/documentation/streetview/

			var STREET_VIEW_HOST = '//maps.googleapis.com/maps/api/streetview?';
			var apiKey = '<?php echo mm_get_gmaps_api_key(); ?>';
			var protocol = apiKey ? 'https:' : ''; // Must be https with API key.

			// Map of param key to value.
			var streetViewParams = {
				// Required params
				size: '200x200',
				location: '', // Set via lat/long inputs.
				sensor: 'false'
				// TODO(dbow): Add support for configuring other URL params:
				// heading: '',
				// fov: '',
				// pitch: ''
			};

			var latEl = jQuery('#mm_annotation_lat');
			var longEl = jQuery('#mm_annotation_long');
			var locationImage = jQuery('#mm_location_img');

			function locationChange() {
				var lat = latEl.val();
				var long = longEl.val();

				// Show the Street View Image for the given lat/long.
				if (lat && long) {
					// Update params map.
					streetViewParams.location = latEl.val() + ',' + longEl.val();
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

				// If there isn't a lat or long, hide the image.
				} else {
					locationImage.hide();
				}
			}

			// Update image based on any current lat/long inputs.
			locationChange();

			// Any time either changes, update image.
			latEl.on('change', locationChange);
			longEl.on('change', locationChange);
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
  if ( isset( $_POST['mm_annotation_category'] ) ) {
    update_post_meta( $post_id, 'mm_annotation_category', esc_attr( $_POST['mm_annotation_category'] ) );
	}
	if ( isset( $_POST['mm_annotation_timecode'] ) ) {
		update_post_meta( $post_id, 'mm_annotation_timecode', esc_attr( $_POST['mm_annotation_timecode'] ) );
	}
	if ( isset( $_POST['mm_annotation_shot'] ) ) {
		update_post_meta( $post_id, 'mm_annotation_shot', esc_attr( $_POST['mm_annotation_shot'] ) );
	}
	if ( isset( $_POST['mm_annotation_lat'] ) ) {
		update_post_meta( $post_id, 'mm_annotation_lat', esc_attr( $_POST['mm_annotation_lat'] ) );
	}
	if ( isset( $_POST['mm_annotation_long'] ) ) {
		update_post_meta( $post_id, 'mm_annotation_long', esc_attr( $_POST['mm_annotation_long'] ) );
	}
	if ( isset( $_POST['mm_annotation_x'] ) ) {
		update_post_meta( $post_id, 'mm_annotation_x', esc_attr( $_POST['mm_annotation_x'] ) );
	}
	if ( isset( $_POST['mm_annotation_y'] ) ) {
		update_post_meta( $post_id, 'mm_annotation_y', esc_attr( $_POST['mm_annotation_y'] ) );
	}
}
