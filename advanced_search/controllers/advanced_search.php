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
class advanced_search_Controller extends Controller {


  /**
  * Create of the index page
  */

  public function index(){
     $items_okay = array();
      $view = new Theme_View("page.html", "collection", "advanced_search");
      $view->content = new View ("advanced_search.html");
      $view->content->offset = 0;
      $view->content->total = 0;
      $view->content->limit = module::get_var("advanced_search","limit_per_page");
      $view->content->items = $items_okay;
      $view->content->enable_exif_gps = module::is_active("exif_gps");
      $view->content->enable_tags = module::is_active("tag"); 
      $view->content->groups = ORM::factory("group")
            ->join("groups_users", "groups_users.group_id", "groups.id", "left")
            ->where("groups_users.group_id", "IS NOT", null)
            ->group_by("id")->find_all();
      print $view;
  }

  /**
  * Beginning of search functions 
  */
   public function search() {
      
      /**
      * Check the Module Tag or Exif is Actived
      */
      $enable_exif_gps = module::is_active("exif_gps");
      $enable_tags = module::is_active("tag");

      /**
      * Get variable limit per page to use pagination in the Gallery Configuration Page 
      */
      $limit = module::get_var("advanced_search","limit_per_page");

      /**
      * Get values of Page 
      */
      $title = Input::instance()->post("title");
      $description = Input::instance()->post("description");
      $tags = Input::instance()->post("tags");
      $login = Input::instance()->post("login");
      $fullname = Input::instance()->post("fullname");
      $without = Input::instance()->post("without");
      $groups = Input::instance()->post("groups");
      $orderby = Input::instance()->post("orderby");
      $itemtype = Input::instance()->post("itemtype");
      $offset = Input::instance()->post("offset");
      $dateby = Input::instance()->post("dateby");
      $datefrom = Input::instance()->post("datefrom");
      $dateto = Input::instance()->post("dateto");

      /**
      * Set the date and time format to capture the values ​​of the data field
      */
      $timestampfrom = strtotime(str_replace('/', '-', $datefrom));
      $timestampto = strtotime(str_replace('/', '-', $dateto));

      /**
      * Insert values of Page in array for use in the queries conditions and printing again the Page
      */
      $form = array("tags" => $tags, "title" => $title, "login" => $login, "fullname" => $fullname,
       "description" => $description, "groups" => $groups, "orderby" => $orderby, "itemtype" => $itemtype,
        "withouttag" => in_array("withouttag", $without), "withoutgps" => in_array("withoutgps", $without),
        "dateby" => $dateby, "datefrom" => $datefrom, "dateto" => $dateto);

      /*
      *  The post send information to the group in the following format:
      *  groups
      *  {
      *    select_index: the index of the group within the select
      *    group_id: is the id of the group corresponding to the index of the select
      *  }
      */
      $values_groups = explode(":", $groups);
      $group_id = $values_groups[1];

      if($group_id > 0){
        $groups_users = db::build()->select()->from("groups_users")->where("group_id", "=", $group_id)->execute();
         
        $group_users_id = array();  
        
        foreach ($groups_users as $key => $group_users) {
          $group_users_id[$key] = $group_users->user_id;
        }

      } 

      /**
      * Instance Objects (User and Item) for to use in query conditions
      */
      $users = ORM::factory("user");
      $items = ORM::factory("item");
      $items2 = ORM::factory("item");

      /**
      * Conditions queries responsible for fetching user using login and owner fields
      */
      if($login){
          $users->where("name", "like","%".trim($login)."%");
      }
      if($fullname){
          $users->where("full_name", "like","%".trim($fullname)."%");
      }

      $users = $users->find_all();

      /**
      * "If" used to capture the id of users 
      */
      if($users->as_array()){
        $users_id = array(); 
        foreach ($users as $key => $user) {
            $users_id[$key] = $user->id;
            $user_array[$user->id] = $user;
        }
      }

      /**
      * "Array_intersec" used to caputurar users who are in the selected group
      */
      $user_result = array_intersect($group_users_id, $users_id);

      /**
      * Conditions queries responsible for fetching user using title and description fields
      * Obs.: To make appointments and counts items for use in pagination was necessary to create duplicate queries.
      */
      if($title){
        $items->where("title", "like","%".trim($title)."%");
        $items2->where("title", "like","%".trim($title)."%");
      }
      if($description){
        $items->where("description", "like","%".trim($description)."%");
        $items2->where("description", "like","%".trim($description)."%"); 
      }

      /**
      * The first thing is to test if the module is active tag;
      * After validating tag module is necessary to blow up the tags that were entered in the fields on the page to perform the search of items containing respectvas tags. 
      * The variable $ message_tags will be used to validate the tag inserted into the tag field.
      */
      if($enable_tags){
       
        $tags_data = array();

        foreach (explode(",", $tags) as $tag_name) {
        
          if ($tag_name) {

                $tag = ORM::factory("tag")->where("name", "=", trim($tag_name))->find();

                if(!$tag->loaded()){
                  $message_tags = true;
                  break;  
                }
                
                /**
                * If the entered tag there is no $ tags_data array is reset
                */
                $tags_data[] = $tag->id;  
                 if(is_null($tag->id)){
                  $tags_data = array();
                  break; 
                }
            }
          }

        /*
        * If typed tags, runs the query below to search items with one or more combinations of tags.
        */

        if($tags_data){
          $total_tags = count($tags_data);
          $items->join("items_tags","items.id","items_tags.item_id", "left")
            ->where("items_tags.tag_id","IN",$tags_data)
            ->group_by("items_tags.item_id")
            ->having('count("*")', '>=', $total_tags);

          $items2->join("items_tags","items.id","items_tags.item_id", "left")
            ->where("items_tags.tag_id","IN",$tags_data)
            ->group_by("items_tags.item_id")
            ->having('count("*")', '>=', $total_tags);
        }

        /*
        * Querry to search all items that do not contain tags
        */
        if(in_array("withouttag", $without)){
          $items->join("items_tags", "items.id", "items_tags.item_id", "left")
            ->where("items_tags.item_id", "IS", null);
          $items2->join("items_tags", "items.id", "items_tags.item_id", "left")
            ->where("items_tags.item_id", "IS", null);  
        }
      }

      /*
      * The "if" checks whether the module is active exif_gps
      * Querry to search all items that do not contain tags
      */
      if($enable_exif_gps){
        if(in_array("withoutgps", $without)){
          $items->join("exif_coordinates", "items.id", "exif_coordinates.item_id", "left")
            ->where("exif_coordinates.item_id", "IS", null);
          $items2->join("exif_coordinates", "items.id", "exif_coordinates.item_id", "left")
            ->where("exif_coordinates.item_id", "IS", null);  
        }
      }

      /**
      * Select items by item type
      */
      switch ($itemtype) {
        case "0":
          $items->where("type", "=", "photo");
          $items2->where("type", "=", "photo");
          break;
        case "1":
          $items->where("type", "=", "movie");
          $items2->where("type", "=", "movie");          
          break;
        case "2":
          $items->where("type", "=", "album");  
          $items2->where("type", "=", "album");
          break;
        case "3":
          break;
      }

      /**
      * Select items using the period between two dates and type of data;
      * When the option is case "0" is displayed photos of all time
      */
      switch ($dateby) {
        case "0":
          break;
        case "1":
          $items->where("captured", 'BETWEEN', array($timestampfrom, $timestampto));
          $items2->where("captured", 'BETWEEN', array($timestampfrom, $timestampto));
          break;
        case "2":
          $items->where("created", 'BETWEEN', array($timestampfrom, $timestampto));
          $items2->where("created", 'BETWEEN', array($timestampfrom, $timestampto));
          break;
        case "3":
          $items->where("updated", 'BETWEEN', array($timestampfrom, $timestampto));
          $items2->where("updated", 'BETWEEN', array($timestampfrom, $timestampto));
          break;
      }

      /**
      * Switch case utilizado para ordenar os itens na tela 
      */
      switch ($orderby) {
        case "0":
          $items->order_by("owner_id","asc");
          break;
        case "1":
          $items->order_by("title","asc");
          break;
        case "2":
          $items->order_by("captured","asc");
          break;
        case "3":
          $items->order_by("created","asc");
          break;
        case "4":
          $items->order_by("updated","asc");
          break;
      }

      /**
      * This query gets the intersect between the table groups and users using $user_result 
      */
      $items->where("owner_id","IN",$user_result);
      $items2->where("owner_id","IN",$user_result);

      /**
      * Create combobox only with groups containing user
      */
      $groups_okay = ORM::factory("group"); 
      $groups_okay->join("groups_users", "groups_users.group_id", "groups.id", "left")
            ->where("groups_users.group_id", "IS NOT", null)
            ->group_by("id");
      $groups_okay = $groups_okay->find_all();

      /**
      * Creating page sending the activation status exif_gps, tags and all groups with users to combobox
      */
      $view = new Theme_View("page.html", "collection", "advanced_search");
      $view->content = new View ("advanced_search.html");
      $view->content->enable_exif_gps = $enable_exif_gps;
      $view->content->enable_tags = $enable_tags;
      $view->content->groups = $groups_okay;
      /**
      * Variable for field validation
      */
      $valid_fields = true;
      
      /**
      * Checking if login and fullname found in database or group selected.
      */
      if(($login && !$user_result) || ($fullname && !$user_result)){
           message::warning(t(" Owner or Login not found"));  
           $valid_fields = false;
      }

      /**
      * Function to validate the date 
      */
      function validDate($date){
        
        $dt=explode("/",$date);
        $d=$dt[0];
        $m=$dt[1];
        $y=$dt[2];
   
        if (!checkdate($m,$d,$y))
          return false;
        
        return true;
      }

      /**
      *  If the combobox date is greater than zero is called the following next that 
      *  invokes the validation date and if it is true is shown the error message 
      */
      if($dateby > 0) {
        if((!validDate($datefrom)) || (!validDate($dateto))){
             message::warning(t("Invalid Date"));
             $valid_fields = false;
        }
      }

      /**
      * if($message_tags || (!$message_tags && $tags == ',')){
      */
      if($message_tags){
          message::warning(t("Invalid Tag"));
          $valid_fields = false;
      }

      /**
      * If one or all fields were typed search is performed and the result printed on the screen as below items 
      *
      */
      if(($title || $description || $without ||
        ($login && $user_result)|| ($fullname && $user_result) ||
        ($tags && !$message_tags)) && $valid_fields){
        // 
        if($users->as_array()){
          // Using find_all to search and paging
          $items = $items->find_all($limit,$offset);
          //Using find_all to count
          $total = $items2->find_all()->count();
          $items_okay = $items->as_array();
          // If the total is equal to zero, shows message " Items not found" 
          if(!$total){
             message::warning(t("Items not found"));
          }
          // Show item only when user has permission to view the item  
          foreach ($items_okay as $key => $item) {
            if(!access::can("view",$item)){
               unset($items_okay[$key]);
            }
          }
          // Sending variables to the page
          $view->content->items = $items_okay;
          $view->content->users = $user_array;
          $view->content->form = $form;
          $view->content->offset = $offset;
          $view->content->limit = $limit;
          $view->content->total = $total;
        } 
      }else{
        // If $valid_fields is true, show message "Blakn fields"
        if($valid_fields){
          message::warning(t("Blank Fields"));  
        }
        // If "if" is false, the values ​​below are sent to page because of else.
        $view->content->form = $form;
        $view->content->offset = 0;
        $view->content->total = 0;
        $view->content->limit = module::get_var("advanced_search","limit_per_page");
        $items_okay = array();
      }
      print $view;
 }

