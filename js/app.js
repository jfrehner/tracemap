$(document).ready(function() {


    var start;
    var markers;
    var bounds;
    var map;
    var coords;
    var path;


    initMap();


    $('#tm-search button').on('click', function(e) {

        e.preventDefault();
        removeMarkers();
        if(path) {
            removePolyline();
        }
        markers.length = 1;
        coords.length = 1;

        var url = $('#tm-search input').val();

        $(this).html('Loading Data…');
        $('#tm-data ul').html('');
        $('#tm-data h2').html('Tracemap-Stats for Destination ' + url.replace('./tracemap/', ''));

        getIpLocation(url, adjustMapBounds);

        $.ajax({
            method: "GET",
            url: "./tracemap/" + url,
            success: function(data) {
                var ip = '';
                data = $.parseJSON(data);

                $('#tm-search button').html('Trace It!');

                for(var key in data) {
                    if(!isNaN(data[key].charAt(0))) {
                        if(data[key].charAt(4) === '*' || data[key].charAt(5) === '*') {
                            $('#tm-data ul').append("<li>Unable to get hop…</li>");
                        } else {
                            var line = data[key];
                            ip = line.substr(line.indexOf('(') + 1, line.indexOf(')') - line.indexOf('(') - 1);
                            getIpLocation(ip);
                            $('#tm-data ul').append("<li>" + key + " " + data[key] + "</li>");
                        }
                    }
                }
                //Caution: this is a hack. Since we need to call adjustMapBounds as a callback
                //we set the last added marker again, so we are able to call adjustMapBounds as a callback.
                //
                //AdjustMapBounds can not be added as a callback to every getIpLocation-call (see Line 37)
                //because this will lead to a stackoverflow. JS is giving a "Too many recursion error".
                getIpLocation(ip, adjustMapBounds, true);
                $('#tm-data').css("display", "block");
            }
        });
    });


    function getIpLocation(url, callback, drawLine) {
        url = url.replace('www.', '');
        $.ajax({
            method: "GET",
            url: "./tracemap/ping/" + url,
            success: function(data) {
                data = $.parseJSON(data);

                var destLong = data.lon;
                var destLat = data.lat;
                var destMarker;

                destMarker = { latitude: destLat, longitude: destLong, title: 'Destination'};

                addMarker(destMarker, callback, drawLine);
            }
        });
    }


    function initMap() {
        window.navigator.geolocation.getCurrentPosition(function(position) {

            var startMarker;

            //Save position as global start-position.
            start = position;

            //Initialize empty markers-Array
            markers = new Array();
            coords = new Array();

            startMarker = { latitude: start.coords.latitude, longitude: start.coords.longitude, title: 'Start'};
            map = new google.maps.Map(document.getElementById("tm-map-initial"), {center: new google.maps.LatLng(start.coords.latitude, start.coords.longitude), zoom: 15});

            addMarker(startMarker);
            map.getZoom();
        });
    }


    function addMarker(newMarker, callback, drawLine) {
        if(!isNaN(newMarker.latitude) && !isNaN(newMarker.longitude)) {
            var pos = new google.maps.LatLng(newMarker.latitude, newMarker.longitude);
            markers.push(new google.maps.Marker({
                position: pos,
                map: map,
                title: newMarker.title
            }));
            coords.push(pos);
            if(typeof(callback) == 'function') callback();
        }
        if(drawLine) {
            var destCoord = coords.splice(1, 1);
            coords.push(destCoord[0]);
            console.log(coords);
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


    function removeMarkers() {
        for(i = 1; i < markers.length; i++) {
            markers[i].setMap(null);
        }
    }


    function removePolyline() {
        path.setMap(null);
    }


    function adjustMapBounds() {
        bounds = new google.maps.LatLngBounds();
        for(i = 0; i < markers.length; i++) {
            bounds.extend(markers[i].getPosition());
        }
        map.fitBounds(bounds);
    }


});
