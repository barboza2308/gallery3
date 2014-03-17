<?php defined("SYSPATH") or die("No direct script access.");/**
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
class advanced_search_event {
  static function admin_menu($menu, $theme) {
    if(module::get_var("advanced_search","limit_per_page")){
    	$menu->get("content_menu")
    		->append(Menu::factory("link")
    		->id("advanced_search")
    		->label(t("Advanced Search"))
    		->url(url::site("advanced_search")));
    }
  }

 static function album_menu($menu, $theme) {
  if(module::get_var("advanced_search","limit_per_page")){
    $menu->append(Menu::factory("link")
           ->id("advanced_search")
           ->label(t("View Advanced Search"))
           ->url(url::site("advanced_search"))
           ->css_id("g-advanced-search-link"));
    }
  }
}
