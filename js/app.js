$(document).ready(function() {

    $('#tm-search button').on('click', function(e) {
        e.preventDefault();
        var url = "./tracemap/";
        url += $('#tm-search input').val();

        $(this).html('Loading Data…');
        $.get(url, function(data) {
            $('#tm-search button').html('Trace It!');
            data = $.parseJSON(data);
            $('#tm-data').html(data);
        });
    });

    window.navigator.geolocation.getCurrentPosition(loadMapWithLocation);

    function loadMapWithLocation(position) {

        var here = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
        var mapOptions = {center: here, zoom: 15};

        var map = new google.maps.Map(document.getElementById("tm-map-initial"), mapOptions);
        map.getZoom();
    };

});