  /**
  * Function to check the type of the item before loading the editing screen
  */
 public function form_edit($id) {
    $item = model_cache::get("item", $id);
    access::required("view", $item);
    access::required("edit", $item);

    switch ($item->type) {
    case "album":
      $form = $this->album_edit_form($item);
      break;

    case "photo":
      $form = $this->photo_edit_form($item);
      break;

    case "movie":
      $form = $this->movie_edit_form($item);
      break;
    }

    print $form;
  }

  /**
  * Forge to edit album
  */

  static function album_edit_form($parent) {
    $form = new Forge(
      "advanced_search/album_update/{$parent->id}", "", "post", array("id" => "g-edit-album-form"));
    $form->hidden("from_id")->value($parent->id);
    $group = $form->group("edit_item")->label(t("Edit Album"));

    $group->input("title")->label(t("Title"))->value($parent->title)
        ->error_messages("required", t("You must provide a title"))
      ->error_messages("length", t("Your title is too long"));
    $group->textarea("description")->label(t("Description"))->value($parent->description);
    if ($parent->id != 1) {
      $group->input("name")->label(t("Directory Name"))->value($parent->name)
        ->error_messages("conflict", t("There is already a movie, photo or album with this name"))
        ->error_messages("no_slashes", t("The directory name can't contain a \"/\""))
        ->error_messages("no_trailing_period", t("The directory name can't end in \".\""))
        ->error_messages("required", t("You must provide a directory name"))
        ->error_messages("length", t("Your directory name is too long"));
      $group->input("slug")->label(t("Internet Address"))->value($parent->slug)
        ->error_messages(
          "conflict", t("There is already a movie, photo or album with this internet address"))
        ->error_messages(
          "reserved", t("This address is reserved and can't be used."))
        ->error_messages(
          "not_url_safe",
          t("The internet address should contain only letters, numbers, hyphens and underscores"))
        ->error_messages("required", t("You must provide an internet address"))
        ->error_messages("length", t("Your internet address is too long"));
    } else {
      $group->hidden("name")->value($parent->name);
      $group->hidden("slug")->value($parent->slug);
    }

    $sort_order = $group->group("sort_order", array("id" => "g-album-sort-order"))
      ->label(t("Sort Order"));

    $sort_order->dropdown("column", array("id" => "g-album-sort-column"))
      ->label(t("Sort by"))
      ->options(album::get_sort_order_options())
      ->selected($parent->sort_column);
    $sort_order->dropdown("direction", array("id" => "g-album-sort-direction"))
      ->label(t("Order"))
      ->options(array("ASC" => t("Ascending"),
                      "DESC" => t("Descending")))
      ->selected($parent->sort_order);

    // Get csrf to send in the post editor
    $csrf = access::csrf_token();

    // JavaScript that will overwrite the post of forge submit and modify their behavior
    $group->script('edit_submit')->text('
           var edit_submit = function() {
        $("input[name=edit]").click(function(){
            $.post("'. url::site("advanced_search/album_update/{$parent->id}?csrf={$csrf}").'", function(data){
            $("#g-dialog").dialog("close");
            $("#btn-search").click();
          });
        });
      }
      $(document).ready(edit_submit);
    ');

    module::event("item_edit_form", $parent, $form);

    $group = $form->group("buttons")->label("");
    $group->hidden("type")->value("album");
    $group->submit("edit")->value(t("Modify"));
    return $form;
  }

  /**
  * Function of update album
  */

  public function album_update($album_id) {
    access::verify_csrf();
    $album = ORM::factory("item", $album_id);
    access::required("view", $album);
    access::required("edit", $album);

    $form = $this->album_edit_form($album);

    try {
      $valid = $form->validate();
      $album->title = $form->edit_item->title->value;
      $album->description = $form->edit_item->description->value;
      $album->sort_column = $form->edit_item->sort_order->column->value;
      $album->sort_order = $form->edit_item->sort_order->direction->value;
      if (array_key_exists("name", $form->edit_item->inputs)) {
        $album->name = $form->edit_item->inputs["name"]->value;
      }
      $album->slug = $form->edit_item->slug->value;
      $album->validate();
    } catch (ORM_Validation_Exception $e) {
      // Translate ORM validation errors into form error messages
      foreach ($e->validation->errors() as $key => $error) {
        $form->edit_item->inputs[$key]->add_error($error, 1);
      }
      $valid = false;
    }

    if ($valid) {
      $album->save();
      module::event("item_edit_form_completed", $album, $form);

      log::success("content", "Updated album", "<a href=\"albums/$album->id\">view</a>");
      message::success(t("Saved album %album_title",
                         array("album_title" => html::purify($album->title))));

      // Stay on the same page
      json::reply(array("result" => "success"));
      }
    else {
      json::reply(array("result" => "error", "html" => (string)$form));
    }
  }

  /**
  * Forge to edit photo
  */
  static function photo_edit_form($photo) {
    $form = new Forge("advanced_search/photo_update/{$photo->id}?csrf={$csrf}", "", "post", array("id" => "g-edit-photo-form"));
    $form->hidden("from_id")->value($photo->id);
    $group = $form->group("edit_item")->label(t("Edit Photo"));
    $group->input("title")->label(t("Title"))->value($photo->title)
      ->error_messages("required", t("You must provide a title"))
      ->error_messages("length", t("Your title is too long"));
    $group->textarea("description")->label(t("Description"))->value($photo->description);
    $group->input("name")->label(t("Filename"))->value($photo->name)
      ->error_messages("conflict", t("There is already a movie, photo or album with this name"))
      ->error_messages("no_slashes", t("The photo name can't contain a \"/\""))
      ->error_messages("no_trailing_period", t("The photo name can't end in \".\""))
      ->error_messages("illegal_data_file_extension", t("You cannot change the photo file extension"))
      ->error_messages("required", t("You must provide a photo file name"))
      ->error_messages("length", t("Your photo file name is too long"));
    $group->input("slug")->label(t("Internet Address"))->value($photo->slug)
      ->error_messages(
        "conflict", t("There is already a movie, photo or album with this internet address"))
      ->error_messages(
        "not_url_safe",
        t("The internet address should contain only letters, numbers, hyphens and underscores"))
      ->error_messages("required", t("You must provide an internet address"))
      ->error_messages("length", t("Your internet address is too long"));

    // Get csrf to send in the post editor
    $csrf = access::csrf_token();

    // JavaScript that will overwrite the post of forge submit and modify their behavior
    $group->script('edit_submit')->text('
           var edit_submit = function() {
        $("input[name=edit]").click(function(){
          $.post("'. url::site("advanced_search/photo_update/{$photo->id}?csrf={$csrf}").'", function(data){
            $("#g-dialog").dialog("close");
            $("#btn-search").click();
          });
        });
      }
      $(document).ready(edit_submit);
    ');

    module::event("item_edit_form", $photo, $form);

    $group = $form->group("buttons")->label("");
    $group->submit("edit")->value(t("Modify"));
    return $form;
  }

  /**
  * Function of update photo
  */
  public function photo_update($photo_id) {
    
    access::verify_csrf();
    $photo = ORM::factory("item", $photo_id);
    access::required("view", $photo);
    access::required("edit", $photo);

    $form = photo::get_edit_form($photo);
    try {
      $valid = $form->validate();
      $photo->title = $form->edit_item->title->value;
      $photo->description = $form->edit_item->description->value;
      $photo->slug = $form->edit_item->slug->value;
      $photo->name = $form->edit_item->inputs["name"]->value;
      $photo->validate();
    } catch (ORM_Validation_Exception $e) {
      // Translate ORM validation errors into form error messages
      foreach ($e->validation->errors() as $key => $error) {
        $form->edit_item->inputs[$key]->add_error($error, 1);
      }
      $valid = false;
    }

    if ($valid) {
      $photo->save();
      module::event("item_edit_form_completed", $photo, $form);

      log::success("content", "Updated photo", "<a href=\"{$photo->url()}\">view</a>");
      message::success(
        t("Saved photo %photo_title", array("photo_title" => html::purify($photo->title))));

      // Stay on the same page
      json::reply(array("result" => "success"));
      }
    else {
      json::reply(array("result" => "error", "html" => (string)$form));
    }
  }

  /**
  * Forge to edit movie
  */
  static function movie_edit_form($movie) {
    $form = new Forge("advanced_search/movie_update/$movie->id", "", "post", array("id" => "g-edit-movie-form"));
    $form->hidden("from_id")->value($movie->id);
    $group = $form->group("edit_item")->label(t("Edit Movie"));
    $group->input("title")->label(t("Title"))->value($movie->title)
      ->error_messages("required", t("You must provide a title batatinha quando nasce...."))
      ->error_messages("length", t("Your title is too long"));
    $group->textarea("description")->label(t("Description"))->value($movie->description);
    $group->input("name")->label(t("Filename"))->value($movie->name)
      ->error_messages(
        "conflict", t("There is already a movie, photo or album with this name"))
      ->error_messages("no_slashes", t("The movie name can't contain a \"/\""))
      ->error_messages("no_trailing_period", t("The movie name can't end in \".\""))
      ->error_messages("illegal_data_file_extension", t("You cannot change the movie file extension"))
      ->error_messages("required", t("You must provide a movie file name"))
      ->error_messages("length", t("Your movie file name is too long"));
    $group->input("slug")->label(t("Internet Address"))->value($movie->slug)
      ->error_messages(
        "conflict", t("There is already a movie, photo or album with this internet address"))
      ->error_messages(
        "not_url_safe",
        t("The internet address should contain only letters, numbers, hyphens and underscores"))
      ->error_messages("required", t("You must provide an internet address"))
      ->error_messages("length", t("Your internet address is too long"));

    // Get csrf to send in the post editor
    $csrf = access::csrf_token();

    // JavaScript that will overwrite the post of forge submit and modify their behavior
    $group->script('edit_submit')->text('
           var edit_submit = function() {
        $("input[name=edit]").click(function(){
          $.post("'. url::site("advanced_search/movie_update/{$movie->id}?csrf={$csrf}").'", function(data){
            $("#g-dialog").dialog("close");
            $("#btn-search").click();
          });
        });
      }
      $(document).ready(edit_submit);
    ');

    module::event("item_edit_form", $movie, $form);

    $group = $form->group("buttons")->label("");
    $group->submit("edit")->value(t("Modify"));

    return $form;
  }

  /**
  *
  */
  public function movie_update($movie_id) {
    access::verify_csrf();
    $movie = ORM::factory("item", $movie_id);
    access::required("view", $movie);
    access::required("edit", $movie);

    $form = $this->movie_edit_form($movie);
    try {
      $valid = $form->validate();
      $movie->title = $form->edit_item->title->value;
      $movie->description = $form->edit_item->description->value;
      $movie->slug = $form->edit_item->slug->value;
      $movie->name = $form->edit_item->inputs["name"]->value;
      $movie->validate();
    } catch (ORM_Validation_Exception $e) {
      // Translate ORM validation errors into form error messages
      foreach ($e->validation->errors() as $key => $error) {
        $form->edit_item->inputs[$key]->add_error($error, 1);
      }
      $valid = false;
    }

    if ($valid) {
      $movie->save();
      module::event("item_edit_form_completed", $movie, $form);

      log::success("content", "Updated movie", "<a href=\"{$movie->url()}\">view</a>");
      message::success(
        t("Saved movie %movie_title", array("movie_title" => html::purify($movie->title))));

      // Stay on the same page
      json::reply(array("result" => "success"));
      }
    else {
      json::reply(array("result" => "error", "html" => (string) $form));
    }
  }

  /**
  * Screen to confirm the deletion
  */

  public function form_delete($id) {
    $item = model_cache::get("item", $id);
    access::required("view", $item);
    access::required("edit", $item);


    $form = new Forge("#", "","post", array("id" => "g-confirm-delete"));
    $group = $form->group("confirm_delete")->label(t("Confirm Deletion"));

    // Get csrf to send in the post delete.
    $csrf = access::csrf_token();

    // JavaScript that will overwrite the post of forge submit and modify their behavior
    $group->script('delete_submit')->text('

      var delete_submit = function() {
        $("input[name=delete]").click(function(){
          $.post("'. url::site("advanced_search/delete/{$item->id}?csrf={$csrf}").'", function(data){
            $("#g-dialog").dialog("close");
            $("#btn-search").click();
          });
          return false;
        });
      }
      $(document).ready(delete_submit);
    ');

    $group->submit("delete")->value(t("Delete"));
    $form->script("")->url(url::abs_file("modules/gallery/js/item_form_delete.js"));

    $v = new View("quick_delete_confirm.html");
    $v->item = $item;
    $v->form = $form;
    print $v;
  }

  /**
  * Delete function
  */
  public function delete($id) {
    access::verify_csrf();

    $item = model_cache::get("item", $id);
    access::required("view", $item);
    access::required("edit", $item);

    if ($item->is_album()) {
      $msg = t("Deleted album <b>%title</b>", array("title" => html::purify($item->title)));
    } else {
      $msg = t("Deleted photo <b>%title</b>", array("title" => html::purify($item->title)));
    }

    $parent = $item->parent();

    // The batch is for recursive delete of items  
    if ($item->is_album()) {
      batch::start();
      $item->delete();
      batch::stop();
    } else {
      $item->delete();
    }

    message::success($msg);
      
    json::reply(array("result" => "success", "reload"));
  }

}