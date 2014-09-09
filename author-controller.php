<?php

class JSON_API_Author_Controller {

  public function image() {
    global $json_api;
    # Takes id= and size= params
    $id = $json_api->query->id;
    $size = $json_api->query->size;
    $avatar_img = get_avatar( $id, $size );
    // Returns only the <img> src value.
    preg_match("/src='(.*?)'/i", $avatar_img, $matches);
    return array(
      "image_url" => $matches[1]
    );
  }

}

?>
