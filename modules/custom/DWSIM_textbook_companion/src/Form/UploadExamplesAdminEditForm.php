<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\UploadExamplesAdminEditForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\textbook_companion\Services\MailService;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Form\EnforcedResponseException;

class UploadExamplesAdminEditForm extends FormBase {

  protected $database;
  protected $currentUser;
  protected $entityTypeManager;
  protected $configFactory;
  protected $mailService;

  public function __construct(
    Connection $database,
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory,
    MailService $mail_service
  ) {
    $this->database = $database;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->mailService = $mail_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('textbook_companion.mail_service')
    );
  }

  public function getFormId() {
    return 'upload_examples_admin_edit_form';
  }

  /**
   * Resolve example_id from route, query, or path.
   */
  protected function resolveExampleId() {
    $example_id = \Drupal::routeMatch()->getParameter('example_id');
    if (!$example_id) {
      $example_id = \Drupal::request()->query->get('example_id');
    }
    if (!$example_id) {
      $parts = explode('/', trim(\Drupal::request()->getPathInfo(), '/'));
      $last = end($parts);
      if (is_numeric($last)) {
        $example_id = (int) $last;
      }
    }
    return $example_id;
  }

  /**
   * Load a file download link for an example file.
   */
  protected function fileDownloadLink($filename, $file_id) {
    return Link::fromTextAndUrl(
      $filename,
      Url::fromRoute('textbook_companion.download_example_file', ['example_file_id' => $file_id])
    )->toString();
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $example_id = $this->resolveExampleId();

    if (!$example_id) {
      $this->messenger()->addError($this->t('Invalid example selected.'));
      throw new EnforcedResponseException(new RedirectResponse(Url::fromRoute('<front>')->toString()));
    }

    $example_data = $this->database->select('textbook_companion_example', 'e')
      ->fields('e')->condition('id', $example_id)->range(0, 1)
      ->execute()->fetchObject();

    if (!$example_data) {
      $this->messenger()->addError($this->t('Invalid example selected.'));
      throw new EnforcedResponseException(new RedirectResponse(Url::fromRoute('<front>')->toString()));
    }

    // Load example files.
    $source_file = ''; $source_file_id = 0;
    $result1_file = ''; $result1_file_id = 0;
    $result2_file = ''; $result2_file_id = 0;
    $xcos1_file = ''; $xcos1_file_id = 0;
    $xcos2_file = ''; $xcos2_file_id = 0;

    $files_q = $this->database->select('textbook_companion_example_files', 'f')
      ->fields('f')->condition('example_id', $example_id)->execute();
    while ($f = $files_q->fetchObject()) {
      if ($f->filetype === 'S') {
        $source_file = $this->fileDownloadLink($f->filename, $f->id);
        $source_file_id = $f->id;
      }
      elseif ($f->filetype === 'R') {
        if (!$result1_file) { $result1_file = $this->fileDownloadLink($f->filename, $f->id); $result1_file_id = $f->id; }
        else { $result2_file = $this->fileDownloadLink($f->filename, $f->id); $result2_file_id = $f->id; }
      }
      elseif ($f->filetype === 'X') {
        if (!$xcos1_file) { $xcos1_file = $this->fileDownloadLink($f->filename, $f->id); $xcos1_file_id = $f->id; }
        else { $xcos2_file = $this->fileDownloadLink($f->filename, $f->id); $xcos2_file_id = $f->id; }
      }
    }

    $chapter_data = $this->database->select('textbook_companion_chapter', 'c')
      ->fields('c')->condition('id', $example_data->chapter_id)->execute()->fetchObject();
    if (!$chapter_data) {
      $this->messenger()->addError($this->t('Invalid chapter selected.'));
      throw new EnforcedResponseException(new RedirectResponse(Url::fromRoute('<front>')->toString()));
    }

    $preference_data = $this->database->select('textbook_companion_preference', 'p')
      ->fields('p')->condition('id', $chapter_data->preference_id)->execute()->fetchObject();
    if (!$preference_data) {
      $this->messenger()->addError($this->t('Invalid book selected.'));
      throw new EnforcedResponseException(new RedirectResponse(Url::fromRoute('<front>')->toString()));
    }

    $proposal_data = $this->database->select('textbook_companion_proposal', 'pr')
      ->fields('pr')->condition('id', $preference_data->proposal_id)->execute()->fetchObject();
    if (!$proposal_data) {
      $this->messenger()->addError($this->t('Invalid proposal selected.'));
      throw new EnforcedResponseException(new RedirectResponse(Url::fromRoute('<front>')->toString()));
    }

    $config = $this->configFactory->get('textbook_companion.settings');
    $source_extensions = $config->get('textbook_companion_source_extensions') ?? '';

    $form['#attributes'] = ['enctype' => 'multipart/form-data'];
    $form['book_details']['book'] = ['#type' => 'item', '#markup' => $preference_data->book, '#title' => $this->t('Title of the Book')];
    $form['contributor_name'] = ['#type' => 'item', '#markup' => $proposal_data->full_name, '#title' => $this->t('Contributor Name')];
    $form['number'] = ['#type' => 'item', '#title' => $this->t('Chapter No'), '#markup' => $chapter_data->number];
    $form['name'] = ['#type' => 'item', '#title' => $this->t('Title of the Chapter'), '#markup' => $chapter_data->name];
    $form['example_number'] = ['#type' => 'item', '#title' => $this->t('Example No'), '#markup' => $example_data->number];
    $form['example_caption'] = [
      '#type' => 'textfield', '#title' => $this->t('Caption'),
      '#size' => 40, '#maxlength' => 255, '#required' => TRUE,
      '#default_value' => $example_data->caption,
    ];
    $form['example_warning'] = [
      '#type' => 'item',
      '#title' => $this->t('You should upload all the files (main or source files, result files, executable file if any)'),
      '#prefix' => '<div style="color:red">', '#suffix' => '</div>',
    ];
    $form['sourcefile'] = ['#type' => 'fieldset', '#title' => $this->t('Main or Source Files'), '#collapsible' => FALSE, '#collapsed' => FALSE];
    if ($source_file) {
      $form['sourcefile']['cur_source'] = ['#type' => 'item', '#title' => $this->t('Existing Main or Source File'), '#markup' => $source_file];
      $form['sourcefile']['cur_source_checkbox'] = ['#type' => 'checkbox', '#title' => $this->t('Delete Existing Main or Source File'), '#description' => 'Check to delete the existing Main or Source file.'];
      $form['sourcefile']['sourcefile1'] = [
        '#type' => 'file', '#title' => $this->t('Upload New Main or Source File'), '#size' => 48,
        '#description' => $this->t('Upload new Main or Source file above if you want to replace the existing file. Leave blank to keep existing.<br />') . $this->t('Allowed file extensions: ') . $source_extensions,
      ];
      $form['sourcefile']['cur_source_file_id'] = ['#type' => 'hidden', '#value' => $source_file_id];
    }
    else {
      $form['sourcefile']['sourcefile1'] = [
        '#type' => 'file', '#title' => $this->t('Upload New Main or Source File'), '#size' => 48,
        '#description' => $this->t('Allowed file extensions: ') . $source_extensions,
      ];
    }

    $form['example_id_hidden'] = ['#type' => 'hidden', '#value' => $example_id];
    $form['submit'] = ['#type' => 'submit', '#value' => $this->t('Submit')];
    $form['cancel'] = [
      '#type' => 'link', '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('textbook_companion.bulk_approval_form'),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (function_exists('check_name')) {
      if (!check_name($form_state->getValue('example_caption'))) {
        $form_state->setErrorByName('example_caption', $this->t('Example Caption can contain only alphabets, numbers and spaces.'));
      }
    }
    $config = $this->configFactory->get('textbook_companion.settings');
    if (isset($_FILES['files'])) {
      foreach ($_FILES['files']['name'] as $field_name => $file_name) {
        if (!$file_name) continue;
        $file_type = strstr($field_name, 'source') ? 'S' : (strstr($field_name, 'result') ? 'R' : (strstr($field_name, 'xcos') ? 'X' : 'U'));
        $ext_map = ['S' => 'textbook_companion_source_extensions', 'R' => 'textbook_companion_result_extensions', 'X' => 'textbook_companion_xcos_extensions'];
        $allowed_str = isset($ext_map[$file_type]) ? ($config->get($ext_map[$file_type]) ?? '') : '';
        $allowed = explode(',', $allowed_str);
        $parts = explode('.', strtolower($file_name));
        if (!in_array(end($parts), $allowed)) {
          $form_state->setErrorByName($field_name, $this->t('Only files with @ext extensions can be uploaded.', ['@ext' => $allowed_str]));
        }
        if ($_FILES['files']['size'][$field_name] <= 0) {
          $form_state->setErrorByName($field_name, $this->t('File size cannot be zero.'));
        }
        if (function_exists('textbook_companion_check_valid_filename') && !textbook_companion_check_valid_filename($file_name)) {
          $form_state->setErrorByName($field_name, $this->t('Invalid file name. Only alphabets, numbers and underscore are allowed.'));
        }
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $example_id = $form_state->getValue('example_id_hidden');

    $example_data = $this->database->select('textbook_companion_example', 'e')
      ->fields('e')->condition('id', $example_id)->range(0, 1)->execute()->fetchObject();
    if (!$example_data) { $this->messenger()->addError($this->t('Invalid example selected.')); return; }

    $chapter_data = $this->database->select('textbook_companion_chapter', 'c')
      ->fields('c')->condition('id', $example_data->chapter_id)->execute()->fetchObject();
    if (!$chapter_data) { $this->messenger()->addError($this->t('Invalid chapter selected.')); return; }

    $preference_data = $this->database->select('textbook_companion_preference', 'p')
      ->fields('p')->condition('id', $chapter_data->preference_id)->execute()->fetchObject();
    if (!$preference_data) { $this->messenger()->addError($this->t('Invalid book selected.')); return; }

    $proposal_data = $this->database->select('textbook_companion_proposal', 'pr')
      ->fields('pr')->condition('id', $preference_data->proposal_id)->execute()->fetchObject();
    if (!$proposal_data) { $this->messenger()->addError($this->t('Invalid proposal selected.')); return; }

    $user_data = $this->entityTypeManager->getStorage('user')->load($proposal_data->uid);

    $root_path = function_exists('textbook_companion_path') ? textbook_companion_path() : '';
    $dest_path = $preference_data->directory_name . '/';
    if ($root_path && !is_dir($root_path . $dest_path)) { mkdir($root_path . $dest_path); }
    $dest_path .= 'CH' . $chapter_data->number . '/';
    if ($root_path && !is_dir($root_path . $dest_path)) { mkdir($root_path . $dest_path); }
    $dest_path .= 'EX' . $example_data->number . '/';
    if ($root_path && !is_dir($root_path . $dest_path)) { mkdir($root_path . $dest_path); }
    $filepath = 'CH' . $chapter_data->number . '/EX' . $example_data->number . '/';

    $this->database->update('textbook_companion_example')
      ->fields(['caption' => $form_state->getValue('example_caption')])
      ->condition('id', $example_id)->execute();

    $cur_file_id = (int) $form_state->getValue('cur_source_file_id');
    if ($cur_file_id > 0) {
      $file_data = $this->database->select('textbook_companion_example_files', 'f')
        ->fields('f')->condition('id', $cur_file_id)->condition('example_id', $example_data->id)->execute()->fetchObject();
      if (!$file_data) { $this->messenger()->addError($this->t('Error deleting example source file. File not found in database.')); return; }
      if ($form_state->getValue('cur_source_checkbox') == 1 && empty($_FILES['files']['name']['sourcefile1'])) {
        if (function_exists('delete_file') && !delete_file($cur_file_id)) {
          $this->messenger()->addError($this->t('Error deleting example source file.')); return;
        }
      }
    }

    if (!empty($_FILES['files']['name']['sourcefile1'])) {
      if ($cur_file_id > 0 && function_exists('delete_file') && !delete_file($cur_file_id)) {
        $this->messenger()->addError($this->t('Error removing previous example source file.')); return;
      }
      $upload_filename = $_FILES['files']['name']['sourcefile1'];
      if ($root_path && file_exists($root_path . $dest_path . $upload_filename)) {
        $this->messenger()->addError($this->t('Error: file @file already exists.', ['@file' => $upload_filename])); return;
      }
      if (!$root_path || move_uploaded_file($_FILES['files']['tmp_name']['sourcefile1'], $root_path . $dest_path . $upload_filename)) {
        $this->database->insert('textbook_companion_example_files')->fields([
          'example_id' => $example_data->id,
          'filename'   => $upload_filename,
          'filepath'   => $filepath . $upload_filename,
          'filemime'   => 'application/dwxml',
          'filesize'   => $_FILES['files']['size']['sourcefile1'],
          'filetype'   => 'S',
          'timestamp'  => time(),
        ])->execute();
        $this->messenger()->addStatus($this->t('@file uploaded successfully.', ['@file' => $upload_filename]));
      }
      else {
        $this->messenger()->addError($this->t('Error uploading file: @path', ['@path' => $dest_path . $upload_filename]));
      }
    }

    if ($user_data) {
      $params['example_updated_admin']['example_id'] = $example_id;
      $params['example_updated_admin']['user_id'] = $proposal_data->uid;
      if (!$this->mailService->sendMail('textbook_companion', 'example_updated_admin', $user_data->getEmail(), $params)) {
        $this->messenger()->addError($this->t('Error sending email message.'));
      }
    }

    $this->messenger()->addStatus($this->t('Example successfully updated.'));
    $form_state->setRedirect('textbook_companion.bulk_approval_form');
  }

}
