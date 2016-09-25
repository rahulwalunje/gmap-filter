<?php
/**
 * Make sure we don't expose any info if called directly
 */
if ( ! defined( 'ABSPATH' ) ) exit;
add_action( 'wp_ajax_gmap_calculate_cordi', 'wps_gmf_calculate_cordi' );
function wps_gmf_calculate_cordi() {
	$address = isset($_POST['address'])?$_POST['address']:'';
    $address = str_replace(" ", "+", $address);
    $json = file_get_contents("http://maps.google.com/maps/api/geocode/json?address=$address&sensor=false");
    $json = json_decode($json);
    echo $lat = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'};
    echo ",";
    echo $long = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'};
	wp_die();
}
add_action( 'wp_ajax_nopriv_gmap_taxon_calc', 'wps_gmf_action_filter_taxo' );
add_action( 'wp_ajax_gmap_taxon_calc', 'wps_gmf_action_filter_taxo' );
function wps_gmf_action_filter_taxo() {
	global $post;
    $select_taxo = isset($_POST['taxonomy'])?$_POST['taxonomy']:'';
    $select_taxo = sanitize_text_field($select_taxo);
    if($select_taxo=='all_groups') {
        $args = array(
            'post_type' => 'address',
            'post_status' => 'publish'
        );
    }
    else {
        $args = array(
            'post_type' => 'address',
            'post_status' => 'publish',
            'tax_query' => array(
                array(
                    'taxonomy' => 'group',
                    'field'    => 'slug',
                    'terms'    => $select_taxo,
                ),
            ),
        );    
    }
    // The Query
    $grp_latlng = array();
    $grp_content = array();
    query_posts( $args );
    while ( have_posts() ) : the_post();
        $select_id = get_the_ID();
        $select_title = get_the_title();
        $latit = get_post_meta($select_id,'_wps_gmapview_latit',true);
        $longi = get_post_meta($select_id,'_wps_gmapview_longit',true);
        $latlng = array('lat' => (float)$latit ,'lng'=> (float)$longi, 'title'=> $select_title );
        array_push($grp_latlng,$latlng);
    endwhile;
    // Reset Query
    wp_reset_query();
    echo json_encode($grp_latlng);
    wp_die();
}
?>