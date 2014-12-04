<?php
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('classes/sdk.php');
require_login();
global $USER;
$pl = local_playlyfe_sdk::get_pl();
$image_id = optional_param('image_id', '', PARAM_TEXT);
$size = optional_param('size', 'medium', PARAM_TEXT);
$query = array('player_id' => 'u'.$USER->id, 'size' => $size);
$picture = $pl->read_image($image_id, $query);
header('Content-type: image/png');
echo $picture;
