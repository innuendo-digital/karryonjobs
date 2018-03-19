jQuery(document).ready(function ($) {

    var lat = $('#map-lat').val();
    var lon = $('#map-lon').val();

    var input_address = $('.term-name-wrap #name');

    if ($('.term-name-wrap #tag-name').length) {
        input_address = $('.term-name-wrap #tag-name');
    }

    $('#jm_location_term_map').locationpicker({
        location: {
            latitude: lat,
            longitude: lon,
        },
        radius: 0,
        inputBinding: {
            latitudeInput: $('#map-lat'),
            longitudeInput: $('#map-lon'),
            locationNameInput: input_address
        },
        enableAutocomplete: true,
        enableAutocompleteBlur: true,
    });

});

