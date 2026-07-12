<?php

/**
 * @file
 * Contains \Drupal\lab_migration\Form\LabMigrationProposalStatusForm.
 */

namespace Drupal\lab_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\lab_migration\Services\MailService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

class LabMigrationProposalStatusForm extends FormBase {

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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The mail service.
   *
   * @var \Drupal\lab_migration\Services\MailService
   */
  protected $mailService;

  /**
   * Constructs a new LabMigrationProposalStatusForm object.
   */
  public function __construct(
    Connection $database,
    MessengerInterface $messenger,
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    RequestStack $request_stack,
    MailService $mail_service
  ) {
    $this->database = $database;
    $this->messenger = $messenger;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $request_stack;
    $this->mailService = $mail_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('messenger'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('request_stack'),
      $container->get('lab_migration.mail_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lab_migration_proposal_status_form';
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
      $this->messenger->addError($this->t('Invalid proposal selected. Please try again.'));
      return $form;
    }

    $form['name'] = [
      '#type' => 'item',
      '#markup' => Link::fromTextAndUrl(
        $proposal_data->name_title . ' ' . $proposal_data->name,
        Url::fromRoute('entity.user.canonical', ['user' => $proposal_data->uid])
      )->toString(),
      '#title' => $this->t('Name'),
    ];

    $proposal_user = $this->entityTypeManager->getStorage('user')->load($proposal_data->uid);
    $form['email_id'] = [
      '#type' => 'item',
      '#markup' => $proposal_user ? $proposal_user->getEmail() : $this->t('Not available'),
      '#title' => $this->t('Email'),
    ];

    $form['contact_ph'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->contact_ph,
      '#title' => $this->t('Contact No.'),
    ];

    $form['department'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->department,
      '#title' => $this->t('Department/Branch'),
    ];

    $form['university'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->university,
      '#title' => $this->t('University/Institute'),
    ];

    $form['country'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->country,
      '#title' => $this->t('Country'),
    ];

    $form['all_state'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->state,
      '#title' => $this->t('State'),
    ];

    $form['city'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->city,
      '#title' => $this->t('City'),
    ];

    $form['pincode'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->pincode,
      '#title' => $this->t('Pincode/Postal code'),
    ];

    $form['operating_system'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->operating_system,
      '#title' => $this->t('Operating System'),
    ];

    $form['syllabus_link'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->syllabus_link,
      '#title' => $this->t('Syllabus Link'),
    ];

    $form['lab_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->lab_title,
      '#title' => $this->t('Title of the Lab'),
    ];

    /* get experiment details */
    $experiment_list = '<ul>';
    $experiment_q = $this->database->select('lab_migration_experiment')
      ->fields('lab_migration_experiment')
      ->condition('proposal_id', $proposal_id)
      ->orderBy('id', 'ASC')
      ->execute();

    while ($experiment_data = $experiment_q->fetchObject()) {
      $experiment_list .= '<li>' . htmlspecialchars($experiment_data->title) . '</li>Description of Experiment : ' . htmlspecialchars($experiment_data->description) . '<br>';
    }
    $experiment_list .= '</ul>';

    $form['experiment'] = [
      '#type' => 'item',
      '#markup' => $experiment_list,
      '#title' => $this->t('Experiments'),
    ];

    if ($proposal_data->solution_provider_uid == 0) {
      $solution_provider = $this->t("User will not provide solution, we will have to provide solution");
    }
    else {
      if ($proposal_data->solution_provider_uid == $proposal_data->uid) {
        $solution_provider = $this->t("Proposer will provide the solution of the lab");
      }
      else {
        $sol_user = $this->entityTypeManager->getStorage('user')->load($proposal_data->solution_provider_uid);
        if ($sol_user) {
          $solution_provider = $this->t("Solution will be provided by user @user", ['@user' => $sol_user->getDisplayName()]);
        }
        else {
          $solution_provider = $this->t("User does not exist");
        }
      }
    }

    $form['solution_provider_uid'] = [
      '#type' => 'item',
      '#title' => $this->t('Who will provide the solution'),
      '#markup' => $solution_provider,
    ];

    $proposal_status = '';
    switch ($proposal_data->approval_status) {
      case 0:
        $proposal_status = $this->t('Pending');
        break;
      case 1:
        $proposal_status = $this->t('Approved');
        break;
      case 2:
        $proposal_status = $this->t('Dis-approved');
        break;
      case 3:
        $proposal_status = $this->t('Completed');
        break;
      default:
        $proposal_status = $this->t('Unknown');
        break;
    }

    $form['proposal_status'] = [
      '#type' => 'item',
      '#markup' => $proposal_status,
      '#title' => $this->t('Proposal Status'),
    ];

    if ($proposal_data->approval_status == 0 || $proposal_data->approval_status == 1) {
      if ($proposal_data->expected_completion_date == 0) {
        $form['completion_date'] = [
          '#type' => 'item',
          '#markup' => $this->t('Expecting date of completion soon'),
          '#title' => ($proposal_data->approval_status == 0) ? $this->t('Expected date of completion') : $this->t('Date of Completion'),
        ];
      }
      else {
        $form['completion_date'] = [
          '#type' => 'item',
          '#markup' => date('d-m-Y', $proposal_data->expected_completion_date),
          '#title' => ($proposal_data->approval_status == 0) ? $this->t('Expected date of completion') : $this->t('Date of Completion'),
        ];
      }
    }

    if ($proposal_data->approval_status == 0) {
      $form['approve'] = [
        '#type' => 'item',
        '#markup' => Link::fromTextAndUrl(
          $this->t('Click here to approve'),
          Url::fromRoute('lab_migration.proposal_approval_form', ['id' => $proposal_id])
        )->toString(),
        '#title' => $this->t('Approve'),
      ];
    }

    if ($proposal_data->approval_status == 1) {
      $form['completed'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Completed'),
        '#description' => $this->t('Check if user has provided all experiment solutions.'),
      ];
    }

    if ($proposal_data->approval_status == 2) {
      $form['message'] = [
        '#type' => 'item',
        '#markup' => $proposal_data->message,
        '#title' => $this->t('Reason for disapproval'),
      ];
    }

    if ($proposal_data->approval_status == 3) {
      $form['completion_date'] = [
        '#type' => 'item',
        '#markup' => date('d-m-Y', $proposal_data->expected_completion_date),
        '#title' => $this->t('Date of Completion'),
      ];
    }

    if ($proposal_data->approval_status == 2) {
      $form['completion_date'] = [
        '#type' => 'item',
        '#markup' => $this->t('Proposal is disapproved'),
        '#title' => $this->t('Date of Completion'),
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    $form['cancel'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl(
        $this->t('Cancel'),
        Url::fromRoute('lab_migration.proposal_all')
      )->toString(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $route_match = $this->requestStack->getCurrentRequest()->attributes->get('_route_match');
    $proposal_id = $route_match ? (int) $route_match->getParameter('id') : 0;

    $proposal_data = $this->database->select('lab_migration_proposal')
      ->fields('lab_migration_proposal')
      ->condition('id', $proposal_id)
      ->execute()
      ->fetchObject();

    if (!$proposal_data) {
      $this->messenger->addError($this->t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirect('lab_migration.proposal_all');
      return;
    }

    if ($form_state->getValue('completed') == 1) {
      $this->database->update('lab_migration_proposal')
        ->fields([
          'approval_status' => 3,
          'expected_completion_date' => time(),
        ])
        ->condition('id', $proposal_id)
        ->execute();

      $user_data = $this->entityTypeManager->getStorage('user')->load($proposal_data->uid);
      if ($user_data && $user_data->getEmail()) {
        $email_to = $user_data->getEmail();
        $config = $this->configFactory()->get('lab_migration.settings');
        $from = $config->get('lab_migration_from_email');
        $bcc = $this->currentUser->getEmail() . ', ' . $config->get('lab_migration_emails');
        $cc = $config->get('lab_migration_cc_emails');

        $params['proposal_completed']['proposal_id'] = $proposal_id;
        $params['proposal_completed']['user_id']     = $proposal_data->uid;
        $params['proposal_completed']['headers'] = [
          'From' => $from,
          'Cc' => $cc,
          'Bcc' => $bcc,
        ];

        if ($this->mailService->sendMail('lab_migration', 'proposal_completed', $email_to, $params)) {
          $this->messenger->addMessage($this->t('Mail sent successfully.'));
        }
      }

      $this->messenger->addMessage($this->t('Congratulations! Lab Migration proposal has been marked as completed. User has been notified of the completion.'), 'status');
      $form_state->setRedirect('lab_migration.proposal_all');
    }
  }

}