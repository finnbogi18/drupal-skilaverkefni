<?php


namespace Drupal\music_search;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\TempStore\PrivateTempStoreFactory;

class DiscogsSearchService extends ServiceProviderBase
{
  protected PrivateTempStoreFactory $tempStoreFactory;
  protected ConfigFactory $configFactory;

  public function __construct(
    PrivateTempStoreFactory $tempStoreFactory,
    ConfigFactory $configFactory
  ){
    $this->tempStoreFactory = $tempStoreFactory;
    $this->configFactory = $configFactory;
  }

  public function discogs_artist_search($artist_name) {
    $uri = 'https://api.discogs.com/database/search?q=' . rawurlencode($artist_name) .
      '&type=artist&per_page=10&page1';
    return $this->discogs_api_get_query($uri);
  }
  public function discogs_track_search($track_name) {
    $uri = 'https://api.discogs.com/database/search?q=' . rawurlencode($track_name) .
      '&type=release_title&per_page=10&page1';
    return $this->discogs_api_get_query($uri);
  }
  public function discogs_album_search($album_name) {
    $uri = 'https://api.discogs.com/database/search?q=' . rawurlencode($album_name) .
      '&type=master&per_page=10&page1';
    return $this->discogs_api_get_query($uri);
  }
  public function discogs_artist_search_id($artist_id) {
    $uri = 'https://api.discogs.com/artists/' . $artist_id;
    return $this->discogs_api_get_query($uri, FALSE);
  }
  public function discogs_track_search_id($track_id) {
    $uri = 'https://api.discogs.com/tracks/' . $track_id;
    return $this->discogs_api_get_query($uri, FALSE);
  }
  public function discogs_album_search_id($album_id) {
    $uri = 'https://api.discogs.com/albums/' . $album_id;
    return $this->discogs_api_get_query($uri, FALSE);
  }
  public function discogs_master_search_id($master_id) {
    $uri = 'https://api.discogs.com/masters/' . $master_id;
    return $this->discogs_api_get_query($uri, FALSE);
  }
  /**
   * Sends a GET query to Discogs for specific URL
   *
   * @param $uri string
   *   The fully generated search string
   *
   * @return object|bool|array Returns a stdClass with the search results or an error message
   *   Returns a stdClass with the search results or an error message
   */
  public function discogs_api_get_query(string $uri, bool $with_auth = TRUE): object|bool|array {
    $cache = $this->discogs_api_get_cache_search($uri);
    $config = $this->configFactory->get('music_search.discogs');
    $search_results = null;
    $consumer_key = $config->get('consumer_id');
    $consumer_secret = $config->get('consumer_secret');

    if (!empty($cache)) {
      $search_results = $cache;
    }
    else {
      $auth_arg = '&key=' . $consumer_key . '&secret=' . $consumer_secret;
      //$token = $this->discogs_api_get_auth_token();
      //$token = json_decode($token);
      $options = array(
        'method' => 'GET',
        'timeout' => 3,
        'headers' => array(
          'Accept' => 'application/json',
        ),
      );
      if ($with_auth == TRUE) {
        $uri .= $auth_arg;
      }
      $search_results = \Drupal::httpClient()->get($uri, $options);

      if (empty($search_results->error)) {
        $search_results = json_decode($search_results->getBody());
        //$this->_spotify_api_set_cache_search($uri, $search_results);

      }
      else {
        \Drupal::messenger()->addMessage(t('The search request resulted in the following error: @error.', array(
          '@error' => $search_results->error,
        )));
        return $search_results->error;
      }
    }

    return $search_results;
  }

  /**
  * Saves a search to Drupal's internal cache.
  *
  * @param string $cid
  *   The cache id to use.
  * @param array $data
  *   The data to cache.
  */
  public function discogs_api_set_cache_search(string $cid, array $data) {
    \Drupal::cache()->set($cid, $data, 86000, ['spotify-api-cache'] );
  }

  /**
  * Looks up the specified cid in cache and returns if found
  *
  * @param string $cid
  *   Normally a uri with a search string
  *
  * @return array|bool
  *   Returns either the cache results or false if nothing is found.
  */
  public function discogs_api_get_cache_search(string $cid): array|bool {
    $cache = \Drupal::cache()->get($cid);
    if (!empty($cache)) {
      if ($cache->expire > time()) {
        return $cache->data;
      }
    }
    return FALSE;
  }

  /**
  * Gets Auth token from the Spotify API
  */
  public function discogs_api_get_auth_token(): bool|string {
    $tempstore = $this->tempStoreFactory->get('ex_form_values');
    $params = $tempstore->get('params');
    $client_id = 'VvosTkAibGzSsWkqcDnr';
    $secret_id = 'PRdkHWbtOngeHOebJKawbDmxyxtBuyOI';
    $connection_string = "https://api.discogs.com/oauth/request_token";
    $key = base64_encode($client_id . ':' . $secret_id);
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $connection_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
    curl_setopt($ch, CURLOPT_POST, 1);

    $headers = array();
    $headers[] = "Authorization: Basic " . $key;
    $headers[] = "Content-Type: application/x-www-form-urlencoded";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);

    curl_close ($ch);
    return $result;
  }
}

