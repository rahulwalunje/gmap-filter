var weu = jQuery.noConflict();
weu(document).ready(function(){
      weu("input#address").change(function(){
      var address = weu(this).val();
      var data = {
          'action': 'gmap_calculate_cordi',    
          'address': address
      };
      weu.post(ajaxurl, data, function(response) {
            // Set long and lat according to address
            var res = response.split(",");
            weu('#longi').html(res[0]);
            weu('#latit').html(res[1]);
      });
  });
});