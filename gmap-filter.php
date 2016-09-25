<?php
/**
 * Plugin Name: Gmap Filter
 * Plugin URI: http://www.wpshopee.com/gmap-filter/
 * Description: Gmap Filter which plots entered address on Gmap and filter according to groups.
 * Version: 1.0.0
 * Author: WPshopee
 * Author URI: http://www.wpshopee.com
 * Text Domain: wps-gmap-filter
 * License: GPL2
 */
/*  Gmap Filter
	Copyright 2016-2017  WPshopee (email : wpshopee@gmail.com)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
/**
 * Make sure we don't expose any info if called directly
 */
if ( ! defined( 'ABSPATH' ) ) exit; 
if ( !function_exists( 'add_action' )) {
	echo 'I am just a plugin, not much I can do when called directly.';
	exit;
}
require_once('gmap-filter-ajax.php');
/**
 * enqueue admin script
 */
function wps_gmf_enqueue_script() {
	wp_enqueue_script( 'gmap-viewer-admin-script', plugins_url('js/gmap-viewer-admin.js', __FILE__ ), array(), '1.0.0', false );
}
add_action( 'admin_enqueue_scripts', 'wps_gmf_enqueue_script' );
add_action( 'init', 'wps_gmf_address_loc_init' );
/**
 * Register a address post type.
 * @link http://codex.wordpress.org/Function_Reference/register_post_type
 */
function wps_gmf_address_loc_init() {
	$labels = array(
		'name'               => _x( 'Address', 'post type general name', 'wps-gmap-filter' ),
		'singular_name'      => _x( 'Address', 'post type singular name', 'wps-gmap-filter' ),
		'menu_name'          => _x( 'GMap Filter', 'admin menu', 'wps-gmap-filter' ),
		'name_admin_bar'     => _x( 'Address', 'add new on admin bar', 'wps-gmap-filter' ),
		'add_new'            => _x( 'Add New', 'address', 'wps-gmap-filter' ),
		'add_new_item'       => __( 'Add New Address', 'wps-gmap-filter' ),
		'new_item'           => __( 'New Address', 'wps-gmap-filter' ),
		'edit_item'          => __( 'Edit Address', 'wps-gmap-filter' ),
		'view_item'          => __( 'View Address', 'wps-gmap-filter' ),
		'all_items'          => __( 'All Address', 'wps-gmap-filter' ),
		'search_items'       => __( 'Search Address', 'wps-gmap-filter' ),
		'parent_item_colon'  => __( 'Parent Address:', 'wps-gmap-filter' ),
		'not_found'          => __( 'No addresss found.', 'wps-gmap-filter' ),
		'not_found_in_trash' => __( 'No addresss found in Trash.', 'wps-gmap-filter' )
	);
	$args = array(
		'labels'             => $labels,
        'description'        => __( 'Description.', 'wps-gmap-filter' ),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'address' ),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' )
	);
	register_post_type( 'address', $args );
}
/**
 * Register meta box(es).
 */
function wps_gmf_register_meta_boxes() {
    add_meta_box( 'wps-gmf-meta-box', __( 'Address Details:', 'wps-gmap-filter' ), 'wps_gmf_post_box', 'address' );
}
add_action( 'add_meta_boxes', 'wps_gmf_register_meta_boxes' );
/**
 * Meta box display callback.
 * @param WP_Post $post Current post object.
 */
function wps_gmf_post_box( $post ) {
	$address= get_post_meta($post->ID,'_wps_gmapview_address',true);
	$latit = get_post_meta($post->ID,'_wps_gmapview_latit',true);
	$longi = get_post_meta($post->ID,'_wps_gmapview_longit',true);
	$address = str_replace("+", " ", $address);
	?>
		<label>Enter Address : </label><input name='gmapv_address' id='address' value="<?php echo $address; ?>" style="display: block;margin: 12px 0 0;height: 3em;width: 100%;">
		<p><span>Latitude : </span><span id='latit'> <?php echo $latit; ?> </span></p>
		<p><span>Longitude : </span><span id='longi'> <?php echo $longi; ?> </span></p>
  	<?php
}
/**
 * Save meta box content.
 *
 * @param int $post_id Post ID
 */
