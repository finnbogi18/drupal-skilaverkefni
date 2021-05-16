<?php

namespace Drupal\music_search\Form;



use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\music_search\DiscogsSearchService;
use Drupal\music_search\SpotifySearchService;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MusicSearchFormEdit extends FormBase {
  protected PrivateTempStoreFactory $tempStoreFactory;
  protected SpotifySearchService $spotifySearchService;
  protected DiscogsSearchService $discogsSearchService;

  public function __construct(
    PrivateTempStoreFactory $tempStoreFactory,
    SpotifySearchService $spotifySearchService,
    DiscogsSearchService $discogsSearchService
  ){
    $this->tempStoreFactory = $tempStoreFactory;
    $this->spotifySearchService = $spotifySearchService;
    $this->discogsSearchService = $discogsSearchService;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('music_search.spotify'),
      $container->get('music_search.discogs')
    );
  }

  public function getFormId() {
    return 'music_search_form_edit';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $tempstore = $this->tempStoreFactory->get('search_collection');
    $spotify = $this->spotifySearchService;
    $discogs = $this->discogsSearchService;
    $search_type = $tempstore->get('search_type');
    $spotify_id = $tempstore->get('spotify_id');
    $discogs_id = $tempstore->get('discogs_id');
    $header = [
      'title' => $this->t('Title'),
      'item' => $this->t('Item')
    ];

    if ($search_type == 'artist'){
      $spotify_result = $spotify->spotify_artist_search_id($spotify_id);
      $discogs_result = $discogs->discogs_artist_search_id($discogs_id);

      $options = [
        0 => ['title' => 'Nafn (Spotify)', 'item' => $spotify_result->name, 'key' => 'title'],
        1 => ['title' => 'Nafn (Discogs)', 'item' => $discogs_result->name, 'key' => 'title'],
        2 => ['title' => 'Mynd (Spotify)', 'item' => $spotify_result->images[0]->url, 'key' => 'mynd'],
        3 => ['title' => 'Mynd (Discogs)', 'item' => $discogs_result->images[0]->resource_url, 'key' => 'mynd'],
        4 => ['title' => 'Lýsing (Discogs)', 'item' => $discogs_result->profile, 'key' => 'lysing']
      ];
    } elseif ($search_type == 'track') {
      $spotify_result = $spotify->spotify_track_search_id($spotify_id);
      $options = [
        0 => ['title' => 'Nafn (Spotify)', 'item' => $spotify_result->name, 'key' => 'title'],
        1 => ['title' => 'Lengd (Spotify)', 'item' => (int)$spotify_result->duration_ms/1000, 'key' => 'lengd'],
        2 => ['title' => 'Spotify ID (Spotify)', 'item' => $spotify_result->id, 'key' => 'id']
      ];
    } elseif ($search_type == 'album') {
      $spotify_result = $spotify->spotify_album_search_id($spotify_id);
      $discogs_result = $discogs->discogs_master_search_id($discogs_id);
      $options = [
        0 => ['title' => 'Nafn (Spotify)', 'item' => $spotify_result->name, 'key' => 'title'],
        1 => ['title' => 'Nafn (Discogs)', 'item' => $discogs_result->title, 'key' => 'title'],
        2 => ['title' => 'Flytjandi (Spotify)', 'item' =>$spotify_result->artists[0]->name, 'key' => 'flytjandi'],
        3 => ['title' => 'Flytjandi (Discogs)', 'item' => $discogs_result->artists[0]->name, 'key' => 'flytjandi'],
        4 => ['title' => 'Mynd (Spotify)', 'item' => $spotify_result->images[0]->url, 'key' => 'mynd'],
        5 => ['title' => 'Mynd (Discogs)', 'item' => $discogs_result->images[0]->resource_url, 'key' => 'mynd'],
        6 => ['title' => 'Útgáfuár (Spotify)', 'item' => $spotify_result->release_date, 'key' => 'release'],
        7 => ['title' => 'Útgáfuár (Discogs)', 'item' => $discogs_result->year, 'key' => 'release']
      ];
    }



    $form['table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options
    ];

    $form ['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save')
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $tempstorage = $this->tempStoreFactory;
    $tempstorage = $tempstorage->get('search_collection');
    $search_type = $tempstorage->get('search_type');
    $table = $form_state->getValues();
    $options = $table['table'];
    $option_values = $form['table']['#options'];
    if ($search_type == 'artist') {
      foreach ($options as $option) {
        if ($option !== 0) {
          $test = $option_values[(int) $option];
          if ($test['key'] == 'title') {
            $result_title = $test['item'];
          }
          if ($test['key'] == 'mynd') {
            $result_mynd = $test['item'];
          }
          if ($test['key'] == 'lysing') {
            $result_lysing = $test['item'];
          }
        }
      }
      $new_artist = Node::create([
        'type' => 'listamadur',
        'title' => $result_title,
        'field_lysing' => $result_lysing
      ]);
      $new_artist->save();
      $this->messenger()->addStatus($this->t('Saved!'));

    } elseif($search_type == 'track') {
      foreach ($options as $option) {
        if ($option !== 0) {
          $test = $option_values[(int) $option];
          if ($test['key'] == 'title') {
            $result_title = $test['item'];
          }
          if ($test['key'] == 'lengd') {
            $result_lengd = $test['item'];
          }
          if ($test['key'] == 'id') {
            $result_id = $test['item'];
          }
        }
      }
      $new_track = Node::create([
        'type' => 'lag',
        'title' => $result_title,
        'field_lengd' => $result_lengd,
        'field_spotify_id' => $result_id
      ]);
      $new_track->save();
      $this->messenger()->addStatus($this->t('Saved!'));

    } elseif($search_type == 'album') {
      foreach ($options as $option) {
        if ($option !== 0) {
          $test = $option_values[(int) $option];
          if ($test['key'] == 'title') {
            $result_title = $test['item'];
          }
          if ($test['key'] == 'mynd') {
            $result_mynd = $test['item'];
          }
          if ($test['key'] == 'flytjandi') {
            $result_flytjandi = $test['item'];
          }
          if ($test['key'] == 'release') {
            $result_release = $test['item'];
          }
        }
      }
      $query = \Drupal::entityQuery('node');
      $artist = $query->condition('type', 'listamadur')
        ->condition('title', $result_flytjandi)
        ->execute();
      $artist_nid = '';

      if ($artist) {
        $artist_nid = reset($artist);
      }

      $new_album = Node::create([
        'type' => 'plata',
        'title' => $result_title,
        'field_ugafuar' => $result_release,
        'field_flytjandi' => $artist_nid
      ]);
      $new_album->save();
      $this->messenger()->addStatus($this->t('Saved!'));
    }
  }
}
