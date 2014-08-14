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
  wp_enqueue_script('streetview',
    plugins_url('js/streetview.js', __FILE__));
  wp_enqueue_script('annotation-editor',
    plugins_url('js/annotation-editor.js', __FILE__));

  ?>
  <style>
    .mejs-container .mejs-controls .ui-slider div {
      width: auto;
      height: auto;
    }
  </style>

  <script>
    window.videoPath = '<?php echo mm_get_source(); ?>';
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


# ADD ANNOTATIONS BY DEFAULT TO CATEGORY PAGES
function add_custom_types_to_tax( $query ) {
  if( is_category() || is_tag() && empty( $query->query_vars['suppress_filters'] ) ) {
    // Get all your post types
    $post_types = array('post', 'mm_annotation');

    $query->set('post_type', $post_types);
    return $query;
  }
}
add_filter('pre_get_posts', 'add_custom_types_to_tax');


# CUSTOM TEMPLATE FOR SHOT-LIKE CATEGORIES
function load_shot_template($template) {
    $category_id = absint(get_query_var('cat'));
    $category = get_category($category_id);

    $templates = array();

    if ( !is_wp_error($category) )
        $templates[] = "category-{$category->slug}.php";

    $templates[] = "category-$cat_ID.php";

    // trace back the parent hierarchy and locate a template
    if ( !is_wp_error($category) ) {
      $category = $category->parent ? get_category($category->parent) : '';

      if(!empty($category)) {
        if (!is_wp_error($category)) {
          $templates[] = "category-{$category->slug}.php";
        }
        $templates[] = "category-{$category->term_id}.php";
      }
    }

    $templates[] = "category.php";
    $template = locate_template($templates);

    return $template;
}
add_action('category_template', 'load_shot_template');
