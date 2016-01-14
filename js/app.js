$(document).ready(function() {


  /**
   * Renders the page on the initial load of the website.
   *
   * @return {String} url The url of the page that was rendered.
   */
  function initPage() {
    var url = window.location.href;
    url = url.substr(url.lastIndexOf("/") + 1, url.length);
    if(url === '') {
      url = 'tracemap';
    }
    renderPage(url);
    return url;
  }


  initPage();


  /**
   * Helper function to transform the first character of the passed String
   * to Uppercase.
   *
   * @param  {String} string The String to transform the first character
   * @return {String}        The transformed String
   */
  function fstUp(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
  }


  /**
   * Updates the URL in the browser so it matches the renedered site the user is on.
   *
   * @param  {String} newSubpage  The subpage the user navigated to and that
   *                              should be displayed in the browser's address bar.
   * @return {String} url         The subpage in the form of http://.. the user navigated to.
   */
  function updateUrl(newSubpage) {
    var url = window.location.href;
    url = url.substr(0, url.lastIndexOf("/")) + "/" + newSubpage;
    window.history.pushState("", "", url);
    return url;
  }


  /**
   * Renders the page-template (whose name is passed as a parameter to the
   * function) and updates the site-title and header.
   *
   * @param  {String} template   The name of the template that should be renedered.
   * @return {String}            The name of the template that was rendered.
   */
  function renderPage(template) {

    $('nav li').removeClass('selected');
    $('nav .view-' + template).addClass('selected');
    $('main').remove();
    $(document).prop('title', fstUp(template));
    $('header h1').empty();
    var title = template;
    if (template === 'tracemap') {
      title = 'tracemap.me';
    }
    $('header h1').append(fstUp(title));
    // Load the html-template into the div-Element with the class load-template
    $('div.load-template').load("./templates/" + template + ".html", function() {
      var view = $('main').attr('id');
      if(view == 'view-tracemap') {
        $('header h2').css('display', 'visible');
        drawMap(true);
        getInfo();
      } else if(view == 'view-statistics') {
        $('header h2').css('visibility', 'visible');
        drawMap(true, [], getTopTraces);
        getCountrycount();
        getInfo();
      } else if(view == 'view-about') {
        $('header h2').css('visibility', 'hidden');
      }
      //Once a new page is rendered we need to initialize all click-event-handlers
      //again, since they are "lost" when reloading a page.
      applyListeners();
      return template;
    });
  }


  /**
   * Initializes and displays a new google map.
   *
   * This function is like a "starting point" for the app. It initializes important
   * variables that are passed to the callback function via a "metaData"-Object.
   *
   * @param {boolean}   drawStartMarker Whether or not a marker for the location of
   *                                    the user should be drawn on the map. The start
   *                                    marker is placed on the location of the server.
   * @param {Array}     data            Optional data in an array that should be passed to
   *                                    the callback-function.
   * @param {function}  callback        A callback function that should be executed
   *                                    once the google map is finished loading. The callback
   *                                    receives the data-object, the map and the meta-Data-
   *                                    objects as parameters.
   * @return {Object}   map             The map that just was created.
   */
  function drawMap(drawStartMarker, data, callback) {
    window.navigator.geolocation.getCurrentPosition(function(start) {
      var startMarker;

      var markers = new Array();
      var coords  = new Array();
      var hops    = new Array();
      var bounds  = new Array();

      var metaData = {};
      metaData.markers = markers;
      metaData.coords  = coords;
      metaData.hops    = hops;
      metaData.bounds  = bounds;

      map = new google.maps.Map(document.getElementById("tm-map-initial"), {center: new google.maps.LatLng(start.coords.latitude, start.coords.longitude), zoom: 15});

      $('#tm-map-initial').css('background-image', 'none');
      if(drawStartMarker) {
        //Add the start marker to the google-map if wished
        // startMarker = { latitude: 43.4106, longitude: -80.5011, title : 'Start' };

        startMarker = { latitude: start.coords.latitude, longitude: start.coords.longitude, title : 'Start' };
        drawMarker(startMarker, generateInfoBoxText('Your current location', ''), metaData, adjustMapBounds, map);
        metaData.start = startMarker;
      }
      map.getZoom();
      if(callback && typeof callback == 'function') {
        callback(data, map, metaData);
      }
      return map;
    });
  }


  /**
   * Gets all the basic information/statistics via a GET-Request of the
   * REST-Api of the app.
   *
   * @return {Object}    data   The json-Object containing all the basic info, that is:
   *                            - Number of traceroutes in the DB
   *                            - Number of hops in the DB
   *                            - The average time to get to a hop
   */
  function getInfo() {
    $.ajax({
      method: "GET",
      url: "./api/info/basic",
      dataType: "json",
      success: function(data) {
        $('.numberOfTraces').html(data.numTraces);
        $('.numberOfHops').html(data.numHops);
        $('.averageHopTime').html(data.hopTime);
        $('.averageHopCount').html(data.averageHopsPerRoute);
        return data;
      }
    });
  }


  /**
   * Gets the top traces via a GET-Request to the REST-Api of the app.
   *
   * Once it has the data it calls a function to draw the topTens on the google map
   * and constructs a pie chart with a table displaying the data.
   *
   * @param   {Object}  data      Optional Array with data that was created before
   *                            	the google map was drawn.
   * @param   {Object}  map       The drawn google map.
   * @param   {Object}  metaData  An Object containing all the relevant metaData
   *                              that is necessary to process the top tens.
   * @return  {Object}  response  The topTen Traces as an Object.
   */
  function getTopTraces(data, map, metaData) {
    $.ajax({
      method: "GET",
      url: "./api/info/topTraces",
      dataType: "json",
      success: function(response) {
        var dataSet  = new Array();
        //Reorder the received data a bit
        for(var i = 0; i < response.length; i++) {
          dataSet.push({count: response[i].traceCount, label: response[i].url});
        }
        drawTopTens(metaData, response, map);
        constructPieChartWTable(dataSet);
        return response;
      }
    });
  }


  /**
   * Gets the countrycount via a GET-Request to the REST-Api of the app.
   *
   * Once it has the data it calls a function to construct a pie chart with a
   * table displaying the data.
   *
   * @return  {Object}  response  The topTen Traces as an Object.
   */
  function getCountrycount() {
    $.ajax({
      method: "GET",
      url: "./api/info/countryCount",
      dataType: "json",
      success: function(response) {
        constructPieChartWTableCountries(response);
        return response;
      }
    });
  }


  /**
   * Draws the top ten traced destinations on the google map and links them with
   * the starting point on the map.
   *
   * @param  {Object} metaData   An Object containing all the relevant metaData
   *                             that is necessary to display the top ten locations.
   * @param  {Array}  topTenData An array containing all the data of the top ten
   *                             traced locations.
   * @param  {Object} map        The google map to draw the markers for the top ten
   *                             locations on.
   * @return {Array}             The locations (longitude and latitude) of the top ten locations.
   */
  function drawTopTens(metaData, topTenData, map) {
    var points = new Array();
    points[0] = {lat: metaData.start.latitude, lng: metaData.start.longitude};
    for(i = 0; i < topTenData.length; i++) {
      points.push({
        lat: Number(topTenData[i].latitude),
        lng: Number(topTenData[i].longitude)
      });
      path = new google.maps.Polyline({
        path: points,
        map: map,
        geodesic: true,
        strokeColor: '#FF0000',
        strokeOpacity: 1.0,
        strokeWeight: 2
      });
      newTopTenMarker = {};
      newTopTenMarker = { latitude: points[1].lat, longitude: points[1].lng, title : topTenData[i].url };
      drawMarker(newTopTenMarker, generateInfoBoxText(topTenData[i].url, topTenData[i].ip, '', topTenData[i].country, topTenData[i].countryCode.toLowerCase()), metaData, '', map);
      points.pop();
    }
    adjustMapBounds(map, metaData);
    return points;
  }


  /**
   * Tests whether the passed URL is a valid one or not.
   *
   * @param  {String}  url The URL to validate.
   * @return {Boolean}     True, if the URL is valid, false otherwise.
   */
  function isUrlValid(url) {
    // Remove https:// and http://
    url = url.replace('https://', '').replace('http://', '');
    // Regex from http://stackoverflow.com/questions/2723140/validating-url-with-jquery-without-the-validate-plugin
    var valid = url.indexOf('/') === -1;
    valid &= /^(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(url);
    return valid;
  }


  /**
   * Resets the submit and cancel button to its initial state.
   *
   * First the text of the submit button is checked to see if it needs to be reset.
   */
  function resetSubmitAndCancelButton() {
    if ($('#submitBtn').html() === 'Loading Data…') {
      $('#submitBtn').css('background-color', '#3E9FFF');
      $('#submitBtn').html('Trace it!');
      $('#submitBtn').prop('disabled', false);
      $('#submitBtn').css('margin-left', 'auto');
      $('#cancelBtn').removeClass('show-button');
      $('#cancelBtn').addClass('hide-button');
      $('#tm-search input').prop('disabled', false);
      $('#tm-search input').css('color', '#000');
    }
  }


  /**
   * Gets all the hops to a given URL in real-time.
   *
   * The function calls itself recursively after 500ms if the traceroute execution is not yet
   * finished.
   *
   * @param  {Array}  data       Array containing all the data of the initially pinged url.
   * @param  {map}               The google map.
   * @param  {Object} metaData   An Object containing all the relevant metaData
   *                             that is necessary to execute a traceroute-execution.
   * @return {Object} tcdata     The data the traceroute got.
   */
  function startTraceroute(data, map, metaData) {
    $.ajax({
      method: "GET",
      url: "./api/traceroute/" + data.id,
      dataType: "json",
      success: function(data) {
        if ($('#submitBtn').html() === 'Loading Data…') {
          if (data.inProgress) {
            setTimeout(function() {
              startTraceroute(data, map, metaData);
            }, 500);
            processTracerouteData(data, map, metaData);
          } else {
            processTracerouteData(data, map, metaData);
            buildHopTimeGraph(data.data);
            resetSubmitAndCancelButton();
            return data;
          }
        }
      }
    });
  }


  /**
   * Processes the response of a traceroute-request.
   *
   * This function does the actual work of the traceroute output: it builds up the
   * table with all the traceroute-data and draws the markers for the hops on the
   * google map.
   *
   * @param  {Array}  response   Array containing all the data of the traceroute-execution.
   * @param  {map}               The google map to draw the markers on.
   * @param  {Object} metaData   An Object containing all the relevant metaData
   *                             that is necessary to process the traceroute data.
   * @return {Object} metaData   An object containing all the data of the hops.
   *
   */
  function processTracerouteData(response, map, metaData) {
    for(var key in response.data) {

      //Save data for one hop in helper-variable
      var hopData = response.data[key];
      metaData.drawLine = true;
      if (hopData.message) {
        $('#tm-data h3').text(fstUp(hopData.message));
      } else {
        //Test if hopNumber is already present. If not, add and draw marker
        if(metaData.hops.indexOf(hopData.hopNumber) === -1) {
          metaData.hops.push(hopData.hopNumber);

          drawMarker(hopData, generateInfoBoxText(hopData.hostname, hopData.ip, hopData.hopNumber), metaData, adjustMapBounds, map);

        }
        generateTableRow(hopData);
        //Test if last row has any * If so, then update the row
        //but do not get any location data, do not draw any markers etc
        if(key === response.length - 1 && (hopData.rtt1 === '*' || hopData.rtt2 === '*' || hopData.rtt3 === '*')) {
          generateTableRow(hopData);
        }
      }
    }
    return metaData;
  }


  /**
   * Creates a new marker and draws it on the google map.
   *
   * @param  {Object}   position    An object containing the position of the marker
   *                                to draw.
   * @param  {Object}   InfoBoxText An object containing all the text to put into the
   *                                infobox of the google marker.
   * @param  {Object}   metaData    An Object containing all the relevant metaData
   *                                that is necessary to draw the marker.
   * @param  {Function} callback    A function that is called once the process
   *                                of drawing the marker on the map is finished
   * @param  {Object}   map         The google map to draw the marker on.
   * @return {Object}   marker      The marker that was drawn on the google map.
   *                                Empty, if no marker was drawn.
   */
  function drawMarker(position, InfoBoxText, metaData, callback, map) {
    if((position.latitude && position.longitude) && (!isNaN(position.latitude) && !isNaN(position.latitude))) {
      var pos = new google.maps.LatLng(position.latitude, position.longitude);
      var newMarker = new google.maps.Marker({
        position: pos,
        map: map
      });

      var infoWindow = new google.maps.InfoWindow({
        content: InfoBoxText
      });

      newMarker.addListener('click', function() {
        infoWindow.open(map, newMarker);
      })

      var objNr = metaData.coords.push(pos);
      metaData.markers.push(newMarker);
      if(metaData.drawLine && objNr > 1) {
        drawLine(map, metaData, objNr - 1);
      }
      if(typeof(callback) == 'function') {
        callback(map, metaData);
      }
      return newMarker;
    }
    return {};
  }


  /**
   * Draws a line between two markers on a google map.
   *
   * @param  {Object}   map      The google map to draw the line on.
   * @param  {Object}   metaData An Object containing all the relevant metaData
   *                             that is necessary to draw a line.
   * @param  {Int}      index    The index of the point to draw the line to
   * @return {Object}   line     The line that was drawn.
   */
  function drawLine(map, metaData, index) {
    var coords = new Array();
    coords.push({
      lat: metaData.coords[index-1].lat(),
      lng: metaData.coords[index-1].lng()
    });
    coords.push({
      lat: metaData.coords[index].lat(),
      lng: metaData.coords[index].lng()
    });
    path = new google.maps.Polyline({
      path: coords,
      map: map,
      geodesic: true,
      strokeColor: '#FF0000',
      strokeOpacity: 1.0,
      strokeWeight: 2
    });
    return path;
  }


  /**
   * Creates a table row for a hop together with a country flag, if the country-code is present.
   *
   * @param  {Object} response  The hop data
   * @return {Object} tablerow  The tablerow that just was created
   */
  function generateTableRow(hopData) {
    var countryFlag = '';

    if(hopData.countryCode) {
      var countryFlag = '<img src="./img/blank.gif" class="flag flag-'  + hopData.countryCode.toLowerCase() + '"></img>';
    }
    var rtt1, rtt2, rtt3, hostname;
    if (hopData.rtt1 === '-1') { rtt1 = '*'; }
    else if (hopData.rtt1 === '0') { rtt1 = '' }
    else { rtt1 = hopData.rtt1; };
    if (hopData.rtt2 === '-1') { rtt2 = '*'; }
    else if (hopData.rtt2 === '0') { rtt2 = '' }
    else { rtt2 = hopData.rtt2; };
    if (hopData.rtt3 === '-1') { rtt3 = '*'; }
    else if (hopData.rtt3 === '0') { rtt3 = '' }
    else { rtt3 = hopData.rtt3; };
    if (hopData.hostname === null) { hostname = ''; } else { hostname = hopData.hostname; };
    // If the table-row already exists and we want to insert it again (since there
    // is no new row, but possibly a new hoptime-value) we delete the row
    // with the old data first.
    if ($('#traceroute-table tbody tr.' + hopData.hopNumber)) {
      $('#traceroute-table tbody tr#' + hopData.hopNumber).remove();
    }
    var tablerow = "<tr id='" + hopData.hopNumber + "'><td>"
    + countryFlag + "</td><td>" + hopData.hopNumber + "</td><td>"
    + hostname + "</td><td>" + hopData.ip + "</td><td>"
    + rtt1 + "</td><td>" + rtt2 + "</td><td>" + rtt3 + "</td>";
    $('#traceroute-table tbody').append(tablerow);

    return tablerow;
  }


  /**
   * Applys click-event-handlers to elements.
   */
  function applyListeners() {


    /**
     * Click-Event-Listener on the "Tracemap"-Nav-Item.
     * Redirects the user to the tracemap-page and initializes the rendering
     * of the page.
     *
     * @return {String}   url   The url that the user was redirected to.
     */
    $('.view-tracemap').on('click', function(e) {
      e.preventDefault();
      var url = updateUrl('tracemap');
      renderPage('tracemap');
      return url;
    });


    /**
     * Click-Event-Listener on the "Statistics"-Nav-Item.
     * Redirects the user to the statistics-page and initializes the rendering
     * of the page.
     *
     * @return {String}   url   The url that the user was redirected to.
     */
    $('.view-statistics').on('click', function(e) {
      e.preventDefault();
      var url = updateUrl('statistics');
      renderPage('statistics');
      return url;
    });

    /**
     * Click-Event-Listener on the "About"-Nav-Item.
     * Redirects the user to the about-page and initializes the rendering
     * the page.
     *
     * @return {String}   url   The url that the user was redirected to.
     */
    $('.view-about').on('click', function(e) {
      e.preventDefault();
      var url = updateUrl('about');
      renderPage('about');
      return url;
    });

    /**
     * Click-Event-Listener to handle click-events on the trace-it button to
     * start a traceroute.
     */
    $('#tm-search #submitBtn').on('click', function(e) {
      e.preventDefault();

      $('#tm-data-raw ul').html('');
      $('#traceroute-table tbody').html('');

      var url = $('#tm-search input').val();

      // If a user clicks before the button can be disabled, it might still get
      // through. This line will catch those invalid urls.
      if(!isUrlValid(url)) {
        return;
      }

      url = url.replace('https://', '').replace('http://', '');

      /**
       * As soon as the user clicks on the 'traceroute'-button display a message
       * on the button itself, that we are loading the data. Furthermore, delete
       * hop data from a previous request - if there is any - and print the current
       * url into the header of the tracemap-stats section under the google map.
       */
      $('#submitBtn').html('Loading Data…');
      $(this).css('background-color', '#aaaaaa');
      $(this).prop('disabled', true);

      /**
       * Display the cancel button
       */
      $('#cancelBtn').removeClass('hide-button');
      $('#cancelBtn').addClass('show-button');

      /**
       * Click-Event-Listener on the cancel button.
       * Resets the submit button to its initial state and hides the cancel button
       */
      $('#cancelBtn').on('click', function(e) {
        e.preventDefault();
        resetSubmitAndCancelButton();
      });

      $(this).css('margin-left', '0');

      $('#tm-search input').prop('disabled', true);
      $('#tm-search input').css('color', '#aaa');
      $('#tm-data ul').html('');
      $('#tm-data h2').html('Tracemap-Stats for Destination ' + url);

      $.ajax({
        method: "GET",
        url: "./api/" + url,
        dataType: "json",
        success: function(data) {
          drawMap(true, data, startTraceroute);
          $('#tm-data').css("display", "block");
        }
      });
    });


    /**
     * Tests on every key-up-event in the input-field of the "Tracemap"-page
     * whether the passed URL is a valid one or not, since only valid urls can be
     * traced.
     */
    $('#tm-search').on('keyup', function(e) {
      if(e.keyCode !== '13') {
        var url = $('#tm-search input').val();
        if(!isUrlValid(url)) {
          $('#submitBtn').css('background-color', '#aaaaaa');
          $('#submitBtn').html('Invalid URL!');
          $('#submitBtn').prop('disabled', true);
        } else {
          $('#submitBtn').css('background-color', '#3E9FFF');
          $('#submitBtn').html('Trace it!');
          $('#submitBtn').prop('disabled', false);
        }
      } else {
        e.preventDefault();
        $('#tm-search button').click();
      }
    });
  };


  /**
   * Generates an infobox for a marker on a google map.
   *
   * @param  {String} hostname The hostname of the hop to display in the infoxbox
   * @param  {String} ip       The ip-address of the hop to display
   * @param  {String} hopNr    The HopNr of the hop
   * @return {String} string   A string containing all the necessary html
   *                           to display a nice Infobox for a marker a the google map
   */
  function generateInfoBoxText(hostname, ip, hopNr, hopCountry, hopCountrycode) {
    var hopStr = '';
    var ipStr = '';
    var hopCtr = '';
    if(hopNr) {
      hopStr = 'Hop-Nr: ' + hopNr + ' &#64; ';
    }
    if(ip) {
      ipStr = '<div id="bodyContent">'+
        '<p>The IP-Address is: '+ ip +'</p>' +
      '</div>';
    }
    if(hopCountry) {
      hopCtr = '<p><img class="flag flag-'  + hopCountrycode + '"></img> ' + hopCountry + '</p>';
    }
    var string = '<div id="content">'+
      '<h4 id="firstHeading" class="firstHeading">' + hopStr + hostname + '</h4>' + ipStr + hopCtr +
    '</div>';
    return string;
  }


  /**
   * Adjusts the "zoom" of the google map to display all existing markers on
   * the map.
   *
   * This prevents displaying a google map with a zoom to close
   * to see all existing markers. This would be a pitty.
   *
   * @param  {Object}   map      The google map to draw the line on.
   * @param  {Object}   metaData An Object containing all the relevant metaData
   *                             that is necessary to adjust the mapBounds.
   * @return {Object}   metaData The metaData with an upated bounds-Array.
   */
  function adjustMapBounds(map, metaData) {
    metaData.bounds = new google.maps.LatLngBounds();
    for(i = 0; i < metaData.markers.length; i++) {
      metaData.bounds.extend(metaData.markers[i].getPosition());
    }
    if(metaData.markers.length > 1) {
      map.fitBounds(metaData.bounds);
    }
  }


  /**
   * Constructs a pie chart with a corresponding table displaying a name - value
   * pair.
   *
   * @param  {Object}  data  Object containing the data that should be displayed.
   * @return {Object}  path  The created pie chart.
   */
  function constructPieChartWTable(data) {
    //remove old charts
    $('#topTenChart').empty();
    var width = 450;
    var height = 400;
    var radius = 130;
    var color = d3.scale.category20c();
    var svg = d3.select('#topTenChart')
      .append('svg')
      .attr('width', width)
      .attr('height', height)
      .append('g')
      .attr('transform', 'translate(' + (width / 2) +  ',' + (height / 2) + ')');
    var arc = d3.svg.arc()
      .outerRadius(radius);
    var arcOver = d3.svg.arc()
      .outerRadius(radius + 10);
    var pie = d3.layout.pie()
      .value(function(d) { return d.count; })
      .sort(null);
    var path = svg.selectAll('path')
      .data(pie(data))
      .enter()
      .append('path')
      .attr('d', arc)
      .attr('fill', function(d, i) {
        return color(d.data.label);
      })
      .on('mouseover', function(d, i) {
        d3.select(this).transition()
                       .duration(200)
                       .attr('d', arcOver);
        var ending = 's';
        if(d.data.count == 1) {
          ending = '';
        }
        svg.append('text')
                      .attr('id', i)
                      .attr('class', 'count-nr')
                      .style('text-anchor', 'middle')
                      .attr('y', '180')
                      .text(d.data.label + ' was traced ' + d.data.count + ' time' + ending);
        d3.select('#topTraces tr#n' + i)
                      .attr('class', 'hover');
      })
      .on('mouseout', function(d) {
        d3.select(this).transition()
                       .duration(200)
                       .attr('d', arc);
        var id = d3.select('text').attr('id');
        svg.select('text.count-nr').remove();
        d3.select('#topTraces tr#n' + id)
                      .classed('hover', false);
      });
    var tr = d3.select('#topTraces')
                .selectAll('tr')
                .data(data)
                .enter()
                .append('tr')
                .attr('id', function(d, i) {
                  return 'n' + i;
                })
                .on('mouseover', function(d, i) {
                  var onePath = path[0][i];
                  d3.select(onePath).transition()
                                    .duration(200)
                                    .attr('d', arcOver);
                })
                .on('mouseout', function(d, i) {
                  var onePath = path[0][i];
                  d3.select(onePath).transition()
                                    .duration(200)
                                    .attr('d', arc);
                });

    tr.append('td').html(function(d) {return d.label});
    tr.append('td').html(function(d) {return d.count});
    return path;
  };


  /**
   * Constructs a pie chart with a corresponding table displaying a name - value
   * pair.
   *
   * @param  {Object}  data  Object containing the data that should be displayed.
   * @return {Object}  path  The created pie chart.
   */
  function constructPieChartWTableCountries(data) {
    $('#topCountries').empty();
    var width = 450;
    var height = 400;
    var radius = 130;
    var color = d3.scale.category20c();
    var svg = d3.select('#topCountries')
      .append('svg')
      .attr('width', width)
      .attr('height', height)
      .append('g')
      .attr('transform', 'translate(' + (width / 2) +  ',' + (height / 2) + ')');
    var arc = d3.svg.arc()
      .outerRadius(radius);
    var arcOver = d3.svg.arc()
      .outerRadius(radius + 10);
    var pie = d3.layout.pie()
      .value(function(d) { return d.count; })
      .sort(null);
    var path = svg.selectAll('path')
      .data(pie(data))
      .enter()
      .append('path')
      .attr('d', arc)
      .attr('fill', function(d, i) {
        return color(d.data.countryCode);
      })
      .on('mouseover', function(d, i) {
        d3.select(this).transition()
                       .duration(200)
                       .attr('d', arcOver);
        var ending = 's';
        if(d.data.count == 1) {
          ending = '';
        }
        svg.append('text')
                      .attr('id', i)
                      .attr('class', 'countrycode')
                      .style('text-anchor', 'middle')
                      .attr('y', '180')
                      .text(d.data.country + ' was reached ' + d.data.count + ' time' + ending);
        d3.select('#topCountriesTable tr#n' + i)
                      .attr('class', 'hover');
      })
      .on('mouseout', function(d) {
        d3.select(this).transition()
                       .duration(200)
                       .attr('d', arc);
        var id = d3.select('text').attr('id');
        svg.select('text.countrycode').remove();
        d3.select('#topCountriesTable tr#n' + id)
                      .classed('hover', false);
      });
    var tr = d3.select('#topCountriesTable')
                .selectAll('tr')
                .data(data)
                .enter()
                .append('tr')
                .attr('id', function(d, i) {
                  return 'n' + i;
                })
                .on('mouseover', function(d, i) {
                  var onePath = path[0][i];
                  d3.select(onePath).transition()
                                    .duration(200)
                                    .attr('d', arcOver);
                })
                .on('mouseout', function(d, i) {
                  var onePath = path[0][i];
                  d3.select(onePath).transition()
                                    .duration(200)
                                    .attr('d', arc);
                });

    tr.append('td').html(function(d) {return '<img class="flag flag-'  + d.countryCode.toLowerCase() + '"></img>'});
    tr.append('td').html(function(d) {return d.country});
    tr.append('td').html(function(d) {return d.count});
    return path;
  };


  /**
   * Builds a hop-response-time-line-graph out of the passed data.
   *
   * @param  {Ojbect} data   Object, containing all the data to the hops of a traceroute.
   * @return {Object}        The created graph
   */
  function buildHopTimeGraph(data) {

    //If present, remove old graphs
    $('#hoptime-graph').empty();

    //Initialize Data-Arrays for every response-request-timeline
    var rtt1 = new Array();
    var rtt2 = new Array();
    var rtt3 = new Array();

    var hopIndex = 1;
    var minTime = 10000;
    var maxTime = 0;
    var maxHopNr = 0;

    rtt1.push({time: 0, hop: '', hopNr: 0});
    rtt2.push({time: 0, hop: '', hopNr: 0});
    rtt3.push({time: 0, hop: '', hopNr: 0});

    for(j = 0; j < data.length; j++) {
      if(data[j].hopNumber !== 0) {
        rtt1.push({time: data[j].rtt1, hop: data[j].hostname, hopNr: hopIndex});
        rtt2.push({time: data[j].rtt2, hop: data[j].hostname, hopNr: hopIndex});
        rtt3.push({time: data[j].rtt3, hop: data[j].hostname, hopNr: hopIndex});
        hopIndex++;

        var innerMin = Math.min(data[j].rtt1, data[j].rtt2, data[j].rtt3);
        var innerMax = Math.max(data[j].rtt1, data[j].rtt2, data[j].rtt3)
        minTime = minTime > innerMin? innerMin : minTime;
        maxTime = maxTime < innerMax? innerMax : maxTime;
        maxHopNr++;
      }
    }

    //Show the Placeholder for the Graph
    $('#tm-data-hoptime-graph').css("display", "block");

    //Build the graph
    var vis = d3.select("#hoptime-graph");
    var WIDTH = 800;
    var HEIGHT = 300;
    var MARGINS = {
      top: 20,
      right: 20,
      bottom: 20,
      left: 50
    };
    var xScale = d3.scale.linear().range([MARGINS.left, WIDTH - MARGINS.right]).domain([0, maxHopNr]);
    var yScale = d3.scale.linear().range([HEIGHT - MARGINS.top, MARGINS.bottom]).domain([0, maxTime]);
    var xAxis = d3.svg.axis()
                  .scale(xScale);
    var yAxis = d3.svg.axis()
                  .scale(yScale)
                  .tickFormat(d3.format(""))
                  .orient("left");

    vis.append("text")      // text label for the x axis
      .attr("x", 400)
      .attr("y",  320)
      .style("text-anchor", "middle")
      .style("text-transform", "uppercase")
      .style("font-weight", "bold")
      .text("Hop-Number");
    vis.append("text")
      .attr("transform", "rotate(-90)")
      .attr("x", -160)
      .attr("dy", "1em")
      .style("text-transform", "uppercase")
      .style("font-weight", "bold")
      .style("text-anchor", "middle")
      .text("Time in miliseconds");
    vis.append("svg:g")
      .attr("class", "x axis")
      .attr("transform", "translate(0," + (HEIGHT - MARGINS.bottom) + ")")
      .call(xAxis);
    vis.append("svg:g")
      .attr("class", "y axis")
      .attr("transform", "translate(" + (MARGINS.left) + ",0)")
      .call(yAxis);
    var lineGen = d3.svg.line()
      .x(function(d) {
        return xScale(d.hopNr);
      })
      .y(function(d) {
        return yScale(d.time);
      })
      .interpolate("basis");
    vis.append('svg:path')
      .attr('d', lineGen(rtt1))
      .attr('stroke', 'green')
      .attr('stroke-width', 2)
      .attr('fill', 'none');
    vis.append('svg:path')
      .attr('d', lineGen(rtt2))
      .attr('stroke', 'blue')
      .attr('stroke-width', 2)
      .attr('fill', 'none');
    vis.append('svg:path')
      .attr('d', lineGen(rtt3))
      .attr('stroke', 'orange')
      .attr('stroke-width', 2)
      .attr('fill', 'none');

    return vis;
  }
});
