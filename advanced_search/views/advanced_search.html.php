<?php defined("SYSPATH") or die("No direct script access.") ?>  
<?= $theme->script("jquery-ui.min.js"); ?>
<?= $theme->script("jquery.ui.datepicker-pt-BR.js"); ?>
	<!-- Javascript for tag autocomplete -->
	<div id="g-advanced_search"> 
	<script type="text/javascript">
	    $(document).ready(function() {
	      $('form input[name^=tags]').ready(function() {
	          $('form input[name^=tags]').gallery_autocomplete(
	            "<?= url::site("/tags/autocomplete") ?>",
	            {max: 30, multiple: true, multipleSeparator: ',', cacheLength: 1});
	        })
	    });
    </script>

	<form id="g-advanced-search-form" action="<?= url::site("advanced_search/search") ?>" method="post" >
	    <?= access::csrf_form_field() ?>
	    <fieldset class="g-advanced-search-view">
	    	<legend>
	       		 <?= t("Advanced Search") ?>
	      	</legend>
			<div class="g-div-form">
				<div class="g-div-content-column">
					<div class="g-div-column">
						<label class="g-labels" for="title"> <?= t("Title").":" ?> </label>
			    		<input class="g-inputs" type="text" name="title" value="<?= $form["title"] ?>" tabindex="1" />
			    		<label class="g-labels" for="fullname"> <?= t("Owner").":" ?> </label>
			    		<input class="g-inputs" type="text" name="fullname" value="<?= $form["fullname"] ?>" tabindex="4" />
			    		<label class="g-labels" for="itemtype"> <?= t("Type").":" ?> </label>
						<select id="itemtype" class="g-combos" name="itemtype" tabindex="7">
							<option value="0"><?= t("Photo") ?></option>
							<option value="1"><?= t("Movie") ?></option>
							<option value="2"><?= t("Album") ?></option>
							<option value="3"><?= t("All") ?></option>
						</select>
						<label class="g-labels" for="dateby"> <?= t("Date").":" ?> </label>
						<select id="dateby" class="g-combos" name="dateby" tabindex="11">
							<option value="0"><?= t("All") ?></option>
							<option value="1"><?= t("Date captured") ?></option>
							<option value="2"><?= t("Date uploaded") ?></option>
							<option value="3"><?= t("Date modified") ?></option>
						</select>
					</div>

					<div class="g-div-column">
						<label class="g-labels" for="description"> <?= t("Description").":" ?> </label>
			    		<input class="g-inputs" type="text" name="description" value="<?= $form["description"] ?>" tabindex="2" />
			    		<label class="g-labels" for="login"> <?= t("Login").":" ?> </label>
			    		<input class="g-inputs" type="text" name="login" value="<?= $form["login"] ?>" tabindex="5" />
			    		<label class="g-labels" for="orderby"> <?= t("Order by").":" ?> </label>
						<select id="orderby" class="g-combos" name="orderby" tabindex="8">
							<option value="0"><?= t("Owner") ?></option>
							<option value="1"><?= t("Title") ?></option>
							<option value="2"><?= t("Date captured") ?></option>
							<option value="3"><?= t("Date uploaded") ?></option>
							<option value="4"><?= t("Date modified") ?></option>
						</select>
						<label class="g-labels" for="datefrom"> <?= t("From").":" ?> </label>
			    		<input id="datefrom" class="date-pick g-inputs" type="text" name="datefrom" value="<?= $form["datefrom"] ?>" tabindex="12" />
					</div>

					<div class="g-div-column">
						<? if($enable_tags){ ?>
					    	<label class="g-labels" for="tags"> <?= t("Tags").":" ?> </label>
						    <input id="tags" class="g-inputs" type="text" name="tags" value="<?= $form["tags"] ?>" autocomplete="off" tabindex="3" />
			    		<? } ?>
			    		<label class="g-labels" for="groups"> <?= t("Groups").":" ?> </label>
						<select id="groups" class="g-combos" name="groups" tabindex="6">
							<? foreach ($groups as $index => $group): ?>
								<option value="<?= $index.':'.$group->id ?>"><?= t($group->name) ?></option>
							<? endforeach ?>
						</select>
						<div id="g-filterby">
						  	<? if($enable_tags || $enable_exif_gps){ ?>	    
								<label id="g-filterby" for="filterby"> <?= t("Filter By").":" ?> </label>
								<? if($enable_tags){ ?>	    	
								    <input id="withouttag" type="checkbox" name="without[]" value="withouttag" tabindex="9" 
								    	onchange="javascript:document.getElementById('tags').disabled = this.checked"/>
									<label for="withouttag"> <?= t("Without Tags") ?> </label>
								    <? if($form["withouttag"]) { ?>
										<script type="text/javascript">
											document.getElementById('withouttag').checked = true;
											document.getElementById('tags').disabled = true;
										</script>
							    	<? } ?> 
							    <? } ?>
							    <br>
						    	<? if($enable_exif_gps){ ?> 
							    	<input id="withoutgps" type="checkbox" name="without[]" value="withoutgps" tabindex="10"/>
							    	<label for="withoutgps"> <?= t("Without Coordinates")?> </label>    		
							    	<? if($form["withoutgps"]) {  ?>
										<script type="text/javascript">
											document.getElementById('withoutgps').checked = true;
										</script>
							    	<? } ?> 
								<? } ?>
							<? } ?>
						</div>
						<label class="g-labels" for="dateto"> <?= t("To").":" ?> </label>
			    		<input id="dateto" class="date-pick g-inputs" type="text" name="dateto" value="<?= $form["dateto"] ?>" tabindex="13" />
					</div>
				</div>

				<div class="g-div-content-buttons">
					<br>
					<input id="btn-search" class="g-button ui-icon-left ui-state-default ui-corner-all" type="submit" value="<?= t("Search") ?>" tabindex="14" />
					<input class="g-button ui-icon-left ui-state-default ui-corner-all" type="reset" value="<?= t("Clear") ?>" tabindex="15"
									onclick="javascript:window.location.href='<?= url::site("advanced_search") ?>'" />				
				</div>
			</div>
			<!-- Code to select combobox items -->
			<script type="text/javascript">
				document.getElementById('groups').selectedIndex = ("<?= $form['groups'] ?>").split(":")[0];
				document.getElementById('orderby').selectedIndex = "<?= $form['orderby'] ?>";
				document.getElementById('dateby').selectedIndex = "<?= $form['dateby'] ?>";
				document.getElementById('itemtype').selectedIndex = "<?= $form['itemtype'] ?>";
				
				var datepick = $('.date-pick'); 

				function checkSelect() {
					var all = $('select[name="dateby"]').val();
				    if(all == "0") {       
			        	datepick.attr('disabled','disabled');
			        } else {
			        	datepick.removeAttr('disabled');          	
			        }
				}

				$(document).ready(function() {
					datepick.datepicker( $.datepicker.regional["<?= module::get_var('gallery','default_locale'); ?>"] );
					$('#ui-datepicker-div a').removeClass('ui-state-highlight');
				    datepick.attr('disabled','disabled');      
				    $('select[name="dateby"]').change(checkSelect);
				    checkSelect();
				});
			</script>
	    </fieldset>
		<br>
		<? if (count($items) > 0){ ?>
		<fieldset>
			<legend>
				<?= t("Result") ?>
		  	</legend>
			<table>
		    <? foreach ($items as $item): 
		 		$user = $users[$item->owner_id]; ?>
			        <tr>
			         	<td style="width: 140px">
				            <a href=<?= $item->url() ?> onclick="window.open(this.href);return false;">
							   <?= $item->thumb_img(array("class" => "g-thumbnail"), 250) ?>
							</a>
			         	</td>
			        	<td>
				            <ul>
				            	<li>
				            		<?= t("Type of item").": ".$item->type ?>
				            	</li>
				            	<li>
				                	<?= t("Title of Item").": ".$item->title ?>
				              	</li>
					            <li>
					            	<?= t("Owner").": ".$user->full_name ?> 
					            </li>
					            <li>
					            	<?= t("Login").": ".$user->name." - ".t("E-mail: ").$user->email ?> 
					            </li>
					            <li>
					            	<?= t("Date captured").": ".gallery::date_time($item->captured); ?> 
					            </li>
					            <li>
					            	<?= t("Date uploaded").": ".gallery::date_time($item->created); ?> 
					            </li>
					            <li>
					            	<?= t("Date modified").": ".gallery::date_time($item->updated); ?> 
					            <li>
					             	<br>
					            	<? if(access::can("edit",$item)){ ?>
					             		<a href="<?= url::site("advanced_search/form_edit/$item->id") ?>"
						             	class="g-dialog-link g-button ui-icon-left ui-state-default ui-corner-all"> <?= t("Edit") ?>  </a>
					            	<? } ?>
									<? if(access::can("add",$item)){ ?>
				              			<a href="<?= url::site("advanced_search/form_delete/$item->id") ?>"
						             	class="g-dialog-link g-button ui-icon-left ui-state-default ui-corner-all"> <?= t("Delete") ?> </a>
					            	<? } ?>
									<? if(access::can("view_full",$item)){ ?>
										<a href="<?= url::site("downloadfullsize/send/$item->id") ?>"
										class="g-button ui-icon-left ui-state-default ui-corner-all"><?= t("Download") ?></a>
									<? } ?>
				          		</li>
			 		    	</ul>
			         	</td>
			        </tr>
		 	<? endforeach ?>
			</table>
	 	</fieldset>
	 	<div>
	 		<input type="hidden" id="g-offset" name="offset" value="<?= $offset ?>" >
	 		<input type="hidden" id="g-total" name="total" value="<?= $total ?>" >
	 		<input type="hidden" id="g-limit" name="limit" value="<?= $limit ?>" >

			<ul class="g-paginator ui-helper-clearfix">
			  <li class="g-first">
			    <a class="g-button ui-icon-left ui-state-default ui-corner-all" onclick="first_page();">
			       <span class="ui-icon ui-icon-seek-first"></span><?= t("First") ?></a>
			    <a class="g-button ui-icon-left ui-state-default ui-corner-all" onclick="previous_page();">
			      <span class="ui-icon ui-icon-seek-prev"></span><?= t("Previous") ?></a>
			  </li>

			  <!-- Values for the pagination -->
			  <li class="g-info">
			  	<? if ($total > 0){ 
			  		if($limit > $total){
			  			$limit = $total;	
			  			echo t("%offset of %total", array("offset" => $offset+$limit, "total" => $total));
			  		}else{
			  			if ($offset+$limit > $total){
			  				$offset=$total-$limit;
				        	echo t("%offset of %total", array("offset" => $offset+$limit, "total" => $total));
			        	}else{
			        		echo t("%offset of %total", array("offset" => $offset+$limit, "total" => $total));
			        	}
			        }
			    } ?>
			  </li>

			  <li class="g-text-right">
			  	<a class="g-button ui-icon-right ui-state-default ui-corner-all" onclick="next_page();">
			      <span class="ui-icon ui-icon-seek-next"></span><?= t("Next") ?></a>
			    <a class="g-button ui-icon-right ui-state-default ui-corner-all" onclick="last_page();">
			        <span class="ui-icon ui-icon-seek-end"></span><?= t("Last") ?></a>
			  </li>
			</ul>
		
		</div>
		

	 	<? } ?>
	 	<!-- Rules of pagination -->
		 <script type="text/javascript">

			var limit = <?= $limit? $limit:0 ?>;
			var offset = <?= $offset? $offset:0 ?>;
			var total =  <?= $total? $total:0 ?>;

			function first_page(){
				if(offset > 0){
					document.getElementById('g-offset').value = 0;
					submit_form();		
				}
			}
			function last_page(){
				if(offset != total & offset+limit != total){
					document.getElementById('g-offset').value = total-limit;
					submit_form();
				}
			}
			function previous_page(){
				if(offset > 0){
					if(offset < limit){
						document.getElementById('g-offset').value = 0;	
					}else{
						document.getElementById('g-offset').value = offset-limit;	
					}
					submit_form();
				}
			}
			function next_page(){
				if(offset != total & offset+limit != total){
					document.getElementById('g-offset').value = offset+limit;
					submit_form();
				}
			}
			function submit_form(){
				document.getElementById('g-advanced-search-form').submit();
			}

		</script>
 	</form>
</div>
