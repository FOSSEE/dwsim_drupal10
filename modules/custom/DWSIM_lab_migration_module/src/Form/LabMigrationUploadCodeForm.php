<?php

/**
 * @file
 * Contains \Drupal\lab_migration\Form\LabMigrationUploadCodeForm.
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

class LabMigrationUploadCodeForm extends FormBase {

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
   * Constructs a new LabMigrationUploadCodeForm object.
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
    return 'lab_migration_upload_code_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $proposal_data = $this->labGlobal->lab_migration_get_proposal();
    if (!$proposal_data) {
      $response = new RedirectResponse(Url::fromRoute('lab_migration.proposal_form')->toString());
      throw new EnforcedFormResponseException($response);
    }

    $form['#attributes'] = ['enctype' => 'multipart/form-data'];

    $form['lab_title'] = [
      '#type' => 'item',
      '#title' => $this->t('Title of the Lab'),
      '#markup' => $proposal_data->lab_title,
    ];

    $form['name'] = [
      '#type' => 'item',
      '#title' => $this->t('Proposer Name'),
      '#markup' => $proposal_data->name_title . ' ' . $proposal_data->name,
    ];

    $query = $this->database->select('lab_migration_experiment', 'e')
      ->fields('e')
      ->condition('proposal_id', $proposal_data->id)
      ->orderBy('id', 'ASC');
    $experiment_q = $query->execute();

    $experiment_rows = [];
    foreach ($experiment_q as $experiment_data) {
      $experiment_rows[$experiment_data->id] = $experiment_data->number . '. ' . $experiment_data->title;
    }

    $form['experiment'] = [
      '#type' => 'select',
      '#title' => $this->t('Title of the Experiment'),
      '#options' => $experiment_rows,
      '#required' => TRUE,
    ];

    $form['code_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Code No'),
      '#required' => TRUE,
    ];

    $form['code_caption'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Caption'),
      '#description' => $this->t('For eg: Shell & Tube Heat Exchanger Simulation'),
      '#size' => 40,
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['os_used'] = [
      '#type' => 'select',
      '#title' => $this->t('Operating System used'),
      '#options' => [
        'Linux' => $this->t('Linux'),
        'Windows' => $this->t('Windows'),
        'Mac' => $this->t('Mac'),
      ],
      '#required' => TRUE,
    ];

    $form['version'] = [
      '#type' => 'select',
      '#title' => $this->t('DWSIM version used'),
      '#options' => $this->labGlobal->_lm_list_of_software_version(),
      '#required' => TRUE,
    ];

    $form['toolbox_used'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Toolbox used (If any)'),
      '#default_value' => 'none',
    ];

    $form['code_warning'] = [
      '#type' => 'container',
      '#title' => $this->t('Upload all the dwsim project files in .dwxml/dwxmz format'),
      '#prefix' => '<div style="color:red">',
      '#suffix' => '</div>',
    ];

    $form['sourcefile'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Main or Source Files'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    $config = $this->configFactory()->get('lab_migration.settings');
    $extensions = $config->get('lab_migration_source_extensions') ?? '';
    $form['sourcefile']['sourcefile1'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload main or source file'),
      '#size' => 48,
      '#description' => $this->t('Only alphabets and numbers are allowed as a valid filename.') . '<br />' . $this->t('Allowed file extensions: ') . $extensions,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    $form['cancel_link'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl($this->t('Cancel'), Url::fromRoute('lab_migration.list_experiments'))->toString(),
    ];

    return $form;
  }

  private function lab_migration_check_code_number($code_number) {
    return preg_match('/^[0-9]+$/', $code_number);
  }

  private function lab_migration_check_name($caption) {
    return preg_match('/^[a-zA-Z0-9 ]+$/', $caption);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$this->lab_migration_check_code_number($form_state->getValue('code_number'))) {
      $form_state->setErrorByName('code_number', $this->t('Invalid Code Number. Code Number can contain only numbers.'));
    }

    if (!$this->lab_migration_check_name($form_state->getValue('code_caption'))) {
      $form_state->setErrorByName('code_caption', $this->t('Caption can contain only alphabets, numbers and spaces.'));
    }

    if (!$form_state->getValue('os_used')) {
      $form_state->setErrorByName('os_used', $this->t('Please select the operating system used.'));
    }

    if (!$form_state->getValue('version')) {
      $form_state->setErrorByName('version', $this->t('Please select the version used.'));
    }

    if (isset($_FILES['files'])) {
      if (empty($_FILES['files']['name']['sourcefile1'])) {
        $form_state->setErrorByName('sourcefile1', $this->t('Please upload at least one main or source file.'));
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

          $config = $this->configFactory()->get('lab_migration.settings');
          $allowed_extensions_str = '';
          switch ($file_type) {
            case 'S':
              $allowed_extensions_str = $config->get('lab_migration_source_extensions') ?? '';
              break;
            case 'R':
              $allowed_extensions_str = $config->get('lab_migration_result_extensions') ?? '';
              break;
            case 'X':
              $allowed_extensions_str = $config->get('lab_migration_xcos_extensions') ?? '';
              break;
          }

          $allowed_extensions = explode(',', $allowed_extensions_str);
          $tmp_ext = explode('.', strtolower($_FILES['files']['name'][$file_form_name]));
          $temp_extension = end($tmp_ext);
          if (!in_array($temp_extension, $allowed_extensions)) {
            $form_state->setErrorByName($file_form_name, $this->t('Only file with @ext extensions can be uploaded.', ['@ext' => $allowed_extensions_str]));
          }
          if ($_FILES['files']['size'][$file_form_name] <= 0) {
            $form_state->setErrorByName($file_form_name, $this->t('File size cannot be zero.'));
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $root_path = $this->labGlobal->lab_migration_path();

    $proposal_data = $this->labGlobal->lab_migration_get_proposal();
    if (!$proposal_data) {
      $form_state->setRedirect('<front>');
      return;
    }

    $proposal_id = $proposal_data->id;
    $proposal_directory = $proposal_data->directory_name;

    $experiment_id = (int) $form_state->getValue('experiment');
    $experiment_data = $this->database->select('lab_migration_experiment', 'e')
      ->fields('e')
      ->condition('id', $experiment_id)
      ->condition('proposal_id', $proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$experiment_data) {
      $this->messenger->addError($this->t("Invalid experiment selected"));
      $form_state->setRedirect('lab_migration.list_experiments');
      return;
    }

    $dest_path = $proposal_directory . '/';
    if (!is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path, 0775, TRUE);
    }

    $code_number = $experiment_data->number . '.' . $form_state->getValue('code_number');

    $cur_solution_d = $this->database->select('lab_migration_solution', 's')
      ->fields('s')
      ->condition('experiment_id', $experiment_id)
      ->condition('code_number', $code_number)
      ->execute()
      ->fetchObject();

    if ($cur_solution_d) {
      if ($cur_solution_d->approval_status == 1) {
        $this->messenger->addError($this->t("Solution already approved. Cannot overwrite it."));
        $form_state->setRedirect('lab_migration.list_experiments');
        return;
      }
      elseif ($cur_solution_d->approval_status == 0) {
        $this->messenger->addError($this->t("Solution is under pending review. Delete the solution and reupload it."));
        $form_state->setRedirect('lab_migration.list_experiments');
        return;
      }
      else {
        $this->messenger->addError($this->t("Error uploading solution. Please contact administrator."));
        $form_state->setRedirect('lab_migration.list_experiments');
        return;
      }
    }

    $dest_path .= 'EXP' . $experiment_data->number . '/';
    if (!is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path, 0775, TRUE);
    }

    $dest_path .= 'CODE' . $experiment_data->number . '.' . $form_state->getValue('code_number') . '/';
    if (!is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path, 0775, TRUE);
    }

    $file_path = 'EXP' . $experiment_data->number . '/' . 'CODE' . $experiment_data->number . '.' . $form_state->getValue('code_number') . '/';

    $solution_id = $this->database->insert('lab_migration_solution')
      ->fields([
        'experiment_id' => $experiment_id,
        'approver_uid' => 0,
        'code_number' => $code_number,
        'caption' => $form_state->getValue('code_caption'),
        'approval_date' => 0,
        'approval_status' => 0,
        'timestamp' => time(),
        'os_used' => $form_state->getValue('os_used'),
        'dwsim_version' => $form_state->getValue('version') ?? '',
        'toolbox_used' => $form_state->getValue('toolbox_used'),
      ])
      ->execute();

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

        if (file_exists($root_path . $dest_path . $file_name)) {
          $this->messenger->addError($this->t("Error uploading file. File @filename already exists.", [
            '@filename' => $file_name,
          ]));
          return;
        }

        if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $file_name)) {
          $this->database->insert('lab_migration_solution_files')
            ->fields([
              'solution_id' => $solution_id,
              'filename' => $file_name,
              'filepath' => $file_path . $file_name,
              'filemime' => mime_content_type($root_path . $dest_path . $file_name),
              'filesize' => $_FILES['files']['size'][$file_form_name],
              'filetype' => $file_type,
              'timestamp' => time(),
            ])
            ->execute();
        }
      }
    }

    $this->messenger->addMessage($this->t('Solution uploaded successfully.'), 'status');

    $user_data = User::load($proposal_data->uid);
    if ($user_data && $user_data->getEmail()) {
      $email_to = $user_data->getEmail();
      $config = $this->configFactory()->get('lab_migration.settings');
      $from = $config->get('lab_migration_from_email');
      $bcc = $config->get('lab_migration_emails');
      $cc = $config->get('lab_migration_cc_emails');

      $params['solution_uploaded'] = [
        'solution_id' => $solution_id,
        'user_id' => $this->currentUser->id(),
        'headers' => [
          'From' => $from,
          'Cc' => $cc,
          'Bcc' => $bcc,
        ],
      ];

      if ($this->mailService->sendMail('lab_migration', 'solution_uploaded', $email_to, $params)) {
        $this->messenger->addMessage($this->t('Mail notification sent successfully.'));
      }
    }

    $form_state->setRedirect('lab_migration.list_experiments');
  }

}