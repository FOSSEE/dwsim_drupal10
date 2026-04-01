<?php

/**
 * @file
 * Contains \Drupal\dwsim_flowsheet\Form\DwsimFlowsheetSettingsForm.
 */

namespace Drupal\dwsim_flowsheet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class DwsimFlowsheetSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dwsim_flowsheet_settings_form';
  }


  //   public function submitForm(array &$form, FormStateInterface $form_state) {
  //     // $fieldsets = $form_state->getValue('fieldsets');
  //     // foreach ($fieldsets as $index => $fieldset) {
  //     //   $this->messenger()->addMessage($this->t('Fieldset @num: Textfield 1 = @field1, Textfield 2 = @field2', [
  //     //     '@num' => $index + 1,
  //     //     '@field1' => $fieldset['field1'],
  //     //     '@field2' => $fieldset['field2'],
  //     //   ]));
  //     // }
  //   }
  // }
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form['emails'] = [
      '#type' => 'textfield',
      '#title' => t('(Bcc) Notification emails'),
      '#description' => t('Specify emails id for Bcc option of mail system with comma separated'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => \Drupal::config('dwsim_flowsheet.settings')->get('dwsim_flowsheet_emails'),
    ];
    $form['cc_emails'] = [
      '#type' => 'textfield',
      '#title' => t('(Cc) Notification emails'),
      '#description' => t('Specify emails id for Cc option of mail system with comma separated'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => \Drupal::config('dwsim_flowsheet.settings')->get('dwsim_flowsheet_cc_emails'),
    ];
    $form['from_email'] = [
      '#type' => 'textfield',
      '#title' => t('Outgoing from email address'),
      '#description' => t('Email address to be display in the from field of all outgoing messages'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => \Drupal::config('dwsim_flowsheet.settings')->get('dwsim_flowsheet_from_email'),
    ];
    $form['extensions']['user_defind_compound_source'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed source file extensions for user defind compound'),
      '#description' => t('A comma separated list WITHOUT SPACE of source file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => \Drupal::config('dwsim_flowsheet.settings')->get('dwsim_flowsheet_user_defind_compound_source_extensions'),
    ];
    $form['extensions']['dependency'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed dependency file extensions'),
      '#description' => t('A comma separated list WITHOUT SPACE of dependency file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => \Drupal::config('dwsim_flowsheet.settings')->get('dwsim_flowsheet_dependency_extensions'),
    ];
    $form['extensions']['result'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed result file extensions'),
      '#description' => t('A comma separated list WITHOUT SPACE of result file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => \Drupal::config('dwsim_flowsheet.settings')->get('dwsim_flowsheet_result_extensions'),
    ];
    $form['extensions']['abstract_upload'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed abstract file extensions'),
      '#description' => t('A comma separated list WITHOUT SPACE of pdf file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => \Drupal::config('dwsim_flowsheet.settings')->get('dwsim_flowsheet_abstract_upload_extensions'),
    ];
    $form['extensions']['flowsheet_upload'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed DWSIM flowsheet for the developed process'),
      '#description' => t('A comma separated list WITHOUT SPACE of pdf file extensions that are permitted to be uploaded on the server'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => \Drupal::config('dwsim_flowsheet.settings')->get('dwsim_flowsheet_flowsheet_developed_process_source_extensions'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    return;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('dwsim_flowsheet.settings')->set('dwsim_flowsheet_emails', $form_state->getValue(['emails']))->save();
    \Drupal::configFactory()->getEditable('dwsim_flowsheet.settings')->set('dwsim_flowsheet_cc_emails', $form_state->getValue(['cc_emails']))->save();
    \Drupal::configFactory()->getEditable('dwsim_flowsheet.settings')->set('dwsim_flowsheet_from_email', $form_state->getValue(['from_email']))->save();
    \Drupal::configFactory()->getEditable('dwsim_flowsheet.settings')->set('dwsim_flowsheet_user_defind_compound_source_extensions', $form_state->getValue(['user_defind_compound_source']))->save();
    \Drupal::configFactory()->getEditable('dwsim_flowsheet.settings')->set('dwsim_flowsheet_dependency_extensions', $form_state->getValue(['dependency']))->save();
    \Drupal::configFactory()->getEditable('dwsim_flowsheet.settings')->set('dwsim_flowsheet_result_extensions', $form_state->getValue(['result']))->save();
    \Drupal::configFactory()->getEditable('dwsim_flowsheet.settings')->set('dwsim_flowsheet_abstract_upload_extensions', $form_state->getValue(['abstract_upload']))->save();
    \Drupal::configFactory()->getEditable('dwsim_flowsheet.settings')->set('dwsim_flowsheet_flowsheet_developed_process_source_extensions', $form_state->getValue(['flowsheet_upload']))->save();
    \Drupal::messenger()->addStatus(t('Settings updated'));
  }

}
?>
