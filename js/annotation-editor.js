jQuery(function() {

  // TIMECODE

  var startTimecodeElement = jQuery('#mm_annotation_start_timecode');
  var endTimecodeElement = jQuery('#mm_annotation_end_timecode');

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
      if (!video.media.readyState) {
        // 0 means setCurrentTime will throw an error because there is no
        // playable resource yet.
        return;
      }
      var currentTime = video.media.currentTime;
      var target = checkBounds(currentTime, start, end);
      if (target !== currentTime) {
        video.pause();
        video.setCurrentTime(target);
      }
    }

    var sliderSetup = false;
    function setupTimestampSlider() {
      if (sliderSetup) {
        // Only do this once.
        return;
      }
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

      sliderSetup = true;
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
      var categoryVal = jQuery(this).parent().text().trim();
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

  var streetViewUrl = jQuery('#mm_annotation_streetview');
  var locationImage = jQuery('#mm_location_img');

  function locationChange() {
    var url = streetViewUrl.val();
    var params = _MM.StreetView.parseStreetViewUrl(url);

    // Show the Street View Image for the given URL.
    if (url && params) {
      // Set image SRC to street view URL and show image.
      locationImage.attr('src',
          _MM.StreetView.buildStreetViewAPIUrl(params)).show();
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

