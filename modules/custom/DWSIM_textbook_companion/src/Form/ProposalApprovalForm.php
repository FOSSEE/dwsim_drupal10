<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\ProposalApprovalForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\textbook_companion\Services\MailService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Form\EnforcedResponseException;

class ProposalApprovalForm extends FormBase {

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
   * @var \Drupal\textbook_companion\Services\MailService
   */
  protected $mailService;

  /**
   * Constructs a new ProposalApprovalForm object.
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
      $container->get('textbook_companion.mail_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'proposal_approval_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $proposal_id = NULL) {
    if (empty($proposal_id)) {
      $proposal_id = \Drupal::routeMatch()->getParameter('proposal_id')
        ?? \Drupal::routeMatch()->getParameter('id')
        ?? \Drupal::request()->attributes->get('proposal_id')
        ?? \Drupal::request()->attributes->get('id');
    }

    if (empty($proposal_id)) {
      // Fallback for path parsing if routing wildcard is not matched.
      $path_args = explode('/', trim(\Drupal::request()->getPathInfo(), '/'));
      $last_arg = end($path_args);
      if (is_numeric($last_arg)) {
        $proposal_id = (int) $last_arg;
      }
    }

    if (empty($proposal_id)) {
      $this->messenger->addError($this->t('Invalid proposal selected. Please try again.'));
      throw new EnforcedResponseException(new RedirectResponse(Url::fromUri('internal:/textbook-companion/manage-proposal')->toString()));
    }

    $query = $this->database->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('proposal_status', 0);
    $query->condition('id', $proposal_id);
    $result = $query->execute();
    $row = $result->fetchObject();

    if (!$row) {
      $this->messenger->addError($this->t('Invalid proposal selected. Please try again.'));
      throw new EnforcedResponseException(new RedirectResponse(Url::fromUri('internal:/textbook-companion/manage-proposal')->toString()));
    }

    $proposal_user = $this->entityTypeManager->getStorage('user')->load($row->uid);

    $form['full_name'] = [
      '#type' => 'item',
      '#markup' => $proposal_user
        ? Link::fromTextAndUrl($row->full_name, Url::fromRoute('entity.user.canonical', ['user' => $row->uid]))->toString()
        : $row->full_name,
      '#title' => $this->t('Contributor Name'),
    ];
    $form['email'] = [
      '#type' => 'item',
      '#markup' => $proposal_user ? $proposal_user->getEmail() : $this->t('Unknown'),
      '#title' => $this->t('Email'),
    ];
    $form['mobile'] = [
      '#type' => 'item',
      '#markup' => $row->mobile,
      '#title' => $this->t('Mobile'),
    ];
    $form['how_project'] = [
      '#type' => 'item',
      '#markup' => $row->how_project,
      '#title' => $this->t('How did you come to know about this project'),
    ];
    $form['course'] = [
      '#type' => 'item',
      '#markup' => $row->course,
      '#title' => $this->t('Course'),
    ];
    $form['branch'] = [
      '#type' => 'item',
      '#markup' => $row->branch,
      '#title' => $this->t('Department/Branch'),
    ];
    $form['university'] = [
      '#type' => 'item',
      '#markup' => $row->university,
      '#title' => $this->t('University/Institute'),
    ];
    $form['city'] = [
      '#type' => 'item',
      '#markup' => $row->city,
      '#title' => $this->t('City/Village'),
    ];
    $form['pincode'] = [
      '#type' => 'item',
      '#markup' => $row->pincode,
      '#title' => $this->t('Pincode'),
    ];
    $form['state'] = [
      '#type' => 'item',
      '#markup' => $row->state,
      '#title' => $this->t('State'),
    ];
    $form['faculty'] = [
      '#type' => 'hidden',
      '#value' => $row->faculty,
    ];
    $form['reviewer'] = [
      '#type' => 'hidden',
      '#value' => $row->reviewer,
    ];
    if ($row->proposed_completion_date != 0) {
      $proposed_completion_date = date('d-m-Y', $row->proposed_completion_date);
    }
    else {
      $proposed_completion_date = "-----";
    }
    $form['proposed_completion_date'] = [
      '#type' => 'item',
      '#markup' => $proposed_completion_date,
      '#title' => $this->t('Proposed Date of Completion'),
    ];
    if ($row->completion_date != 0) {
      $actual_completion_date = date('d-m-Y', $row->completion_date);
    }
    else {
      $actual_completion_date = "-----";
    }
    $form['completion_date'] = [
      '#type' => 'item',
      '#markup' => $actual_completion_date,
      '#title' => $this->t('Actual Date of Completion'),
    ];
    $form['operating_system'] = [
      '#type' => 'item',
      '#markup' => $row->operating_system,
      '#title' => $this->t('Operating System'),
    ];
    $form['version'] = [
      '#type' => 'item',
      '#markup' => $row->dwsim_version,
      '#title' => $this->t('dwsim Version'),
    ];
    $form['reference'] = [
      '#type' => 'item',
      '#markup' => $row->reference,
      '#title' => $this->t('References'),
    ];
    $form['reason'] = [
      '#type' => 'item',
      '#markup' => $row->reason,
      '#title' => $this->t('Reasons'),
    ];

    /* get book preference */
    $preference_rows = [];
    $query = $this->database->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_id);
    $query->orderBy('pref_number', 'ASC');
    $preference_q = $query->execute();
    while ($preference_data = $preference_q->fetchObject()) {
      $preference_rows[$preference_data->id] = $preference_data->book . ' (Written by ' . $preference_data->author . ')';
    }

    $form['book_preference'] = [
      '#type' => 'radios',
      '#options' => $preference_rows,
      '#title' => $this->t('Book Preferences'),
      '#required' => !$form_state->getValue('disapprove'),
    ];

    if ($row->samplefilepath != "Not available") {
      $form['samplecode'] = [
        '#type' => 'markup',
        '#markup' => Link::fromTextAndUrl($this->t('Download Sample Code'), Url::fromUri('internal:/textbook-companion/download/samplecode/' . $proposal_id))->toString() . "<br><br>",
      ];
    }
    $form['disapprove'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disapprove all the above book preferences'),
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Reason for disapproval'),
      '#states' => [
        'visible' => [
          ':input[name="disapprove"]' => [
            'checked' => TRUE
          ]
        ],
        'required' => [':input[name="disapprove"]' => ['checked' => TRUE]],
      ],
    ];
    $form['proposal_id'] = [
      '#type' => 'hidden',
      '#value' => $proposal_id,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl($this->t('Cancel'), Url::fromUri('internal:/textbook-companion/manage-proposal'))->toString(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('disapprove')) {
      if (strlen(trim($form_state->getValue('message'))) <= 30) {
        $form_state->setErrorByName('message', $this->t('Please mention the reason for disapproval in minimum 30 characters.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $proposal_id = $form_state->getValue('proposal_id');

    $query = $this->database->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('proposal_status', 0);
    $query->condition('id', $proposal_id);
    $result = $query->execute();
    $row = $result->fetchObject();

    if (!$row) {
      $this->messenger->addError($this->t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirectUrl(Url::fromUri('internal:/textbook-companion/manage-proposal'));
      return;
    }

    /* disapprove */
    if ($form_state->getValue('disapprove')) {
      $query = $this->database->update('textbook_companion_proposal');
      $query->fields([
        'approver_uid' => $this->currentUser->id(),
        'approval_date' => time(),
        'proposal_status' => 2,
        'completion_date' => '0',
        'message' => $form_state->getValue('message'),
      ]);
      $query->condition('id', $proposal_id);
      $query->execute();

      $query = $this->database->update('textbook_companion_preference');
      $query->fields(['approval_status' => 2]);
      $query->condition('proposal_id', $proposal_id);
      $query->execute();

      /* sending email */
      $book_user = $this->entityTypeManager->getStorage('user')->load($row->uid);
      if ($book_user) {
        $email_to = $book_user->getEmail();
        $params['proposal_disapproved']['proposal_id'] = $proposal_id;
        $params['proposal_disapproved']['user_id'] = $row->uid;
        if (!$this->mailService->sendMail('textbook_companion', 'proposal_disapproved', $email_to, $params)) {
          $this->messenger->addError($this->t('Error sending email message.'));
        }
      }
      $this->messenger->addError($this->t('Book proposal dis-approved. User has been notified of the dis-approval.'));
      $form_state->setRedirectUrl(Url::fromUri('internal:/textbook-companion/manage-proposal'));
      return;
    }

    /* get book preference and set the status */
    $preference_id = $form_state->getValue('book_preference');

    $query = $this->database->update('textbook_companion_proposal');
    $query->fields([
      'approver_uid' => $this->currentUser->id(),
      'approval_date' => time(),
      'proposal_status' => 1,
    ]);
    $query->condition('id', $proposal_id);
    $query->execute();

    $query = $this->database->update('textbook_companion_preference');
    $query->fields(['approval_status' => 1]);
    $query->condition('id', $preference_id);
    $query->execute();

    /* sending email */
    $book_user = $this->entityTypeManager->getStorage('user')->load($row->uid);
    if ($book_user) {
      $email_to = $book_user->getEmail();
      $params['proposal_approved']['proposal_id'] = $proposal_id;
      $params['proposal_approved']['user_id'] = $row->uid;
      if (!$this->mailService->sendMail('textbook_companion', 'proposal_approved', $email_to, $params)) {
        $this->messenger->addError($this->t('Error sending email message.'));
      }
    }
    $this->messenger->addStatus($this->t('Book proposal approved. User has been notified of the approval.'));
    $form_state->setRedirectUrl(Url::fromUri('internal:/textbook-companion/manage-proposal'));
  }

}
