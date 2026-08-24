<?php

namespace Drupal\custom_model\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\custom_model\Services\MailService;

/**
 * Contains \Drupal\custom_model\Form\CustomModelProposalApprovalForm.
 */
class CustomModelProposalApprovalForm extends FormBase {

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
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The mail service.
   *
   * @var \Drupal\dwsim_flowsheet\Services\MailService
   */
  protected $mailService;

  /**
   * Constructs a new CustomModelProposalApprovalForm.
   */
  public function __construct(
    Connection $connection,
    MessengerInterface $messenger,
    AccountInterface $currentUser,
    RouteMatchInterface $route_match,
    EntityTypeManagerInterface $entity_type_manager,
    MailService $mail_service
  ) {
    $this->connection = $connection;
    $this->messenger = $messenger;
    $this->currentUser = $currentUser;
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('custom_model.mail_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_model_proposal_approval_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $proposal_id = (int) $this->routeMatch->getParameter('id');

    $query = $this->connection->select('custom_model_proposal');
    $query->fields('custom_model_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    
    $proposal_data = $proposal_q ? $proposal_q->fetchObject() : FALSE;

    if (!$proposal_data) {
      $this->messenger->addError($this->t('Invalid proposal selected. Please try again.'));
      return;
    }

    if ($proposal_data->contact_no == "NULL" || $proposal_data->contact_no == "") {
      $contact_no = "Not Entered";
    } else {
      $contact_no = $proposal_data->contact_no;
    }

    $form['contributor_name'] = [
      '#type' => 'item',
      '#markup' => Link::fromTextAndUrl(
        $proposal_data->name_title . ' ' . $proposal_data->contributor_name,
        Url::fromUri('internal:/user/' . $proposal_data->uid)
      )->toString(),
      '#title' => $this->t('Student name'),
    ];

    $user_entity = $this->entityTypeManager->getStorage('user')->load($proposal_data->uid);
    $form['student_email_id'] = [
      '#type' => 'item',
      '#markup' => $user_entity ? $user_entity->getEmail() : '',
      '#title' => $this->t('Email'),
    ];

    $form['contributor_contact_no'] = [
      '#title' => $this->t('Contact No.'),
      '#type' => 'item',
      '#markup' => $contact_no,
    ];
    $form['university'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->university,
      '#title' => $this->t('University/Institute'),
    ];
    $form['department'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->department,
      '#title' => $this->t('Department/Branch'),
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
    $form['version'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->version,
      '#title' => $this->t('DWSIM Version used'),
    ];
    $form['project_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->project_title,
      '#title' => $this->t('Title of the Custom Model'),
    ];
    $form['script_used'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->script_used,
      '#title' => $this->t('Script used to create the Custom Model'),
    ];

    if (!empty($proposal_data->samplefilepath) && $proposal_data->samplefilepath !== 'NULL') {
      $str = substr($proposal_data->samplefilepath, strrpos($proposal_data->samplefilepath, '/'));
      $resource_file = ltrim($str, '/');

      $form['samplefilepath'] = [
        '#type' => 'item',
        '#title' => $this->t('Uploaded Abstract of Custom Model'),
        '#markup' => Link::fromTextAndUrl(
          $resource_file,
          Url::fromUri('internal:/custom-model/download/resource-file/' . $proposal_id)
        )->toString(),
      ];
    } else {
      $form['samplefilepath'] = [
        '#type' => 'item',
        '#title' => $this->t('Uploaded Abstract of Custom Model'),
        '#markup' => "Not uploaded<br><br>",
      ];
    }

    $form['approval'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select an action on the Custom model proposal'),
      '#options' => [
        '1' => 'Approve',
        '2' => 'Disapprove',
      ],
      '#required' => TRUE,
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Reason for disapproval'),
      '#attributes' => [
        'placeholder' => $this->t('Enter reason for disapproval in minimum 30 characters '),
        'cols' => 50,
        'rows' => 4,
      ],
      '#states' => [
        'visible' => [
          ':input[name="approval"]' => [
            'value' => '2'
          ]
        ]
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'item',
      '#markup' => Link::fromTextAndUrl(
        $this->t('Cancel'),
        Url::fromUri('internal:/custom-model/manage-proposal/pending')
      )->toString(),
    ];
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('approval') == 2) {
      if (empty($form_state->getValue('message'))) {
        $form_state->setErrorByName('message', $this->t('Reason for disapproval could not be empty'));
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $proposal_id = (int) $this->routeMatch->getParameter('id');

    $query = $this->connection->select('custom_model_proposal');
    $query->fields('custom_model_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    
    $proposal_data = $proposal_q ? $proposal_q->fetchObject() : FALSE;

    if (!$proposal_data) {
      $this->messenger->addError($this->t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirectUrl(Url::fromRoute('custom_model.proposal_pending'));
      return;
    }

    $user_data = $this->entityTypeManager->getStorage('user')->load($proposal_data->uid);
    $email_to = $user_data ? $user_data->getEmail() : '';

    if ($form_state->getValue('approval') == 1) {
      $query = "UPDATE {custom_model_proposal} SET approver_uid = :uid, approval_date = :date, approval_status = 1 WHERE id = :proposal_id";
      $args = [
        ":uid" => $this->currentUser->id(),
        ":date" => time(),
        ":proposal_id" => $proposal_id,
      ];
      $this->connection->query($query, $args);

      /* sending email using MailService */
      if ($email_to) {
        $this->mailService->sendApprovalMail('custom_model', $proposal_id, $proposal_data->uid, $email_to);
      }

      $this->messenger->addStatus($this->t('Custom Model proposal No. @id approved. User has been notified of the approval.', ['@id' => $proposal_id]));
      $form_state->setRedirectUrl(Url::fromRoute('custom_model.proposal_pending'));

    } elseif ($form_state->getValue('approval') == 2) {
      $query = "UPDATE {custom_model_proposal} SET approver_uid = :uid, approval_date = :date, approval_status = 2, dissapproval_reason = :dissapproval_reason WHERE id = :proposal_id";
      $args = [
        ":uid" => $this->currentUser->id(),
        ":date" => time(),
        ":dissapproval_reason" => $form_state->getValue('message'),
        ":proposal_id" => $proposal_id,
      ];
      $this->connection->query($query, $args);

      /* sending email using MailService */
      if ($email_to) {
        $this->mailService->sendRejectionMail('custom_model', $proposal_id, $proposal_data->uid, $email_to, $form_state->getValue('message'));
      }

      $this->messenger->addError($this->t('Custom Model proposal No. @id dis-approved. User has been notified of the dis-approval.', ['@id' => $proposal_id]));
      $form_state->setRedirectUrl(Url::fromRoute('custom_model.proposal_pending'));
    }
  }

}
