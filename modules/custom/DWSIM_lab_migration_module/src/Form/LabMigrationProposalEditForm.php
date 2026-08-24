<?php

/**
 * @file
 * Contains \Drupal\lab_migration\Form\LabMigrationProposalEditForm.
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

class LabMigrationProposalEditForm extends FormBase {

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
   * Constructs a new LabMigrationProposalEditForm object.
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
    return 'lab_migration_proposal_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $route_match = $this->requestStack->getCurrentRequest()->attributes->get('_route_match');
    $proposal_id = $route_match ? (int) $route_match->getParameter('id') : 0;

    $proposal_data = $this->database->select('lab_migration_proposal')
      ->fields('lab_migration_proposal')
      ->condition('id', $proposal_id)
      ->execute()
      ->fetchObject();

    if (!$proposal_data) {
      $this->messenger->addError($this->t('Invalid proposal selected. Please try again.'));
      return $form;
    }

    $user_data = $this->entityTypeManager->getStorage('user')->load($proposal_data->uid);

    $form['name_title'] = [
      '#type' => 'select',
      '#title' => $this->t('Title'),
      '#options' => [
        'Mr' => 'Mr',
        'Ms' => 'Ms',
        'Mrs' => 'Mrs',
        'Dr' => 'Dr',
        'Prof' => 'Prof',
      ],
      '#required' => TRUE,
      '#default_value' => $proposal_data->name_title,
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name of the Proposer'),
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
      '#default_value' => $proposal_data->name,
    ];

    $form['email_id'] = [
      '#type' => 'item',
      '#title' => $this->t('Email'),
      '#markup' => $user_data ? $user_data->getEmail() : $this->t('Unknown'),
    ];

    $form['contact_ph'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Contact No.'),
      '#size' => 30,
      '#maxlength' => 15,
      '#required' => TRUE,
      '#default_value' => $proposal_data->contact_ph,
    ];

    $form['department'] = [
      '#type' => 'select',
      '#title' => $this->t('Department/Branch'),
      '#options' => $this->labGlobal->_lm_list_of_departments(),
      '#required' => TRUE,
      '#default_value' => $proposal_data->department,
    ];

    $form['university'] = [
      '#type' => 'textfield',
      '#title' => $this->t('University/Institute'),
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
      '#default_value' => $proposal_data->university,
    ];

    $form['country'] = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#options' => [
        'India' => 'India',
        'Others' => 'Others',
      ],
      '#default_value' => $proposal_data->country,
      '#required' => TRUE,
      '#tree' => TRUE,
      '#validated' => TRUE,
    ];

    $form['other_country'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other than India'),
      '#size' => 100,
      '#default_value' => $proposal_data->country,
      '#attributes' => [
        'placeholder' => $this->t('Enter your country name'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others',
          ],
        ],
      ],
    ];

    $form['other_state'] = [
      '#type' => 'textfield',
      '#title' => $this->t('State other than India'),
      '#size' => 100,
      '#attributes' => [
        'placeholder' => $this->t('Enter your state/region name'),
      ],
      '#default_value' => $proposal_data->state,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others',
          ],
        ],
      ],
    ];

    $form['other_city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City other than India'),
      '#size' => 100,
      '#attributes' => [
        'placeholder' => $this->t('Enter your city name'),
      ],
      '#default_value' => $proposal_data->city,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others',
          ],
        ],
      ],
    ];

    $form['all_state'] = [
      '#type' => 'select',
      '#title' => $this->t('State'),
      '#options' => $this->labGlobal->_lm_list_of_states(),
      '#default_value' => $proposal_data->state,
      '#validated' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'India',
          ],
        ],
      ],
    ];

    $form['city'] = [
      '#type' => 'select',
      '#title' => $this->t('City'),
      '#options' => $this->labGlobal->_lm_list_of_cities(),
      '#default_value' => $proposal_data->city,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'India',
          ],
        ],
      ],
    ];

    $form['pincode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pincode'),
      '#size' => 30,
      '#maxlength' => 6,
      '#default_value' => $proposal_data->pincode,
      '#attributes' => [
        'placeholder' => $this->t('Insert pincode of your city/ village....'),
      ],
    ];

    $form['lab_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title of the Lab'),
      '#size' => 100,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $proposal_data->lab_title,
    ];

    /* get experiment details */
    $experiment_q = $this->database->select('lab_migration_experiment')
      ->fields('lab_migration_experiment')
      ->condition('proposal_id', $proposal_id)
      ->orderBy('id', 'ASC')
      ->execute();

    for ($counter = 1; $counter <= 15; $counter++) {
      $experiment_title = '';
      $experiment_data = $experiment_q->fetchObject();
      if ($experiment_data) {
        $experiment_title = $experiment_data->title;
        $experiment_description = $experiment_data->description;

        $form['lab_experiment_update' . $experiment_data->id] = [
          '#type' => 'textfield',
          '#title' => $this->t('Title of the Experiment @num', ['@num' => $counter]),
          '#size' => 100,
          '#default_value' => $experiment_title,
        ];

        $namefield = "lab_experiment_update" . $experiment_data->id;
        $form['lab_experiment_description_update' . $experiment_data->id] = [
          '#type' => 'textarea',
          '#attributes' => [
            'placeholder' => $this->t('Enter Description for your experiment @num', ['@num' => $counter]),
          ],
          '#default_value' => $experiment_description,
          '#title' => $this->t('Description for Experiment @num', ['@num' => $counter]),
        ];
      }
      else {
        $form['lab_experiment_insert' . $counter] = [
          '#type' => 'textfield',
          '#title' => $this->t('Title of the Experiment @num', ['@num' => $counter]),
          '#size' => 100,
          '#required' => FALSE,
          '#default_value' => $experiment_title,
        ];

        $namefield = "lab_experiment_insert" . $counter;
        $form['lab_experiment_description_insert' . $counter] = [
          '#type' => 'textarea',
          '#attributes' => [
            'placeholder' => $this->t('Enter Description for your experiment @num', ['@num' => $counter]),
          ],
          '#title' => $this->t('Description for Experiment @num', ['@num' => $counter]),
          '#states' => [
            'invisible' => [
              ':input[name="' . $namefield . '"]' => [
                'value' => "",
              ],
            ],
          ],
        ];
      }
    }

    if ($proposal_data->solution_provider_uid == 0) {
      $solution_provider_user = $this->t('Open');
    }
    else {
      if ($proposal_data->solution_provider_uid == $proposal_data->uid) {
        $solution_provider_user = $this->t('Proposer');
      }
      else {
        $sol_user = $this->entityTypeManager->getStorage('user')->load($proposal_data->solution_provider_uid);
        if (!$sol_user) {
          $solution_provider_user = $this->t('NA');
          $this->messenger->addError($this->t('Solution provider user name is invalid.'));
        }
        else {
          $solution_provider_user = $proposal_data->solution_provider_name;
        }
      }
    }

    $form['solution_provider_uid'] = [
      '#type' => 'item',
      '#title' => $this->t('Who will provide the solution'),
      '#markup' => $solution_provider_user,
    ];

    $form['open_solution'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open the solution for everyone'),
    ];

    $form['solution_display'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Do you want to display the solution on the website'),
      '#required' => TRUE,
      '#default_value' => '1',
    ];

    $form['delete_proposal'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete Proposal'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    $form['cancel'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl(
        $this->t('Cancel'),
        Url::fromRoute('lab_migration.proposal_pending')
      )->toString(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $route_match = $this->requestStack->getCurrentRequest()->attributes->get('_route_match');
    $proposal_id = $route_match ? (int) $route_match->getParameter('id') : 0;

    if ($form_state->getValue('delete_proposal') == 1) {
      $experiment_q = $this->database->select('lab_migration_experiment')
        ->fields('lab_migration_experiment')
        ->condition('proposal_id', $proposal_id)
        ->execute();

      while ($experiment_data = $experiment_q->fetchObject()) {
        $solution_data = $this->database->select('lab_migration_solution')
          ->fields('lab_migration_solution')
          ->condition('experiment_id', $experiment_data->id)
          ->execute()
          ->fetchObject();

        if ($solution_data) {
          $form_state->setErrorByName('delete_proposal', $this->t('Cannot delete proposal since there are solutions already uploaded. Use the "Bulk Manage" interface to delete this proposal.'));
          break;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $route_match = $this->requestStack->getCurrentRequest()->attributes->get('_route_match');
    $proposal_id = $route_match ? (int) $route_match->getParameter('id') : 0;

    $proposal_data = $this->database->select('lab_migration_proposal')
      ->fields('lab_migration_proposal')
      ->condition('id', $proposal_id)
      ->execute()
      ->fetchObject();

    if (!$proposal_data) {
      $this->messenger->addError($this->t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirect('lab_migration.proposal_pending');
      return;
    }

    /* delete proposal */
    if ($form_state->getValue('delete_proposal') == 1) {
      $this->database->delete('lab_migration_proposal')
        ->condition('id', $proposal_id)
        ->execute();

      $this->database->delete('lab_migration_experiment')
        ->condition('proposal_id', $proposal_id)
        ->execute();

      $this->messenger->addMessage($this->t('Proposal Deleted'), 'status');
      $form_state->setRedirect('lab_migration.proposal_pending');
      return;
    }

    if ($form_state->getValue('open_solution') == 1) {
      $result = $this->database->update('lab_migration_proposal')
        ->fields([
          'solution_provider_uid' => 0,
          'solution_status' => 0,
          'solution_provider_name_title' => '',
          'solution_provider_name' => '',
          'solution_provider_contact_ph' => '',
          'solution_provider_department' => '',
          'solution_provider_university' => '',
        ])
        ->condition('id', $proposal_id)
        ->execute();

      if (!$result) {
        $this->messenger->addError($this->t('Solution already open for everyone.'));
      }
    }

    $solution_display = ($form_state->getValue('solution_display') == 1) ? 1 : 0;

    /* update proposal */
    $v = $form_state->getValues();
    $lab_title = $v['lab_title'];
    $proposar_name = $v['name_title'] . ' ' . $v['name'];
    $university = $v['university'];
    $directory_names = $this->labGlobal->_lm_dir_name($lab_title, $proposar_name, $university);

    if ($this->labGlobal->LM_RenameDir($proposal_id, $directory_names)) {
      $directory_name = $directory_names;
    }
    else {
      return;
    }

    $this->database->update('lab_migration_proposal')
      ->fields([
        'name_title' => $v['name_title'],
        'name' => $v['name'],
        'contact_ph' => $v['contact_ph'],
        'department' => $v['department'],
        'university' => $v['university'],
        'city' => $v['city'],
        'pincode' => $v['pincode'],
        'state' => $v['all_state'],
        'lab_title' => $v['lab_title'],
        'solution_display' => $solution_display,
        'directory_name' => $directory_name,
      ])
      ->condition('id', $proposal_id)
      ->execute();

    /* updating existing experiments */
    $experiment_q = $this->database->select('lab_migration_experiment')
      ->fields('lab_migration_experiment')
      ->condition('proposal_id', $proposal_id)
      ->orderBy('id', 'ASC')
      ->execute();

    for ($counter = 1; $counter <= 15; $counter++) {
      $experiment_data = $experiment_q->fetchObject();
      if ($experiment_data) {
        $experiment_field_name = 'lab_experiment_update' . $experiment_data->id;
        $experiment_description = 'lab_experiment_description_update' . $experiment_data->id;

        if (strlen(trim($form_state->getValue($experiment_field_name))) >= 1) {
          $this->database->update('lab_migration_experiment')
            ->fields([
              'title' => trim($form_state->getValue($experiment_field_name)),
              'description' => trim($form_state->getValue($experiment_description)),
            ])
            ->condition('id', $experiment_data->id)
            ->execute();
        }
        else {
          $this->database->delete('lab_migration_experiment')
            ->condition('id', $experiment_data->id)
            ->execute();
        }
      }
    }

    /* inserting new experiments */
    $number_q = $this->database->select('lab_migration_experiment')
      ->fields('lab_migration_experiment')
      ->condition('proposal_id', $proposal_id)
      ->orderBy('number', 'DESC')
      ->range(0, 1)
      ->execute();

    if ($number_data = $number_q->fetchObject()) {
      $number = (int) $number_data->number;
      $number++;
    }
    else {
      $number = 1;
    }

    for ($counter = 1; $counter <= 15; $counter++) {
      $lab_experiment_insert = 'lab_experiment_insert' . $counter;
      $lab_experiment_description_insert = 'lab_experiment_description_insert' . $counter;

      if (strlen(trim($form_state->getValue($lab_experiment_insert))) >= 1) {
        $this->database->insert('lab_migration_experiment')
          ->fields([
            'proposal_id' => $proposal_id,
            'number' => $number,
            'title' => trim($form_state->getValue($lab_experiment_insert)),
            'description' => trim($form_state->getValue($lab_experiment_description_insert)),
          ])
          ->execute();
        $number++;
      }
    }

    $this->messenger->addMessage($this->t('Proposal Updated'), 'status');
    $form_state->setRedirect('lab_migration.proposal_pending');
  }

}