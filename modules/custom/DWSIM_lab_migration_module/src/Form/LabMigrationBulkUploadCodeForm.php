<?php

/**
 * @file
 * Contains \Drupal\lab_migration\Form\LabMigrationBulkUploadCodeForm.
 */

namespace Drupal\lab_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\lab_migration\Services\LabMigrationGlobalfunction;
use Drupal\lab_migration\Services\MailService;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Form\EnforcedFormResponseException;

class LabMigrationBulkUploadCodeForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The lab migration global service.
   *
   * @var \Drupal\lab_migration\Services\LabMigrationGlobalfunction
   */
  protected $labGlobal;

  /**
   * The mail service.
   *
   * @var \Drupal\lab_migration\Services\MailService
   */
  protected $mailService;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new LabMigrationBulkUploadCodeForm object.
   */
  public function __construct(
    Connection $database,
    MessengerInterface $messenger,
    AccountProxyInterface $current_user,
    LabMigrationGlobalfunction $lab_global,
    MailService $mail_service,
    RequestStack $request_stack
  ) {
    $this->database = $database;
    $this->messenger = $messenger;
    $this->currentUser = $current_user;
    $this->labGlobal = $lab_global;
    $this->mailService = $mail_service;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('messenger'),
      $container->get('current_user'),
      $container->get('lab_migration_global'),
      $container->get('lab_migration.mail_service'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lab_migration_bulk_upload_code_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $route_match = $this->requestStack->getCurrentRequest()->attributes->get('_route_match');
    $proposal_id = $route_match ? (int) $route_match->getParameter('proposal_id') : 0;

    $proposal_data = $this->database->select('lab_migration_proposal')
      ->fields('lab_migration_proposal')
      ->condition('id', $proposal_id)
      ->execute()
      ->fetchObject();

    if (!$proposal_data) {
      $this->messenger->addError($this->t("Invalid proposal selected"));
      $response = new RedirectResponse(Url::fromRoute('lab_migration.bulk_approval_form')->toString());
      throw new EnforcedFormResponseException($response);
    }

    $dep_selection_js = "(function ($) {
    $('#edit-existing-depfile-dep-lab-title').change(function() {
      var dep_selected = ''; 
      var activeClass = $(this).val();
      /* showing and hiding relevant files */
      $('.form-checkboxes .form-item').hide();
      $('.form-checkboxes .form-item').each(function() {
        if ($(this).find('input').hasClass(activeClass)) {
          $(this).show();
        }
        if ($(this).find('input').prop('checked') == true) {
          dep_selected += $(this).find('label').text() + '<br />';
        }
      });
      /* showing list of already existing dependencies */
      $('#existing_depfile_selected').html(dep_selected);
    });

    $('.form-checkboxes input').change(function() {
      $('#edit-existing-depfile-dep-lab-title').trigger('change');
    });
    $('#edit-existing-depfile-dep-lab-title').trigger('change');
  })(jQuery);";

    $form['#attached']['html_head'][] = [
      [
        '#tag' => 'script',
        '#value' => $dep_selection_js,
      ],
      'dep_selection_js',
    ];

    $form['#attributes'] = ['enctype' => "multipart/form-data"];

    $form['lab_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->lab_title,
      '#title' => $this->t('Title of the Lab'),
    ];

    $form['name'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->name_title . ' ' . $proposal_data->name,
      '#title' => $this->t('Proposer Name'),
    ];

    /* get experiment list */
    $experiment_rows = [];
    $experiment_q = $this->database->select('lab_migration_experiment')
      ->fields('lab_migration_experiment')
      ->condition('proposal_id', $proposal_data->id)
      ->orderBy('id', 'ASC')
      ->execute();

    while ($experiment_data = $experiment_q->fetchObject()) {
      $experiment_rows[$experiment_data->id] = $experiment_data->number . '. ' . $experiment_data->title;
    }

    $form['experiment'] = [
      '#type' => 'select',
      '#title' => $this->t('Title of the Experiment'),
      '#options' => $experiment_rows,
      '#multiple' => FALSE,
      '#size' => 1,
      '#required' => TRUE,
    ];

    $form['code_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Code No'),
      '#size' => 5,
      '#maxlength' => 10,
      '#required' => TRUE,
    ];

    $form['code_caption'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Caption'),
      '#size' => 40,
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['code_warning'] = [
      '#type' => 'item',
      '#markup' => $this->t('Upload all the dwsim project files in .zip format'),
      '#prefix' => '<div style="color:red">',
      '#suffix' => '</div>',
    ];

    $config = $this->configFactory()->get('lab_migration.settings');

    $form['sourcefile'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Main or Source Files'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    $form['sourcefile']['sourcefile1'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload main or source file'),
      '#size' => 48,
      '#description' => $this->t('Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . $this->t('Allowed file extensions : ') . $config->get('lab_migration_source_extensions'),
    ];

    $form['dep_files'] = [
      '#type' => 'item',
      '#title' => $this->t('Dependency Files'),
    ];

    /************ START OF EXISTING DEPENDENCIES **************/
    $form['existing_depfile'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Use Already Existing Dependency Files'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#prefix' => '<div id="existing-depfile-wrapper">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];

    $form['existing_depfile']['selected'] = [
      '#type' => 'item',
      '#title' => $this->t('Existing Dependency Files Selected'),
      '#markup' => '<div id="existing_depfile_selected"></div>',
    ];

    $form['existing_depfile']['dep_lab_title'] = [
      '#type' => 'select',
      '#title' => $this->t('Title of the Lab'),
      '#options' => $this->_list_of_lab_titles(),
    ];

    list($files_options, $files_options_class) = $this->_list_of_dependency_files();
    $form['existing_depfile']['dep_experiment_files'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Dependency Files'),
      '#options' => $files_options,
      '#options_attributes' => $files_options_class,
      '#multiple' => TRUE,
    ];

    $form['existing_depfile']['dep_upload'] = [
      '#type' => 'item',
      '#markup' => Link::fromTextAndUrl($this->t('Upload New Dependency Files'), Url::fromRoute('lab_migration.upload_dep'))->toString(),
    ];
    /************ END OF EXISTING DEPENDENCIES **************/

    $form['result'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Result Files'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    $form['result']['result1'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload result file'),
      '#size' => 48,
      '#description' => $this->t('Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . $this->t('Allowed file extensions : ') . $config->get('lab_migration_result_extensions'),
    ];

    $form['result']['result2'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload result file'),
      '#size' => 48,
      '#description' => $this->t('Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . $this->t('Allowed file extensions : ') . $config->get('lab_migration_result_extensions'),
    ];

    $form['xcos'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('XCOS Files'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    $form['xcos']['xcos1'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload xcos file'),
      '#size' => 48,
      '#description' => $this->t('Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . $this->t('Allowed file extensions : ') . $config->get('lab_migration_xcos_extensions'),
    ];

    $form['xcos']['xcos2'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload xcos file'),
      '#size' => 48,
      '#description' => $this->t('Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . $this->t('Allowed file extensions : ') . $config->get('lab_migration_xcos_extensions'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    $form['cancel'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl($this->t('Cancel'), Url::fromRoute('lab_migration.bulk_approval_form'))->toString(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$this->labGlobal->lab_migration_check_code_number($form_state->getValue('code_number'))) {
      $form_state->setErrorByName('code_number', $this->t('Invalid Code Number. Code Number can contain only numbers.'));
    }

    if (!$this->labGlobal->lab_migration_check_name($form_state->getValue('code_caption'))) {
      $form_state->setErrorByName('code_caption', $this->t('Caption can contain only alphabets, numbers and spaces.'));
    }

    if (isset($_FILES['files'])) {
      if (!($_FILES['files']['name']['sourcefile1'] || $_FILES['files']['name']['xcos1'])) {
        $form_state->setErrorByName('sourcefile1', $this->t('Please upload at least one main or source file or xcos file.'));
      }

      $config = $this->configFactory()->get('lab_migration.settings');

      foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
        if ($file_name) {
          if (strstr($file_form_name, 'source')) {
            $file_type = 'S';
          }
          elseif (strstr($file_form_name, 'result')) {
            $file_type = 'R';
          }
          elseif (strstr($file_form_name, 'xcos')) {
            $file_type = 'X';
          }
          else {
            $file_type = 'U';
          }

          $allowed_extensions_str = '';
          switch ($file_type) {
            case 'S':
              $allowed_extensions_str = $config->get('lab_migration_source_extensions');
              break;
            case 'R':
              $allowed_extensions_str = $config->get('lab_migration_result_extensions');
              break;
            case 'X':
              $allowed_extensions_str = $config->get('lab_migration_xcos_extensions');
              break;
          }

          $allowed_extensions = explode(',', $allowed_extensions_str);
          $parts = explode('.', strtolower($_FILES['files']['name'][$file_form_name]));
          $temp_extension = end($parts);

          if (!in_array($temp_extension, $allowed_extensions)) {
            $form_state->setErrorByName($file_form_name, $this->t('Only file with @ext extensions can be uploaded.', ['@ext' => $allowed_extensions_str]));
          }

          if ($_FILES['files']['size'][$file_form_name] <= 0) {
            $form_state->setErrorByName($file_form_name, $this->t('File size cannot be zero.'));
          }

          if (!$this->labGlobal->lab_migration_check_valid_filename($_FILES['files']['name'][$file_form_name])) {
            $form_state->setErrorByName($file_form_name, $this->t('Invalid file name specified. Only alphabets and numbers are allowed as a valid filename.'));
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $route_match = $this->requestStack->getCurrentRequest()->attributes->get('_route_match');
    $proposal_id = $route_match ? (int) $route_match->getParameter('proposal_id') : 0;

    $proposal_data = $this->database->select('lab_migration_proposal')
      ->fields('lab_migration_proposal')
      ->condition('id', $proposal_id)
      ->execute()
      ->fetchObject();

    if (!$proposal_data) {
      $this->messenger->addError($this->t("Invalid proposal selected"));
      $form_state->setRedirect('lab_migration.bulk_approval_form');
      return;
    }

    $proposal_id = $proposal_data->id;
    $experiment_id = (int) $form_state->getValue('experiment');

    $experiment_data = $this->database->select('lab_migration_experiment')
      ->fields('lab_migration_experiment')
      ->condition('id', $experiment_id)
      ->condition('proposal_id', $proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$experiment_data) {
      $this->messenger->addError($this->t("Invalid experiment selected"));
      $form_state->setRedirect('lab_migration.bulk_approval_form');
      return;
    }

    $root_path = $this->labGlobal->lab_migration_path();
    $dest_path = $proposal_id . '/';
    if (!is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path);
    }

    $code_number = $experiment_data->number . '.' . $form_state->getValue('code_number');
    $cur_solution_d = $this->database->select('lab_migration_solution')
      ->fields('lab_migration_solution')
      ->condition('experiment_id', $experiment_id)
      ->condition('code_number', $code_number)
      ->execute()
      ->fetchObject();

    if ($cur_solution_d) {
      if ($cur_solution_d->approval_status == 1) {
        $this->messenger->addError($this->t("Solution already approved. Cannot overwrite it."));
        return;
      }
      else {
        if ($cur_solution_d->approval_status == 0) {
          $this->messenger->addError($this->t("Solution is under pending review. Delete the solution and reupload it."));
          return;
        }
        else {
          $this->messenger->addError($this->t("Error uploading solution. Please contact administrator."));
          return;
        }
      }
    }

    $dest_path .= 'EXP' . $experiment_data->number . '/';
    if (!is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path);
    }

    $dest_path .= 'CODE' . $experiment_data->number . '.' . $form_state->getValue('code_number') . '/';
    if (!is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path);
    }

    $solution_id = $this->database->insert('lab_migration_solution')
      ->fields([
        'experiment_id' => $experiment_id,
        'approver_uid' => 0,
        'code_number' => $code_number,
        'caption' => $form_state->getValue('code_caption'),
        'approval_date' => 0,
        'approval_status' => 0,
        'timestamp' => time(),
      ])
      ->execute();

    if ($form_state->getValue(['existing_depfile', 'dep_experiment_files'])) {
      foreach ($form_state->getValue(['existing_depfile', 'dep_experiment_files']) as $row) {
        if ($row > 0) {
          $this->database->insert('lab_migration_solution_dependency')
            ->fields([
              'solution_id' => $solution_id,
              'dependency_id' => $row,
            ])
            ->execute();
        }
      }
    }

    foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
      if ($file_name) {
        if (strstr($file_form_name, 'source')) {
          $file_type = 'S';
        }
        elseif (strstr($file_form_name, 'result')) {
          $file_type = 'R';
        }
        elseif (strstr($file_form_name, 'xcos')) {
          $file_type = 'X';
        }
        else {
          $file_type = 'U';
        }

        if (file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
          $this->messenger->addError($this->t("Error uploading file. File @filename already exists.", ['@filename' => $_FILES['files']['name'][$file_form_name]]));
          return;
        }

        if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
          $this->database->insert('lab_migration_solution_files')
            ->fields([
              'solution_id' => $solution_id,
              'filename' => $_FILES['files']['name'][$file_form_name],
              'filepath' => $dest_path . $_FILES['files']['name'][$file_form_name],
              'filemime' => $_FILES['files']['type'][$file_form_name],
              'filesize' => $_FILES['files']['size'][$file_form_name],
              'filetype' => $file_type,
              'timestamp' => time(),
            ])
            ->execute();
          $this->messenger->addMessage($file_name . ' uploaded successfully.', 'status');
        }
        else {
          $this->messenger->addError($this->t('Error uploading file: @file', ['@file' => $dest_path . $file_name]));
        }
      }
    }

    $this->messenger->addMessage($this->t('Solution uploaded successfully.'));

    $user_data = User::load($proposal_data->uid);
    if ($user_data && $user_data->getEmail()) {
      $email_to = $user_data->getEmail();
      $config = $this->configFactory()->get('lab_migration.settings');
      $from = $config->get('lab_migration_from_email');
      $bcc = $config->get('lab_migration_emails');
      $cc = $config->get('lab_migration_cc_emails');

      $params['solution_uploaded']['solution_id'] = $solution_id;
      $params['solution_uploaded']['user_id'] = $this->currentUser->id();
      $params['solution_uploaded']['headers'] = [
        'From' => $from,
        'Cc' => $cc,
        'Bcc' => $bcc,
      ];

      if ($this->mailService->sendMail('lab_migration', 'solution_uploaded', $email_to, $params)) {
        $this->messenger->addMessage($this->t('Mail notification sent successfully.'));
      }
    }

    $form_state->setRedirect('lab_migration.bulk_approval_form');
  }

  /**
   * Helper function to get approved lab titles.
   */
  protected function _list_of_lab_titles() {
    $lab_titles = ['0' => $this->t('Please select...')];
    $results = $this->database->select('lab_migration_proposal', 'p')
      ->fields('p', ['id', 'lab_title', 'name'])
      ->condition('approval_status', 3)
      ->orderBy('lab_title', 'ASC')
      ->execute();
    foreach ($results as $row) {
      $lab_titles[$row->id] = $row->lab_title . ' (Proposed by ' . $row->name . ')';
    }
    return $lab_titles;
  }

  /**
   * Helper function to get dependency files list.
   */
  protected function _list_of_dependency_files() {
    $options = [];
    $options_class = [];
    $query = $this->database->select('lab_migration_dependency_files', 'd')
      ->fields('d', ['id', 'filename', 'proposal_id'])
      ->execute();
    foreach ($query as $row) {
      $options[$row->id] = $row->filename;
      $options_class[$row->id] = [
        'class' => [$row->proposal_id],
      ];
    }
    return [$options, $options_class];
  }

}