<?php

/**
 * @file
 * Contains \Drupal\lab_migration\Form\LabMigrationCategoryEditForm.
 */

namespace Drupal\lab_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\lab_migration\Services\LabMigrationGlobalfunction;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

class LabMigrationCategoryEditForm extends FormBase {

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
   * Constructs a new LabMigrationCategoryEditForm object.
   */
  public function __construct(
    Connection $database,
    MessengerInterface $messenger,
    EntityTypeManagerInterface $entity_type_manager,
    RequestStack $request_stack,
    LabMigrationGlobalfunction $lab_global
  ) {
    $this->database = $database;
    $this->messenger = $messenger;
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $request_stack;
    $this->labGlobal = $lab_global;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('messenger'),
      $container->get('entity_type.manager'),
      $container->get('request_stack'),
      $container->get('lab_migration_global')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lab_migration_category_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $route_match = $this->requestStack->getCurrentRequest()->attributes->get('_route_match');
    $proposal_id = $route_match ? (int) $route_match->getParameter('id') : 0;

    $proposal_data = $this->database->select('lab_migration_proposal', 'p')
      ->fields('p')
      ->condition('id', $proposal_id)
      ->execute()
      ->fetchObject();

    if (!$proposal_data) {
      $this->messenger->addError($this->t('Invalid proposal selected. Please try again.'));
      return $form;
    }

    $form['name'] = [
      '#type' => 'item',
      '#markup' => Link::fromTextAndUrl(
        $proposal_data->name_title . ' ' . $proposal_data->name,
        Url::fromRoute('entity.user.canonical', ['user' => $proposal_data->uid])
      )->toString(),
      '#attributes' => ['class' => ['form-control']],
      '#title' => $this->t('Name'),
    ];

    $proposal_user = $this->entityTypeManager->getStorage('user')->load($proposal_data->uid);
    $form['email_id'] = [
      '#type' => 'item',
      '#markup' => $proposal_user ? $proposal_user->getEmail() : $this->t('Unknown'),
      '#attributes' => ['class' => ['form-control']],
      '#title' => $this->t('Email'),
    ];

    $form['contact_ph'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->contact_ph,
      '#attributes' => ['class' => ['form-control']],
      '#title' => $this->t('Contact No.'),
    ];

    $form['department'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->department,
      '#attributes' => ['class' => ['form-control']],
      '#title' => $this->t('Department/Branch'),
    ];

    $form['university'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->university,
      '#attributes' => ['class' => ['form-control']],
      '#title' => $this->t('University/Institute'),
    ];

    $form['lab_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->lab_title,
      '#attributes' => ['class' => ['form-control']],
      '#title' => $this->t('Title of the Lab'),
    ];

    $form['category'] = [
      '#type' => 'select',
      '#attributes' => ['class' => ['form-control']],
      '#title' => $this->t('Category'),
      '#options' => $this->labGlobal->_lm_list_of_departments(),
      '#required' => TRUE,
      '#default_value' => $proposal_data->category,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    $form['cancel'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl(
        $this->t('Cancel'),
        Url::fromRoute('lab_migration.category_all')
      )->toString(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $route_match = $this->requestStack->getCurrentRequest()->attributes->get('_route_match');
    $proposal_id = $route_match ? (int) $route_match->getParameter('id') : 0;

    $proposal_data = $this->database->select('lab_migration_proposal', 'p')
      ->fields('p')
      ->condition('id', $proposal_id)
      ->execute()
      ->fetchObject();

    if (!$proposal_data) {
      $this->messenger->addError($this->t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirect('lab_migration.category_all');
      return;
    }

    $this->database->update('lab_migration_proposal')
      ->fields([
        'category' => $form_state->getValue('category'),
      ])
      ->condition('id', $proposal_id)
      ->execute();

    $this->messenger->addMessage($this->t('Proposal Category Updated'), 'status');
    $form_state->setRedirect('lab_migration.category_all');
  }

}