<?php

namespace Drupal\custom_model\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\custom_model\Services\MailService;

/**
 * Bulk approval form for custom model abstract submissions.
 */
class CustomModelAbstractSubmissionBulkApprovalForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The mail service.
   *
   * @var \Drupal\custom_model\Services\MailService
   */
  protected $mailService;

  /**
   * Constructs the form with injected services.
   */
  public function __construct(
    Connection $connection,
    MessengerInterface $messenger,
    AccountInterface $currentUser,
    EntityTypeManagerInterface $entityTypeManager,
    MailService $mailService
  ) {
    $this->connection = $connection;
    $this->messenger = $messenger;
    $this->currentUser = $currentUser;
    $this->entityTypeManager = $entityTypeManager;
    $this->mailService = $mailService;
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
      $container->get('custom_model.mail_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_model_abstract_submission_bulk_approval_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = $this->_bulk_list_of_custom_model_proposals();
    $selected = $form_state->getValue('custom_model_proposals') ?? key($options);

    $form['custom_model_proposals'] = [
      '#type' => 'select',
      '#title' => $this->t('Title of the custom model'),
      '#options' => $options,
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => '::ajax_bulk_custom_model_abstract_details_callback',
        'wrapper' => 'ajax_selected_custom_model',
      ],
    ];

    $form['update_custom_model'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'ajax_selected_custom_model'],
      '#states' => [
        'invisible' => [
          ':input[name="custom_model_proposals"]' => [
            'value' => 0,
          ],
        ],
      ],
    ];

    $form['update_custom_model']['cm_details'] = [
      '#type' => 'markup',
      '#markup' => $this->_custom_model_details($form_state->getValue('custom_model_proposals')),
      '#states' => [
        'invisible' => [
          ':input[name="custom_model_proposals"]' => [
            'value' => 0,
          ],
        ],
      ],
    ];

    $form['custom_model_actions'] = [
      '#type' => 'select',
      '#title' => $this->t('Please select action for Custom Model project'),
      '#options' => $this->_bulk_list_custom_model_actions(),
      '#default_value' => 0,
      '#states' => [
        'invisible' => [
          ':input[name="custom_model_proposals"]' => [
            'value' => 0,
          ],
        ],
      ],
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Please specify the reason for marking resubmit/disapproval'),
      '#prefix' => '<div id="message_submit">',
      '#states' => [
        'visible' => [
          [':input[name="custom_model_actions"]' => ['value' => 2]],
          'or',
          [':input[name="custom_model_actions"]' => ['value' => 3]],
        ],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * AJAX callback: returns the detail wrapper to update on proposal selection.
   */
  public function ajax_bulk_custom_model_abstract_details_callback(array &$form, FormStateInterface $form_state) {
    return $form['update_custom_model'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $action = $form_state->getValue('custom_model_actions');
    $message_text = trim($form_state->getValue('message') ?? '');

    if (in_array($action, [2, 3]) && strlen($message_text) < 30) {
      $form_state->setErrorByName('message', $this->t('Please provide a reason of at least 30 characters.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $proposal_id = $form_state->getValue('custom_model_proposals');
    $action = $form_state->getValue('custom_model_actions');
    $message_text = trim($form_state->getValue('message') ?? '');

    // Access check.
    if (!$proposal_id || !$this->currentUser->hasPermission('custom model bulk manage submission')) {
      $this->messenger->addError($this->t('Access denied or invalid proposal.'));
      return;
    }

    // Load proposal.
    $proposal = $this->connection->select('custom_model_proposal', 'cmp')
      ->fields('cmp')
      ->condition('id', $proposal_id)
      ->execute()
      ->fetchObject();

    if (!$proposal) {
      $this->messenger->addError($this->t('Proposal not found.'));
      return;
    }

    // Load user.
    $user = $this->entityTypeManager->getStorage('user')->load($proposal->uid);
    $email_to = $user ? $user->getEmail() : '';

    if ($action == 1) {
      // Approve all submitted abstracts and their files.
      $abstracts = $this->connection->select('custom_model_submitted_abstracts', 'a')
        ->fields('a')
        ->condition('proposal_id', $proposal_id)
        ->execute();

      foreach ($abstracts as $abstract) {
        $this->connection->update('custom_model_submitted_abstracts')
          ->fields([
            'abstract_approval_status' => 1,
            'is_submitted' => 1,
            'approver_uid' => $this->currentUser->id(),
          ])
          ->condition('id', $abstract->id)
          ->execute();

        $this->connection->update('custom_model_submitted_abstracts_file')
          ->fields([
            'file_approval_status' => 1,
            'approvar_uid' => $this->currentUser->id(),
          ])
          ->condition('submitted_abstract_id', $abstract->id)
          ->execute();
      }

      $this->messenger->addStatus($this->t('Approved Custom Model project.'));

      // Send approval email using MailService.
      if ($email_to) {
        $subject = $this->t('[Custom Model] Your uploaded Custom Model has been approved');
        $body = $this->t("Dear @name,\n\nYour uploaded abstract for the Custom Model has been approved:\n\nTitle of Custom Model: @title\n\nBest Wishes,\nFOSSEE, IIT Bombay", [
          '@name' => $proposal->contributor_name,
          '@title' => $proposal->project_title,
        ]);
        $this->mailService->sendNotification('custom_model', 'custom_model_abstract_approved', $email_to, $subject, [$body]);
      }

    }
    elseif ($action == 2) {
      // Resubmit: mark abstracts and files as pending.
      $abstracts = $this->connection->select('custom_model_submitted_abstracts', 'a')
        ->fields('a')
        ->condition('proposal_id', $proposal_id)
        ->execute();

      foreach ($abstracts as $abstract) {
        $this->connection->update('custom_model_submitted_abstracts')
          ->fields([
            'abstract_approval_status' => 0,
            'is_submitted' => 0,
            'approver_uid' => $this->currentUser->id(),
          ])
          ->condition('id', $abstract->id)
          ->execute();

        $this->connection->update('custom_model_proposal')
          ->fields([
            'is_submitted' => 0,
            'approver_uid' => $this->currentUser->id(),
          ])
          ->condition('id', $abstract->proposal_id)
          ->execute();

        $this->connection->update('custom_model_submitted_abstracts_file')
          ->fields([
            'file_approval_status' => 0,
            'approvar_uid' => $this->currentUser->id(),
          ])
          ->condition('submitted_abstract_id', $abstract->id)
          ->execute();
      }

      $this->messenger->addStatus($this->t('Resubmit the project files'));

      // Send resubmit email.
      if ($email_to) {
        $subject = $this->t('[Custom Model] Your uploaded Custom Model has been marked as pending');
        $body = $this->t("Dear @name,\n\nKindly resubmit the project files for the project: @title.\n\nReason: @reason\n\nBest Wishes,\nFOSSEE, IIT Bombay", [
          '@name' => $proposal->contributor_name,
          '@title' => $proposal->project_title,
          '@reason' => $message_text,
        ]);
        $this->mailService->sendNotification('custom_model', 'custom_model_abstract_resubmit', $email_to, $subject, [$body]);
      }

    }
    elseif ($action == 3) {
      // Disapprove and delete.
      if (!$this->currentUser->hasPermission('custom model bulk delete abstract')) {
        $this->messenger->addError($this->t('You do not have permission to delete this Custom Model project.'));
        return;
      }

      if (custom_model_abstract_delete_project($proposal_id)) {
        $this->messenger->addStatus($this->t('Disapproved and Deleted Entire Custom Model project.'));

        // Send disapproval email.
        if ($email_to) {
          $subject = $this->t('[Custom Model] Your uploaded Custom Model has been marked as dis-approved');
          $body = $this->t("Dear @name,\n\nYour uploaded Custom Model files for the Custom Model Title: @title have been marked as dis-approved.\n\nReason: @reason\n\nBest Wishes,\nFOSSEE, IIT Bombay", [
            '@name' => $proposal->contributor_name,
            '@title' => $proposal->project_title,
            '@reason' => $message_text,
          ]);
          $this->mailService->sendNotification('custom_model', 'custom_model_abstract_disapproved', $email_to, $subject, [$body]);
        }
      }
      else {
        $this->messenger->addError($this->t('Error deleting the Custom Model project.'));
      }
    }
  }

  /**
   * Returns the proposal detail HTML for AJAX rendering.
   */
  private function _custom_model_details($custom_model_proposal_id) {
    if (!$custom_model_proposal_id) {
      return '';
    }

    $abstracts_pro = $this->connection->select('custom_model_proposal', 'cmp')
      ->fields('cmp')
      ->condition('id', $custom_model_proposal_id)
      ->execute()
      ->fetchObject();

    if (!$abstracts_pro) {
      return '';
    }

    // Abstract file (type A).
    $abstracts_pdf = $this->connection->select('custom_model_submitted_abstracts_file', 'pdf')
      ->fields('pdf')
      ->condition('proposal_id', $custom_model_proposal_id)
      ->condition('filetype', 'A')
      ->execute()
      ->fetchObject();

    $abstract_filename = (!empty($abstracts_pdf) && !empty($abstracts_pdf->filename) && $abstracts_pdf->filename !== "NULL")
      ? $abstracts_pdf->filename
      : 'File not uploaded';

    // Simulation file (type S).
    $abstracts_process = $this->connection->select('custom_model_submitted_abstracts_file', 'proc')
      ->fields('proc')
      ->condition('proposal_id', $custom_model_proposal_id)
      ->condition('filetype', 'S')
      ->execute()
      ->fetchObject();

    $process_filename = (!empty($abstracts_process) && !empty($abstracts_process->filename) && $abstracts_process->filename !== "NULL")
      ? $abstracts_process->filename
      : 'File not uploaded';

    // Script file (type P).
    $abstracts_script = $this->connection->select('custom_model_submitted_abstracts_file', 'script')
      ->fields('script')
      ->condition('proposal_id', $custom_model_proposal_id)
      ->condition('filetype', 'P')
      ->execute()
      ->fetchObject();

    $script_filename = (!empty($abstracts_script) && !empty($abstracts_script->filename) && $abstracts_script->filename !== "NULL")
      ? $abstracts_script->filename
      : 'File not uploaded';

    $download_link = Link::fromTextAndUrl(
      $this->t('Download Custom Model'),
      Url::fromUserInput('/custom-model/full-download/project/' . $custom_model_proposal_id)
    )->toString();

    $html = '<strong>Proposer Name:</strong><br />' . $abstracts_pro->name_title . ' ' . $abstracts_pro->contributor_name . '<br /><br />';
    $html .= '<strong>Title of the Custom Model:</strong><br />' . $abstracts_pro->project_title . '<br /><br />';
    $html .= '<strong>Uploaded an abstract (brief outline) of the project:</strong><br />' . $abstract_filename . '<br /><br />';
    $html .= '<strong>Uploaded Custom Model as DWSIM Simulation File:</strong><br />' . $process_filename . '<br /><br />';
    $html .= '<strong>Uploaded script file:</strong><br />' . $script_filename . '<br /><br />';
    $html .= $download_link;

    return $html;
  }

  /**
   * Returns the list of proposals eligible for bulk review.
   */
  private function _bulk_list_of_custom_model_proposals() {
    $project_titles = ['0' => $this->t('Please select...')];

    $results = $this->connection->select('custom_model_proposal', 'cmp')
      ->fields('cmp')
      ->condition('is_submitted', 1)
      ->condition('approval_status', 1)
      ->orderBy('project_title', 'ASC')
      ->execute()
      ->fetchAll();

    foreach ($results as $record) {
      $project_titles[$record->id] = $record->project_title . ' (Proposed by ' . $record->contributor_name . ')';
    }

    return $project_titles;
  }

  /**
   * Returns the available bulk actions.
   */
  private function _bulk_list_custom_model_actions() {
    return [
      0 => $this->t('Please select...'),
      1 => $this->t('Approve Entire Custom Model'),
      2 => $this->t('Resubmit Project files'),
      3 => $this->t('Dis-Approve Entire Custom Model (This will delete Custom Model)'),
    ];
  }

}
