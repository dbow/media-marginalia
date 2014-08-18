<?php
/**
 * The template for an individual annotation in the shots category.
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
  <?php twentyfourteen_post_thumbnail(); ?>

  <header class="entry-header">
    <?php if ( in_array( 'category', get_object_taxonomies( get_post_type() ) ) && twentyfourteen_categorized_blog() ) : ?>
    <?php
      endif;

      if ( is_single() ) :
        the_title( '<h1 class="entry-title">', '</h1>' );
      else :
        the_title( '<h1 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h1>' );
      endif;
    ?>

    <div class="entry-meta">
      <?php
        twentyfourteen_posted_on();
      ?>
      <?php
        edit_post_link( __( 'Edit', 'twentyfourteen' ), '<span class="edit-link">', '</span>' );
      ?>
    </div><!-- .entry-meta -->
  </header><!-- .entry-header -->

  <div class="entry-author">
    <?php echo get_avatar( get_the_author_meta( 'ID' ), 32 ); ?>
    <?php the_author_posts_link(); ?>
  </div>

  <?php
    $custom_fields = get_post_custom(get_the_ID());
    $start_timecode = $custom_fields['mm_annotation_start_timecode'];
    foreach ( $start_timecode as $key => $value ) {
      $start_timecode = $value;
    }
    $end_timecode = $custom_fields['mm_annotation_end_timecode'];
    foreach ( $end_timecode as $key => $value ) {
      $end_timecode = $value;
    }

    // Use the timestamp bounds param to limit video playback. e.g. #t=10,20
  ?>

  <div class="video-and-streetview group">
    <div id="video-container-<?php the_ID(); ?>" class="video-container">
      <video style="width: 100%; height: 100%;" data-start-timestamp="<?php echo $start_timecode; ?>" data-end-timestamp="<?php echo $end_timecode; ?>" autoplay="true" muted="true" src="<?php echo mm_get_source(); global $shot_category; echo $shot_category; ?>.mp4#t=<?php echo $start_timecode . ',' . $end_timecode; ?>" width="320" height="240"></video>
      <div class="timecodes">
        <span>start: <?php echo $start_timecode; ?></span>
        <span id="current-time-<?php the_ID(); ?>"></span>
        <span>end: <?php echo $end_timecode; ?></span>
      </div>
    </div>
    <div class="streetview-container">
      <img id="streetview-image-<?php the_ID(); ?>"></img>
      <script>
        <?php
          $street_view = $custom_fields['mm_annotation_streetview'];
          echo 'var streetViewUrls = [';
          foreach ( $street_view as $key => $value ) {
            echo '"' . $value . '",';
          }
          echo '];';
        ?>
        var params = streetViewUrls.length &&
                     _MM.StreetView.parseStreetViewUrl(streetViewUrls[0], '320x240');

        // Show the Street View Image for the given URL.
        if (params) {
          // Set image SRC to street view URL and show image.
          jQuery('#streetview-image-<?php the_ID(); ?>').attr('src',
              _MM.StreetView.buildStreetViewAPIUrl(params)).show();
        }
      </script>
    </div>
  </div>

  <div class="entry-content">
    <?php
      the_content( __( 'Continue reading <span class="meta-nav">&rarr;</span>', 'twentyfourteen' ) );
      wp_link_pages( array(
        'before'      => '<div class="page-links"><span class="page-links-title">' . __( 'Pages:', 'twentyfourteen' ) . '</span>',
        'after'       => '</div>',
        'link_before' => '<span>',
        'link_after'  => '</span>',
      ) );
  ?>

  </div><!-- .entry-content -->


  <?php the_tags( '<footer class="entry-meta"><span class="tag-links">', '', '</span></footer>' ); ?>
</article><!-- #post-## -->
