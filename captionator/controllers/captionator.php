<?php defined("SYSPATH") or die("No direct script access.");
/**
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2013 Bharat Mediratta
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA  02110-1301, USA.
 */
class Captionator_Controller extends Controller {
  function dialog($album_id) {
  	$album = ORM::factory("item", $album_id);
    
    access::required("view", $album);

    if (!$album->loaded() && !access::can("edit", $album)) {
      // The user can't edit; perhaps they just logged out?
      url::redirect($album->abs_url());
    }

    $items = $album->viewable()->children()->as_array();
    $albums = array();
    if(module::is_active("exif_gps")){
    	foreach ($items as $item) {
    		$albums[] = array($item, ORM::factory("EXIF_Coordinate")->where('item_id', '=', $item->id)->find());
    	}
    }else{
    	foreach ($items as $item) {
    		$albums[] = array($item);
    	}
    }

    $v = new Theme_View("page.html", "collection", "captionator");
    $v->content = new View("captionator_dialog.html");
    $v->content->album_id = $album->id;
    $v->content->album_title = $album->title;
    $v->content->album = $albums;
    $v->content->enable_exif_gps = module::is_active("exif_gps");
    $v->content->enable_tags = module::is_active("tag");

    if($v->content->enable_exif_gps){
    	$latDefault;
    	$lngDefault;
    	
    	$exif_coordinate = ORM::factory("EXIF_Coordinate")->where('item_id', '=',$album_id)->find();
    	
    	if($exif_coordinate->latitude == NULL || $exif_coordinate->longitude == NULL){
    		if($album->id == 1){
    			  	$exif_coordinate = ORM::factory("EXIF_Coordinate");
				  	$exif_coordinate->item_id = 1;
				  	$exif_coordinate->latitude = '-24.77031290445274';
				  	$exif_coordinate->longitude = '-54.36337910592556';
				  	$exif_coordinate->save();
    		}else{
    			$album_parent = ORM::factory("item", $album->parent_id);
    			$exif_coordinate = ORM::factory("EXIF_Coordinate")->where('item_id', '=',$album_parent->id)->find();
    			 
    			while ($exif_coordinate->latitude == NULL || $exif_coordinate->longitude == NULL) {
    				if($album_parent->id == 1){
    					  	$exif_coordinate = ORM::factory("EXIF_Coordinate");
						  	$exif_coordinate->item_id = 1;
						  	$exif_coordinate->latitude = '-24.77031290445274';
						  	$exif_coordinate->longitude = '-54.36337910592556';
						  	$exif_coordinate->save();
    				}else{
    					$album_parent = ORM::factory("item", $album_parent->parent_id);
    					$exif_coordinate = ORM::factory("EXIF_Coordinate")->where('item_id', '=',$album_parent->id)->find();
    				}
    			}
    		}
    	}
    		
    	$latDefault = $exif_coordinate->latitude;
    	$lngDefault = $exif_coordinate->longitude;

    	$v->content->googlemap_api_key = module::get_var("exif_gps", "googlemap_api_key", "");
    	$v->content->latDefault = $latDefault;
    	$v->content->lngDefault = $lngDefault;
    }
    
    if ($v->content->enable_tags) {
      $v->content->tags = array();
      foreach ($album->viewable()->children() as $child) {
        $item = ORM::factory("item", $child->id);
        $tag_names = array();
        foreach (tag::item_tags($item) as $tag) {
          $tag_names[] = $tag->name;
        }
        $v->content->tags[$child->id] = implode(", ", $tag_names);
      }
    }

    print $v;
  }

  function save($album_id) {
    access::verify_csrf();

    $album = ORM::factory("item", $album_id);
    access::required("edit", $album);

    if (Input::instance()->post("save")) {
      $titles = Input::instance()->post("title");
      $descriptions = Input::instance()->post("description");
      $filenames = Input::instance()->post("filename");
      $internetaddresses = Input::instance()->post("internetaddress");
      $tags = Input::instance()->post("tags");
      $enable_tags = module::is_active("tag");
      $latitudes = Input::instance()->post("lat");
      $longitudes = Input::instance()->post("lng");

      foreach (array_keys($titles) as $id) {	
        $item = ORM::factory("item", $id);
        if ($item->loaded() && access::can("edit", $item)) {
          $gps = ORM::factory("EXIF_Coordinate")->where('item_id', '=', $id)->find();
          if (!empty($latitudes[$id]) && !empty($longitudes[$id])) {
            if (!$gps->loaded()) {
              $gps = ORM::factory("EXIF_Coordinate");
            }
            $gps->latitude = $latitudes[$id];
            $gps->longitude = $longitudes[$id];
            $gps->item_id = $id;
            $gps->save();
          }else{
            if ($gps->loaded()) {
              $gps->delete();
            }
          }

          $item->title = $titles[$id];
          $item->description = $descriptions[$id];
          $item->name = $filenames[$id];
          $item->slug = $internetaddresses[$id];
          $item->save();
          if ($enable_tags) {
            tag::clear_all($item);
            foreach (explode(",", $tags[$id]) as $tag_name) {
              if ($tag_name) {
                tag::add($item, trim($tag_name));
              }
            }
            tag::compact();
          }
        }
      }
      message::success(t("Captions saved"));
    }
    url::redirect($album->abs_url());
  }
  
}

