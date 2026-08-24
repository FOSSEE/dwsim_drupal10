<?php

/**
 * @file
 * Contains \Drupal\lab_migration\Form\LabMigrationProposalApprovalForm.
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
use Drupal\user\Entity\User;

class LabMigrationProposalApprovalForm extends FormBase {

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
   * Constructs a new LabMigrationProposalApprovalForm object.
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
    return 'lab_migration_proposal_approval_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $route_match = $this->requestStack->getCurrentRequest()->attributes->get('_route_match');
    $proposal_id = $route_match ? (int) $route_match->getParameter('id') : 0;

    $proposal_data = $this->database->select('lab_migration_proposal', 'p')
      ->fields('p')
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
        Url::fromUserInput('/user/' . $proposal_data->uid)
      )->toString(),
      '#title' => $this->t('Name'),
    ];

    $proposal_user = $this->entityTypeManager->getStorage('user')->load($proposal_data->uid);
    $form['email_id'] = [
      '#type' => 'item',
      '#markup' => $proposal_user ? $proposal_user->getEmail() : $this->t('Unknown'),
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

    $form['lab_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->lab_title,
      '#title' => $this->t('Title of the Lab'),
    ];

    /* get experiment details */
    $experiment_list = '<ul>';
    $experiment_q = $this->database->select('lab_migration_experiment', 'e')
      ->fields('e')
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
      '#title' => $this->t('Do you want to provide the solution'),
      '#markup' => $solution_provider,
    ];

    $form['approval'] = [
      '#type' => 'radios',
      '#title' => $this->t('Lab migration proposal'),
      '#options' => [
        '1' => $this->t('Approve'),
        '2' => $this->t('Disapprove'),
      ],
      '#required' => TRUE,
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Reason for disapproval'),
      '#attributes' => [
        'placeholder' => $this->t('Enter reason for disapproval in minimum 30 characters'),
        'cols' => 50,
        'rows' => 4,
      ],
      '#states' => [
        'visible' => [
          ':input[name="approval"]' => [
            'value' => '2',
          ],
        ],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    $form['cancel'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl(
        $this->t('Cancel'),
        Url::fromUri('internal:/lab-migration/manage-proposal/pending')
      )->toString(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('approval') == 2) {
      if (trim($form_state->getValue('message')) == '') {
        $form_state->setErrorByName('message', $this->t('Reason for disapproval cannot be empty.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $route_match = $this->requestStack->getCurrentRequest()->attributes->get('_route_match');
    $proposal_id = $route_match ? (int) $route_match->getParameter('id') : 0;

    $proposal_data = $this->database->select('lab_migration_proposal', 'p')
      ->fields('p')
      ->condition('id', $proposal_id)
      ->execute()
      ->fetchObject();

    if (!$proposal_data) {
      $this->messenger->addError($this->t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirect('lab_migration.proposal_pending');
      return;
    }

    if ($form_state->getValue('approval') == 1) {
      $this->database->update('lab_migration_proposal')
        ->fields([
          'approver_uid' => $this->currentUser->id(),
          'approval_date' => time(),
          'approval_status' => 1,
          'solution_status' => 2,
        ])
        ->condition('id', $proposal_id)
        ->execute();

      // Send email.
      $config = $this->configFactory()->get('lab_migration.settings');
      $user_data = $this->entityTypeManager->getStorage('user')->load($proposal_data->uid);
      if ($user_data) {
        $email_to = $user_data->getEmail();
        $from = $config->get('lab_migration_from_email');
        $bcc = $config->get('lab_migration_emails');
        $cc = $config->get('lab_migration_cc_emails');

        $params['proposal_approved']['proposal_id'] = $proposal_id;
        $params['proposal_approved']['user_id']     = $proposal_data->uid;
        $params['proposal_approved']['headers'] = [
          'From' => $from,
          'Cc' => $cc,
          'Bcc' => $bcc,
        ];

        if ($this->mailService->sendMail('lab_migration', 'proposal_approved', $email_to, $params)) {
          $this->messenger->addMessage($this->t('Mail sent successfully.'));
        }
      }

      $this->messenger->addMessage($this->t('Lab migration proposal No. @id approved. User has been notified of the approval.', ['@id' => $proposal_id]), 'status');
      $form_state->setRedirect('lab_migration.proposal_pending');
    }
    else {
      if ($form_state->getValue('approval') == 2) {
        $reason = $form_state->getValue('message');
        $this->database->update('lab_migration_proposal')
          ->fields([
            'approver_uid' => $this->currentUser->id(),
            'approval_date' => time(),
            'approval_status' => 2,
            'message' => $reason,
            'solution_provider_uid' => 0,
            'solution_status' => 0,
          ])
          ->condition('id', $proposal_id)
          ->execute();

        // Send email.
        $config = $this->configFactory()->get('lab_migration.settings');
        $user_data = $this->entityTypeManager->getStorage('user')->load($proposal_data->uid);
        if ($user_data) {
          $email_to = $user_data->getEmail();
          $from = $config->get('lab_migration_from_email');
          $bcc = $config->get('lab_migration_emails');
          $cc = $config->get('lab_migration_cc_emails');

          $params['proposal_disapproved']['proposal_id'] = $proposal_id;
          $params['proposal_disapproved']['user_id']     = $proposal_data->uid;
          $params['proposal_disapproved']['headers'] = [
            'From' => $from,
            'Cc' => $cc,
            'Bcc' => $bcc,
          ];

          if ($this->mailService->sendMail('lab_migration', 'proposal_disapproved', $email_to, $params)) {
            $this->messenger->addMessage($this->t('Mail sent successfully.'));
          }
        }

        $this->messenger->addMessage($this->t('Lab migration proposal No. @id disapproved. User has been notified of the disapproval.', ['@id' => $proposal_id]), 'error');
        $form_state->setRedirect('lab_migration.proposal_pending');
      }
    }
  }

}