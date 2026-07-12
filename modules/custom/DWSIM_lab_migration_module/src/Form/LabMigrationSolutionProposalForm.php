<?php

/**
 * @file
 * Contains \Drupal\lab_migration\Form\LabMigrationSolutionProposalForm.
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

class LabMigrationSolutionProposalForm extends FormBase {

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
   * Constructs a new LabMigrationSolutionProposalForm object.
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
    return 'lab_migration_solution_proposal_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $route_match = $this->requestStack->getCurrentRequest()->attributes->get('_route_match');
    $proposal_id = $route_match ? (int) $route_match->getParameter('id') : 0;

    $proposal_data = $this->database->select('lab_migration_proposal')
      ->fields('lab_migration_proposal')
      ->condition('id', $proposal_id)
      ->execute()
      ->fetchObject();

    if (!$proposal_data) {
      $this->messenger->addError($this->t("Invalid proposal."));
      $response = new RedirectResponse(Url::fromRoute('lab_migration.proposal_open')->toString());
      throw new EnforcedFormResponseException($response);
    }

    $proposer_link = Link::fromTextAndUrl(
      $proposal_data->name_title . ' ' . $proposal_data->name,
      Url::fromUri('internal:/user/' . $proposal_data->uid)
    )->toRenderable();

    $form['name'] = [
      '#type' => 'item',
      '#title' => $this->t('Proposer Name'),
      'link' => $proposer_link,
    ];

    $form['lab_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->lab_title,
      '#title' => $this->t('Title of the Lab'),
    ];

    $experiment_html = '';
    $experiment_q = $this->database->select('lab_migration_experiment')
      ->fields('lab_migration_experiment')
      ->condition('proposal_id', $proposal_id)
      ->execute();

    while ($experiment_data = $experiment_q->fetchObject()) {
      $experiment_html .= $experiment_data->title . '<br>';
    }

    $form['experiment'] = [
      '#type' => 'item',
      '#markup' => $experiment_html,
      '#title' => $this->t('Experiment List'),
    ];

    $form['solution_provider_name_title'] = [
      '#type' => 'select',
      '#title' => $this->t('Title'),
      '#options' => [
        'Mr' => $this->t('Mr'),
        'Ms' => $this->t('Ms'),
        'Mrs' => $this->t('Mrs'),
        'Dr' => $this->t('Dr'),
        'Prof' => $this->t('Prof'),
      ],
      '#required' => TRUE,
    ];

    $form['solution_provider_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name of the Solution Provider'),
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
    ];

    $user_email = '';
    $user = User::load($this->currentUser->id());
    if ($user) {
      $user_email = $user->getEmail();
    }

    $form['solution_provider_email_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email'),
      '#size' => 30,
      '#value' => $user_email,
      '#disabled' => TRUE,
    ];

    $form['solution_provider_contact_ph'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Contact No.'),
      '#size' => 30,
      '#maxlength' => 15,
      '#required' => TRUE,
    ];

    $form['solution_provider_department'] = [
      '#type' => 'select',
      '#title' => $this->t('Department/Branch'),
      '#options' => $this->labGlobal->_lm_list_of_departments(),
      '#required' => TRUE,
    ];

    $form['solution_provider_university'] = [
      '#type' => 'textfield',
      '#title' => $this->t('University/Institute'),
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
    ];

    $form['country'] = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#options' => [
        'India' => $this->t('India'),
        'Others' => $this->t('Others'),
      ],
      '#required' => TRUE,
      '#tree' => TRUE,
      '#validated' => TRUE,
    ];

    $form['other_country'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other than India'),
      '#size' => 100,
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
      '#size' => 100,
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
      '#size' => 100,
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
      '#required' => FALSE,
      '#attributes' => [
        'placeholder' => $this->t('Enter pincode....'),
      ],
    ];

    $form['version'] = [
      '#type' => 'select',
      '#title' => $this->t('Version'),
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
            'value' => 'olderversion',
          ],
        ],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply for Solution'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
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

    if ($form_state->getValue('version') == 'olderversion') {
      if ($form_state->getValue('older') == '') {
        $form_state->setErrorByName('older', $this->t('Please provide valid version'));
      }
    }

    $solution_provider_q = $this->database->select('lab_migration_proposal')
      ->fields('lab_migration_proposal')
      ->condition('solution_provider_uid', $this->currentUser->id())
      ->condition('approval_status', [0, 1], 'IN')
      ->condition('solution_status', [0, 1, 2], 'IN')
      ->execute();

    if ($solution_provider_q->fetchObject()) {
      $form_state->setErrorByName('', $this->t("You have already applied for a solution. Please complete that before applying for another solution."));
      $response = new RedirectResponse(Url::fromRoute('lab_migration.proposal_open')->toString());
      throw new EnforcedFormResponseException($response);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $route_match = $this->requestStack->getCurrentRequest()->attributes->get('_route_match');
    $proposal_id = $route_match ? (int) $route_match->getParameter('id') : 0;

    if ($form_state->getValue('version') == 'olderversion') {
      $form_state->setValue('version', $form_state->getValue('older'));
    }

    $proposal_data = $this->database->select('lab_migration_proposal')
      ->fields('lab_migration_proposal')
      ->condition('id', $proposal_id)
      ->execute()
      ->fetchObject();

    if (!$proposal_data) {
      $this->messenger->addError($this->t("Invalid proposal."));
      $form_state->setRedirect('lab_migration.proposal_open');
      return;
    }

    if ($proposal_data->solution_provider_uid != 0) {
      $this->messenger->addError($this->t("Someone has already applied for solving this Lab."));
      $form_state->setRedirect('lab_migration.proposal_open');
      return;
    }

    $this->database->update('lab_migration_proposal')
      ->fields([
        'solution_provider_uid' => $this->currentUser->id(),
        'solution_status' => 1,
        'solution_provider_name_title' => $form_state->getValue('solution_provider_name_title'),
        'solution_provider_name' => $form_state->getValue('solution_provider_name'),
        'solution_provider_contact_ph' => $form_state->getValue('solution_provider_contact_ph'),
        'solution_provider_department' => $form_state->getValue('solution_provider_department'),
        'solution_provider_university' => $form_state->getValue('solution_provider_university'),
        'version' => $form_state->getValue('version'),
      ])
      ->condition('id', $proposal_id)
      ->execute();

    $this->messenger->addMessage($this->t("We have received your application. We will get back to you soon."), 'status');

    $user = User::load($this->currentUser->id());
    if ($user && $user->getEmail()) {
      $email_to = $user->getEmail();
      $config = $this->configFactory()->get('lab_migration.settings');
      $from = $config->get('lab_migration_from_email');
      $bcc  = $config->get('lab_migration_emails');
      $cc   = $config->get('lab_migration_cc_emails');

      if (empty($from)) {
        $from = $this->configFactory()->get('system.site')->get('mail');
      }

      $params['solution_proposal_received'] = [
        'proposal_id' => $proposal_id,
        'user_id' => $user->id(),
        'headers' => [
          'From' => $from,
          'Cc' => $cc,
          'Bcc' => $bcc,
        ],
      ];

      if ($this->mailService->sendMail('lab_migration', 'solution_proposal_received', $email_to, $params)) {
        $this->messenger->addMessage($this->t('Confirmation email sent successfully.'));
      }

      $email_admin = $config->get('lab_migration_emails');
      if ($email_admin) {
        $this->mailService->sendMail('lab_migration', 'solution_proposal_received', $email_admin, $params);
      }
    }

    $form_state->setRedirect('lab_migration.proposal_open');
  }

}