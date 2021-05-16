<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class DiscogsConfigForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['music_search.discogs'];
  }

  public function getFormId() {
    return 'discogs_config_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Discogs consumer key'),
      '#description' => $this->t('Enter your discogs consumer key.'),
      '#required' => TRUE
    ];
    $form['secret_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Discogs secret key'),
      '#description' => $this->t('Enter your discogs secret key.'),
      '#required' => TRUE
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save')
    ];
    return $form;
  }
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('music_search.discogs')
      ->set('consumer_id', $form_state->getValue('client_id'))
      ->set('consumer_secret', $form_state->getValue('secret_id'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
