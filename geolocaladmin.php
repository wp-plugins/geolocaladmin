<?php

/**
 * Plugin Name: GeolocalAdmin
 * Plugin URI: http://mateo-design.com  
 * Description: a plugin to geolocalizate any backend user in order to show this info in post, displaying a googlemap of his last connect place...
 * Version: 0.1
 * Author: mateo-design
 * Author URI: http://mateo-design.com
 *
 * A plugin to geolocalizate any backend user in order to use this info in widget, displaying a googlemap of his last connect place...
 *
 * @copyright 2012
 * @version 0.1
 * @author mateo-design
 * @link http://mateo-design.com
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @package GeolocalAdmin
 */


/* Variables & Constants. */
define( 'GEOLOCALADMIN_URI', trailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'GEOLOCALADMIN_VERSION',0.1 );
define( 'GEOLOCALADMIN_ID_BASE', 'geolocaladmin_widget');
require(dirname(__FILE__).'/geolocaladmin_widget.php');

/* Launch the plugin = Create instance; */
$geolocalAdmin = new GeolocalAdmin();

////////// TODO : clean database on uninstall ////////////
// /* Register plugin activation hook. */
// register_activation_hook( __FILE__, 'geolocaladmin_activation' );	
// /* Register plugin deactivation hook. */
// register_deactivation_hook( __FILE__, 'geolocaladmin_deactivation' );
// /* Register plugin activation hook. */
// register_uninstall_hook( __FILE__, 'geolocaladmin_uninstall' );

class GeolocalAdmin{

	private $currentAdminPage;

	/**
	 * Setup function.
	 *
	 */
	function __construct(){		
		
		/* Launch the widget. */
		add_action( 'widgets_init', array($this,'widget_init') );
		/* Add the Meta Viewport */
		add_action( 'wp_head', array($this,'add_meta_viewport') );
		/* Enqueue the stylesheet. */
		add_action( 'wp_enqueue_scripts', array($this, 'add_stylesheet') );
		add_action( 'admin_enqueue_scripts', array($this,'add_stylesheet') );
		/* Enqueue the JavaScript. */
		add_action( 'wp_enqueue_scripts', array($this,'enqueue_scripts') );
		add_action( 'admin_enqueue_scripts', array($this,'enqueue_scripts') );	
		/* Print the JavaScript. */
		add_action('admin_footer-index.php', array($this,'print_scripts') );
		add_action( 'admin_footer-widgets.php', array($this,'print_scripts') );
		add_action( 'admin_footer-widgets.php', array($this,'ajax_save_script') );
		add_action( 'wp_footer', array($this,'print_scripts_front_side') );
		/* Ajax */
		add_action('wp_ajax_geolocaladmin_ajax_save', array($this,'ajax_save'));
    	add_action('wp_ajax_nopriv_geolocaladmin_ajax_save', array($this,'ajax_save'));
    	
	}

	/**
	 * Function Usefull to check on page loading if one active widget have been blocked on current location
	 *
	 * @since 0.1
	 */
	static function actives_widgets(){

		$sidebars_widgets = wp_get_sidebars_widgets(); 
		$actives_widgets = array();

		foreach ($sidebars_widgets as $sidebar) {
			if($sidebar !=="wp_inactive_widgets"){
				foreach ($sidebar as $active_widget) {
					$actives_widgets[] = $active_widget;
				}
			}
		}
		return $actives_widgets;
		
	}
	/**
	 * Function Usefull to check on page loading if one active widget have been blocked on current location
	 *
	 * @since 0.1
	 */
	static function blocker_verif(){
		
		$current_user_id = get_current_user_id();
		$current_actives_widgets = self::actives_widgets();
		$blocked_widgets	= 0;
		$test_result=false;

		
		if(get_option("widget_".GEOLOCALADMIN_ID_BASE)){
			foreach (get_option("widget_".GEOLOCALADMIN_ID_BASE) as $key => $widget) {	
				$index =  GEOLOCALADMIN_ID_BASE . '-' . $key;
				if(in_array($index, $current_actives_widgets)){
					if($widget["blocked"]=='yes' || $current_user_id!= (int)$widget["geolocaladmin_user"])
						$blocked_widgets ++;
				}
			}
			if($blocked_widgets>=sizeof($current_actives_widgets)){
				$test_result = true;
			}
		}else{
			$test_result = true;
		}

		return $test_result;

	}

	/**
	 * Enqueue the stylesheet. It's the same for both front-end and back-end
	 *
	 * @since 0.1
	 */
	public function add_stylesheet($hook) {

		if(!is_admin() || 'widgets.php'==$hook) 
		wp_enqueue_style( 'geolocaladmin_admin', GEOLOCALADMIN_URI . 'geolocaladmin.css', false, 0.1, 'all' );

	}

