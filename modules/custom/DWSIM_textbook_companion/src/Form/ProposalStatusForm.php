<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\ProposalStatusForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\textbook_companion\Services\MailService;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Form\EnforcedResponseException;

class ProposalStatusForm extends FormBase {

  protected $database;
  protected $messenger;
  protected $currentUser;
  protected $entityTypeManager;
  protected $mailService;

  public function __construct(
    Connection $database,
    MessengerInterface $messenger,
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    MailService $mail_service
  ) {
    $this->database = $database;
    $this->messenger = $messenger;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->mailService = $mail_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('messenger'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('textbook_companion.mail_service')
    );
  }

  public function getFormId() {
    return 'proposal_status_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    // Extract proposal_id from query parameter.
    $proposal_id = \Drupal::request()->query->get('proposal_id')
      ?? \Drupal::routeMatch()->getParameter('proposal_id');

    if (empty($proposal_id)) {
      $path_parts = explode('/', trim(\Drupal::request()->getPathInfo(), '/'));
      $last = end($path_parts);
      if (is_numeric($last)) {
        $proposal_id = (int) $last;
      }
    }

    if (empty($proposal_id)) {
      $this->messenger->addError($this->t('Invalid proposal selected. Please try again.'));
      throw new EnforcedResponseException(
        new RedirectResponse(Url::fromRoute('textbook_companion._proposal_all')->toString())
      );
    }

    $query = $this->database->select('textbook_companion_proposal', 'p');
    $query->fields('p');
    $query->condition('id', $proposal_id);
    $proposal_data = $query->execute()->fetchObject();

    if (!$proposal_data) {
      $this->messenger->addError($this->t('Invalid proposal selected. Please try again.'));
      throw new EnforcedResponseException(
        new RedirectResponse(Url::fromRoute('textbook_companion._proposal_all')->toString())
      );
    }

    // Load user for email.
    $proposal_user = $this->entityTypeManager->getStorage('user')->load($proposal_data->uid);
    $user_email = $proposal_user ? $proposal_user->getEmail() : $this->t('Unknown');

    $form['full_name'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->full_name,
      '#title' => $this->t('Contributor Name'),
    ];
    $form['email'] = [
      '#type' => 'item',
      '#markup' => $user_email,
      '#title' => $this->t('Email'),
    ];
    $form['mobile'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->mobile,
      '#title' => $this->t('Mobile'),
    ];
    $form['how_project'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->how_project,
      '#title' => $this->t('How did you come to know about this project'),
    ];
    $form['course'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->course,
      '#title' => $this->t('Course'),
    ];
    $form['branch'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->branch,
      '#title' => $this->t('Department/Branch'),
    ];
    $form['university'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->university,
      '#title' => $this->t('University/Institute'),
    ];
    $form['city'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->city,
      '#title' => $this->t('City/Village'),
    ];
    $form['pincode'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->pincode,
      '#title' => $this->t('Pincode'),
    ];
    $form['state'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->state,
      '#title' => $this->t('State'),
    ];
    $form['faculty'] = [
      '#type' => 'hidden',
      '#value' => $proposal_data->faculty,
    ];
    $form['reviewer'] = [
      '#type' => 'hidden',
      '#value' => $proposal_data->reviewer,
    ];
    $completion_ts = $proposal_data->completion_date;
    $form['completion_date'] = [
      '#type' => 'item',
      '#markup' => $completion_ts ? date('d-m-Y', $completion_ts) : '-----',
      '#title' => $this->t('Expected Date of Completion'),
    ];
    $form['operating_system'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->operating_system,
      '#title' => $this->t('Operating System'),
    ];
    $form['version'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->dwsim_version,
      '#title' => $this->t('DWSIM Version'),
    ];

    if ($proposal_data->proposal_type == 1) {
      $form['reason'] = [
        '#type' => 'item',
        '#markup' => $proposal_data->reason,
        '#title' => $this->t('Reason'),
      ];
      $form['reference'] = [
        '#type' => 'item',
        '#markup' => $proposal_data->reference,
        '#title' => $this->t('References'),
      ];
    }

    // Book preferences list.
    $preference_html = '<ul>';
    $pref_query = $this->database->select('textbook_companion_preference', 'pref');
    $pref_query->fields('pref');
    $pref_query->condition('proposal_id', $proposal_id);
    $pref_query->orderBy('pref_number', 'ASC');
    $pref_result = $pref_query->execute();
    while ($preference_data = $pref_result->fetchObject()) {
      if ($preference_data->approval_status == 1) {
        $preference_html .= '<li><strong>' . $preference_data->book . ' (Written by ' . $preference_data->author . ') - Approved Book</strong></li>';
      }
      else {
        $preference_html .= '<li>' . $preference_data->book . ' (Written by ' . $preference_data->author . ')</li>';
      }
    }
    $preference_html .= '</ul>';
    $form['book_preference'] = [
      '#type' => 'item',
      '#markup' => $preference_html,
      '#title' => $this->t('Book Preferences'),
    ];

    // Proposal status label.
    $statuses = [
      0 => $this->t('Pending'),
      1 => $this->t('Approved'),
      2 => $this->t('Dis-approved'),
      3 => $this->t('Completed'),
      4 => $this->t('External'),
      5 => $this->t('Submitted all codes'),
    ];
    $proposal_status = $statuses[$proposal_data->proposal_status] ?? $this->t('Unknown');
    $form['proposal_status'] = [
      '#type' => 'item',
      '#markup' => $proposal_status,
      '#title' => $this->t('Proposal Status'),
    ];

    if ($proposal_data->proposal_status == 2) {
      $form['message'] = [
        '#type' => 'item',
        '#markup' => $proposal_data->message,
        '#title' => $this->t('Reason for disapproval'),
      ];
    }

    // Re-query first preference for submission status.
    $pref_status_query = $this->database->select('textbook_companion_preference', 'pref');
    $pref_status_query->fields('pref');
    $pref_status_query->condition('proposal_id', $proposal_id);
    $pref_status_query->orderBy('pref_number', 'ASC');
    $preference_q_status = $pref_status_query->execute()->fetchObject();

    if ($preference_q_status) {
      if ($preference_q_status->submited_all_examples_code == 1) {
        $form['submit_all_code'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('<strong>Enable Code Submission for user</strong>'),
          '#description' => $this->t('Check if user has not submitted all the book examples.'),
        ];
        $form['completed'] = ['#type' => 'hidden', '#value' => 0];
      }
      elseif ($preference_q_status->submited_all_examples_code == 2) {
        if (in_array($proposal_data->proposal_status, [1, 4, 5])) {
          $form['completed'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('<strong>Completed</strong>'),
            '#description' => $this->t('Check if user has completed all the book examples.'),
          ];
          $form['submit_all_code'] = ['#type' => 'hidden', '#value' => 0];
        }
      }
    }

    if ($proposal_data->proposal_status == 0) {
      $approve_url = Url::fromRoute('textbook_companion.proposal_approval_form', [], [
        'query' => ['proposal_id' => $proposal_id],
      ]);
      $form['approve'] = [
        '#type' => 'item',
        '#markup' => Link::fromTextAndUrl($this->t('Click here'), $approve_url)->toString(),
        '#title' => $this->t('Approve'),
      ];
      $form['completed'] = ['#type' => 'hidden', '#value' => 0];
      $form['submit_all_code'] = ['#type' => 'hidden', '#value' => 0];
    }

    $form['proposal_id'] = ['#type' => 'hidden', '#value' => $proposal_id];
    $form['submit'] = ['#type' => 'submit', '#value' => $this->t('Submit')];
    $form['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('textbook_companion._proposal_all'),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $proposal_id = $form_state->getValue('proposal_id');

    $query = $this->database->select('textbook_companion_proposal', 'p');
    $query->fields('p');
    $query->condition('id', $proposal_id);
    $proposal_data = $query->execute()->fetchObject();

    if (!$proposal_data) {
      $this->messenger->addError($this->t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirect('textbook_companion._proposal_all');
      return;
    }

    $proposal_user = $this->entityTypeManager->getStorage('user')->load($proposal_data->uid);

    if ($form_state->getValue('submit_all_code') == 1) {
      $this->database->update('textbook_companion_preference')
        ->fields(['submited_all_examples_code' => 0])
        ->condition('proposal_id', $proposal_id)
        ->execute();

      if ($proposal_user) {
        $params['all_code_submitted_status_changed']['proposal_id'] = $proposal_id;
        $params['all_code_submitted_status_changed']['user_id'] = $proposal_data->uid;
        if (!$this->mailService->sendMail('textbook_companion', 'all_code_submitted_status_changed', $proposal_user->getEmail(), $params)) {
          $this->messenger->addError($this->t('Error sending email message.'));
        }
      }
      $this->messenger->addStatus($this->t('User has been notified that code submission interface is now available.'));
    }
    elseif ($form_state->getValue('completed') == 1) {
      $this->database->update('textbook_companion_proposal')
        ->fields(['proposal_status' => 3, 'completion_date' => time()])
        ->condition('id', $proposal_id)
        ->execute();

      if (function_exists('CreateReadmeFileTextbookCompanion')) {
        CreateReadmeFileTextbookCompanion($proposal_id);
      }

      if ($proposal_user) {
        $params['proposal_completed']['proposal_id'] = $proposal_id;
        $params['proposal_completed']['user_id'] = $proposal_data->uid;
        if (!$this->mailService->sendMail('textbook_companion', 'proposal_completed', $proposal_user->getEmail(), $params)) {
          $this->messenger->addError($this->t('Error sending email message.'));
        }
      }
      $this->messenger->addStatus($this->t('Congratulations! Book proposal has been marked as completed. User has been notified of the completion.'));
    }
    else {
      $this->messenger->addError($this->t('Please select any one action.'));
      $form_state->setRedirect('textbook_companion.proposal_status_form', [], [
        'query' => ['proposal_id' => $proposal_id],
      ]);
      return;
    }

    $form_state->setRedirect('textbook_companion._proposal_all');
  }

}
