<?php defined("SYSPATH") or die("No direct script access.") ?>
<div id="g-captionator-dialog">
	<script type="text/javascript">
	    $(document).ready(function() {
	      $('form input[name^=tags]').ready(function() {
	          $('form input[name^=tags]').gallery_autocomplete(
	            "<?= url::site("/tags/autocomplete") ?>",
	            {max: 30, multiple: true, multipleSeparator: ',', cacheLength: 1});
	        });
	      $('form input[name^=title]').change(function() {
	        var title = $(this).val();
	        slug = title.replace(/^\'/, "");
	        var slug = title.replace(/[^A-Za-z0-9-_]+/g, "-");
	        slug = slug.replace(/^-/, "");
	        slug = slug.replace(/-$/, "");
	        $(this).parent().parent().find("input[name^=internetaddress]").val(slug);
	      });
	    });
	</script>
	<form action="<?= url::site("captionator/save/{$album_id}") ?>"
		method="post" id="g-captionator-form">
		<?= access::csrf_form_field() ?>
		<fieldset>
			<legend>
				<?= t("Add captions for photos in <b>%album_title</b>", array("album_title" => $album_title)) ?>
			</legend>
				<? foreach ($album as $child): ?>
					<table>
						<tr>
							<td style="width: 140px">
								<?= $child[0]->thumb_img(array(), 140, true) ?>
							</td>
							<td>
								<ul>
									<li><label for="title[<?= $child[0]->id ?>]"> <?= t("Title") ?> </label>
										<input required type="text" name="title[<?= $child[0]->id ?>]"
											value="<?= html::chars($child[0]->title) ?>" /></li>
									<li><label for="description[<?= $child[0]->id ?>]"> <?= t("Description") ?> </label>
										<textarea style="height: 5em"
											name="description[<?= $child[0]->id ?>]"><?= $child[0]->description ?></textarea></li>
									<? if ($enable_tags): ?>
										<li><label for="tags[<?= $child[0]->id ?>]"> <?= t("Tags (comma separated)") ?> </label>
											<input type="text" name="tags[<?= $child[0]->id ?>]"
												class="ac_input" autocomplete="off"
												value="<?= html::chars($tags[$child[0]->id]) ?>" /></li>
									<? endif ?>
										<li><label for="filename[<?= $child[0]->id ?>]"> <?= t("Filename") ?> </label>
											<input type="text" name="filename[<?= $child[0]->id ?>]"
												class="ac_input" autocomplete="off"
												value="<?= html::chars($child[0]->name) ?>" /></li>
										<li><label for="internetaddress[<?= $child[0]->id ?>]"> <?= t("Internet Address") ?> </label>
											<input type="text" name="internetaddress[<?= $child[0]->id ?>]"
											class="ac_input" autocomplete="off"
											value="<?= html::chars($child[0]->slug) ?>" /></li>
									<? if ($enable_exif_gps): ?>
										<li><label for="lat[<?= $child[0]->id ?>]"> <?= t("Latitude") ?> </label>
											<input type="text" name="lat[<?= $child[0]->id ?>]"
											class="ac_input" autocomplete="off"
											value="<?= $child[1]->latitude ?>" /></li>
										<li><label for="lng[<?= $child[0]->id ?>]"> <?= t("Longitude") ?> </label>
											<input type="text" name="lng[<?= $child[0]->id ?>]"
											class="ac_input" autocomplete="off"
											value="<?= $child[1]->longitude ?>" /></li>
										<li><a style="cursor: pointer" onclick="open_dialog(<?= $child[0]->id ?>)"  
												class="g-button ui-state-default ui-corner-all "><?= t("Map")?></a>
									<? endif ?>
								</ul>
							</td>
						</tr>
				</table>
			<? endforeach ?>
		</fieldset>
		<fieldset>
			<input type="submit" name="cancel" value="<?= t("Cancel") ?>" />
			<input type="submit" name="save" value="<?= t("Save") ?>" />
		</fieldset>
	</form>
</div>

<? if ($enable_exif_gps): ?>
	<div id="dialog-canvas">
		<div id="map-canvas" style="width: 100%; height: 100%;"></div>
	</div>
	
	<script	src="https://maps.googleapis.com/maps/api/js?v=3.exp&key=<?= $googlemap_api_key ?>&sensor=false"></script>
	
	<script type="text/javascript">
	
		var latDefault = <?= $latDefault ?>;
		var lngDefault = <?= $lngDefault ?>;
		var item_id = null;
		var markers=[];
		var lat,lng,latLng,map;
	
		function initialize(){
			
			latLng = new google.maps.LatLng(latDefault, lngDefault);
			
			var mapOptions = {
				zoom: 8,
				center: latLng,
				mapTypeId: google.maps.MapTypeId.HYBRID
			};
		  
			map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);
		
			config_dialog();
		
		}
		
		google.maps.event.addDomListener(window, 'load', initialize);
		
		function config_dialog(){
			
			var wWidth = $(window).width();
		    var dWidth = wWidth * 0.5;
		    var wHeight = $(window).height();
		    var dHeight = wHeight * 0.5;
			
		    $('#dialog-canvas').dialog({
				autoOpen: false,
				height: dHeight,
			  	width: dWidth,
			  	modal: true,
			  	title: '<?= t("Select a location from the map") ?>',
			  	position: ['right','bottom'],
				resizeStop: function(event, ui) { google.maps.event.trigger(map, 'resize'); },
				open: function(event, ui) { google.maps.event.trigger(map, 'resize'); },
	
		    });
		    
		}
		
		function create_marker(lat,lng){
			
			latLng = new google.maps.LatLng(lat, lng);
			
			marker = new google.maps.Marker({
				map: map,
			    draggable: true 
			});
		
			if(lat != latDefault || lng != lngDefault){
				marker.setPosition(latLng);
				map.setZoom(15);
			}else{
				map.setZoom(8);
			}
		
			google.maps.event.addListener(map, 'click', function(event) {
				set_inputs(event);
				marker.setPosition(event.latLng);
			});
		
			google.maps.event.addListener(marker, 'drag', function(event) {
				set_inputs(event);
				marker.setPosition(event.latLng);
			});
			
			markers.push(marker);
	
			map.setCenter(latLng);
	
		}
		
		
		function delete_markers() {
	
			for (var i = 0; i < markers.length; i++) {
				markers[i].setMap(null);
			}
			markers = [];
			
		}
			
		function set_inputs(event) {
				
			$('input[name=lat['+item_id+']]').attr('value', event.latLng.lat());
			$('input[name=lng['+item_id+']]').attr('value', event.latLng.lng());
			
		}
		
		function get_inputs() {
				
			lat = $('input[name=lat['+item_id+']]').attr('value'); 
			lat = ((typeof lat === 'undefined') || (lat == '')) ? latDefault : lat;
			lng = $('input[name=lng['+item_id+']]').attr('value');
			lng = ((typeof lng === 'undefined')  || (lng == '')) ? lngDefault : lng;
				
		}
		
		function open_dialog(id) {
		
		  	item_id = id;
		  	delete_markers();
		 	get_inputs();
		 	$('#dialog-canvas').dialog('open');
		 	create_marker(lat,lng);
		
		}
	
	</script>
<? endif ?>