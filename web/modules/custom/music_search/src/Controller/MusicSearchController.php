<?php

namespace Drupal\music_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\music_search\DiscogsSearchService;
use Drupal\music_search\SpotifySearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class MusicSearchController
 *
 * @package Drupal\music_search\Controller
 */
class MusicSearchController extends ControllerBase
{
  protected PrivateTempStoreFactory $tempStoreFactory;
  protected SpotifySearchService $spotifySearchService;
  protected DiscogsSearchService $discogsSearchService;

  public function __construct(
    PrivateTempStoreFactory $tempStoreFactory,
    SpotifySearchService $spotifySearchService,
    DiscogsSearchService $discogsSearchService
  ) {
    $this->tempStoreFactory = $tempStoreFactory;
    $this->spotifySearchService = $spotifySearchService;
    $this->discogsSearchService = $discogsSearchService;
  }

  public static function create(
    ContainerInterface $container
  ) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('music_search.spotify'),
      $container->get('music_search.discogs')
    );
  }

  /**
   *
   * @return array
   *
   */
  public function musicSearch() {
    $tempstore = $this->tempStoreFactory->get('ex_form_values');
    $params = $tempstore->get('params');
    $client_id = $params['client_id'];
    $secret_id = $params['secret_id'];
    $spotify = $this->spotifySearchService;
    $discogs = $this->discogsSearchService;

    $response = $discogs->discogs_artist_search('Justin');
    $items = $response->artists->items;
    foreach ($items as $item) {
      $matches[] = ['name' => $item->name];
    }

    $build[]['message'] = [
      '#type' => 'markup',
      '#markup' => t("Client ID: @client_id - Secret ID: @secret_id", ['@client_id' => $client_id, '@secret_id' => $secret_id])
    ];

    return $build;
  }

  public function autocompleteArtist(request $request): JsonResponse {
    $spotify = $this->spotifySearchService;
    $discogs = $this->discogsSearchService;
    $matches = array();
    $string = $request->query->get('q');
    if ($string) {
      $matches = array();
      $response = $spotify->spotify_artist_search($string);
      $items = $response->artists->items;
      $matches[] = ['label' => '---Spotify---'];
      foreach ($items as $item) {
        $matches[] = ['name' => $item->name, 'label' => $item->name];
      }
      $response_disc = $discogs->discogs_artist_search($string);
      $items = $response_disc->results;
      $matches[] = ['label' => '---Discogs---'];
      foreach ($items as $item) {
        $matches[] = ['label' => $item->title];
      }
    }
    return new JsonResponse($matches);
  }
  public function autocompleteAlbum(request $request): JsonResponse {
    $discogs = $this->discogsSearchService;
    $spotify = $this->spotifySearchService;
    $matches = array();
    $string = $request->query->get('q');
    if ($string) {
      $matches = array();
      $response = $spotify->spotify_album_search($string);
      $items = $response->albums->items;
      $matches[] = ['label' => '---Spotify---'];
      foreach ($items as $item) {
        $matches[] = ['name' => $item->name, 'label' => $item->name];
      }
      $response_disc = $discogs->discogs_album_search($string);
      $items = $response_disc->results;
      $matches[] = ['label' => '---Discogs---'];
      foreach ($items as $item) {
        $matches[] = ['label' => $item->title];
      }
    }
    return new JsonResponse($matches);
  }
  public function autocompleteTrack(request $request): JsonResponse {
    $spotify = $this->spotifySearchService;
    $discogs = $this->discogsSearchService;
    $matches = array();
    $string = $request->query->get('q');
    if ($string) {
      $matches = array();
      $response = $spotify->spotify_track_search($string);
      $items = $response->tracks->items;
      $matches[] = ['label' => '---Spotify---'];
      foreach ($items as $item) {
        $matches[] = ['name' => $item->name, 'label' => $item->name];
      }
      /* Discogs styður ekki leit af lögum - commented þar til ég finn lausn.
      $response_disc = $discogs->discogs_track_search($string);
      $items = $response_disc->results;
      $matches[] = ['label' => '---Discogs---'];
      foreach ($items as $item) {
        $matches[] = ['label' => $item->title];
      }
      */
    }
    return new JsonResponse($matches);
  }
}
