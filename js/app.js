$(document).ready(function() {


    var start;
    var markers;
    var bounds;
    var map;
    var coords;

    /**
     * Holds the number of all the hops of the current traceroute so we can test
     * whether or not a hop already has a marker or not.
     */
    var hops;
    var path;

    function initPage() {
      var url = window.location.href;
      url = url.substr(url.lastIndexOf("/") + 1, url.length);
      if(url === '') {
        url = 'tracemap';
      }
      renderPage(url);
    }

    initPage();

    var locationCache = [];

    $('nav .view-tracemap').on('click', function(e) {
      e.preventDefault();
      updateUrl('tracemap');
      renderPage('tracemap');
    });

    $('nav .view-statistics').on('click', function(e) {
      e.preventDefault();
      updateUrl('statistics');
      renderPage('statistics');
    });

    $('nav .view-about').on('click', function(e) {
      e.preventDefault();
      updateUrl('about');
      renderPage('about');
    });


    /**
     * Helper function to transform the first character of the passed String
     * to Uppercase.
     *
     * @param  {string} string The String to transform the first character
     * @return {string}        The transformed String
     */
    function fstUp(string) {
      return string.charAt(0).toUpperCase() + string.slice(1);
    }

    /**
     * Updates the URL in the browser so it matches the site the user is on.
     *
     * @param  {string} newSubpage  The subpage the user navigated to and that
     *                              should be displayed in the browser's address bar.
     * @return {string} url         The subpage in the form of http://.. the user navigated to.
     */
    function updateUrl(newSubpage) {
      var url = window.location.href;
      url = url.substr(0, url.lastIndexOf("/")) + "/" + newSubpage;
      window.history.pushState("", "", url);
      return url;
    }


    /**
     * Renders the passed page-template and updates the site-title and header.
     *
     * @param  {string} template The name of the template that should be renedered.
     * @return {string}          The template that just was rendered.
     */
    function renderPage(template) {

      $('nav li').removeClass('selected');
      $('nav .view-' + template).addClass('selected');
      $('main').remove();
      $(document).prop('title', fstUp(template));
      $('header h1').empty();
      $('header h1').append(fstUp(template));
      $('div.load-template').load("./templates/" + template + ".html", function() {
        var view = $('main').attr('id');
        if(view == 'view-tracemap') {
          drawMap(true);
        } else if(view == 'view-statistics') {
          initMap(false, getTopTraces);
          drawMap(); // TODO: Call only when info is needed
        } else if(view == 'view-about') {
        }
        applyListeners();
        return template;
      });
    }


    /**
     * Initializes the google-map and inserts it into the page.
     *
     * CAUTION (8.1.16): There seems to be quite inconsistent behavior with the
     * navigator.geolocation in Firefox. Sometimes it works quite fine, sometimes
     * Firefox is not doing anything at all. It seems to work fine in Chrome.
     */
    // function initMap(showStartMarker, callback) {
    //   window.navigator.geolocation.getCurrentPosition(function(position) {
    //     var startMarker;
    //
    //     //Save position as global start-position.
    //     start = position;
    //
    //     //Initialize empty Arrays for the new traceroute-data
    //     markers = new Array();
    //     coords = new Array();
    //     hops = new Array();
    //
    //     //Set start marker
    //     if(showStartMarker) {
    //       startMarker = { latitude: start.coords.latitude, longitude: start.coords.longitude, title: 'Start'};
    //     }
    //     map = new google.maps.Map(document.getElementById("tm-map-initial"), {center: new google.maps.LatLng(start.coords.latitude, start.coords.longitude), zoom: 15});
    //     $('#tm-map-initial').css('background-image', 'none');
    //     if(showStartMarker) {
    //       //Add the start marker to the google-map if wished
    //       drawMarker(startMarker, generateInfoBoxText('Our Server', ''));
    //     }
    //     map.getZoom();
    //     if(callback && typeof callback == 'function') callback();
    //   });
    // }

    /**
     * Initializes a new google map.
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
          startMarker = { latitude: start.coords.latitude, longitude: start.coords.longitude, title : 'Start' };
          drawMarker(startMarker, generateInfoBoxText('Our Server', ''), metaData, adjustMapBounds, map);
        }
        map.getZoom();
        if(callback && typeof callback == 'function') callback(data, map, metaData);
      });
    }


    /**
     * Gets all the basic information (stats) via a GET-Request to the
     * REST-Api of the app.
     *
     * @return {json} The json containing all the basic info, that is:
     *                - Number of traceroutes in the DB
     *                - Number of hops in the DB
     *                - The average time to get to a hop
     */
    function getInfo() {
      $.ajax({
        method: "GET",
        url: "./api/info/basic",
        success: function(data) {
          data = $.parseJSON(data);
          $('.numberOfTraces').html(data.numTraces);
          $('.numberOfHops').html(data.numHops);
          $('.averageHopTime').html(data.hopTime);
        }
      });
    }


    /**
     * Gets the top traces via a GET-Request to the REST-Api of the app and
     * displays the stats in a nice pie chart together with a table.
     * Furthermore, it displays all the hops on the google map.
     */
    //TODO Refactor
    function getTopTraces() {
      $.ajax({
        method: "GET",
        url: "./api/info/topTraces",
        success: function(data) {
          data = $.parseJSON(data);
          var dataSet = new Array();
          for(var i = 0; i < data.length; i++) {
            dataSet.push({count: data[i].traceCount, label: data[i].url});
            var url = data[i].url;
            getIpLocationMarker({url: url, infobox: generateInfoBoxText(data[i].url, '')}, adjustMapBounds);
          }
          constructPieChartWTable(dataSet);
          tryToDraw(200);
        }
      });
    }

    function tryToDraw(timeout) {
      setTimeout(function() {
          if(coords.length > 1) {
          drawTopTens(coords);
        } else {
          tryToDraw(200);
        }
      }, timeout);
    }


    /**
     * Tests whether the passed URL is a valid one or not.
     *
     * @param  {string}  url The URL to validate
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
     * Tests on every key-up-event whether the passed URL is a valid one or not.
     * Reaction "lags" behind by 700ms as to not disturb the user with an
     * "invalid URL"-Message if he's still inserting his URL.
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


    /**
     * Gets all the hops to a given URL in real-time.
     * The function calls itself recursively after the passed 'timeout' and if
     * there is still more data to fetch.
     *
     * @param  {array} data     Array containing all the data of the initially pinged url.
     * @param  {int}   timeout  The timeout after which the function should call
     *                          itself again
     */
    function startTraceroute(data, map, metaData) {
      $.ajax({
          method: "GET",
          url: "./api/traceroute/" + data.id,
          dataType: "json",
          success: function(data) {
            if (data.inProgress) {
              setTimeout(function() {
                startTraceroute(data, map, metaData);
              }, 500);
              processTracerouteData(data, map, metaData);
            } else {
              processTracerouteData(data, map, metaData);
              $('#submitBtn').css('background-color', '#3E9FFF');
              $('#submitBtn').html('Trace it!');
              $('#submitBtn').prop('disabled', false);
              $('#tm-search input').prop('disabled', false);
              $('#tm-search input').css('color', '#000');
            }
          }
      });
    }

    /**
     * Gets all Hops from the passed data.
     *
     * @return {[type]} [description]
     */
    function processTracerouteData(response, map, metaData) {
      for(var key in response.data) {

        //Save data for one hop in helper-variable
        var hopData = response.data[key];

        if (hopData.message) {
          $('#tm-data p').text(hopData.message);

        } else {
          console.log(hopData);
          console.log(hopData.hopNumber);
          console.log(metaData.hops);
          //Test if hopNumber is already present. If not, add and draw marker
          if(metaData.hops.indexOf(hopData.hopNumber) === -1) {
            metaData.hops.push(hopData.hopNumber);

            drawMarker(hopData, generateInfoBoxText(hopData.hostname, hopData.ip, hopData.hopNumber), metaData, adjustMapBounds, map);
            generateTableRow(hopData);
          }

          //Test if last row has any * If so, then update the row
          //but do not get any location data, do not draw any markers etc
          if(key === response.length - 1 && (hopData.rtt1 === '*' || hopData.rtt2 === '*' || hopData.rtt3 === '*')) {
            generateTableRow(hopData);
          }
        }
      }
    }


    /**
     * Creates a new marker on the google map.
     *
     * @param  {[type]}   hopData  [description]
     * @param  {Function} callback [description]
     * @return {[type]}            [description]
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
        if(objNr > 1) {
          drawLine(map, metaData, objNr - 1);
        }
        if(typeof(callback) == 'function') {
          callback(map, metaData);
        }

      }
    }

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
    }


    /**
     * Creates a table row for a hop together with a country flag, if the country-code is present.
     *
     * @param  {Object} response The hop data
     * @return {[type]}          [description]
     */
    function generateTableRow(hopData) {
      var countryFlag = '';

      if(hopData.countryCode) {
        var countryFlag = '<img src="./css/blank.gif" class="flag flag-'  + hopData.countryCode + '"></img>';
      }
      $('#traceroute-table tbody').append("<tr><td>" + countryFlag + "</td><td>" + hopData.hopNumber + "</td><td>"
      + hopData.hostname + "</td><td>" + hopData.ip + "</td><td>"
      + hopData.rtt1 + "</td><td>" + hopData.rtt2 + "</td><td>" + hopData.rtt3 + "</td>");
    }


    // function generateHopTable(data) {
    //   for(var key in data.data) {
    //     var countryCode = '';
    //     if (data.data[key].message) {
    //       $('#tm-data p').text(data.data[key].message);
    //     } else {
    //       if(hops.indexOf(data.data[key].hopNr) === -1) {
    //         hops.push(data.data[key].hopNr);
    //         var infobox = generateInfoBoxText(data.data[key].host, data.data[key].ip, data.data[key].hopNr);
    //         if (data.data[key].ip) getIpLocationMarker({url: data.data[key].ip, infobox: infobox}, adjustMapBounds);
    //         if(locationCache[data.data[key].ip] && locationCache[data.data[key].ip].country_code) {
    //           countryCode = locationCache[data.data[key].ip].country_code.toLowerCase();
    //           $('#traceroute-table tbody').append("<tr><td><img src='./css/blank.gif' class=\"flag flag-" + countryCode + "\"></img></td><td>" + data.data[key].hopNr + "</td><td>" + data.data[key].host + "</td><td>" + data.data[key].ip + "</td><td>" + data.data[key].hop1 + "</td><td>" + data.data[key].hop2 + "</td><td>" + data.data[key].hop3 + "</td>");
    //         } else {
    //           countryCode = '';
    //           $('#traceroute-table tbody').append("<tr><td></td><td>" + data.data[key].hopNr + "</td><td>" + data.data[key].host + "</td><td>" + data.data[key].ip + "</td><td>" + data.data[key].hop1 + "</td><td>" + data.data[key].hop2 + "</td><td>" + data.data[key].hop3 + "</td>");
    //         }
    //       }
    //     }
    //   }
    // }

    function applyListeners() {
      $('#tm-search button').on('click', function(e) {
        e.preventDefault();
        // removeMarkers();
        // removePolyline();
        //Delete all markers and coords except the ones for the start (our sever)
        // markers.length = 1;
        // coords.length = 1;

        $('#tm-data-raw ul').html('');
        $('#traceroute-table tbody').html('');
        $('#tm-data-raw h2').html('Traceroute output');

        var url = $('#tm-search input').val();

        url = url.replace('https://', '').replace('http://', '');

        /*
        As soon as the user clicks on the 'traceroute'-button display a message
        on the button itself, that we are loading the data. Furthermore, delete
        hop data from a previous request - if there is any - and print the current
        url into the header of the tracemap-stats section under the google map.
         */
        $(this).html('Loading Data…');
        $(this).css('background-color', '#aaaaaa');
        $(this).prop('disabled', true);
        $('#tm-search input').prop('disabled', true);
        $('#tm-search input').css('color', '#aaa');
        $('#tm-data ul').html('');
        $('#tm-data h2').html('Tracemap-Stats for Destination ' + url);

        //Get the destination-marker via a ping and adjust the map-bounds
        //so start and end are visible.
        //getIpLocationMarker({url: url, infobox: generateInfoBoxText('Destination-Server', '')}, adjustMapBounds);

        $.ajax({
            method: "GET",
            url: "./api/" + url,
            dataType: "json",
            success: function(data) {
              drawMap(true, data, startTraceroute);
              //startTraceroute(data.id);
              //Caution: this is a hack. Since we need to call adjustMapBounds as a callback
              //we set the last added marker again, so we are able to call adjustMapBounds as a callback.
              //
              //AdjustMapBounds can not be added as a callback to every getIpLocationMarker-call (see Line 37)
              //because this will lead to a stackoverflow. JS is giving a "Too many recursion error".
              //getIpLocationMarker({url: ip}, adjustMapBounds, true);
              $('#tm-data').css("display", "block");
            }
        });
      });
    };


    // function getIpLocationMarker(info, callback, drawLine) {
    //   // TODO: Remove duplicate code
    //     var url = info.url.replace('www.', '');
    //     if (locationCache[url]) {
    //
    //       var destLong = locationCache[url].longitude;
    //       var destLat = locationCache[url].latitude;
    //       var destMarker;
    //
    //       destMarker = { latitude: destLat, longitude: destLong, title: 'Destination'};
    //       return addMarker(destMarker, info.infobox, callback, drawLine);
    //     } else {
    //       $.ajax({
    //           method: "GET",
    //           url: "./api/ping/" + url,
    //           success: function(data) {
    //             data = $.parseJSON(data);
    //
    //             locationCache[url] = data;
    //
    //             var destLong = data.longitude;
    //             var destLat = data.latitude;
    //             var destMarker;
    //
    //             destMarker = { latitude: destLat, longitude: destLong, title: 'Destination'};
    //             return addMarker(destMarker, info.infobox, callback, drawLine);
    //           }
    //       });
    //     }
    // }


    /**
     * Generates an infobox for a marker on a google map.
     *
     * @param  {string} hostname The hostname of the hop to display in the infoxbox
     * @param  {string} ip       The ip-address of the hop to display
     * @param  {string} hopNr    The HopNr of the hop
     * @return {string}          A string containing all the necessary html
     *                           to display a nice Infobox for a marker a the google map
     */
    function generateInfoBoxText(hostname, ip, hopNr) {
      var hopStr = '';
      var ipStr = '';
      if(hopNr) {
        hopStr = 'Hop-Nr: ' + hopNr + ' &#64; ';
      }
      if(ip) {
        ipStr = '<div id="bodyContent">'+
          '<p>The IP-Address is: '+ ip +'</p>' +
        '</div>';
      }
      var string = '<div id="content">'+
        '<h4 id="firstHeading" class="firstHeading">' + hopStr + hostname + '</h4>' + ipStr +
      '</div>';
      return string;
    }

    // function addMarker(newMarker, contentString, callback, drawLine) {
    //     if(!isNaN(newMarker.latitude) && !isNaN(newMarker.longitude)) {
    //         var pos = new google.maps.LatLng(newMarker.latitude, newMarker.longitude);
    //         var myNewMarker = new google.maps.Marker({
    //             position: pos,
    //             map: map,
    //             title: newMarker.title
    //         });
    //
    //         if (contentString) {
    //           var infowindow = new google.maps.InfoWindow({
    //             content: contentString
    //           });
    //
    //           myNewMarker.addListener('click', function() {
    //             infowindow.open(map, myNewMarker);
    //           });
    //         }
    //         markers.push(myNewMarker);
    //         coords.push(pos);
    //         if(typeof(callback) == 'function') {
    //           callback();
    //         }
    //         return pos;
    //     }
    //     if(drawLine) {
    //         var destCoord = coords.splice(1, 1);
    //         coords.push(destCoord[0]);
    //         path = new google.maps.Polyline({
    //             path: coords,
    //             map: map,
    //             geodesic: true,
    //             strokeColor: '#FF0000',
    //             strokeOpacity: 1.0,
    //             strokeWeight: 2
    //         });
    //     }
    // }


    function drawTopTens(coords) {
      var points = new Array();
      points[0] = {lat: start.coords.latitude, lng: start.coords.longitude};
      for(i = 0; i < coords.length; i++) {
        points.push(coords[i]);
        path = new google.maps.Polyline({
          path: points,
          map: map,
          geodesic: true,
          strokeColor: '#FF0000',
          strokeOpacity: 1.0,
          strokeWeight: 2
        });
        points.pop();
      }
    }


    /**
     * Adjusts the "zoom" of the google map to display all existing markers on
     * the map. This prevents displaying a google map with a zoom to close
     * to see all existing markers. This would be a pitty.
     */
    function adjustMapBounds(map, metaData) {
        metaData.bounds = new google.maps.LatLngBounds();
        for(i = 0; i < metaData.markers.length; i++) {
          metaData.bounds.extend(metaData.markers[i].getPosition());
        }
        map.fitBounds(metaData.bounds);
    }

    function constructPieChartWTable(data) {
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
                        .text(d.data.label + ' was called ' + d.data.count + ' time' + ending);
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
    };
});
