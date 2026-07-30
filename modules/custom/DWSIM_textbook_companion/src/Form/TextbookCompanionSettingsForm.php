<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\TextbookCompanionSettingsForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class TextbookCompanionSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['textbook_companion.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'textbook_companion_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('textbook_companion.settings');

    $form['bcc_emails'] = [
      '#type' => 'textfield',
      '#title' => $this->t('(Bcc) Notification emails'),
      '#description' => $this->t('Specify emails id for Bcc option of mail system with comma separated'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('textbook_companion_bcc_emails') ?? \Drupal::state()->get('textbook_companion_bcc_emails', ''),
    ];
    $form['cc_emails'] = [
      '#type' => 'textfield',
      '#title' => $this->t('(Cc) Notification emails'),
      '#description' => $this->t('Specify emails id for Cc option of mail system with comma separated'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('textbook_companion_cc_emails') ?? \Drupal::state()->get('textbook_companion_cc_emails', ''),
    ];
    $form['from_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Outgoing from email address'),
      '#description' => $this->t('Email address to be display in the from field of all outgoing messages'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('textbook_companion_from_email') ?? \Drupal::state()->get('textbook_companion_from_email', ''),
    ];
    $form['extensions']['source'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed source file extensions'),
      '#description' => $this->t('A comma separated list WITHOUT SPACE of source file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $config->get('textbook_companion_source_extensions') ?? \Drupal::state()->get('textbook_companion_source_extensions', ''),
    ];
    $options = [
      '1' => $this->t('1'),
      '2' => $this->t('2'),
      '3' => $this->t('3'),
    ];
    $form['book_preference_options'] = [
      '#type' => 'radios',
      '#title' => $this->t('Book Preferences'),
      '#options' => $options,
      '#required' => TRUE,
      '#description' => $this->t('Set number book preference to be allowed'),
      '#default_value' => $config->get('textbook_companion_book_preferences') ?? \Drupal::state()->get('textbook_companion_book_preferences', ''),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $bcc_emails = $form_state->getValue('bcc_emails');
    $cc_emails = $form_state->getValue('cc_emails');
    $from_email = $form_state->getValue('from_email');
    $source_extensions = $form_state->getValue('source');
    $book_preferences = $form_state->getValue('book_preference_options');

    // Save to Configuration.
    $this->config('textbook_companion.settings')
      ->set('textbook_companion_bcc_emails', $bcc_emails)
      ->set('textbook_companion_emails', $bcc_emails)
      ->set('textbook_companion_cc_emails', $cc_emails)
      ->set('textbook_companion_from_email', $from_email)
      ->set('textbook_companion_source_extensions', $source_extensions)
      ->set('textbook_companion_book_preferences', $book_preferences)
      ->save();

    // Synchronize to State API for backward compatibility.
    \Drupal::state()->set('textbook_companion_bcc_emails', $bcc_emails);
    \Drupal::state()->set('textbook_companion_emails', $bcc_emails);
    \Drupal::state()->set('textbook_companion_cc_emails', $cc_emails);
    \Drupal::state()->set('textbook_companion_from_email', $from_email);
    \Drupal::state()->set('textbook_companion_source_extensions', $source_extensions);
    \Drupal::state()->set('textbook_companion_book_preferences', $book_preferences);

    $this->messenger()->addMessage($this->t('Settings updated'), 'status');
    parent::submitForm($form, $form_state);
  }

}
