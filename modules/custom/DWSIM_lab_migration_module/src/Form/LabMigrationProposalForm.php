<?php

/**
 * @file
 * Contains \Drupal\lab_migration\Form\LabMigrationProposalForm.
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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Form\EnforcedFormResponseException;

class LabMigrationProposalForm extends FormBase {

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
   * Constructs a new LabMigrationProposalForm object.
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
    return 'lab_migration_proposal_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($this->currentUser->isAnonymous()) {
      $this->messenger->addMessage($this->t('It is mandatory to login to this website to access the lab proposal form. If you are a new user, please create an account first.'));
      $response = new RedirectResponse(Url::fromRoute('user.page')->toString());
      throw new EnforcedFormResponseException($response);
    }

    $proposal_data = $this->database->select('lab_migration_proposal')
      ->fields('lab_migration_proposal')
      ->condition('uid', $this->currentUser->id())
      ->orderBy('id', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if ($proposal_data) {
      if ($proposal_data->approval_status == 0 || $proposal_data->approval_status == 1) {
        $this->messenger->addMessage($this->t('We have already received your proposal.'));
        $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
        throw new EnforcedFormResponseException($response);
      }
    }

    $form['#attributes'] = ['enctype' => "multipart/form-data"];

    $form['name_title'] = [
      '#type' => 'select',
      '#title' => $this->t('Title'),
      '#options' => [
        'Dr' => 'Dr',
        'Prof' => 'Prof',
      ],
      '#required' => TRUE,
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name of the Proposer'),
      '#size' => 100,
      '#attributes' => [
        'placeholder' => $this->t('Enter your full name'),
      ],
      '#maxlength' => 200,
      '#required' => TRUE,
    ];

    $form['email_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email'),
      '#size' => 30,
      '#value' => $this->currentUser->getEmail(),
      '#disabled' => TRUE,
    ];

    $form['contact_ph'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Contact No.'),
      '#size' => 30,
      '#attributes' => [
        'placeholder' => $this->t('Enter your contact number'),
      ],
      '#maxlength' => 15,
      '#required' => TRUE,
    ];

    $form['department'] = [
      '#type' => 'select',
      '#title' => $this->t('Department/Branch'),
      '#options' => $this->labGlobal->_lm_list_of_departments(),
      '#required' => TRUE,
    ];

    $form['university'] = [
      '#type' => 'textfield',
      '#title' => $this->t('University/ Institute'),
      '#size' => 50,
      '#maxlength' => 200,
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('Insert full name of your institute/ university.... '),
      ],
    ];

    $form['country'] = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#options' => [
        'India' => 'India',
        'Others' => 'Others',
      ],
      '#required' => TRUE,
      '#tree' => TRUE,
      '#validated' => TRUE,
    ];

    $form['other_country'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other than India'),
      '#size' => 30,
      '#attributes' => [
        'placeholder' => $this->t('Enter your country name'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others',
          ],
        ],
      ],
    ];

    $form['other_state'] = [
      '#type' => 'textfield',
      '#title' => $this->t('State other than India'),
      '#size' => 50,
      '#attributes' => [
        'placeholder' => $this->t('Enter your state/region name'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others',
          ],
        ],
      ],
    ];

    $form['other_city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City other than India'),
      '#size' => 50,
      '#attributes' => [
        'placeholder' => $this->t('Enter your city name'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others',
          ],
        ],
      ],
    ];

    $form['all_state'] = [
      '#type' => 'select',
      '#title' => $this->t('State'),
      '#options' => $this->labGlobal->_lm_list_of_states(),
      '#validated' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'India',
          ],
        ],
      ],
    ];

    $form['city'] = [
      '#type' => 'select',
      '#title' => $this->t('City'),
      '#options' => $this->labGlobal->_lm_list_of_cities(),
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'India',
          ],
        ],
      ],
    ];

    $form['pincode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pincode'),
      '#size' => 30,
      '#maxlength' => 6,
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('Enter pincode....'),
      ],
    ];

    $form['hr'] = [
      '#type' => 'item',
      '#markup' => '<hr>',
    ];

    $form['version'] = [
      '#type' => 'select',
      '#title' => $this->t('DWSIM Version'),
      '#options' => $this->labGlobal->_lm_list_of_software_version(),
      '#required' => TRUE,
    ];

    $form['older'] = [
      '#type' => 'textfield',
      '#size' => 30,
      '#maxlength' => 50,
      '#description' => $this->t('Specify the Older version used'),
      '#states' => [
        'visible' => [
          ':input[name="version"]' => [
            'value' => 'Others',
          ],
        ],
      ],
    ];

    $form['lab_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title of the Lab'),
      '#size' => 100,
      '#required' => TRUE,
    ];

    for ($counter = 1; $counter <= 15; $counter++) {
      $required = ($counter <= 1);
      $form['lab_experiment-' . $counter] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title of the Experiment @num', ['@num' => $counter]),
        '#size' => 100,
        '#required' => $required,
      ];

      $namefield = "lab_experiment-" . $counter;
      $form['lab_experiment_description-' . $counter] = [
        '#type' => 'textarea',
        '#required' => $required,
        '#attributes' => [
          'placeholder' => $this->t('Enter Description for your experiment @num', ['@num' => $counter]),
          'cols' => 50,
          'rows' => 4,
        ],
        '#title' => $this->t('Description for Experiment @num', ['@num' => $counter]),
        '#states' => [
          'invisible' => [
            ':input[name="' . $namefield . '"]' => [
              'value' => "",
            ],
          ],
        ],
      ];
    }

    $form['solution_provider_uid'] = [
      '#type' => 'radios',
      '#title' => $this->t('Do you want to provide the solution'),
      '#options' => [
        '1' => 'Yes',
        '2' => 'No',
      ],
      '#required' => TRUE,
      '#default_value' => '1',
      '#description' => $this->t('If you do not want to provide the solution then it will be opened for the community, anyone may come forward and provide the solution.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!preg_match('/^[0-9\ \+]{0,15}$/', $form_state->getValue('contact_ph'))) {
      $form_state->setErrorByName('contact_ph', $this->t('Invalid contact phone number'));
    }

    if ($form_state->getValue('country') == 'Others') {
      if ($form_state->getValue('other_country') == '') {
        $form_state->setErrorByName('other_country', $this->t('Enter country name'));
      }
      else {
        $form_state->setValue('country', $form_state->getValue('other_country'));
      }

      if ($form_state->getValue('other_state') == '') {
        $form_state->setErrorByName('other_state', $this->t('Enter state name'));
      }
      else {
        $form_state->setValue('all_state', $form_state->getValue('other_state'));
      }

      if ($form_state->getValue('other_city') == '') {
        $form_state->setErrorByName('other_city', $this->t('Enter city name'));
      }
      else {
        $form_state->setValue('city', $form_state->getValue('other_city'));
      }
    }
    else {
      if ($form_state->getValue('country') == '') {
        $form_state->setErrorByName('country', $this->t('Select country name'));
      }
      if ($form_state->getValue('all_state') == '') {
        $form_state->setErrorByName('all_state', $this->t('Select state name'));
      }
      if ($form_state->getValue('city') == '') {
        $form_state->setErrorByName('city', $this->t('Select city name'));
      }
    }

    for ($counter = 1; $counter <= 15; $counter++) {
      $experiment_field_name = 'lab_experiment-' . $counter;
      $experiment_description = 'lab_experiment_description-' . $counter;
      if (strlen(trim($form_state->getValue($experiment_field_name))) >= 1) {
        if (strlen(trim($form_state->getValue($experiment_description))) <= 49) {
          $form_state->setErrorByName($experiment_description, $this->t('Description should be minimum of 50 characters'));
        }
      }
    }

    if ($form_state->getValue('version') == 'olderversion') {
      if ($form_state->getValue('older') == '') {
        $form_state->setErrorByName('older', $this->t('Please provide valid version'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!$this->currentUser->id()) {
      $this->messenger->addError($this->t('It is mandatory to login on this website to access the proposal form'));
      return;
    }

    $solution_provider_uid = 0;
    $solution_status = 0;
    $solution_provider_name_title = '';
    $solution_provider_name = '';
    $solution_provider_contact_ph = '';
    $solution_provider_department = '';
    $solution_provider_university = '';

    if ($form_state->getValue('solution_provider_uid') == "1") {
      $solution_provider_uid = $this->currentUser->id();
      $solution_status = 1;
      $solution_provider_name_title = $form_state->getValue('name_title');
      $solution_provider_name = $form_state->getValue('name');
      $solution_provider_contact_ph = $form_state->getValue('contact_ph');
      $solution_provider_department = $form_state->getValue('department');
      $solution_provider_university = $form_state->getValue('university');
    }

    $solution_display = 1;

    if ($form_state->getValue('version') == 'olderversion') {
      $form_state->setValue('version', $form_state->getValue('older'));
    }

    $v = $form_state->getValues();
    $lab_title = $v['lab_title'];
    $proposar_name = $v['name_title'] . ' ' . $v['name'];
    $university = $v['university'];
    $directory_name = $this->labGlobal->_lm_dir_name($lab_title, $proposar_name, $university);

    $proposal_id = $this->database->insert('lab_migration_proposal')
      ->fields([
        'uid' => $this->currentUser->id(),
        'approver_uid' => 0,
        'name_title' => $v['name_title'],
        'name' => $v['name'],
        'contact_ph' => $v['contact_ph'],
        'department' => $v['department'],
        'university' => $v['university'],
        'city' => $v['city'],
        'pincode' => $v['pincode'],
        'state' => $v['all_state'],
        'country' => $v['country'],
        'version' => $form_state->getValue('version'),
        'lab_title' => $v['lab_title'],
        'approval_status' => 0,
        'solution_status' => $solution_status,
        'solution_provider_uid' => $solution_provider_uid,
        'solution_display' => $solution_display,
        'creation_date' => time(),
        'approval_date' => 0,
        'solution_date' => 0,
        'solution_provider_name_title' => $solution_provider_name_title,
        'solution_provider_name' => $solution_provider_name,
        'solution_provider_contact_ph' => $solution_provider_contact_ph,
        'solution_provider_department' => $solution_provider_department,
        'solution_provider_university' => $solution_provider_university,
        'directory_name' => $directory_name,
      ])
      ->execute();

    if (!$proposal_id) {
      $this->messenger->addError($this->t('Error receiving your proposal. Please try again.'));
      return;
    }

    $root_path = $this->labGlobal->lab_migration_path();
    $dest_path = $proposal_id . '/';
    if (!is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path);
    }

    $number = 1;
    for ($counter = 1; $counter <= 15; $counter++) {
      $experiment_field_name = 'lab_experiment-' . $counter;
      $experiment_description = 'lab_experiment_description-' . $counter;

      if (strlen(trim($form_state->getValue($experiment_field_name))) >= 1) {
        $this->database->insert('lab_migration_experiment')
          ->fields([
            'proposal_id' => $proposal_id,
            'number' => $number,
            'title' => trim($form_state->getValue($experiment_field_name)),
            'description' => trim($form_state->getValue($experiment_description)),
          ])
          ->execute();
        $number++;
      }
    }

    $email_to = $this->currentUser->getEmail();
    $config = $this->configFactory()->get('lab_migration.settings');
    $from = $config->get('lab_migration_from_email');
    $bcc = $config->get('lab_migration_emails');
    $cc = $config->get('lab_migration_cc_emails');

    $params['proposal_received']['proposal_id'] = $proposal_id;
    $params['proposal_received']['user_id'] = $this->currentUser->id();
    $params['proposal_received']['headers'] = [
      'From' => $from,
      'Cc' => $cc,
      'Bcc' => $bcc,
    ];

    if ($this->mailService->sendMail('lab_migration', 'proposal_received', $email_to, $params)) {
      $this->messenger->addMessage($this->t('Email notification sent.'));
    }

    $this->messenger->addMessage($this->t('We have received your Lab migration proposal. We will get back to you soon.'));
    $form_state->setRedirect('<front>');
  }

}