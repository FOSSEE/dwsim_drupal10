<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\EditCodeSubmissionForm.
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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Form\EnforcedResponseException;

class EditCodeSubmissionForm extends FormBase {

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
   * Constructs an EditCodeSubmissionForm object.
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
    return 'edit_code_submission_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $preference_id = NULL) {
    // Robustly extract preference_id from parameter, routeMatch, query or path fallback.
    if (!$preference_id) {
      $preference_id = \Drupal::routeMatch()->getParameter('preference_id');
    }
    if (!$preference_id) {
      $preference_id = \Drupal::request()->query->get('preference_id');
    }
    if (!$preference_id) {
      $path = \Drupal::request()->getPathInfo();
      $parts = explode('/', trim($path, '/'));
      $last_part = end($parts);
      if (is_numeric($last_part)) {
        $preference_id = $last_part;
      }
    }

    if (!$preference_id) {
      $this->messenger()->addError($this->t('Invalid book selected. Please try again.'));
      // Redirect back to list page if route exists, else front page.
      $redirect_url = Url::fromRoute('<front>')->toString();
      try {
        $redirect_url = Url::fromRoute('textbook_companion.edit_code_submission_form')->toString();
      }
      catch (\Exception $e) {
        // Fallback if route not compiled yet
      }
      $response = new RedirectResponse($redirect_url);
      throw new EnforcedResponseException($response);
    }

    $query = $this->database->select('textbook_companion_preference', 'pref');
    $query->fields('pref');
    $query->condition('id', $preference_id);
    $preference_data = $query->execute()->fetchObject();

    if (!$preference_data) {
      $this->messenger()->addError($this->t('Invalid book selected. Please try again.'));
      $redirect_url = Url::fromRoute('<front>')->toString();
      try {
        $redirect_url = Url::fromRoute('textbook_companion.edit_code_submission_form')->toString();
      }
      catch (\Exception $e) {
        // Fallback
      }
      $response = new RedirectResponse($redirect_url);
      throw new EnforcedResponseException($response);
    }

    $form = [];
    $form['book'] = [
      '#type' => 'item',
      '#title' => $this->t('Title of the book'),
      '#markup' => $preference_data->book,
    ];
    $form['author'] = [
      '#type' => 'item',
      '#title' => $this->t('Author Name'),
      '#markup' => $preference_data->author,
    ];
    $form['isbn'] = [
      '#type' => 'item',
      '#title' => $this->t('ISBN No'),
      '#markup' => $preference_data->isbn,
    ];
    $form['publisher'] = [
      '#type' => 'item',
      '#title' => $this->t('Publisher & Place'),
      '#markup' => $preference_data->publisher,
    ];
    $form['edition'] = [
      '#type' => 'item',
      '#title' => $this->t('Edition'),
      '#markup' => $preference_data->edition,
    ];
    $form['year'] = [
      '#type' => 'item',
      '#title' => $this->t('Year of publication'),
      '#markup' => $preference_data->year,
    ];
    $form['all_example_submitted'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable code submission interface for user'),
      '#description' => $this->t('Once you have submitted this option user can upload more examples.'),
      '#required' => TRUE,
    ];
    $form['hidden_preference_id'] = [
      '#type' => 'hidden',
      '#value' => $preference_id,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    $cancel_url = Url::fromRoute('<front>');
    try {
      $cancel_url = Url::fromRoute('textbook_companion.edit_code_submission_form');
    }
    catch (\Exception $e) {
      // Fallback
    }

    $form['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => $cancel_url,
      '#attributes' => [
        'class' => ['button'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('all_example_submitted') != 1) {
      $form_state->setErrorByName('all_example_submitted', $this->t('Please check the field if you are interested to submit all uploaded examples for review!'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_uid = $this->currentUser->id();
    $preference_id = $form_state->getValue('hidden_preference_id');

    if ($form_state->getValue('all_example_submitted') == 1) {
      $updated = $this->database->update('textbook_companion_preference')
        ->fields(['submited_all_examples_code' => 0])
        ->condition('id', $preference_id)
        ->execute();

      if ($updated) {
        $query = $this->database->select('textbook_companion_preference', 'p');
        $query->fields('p', ['proposal_id']);
        $query->condition('id', $preference_id);
        $proposal_data_result = $query->execute()->fetchObject();

        if ($proposal_data_result) {
          $proposal_query = $this->database->select('textbook_companion_proposal', 'pr');
          $proposal_query->fields('pr');
          $proposal_query->condition('proposal_status', 1);
          $proposal_query->condition('id', $proposal_data_result->proposal_id);
          $proposal_data_query = $proposal_query->execute()->fetchObject();

          if ($proposal_data_query) {
            /** @var \Drupal\user\UserInterface $book_user */
            $book_user = $this->entityTypeManager->getStorage('user')->load($proposal_data_query->uid);
            if ($book_user) {
              $email_to = $book_user->getEmail();
              $params = [];
              $params['all_code_submitted_status_changed']['proposal_id'] = $proposal_data_result->proposal_id;
              $params['all_code_submitted_status_changed']['user_id'] = $current_uid;

              if (!$this->mailService->sendMail('textbook_companion', 'all_code_submitted_status_changed', $email_to, $params)) {
                $this->messenger()->addError($this->t('Error sending email message.'));
              }
            }
          }
        }

        $this->messenger()->addStatus($this->t('Enabled code submission interface for user'));
      }
    }

    $redirect_route = 'textbook_companion.code_approval';
    try {
      $redirect_route = 'textbook_companion.edit_code_submission_form';
    }
    catch (\Exception $e) {
      // Fallback
    }
    $form_state->setRedirect($redirect_route);
  }

}
