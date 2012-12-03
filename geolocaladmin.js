/**
 * Plugin Name: jQuery GeolocalAdmin
 * Plugin URI: http://mateo-design.com  
 * Description: The jQuery for GeolocalAdmin Wordpres plugin;
 * Version: 0.1
 * Author: mateo-design
 * Author URI: http://mateo-design.com
 */
jQuery(document).ready(function($) {

	$.fn.geolocalAdmin = function(){

		var element = this;
		/*THE CALL BACK => CALL GOOGLE API TO CREATE THE MAP
		************************/
		this.createMap = function($lat, $long, $canvas) 
		{
			if(!$canvas){
				$canvas = element[0];
			}
			map = new google.maps.Map($canvas, {
				zoom : 13,
				center : new google.maps.LatLng($lat, $long),
				mapTypeId : google.maps.MapTypeId.ROADMAP
			});
			return this;
		}

		/*LOAD THE GOOGLE API AND CHANGE THE VALUE OF THE FIELDS / CALLBACK WITH LAT/LONG VALUES
		************************/
		this.detect = function($ajaxUrl){
				if(navigator.geolocation) {						
					if(!$(this).hasClass('blocked') && $('body').hasClass('widgets-php')){
						navigator.geolocation.getCurrentPosition(function(position) {
							var $currentLat = position.coords.latitude;
							var $currentLong = position.coords.longitude;
							element.ajaxSaved($ajaxUrl,$currentLat,$currentLong);
							element.siblings('.lat').val($currentLat);
							element.siblings('.long').val($currentLong);
							element.createMap($currentLat, $currentLong,element[0]);
						});	
					}else if($('body').hasClass('index-php')){
						navigator.geolocation.getCurrentPosition(function(position) {
							var $currentLat = position.coords.latitude;
							var $currentLong = position.coords.longitude;
							element.ajaxSaved($ajaxUrl,$currentLat,$currentLong);							
						});		
					}else{
						this.createMap($(this).siblings('.lat').val(), $(this).siblings('.long').val(),$(this)[0]);	
					}		
				}else{
					alert("Your browser doesn't support the HTML5 geolocalisation");
				}						
			return this;
		}

		/*AJAX SAVE FOR THE PANEL PAGE
		************************/
		this.ajaxSaved = function($ajaxUrl,$currentLat,$currentLong){					
					var data = {
						action: 'geolocaladmin_ajax_save',
						lat : $currentLat,
						long : $currentLong,
					}
					$.ajax({
						type : 'post',
						url : $ajaxUrl,
						data : data
					})/*.done(function(data){
					});*/
			return this;
		}
		return this;
	}
});