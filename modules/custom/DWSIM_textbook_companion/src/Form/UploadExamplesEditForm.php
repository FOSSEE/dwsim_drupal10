<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\UploadExamplesEditForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\textbook_companion\Services\MailService;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Form\EnforcedResponseException;

class UploadExamplesEditForm extends FormBase {

  protected $database;
  protected $currentUser;
  protected $configFactory;
  protected $mailService;

  public function __construct(
    Connection $database,
    AccountProxyInterface $current_user,
    ConfigFactoryInterface $config_factory,
    MailService $mail_service
  ) {
    $this->database = $database;
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
    $this->mailService = $mail_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('textbook_companion.mail_service')
    );
  }

  public function getFormId() {
    return 'upload_examples_edit_form';
  }

  /**
   * Resolve example_id from route, query, or path.
   */
  protected function resolveExampleId() {
    $example_id = \Drupal::routeMatch()->getParameter('example_id');
    if (!$example_id) $example_id = \Drupal::request()->query->get('example_id');
    if (!$example_id) {
      $parts = explode('/', trim(\Drupal::request()->getPathInfo(), '/'));
      $last = end($parts);
      if (is_numeric($last)) $example_id = (int) $last;
    }
    return $example_id;
  }

  protected function redirectFront(FormStateInterface $form_state = NULL) {
    $url = Url::fromRoute('textbook_companion.list_chapters')->toString();
    throw new EnforcedResponseException(new RedirectResponse($url));
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $uid = $this->currentUser->id();
    $example_id = $this->resolveExampleId();

    if (!$example_id) {
      $this->messenger()->addError($this->t('Invalid example selected.'));
      $this->redirectFront();
    }

    $example_data = $this->database->select('textbook_companion_example', 'e')
      ->fields('e')->condition('id', $example_id)->range(0, 1)->execute()->fetchObject();
    if (!$example_data) {
      $this->messenger()->addError($this->t('Invalid example selected.'));
      $this->redirectFront();
    }
    if ($example_data->approval_status != 0) {
      $this->messenger()->addError($this->t('You cannot edit an example after it has been approved or dis-approved.'));
      $this->redirectFront();
    }

    $source_file = ''; $source_file_id = 0;
    $files_q = $this->database->select('textbook_companion_example_files', 'f')
      ->fields('f')->condition('example_id', $example_id)->execute();
    while ($f = $files_q->fetchObject()) {
      if ($f->filetype === 'S') {
        $source_file = Link::fromTextAndUrl(
          $f->filename,
          Url::fromRoute('textbook_companion.download_example_file', ['example_file_id' => $f->id])
        )->toString();
        $source_file_id = $f->id;
      }
    }

    $chapter_data = $this->database->select('textbook_companion_chapter', 'c')
      ->fields('c')->condition('id', $example_data->chapter_id)->execute()->fetchObject();
    if (!$chapter_data) { $this->messenger()->addError($this->t('Invalid chapter selected.')); $this->redirectFront(); }

    $preference_data = $this->database->select('textbook_companion_preference', 'p')
      ->fields('p')->condition('id', $chapter_data->preference_id)->execute()->fetchObject();
    if (!$preference_data) { $this->messenger()->addError($this->t('Invalid book selected.')); $this->redirectFront(); }
    if ($preference_data->approval_status != 1) {
      $this->messenger()->addError($this->t('Cannot edit example. Book proposal has not been approved or was rejected.'));
      $this->redirectFront();
    }

    $proposal_data = $this->database->select('textbook_companion_proposal', 'pr')
      ->fields('pr')->condition('id', $preference_data->proposal_id)->execute()->fetchObject();
    if (!$proposal_data) { $this->messenger()->addError($this->t('Invalid proposal selected.')); $this->redirectFront(); }
    if ($proposal_data->uid != $uid) {
      $this->messenger()->addError($this->t('You do not have permissions to edit this example.'));
      $this->redirectFront();
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
        '#description' => $this->t('Upload new Main or Source file if you want to replace the existing file.<br />') . $this->t('Allowed file extensions: ') . $source_extensions,
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
      '#url' => Url::fromRoute('textbook_companion.list_chapters'),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (function_exists('check_name') && !check_name($form_state->getValue('example_caption'))) {
      $form_state->setErrorByName('example_caption', $this->t('Example Caption can contain only alphabets, numbers and spaces.'));
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
    $uid = $this->currentUser->id();
    $example_id = $form_state->getValue('example_id_hidden');

    $example_data = $this->database->select('textbook_companion_example', 'e')
      ->fields('e')->condition('id', $example_id)->range(0, 1)->execute()->fetchObject();
    if (!$example_data || $example_data->approval_status != 0) {
      $this->messenger()->addError($this->t('Cannot edit example at this stage.'));
      return;
    }

    $chapter_data = $this->database->select('textbook_companion_chapter', 'c')
      ->fields('c')->condition('id', $example_data->chapter_id)->execute()->fetchObject();
    $preference_data = $chapter_data
      ? $this->database->select('textbook_companion_preference', 'p')->fields('p')->condition('id', $chapter_data->preference_id)->execute()->fetchObject()
      : NULL;
    $proposal_data = $preference_data
      ? $this->database->select('textbook_companion_proposal', 'pr')->fields('pr')->condition('id', $preference_data->proposal_id)->execute()->fetchObject()
      : NULL;

    if (!$proposal_data || $proposal_data->uid != $uid) {
      $this->messenger()->addError($this->t('You do not have permissions to edit this example.'));
      return;
    }

    $root_path = function_exists('textbook_companion_path') ? textbook_companion_path() : '';
    $dest_path = $preference_data->directory_name . '/CH' . $chapter_data->number . '/EX' . $example_data->number . '/';
    $filepath = 'CH' . $chapter_data->number . '/EX' . $example_data->number . '/';

    if ($root_path) {
      $base = $preference_data->directory_name . '/';
      if (!is_dir($root_path . $base)) mkdir($root_path . $base);
      $base .= 'CH' . $chapter_data->number . '/';
      if (!is_dir($root_path . $base)) mkdir($root_path . $base);
      $base .= 'EX' . $example_data->number . '/';
      if (!is_dir($root_path . $base)) mkdir($root_path . $base);
    }

    $this->database->update('textbook_companion_example')
      ->fields(['caption' => $form_state->getValue('example_caption')])
      ->condition('id', $example_id)->execute();

    $cur_file_id = (int) $form_state->getValue('cur_source_file_id');
    if ($cur_file_id > 0) {
      $file_data = $this->database->select('textbook_companion_example_files', 'f')
        ->fields('f')->condition('id', $cur_file_id)->condition('example_id', $example_data->id)->execute()->fetchObject();
      if (!$file_data) { $this->messenger()->addError($this->t('Error: source file not found in database.')); return; }
      if ($form_state->getValue('cur_source_checkbox') == 1 && empty($_FILES['files']['name']['sourcefile1'])) {
        if (function_exists('delete_file') && !delete_file($cur_file_id)) {
          $this->messenger()->addError($this->t('Error deleting example source file.')); return;
        }
      }
    }

    if (!empty($_FILES['files']['name']['sourcefile1'])) {
      if ($cur_file_id > 0 && function_exists('delete_file') && !delete_file($cur_file_id)) {
        $this->messenger()->addError($this->t('Error removing previous source file.')); return;
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
        $this->messenger()->addError($this->t('Error uploading file.'));
      }
    }

    $email_to = $this->currentUser->getEmail();
    $params['example_updated']['example_id'] = $example_id;
    $params['example_updated']['user_id'] = $uid;
    if (!$this->mailService->sendMail('textbook_companion', 'example_updated', $email_to, $params)) {
      $this->messenger()->addError($this->t('Error sending email message.'));
    }

    $this->messenger()->addStatus($this->t('Example successfully updated.'));
    $form_state->setRedirect('textbook_companion.list_chapters');
  }

}
