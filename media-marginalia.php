<?php

/*
Plugin Name: Media Marginalia
Plugin URI: https://github.com/dbow/media-marginalia
Description: Wordpress Plugin that provides an interface to create/read/update/delete multi-media annotations for a given media file.
Author: Danny Bowman
Version: 0.1
Author URI: http://www.d-bow.com
*/


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
      <label for="mm_annotation_timecode">Timecode</label>
      <input type="text" name="mm_annotation_timecode" id="mm_annotation_timecode" value="<?php echo $timecode; ?>" />
  </p>
	<p>
			<label for="mm_annotation_shot">Shot</label>
			<input type="text" name="mm_annotation_shot" id="mm_annotation_shot" value="<?php echo $shot; ?>" />
	</p>
	<p>
			<label>Location</label>
			<span class="description">e.g. 40.710007,-73.950643</span>
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
