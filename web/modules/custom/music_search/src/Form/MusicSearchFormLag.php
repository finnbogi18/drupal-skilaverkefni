<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\music_search\SpotifySearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MusicSearchFormLag extends FormBase
{
  protected PrivateTempStoreFactory $tempStoreFactory;

  /**
   * MusicSearchFormLag constructor.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   */
  public function __construct(
    PrivateTempStoreFactory $tempStoreFactory
  ) {
    $this->tempStoreFactory = $tempStoreFactory;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\music_search\Form\MusicSearchFormLag|static
   */

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private')
    );
  }

  /**
   * @return string
   */
  public function getFormId(): string {
    return 'music_search_form';
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['track'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Lag'),
      '#autocomplete_route_name'=> 'music_search.autocompleteTrack',
      '#required' => TRUE
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search')
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $track = $form_state->getValue('track');
    $search_type = 'track';
    $tempstore = $this->tempStoreFactory->get('search_collection');
    $tempstore->set('search', $track);
    $tempstore->set('search_type', $search_type);
    $form_state->setRedirect('music_search.search_result_form');
  }
}
