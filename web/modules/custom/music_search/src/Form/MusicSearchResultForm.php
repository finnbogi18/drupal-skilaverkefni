<?php

namespace Drupal\music_search\Form;



use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\migrate_drupal\Plugin\migrate\source\ContentEntity;
use Drupal\music_search\DiscogsSearchService;
use Drupal\music_search\SpotifySearchService;
use Drupal\views\Plugin\views\area\Entity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\Node;

class MusicSearchResultForm extends FormBase
{
  protected PrivateTempStoreFactory $tempStoreFactory;
  protected SpotifySearchService $spotifySearchService;
  protected DiscogsSearchService $discogsSearchService;

  /**
   * MusicSearchResultForm constructor.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   * @param \Drupal\music_search\SpotifySearchService $spotifySearchService
   */
  public function __construct(
    PrivateTempStoreFactory $tempStoreFactory,
    SpotifySearchService $spotifySearchService,
    DiscogsSearchService $discogsSearchService
  ){
    $this->tempStoreFactory = $tempStoreFactory;
    $this->spotifySearchService = $spotifySearchService;
    $this->discogsSearchService = $discogsSearchService;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\music_search\Form\MusicSearchResultForm|static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('music_search.spotify'),
      $container->get('music_search.discogs')
    );
  }

  /**
   * Provides the form with a unique ID.
   *
   * @return string
   */
  public function getFormId(): string {
    return 'music_search_result_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $tempstore = $this->tempStoreFactory->get('search_collection');
    $spotify = $this->spotifySearchService;
    $discogs = $this->discogsSearchService;
    $search = $tempstore->get('search');
    $search_type = $tempstore->get('search_type');
    $search_items_spotify = array();
    $search_headers_spotify = array();
    $search_items_discogs = array();
    $search_headers_discogs = array();


    if ($search_type == 'artist') {
      $result = $spotify->spotify_artist_search($search);
      $items = $result->artists->items;
      $search_headers_spotify = [
        'title' => $this->t('Nafn'),
        'field_listamadur_url' => $this->t('Website URL'),
        'field_spotify_id' => $this->t('Spotify ID')
      ];
      foreach ($items as $item) {
        $search_items_spotify[] = [
          'title' => $item->name,
          'field_listamadur_url' => $item->external_urls->spotify,
          'field_spotify_id' => $item->id,
        ];
      }
      $search_headers_discogs = [
        'title' => $this->t('Nafn'),
        'field_listamadur_url' => $this->t('Website URL'),
        'field_discogs_id' => $this->t('Discogs ID')
      ];
      $result = $discogs->discogs_artist_search($search);
      $items = $result->results;
      foreach ($items as $item) {
        $search_items_discogs[] = [
          'title' => $item->title,
          'field_listamadur_url' =>$item->resource_url,
          'field_discogs_id' =>$item->id
        ];
      }
    }


    elseif ($search_type == 'album') {
      $result = $spotify->spotify_album_search($search);
      $items = $result->albums->items;
      $search_headers_spotify = [
        'title' => $this->t('Nafn'),
        'field_album_url' => $this->t('Website URL'),
        'field_spotify_id' => $this->t('Spotify ID'),
        'field_flytjandi' => $this->t('Listamaður'),
        'field_ugafuar' =>$this->t('Útgáfudagur')
      ];
      foreach ($items as $item) {
        $search_items_spotify[] = [
          'title' => $item->name,
          'field_album_url' => $item->external_urls->spotify,
          'field_spotify_id' => $item->id,
          'field_flytjandi' => $item->artists[0]->name,
          'field_ugafuar' => $item->release_date
        ];
      }
      $result = $discogs->discogs_album_search($search);
      $items = $result->results;
      $search_headers_discogs = [
        'title' => $this->t('Nafn'),
        'field_album_url' => $this->t('Website URL'),
        'field_discogs_id' => $this->t('Discogs ID'),
        'field_flytjandi' => $this->t('Listamaður'),
        'field_ugafuar' =>$this->t('Útgáfudagur')
      ];
      foreach ($items as $item) {
        $master = $discogs->discogs_master_search_id($item->id);
        $search_items_discogs[] = [
          'title' => $master->title,
          'field_album_url' => $master->resource_url,
          'field_discogs_id' => $master->id,
          'field_flytjandi' => $master->artists[0]->name,
          'field_ugafuar' => $master->year
        ];
      }
    }
    elseif ($search_type == 'track') {
      $result = $spotify->spotify_track_search($search);
      $items = $result->tracks->items;
      $search_headers_spotify = [
        'title' => $this->t('Nafn'),
        'artist' => $this->t('Listamaður'),
        'field_lengd' => $this->t('Lengd'),
        'field_spotify_id' => $this->t('Spotify ID')
      ];
      foreach ($items as $item) {
        $search_items_spotify[] = [
          'title' => $item->name,
          'field_lengd' => $item->duration_ms/1000,
          'field_spotify_id' => $item->id,
          'artist' => $item->artists[0]->name
        ];
      }

      $search_headers_discogs = [
        'title' => $this->t('Nafn'),
        'artist' => $this->t('Listamaður'),
        'field_lengd' => $this->t('Lengd'),
        'field_discogs_id' => $this->t('Discogs ID')
      ];
      /* Commented út þar sem Discogs styður lög takmarkað.
      $result = $discogs->discogs_track_search($search);
      $items = $result->results;
      foreach ($items as $item) {
        $search_items_discogs[] = [
          'title' => $item->title,
          'field_listamadur_url' =>$item->artist,
          'field_lengd' =>$item->duration,
          'field_discogs_id' => $item->id
        ];
      }
      */
    }
    $form['table_spotify'] = [
      '#type' => 'tableselect',
      '#header' => $search_headers_spotify,
      '#options' => $search_items_spotify,
      '#empty' => $this->t('No results.')
    ];
    $form['table_discogs'] = [
      '#type' =>'tableselect',
      '#header' => $search_headers_discogs,
      '#options' => $search_items_discogs,
      '#empty' => $this->t('No results.')
    ];

    $form ['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next')
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $tempstore = $this->tempStoreFactory->get('search_collection');
    $options = $form_state->getValues();
    $check_spotify = $options['table_spotify'];
    $check_discogs = $options['table_discogs'];
    $options_spotify = $form['table_spotify']['#options'];
    $options_discogs = $form['table_discogs']['#options'];
    if ($check_spotify){
      foreach ($check_spotify as $check) {
        if ($check !== 0) {
          $result = $options_spotify[(int)$check];
          $id = $result['field_spotify_id'];
          $tempstore->set('spotify_id', $id);
        }
      }
    }
    if ($check_discogs){
      foreach ($check_discogs as $check) {
        if ($check !== 0) {
          $result = $options_discogs[(int) $check];
          $id = $result['field_discogs_id'];
          $tempstore->set('discogs_id', $id);
        }
      }
    }
    $form_state->setRedirect('music_search.search_form_edit');
  }


  /*public function submitForm(array &$form, FormStateInterface $form_state) {
    $spotify = $this->spotifySearchService;
    $options = $form_state->getValues('table_spotify');
    $check_options = $options['table'];
    $check_items = [];
    $option_values = $form['table']['#options'];
    $tempstore = $this->tempStoreFactory->get('search_collection');
    $search_type = $tempstore->get('search_type');
    foreach ($check_options as $option) {
      if ($option !== 0) {
        $result = $option_values[(int) $option];
        if ($search_type == 'track') {
          $newnode = Node::create([
            'type' => 'lag',
            'title' => $result['title'],
            'field_lengd' => $result['field_lengd'],
            'field_spotify_id' => $result['field_spotify_id']
          ]);
        }
        if ($search_type == 'artist') {
          $newnode = Node::create([
            'type' => 'listamadur',
            'title' => $result['title'],
            'field_heimasida' => $result['field_listamadur_url']
          ]);
        }
        if ($search_type == 'album') {
          $album = $spotify->spotify_album_search_id($result['field_spotify_id']);
          $track_list = $album->tracks->items;
          $track_array = array();
          foreach ($track_list as $track) {
            $new_track = Node::create([
              'type' => 'lag',
              'title' => $this->t($track->name),
              'field_lengd' => $track->duration_ms/1000,
              'field_spotify_id' => $track->id
            ]);
            $new_track->save();
            $track_array [] = [$new_track->id()];
          }

          $query = \Drupal::entityQuery('node');
          $artist = $query->condition('type', 'listamadur')
            ->condition('title', $album->artists[0]->name)
            ->execute();

          $artist_nid = '';

          if ($artist) {
            $artist_nid = reset($artist);
          }

          $new_album = Node::create([
            'type' => 'plata',
            'title' => $album->name,
            'field_ugafuar' => $album->release_date,
          ]);

          foreach ($track_array as $track_id) {
            $new_album->get('field_lag')->appendItem($track_id[0]);
          }

          $new_album->get('field_flytjandi')->appendItem($artist_nid);
          $new_album->save();
        }
        //$newnode->save();

        $check_items[] = [$result];
      }
      // TODO: Implement submitForm() method.
    }
  }*/
}
