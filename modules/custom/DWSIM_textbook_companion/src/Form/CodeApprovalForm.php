<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\CodeApprovalForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\textbook_companion\Services\MailService;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CodeApprovalForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

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
   * The custom mail service.
   *
   * @var \Drupal\textbook_companion\Services\MailService
   */
  protected $mailService;

  /**
   * Constructs a CodeApprovalForm object.
   */
  public function __construct(Connection $database, AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager, MailService $mail_service) {
    $this->database = $database;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->mailService = $mail_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('current_user'),
      $container->get('entity_type_manager'),
      $container->get('textbook_companion.mail_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'code_approval_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $chapter_id = NULL) {
    // Robustly extract chapter_id if not directly passed by routing wildcard.
    if (!$chapter_id) {
      $chapter_id = \Drupal::routeMatch()->getParameter('chapter_id');
    }
    if (!$chapter_id) {
      $chapter_id = \Drupal::request()->query->get('chapter_id');
    }
    if (!$chapter_id) {
      $path = \Drupal::request()->getPathInfo();
      $parts = explode('/', trim($path, '/'));
      $last_part = end($parts);
      if (is_numeric($last_part)) {
        $chapter_id = $last_part;
      }
    }

    if (!$chapter_id) {
      $this->messenger()->addError($this->t('Invalid chapter selected.'));
      $form_state->setRedirect('textbook_companion.code_approval');
      return [];
    }

    $query = $this->database->select('textbook_companion_chapter', 'c');
    $query->fields('c');
    $query->condition('id', $chapter_id);
    $pending_chapter_data = $query->execute()->fetchObject();

    if (!$pending_chapter_data) {
      $this->messenger()->addError($this->t('Invalid chapter selected.'));
      $form_state->setRedirect('textbook_companion.code_approval');
      return [];
    }

    /* get preference data */
    $query = $this->database->select('textbook_companion_preference', 'p');
    $query->fields('p');
    $query->condition('id', $pending_chapter_data->preference_id);
    $preference_data = $query->execute()->fetchObject();

    if (!$preference_data) {
      $this->messenger()->addError($this->t('Book preference data not found.'));
      $form_state->setRedirect('textbook_companion.code_approval');
      return [];
    }

    /* get proposal data */
    $query = $this->database->select('textbook_companion_proposal', 'pr');
    $query->fields('pr');
    $query->condition('id', $preference_data->proposal_id);
    $proposal_data = $query->execute()->fetchObject();

    if (!$proposal_data) {
      $this->messenger()->addError($this->t('Proposal data not found.'));
      $form_state->setRedirect('textbook_companion.code_approval');
      return [];
    }

    $form['#tree'] = TRUE;
    $form['contributor'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->full_name,
      '#title' => $this->t('Contributor Name'),
    ];
    $form['book_details']['book'] = [
      '#type' => 'item',
      '#markup' => $preference_data->book,
      '#title' => $this->t('Title of the Book'),
    ];
    $form['book_details']['number'] = [
      '#type' => 'item',
      '#markup' => $pending_chapter_data->number,
      '#title' => $this->t('Chapter Number'),
    ];
    $form['book_details']['name'] = [
      '#type' => 'item',
      '#markup' => $pending_chapter_data->name,
      '#title' => $this->t('Title of the Chapter'),
    ];
    $form['book_details']['back_to_list'] = [
      '#type' => 'item',
      '#markup' => Link::fromTextAndUrl($this->t('Back to Code Approval List'), Url::fromRoute('textbook_companion.code_approval'))->toString(),
    ];

    /* get example data */
    $query = $this->database->select('textbook_companion_example', 'e');
    $query->fields('e');
    $query->condition('chapter_id', $chapter_id);
    $query->condition('approval_status', 0);
    $example_q = $query->execute();
    
    $has_examples = FALSE;
    while ($example_data = $example_q->fetchObject()) {
      $has_examples = TRUE;
      $form['example_details'][$example_data->id] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Example Details'),
        '#collapsible' => FALSE,
        '#collapsed' => TRUE,
      ];
      $form['example_details'][$example_data->id]['example_number'] = [
        '#type' => 'item',
        '#markup' => $example_data->number,
        '#title' => $this->t('Example Number'),
      ];
      $form['example_details'][$example_data->id]['example_caption'] = [
        '#type' => 'item',
        '#markup' => $example_data->caption,
        '#title' => $this->t('Example Caption'),
      ];
      
      $download_url = Url::fromRoute('textbook_companion.download_example', ['example_id' => $example_data->id]);
      $form['example_details'][$example_data->id]['download'] = [
        '#type' => 'markup',
        '#markup' => Link::fromTextAndUrl($this->t('Download Example'), $download_url)->toString(),
      ];
      $form['example_details'][$example_data->id]['approved'] = [
        '#type' => 'radios',
        '#options' => [
          $this->t('Approved'),
          $this->t('Dis-approved'),
        ],
      ];
      $form['example_details'][$example_data->id]['message'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Reason for dis-approval'),
      ];
      $form['example_details'][$example_data->id]['example_id'] = [
        '#type' => 'hidden',
        '#value' => $example_data->id,
      ];
      $form['example_details'][$example_data->id]['example_number_hidden'] = [
        '#type' => 'hidden',
        '#value' => $example_data->number,
      ];
    }

    if ($has_examples) {
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
      ];
    }
    else {
      $form['no_examples'] = [
        '#type' => 'item',
        '#markup' => $this->t('No pending examples to approve for this chapter.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $example_details = $form_state->getValue('example_details');
    if ($example_details) {
      foreach ($example_details as $ex_id => $ex_data) {
        if ($ex_data['approved'] == "1") {
          if (empty($ex_data['message'])) {
            $form_state->setErrorByName("example_details][$ex_id][message", $this->t('Enter reason for disapproval for experiment no: @num', ['@num' => $ex_data['example_number_hidden']]));
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_uid = $this->currentUser->id();
    $example_details = $form_state->getValue('example_details') ?: [];

    foreach ($example_details as $ex_id => $ex_data) {
      $query = $this->database->select('textbook_companion_example', 'e');
      $query->fields('e');
      $query->condition('id', $ex_data['example_id']);
      $example_data = $query->execute()->fetchObject();

      if (!$example_data) {
        continue;
      }

      $query = $this->database->select('textbook_companion_chapter', 'c');
      $query->fields('c');
      $query->condition('id', $example_data->chapter_id);
      $chapter_data = $query->execute()->fetchObject();

      if (!$chapter_data) {
        continue;
      }

      $query = $this->database->select('textbook_companion_preference', 'p');
      $query->fields('p');
      $query->condition('id', $chapter_data->preference_id);
      $preference_data = $query->execute()->fetchObject();

      if (!$preference_data) {
        continue;
      }

      $query = $this->database->select('textbook_companion_proposal', 'pr');
      $query->fields('pr');
      $query->condition('id', $preference_data->proposal_id);
      $proposal_data = $query->execute()->fetchObject();

      if (!$proposal_data) {
        continue;
      }

      /** @var \Drupal\user\UserInterface $user_data */
      $user_data = $this->entityTypeManager->getStorage('user')->load($proposal_data->uid);
      if (!$user_data) {
        continue;
      }

      // Call module function to delete cache/generated PDF.
      if (function_exists('del_book_pdf')) {
        del_book_pdf($preference_data->id);
      }

      if ($ex_data['approved'] == "0") {
        $this->database->update('textbook_companion_example')
          ->fields([
            'approval_status' => 1,
            'approver_uid' => $current_uid,
            'approval_date' => time(),
          ])
          ->condition('id', $ex_data['example_id'])
          ->execute();

        /* sending email */
        $email_to = $user_data->getEmail();
        $params = [];
        $params['example_approved']['example_id'] = $ex_data['example_id'];
        $params['example_approved']['user_id'] = $user_data->id();

        if (!$this->mailService->sendMail('textbook_companion', 'example_approved', $email_to, $params)) {
          $this->messenger()->addError($this->t('Error sending email message.'));
        }
      }
      elseif ($ex_data['approved'] == "1") {
        // Call module function to delete example.
        if (function_exists('delete_example')) {
          if (delete_example($ex_data['example_id'])) {
            /* sending email */
            $email_to = $user_data->getEmail();
            $params = [];
            $params['example_disapproved']['preference_id'] = $chapter_data->preference_id;
            $params['example_disapproved']['chapter_id'] = $example_data->chapter_id;
            $params['example_disapproved']['example_number'] = $example_data->number;
            $params['example_disapproved']['example_caption'] = $example_data->caption;
            $params['example_disapproved']['user_id'] = $user_data->id();
            $params['example_disapproved']['message'] = $ex_data['message'];

            if (!$this->mailService->sendMail('textbook_companion', 'example_disapproved', $email_to, $params)) {
              $this->messenger()->addError($this->t('Error sending email message.'));
            }
          }
          else {
            $this->messenger()->addError($this->t('Error disapproving and deleting example. Please contact administrator.'));
          }
        }
      }
    }

    $this->messenger()->addStatus($this->t('Updated successfully.'));
    $form_state->setRedirect('textbook_companion.code_approval');
  }

}
