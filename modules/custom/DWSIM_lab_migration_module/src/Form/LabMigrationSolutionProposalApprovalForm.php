<?php

/**
 * @file
 * Contains \Drupal\lab_migration\Form\LabMigrationSolutionProposalApprovalForm.
 */

namespace Drupal\lab_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\lab_migration\Services\MailService;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Form\EnforcedFormResponseException;

class LabMigrationSolutionProposalApprovalForm extends FormBase {

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
   * Constructs a new LabMigrationSolutionProposalApprovalForm object.
   */
  public function __construct(
    Connection $database,
    MessengerInterface $messenger,
    AccountProxyInterface $current_user,
    MailService $mail_service,
    RequestStack $request_stack
  ) {
    $this->database = $database;
    $this->messenger = $messenger;
    $this->currentUser = $current_user;
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
      $container->get('lab_migration.mail_service'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lab_migration_solution_proposal_approval_form';
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
      $this->messenger->addError($this->t('Invalid proposal selected. Please try again.'));
      $response = new RedirectResponse(Url::fromRoute('lab_migration.solution_proposal_pending')->toString());
      throw new EnforcedFormResponseException($response);
    }

    $form['name'] = [
      '#type' => 'item',
      '#markup' => Link::fromTextAndUrl(
        $proposal_data->solution_provider_name_title . ' ' . $proposal_data->solution_provider_name,
        Url::fromUri('internal:/user/' . $proposal_data->solution_provider_uid)
      )->toString(),
      '#title' => $this->t('Solution Provider Name'),
    ];

    $solution_provider_user = User::load($proposal_data->solution_provider_uid);
    $form['email_id'] = [
      '#type' => 'item',
      '#markup' => $solution_provider_user ? $solution_provider_user->getEmail() : '',
      '#title' => $this->t('Solution Provider Email'),
    ];

    $form['contact_ph'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->solution_provider_contact_ph,
      '#title' => $this->t('Solution Provider Contact No.'),
    ];

    $form['department'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->solution_provider_department,
      '#title' => $this->t('Department/Branch'),
    ];

    $form['university'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->solution_provider_university,
      '#title' => $this->t('University/Institute'),
    ];

    $form['country'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->solution_provider_country,
      '#title' => $this->t('Country'),
    ];

    $form['all_state'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->solution_provider_state,
      '#title' => $this->t('State'),
    ];

    $form['city'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->solution_provider_city,
      '#title' => $this->t('City'),
    ];

    $form['pincode'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->solution_provider_pincode,
      '#title' => $this->t('Pincode/Postal code'),
    ];

    $form['dwsim_version'] = [
      '#type' => 'item',
      '#title' => $this->t('dwsim version used'),
      '#markup' => $proposal_data->dwsim_version,
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
      $experiment_list .= '<li>' . $experiment_data->title . '</li>Description of Experiment : ' . $experiment_data->description . '<br>';
    }
    $experiment_list .= '</ul>';

    $form['experiment'] = [
      '#type' => 'item',
      '#markup' => $experiment_list,
      '#title' => $this->t('Experiments'),
    ];

    $form['solution_display'] = [
      '#type' => 'item',
      '#title' => $this->t('Display the solution on the website'),
      '#markup' => ($proposal_data->solution_display == 1) ? $this->t('Yes') : $this->t('No'),
    ];

    $proposer_user = User::load($proposal_data->uid);
    $proposer_email = $proposer_user ? $proposer_user->getEmail() : '';

    $proposer = '<ul>' . '<li><strong>Proposer:</strong> ' . Link::fromTextAndUrl($proposal_data->name, Url::fromUri('internal:/user/' . $proposal_data->uid))->toString() . '</li>' . '<li><strong>Proposer Name:</strong> ' . $proposal_data->name_title . ' ' . $proposal_data->name . '</li>' . '<li><strong>Contact No:</strong> ' . $proposal_data->contact_ph . '</li>' . '<li><strong>Email:</strong> ' . $proposer_email . '</li>' . '<li><strong>Department:</strong> ' . $proposal_data->department . '</li>' . '<li><strong>University:</strong> ' . $proposal_data->university . '</li>' . '<li><strong>Country:</strong> ' . $proposal_data->country . '</li>' . '<li><strong>State:</strong> ' . $proposal_data->state . '</li>' . '<li><strong>City:</strong> ' . $proposal_data->city . '</li>' . '<li><strong>Pincode:</strong> ' . $proposal_data->pincode . '</li>' . '</ul>';

    $form['proposer_details'] = [
      '#type' => 'item',
      '#title' => $this->t('Proposer of Lab :'),
      '#markup' => $proposer,
    ];

    $form['approval'] = [
      '#type' => 'radios',
      '#title' => $this->t('Solution Provider'),
      '#options' => [
        '1' => $this->t('Approve'),
        '2' => $this->t('Disapprove'),
      ],
      '#required' => TRUE,
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Reason for disapproval'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    $form['cancel'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl($this->t('Cancel'), Url::fromRoute('lab_migration.solution_proposal_pending'))->toString(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $route_match = $this->requestStack->getCurrentRequest()->attributes->get('_route_match');
    $proposal_id = $route_match ? (int) $route_match->getParameter('proposal_id') : 0;

    $solution_provider_data = $this->database->select('lab_migration_proposal')
      ->fields('lab_migration_proposal')
      ->condition('id', $proposal_id)
      ->execute()
      ->fetchObject();

    if ($solution_provider_data) {
      $solution_provider_present_q = $this->database->select('lab_migration_proposal')
        ->fields('lab_migration_proposal')
        ->condition('solution_provider_uid', $solution_provider_data->uid)
        ->condition('approval_status', [0, 1], 'IN')
        ->condition('id', $proposal_id, '<>')
        ->execute();

      if ($solution_provider_present_q->fetchObject()) {
        $form_state->setErrorByName('', $this->t('Solution provider already has one active proposal.'));
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
      $this->messenger->addError($this->t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirect('lab_migration.solution_proposal_pending');
      return;
    }

    $user_data = User::load($proposal_data->solution_provider_uid);
    if (!$user_data) {
      $this->messenger->addError($this->t('Solution provider user account does not exist.'));
      $form_state->setRedirect('lab_migration.solution_proposal_pending');
      return;
    }

    $config = $this->configFactory()->get('lab_migration.settings');
    $from = $config->get('lab_migration_from_email');
    $bcc = $this->currentUser->getEmail() . ', ' . $config->get('lab_migration_emails');
    $cc = $config->get('lab_migration_cc_emails');

    if ($form_state->getValue('approval') == 1) {
      $this->database->update('lab_migration_proposal')
        ->fields(['solution_status' => 2])
        ->condition('id', $proposal_id)
        ->execute();

      /* sending email */
      $email_to = $user_data->getEmail();
      $params['solution_proposal_approved']['proposal_id'] = $proposal_id;
      $params['solution_proposal_approved']['user_id'] = $proposal_data->solution_provider_uid;
      $params['solution_proposal_approved']['headers'] = [
        'From' => $from,
        'Cc' => $cc,
        'Bcc' => $bcc,
      ];

      if ($this->mailService->sendMail('lab_migration', 'solution_proposal_approved', $email_to, $params)) {
        $this->messenger->addMessage($this->t('Mail notification sent successfully.'));
      }

      $this->messenger->addMessage($this->t('Lab migration solution proposal approved. User has been notified of the approval.'), 'status');
    }
    elseif ($form_state->getValue('approval') == 2) {
      $this->database->update('lab_migration_proposal')
        ->fields([
          'solution_provider_uid' => 0,
          'solution_status' => 0,
          'solution_provider_name_title' => '',
          'solution_provider_name' => '',
          'solution_provider_contact_ph' => '',
          'solution_provider_department' => '',
          'solution_provider_university' => '',
        ])
        ->condition('id', $proposal_id)
        ->execute();

      /* sending email */
      $email_to = $user_data->getEmail();
      $params['solution_proposal_disapproved']['proposal_id'] = $proposal_id;
      $params['solution_proposal_disapproved']['user_id'] = $proposal_data->solution_provider_uid;
      $params['solution_proposal_disapproved']['message'] = $form_state->getValue('message');
      $params['solution_proposal_disapproved']['headers'] = [
        'From' => $from,
        'Cc' => $cc,
        'Bcc' => $bcc,
      ];

      if ($this->mailService->sendMail('lab_migration', 'solution_proposal_disapproved', $email_to, $params)) {
        $this->messenger->addMessage($this->t('Mail notification sent successfully.'));
      }

      $this->messenger->addMessage($this->t('Lab migration solution proposal dis-approved. User has been notified of the dis-approval.'), 'status');
    }

    $form_state->setRedirect('lab_migration.solution_proposal_pending');
  }

}