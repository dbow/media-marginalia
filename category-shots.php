<?php
/**
 * The template for displaying SHOT category pages
 */

# TODO(dbow): This feels real wrong but not sure how to pass the category into "the loop"...
global $shot_category;
$shot_category = single_cat_title( '', false );

wp_enqueue_script('streetview',
                  plugins_url('js/streetview.js', __FILE__));

get_header(); ?>

  <style>
    #content {
      position: relative;
    }
    .archive-header {
      text-align: center;
    }
    .archive-title {
      font-size: 22px;
    }
    .shot-nav {
      position: absolute;
      left: 0px;
      right: 0px;
      top: 5px;
    }
    .shot-nav a {
      position: absolute;
    }
    .previous-shot {
      left: 100px;
    }
    .next-shot {
      right: 100px;
    }
    .group:after {
      content: "";
      display: table;
      clear: both;
    }
    .entry-author {
      margin: 0 auto;
      width: 100%;
      text-align: center;
    }
    .video-and-streetview {
      position: relative;
      width: 100%;
      margin-top: 15px;
      margin-bottom: 20px;
    }
    .video-container {
      width: 320px;
      height: 240px;
      float: left;
      margin-left: 170px;
      position: relative;
    }
    .position-marker {
      position: absolute;
      color: #fff;
      width: 30px;
      height: 30px;
      background-image: radial-gradient(circle, #00aeef, rgba(177, 181, 190, 0) 15px);
      text-align: center;
      margin-left: -15px;
      margin-top: -13px;
    }
    .timecodes {
      position: absolute;
      top: 240px;
      width: 320px;
      text-align: center;
    }
    .timecodes span {
      margin: 0 10px;
    }
    .streetview-container {
      float: left;
      margin-left: 70px;
    }
  </style>

  <section id="primary" class="content-area">
    <div id="content" class="site-content" role="main">

      <?php if ( have_posts() ) : ?>

      <header class="archive-header">
        <h1 class="archive-title"><?php printf( __( 'Shot #%s', 'twentyfourteen' ), single_cat_title( '', false ) ); ?></h1>


        <nav class="shot-nav">
          <a class="previous-shot" href="<?php echo get_category_link( get_category_by_slug(sprintf('%04d', (single_cat_title( '', false ) - 1)))->term_id ); ?>">previous shot</a>
          <a class="next-shot" href="<?php echo get_category_link( get_category_by_slug(sprintf('%04d', (single_cat_title( '', false ) + 1)))->term_id ); ?>">next shot</a>
        </nav>

        <?php
          // Show an optional term description.
          $term_description = term_description();
          if ( ! empty( $term_description ) ) :
            printf( '<div class="taxonomy-description">%s</div>', $term_description );
          endif;
        ?>
      </header><!-- .archive-header -->

      <?php
          // Start the Loop.
          while ( have_posts() ) : the_post();

          /*
           * Include the post format-specific template for the content. If you want to
           * use this in a child theme, then include a file called called content-___.php
           * (where ___ is the post format) and that will be used instead.
           */
          get_template_part( '../../plugins/media-marginalia/content', 'shot' );

          endwhile;
          // Previous/next page navigation.
          twentyfourteen_paging_nav();

        else :
          // If no content, include the "No posts found" template.
          get_template_part( 'content', 'none' );

        endif;
      ?>
      <script>
        jQuery('video').each(function() {
          this.addEventListener('pause', function() {
            this.currentTime = this.dataset.startTimestamp || this.played.start(0);
            this.play();
          }, true);
          this.addEventListener('timeupdate', function() {
            var idParts = this.parentElement.id.split('-');
            var id = idParts[idParts.length - 1];
            jQuery('#current-time-' + id).text(this.currentTime);
            if (this.dataset.endTimestamp && this.currentTime >= this.dataset.endTimestamp) {
              this.pause();
            }
          }, true);
        });

      </script>
    </div><!-- #content -->
  </section><!-- #primary -->

<?php
get_sidebar( 'content' );
get_sidebar();
get_footer();
