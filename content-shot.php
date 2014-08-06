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

  <div id="video_container" style="width: 320px; height: 240px; position: relative; left: 120px;">
    <video id="" style="width: 100%; height: 100%;" src="<?php echo mm_get_source(); global $shot_category; echo $shot_category; ?>.mp4" width="320" height="240" controls></video>
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
  <div class="custom-fields">
    <?php
      $custom_fields = get_post_custom(get_the_ID());

      $street_view = $custom_fields['mm_annotation_streetview'];
      foreach ( $street_view as $key => $value ) {
        echo 'Street View URL: ' . $value . '<br />';
      }

    ?>
  </div>

  </div><!-- .entry-content -->


  <?php the_tags( '<footer class="entry-meta"><span class="tag-links">', '', '</span></footer>' ); ?>
</article><!-- #post-## -->
