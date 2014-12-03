<?php
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('classes/sdk.php');
require_login();
global $USER;
$pl = local_playlyfe_sdk::get_pl();

$route = optional_param('route', '/definitions/metrics/', PARAM_TEXT);
$size = optional_param('size', 'medium', PARAM_TEXT);
$metric = optional_param('metric', '', PARAM_TEXT);
$item = optional_param('item', '', PARAM_TEXT);

$query = array('player_id' => 'u'.$USER->id, 'size' => $size);
if($item != ''){
  $query['item'] = $item;
}
$url = $pl->get_image_url('/runtime/assets'.$route.$metric, $query);
$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
$picture = curl_exec($ch);
curl_close($ch);
header('Content-type: image/png');
echo $picture;
