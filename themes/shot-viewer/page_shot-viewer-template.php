<?php
/*
Template Name: Shot Viewer
 */

?>
<!doctype html>
<html class="no-js" lang="">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Shot Viewer</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/../shot-viewer/style.css"></link>
    </head>
    <body>
        <div id="video-container">
          <video width="800px" height="600px" id="lossures-video">
            <source src="//www.uniondocs.org/los_sures/Diego_Echeveria_Los_Sures.mp4">
          </video>

          <div id="shot-visualizer"></div>
          <div id="shot-footnote"></div>
        </div>

        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
        <script>window.jQuery || document.write('<script src="js/vendor/jquery-1.11.1.min.js"><\/script>')</script>
        <script src="//cdn.popcornjs.org/code/dist/popcorn-complete.min.js"></script>

        <script type="text/javascript" src="<?php echo get_template_directory_uri(); ?>/../shot-viewer/js/shot-viewer.js"></script>
        <!-- Google Analytics: change UA-XXXXX-X to be your site's ID. -->
        <script>
            (function(b,o,i,l,e,r){b.GoogleAnalyticsObject=l;b[l]||(b[l]=
            function(){(b[l].q=b[l].q||[]).push(arguments)});b[l].l=+new Date;
            e=o.createElement(i);r=o.getElementsByTagName(i)[0];
            e.src='//www.google-analytics.com/analytics.js';
            r.parentNode.insertBefore(e,r)}(window,document,'script','ga'));
            ga('create','UA-XXXXX-X');ga('send','pageview');
        </script>
    </body>
</html>
