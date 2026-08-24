<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\PaperSubmissionForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Url;
use Drupal\textbook_companion\Services\MailService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Form for paper submission status and notification emails.
 */
class PaperSubmissionForm extends FormBase {

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
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Constructs a PaperSubmissionForm object.
   */
  public function __construct(
    Connection $database,
    EntityTypeManagerInterface $entity_type_manager,
    MailService $mail_service,
    RouteProviderInterface $route_provider
  ) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->mailService = $mail_service;
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('textbook_companion.mail_service'),
      $container->get('router.route_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'paper_submission_form';
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
   * Returns manage-proposals URL or front if route is missing.
   */
  protected function getManageProposalsUrl(): Url {
    try {
      $this->routeProvider->getRouteByName('textbook_companion._proposal_all');
      return Url::fromRoute('textbook_companion._proposal_all');
    }
    catch (RouteNotFoundException $e) {
      return Url::fromRoute('<front>');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $proposal_id = NULL) {
    $proposal_id = $this->resolveProposalId($proposal_id);

    if (empty($proposal_id)) {
      $this->messenger()->addError($this->t('Invalid proposal selected. Please try again.'));
      throw new EnforcedResponseException(
        new RedirectResponse($this->getManageProposalsUrl()->toString())
      );
    }

    $query = $this->database->select('textbook_companion_paper', 'p');
    $query->fields('p');
    $query->condition('proposal_id', $proposal_id);
    $data = $query->execute()->fetchObject();

    $form1 = 0;
    $form2 = 0;
    $form3 = 0;
    $form4 = 0;

    if ($data) {
      $form1 = $data->internship_form;
      $form2 = $data->copyright_form;
      $form3 = $data->undertaking_form;
      $form4 = $data->reciept_form;
    }
    else {
      $this->database->insert('textbook_companion_paper')
        ->fields(['proposal_id' => $proposal_id])
        ->execute();
    }

    $form['proposal_id'] = [
      '#type' => 'hidden',
      '#default_value' => $proposal_id,
    ];
    $form['internshipform'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Recieved Internship Application'),
      '#description' => $this->t('Check if the Internship Application has been recieved.'),
      '#default_value' => $form1,
    ];
    $form['copyrighttransferform'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Recieved Copyright Transfer Form'),
      '#description' => $this->t('Check if the Copyright Transfer Form has been recieved.'),
      '#default_value' => $form2,
    ];
    $form['undertakingform'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Recieved Undertaking Form'),
      '#description' => $this->t('Check if the Undertaking Form has been recieved.'),
      '#default_value' => $form3,
    ];
    $form['recieptform'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Recieved Reciept Form'),
      '#description' => $this->t('Check if the Reciept Form has been recieved.'),
      '#default_value' => $form4,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Email'),
    ];
    $form['cancel'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl($this->t('Cancel'), $this->getManageProposalsUrl())->toString(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $proposal_id = $form_state->getValue('proposal_id');

    $this->database->update('textbook_companion_paper')
      ->fields([
        'internship_form' => $form_state->getValue('internshipform'),
        'copyright_form' => $form_state->getValue('copyrighttransferform'),
        'undertaking_form' => $form_state->getValue('undertakingform'),
        'reciept_form' => $form_state->getValue('recieptform'),
      ])
      ->condition('proposal_id', $proposal_id)
      ->execute();

    $proposal_data = $this->database->select('textbook_companion_proposal', 'p')
      ->fields('p')
      ->condition('id', $proposal_id)
      ->execute()
      ->fetchObject();

    if (!$proposal_data) {
      $this->messenger()->addError($this->t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirectUrl($this->getManageProposalsUrl());
      return;
    }

    $book_user = $this->entityTypeManager->getStorage('user')->load($proposal_data->uid);
    $email_to = $book_user ? $book_user->getEmail() : '';

    $empty_book_params = [
      'book_title' => '',
      'chapter_number' => '',
      'chapter_title' => '',
      'example_no' => '',
    ];

    if ($form_state->getValue('internshipform') == 1) {
      $params = [
        'internshipform' => array_merge($empty_book_params, [
          'proposal_id' => $proposal_id,
          'user_id' => $proposal_data->uid,
        ]),
      ];
      if (!$this->mailService->sendMail('textbook_companion', 'internshipform', $email_to, $params)) {
        $this->messenger()->addError($this->t('Error sending email message.'));
      }
      $this->messenger()->addStatus($this->t('Internship Form for Book proposal has been recieved. User has been notified .'));
    }
    else {
      if (!$this->mailService->sendNotification(
        'textbook_companion',
        'standard',
        $email_to,
        (string) $this->t('[Textbook Companion] Internship Form not received'),
        (string) $this->t('Dear user,

Your Internship Form for the Book proposal has not been received.

Regards,
DWSIM TBC Team,
FOSSEE.')
      )) {
        $this->messenger()->addError($this->t('Error sending email message.'));
      }
      $this->messenger()->addStatus($this->t('Internship Form for Book proposal has not been recieved. User has been notified .'));
    }

    if ($form_state->getValue('copyrighttransferform') == 1) {
      $params = [
        'copyrighttransferform' => array_merge($empty_book_params, [
          'proposal_id' => $proposal_id,
          'user_id' => $proposal_data->uid,
        ]),
      ];
      if (!$this->mailService->sendMail('textbook_companion', 'copyrighttransferform', $email_to, $params)) {
        $this->messenger()->addError($this->t('Error sending email message.'));
      }
      $this->messenger()->addStatus($this->t('Copyright Form for Book proposal has been recieved. User has been notified .'));
    }
    else {
      if (!$this->mailService->sendNotification(
        'textbook_companion',
        'standard',
        $email_to,
        (string) $this->t('[Textbook Companion] Copyright Transfer Form not received'),
        (string) $this->t('Dear user,

Your Copyright Transfer Form for the Book proposal has not been received.

Regards,
DWSIM TBC Team,
FOSSEE.')
      )) {
        $this->messenger()->addError($this->t('Error sending email message.'));
      }
      $this->messenger()->addStatus($this->t('Copyright Transfer Form for Book proposal has not been recieved. User has been notified .'));
    }

    if ($form_state->getValue('undertakingform') == 1) {
      $params = [
        'undertakingform' => array_merge($empty_book_params, [
          'proposal_id' => $proposal_id,
          'user_id' => $proposal_data->uid,
        ]),
      ];
      if (!$this->mailService->sendMail('textbook_companion', 'undertakingform', $email_to, $params)) {
        $this->messenger()->addError($this->t('Error sending email message.'));
      }
      $this->messenger()->addStatus($this->t('Undertaking Form for Book proposal has been recieved. User has been notified .'));
    }
    else {
      if (!$this->mailService->sendNotification(
        'textbook_companion',
        'standard',
        $email_to,
        (string) $this->t('[Textbook Companion] Undertaking Form not received'),
        (string) $this->t('Dear user,

Your Undertaking Form for the Book proposal has not been received.

Regards,
DWSIM TBC Team,
FOSSEE.')
      )) {
        $this->messenger()->addError($this->t('Error sending email message.'));
      }
      $this->messenger()->addStatus($this->t('Undertaking Form for Book proposal has not been recieved. User has been notified .'));
    }

    $this->messenger()->addStatus($this->t('Proposal Updated'));
    $form_state->setRedirectUrl($this->getManageProposalsUrl());
  }

}
