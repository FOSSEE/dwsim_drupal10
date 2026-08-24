<?php

/**
 * @file
 * Upload example code form for Textbook Companion.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\textbook_companion\Services\MailService;
use Drupal\textbook_companion\Services\AjaxHelper;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Form\EnforcedResponseException;

/**
 * Migrated upload_examples_form — same gates, upload path, and mail key.
 */
class UploadExamplesForm extends FormBase {

  protected $database;
  protected $currentUser;
  protected $configFactory;
  protected $mailService;
  protected $ajaxHelper;

  public function __construct(
    Connection $database,
    AccountProxyInterface $current_user,
    ConfigFactoryInterface $config_factory,
    MailService $mail_service,
    AjaxHelper $ajax_helper
  ) {
    $this->database = $database;
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
    $this->mailService = $mail_service;
    $this->ajaxHelper = $ajax_helper;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('textbook_companion.mail_service'),
      $container->get('textbook_companion.ajax_helper')
    );
  }

  public function getFormId() {
    return 'upload_examples_form';
  }

  protected function redirectFront() {
    throw new EnforcedResponseException(new RedirectResponse(Url::fromRoute('<front>')->toString()));
  }

  protected function redirectCode() {
    throw new EnforcedResponseException(new RedirectResponse(Url::fromRoute('textbook_companion.list_chapters')->toString()));
  }

  /**
   * Shared proposal/preference gate used by build and submit.
   */
  protected function loadApprovedContext() {
    $proposal_data = $this->database->select('textbook_companion_proposal')
      ->fields('textbook_companion_proposal')
      ->condition('uid', $this->currentUser->id())
      ->orderBy('id', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$proposal_data) {
      $link = Link::fromTextAndUrl($this->t('proposal'), Url::fromRoute('textbook_companion.proposal_all'))->toString();
      $this->messenger()->addError($this->t('Please submit a @link.', ['@link' => $link]));
      $this->redirectFront();
    }

    if ($proposal_data->proposal_status != 1 && $proposal_data->proposal_status != 4) {
      $here = Link::fromTextAndUrl($this->t('here'), Url::fromRoute('textbook_companion.proposal_all'))->toString();
      switch ($proposal_data->proposal_status) {
        case 0:
          $this->messenger()->addStatus($this->t('We have already received your proposal. We will get back to you soon.'));
          $this->redirectFront();
        case 2:
          $this->messenger()->addError($this->t('Your proposal has been dis-approved. Please create another proposal @here.', ['@here' => $here]));
          $this->redirectFront();
        case 3:
          $this->messenger()->addStatus($this->t('Congratulations! You have completed your last book proposal. You have to create another proposal @here.', ['@here' => $here]));
          $this->redirectFront();
        case 5:
          $this->messenger()->addStatus($this->t('You have submitted your all codes.'));
          $this->redirectFront();
        default:
          $this->messenger()->addError($this->t('Invalid proposal state. Please contact site administrator for further information.'));
          $this->redirectFront();
      }
    }

    $preference_data = $this->database->select('textbook_companion_preference')
      ->fields('textbook_companion_preference')
      ->condition('proposal_id', $proposal_data->id)
      ->condition('approval_status', 1)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$preference_data) {
      $this->messenger()->addError($this->t('Invalid Book Preference status. Please contact site administrator for further information.'));
      $this->redirectFront();
    }

    return [$proposal_data, $preference_data];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    list($proposal_data, $preference_data) = $this->loadApprovedContext();

    $form['#attributes'] = ['enctype' => 'multipart/form-data'];
    $form['book_details']['pref_id'] = [
      '#type' => 'hidden',
      '#value' => $preference_data->id,
    ];
    $form['book_details']['book'] = [
      '#type' => 'item',
      '#markup' => $preference_data->book,
      '#title' => $this->t('Title of the Book'),
    ];
    $form['contributor_name'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->full_name,
      '#title' => $this->t('Contributor Name'),
    ];

    $options = ['' => '(Select)'];
    for ($i = 1; $i <= 100; $i++) {
      $options[$i] = $i;
    }
    $form['number'] = [
      '#type' => 'select',
      '#title' => $this->t('Chapter No'),
      '#options' => $options,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::ajaxChapterNameCallback',
        'event' => 'change',
      ],
    ];
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title of the Chapter'),
      '#size' => 40,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#prefix' => '<div id="ajax-chapter-name-replace">',
      '#suffix' => '</div>',
    ];
    $form['example_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Example No'),
      '#size' => 5,
      '#maxlength' => 10,
      '#description' => $this->t('Example number should be separated by dots only.<br />Example: 1.1.a &nbsp;or&nbsp; 1.1.1'),
      '#required' => TRUE,
    ];
    $form['example_caption'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Caption'),
      '#size' => 40,
      '#maxlength' => 255,
      '#description' => $this->t('Example caption should contain only alphabets, numbers and spaces.'),
      '#required' => TRUE,
    ];
    $form['example_warning'] = [
      '#type' => 'item',
      '#title' => $this->t('You should upload all the files as zip (main or source files, result files, executable file if any): '),
      '#prefix' => '<div style="color:red">',
      '#suffix' => '</div>',
    ];

    $ext = $this->configFactory->get('textbook_companion.settings')->get('textbook_companion_source_extensions');
    if (!$ext && function_exists('tc_variable_get')) {
      $ext = tc_variable_get('textbook_companion_source_extensions', '');
    }
    $form['sourcefile'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Main or Source Files'),
    ];
    $form['sourcefile']['sourcefile1'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload main or source file'),
      '#size' => 48,
      '#description' => $this->t('Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br /><span style="color:red;">Allowed file extensions : ' . $ext . '</span>',
    ];
    $form['submit'] = ['#type' => 'submit', '#value' => $this->t('Submit')];
    $form['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('textbook_companion.list_chapters'),
    ];

    return $form;
  }

  /**
   * AJAX: fill chapter title when chapter number already exists.
   */
  public function ajaxChapterNameCallback(array &$form, FormStateInterface $form_state) {
    $pref_id = $form_state->getValue('pref_id');
    $chapter_number = $form_state->getValue('number');
    $chapter = $this->database->select('textbook_companion_chapter')
      ->fields('textbook_companion_chapter')
      ->condition('preference_id', $pref_id)
      ->condition('number', $chapter_number)
      ->execute()
      ->fetchObject();

    if ($chapter) {
      $form['name']['#value'] = $chapter->name;
      $form['name']['#attributes']['readonly'] = 'readonly';
    }
    else {
      $form['name']['#value'] = ' ';
      unset($form['name']['#attributes']['readonly']);
    }

    return $this->ajaxHelper->replaceWrapper('#ajax-chapter-name-replace', $form['name']);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (function_exists('check_name') && !check_name($form_state->getValue('name'))) {
      $form_state->setErrorByName('name', $this->t('Title of the Chapter can contain only alphabets, numbers and spaces.'));
    }
    if (function_exists('check_name') && !check_name($form_state->getValue('example_caption'))) {
      $form_state->setErrorByName('example_caption', $this->t('Example Caption can contain only alphabets, numbers and spaces.'));
    }
    if (function_exists('check_chapter_number') && !check_chapter_number($form_state->getValue('example_number'))) {
      $form_state->setErrorByName('example_number', $this->t('Invalid Example Number. Example Number can contain only alphabets and numbers sepereated by dot.'));
    }
    if (empty($_FILES['files']['name']['sourcefile1'])) {
      $form_state->setErrorByName('sourcefile1', $this->t('Please upload source file.'));
    }
    if (!empty($_FILES['files']['name'])) {
      $allowed_extensions_str = function_exists('tc_variable_get')
        ? tc_variable_get('textbook_companion_source_extensions', '')
        : (string) $this->configFactory->get('textbook_companion.settings')->get('textbook_companion_source_extensions');
      $allowed_extensions = array_filter(array_map('trim', explode(',', $allowed_extensions_str)));
      foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
        if ($file_name) {
          $temp_ext = explode('.', strtolower($file_name));
          $temp_extension = end($temp_ext);
          if ($allowed_extensions && !in_array($temp_extension, $allowed_extensions)) {
            $form_state->setErrorByName($file_form_name, $this->t('Only file with @ext extensions can be uploaded.', ['@ext' => $allowed_extensions_str]));
          }
          if ($_FILES['files']['size'][$file_form_name] <= 0) {
            $form_state->setErrorByName($file_form_name, $this->t('File size cannot be zero.'));
          }
        }
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    list($proposal_data, $preference_data) = $this->loadApprovedContext();
    $root_path = function_exists('textbook_companion_path') ? textbook_companion_path() : '';

    $dup = $this->database->select('textbook_companion_preference')
      ->fields('textbook_companion_preference')
      ->condition('proposal_id', $proposal_data->id)
      ->condition('approval_status', 1)
      ->execute()
      ->rowCount();
    if ($dup > 1) {
      $this->messenger()->addError($this->t('You cannot upload your code. This name of book directory alrady preasent in directory folder, please contact to administrator.'));
      return;
    }

    $proposal_directory = $preference_data->directory_name;
    $dest_path = $proposal_directory . '/';
    if ($root_path && !is_dir($root_path . $dest_path)) {
      if (!mkdir($root_path . $dest_path)) {
        $this->messenger()->addError($this->t('You cannot upload your code. Error in creating directory'));
      }
    }

    $preference_id = $preference_data->id;
    $chapter_row = $this->database->select('textbook_companion_chapter')
      ->fields('textbook_companion_chapter')
      ->condition('preference_id', $preference_id)
      ->condition('number', $form_state->getValue('number'))
      ->execute()
      ->fetchObject();

    if (!$chapter_row) {
      $chapter_id = $this->database->insert('textbook_companion_chapter')
        ->fields([
          'preference_id' => $preference_id,
          'number' => $form_state->getValue('number'),
          'name' => $form_state->getValue('name'),
        ])
        ->execute();
    }
    else {
      $chapter_id = $chapter_row->id;
      $this->database->update('textbook_companion_chapter')
        ->fields(['name' => $form_state->getValue('name')])
        ->condition('id', $chapter_id)
        ->execute();
    }

    $cur_example = $this->database->select('textbook_companion_example')
      ->fields('textbook_companion_example')
      ->condition('chapter_id', $chapter_id)
      ->condition('number', $form_state->getValue('example_number'))
      ->execute()
      ->fetchObject();
    if ($cur_example) {
      if ($cur_example->approval_status == 1) {
        $this->messenger()->addError($this->t('Example already approved. Cannot overwrite it.'));
      }
      elseif ($cur_example->approval_status == 0) {
        $this->messenger()->addError($this->t('Example is under pending review. Delete the example and reupload it.'));
      }
      else {
        $this->messenger()->addError($this->t('Error uploading example. Please contact administrator.'));
      }
      $this->redirectCode();
    }

    $dest_path .= 'CH' . $form_state->getValue('number') . '/';
    if ($root_path && !is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path);
    }
    $dest_path .= 'EX' . $form_state->getValue('example_number') . '/';
    if ($root_path && !is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path);
    }
    $filepath = 'CH' . $form_state->getValue('number') . '/' . 'EX' . $form_state->getValue('example_number') . '/';

    $example_id = $this->database->insert('textbook_companion_example')
      ->fields([
        'chapter_id' => $chapter_id,
        'number' => $form_state->getValue('example_number'),
        'caption' => $form_state->getValue('example_caption'),
        'approval_date' => time(),
        'approval_status' => 0,
        'timestamp' => time(),
      ])
      ->execute();

    if (!empty($_FILES['files']['name'])) {
      foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
        if ($file_name) {
          $file_type = 'S';
          if ($root_path && file_exists($root_path . $dest_path . $file_name)) {
            $this->messenger()->addError($this->t('Error uploading file. File @filename already exists.', ['@filename' => $file_name]));
            return;
          }
          if ($root_path && move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $file_name)) {
            $this->database->insert('textbook_companion_example_files')
              ->fields([
                'example_id' => $example_id,
                'filename' => $file_name,
                'filepath' => $filepath . $file_name,
                'filemime' => 'application/zip',
                'filesize' => $_FILES['files']['size'][$file_form_name],
                'filetype' => $file_type,
                'timestamp' => time(),
              ])
              ->execute();
            $this->messenger()->addStatus($file_name . ' uploaded successfully.');
          }
          else {
            $this->messenger()->addError('Error uploading file : ' . $dest_path . '/' . $file_name);
          }
        }
      }
    }

    $this->messenger()->addStatus($this->t('Example uploaded successfully.'));
    $params = [];
    $params['example_uploaded']['example_id'] = $example_id;
    $params['example_uploaded']['user_id'] = $this->currentUser->id();
    if (!$this->mailService->sendMail('textbook_companion', 'example_uploaded', $this->currentUser->getEmail(), $params)) {
      $this->messenger()->addError($this->t('Error sending email message.'));
    }
    $form_state->setRedirect('textbook_companion.list_chapters');
  }

}
