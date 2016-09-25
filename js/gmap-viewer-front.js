var weu = jQuery.noConflict();
weu(document).ready(function(){
  var latlong;
  var map;
  var ks;
  initiateMap();
  weu("select#wps_addrres_cats").change(function(SelectTaxo){
      var SelectTaxo = this.value;
      var data = {
        'action': 'gmap_taxon_calc',    
        'taxonomy': SelectTaxo
      };
      weu.post(weu_widget_notices.weu_ajax_url, data, function(response) {
            var respon = JSON.parse(response);
            var map = new google.maps.Map(document.getElementById('wps_address_container'), {
                    zoom: 5,
                    center: respon[0]
            }); 
            for(var i=0; i<respon.length;i++){
                var latlong = respon[i];
                var title = respon[i].title;
                addMarker(latlong,map,title);
            }
      });
  });
// Adds a marker to the map.
function addMarker(location, map, content) {
    var infowindow = new google.maps.InfoWindow({
        content: content
    });
    var marker = new google.maps.Marker({
        position: location,
        map: map
    });
    // add title window
    marker.addListener('click', function() {
      infowindow.open(map, marker);
    });
    return;
}
// Initiate maps by marking all availble address
function initiateMap() {
    var data = {
      'action': 'gmap_taxon_calc',    
      'taxonomy': 'all_groups'
    };
    weu.post(weu_widget_notices.weu_ajax_url, data, function(response) {            
          var respon = JSON.parse(response);
          var map = new google.maps.Map(document.getElementById('wps_address_container'), {
                  zoom: 3,
                  center: respon[0]
          }); 
          for(var i=0; i<respon.length;i++){
              var latlong = respon[i];
              var title = respon[i].title;
              addMarker(latlong,map,title);
          }
    });
}
});