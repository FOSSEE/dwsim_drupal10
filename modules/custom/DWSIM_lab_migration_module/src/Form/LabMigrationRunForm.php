<?php

namespace Drupal\lab_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\lab_migration\Services\LabMigrationGlobalfunction;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Form\EnforcedFormResponseException;

class LabMigrationRunForm extends FormBase {

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
   * The lab migration global service.
   *
   * @var \Drupal\lab_migration\Services\LabMigrationGlobalfunction
   */
  protected $labGlobal;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new LabMigrationRunForm object.
   */
  public function __construct(
    Connection $database,
    MessengerInterface $messenger,
    AccountProxyInterface $current_user,
    LabMigrationGlobalfunction $lab_global,
    RequestStack $request_stack
  ) {
    $this->database = $database;
    $this->messenger = $messenger;
    $this->currentUser = $current_user;
    $this->labGlobal = $lab_global;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('messenger'),
      $container->get('current_user'),
      $container->get('lab_migration_global'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lab_migration_run_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options_first = $this->_list_of_labs();
    $route_match = $this->requestStack->getCurrentRequest()->attributes->get('_route_match');
    $url_lab_id = $route_match ? $route_match->getParameter('url_lab_id') : NULL;

    if ($url_lab_id !== NULL && $url_lab_id !== '') {
      $selected = (int) $url_lab_id;
    }
    else {
      $selected = $form_state->getValue('lab', !empty($options_first) ? key($options_first) : 0);
    }

    $form['lab'] = [
      '#type' => 'select',
      '#title' => $this->t('Title of the lab'),
      '#options' => $options_first,
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => '::ajax_experiment_list_callback',
        'wrapper' => 'ajax_selected_lab',
      ],
    ];

    $form['download_lab_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'ajax_selected_lab'],
    ];

    $lab_default_value = $form_state->getValue('lab', $selected);

    $form['download_lab_wrapper']['selected_lab'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl(
        $this->t('Download Lab Solutions'),
        Url::fromUri('internal:/lab-migration/download/lab/' . $lab_default_value)
      )->toString(),
    ];

    $form['download_lab_wrapper']['lab_details'] = [
      '#type' => 'item',
      '#markup' => $this->_lab_details($lab_default_value),
    ];

    $form['download_lab_wrapper']['lab_experiment_list'] = [
      '#type' => 'select',
      '#title' => $this->t('Title of the experiment'),
      '#options' => $this->_ajax_get_experiment_list($lab_default_value),
      '#default_value' => $form_state->getValue('lab_experiment_list', ''),
      '#ajax' => [
        'callback' => '::ajax_solution_list_callback',
        'wrapper'  => 'ajax_download_experiments',
      ],
      '#prefix' => '<div id="ajax_selected_experiment">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="lab"]' => ['value' => 0],
        ],
      ],
    ];

    $form['download_experiment_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'ajax_download_experiments'],
    ];

    $form['download_experiment_wrapper']['download_experiment'] = [
      '#type' => 'item',
      '#markup' => Link::fromTextAndUrl(
        $this->t('Download Experiment'),
        Url::fromUri('internal:/lab-migration/download/experiment/' . $form_state->getValue('lab_experiment_list'))
      )->toString(),
    ];

    $form['download_experiment_wrapper']['solution_list'] = [
      '#type' => 'select',
      '#title' => $this->t('Title of the solution'),
      '#options' => $this->_ajax_get_solution_list($form_state->getValue('lab_experiment_list')),
      '#default_value' => $form_state->getValue('solution_list', ''),
      '#ajax' => [
        'callback' => '::ajax_solution_files_callback',
        'wrapper'  => 'ajax_download_solution_file',
      ],
    ];

    $form['download_solution_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'ajax_download_solution_file'],
    ];

    $form['download_solution_wrapper']['download_solution'] = [
      '#type' => 'item',
      '#markup' => Link::fromTextAndUrl(
        $this->t('Download Solution'),
        Url::fromUri('internal:/lab-migration/download/solution/' . $form_state->getValue('solution_list'))
      )->toString(),
    ];

    $solution_files_rows = [];
    if ($form_state->getValue('solution_list')) {
      $solution_list_q = $this->database->select('lab_migration_solution_files', 's')
        ->fields('s')
        ->condition('solution_id', $form_state->getValue('solution_list'))
        ->execute();

      while ($solution_list_data = $solution_list_q->fetchObject()) {
        switch ($solution_list_data->filetype) {
          case 'S':
            $solution_file_type = $this->t('Source or Main file');
            break;
          case 'R':
            $solution_file_type = $this->t('Result file');
            break;
          case 'X':
            $solution_file_type = $this->t('xcos file');
            break;
          default:
            $solution_file_type = $this->t('Unknown');
            break;
        }

        $solution_files_rows[] = [
          Link::fromTextAndUrl($solution_list_data->filename, Url::fromUri('internal:/lab-migration/download/file/' . $solution_list_data->id))->toString(),
          $solution_file_type,
        ];
      }
    }

    $form['download_solution_wrapper']['solution_files'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('List of solution files'),
    ];

    $form['download_solution_wrapper']['solution_files']['table'] = [
      '#type' => 'table',
      '#header' => [$this->t('Filename'), $this->t('Type')],
      '#rows' => $solution_files_rows,
      '#attributes' => [
        'style' => 'width: 100%;',
      ],
    ];

    return $form;
  }

  public function ajax_experiment_list_callback(array &$form, FormStateInterface $form_state) {
    return $form['download_lab_wrapper'];
  }

  public function ajax_solution_list_callback(array &$form, FormStateInterface $form_state) {
    return $form['download_experiment_wrapper'];
  }

  public function ajax_solution_files_callback(array &$form, FormStateInterface $form_state) {
    return $form['download_solution_wrapper'];
  }

  public function _list_of_labs() {
    $lab_titles = [
      '0' => $this->t('Please select...'),
    ];

    $results = $this->database->select('lab_migration_proposal', 'lmp')
      ->fields('lmp', ['id', 'lab_title', 'name_title', 'name'])
      ->condition('solution_display', 1)
      ->condition('approval_status', 3)
      ->orderBy('lab_title', 'ASC')
      ->execute();

    foreach ($results as $row) {
      $lab_titles[$row->id] = $row->lab_title . ' (Proposed by ' . $row->name_title . ' ' . $row->name . ')';
    }

    return $lab_titles;
  }

  public function _ajax_get_experiment_list($lab_default_value = '') {
    $experiments = [
      '0' => $this->t('Please select...'),
    ];

    if (empty($lab_default_value)) {
      return $experiments;
    }

    $results = $this->database->select('lab_migration_experiment', 'lme')
      ->fields('lme', ['id', 'number', 'title'])
      ->condition('proposal_id', $lab_default_value)
      ->orderBy('number', 'ASC')
      ->execute();

    foreach ($results as $row) {
      $experiments[$row->id] = $row->number . '. ' . $row->title;
    }

    return $experiments;
  }

  public function _ajax_get_solution_list($lab_experiment_list = '') {
    $solutions = [
      '0' => $this->t('Please select...'),
    ];

    if (empty($lab_experiment_list)) {
      return $solutions;
    }

    $results = $this->database->select('lab_migration_solution', 'lms')
      ->fields('lms', ['id', 'code_number', 'caption'])
      ->condition('experiment_id', $lab_experiment_list)
      ->execute();

    foreach ($results as $row) {
      $solutions[$row->id] = $row->code_number . ' (' . $row->caption . ')';
    }

    return $solutions;
  }

  public function _lab_information($proposal_id) {
    $lab_data = $this->database->select('lab_migration_proposal', 'l')
      ->fields('l')
      ->condition('l.id', $proposal_id)
      ->condition('l.approval_status', 3)
      ->execute()
      ->fetchObject();

    return $lab_data ?: NULL;
  }

  public function _lab_details($lab_default_value) {
    $lab_details = $this->_lab_information($lab_default_value);
    if ($lab_default_value != 0) {
      if (!$lab_details) {
        $response = new RedirectResponse(Url::fromRoute('lab_migration.run_form')->toString());
        throw new EnforcedFormResponseException($response);
      }

      $solution_provider = '';
      if ($lab_details->solution_provider_uid > 0) {
        $user_solution_provider = User::load($lab_details->solution_provider_uid);
        if ($user_solution_provider) {
          $solution_provider = '<span style="color: rgb(128, 0, 0);"><strong>Solution Provider</strong></span></td><td style="width: 35%;"><br />' . '<ul>' . '<li><strong>Solution Provider Name:</strong> ' . $lab_details->solution_provider_name_title . ' ' . $lab_details->solution_provider_name . '</li>' . '<li><strong>Department:</strong> ' . $lab_details->solution_provider_department . '</li>' . '<li><strong>University:</strong> ' . $lab_details->solution_provider_university . '</li>' . '</ul>';
        }
        else {
          $solution_provider = '<span style="color: rgb(128, 0, 0);"><strong>Solution Provider</strong></span></td><td style="width: 35%;"><br />' . '<ul>' . '<li><strong>Solution Provider: </strong> (Open) </li>' . '</ul>';
        }
      }
      else {
        $solution_provider = '<span style="color: rgb(128, 0, 0);"><strong>Solution Provider</strong></span></td><td style="width: 35%;"><br />' . '<ul>' . '<li><strong>Solution Provider: </strong> (Open) </li>' . '</ul>';
      }

      return '<span style="color: rgb(128, 0, 0);"><strong>About the Lab</strong></span></td><td style="width: 35%;"><br />' . '<ul>' . '<li><strong>Proposer Name:</strong> ' . $lab_details->name_title . ' ' . $lab_details->name . '</li>' . '<li><strong>Title of the Lab:</strong> ' . $lab_details->lab_title . '</li>' . '<li><strong>Department:</strong> ' . $lab_details->department . '</li>' . '<li><strong>University:</strong> ' . $lab_details->university . '</li>' . '<li><strong>Version:</strong> ' . $lab_details->version . '</li>' . '<li><strong>Operating System:</strong> ' . $lab_details->operating_system . '</li>' . '</ul>' . $solution_provider;
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No submit actions needed for download/display run form.
  }

}