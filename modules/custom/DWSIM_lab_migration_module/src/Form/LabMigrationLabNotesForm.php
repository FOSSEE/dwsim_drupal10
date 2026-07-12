<?php

/**
 * @file
 * Contains \Drupal\lab_migration\Form\LabMigrationLabNotesForm.
 */

namespace Drupal\lab_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

class LabMigrationLabNotesForm extends FormBase {

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
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new LabMigrationLabNotesForm object.
   */
  public function __construct(
    Connection $database,
    MessengerInterface $messenger,
    RequestStack $request_stack
  ) {
    $this->database = $database;
    $this->messenger = $messenger;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('messenger'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lab_migration_lab_notes_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $route_match = $this->requestStack->getCurrentRequest()->attributes->get('_route_match');
    $proposal_id = 0;
    if ($route_match) {
      $proposal_id = (int) $route_match->getParameter('proposal_id');
      if (!$proposal_id) {
        $proposal_id = (int) $route_match->getParameter('id');
      }
    }

    $proposal_data = $this->database->select('lab_migration_proposal')
      ->fields('lab_migration_proposal')
      ->condition('id', $proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$proposal_data) {
      $this->messenger->addError($this->t('Invalid lab selected. Please try again.'));
      // In buildForm we cannot use $form_state->setRedirect. We return the empty form or let it return.
      return $form;
    }

    /* get current notes */
    $notes = '';
    $notes_data = $this->database->select('lab_migration_notes')
      ->fields('lab_migration_notes')
      ->condition('proposal_id', $proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if ($notes_data) {
      $notes = $notes_data->notes;
    }

    $form['lab_details'] = [
      '#type' => 'item',
      '#markup' => '<span style="color: rgb(128, 0, 0);"><strong>About the Lab</strong></span><br />' . '<strong>Proposer:</strong> ' . htmlspecialchars($proposal_data->name) . '<br />' . '<strong>Title of the Lab:</strong> ' . htmlspecialchars($proposal_data->lab_title) . '<br />',
    ];

    $form['notes'] = [
      '#type' => 'textarea',
      '#rows' => 20,
      '#title' => $this->t('Notes for Reviewers'),
      '#default_value' => $notes,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    $form['cancel'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl(
        $this->t('Back'),
        Url::fromRoute('lab_migration.code_approval')
      )->toString(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $route_match = $this->requestStack->getCurrentRequest()->attributes->get('_route_match');
    $proposal_id = 0;
    if ($route_match) {
      $proposal_id = (int) $route_match->getParameter('proposal_id');
      if (!$proposal_id) {
        $proposal_id = (int) $route_match->getParameter('id');
      }
    }

    $proposal_data = $this->database->select('lab_migration_proposal')
      ->fields('lab_migration_proposal')
      ->condition('id', $proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$proposal_data) {
      $this->messenger->addError($this->t('Invalid lab selected. Please try again.'));
      $form_state->setRedirect('lab_migration.code_approval');
      return;
    }

    $notes_data = $this->database->select('lab_migration_notes')
      ->fields('lab_migration_notes')
      ->condition('proposal_id', $proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if ($notes_data) {
      $this->database->update('lab_migration_notes')
        ->fields([
          'notes' => $form_state->getValue('notes'),
        ])
        ->condition('id', $notes_data->id)
        ->execute();

      $this->messenger->addMessage($this->t('Notes updated successfully.'));
    }
    else {
      $this->database->insert('lab_migration_notes')
        ->fields([
          'proposal_id' => $proposal_id,
          'notes' => $form_state->getValue('notes'),
        ])
        ->execute();

      $this->messenger->addMessage($this->t('Notes added successfully.'));
    }

    $form_state->setRedirect('lab_migration.code_approval');
  }

}
