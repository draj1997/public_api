<?php

/**
 * @file
 * Contains \Drupal\public_info\Form\PublicInfoSettingsForm.
 */

namespace Drupal\public_info\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Public Info settings for this site.
 */
class PublicInfoSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'public_info.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'public_info_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('public_info.settings');

    $form['cache_ttl'] = [
      '#type' => 'number',
      '#title' => $this->t('Cache TTL (minutes)'),
      '#default_value' => $config->get('cache_ttl') ?? 15,
      '#min' => 1,
      '#required' => TRUE,
    ];
    
    $form['launch_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of launches to display'),
      '#default_value' => $config->get('launch_limit') ?? 5,
      '#min' => 1,
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('public_info.settings')
      ->set('cache_ttl', $form_state->getValue('cache_ttl'))
      ->set('launch_limit', $form_state->getValue('launch_limit'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
