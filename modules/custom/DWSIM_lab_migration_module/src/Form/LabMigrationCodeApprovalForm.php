<?php

/**
 * @file
 * Contains \Drupal\lab_migration\Form\LabMigrationCodeApprovalForm.
 */

namespace Drupal\lab_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\lab_migration\Services\LabMigrationGlobalfunction;
use Drupal\lab_migration\Services\MailService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

class LabMigrationCodeApprovalForm extends FormBase {

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
   * Constructs a new LabMigrationCodeApprovalForm object.
   */
  public function __construct(
    Connection $database,
    MessengerInterface $messenger,
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    RequestStack $request_stack,
    LabMigrationGlobalfunction $lab_global,
    MailService $mail_service
  ) {
    $this->database = $database;
    $this->messenger = $messenger;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $request_stack;
    $this->labGlobal = $lab_global;
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
      $container->get('lab_migration_global'),
      $container->get('lab_migration.mail_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lab_migration_code_approval_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $route_match = $this->requestStack->getCurrentRequest()->attributes->get('_route_match');
    $solution_id = $route_match ? (int) $route_match->getParameter('solution_id') : 0;

    $solution_data = $this->database->select('lab_migration_solution')
      ->fields('lab_migration_solution')
      ->condition('id', $solution_id)
      ->execute()
      ->fetchObject();

    if (!$solution_data) {
      $this->messenger->addMessage($this->t('Invalid solution selected.'), 'status');
      return $form;
    }

    if ($solution_data->approval_status == 1) {
      $this->messenger->addMessage($this->t('This solution has already been approved. Are you sure you want to change the approval status?'), 'error');
    }
    if ($solution_data->approval_status == 2) {
      $this->messenger->addMessage($this->t('This solution has already been dis-approved. Are you sure you want to change the approval status?'), 'error');
    }

    $experiment_data = $this->database->select('lab_migration_experiment')
      ->fields('lab_migration_experiment')
      ->condition('id', $solution_data->experiment_id)
      ->execute()
      ->fetchObject();

    $proposal_data = $this->database->select('lab_migration_proposal')
      ->fields('lab_migration_proposal')
      ->condition('id', $experiment_data->proposal_id)
      ->execute()
      ->fetchObject();

    $form['#tree'] = TRUE;

    $form['lab_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->lab_title,
      '#title' => $this->t('Title of the Lab'),
    ];

    $form['name'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->name,
      '#title' => $this->t('Contributor Name'),
    ];

    $form['experiment']['number'] = [
      '#type' => 'item',
      '#markup' => $experiment_data->number,
      '#title' => $this->t('Experiment Number'),
    ];

    $form['experiment']['title'] = [
      '#type' => 'item',
      '#markup' => $experiment_data->title,
      '#title' => $this->t('Title of the Experiment'),
    ];

    $form['back_to_list'] = [
      '#type' => 'item',
      '#markup' => Link::fromTextAndUrl(
        $this->t('Back to Code Approval List'),
        Url::fromRoute('lab_migration.code_approval')
      )->toString(),
    ];

    $form['code_number'] = [
      '#type' => 'item',
      '#markup' => $solution_data->code_number,
      '#title' => $this->t('Code No'),
    ];

    $form['code_caption'] = [
      '#type' => 'item',
      '#markup' => $solution_data->caption,
      '#title' => $this->t('Caption'),
    ];

    $solution_files_html = '';
    $solution_files_q = $this->database->select('lab_migration_solution_files', 'lmsf')
      ->fields('lmsf')
      ->condition('solution_id', $solution_id)
      ->orderBy('id', 'ASC')
      ->execute();

    if ($solution_files_q) {
      $filetype_map = [
        'S' => 'Source',
        'R' => 'Result',
        'X' => 'Xcox',
        'U' => 'Unknown',
      ];
      while ($solution_files_data = $solution_files_q->fetchObject()) {
        $code_file_type = $filetype_map[$solution_files_data->filetype] ?? 'Unknown';
        $url = Url::fromUri('internal:/lab-migration/download/solution/' . $solution_files_data->id);
        $link = Link::fromTextAndUrl($solution_files_data->filename, $url)->toString();
        $solution_files_html .= $link . ' (' . $code_file_type . ')<br/>';
      }
    }

    $form['solution_files'] = [
      '#type' => 'item',
      '#markup' => $solution_files_html ?: '<em>No files uploaded.</em>',
      '#title' => $this->t('Solution Files'),
    ];

    $form['approved'] = [
      '#type' => 'radios',
      '#options' => [
        '0' => 'Pending',
        '1' => 'Approved',
        '2' => 'Dis-approved (Solution will be deleted)',
      ],
      '#title' => $this->t('Approval'),
      '#default_value' => $solution_data->approval_status,
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Reason for dis-approval'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    $form['cancel'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl(
        $this->t('Cancel'),
        Url::fromRoute('lab_migration.code_approval')
      )->toString(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('approved') == 2) {
      if (strlen(trim($form_state->getValue('message'))) <= 30) {
        $form_state->setErrorByName('message', $this->t('Please mention the reason for disapproval.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $route_match = $this->requestStack->getCurrentRequest()->attributes->get('_route_match');
    $solution_id = $route_match ? (int) $route_match->getParameter('solution_id') : 0;

    $solution_data = $this->database->select('lab_migration_solution')
      ->fields('lab_migration_solution')
      ->condition('id', $solution_id)
      ->execute()
      ->fetchObject();

    if (!$solution_data) {
      $this->messenger->addMessage($this->t('Invalid solution selected.'), 'status');
      $form_state->setRedirect('lab_migration.code_approval');
      return;
    }

    $experiment_data = $this->database->select('lab_migration_experiment')
      ->fields('lab_migration_experiment')
      ->condition('id', $solution_data->experiment_id)
      ->execute()
      ->fetchObject();

    $proposal_data = $this->database->select('lab_migration_proposal')
      ->fields('lab_migration_proposal')
      ->condition('id', $experiment_data->proposal_id)
      ->execute()
      ->fetchObject();

    $user_data = $this->entityTypeManager->getStorage('user')->load($proposal_data->uid);

    if ($form_state->getValue('approved') == "0") {
      $this->database->update('lab_migration_solution')
        ->fields([
          'approval_status' => 0,
          'approver_uid' => $this->currentUser->id(),
          'approval_date' => time(),
        ])
        ->condition('id', $solution_id)
        ->execute();
    }
    elseif ($form_state->getValue('approved') == "1") {
      $this->database->update('lab_migration_solution')
        ->fields([
          'approval_status' => 1,
          'approver_uid' => $this->currentUser->id(),
          'approval_date' => time(),
        ])
        ->condition('id', $solution_id)
        ->execute();

      if ($user_data) {
        $email_to = $user_data->getEmail();
        $config = $this->configFactory()->get('lab_migration.settings');
        $from = $config->get('lab_migration_from_email');
        $bcc = $config->get('lab_migration_emails');
        $cc = $config->get('lab_migration_cc_emails');

        $param['solution_approved'] = [
          'solution_id' => $solution_id,
          'user_id' => $user_data->id(),
          'headers' => [
            'From' => $from,
            'Cc' => is_array($cc) ? implode(',', $cc) : $cc,
            'Bcc' => is_array($bcc) ? implode(',', $bcc) : $bcc,
          ],
        ];

        if ($this->mailService->sendMail('lab_migration', 'solution_approved', $email_to, $param)) {
          $this->messenger->addMessage($this->t('Mail sent successfully.'));
        }
      }
    }
    elseif ($form_state->getValue('approved') == "2") {
      if ($this->labGlobal->lab_migration_delete_solution($solution_id)) {
        if ($user_data) {
          $email_to = $user_data->getEmail();
          $config = $this->configFactory()->get('lab_migration.settings');
          $from = $config->get('lab_migration_from_email');
          $bcc = $config->get('lab_migration_emails');
          $cc = $config->get('lab_migration_cc_emails');

          $params = [
            'solution_id' => $proposal_data->id,
            'experiment_number' => $experiment_data->number,
            'experiment_title' => $experiment_data->title,
            'solution_number' => $solution_data->code_number,
            'solution_caption' => $solution_data->caption,
            'user_id' => $user_data->id(),
            'message' => $form_state->getValue('message'),
            'headers' => [
              'From' => $from,
              'Cc' => is_array($cc) ? implode(',', $cc) : $cc,
              'Bcc' => is_array($bcc) ? implode(',', $bcc) : $bcc,
            ],
          ];

          if ($this->mailService->sendMail('lab_migration', 'solution_disapproved', $email_to, $params)) {
            $this->messenger->addMessage($this->t('Mail sent successfully.'));
          }
          else {
            $this->messenger->addError($this->t('Mail sending failed.'));
          }
        }
      }
      else {
        $this->messenger->addError($this->t('Error disapproving and deleting solution. Please contact administrator.'));
      }
    }

    $this->messenger->addMessage($this->t('Updated successfully.'), 'status');
    $form_state->setRedirect('lab_migration.code_approval');
  }

}