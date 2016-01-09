$(document).ready(function() {


    var start;
    var markers;
    var bounds;
    var map;
    var coords;
    console.log('init coords');
    var path;

    var view = $('body').attr('id');

    var locationCache = [];

    if(view == 'view-tracemap') {
      initMap(true);
    } else if(view == 'view-stats') {
      initMap(false, getTopTraces);
      getInfo(); // TODO: Call only when info is needed
    } else if(view == 'view-about') {

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

    // This function is taken from https://davidwalsh.name/javascript-debounce-function
    // and is originately found in underscore.js
    //
    // Returns a function, that, as long as it continues to be invoked, will not
    // be triggered. The function will be called after it stops being called for
    // N milliseconds. If `immediate` is passed, trigger the function on the
    // leading edge, instead of the trailing.
    function debounce(func, wait, immediate) {
    	var timeout;
    	return function() {
    		var context = this, args = arguments;
    		var later = function() {
    			timeout = null;
    			if (!immediate) func.apply(context, args);
    		};
    		var callNow = immediate && !timeout;
    		clearTimeout(timeout);
    		timeout = setTimeout(later, wait);
    		if (callNow) func.apply(context, args);
    	};
    };

    /**
     * Tests on every key-up-event whether the passed URL is a valid one or not.
     * Reaction "lags" behind by 700ms as to not disturb the user with an
     * "invalid URL"-Message if he's still inserting his URL.
     */
    $('#tm-search').on('keyup', debounce(function(e) {
      var url = $('#tm-search input').val();
      if(!isUrlValid(url)) {
        $('#submitBtn').css('background-color', '#aaaaaa');
        $('#submitBtn').html('No valid URL!');
        $('#submitBtn').prop('disabled', true);
      } else {
        $('#submitBtn').css('background-color', '#3E9FFF');
        $('#submitBtn').html('Trace it!');
        $('#submitBtn').prop('disabled', false);
      }
    }, 300));


    /**
     * Gets all the hops to a given URL in real-time.
     * The function calls itself recursively after the passed 'timeout' and if
     * there is still more data to fetch.
     *
     * @param  {array} data     Array containing all the data of the initially pinged url.
     * @param  {int}   timeout  The timeout after which the function should call
     *                          itself again
     */
    function getHops(id) {
      $.ajax({
          method: "GET",
          url: "./api/traceroute/" + id,
          dataType: "json",
          success: function(data) {
            if (data.inProgress) {
              setTimeout(function() {
                getHops(id)
              }, 500);

              $('#tm-data-raw ul').html('');
              $('#tm-data-raw h2').html('Traceroute output');
              for(var key in data.data) {
                if (data.data[key].message) {
                  $('#tm-data-raw ul').append("<li>" + data.data[key].message + "</li>");
                } else {
                  var infobox = generateInfoBoxText(data.data[key].host, data.data[key].ip, []);
                  if (data.data[key].ip) getIpLocationMarker({url: data.data[key].ip, infobox: infobox});
                  $('#tm-data-raw ul').append("<li>" + data.data[key].hopNr + " " + data.data[key].host + " " + data.data[key].ip + " " + data.data[key].hop1 + " " + data.data[key].hop2 + " " + data.data[key].hop3 + "</li>");
                }
              }
              console.log("IN PROGRESS");
            } else {
              $('#tm-data-raw ul').html('');
              $('#tm-data-raw h2').html('Traceroute output');
              for(var key in data.data) {
                console.log(data.data[key]);
                if (data.data[key].message) {
                  $('#tm-data-raw ul').append("<li>" + data.data[key].message + "</li>");
                } else {
                  var infobox = generateInfoBoxText(data.data[key].host, data.data[key].ip, []);
                  if (data.data[key].ip) getIpLocationMarker({url: data.data[key].ip, infobox: infobox});
                  $('#tm-data-raw ul').append("<li>" + data.data[key].hopNr + " " + data.data[key].host + " " + data.data[key].ip + " " + data.data[key].hop1 + " " + data.data[key].hop2 + " " + data.data[key].hop3 + "</li>");
                }
              }
              console.log("FINISHED");
            }
          }
      });
    }


    /**
     * Gets the top traces via a GET-Request to the REST-Api of the app and
     * displays the stats in a nice pie chart together with a table.
     * Furthermore, it displays all the hops on the google map.
     */
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


    $('#tm-search button').on('click', function(e) {

      e.preventDefault();
      removeMarkers();
      removePolyline();
      markers.length = 1;
      coords.length = 1;

      var url = $('#tm-search input').val();

      url = url.replace('https://', '').replace('http://', '');

      /*
      As soon as the user clicks on the 'traceroute'-button display a message
      on the button itself, that we are loading the data. Furthermore, delete
      hop data from a previous request - if there is any - and print the current
      url into the header of the tracemap-stats section under the google map.
       */
      $(this).html('Loading Data…');
      $('#tm-data ul').html('');
      $('#tm-data h2').html('Tracemap-Stats for Destination ' + url);

      getIpLocationMarker({url: url}, adjustMapBounds);

      $.ajax({
          method: "GET",
          url: "./api/" + url,
          dataType: "json",
          success: function(data) {
            console.log(data);
            console.log("./api/traceroute/" + data.id);

            getHops(data.id);
              /*
                var ip = '';
                data = $.parseJSON(data);

                $('#tm-search button').html('Trace It!');

                for(var key in data) {
                    if(!isNaN(data[key].charAt(0))) {
                        if(data[key].charAt(4) === '*' || data[key].charAt(5) === '*') {
                            $('#tm-data ul').append("<li>Unable to get hop…</li>");
                        } else {
                            var line = data[key];
                            var parts = line.trim().split(' ');
                            ip = line.substr(line.indexOf('(') + 1, line.indexOf(')') - line.indexOf('(') - 1);
                            // Using the hostname to get location info (because we want to save hostname and ip to the db)
                            var infobox = generateInfoBoxText(parts[2], ip, []);
                            getIpLocationMarker({url: ip, infobox: infobox});
                            $('#tm-data ul').append("<li>" + key + " " + data[key] + "</li>");
                        }
                    }
                }*/
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


    function getIpLocationMarker(info, callback, drawLine) {
      // TODO: Remove duplicate code
        var url = info.url.replace('www.', '');
        if (locationCache[url]) {
          console.log('CACHED');
          console.log(url + ' is in ' + locationCache[url].country);

          var destLong = locationCache[url].longitude;
          var destLat = locationCache[url].latitude;
          var destMarker;

          destMarker = { latitude: destLat, longitude: destLong, title: 'Destination'};

          return addMarker(destMarker, info.infobox, callback, drawLine);

        } else {
          $.ajax({
              method: "GET",
              url: "./api/ping/" + url,
              success: function(data) {
                  data = $.parseJSON(data);

                  locationCache[url] = data;
                  console.log(url + ' is in ' + data.country);

                  var destLong = data.longitude;
                  var destLat = data.latitude;
                  var destMarker;

                  destMarker = { latitude: destLat, longitude: destLong, title: 'Destination'};

                  return addMarker(destMarker, info.infobox, callback, drawLine);
              }
          });
        }
    }


    /**
     * Initializes the google-map and inserts it into the page.
     *
     * CAUTION (8.1.16): There seems to be quite inconsistent behavior with the
     * navigator.geolocation in Firefox. Sometimes it works quite fine, sometimes
     * Firefox is not doing anything at all. It seems to work fine in Chrome.
     */
    function initMap(showStartMarker, callback) {
      window.navigator.geolocation.getCurrentPosition(function(position) {
        var startMarker;

        //Save position as global start-position.
        start = position;

        //Initialize empty markers-Array
        markers = new Array();
        coords = new Array();
        console.log('created new coords');

        //Set start marker
        if(showStartMarker) {
          startMarker = { latitude: start.coords.latitude, longitude: start.coords.longitude, title: 'Start'};
        }
        map = new google.maps.Map(document.getElementById("tm-map-initial"), {center: new google.maps.LatLng(start.coords.latitude, start.coords.longitude), zoom: 15});
        $('#tm-map-initial').css('background-image', 'none');
        if(showStartMarker) {
          //Add the start marker to the google-map if wished
          addMarker(startMarker, generateInfoBoxText('Our Server', ''));
        }
        map.getZoom();
        if(callback && typeof callback == 'function') callback();
      });
    }

    /**
     * Generates an infobox for a marker on a google map.
     *
     * @param  {string} hostname The hostname of the hop to display in the infoxbox
     * @param  {string} ip       The ip-address of the hop to display
     * @param  {array} hops      The array containing all the hops
     * @return {string}          A string containing all the necessary html
     *                           to display a nice Infobox for a marker a the google map
     */
    function generateInfoBoxText(hostname, ip, hops) {
      var stringHops = '';
      if (hops) {
        for(var i = 0; i < hops.length; i++) {
          stringHops += '<p>'+ (i+1) +': ' + hops[i] + '</p>'
        }
      }
      //TODO: Style the infobox
      var string = '<div id="content">'+
        '<h4 id="firstHeading" class="firstHeading">'+ hostname +'</h4>'+
        '<div id="bodyContent">'+
          '<p>'+ ip +'</p>' +
        stringHops +
        '</div>'+
      '</div>';
      return string;
    }

    function addMarker(newMarker, contentString, callback, drawLine) {
        if(!isNaN(newMarker.latitude) && !isNaN(newMarker.longitude)) {
            var pos = new google.maps.LatLng(newMarker.latitude, newMarker.longitude);
            var myNewMarker = new google.maps.Marker({
                position: pos,
                map: map,
                title: newMarker.title
            });

            if (contentString) {
              var infowindow = new google.maps.InfoWindow({
                content: contentString
              });

              myNewMarker.addListener('click', function() {
                infowindow.open(map, myNewMarker);
              });
            }
            if(!markers) {
              markers = new Array();
            }
            if(!coords) {
              coords = new Array();
            }
            markers.push(myNewMarker);
            coords.push(pos);
            if(typeof(callback) == 'function') callback();
            return pos;
        }
        if(drawLine) {
            var destCoord = coords.splice(1, 1);
            coords.push(destCoord[0]);
            path = new google.maps.Polyline({
                path: coords,
                map: map,
                geodesic: true,
                strokeColor: '#FF0000',
                strokeOpacity: 1.0,
                strokeWeight: 2
            });
        }
    }


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
     * Removes all existing markers from the google map they're on.
     * This is necessary to get a "clean" google map in case of a second
     * tracemap-request after a first one.
     */
    function removeMarkers() {
      if (markers) {
        for(i = 1; i < markers.length; i++) {
            markers[i].setMap(null);
        }
      }
    }


    /**
     * Removes the existing polyline from the google map it's on.
     * This is necessary to get a "clean" google map in case of a second
     * tracemap-request after a first one.
     */
    function removePolyline() {
      if(path) {
        path.setMap(null);
      }
    }


    /**
     * Adjusts the "zoom" of the google map to display all existing markers on
     * the map. This prevents displaying a google map with a zoom to close
     * to see all existing markers. This would be a pitty.
     */
    function adjustMapBounds() {
        bounds = new google.maps.LatLngBounds();
        for(i = 0; i < markers.length; i++) {
            bounds.extend(markers[i].getPosition());
        }
        map.fitBounds(bounds);
    }


});
