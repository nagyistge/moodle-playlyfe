<?php

require_once('/var/www/html/vendor/autoload.php');
use Playlyfe\Sdk\Playlyfe;

class block_playlyfe_sdk {

    public static function get_pl() {
      $client_id = get_config('playlyfe', 'client_id');
      $client_secret = get_config('playlyfe', 'client_secret');
      if ($client_id === null or $client_secret === null) {
        throw new \Exception('Please set your client_id and client_secret in the Playlyfe Plugin Settings Page');
      }
      $options =  array(
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'type' => 'client',
        'store' => function($token) {
          set_config('access_token', $token['access_token'], 'playlyfe');
          set_config('expires_at', $token['expires_at'], 'playlyfe');
        },
        'load' => function() {
          $access_token = array(
            'access_token' => get_config('playlyfe', 'access_token'),
            'expires_at' => get_config('playlyfe', 'expires_at')
          );
          return $access_token;
        }
      );
      if(get_config('playlyfe', 'access_token') !== null) {
        $options['token'] = true;
      }
      return new Playlyfe($options);
    }
}