function wps_gmf_save_meta_box( $post_id ) {
$address = isset($_POST['gmapv_address'])?$_POST['gmapv_address']:'';
$address = sanitize_text_field($address);
	if(!empty($address)) {
		$address = str_replace(" ", "+", $address);
		$json = file_get_contents("http://maps.google.com/maps/api/geocode/json?address=$address&sensor=false");
		$json = json_decode($json);
		$lat = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'};
		$long = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'};
		update_post_meta($post_id,'_wps_gmapview_address', $address);
		update_post_meta($post_id,'_wps_gmapview_latit', $lat);
		update_post_meta($post_id,'_wps_gmapview_longit', $long);
	}	
}
add_action( 'save_post', 'wps_gmf_save_meta_box' );
add_action( 'edit_post', 'wps_gmf_save_meta_box' );
add_action( 'init', 'wps_gmf_group_taxonomy', 0 );
// create taxonomy for address
function wps_gmf_group_taxonomy() {
	$labels = array(
		'name'              => _x( 'Groups', 'taxonomy general name', 'wps-gmap-filter' ),
		'search_items'      => __( 'Search Groups', 'wps-gmap-filter' ),
		'all_items'         => __( 'All Groups', 'wps-gmap-filter' ),
		'parent_item'       => __( 'Parent Group', 'wps-gmap-filter' ),
		'parent_item_colon' => __( 'Parent Group:', 'wps-gmap-filter' ),
		'edit_item'         => __( 'Edit Group', 'wps-gmap-filter' ),
		'update_item'       => __( 'Update Group', 'wps-gmap-filter' ),
		'add_new_item'      => __( 'Add New Group', 'wps-gmap-filter' ),
		'new_item_name'     => __( 'New Group Name', 'wps-gmap-filter' ),
		'menu_name'         => __( 'Group', 'wps-gmap-filter' ),
	);
	$args = array(
		'hierarchical'      => true,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'group' ),
	);
	register_taxonomy( 'group', array( 'address' ), $args );
}
function wps_gmf_viewer_shortcode($atts) {
	$scodes_atts = shortcode_atts( array(
		'filter' => 'on'
	), $atts );
	$allgroups = get_terms('group');
	$group_tax = '<option value="all_groups"> All Groups </option>';
	for( $i=0;$i<count($allgroups);$i++) {
		$group_tax = $group_tax.'<option value="'.$allgroups[$i]->slug.'">'.$allgroups[$i]->name.'</option>';
	}
	$html='<div style="width:800px;height:800px;" id="wps_address_container"></div>';
	if($scodes_atts['filter'] != 'off') {
		$html.= "<select id='wps_addrres_cats' style='display: block;margin: 12px 0 0; height: 3em; width: 250px;'
>".$group_tax."</select>";	
	}
	$weu_ajaxurl = admin_url( 'admin-ajax.php' );
	wp_enqueue_script( 'gmap-viewer-front-script', plugins_url('js/gmap-viewer-front.js', __FILE__ ), array(), '1.0.0', false );
	$weu_select_params = array(
		'weu_ajax_url'	=> $weu_ajaxurl
	);
	$gmap_key = get_option('wps_gmap_filter_key');
	wp_localize_script( 'gmap-viewer-front-script', 'weu_widget_notices', $weu_select_params );
	$html.='<script src="https://maps.googleapis.com/maps/api/js?key='.$gmap_key.'" async defer></script>';
	return $html;
}
add_shortcode('gmapviewer', 'wps_gmf_viewer_shortcode');
# Register submenu
add_action( 'admin_menu', 'wps_gmf_register_submenu' );
function wps_gmf_register_submenu() {
    add_submenu_page( 'edit.php?post_type=address', 'Gmap Filter Options', 'API Key', 'manage_options', 'wps-gmap-filter-manage', 'wps_gmf_admin_page');
    remove_menu_page('gmap_filter_admin_page');
}
function wps_gmf_admin_page() {
	$gkey = isset($_POST['api_key'])?$_POST['api_key']:'';
	$gkey = sanitize_text_field($gkey);
	if(!empty($gkey)) {
		update_option('wps_gmap_filter_key',$gkey);	
	}
	$gmap_key = get_option('wps_gmap_filter_key');
?>
<div class="wrap">
	<form action="#" method="post">
	<h1>Gmap Filter</h1>
	<p>Please enter API key to get started.</p>
	<table class="form-table plgin-all-info">
	<tbody>
		<tr>
			<td style="width: 10%;"><strong>API Key</strong></td>
			<td><input type="text" name="api_key" style="width: 50%;" value="<?php echo $gmap_key; ?>"></td>
		</tr>
		<tr>
			<td style=""></td>
			<td scope="row"><input class="button button-primary" type="submit" value="Save Changes"></td>
		</tr>
		<tr>
			<td style=""><strong>shortcodes</strong></td>
			<td style=""><strong><pre>[gmapviewer filter="on"]</pre></strong><span> Display maps with category filter</span></td>
		</tr>
		<tr>
			<td style=""></td>
			<td style=""><strong><pre>[gmapviewer filter="off"]</pre></strong><span> Display maps without category filter</span></td>
		</tr>
	</tbody></table>
</form>
</div>
<?php
}
?>