	/**
	* Add viewport
	*
	* @since 0.1
	*/
	public function add_meta_viewport() {

		echo '<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />';

	}
	/**
	* Geolocalisation jQuery plugin by m@teo
	*
	* @since 0.1
	*/

	public function enqueue_scripts($hook){

		if(!is_admin()){
			wp_deregister_script( 'jquery' );
		    wp_register_script( 'jquery', 'http://code.jquery.com/jquery-1.8.2.min.js');
		    wp_enqueue_script( 'jquery' );
		}

	    if('widgets.php'!=$hook && is_admin() && 'index.php'!=$hook)
			return;
		wp_register_script( 'googleapigeolocal', 'http://maps.google.com/maps/api/js?sensor=true');
		wp_register_script('geolocaladmin_admin_scripts', plugins_url('/geolocaladmin.js',__FILE__), array('jquery'));

		wp_enqueue_script('googleapigeolocal');
		wp_enqueue_script('geolocaladmin_admin_scripts');

	}

	/**
	* Widget, on the widget Class
	*
	* @since 0.1
	*
	*/
	public function widget_init (){

		register_widget('geolocaladmin_widget');

	}	

	/**
	*The ajax save on login
	*
	* @since 0.1
	*
	*/
	public function ajax_save (){

		$sidebars_widgets = wp_get_sidebars_widgets(); 
		$current_actives_widgets = self::actives_widgets();
		$current_user_id = get_current_user_id();
		$post_datas = array(
				'lat' => $_POST['lat'],
				'long' => $_POST['long']
				);
		$update_datas = array();
		$old_datas = get_option("widget_".GEOLOCALADMIN_ID_BASE);
		$new_datas = $old_datas;

		if($old_datas){
			foreach ($old_datas as $key => $widget) {	
				$index =  GEOLOCALADMIN_ID_BASE . '-' . $key;
				if(in_array($index, $current_actives_widgets)){
					if( $current_user_id == (int)$widget["geolocaladmin_user"] && $widget["blocked"] != 'yes'){
						$update_datas = array_merge($widget,$post_datas);
						$new_datas[$key] = $update_datas;
					}			
				}
			}
			if(sizeof($current_actives_widgets)==0){
				return;
			}
		}else{
			return;
		}
		update_option( "widget_".GEOLOCALADMIN_ID_BASE, $new_datas ); 
		die();

	}

	/**
	 * print the JavaScript launch functions.
	 *
	 * @since 0.1
	 */
	public function print_scripts() {

		$this->currentAdminPage = get_current_screen(); 
		
		if(!self::blocker_verif()) {
		?>
			<script type="text/javascript">
				jQuery(document).ready(function($) {
					var $ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';				
					<?php if($this->currentAdminPage->parent_base == 'index'): ?>
						$().geolocalAdmin().detect($ajaxUrl)				
					<?php else: ?>
						$('#widgets-right .map_canvas').each(function(){
							$(this).geolocalAdmin().detect($ajaxUrl);
						});
					<?php endif; ?>
				});
			</script>
		<?php }else{ ?>
			<script type="text/javascript">
				jQuery(document).ready(function($) {
					$('#widgets-right .map_canvas').each(function(){
						$(this).geolocalAdmin().createMap($(this).siblings('#lat').val(), $(this).siblings('#long').val());	
					});
				});
			</script>
		<?php 	}
	}

	/**
	 * print the Ajax save on the admin side.
	 *
	 * @since 0.1
	 */
	public function ajax_save_script() {

		?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$(document).ajaxSuccess(function(e, xhr, settings) {
					var $ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
					if(settings.data.search('action=save-widget') != -1 && settings.data.search('id_base=<?php echo GEOLOCALADMIN_ID_BASE; ?>') != -1) {
						$('#widgets-right .map_canvas').each(function(){
								$(this).geolocalAdmin().detect($ajaxUrl);
						});
					}
				});
			});
		</script>

	<?php }

	/**
	 * print the JavaScript launch functions on the front side.
	 *
	 * @since 0.1
	 */
	public function print_scripts_front_side() {

		?>
			<script type="text/javascript">
				jQuery(document).ready(function($) {				
					$('.geolocaladmin_widget #map_canvas').each(function(){
						var $lat = $(this).siblings('input.lat').val(),
						 	$long = $(this).siblings('input.long').val();
							$(this).geolocalAdmin().createMap($lat,$long);
					});
				});
			</script>
		<?php

	}
}