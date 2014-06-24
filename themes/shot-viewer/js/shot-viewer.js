var _shots = _shots || {};

(function(api) {

  'use strict';

  var app = {};

  // GET SHOT INFO FROM GOOGLE SPREADSHEET.

  var key = '10fRfd40j_bdieQuqDrUxMoS4hFvGX3Fdb98YdC9af9A',
      url = '//spreadsheets.google.com/feeds/cells/' +
            key +
            '/od6/public/basic?alt=json-in-script&callback=_shots.handleData',
      shots = {};

  app.requestDataFromSpreadsheet = function() {
    // Via JSONP.
    var script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = url;
    $(document.body).append(script);
  };

  api.handleData = function(data) {
    data = data.feed && data.feed.entry;
    if (!data || !data.length) {
      return;
    }

    // Parse response.
    var contents,
        cell,
        row,
        column,
        value,
        parseCellData,
        shots = {};

    function toSeconds(data) {
      if (!data) {
        return;
      }
      data = data.split(':');
      var mins = data[0];
      var secs = data[1];
      var frames = data[2];
      var FPS = 30;
      return Popcorn.util.toSeconds(mins + ':' + secs) + (frames / FPS);
    }

    // Functions to handle the data in the different columns
    parseCellData  = {
      'A': function(data, row) {
        shots[row].number = data;
      },
      'B': function(data, row) {
        shots[row].in = toSeconds(data);
      },
      'C': function(data, row) {
        shots[row].out = toSeconds(data);
      },
      'D': function(data, row) {
        shots[row].themes = data;
      },
      'E': function(data, row) {
        shots[row].location = data;
      }
    };


    for (var i = 0, len = data.length; i < len; i++) {
      contents = data[i];
      cell = contents.title.$t; // e.g. "A1" or "C3".
      row = parseInt(cell.replace(/[a-zA-Z]/g, ''), 10); // Replace letters to get row e.g. 1 or 3.
      // Only handle rows other than header.
      if (row > 1) {
        if (!shots[row]) {
          shots[row] = {};
        }
        column = cell.replace(/[0-9]/g, ''); // Replace numbers to get column e.g. "A" or "C".
        value = contents.content.$t; // Get value of cell.
        parseCellData[column](value, row);
      }
    }

    var filtered = [];
    var shot;
    for (var s in shots) {
      if (shots.hasOwnProperty(s)) {
        shot = shots[s];
        if (shot.in !== undefined && shot.out !== undefined) {
          filtered.push(shot);
        }
      }
    }

    app.createFootnotes(filtered);
    app.createShotVisualizer(filtered);
  };


  // POPCORN.JS VIDEO

  var video = new Popcorn('#lossures-video');
  video.controls(true);

  app.createFootnotes = function(shots) {
    var shot;
    var text;
    for (var s in shots) {
      if (shots.hasOwnProperty(s)) {
        shot = shots[s];
        text = '<div><span class="label">SHOT #:</span>' + shot.number + '</div>' +
               '<div><span class="label">THEMES:</span>' + shot.themes + '</div>' +
               '<div><span class="label">LOCATION:</span>' + (shot.location || '') + '</div>';
        video.footnote({
          start: shot.in,
          end: shot.out,
          text: text,
          target: 'shot-footnote'
        });
      }
    }
  };

  app.createShotVisualizer = function(shots) {
    var duration = video.duration();
    if (isNaN(duration)) {
      window.setTimeout(function() {
        app.createShotVisualizer.call(null, shots);
      }, 50);
      return;
    }
    var visualizer = $('#shot-visualizer');
    var lengthInPixels = visualizer.width();

    var toAppend = '';
    var w, l;
    for (var s in shots) {
      if (shots.hasOwnProperty(s)) {
        w = ((shots[s].out - shots[s].in) / duration) * lengthInPixels;
        l = (shots[s].in / duration) * lengthInPixels;
        toAppend += '<span id="shot-' + shots[s].number + '" style="width: ' + w + 'px; left: ' + l + 'px;"></span>';
      }
    }

    visualizer.html(toAppend);

    var current;
    video.on('timeupdate', function() {
      var t = video.currentTime();
      if (current && current.out > t && current.in < t) {
        return;
      }
      for (var s in shots) {
        if (shots.hasOwnProperty(s)) {
          if (shots[s].in < t && shots[s].out > t) {
            current = shots[s];
            break;
          }
        }
      }
      if (!current) {
        return;
      }
      $('.highlight-shot').removeClass('highlight-shot');
      $('#shot-' + current.number).addClass('highlight-shot');
    });
  };


  // DOM Ready listener to init.

  app.init = function() {
    app.requestDataFromSpreadsheet();
  };

  $(function() {
    app.init();
  });

}(_shots));

