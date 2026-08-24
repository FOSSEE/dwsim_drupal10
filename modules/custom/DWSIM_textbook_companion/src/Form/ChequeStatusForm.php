<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\ChequeStatusForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\textbook_companion\Services\MailService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form for managing cheque status and delivery details.
 */
class ChequeStatusForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The mail service.
   *
   * @var \Drupal\textbook_companion\Services\MailService
   */
  protected $mailService;

  /**
   * Constructs a ChequeStatusForm object.
   */
  public function __construct(
    Connection $database,
    EntityTypeManagerInterface $entity_type_manager,
    MailService $mail_service
  ) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->mailService = $mail_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('textbook_companion.mail_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cheque_status_form';
  }

  /**
   * Resolves proposal ID from query, route, or path.
   */
  protected function resolveProposalId($proposal_id = NULL) {
    if (empty($proposal_id)) {
      $proposal_id = \Drupal::request()->query->get('proposal_id')
        ?? \Drupal::routeMatch()->getParameter('proposal_id');
    }

    if (empty($proposal_id)) {
      $parts = explode('/', trim(\Drupal::request()->getPathInfo(), '/'));
      $last = end($parts);
      if (is_numeric($last)) {
        $proposal_id = (int) $last;
      }
    }

    return $proposal_id;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $proposal_id = NULL) {
    $proposal_id = $this->resolveProposalId($proposal_id);

    if (empty($proposal_id)) {
      $this->messenger()->addError($this->t('Invalid proposal selected. Please try again.'));
      throw new EnforcedResponseException(
        new RedirectResponse(Url::fromRoute('textbook_companion._proposal_all')->toString())
      );
    }

    $proposal_data = $this->database->select('textbook_companion_proposal', 'p')
      ->fields('p')
      ->condition('id', $proposal_id)
      ->execute()
      ->fetchObject();

    if (!$proposal_data) {
      $this->messenger()->addError($this->t('Invalid proposal selected. Please try again.'));
      throw new EnforcedResponseException(
        new RedirectResponse(Url::fromRoute('textbook_companion._proposal_all')->toString())
      );
    }

    $cheque_data = $this->database->select('textbook_companion_cheque', 'c')
      ->fields('c')
      ->condition('proposal_id', $proposal_id)
      ->execute()
      ->fetchObject();

    if (!$cheque_data) {
      $this->database->insert('textbook_companion_cheque')
        ->fields(['proposal_id' => $proposal_id])
        ->execute();
      $cheque_data = (object) [
        'alt_mobno' => '',
        'cheque_no' => '',
        'address' => '',
        'cheque_amt' => '',
        'cheque_sent' => 0,
        'cheque_cleared' => 0,
        'perm_city' => '',
        'perm_state' => '',
        'perm_pincode' => '',
        'temp_chq_address' => '',
        'temp_city' => '',
        'temp_state' => '',
        'temp_pincode' => '',
        'commentf' => '',
        't_cheque_amt' => '',
        't_cheque_no' => '',
      ];
    }

    $proposal_user = $this->entityTypeManager->getStorage('user')->load($proposal_data->uid);

    $form['proposal_id'] = [
      '#type' => 'hidden',
      '#default_value' => $proposal_id,
    ];

    $form['candidate_detail'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Candidate Details'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#attributes' => ['id' => 'candidate_detail'],
    ];
    $form['candidate_detail']['full_name'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->full_name,
      '#title' => $this->t('Contributor Name'),
    ];
    $form['candidate_detail']['email'] = [
      '#type' => 'item',
      '#markup' => $proposal_user ? $proposal_user->getEmail() : '',
      '#title' => $this->t('Email'),
    ];
    $form['candidate_detail']['mobile'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->mobile,
      '#title' => $this->t('Mobile'),
    ];
    $form['candidate_detail']['alt_mobile'] = [
      '#type' => 'item',
      '#markup' => $cheque_data->alt_mobno ?? '',
      '#title' => $this->t('Alternate Mobile No.'),
    ];

    $form_data = $this->database->select('textbook_companion_paper', 'p')
      ->fields('p')
      ->condition('proposal_id', $proposal_id)
      ->execute()
      ->fetchObject();

    $preference_html = '<ul>';
    $preference_q = $this->database->select('textbook_companion_preference', 'p')
      ->fields('p')
      ->condition('proposal_id', $proposal_id)
      ->orderBy('pref_number', 'ASC')
      ->execute();

    while ($preference_data = $preference_q->fetchObject()) {
      if ($preference_data->approval_status == 1) {
        $preference_html .= '<li><strong>' . $preference_data->book . ' (Written by ' . $preference_data->author . ')  - Approved Book</strong></li>';
      }
      else {
        $preference_html .= '<li>' . $preference_data->book . ' (Written by ' . $preference_data->author . ')</li>';
      }
    }
    $preference_html .= '</ul>';

    $form['book_preference_f'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Book Preferences/Application Status'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#attributes' => ['id' => 'book_preference_f'],
    ];
    $form['book_preference_f']['book_preference'] = [
      '#type' => 'item',
      '#markup' => $preference_html,
      '#title' => $this->t('Book Preferences'),
    ];

    $form_html = '<ul>';
    if (!empty($form_data->internship_form)) {
      $form_html .= '<li><strong>Internship Application </strong> Form Submitted</li>';
    }
    else {
      $form_html .= '<li><strong>Internship Application </strong> Form Not Submitted </li>';
    }
    if (!empty($form_data->copyright_form)) {
      $form_html .= '<li><strong>Copyright Application </strong> Form Submitted</li>';
    }
    else {
      $form_html .= '<li><strong>Copyright Application</strong> Form Not Submitted </li>';
    }
    if (!empty($form_data->undertaking_form)) {
      $form_html .= '<li><strong>Undertaking Application </strong> Form Submitted</li>';
    }
    else {
      $form_html .= '<li><strong>Undertaking Application</strong> Form Not Submitted </li>';
    }
    $form_html .= '</ul>';

    $form['book_preference_f']['formsubmit'] = [
      '#type' => 'item',
      '#markup' => $form_html,
      '#title' => $this->t('Application Form Status'),
    ];

    $form['stu_cheque_details'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Student Cheque Details'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#attributes' => ['id' => 'stu_cheque_details'],
    ];
    $form['tea_cheque_details'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Teacher Cheque Details'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#attributes' => ['id' => 'tea_cheque_details'],
    ];
    $form['perm_cheque_address'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Permanent Address'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#attributes' => ['id' => 'perm_cheque_address'],
    ];
    $form['temp_cheque_address'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Temporary Address'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#attributes' => ['id' => 'temp_cheque_address'],
    ];
    $form['cheque_delivery'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Cheque Delivery'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#attributes' => ['id' => 'cheque_delivery'],
    ];
    $form['commentf'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Remark'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#attributes' => ['id' => 'commentf'],
    ];

    $chqe = $this->database->select('textbook_companion_cheque', 'c')
      ->fields('c')
      ->condition('proposal_id', $proposal_id)
      ->execute()
      ->fetchObject();

    if ($chqe) {
      $form['stu_cheque_details']['cheque_no'] = [
        '#type' => 'textfield',
        '#default_value' => $chqe->cheque_no,
        '#title' => $this->t('Cheque No'),
        '#size' => 54,
      ];
      $form['tea_cheque_details']['cheque_no_t'] = [
        '#type' => 'textfield',
        '#default_value' => $chqe->t_cheque_no,
        '#title' => $this->t('Cheque No'),
        '#size' => 54,
      ];
      $form['perm_cheque_address']['chq_address'] = [
        '#type' => 'textarea',
        '#default_value' => $chqe->address,
        '#title' => $this->t('Address Street 1'),
        '#attributes' => ['readonly' => 'readonly'],
      ];
      $form['perm_cheque_address']['perm_city'] = [
        '#type' => 'textfield',
        '#default_value' => $chqe->perm_city,
        '#title' => $this->t('City'),
        '#size' => 35,
        '#attributes' => ['readonly' => 'readonly'],
      ];
      $form['perm_cheque_address']['perm_state'] = [
        '#type' => 'textfield',
        '#default_value' => $chqe->perm_state,
        '#title' => $this->t('State'),
        '#size' => 35,
        '#attributes' => ['readonly' => 'readonly'],
      ];
      $form['perm_cheque_address']['perm_pincode'] = [
        '#type' => 'textfield',
        '#default_value' => $chqe->perm_pincode,
        '#title' => $this->t('Zip code'),
        '#size' => 35,
        '#attributes' => ['readonly' => 'readonly'],
      ];
      $form['stu_cheque_details']['cheq_amt'] = [
        '#type' => 'textfield',
        '#default_value' => $chqe->cheque_amt,
        '#title' => $this->t('Cheque Amount'),
        '#size' => 54,
      ];
      $form['tea_cheque_details']['cheq_amt_t'] = [
        '#type' => 'textfield',
        '#default_value' => $chqe->t_cheque_no,
        '#title' => $this->t('Cheque Amount'),
        '#size' => 54,
      ];
      $form['temp_cheque_address']['temp_chq_address'] = [
        '#type' => 'textarea',
        '#default_value' => $chqe->temp_chq_address,
        '#title' => $this->t('Address Street 1'),
        '#attributes' => ['readonly' => 'readonly'],
      ];
      $form['temp_cheque_address']['temp_city'] = [
        '#type' => 'textfield',
        '#default_value' => $chqe->temp_city,
        '#title' => $this->t('City'),
        '#size' => 35,
        '#attributes' => ['readonly' => 'readonly'],
      ];
      $form['temp_cheque_address']['temp_state'] = [
        '#type' => 'textfield',
        '#default_value' => $chqe->temp_state,
        '#title' => $this->t('State'),
        '#size' => 35,
        '#attributes' => ['readonly' => 'readonly'],
      ];
      $form['temp_cheque_address']['temp_pincode'] = [
        '#type' => 'textfield',
        '#default_value' => $chqe->temp_pincode,
        '#title' => $this->t('Zipcode'),
        '#size' => 35,
        '#attributes' => ['readonly' => 'readonly'],
      ];
    }
    else {
      $form['stu_cheque_details']['cheque_no'] = [
        '#type' => 'textfield',
        '#default_value' => '',
        '#title' => $this->t('Cheque No'),
      ];
      $form['tea_cheque_details']['cheque_no_t'] = [
        '#type' => 'textfield',
        '#default_value' => '',
        '#title' => $this->t('Cheque No'),
      ];
      $form['perm_cheque_address']['chq_address'] = [
        '#type' => 'textarea',
        '#default_value' => '',
        '#title' => $this->t('Address Street 1'),
      ];
      $form['perm_cheque_address']['perm_city'] = [
        '#type' => 'textfield',
        '#default_value' => '',
        '#title' => $this->t('City'),
        '#size' => 35,
      ];
      $form['perm_cheque_address']['perm_state'] = [
        '#type' => 'textfield',
        '#default_value' => '',
        '#title' => $this->t('State'),
        '#size' => 35,
      ];
      $form['perm_cheque_address']['perm_pincode'] = [
        '#type' => 'textfield',
        '#default_value' => '',
        '#title' => $this->t('Zip code'),
        '#size' => 35,
      ];
      $form['perm_cheque_address']['same_address'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Same As Permanent Address'),
        '#attributes' => ['onclick' => 'copy_address()'],
      ];
      $form['stu_cheque_details']['cheq_amt'] = [
        '#type' => 'textfield',
        '#default_value' => '',
        '#title' => $this->t('Cheque Amount'),
      ];
      $form['tea_cheque_details']['cheq_amt_t'] = [
        '#type' => 'textfield',
        '#default_value' => '',
        '#title' => $this->t('Cheque Amount'),
      ];
      $form['temp_cheque_address']['temp_chq_address'] = [
        '#type' => 'textarea',
        '#default_value' => '',
        '#title' => $this->t('Address Street 1'),
      ];
      $form['temp_cheque_address']['temp_city'] = [
        '#type' => 'textfield',
        '#default_value' => '',
        '#title' => $this->t('City'),
        '#size' => 35,
      ];
      $form['temp_cheque_address']['temp_state'] = [
        '#type' => 'textfield',
        '#default_value' => '',
        '#title' => $this->t('State'),
        '#size' => 35,
      ];
      $form['temp_cheque_address']['temp_pincode'] = [
        '#type' => 'textfield',
        '#default_value' => '',
        '#title' => $this->t('Zip code'),
        '#size' => 35,
      ];
      $form['temp_cheque_address']['same_address'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Same As Permanent Address'),
        '#attributes' => ['onclick' => 'copy_address()'],
      ];
    }

    $form['cheque_delivery']['cheque_sent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cheque Sent'),
      '#default_value' => $chqe ? $chqe->cheque_sent : 0,
      '#description' => $this->t('Check if the Cheque has been sent to the user.'),
      '#attributes' => ['id' => 'cheque_sent'],
    ];
    $form['cheque_delivery']['cheque_cleared'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cheque Cleared'),
      '#default_value' => $chqe ? $chqe->cheque_cleared : 0,
      '#description' => $this->t('Check if the Cheque has been <strong>Realised</strong> to the User Account.'),
      '#attributes' => ['id' => 'cheque_cleared'],
    ];
    $form['commentf']['comment_cheque'] = [
      '#type' => 'textarea',
      '#attributes' => ['id' => 'comment'],
      '#default_value' => $chqe ? $chqe->commentf : '',
    ];

    if (!$form_data) {
      $this->messenger()->addError($this->t('Invalid proposal selected. Please try again.'));
      throw new EnforcedResponseException(
        new RedirectResponse(Url::fromRoute('textbook_companion._proposal_all')->toString())
      );
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl(
        $this->t('Cancel'),
        Url::fromRoute('textbook_companion._proposal_all')
      )->toString(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $proposal_id = $form_state->getValue('proposal_id');

    $proposal_data = $this->database->select('textbook_companion_proposal', 'p')
      ->fields('p')
      ->condition('id', $proposal_id)
      ->execute()
      ->fetchObject();

    if (!$proposal_data) {
      $this->messenger()->addError($this->t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirectUrl(Url::fromRoute('textbook_companion._proposal_all'));
      return;
    }

    $this->database->update('textbook_companion_cheque')
      ->fields([
        'cheque_no' => $form_state->getValue('cheque_no'),
        'cheque_amt' => $form_state->getValue('cheq_amt'),
        'alt_mobno' => $form_state->getValue('mobileno2'),
        'address' => $form_state->getValue('chq_address'),
        'perm_city' => $form_state->getValue('perm_city'),
        'perm_state' => $form_state->getValue('perm_state'),
        'perm_pincode' => $form_state->getValue('perm_pincode'),
        'temp_chq_address' => $form_state->getValue('temp_chq_address'),
        'temp_city' => $form_state->getValue('temp_city'),
        'temp_state' => $form_state->getValue('temp_state'),
        'temp_pincode' => $form_state->getValue('temp_pincode'),
        'commentf' => $form_state->getValue('comment_cheque'),
        't_cheque_no' => $form_state->getValue('cheque_no_t'),
        't_cheque_amt' => $form_state->getValue('cheq_amt_t'),
      ])
      ->condition('proposal_id', $proposal_id)
      ->execute();

    $book_user = $this->entityTypeManager->getStorage('user')->load($proposal_data->uid);
    $email_to = $book_user ? $book_user->getEmail() : '';

    $empty_book_params = [
      'book_title' => '',
      'chapter_number' => '',
      'chapter_title' => '',
      'example_no' => '',
    ];

    if ($form_state->getValue('cheque_sent') == 1) {
      $this->database->update('textbook_companion_cheque')
        ->fields(['cheque_sent' => $form_state->getValue('cheque_sent')])
        ->condition('proposal_id', $proposal_id)
        ->execute();

      $params = [
        'cheque_sent' => array_merge($empty_book_params, [
          'proposal_id' => $proposal_id,
          'user_id' => $proposal_data->uid,
        ]),
      ];
      if (!$this->mailService->sendMail('textbook_companion', 'cheque_sent', $email_to, $params)) {
        $this->messenger()->addError($this->t('Error sending email message.'));
      }
      $this->messenger()->addStatus($this->t('Cheque for Book proposal has been Sent. User has been notified .'));
    }

    if ($form_state->getValue('cheque_cleared') == 1) {
      $this->database->update('textbook_companion_cheque')
        ->fields(['cheque_cleared' => $form_state->getValue('cheque_cleared')])
        ->condition('proposal_id', $proposal_id)
        ->execute();

      $this->messenger()->addStatus($this->t('Cheque Has Been Debited into User Account.'));

      $this->database->update('textbook_companion_cheque')
        ->fields(['cheque_dispatch_date' => time()])
        ->condition('proposal_id', $proposal_id)
        ->execute();
    }

    if ($form_state->getValue('comment_cheque')) {
      $params = [
        'remark' => array_merge($empty_book_params, [
          'proposal_id' => $proposal_id,
          'user_id' => $proposal_data->uid,
        ]),
        'internshipform' => $empty_book_params,
      ];
      if (!$this->mailService->sendMail('textbook_companion', 'remark', $email_to, $params)) {
        $this->messenger()->addError($this->t('Error sending email message.'));
      }
      $this->messenger()->addStatus($this->t('Remark Updated. User has been notified'));
    }
    else {
      if (!$this->mailService->sendNotification(
        'textbook_companion',
        'standard',
        $email_to,
        (string) $this->t('[Textbook Companion] No remark on cheque details'),
        (string) $this->t('Dear user,

No remark has been added to your cheque/contact details at this time.

Regards,
DWSIM TBC Team,
FOSSEE.')
      )) {
        $this->messenger()->addError($this->t('Error sending email message.'));
      }
      $this->messenger()->addStatus($this->t('No Remarks. User has been notified .'));
    }
  }

}
