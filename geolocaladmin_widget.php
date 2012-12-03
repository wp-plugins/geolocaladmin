<?php 
/**
 *The widget for GeolocalAdmin plugin;
 *
 * @copyright 2012
 * @version 0.1
 * @author mateo-design
 * @link http://mateo-design.com
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @package GeolocalAdmin
 */

class geolocaladmin_widget extends WP_Widget {

	function geolocaladmin_widget() {
		$options = array(
			'classname'=>GEOLOCALADMIN_ID_BASE,
			'description'=>'A widget to get your GPS position and display a googlemap of it;'
		);
		$this->WP_Widget(GEOLOCALADMIN_ID_BASE, 'GeolocalAdmin Widget', $options);
	}

	function widget($args, $geolocaladmin_options) {
		extract($args);
		echo $before_widget;
		echo $before_title.$geolocaladmin_options['title'].$after_title; ?>
		<div id="map_canvas" class="map_canvas<?php if ($geolocaladmin_options['blocked']=='yes'){echo ' blocked';}else{echo'';} ?>"></div>
		<input value="<?php echo $geolocaladmin_options['long'] ?>" name="<?php echo $this->get_field_name('long')?>" class="long" type="hidden"/>
		<input value="<?php echo $geolocaladmin_options['lat'] ?>" name="<?php echo $this->get_field_name('lat')?>" class="lat" type="hidden"/>
		<?php echo $after_widget; }
	
	function update($new_instance, $old_instance) {		
		$geolocaladmin_options = $old_instance;
		if($new_instance['blocked'] != 'yes' && $new_instance['geolocaladmin_user']==get_current_user_id()){
			$geolocaladmin_options['lat'] = strip_tags($new_instance['lat']);
			$geolocaladmin_options['long'] = strip_tags($new_instance['long']);
		}
		$geolocaladmin_options['title'] = strip_tags($new_instance['title']);
		$geolocaladmin_options['blocked'] = strip_tags($new_instance['blocked']);
		$geolocaladmin_options['geolocaladmin_user'] = strip_tags($new_instance['geolocaladmin_user']);
		return $geolocaladmin_options;	
	}
	
	function form($geolocaladmin_options) {
		$geolocaladmin_optionsefaults = array(
		'title'=> 'Where am I ??',
		'lat' => 48.856614,
		'long'=> 2.352222,
		'blocked' => '',
		'geolocaladmin_user' => 'admin'
		);
		$geolocaladmin_options = wp_parse_args($geolocaladmin_options, $geolocaladmin_optionsefaults); 
		$current_user_id = get_current_user_id();?>
			<label for="<?php echo $this->get_field_id('title')?>">Title</label>
			<input class="widefat" value="<?php echo $geolocaladmin_options['title'] ?>" name="<?php echo $this->get_field_name('title')?>" id="<?php echo $this->get_field_id('title')?>" type="text"/>
			<label for="<?php echo $this->get_field_id('lat')?>">Latitude</label>
			<input class="widefat lat" value="<?php echo $geolocaladmin_options['lat'] ?>" name="<?php echo $this->get_field_name('lat')?>" id="lat" type="text"/>
			<label for="<?php echo $this->get_field_id('long')?>">Longitude</label>
			<input class="widefat long" value="<?php echo $geolocaladmin_options['long'] ?>" name="<?php echo $this->get_field_name('long')?>" id="long" type="text"/>
			<div class="map_canvas<?php if ($geolocaladmin_options['blocked']=='yes' || $current_user_id != $geolocaladmin_options['geolocaladmin_user'] ){echo ' blocked';}else{echo"";} ?>"></div>
			<label for="<?php echo $this->get_field_id( 'blocked' ); ?>">
			<?php _e('Block on current location:', 'geolocaladmin'); ?></label><br />
		    <input type="checkbox" id="blocked" name="<?php echo $this->get_field_name('blocked'); ?>" value="yes" <?php checked($geolocaladmin_options['blocked'], 'yes' ); ?>  />
			<label for="<?php echo $this->get_field_id( 'geolocaladmin_user' ); ?>"><?php __('Choose the user to track:', 'geolocaladmin'); ?></label><br />
		<?php 
		$args = array(
		    'show_option_all'         => null, 
		    'show_option_none'        => null, 
		    'hide_if_only_one_author' => null,
		    'orderby'                 => 'display_name',
		    'order'                   => 'ASC',
		    'include'                 => null, 
		    'exclude'                 => null, 
		    'multi'                   => false,
		    'show'                    => 'display_name',
		    'echo'                    => true,
		    'selected'                => $geolocaladmin_options['geolocaladmin_user'],
		    'include_selected'        => $geolocaladmin_options['geolocaladmin_user'],
		    'name'                    => $this->get_field_name( 'geolocaladmin_user' ), 
		    'id'                      => $this->get_field_id( 'geolocaladmin_user' ), 
		    'class'                   => $this->get_field_id( 'geolocaladmin_user' ), 
		    'blog_id'                 => $GLOBALS['blog_id'],
	     );
		 wp_dropdown_users( $args ); ?> 
		<p><i><?php echo __('Please reload to actualize your position', 'geolocaladmin'); ?></i> </p>
		<?php 
		}
	}