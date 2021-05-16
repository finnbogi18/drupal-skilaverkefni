<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SpotifyConfigForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['music_search.spotify'];
  }

  public function getFormId() {
    return 'spotify_config_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spotify Client ID'),
      '#description' => $this->t('Enter your Spotify client ID.'),
      '#required' => TRUE
    ];
    $form['secret_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spotify secret ID'),
      '#description' => $this->t('Enter your Spotify secret ID.'),
      '#required' => TRUE
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save')
    ];
    return $form;
  }
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('music_search.spotify')
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('secret_id', $form_state->getValue('secret_id'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
