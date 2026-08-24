<?php

/**
 * @file
 * Contains \Drupal\lab_migration\Form\LabMigrationBulkApprovalForm.
 */

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

class LabMigrationBulkApprovalForm extends FormBase {

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
   * Constructs a new LabMigrationBulkApprovalForm object.
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
    return 'lab_migration_bulk_approval_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options_first = $this->_bulk_list_of_labs();
    $selected = $form_state->getValue('lab', !empty($options_first) ? key($options_first) : '');

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

    $lab_default_value = $form_state->getValue('lab');
    $form['download_lab_wrapper']['selected_lab'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl(
        $this->t('Download'),
        Url::fromUri('internal:/lab-migration/full-download/lab/' . $lab_default_value)
      )->toString() . ' ' . $this->t('(Download all the approved and unapproved solutions of the entire lab)'),
    ];

    $form['download_lab_wrapper']['lab_actions'] = [
      '#type' => 'select',
      '#title' => $this->t('Please select action for Entire Lab'),
      '#options' => $this->_bulk_list_lab_actions(),
      '#default_value' => 0,
      '#prefix' => '<div id="ajax_selected_lab_action" style="color:red;">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="lab"]' => ['value' => 0],
        ],
      ],
    ];

    $form['download_lab_wrapper']['lab_experiment_list'] = [
      '#type' => 'select',
      '#title' => $this->t('Title of the experiment'),
      '#options' => $this->_ajax_bulk_get_experiment_list($lab_default_value),
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

    $form['download_experiment_wrapper']['lab_experiment_actions'] = [
      '#type' => 'select',
      '#title' => $this->t('Please select action for Entire Experiment'),
      '#options' => $this->_bulk_list_experiment_actions(),
      '#default_value' => 0,
      '#prefix' => '<div id="ajax_selected_lab_experiment_action" style="color:red;">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="lab"]' => ['value' => 0],
        ],
      ],
    ];

    $form['download_experiment_wrapper']['solution_list'] = [
      '#type' => 'select',
      '#title' => $this->t('Title of the solution'),
      '#options' => $this->_ajax_bulk_get_solution_list($form_state->getValue('lab_experiment_list')),
      '#ajax' => [
        'callback' => '::ajax_solution_file_callback',
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

    $form['download_solution_wrapper']['lab_experiment_solution_actions'] = [
      '#type' => 'select',
      '#title' => $this->t('Please select action for solution'),
      '#options' => $this->_bulk_list_solution_actions(),
      '#default_value' => 0,
      '#prefix' => '<div id="ajax_selected_lab_experiment_solution_action" style="color:red;">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="lab"]' => ['value' => 0],
        ],
      ],
    ];

    $form['download_solution'] = [
      '#type' => 'item',
      '#markup' => '<div id="ajax_download_experiment_solution"></div>',
    ];

    $form['edit_solution'] = [
      '#type' => 'item',
      '#markup' => '<div id="ajax_edit_experiment_solution"></div>',
    ];

    $form['solution_files'] = [
      '#type' => 'item',
      '#markup' => '<div id="ajax_solution_files"></div>',
      '#states' => [
        'invisible' => [
          ':input[name="lab"]' => ['value' => 0],
        ],
      ],
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('If Dis-Approved, please specify reason for Dis-Approval'),
      '#prefix' => '<div id="message_submit">',
      '#states' => [
        'required' => [
          [
            [':input[name="lab_actions"]' => ['value' => 3]],
            'or',
            [':input[name="lab_experiment_actions"]' => ['value' => 3]],
            'or',
            [':input[name="lab_experiment_solution_actions"]' => ['value' => 3]],
            'or',
            [':input[name="lab_actions"]' => ['value' => 4]],
          ],
        ],
      ],
    ];

    $solution_files_rows = [];
    $solution_list_q = $this->database->select('lab_migration_solution_files', 's')
      ->fields('s')
      ->condition('solution_id', $form_state->getValue('solution_list'))
      ->execute();

    while ($solution_list_data = $solution_list_q->fetchObject()) {
      $solution_file_type = '';
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

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  public function ajax_experiment_list_callback(array &$form, FormStateInterface $form_state) {
    return $form['download_lab_wrapper'];
  }

  public function ajax_solution_list_callback(array &$form, FormStateInterface $form_state) {
    return $form['download_experiment_wrapper'];
  }

  public function ajax_solution_file_callback(array &$form, FormStateInterface $form_state) {
    return $form['download_solution_wrapper'];
  }

  public function _ajax_bulk_get_experiment_list($lab_default_value = '') {
    $experiments = [
      '0' => $this->t('Please select...'),
    ];

    $experiments_q = $this->database->select('lab_migration_experiment', 'lme')
      ->fields('lme', ['id', 'number', 'title'])
      ->condition('proposal_id', $lab_default_value)
      ->orderBy('number', 'ASC')
      ->execute();

    foreach ($experiments_q as $experiments_data) {
      $experiments[$experiments_data->id] = $experiments_data->number . '. ' . $experiments_data->title;
    }

    return $experiments;
  }

  public function _bulk_list_lab_actions(): array {
    return [
      0 => $this->t('Please select...'),
      1 => $this->t('Approve Entire Lab'),
      2 => $this->t('Pending Review Entire Lab'),
      3 => $this->t('Dis-Approve Entire Lab (This will delete all the solutions in the lab)'),
      4 => $this->t('Delete Entire Lab Including Proposal'),
    ];
  }

  public function _bulk_list_of_labs(): array {
    $lab_titles = [
      '0' => $this->t('Please select...'),
    ];

    $results = $this->database->select('lab_migration_proposal', 'lmp')
      ->fields('lmp', ['id', 'lab_title', 'name'])
      ->condition('solution_display', 1)
      ->orderBy('lab_title', 'ASC')
      ->execute();

    foreach ($results as $lab_titles_data) {
      $lab_titles[$lab_titles_data->id] = $lab_titles_data->lab_title . ' (Proposed by ' . $lab_titles_data->name . ')';
    }

    return $lab_titles;
  }

  public function _ajax_bulk_get_solution_list($lab_experiment_list = ''): array {
    $solutions = [
      0 => $this->t('Please select...'),
    ];

    if (empty($lab_experiment_list)) {
      return $solutions;
    }

    $query = $this->database->select('lab_migration_solution', 'lms')
      ->fields('lms', ['id', 'code_number', 'caption'])
      ->condition('experiment_id', $lab_experiment_list);

    $query->addExpression("CAST(SUBSTRING_INDEX(code_number, '.', 1) AS BINARY)", 'part1');
    $query->addExpression("CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(code_number, '.', 2), '.', -1) AS UNSIGNED)", 'part2');
    $query->addExpression("CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(code_number, '.', -1), '.', 1) AS UNSIGNED)", 'part3');
    $query->orderBy('part1', 'ASC');
    $query->orderBy('part2', 'ASC');
    $query->orderBy('part3', 'ASC');

    $results = $query->execute();
    foreach ($results as $solution) {
      $solutions[$solution->id] = $solution->code_number . ' (' . $solution->caption . ')';
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
      if ($lab_details) {
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
      }
      else {
        return;
      }

      $form['lab_details']['#markup'] = '<span style="color: rgb(128, 0, 0);"><strong>About the Lab</strong></span></td><td style="width: 35%;"><br />' . '<ul>' . '<li><strong>Proposer Name:</strong> ' . $lab_details->name_title . ' ' . $lab_details->name . '</li>' . '<li><strong>Title of the Lab:</strong> ' . $lab_details->lab_title . '</li>' . '<li><strong>Department:</strong> ' . $lab_details->department . '</li>' . '<li><strong>University:</strong> ' . $lab_details->university . '</li>'  . '<li><strong>Operating System:</strong> ' . $lab_details->operating_system . '</li>' . '</ul>' . $solution_provider;

      return $form['lab_details']['#markup'];
    }
  }

  public function _bulk_list_solution_actions(): array {
    return [
      0 => $this->t('Please select...'),
      1 => $this->t('Approve Entire Solution'),
      2 => $this->t('Pending Review Entire Solution'),
      3 => $this->t('Dis-approve Solution (This will delete the solution)'),
    ];
  }

  public function _bulk_list_experiment_actions() {
    return [
      0 => $this->t('Please select...'),
      1 => $this->t('Approve Entire Experiment'),
      2 => $this->t('Pending Review Entire Experiment'),
      3 => $this->t('Dis-Approve Entire Experiment (This will delete all the solutions in the experiment)'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $root_path = $this->labGlobal->lab_migration_path();

    if ($form_state->getValue('lab')) {
      if ($this->currentUser->hasPermission('lab migration bulk manage code')) {
        $user_info = $this->database->select('lab_migration_proposal')
          ->fields('lab_migration_proposal')
          ->condition('id', $form_state->getValue('lab'))
          ->execute()
          ->fetchObject();

        $user_data = User::load($user_info->uid);

        if (($form_state->getValue('lab_actions') == 1) && ($form_state->getValue('lab_experiment_actions') == 0) && ($form_state->getValue('lab_experiment_solution_actions') == 0)) {
          /* approving entire lab */
          $experiment_q = $this->database->select('lab_migration_experiment')
            ->fields('lab_migration_experiment')
            ->condition('proposal_id', $form_state->getValue('lab'))
            ->orderBy('number', 'ASC')
            ->execute();

          while ($experiment_data = $experiment_q->fetchObject()) {
            $this->database->update('lab_migration_solution')
              ->fields([
                'approval_status' => 1,
                'approver_uid' => $this->currentUser->id(),
              ])
              ->condition('experiment_id', $experiment_data->id)
              ->condition('approval_status', 0)
              ->execute();
          }
          $this->messenger->addMessage($this->t('Approved Entire Lab. Click on the checkbox below to mark this lab completed'), 'status');
        }
        elseif (($form_state->getValue('lab_actions') == 2) && ($form_state->getValue('lab_experiment_actions') == 0) && ($form_state->getValue('lab_experiment_solution_actions') == 0)) {
          /* pending review entire lab */
          $experiment_q = $this->database->select('lab_migration_experiment')
            ->fields('lab_migration_experiment')
            ->condition('proposal_id', $form_state->getValue('lab'))
            ->execute();

          while ($experiment_data = $experiment_q->fetchObject()) {
            $this->database->update('lab_migration_solution')
              ->fields(['approval_status' => 0])
              ->condition('experiment_id', $experiment_data->id)
              ->execute();
          }
          $this->messenger->addMessage($this->t('Pending Review Entire Lab.'), 'status');
        }
        elseif (($form_state->getValue('lab_actions') == 3) && ($form_state->getValue('lab_experiment_actions') == 0) && ($form_state->getValue('lab_experiment_solution_actions') == 0)) {
          if (strlen(trim($form_state->getValue('message'))) <= 30) {
            $this->messenger->addError($this->t("Please mention the reason for disapproval. Minimum 30 character required"));
            return;
          }
          if (!$this->currentUser->hasPermission('lab migration bulk delete code')) {
            $this->messenger->addError($this->t('You do not have permission to Bulk Dis-Approved and Deleted Entire Lab.'));
            return;
          }
          if ($this->labGlobal->lab_migration_delete_lab($form_state->getValue('lab'))) {
            $this->messenger->addMessage($this->t('Dis-Approved and Deleted Entire Lab.'), 'status');
          }
          else {
            $this->messenger->addError($this->t('Error Dis-Approving and Deleting Entire Lab.'));
          }
        }
        elseif (($form_state->getValue('lab_actions') == 4) && ($form_state->getValue('lab_experiment_actions') == 0) && ($form_state->getValue('lab_experiment_solution_actions') == 0)) {
          if (strlen(trim($form_state->getValue('message'))) <= 30) {
            $this->messenger->addError($this->t("Please mention the reason for disapproval/deletion. Minimum 30 character required"));
            return;
          }
          if (!$this->currentUser->hasPermission('lab migration bulk delete code')) {
            $this->messenger->addError($this->t('You do not have permission to Bulk Delete Entire Lab Including Proposal.'));
            return;
          }
          /* check if dependency files are present */
          $dep_data = $this->database->select('lab_migration_dependency_files')
            ->fields('lab_migration_dependency_files')
            ->condition('proposal_id', $form_state->getValue('lab'))
            ->execute()
            ->fetchObject();

          if ($dep_data) {
            $this->messenger->addError($this->t("Cannot delete lab since it has dependency files that can be used by others. First delete the dependency files before deleting the lab."));
            return;
          }
          if ($this->labGlobal->lab_migration_delete_lab($form_state->getValue('lab'))) {
            $this->messenger->addMessage($this->t('Dis-Approved and Deleted Entire Lab solutions.'), 'status');
            $proposal_q = $this->database->select('lab_migration_proposal')
              ->fields('lab_migration_proposal')
              ->condition('id', $form_state->getValue('lab'))
              ->execute()
              ->fetchObject();

            $experiment_data = $this->database->select('lab_migration_experiment')
              ->fields('lab_migration_experiment')
              ->condition('proposal_id', $form_state->getValue('lab'))
              ->execute()
              ->fetchObject();

            $dir_path = $root_path . $proposal_q->directory_name;
            if (is_dir($dir_path)) {
              if ($experiment_data) {
                $exp_path = $root_path . $proposal_q->directory_name . '/EXP' . $experiment_data->number;
                @rmdir($exp_path);
              }
              $res = @rmdir($dir_path);
              if (!$res) {
                $this->messenger->addError($this->t("Cannot delete Lab directory: @dir. Please contact administrator.", ['@dir' => $dir_path]));
                return;
              }
            }
            else {
              $this->messenger->addMessage($this->t("Lab directory not present: @dir. Skipping deleting lab directory.", ['@dir' => $dir_path]), 'status');
            }

            $proposal_data = $this->database->select('lab_migration_proposal')
              ->fields('lab_migration_proposal')
              ->condition('id', $form_state->getValue('lab'))
              ->execute()
              ->fetchObject();

            if ($proposal_data) {
              $this->database->delete('lab_migration_experiment')
                ->condition('proposal_id', $proposal_data->id)
                ->execute();

              $this->database->delete('lab_migration_proposal')
                ->condition('id', $proposal_data->id)
                ->execute();
            }

            $this->messenger->addMessage($this->t('Deleted Lab Proposal.'), 'status');
          }
          else {
            $this->messenger->addError($this->t('Error Dis-Approving and Deleting Entire Lab.'));
          }
        }
        elseif (($form_state->getValue('lab_actions') == 0) && ($form_state->getValue('lab_experiment_actions') == 1) && ($form_state->getValue('lab_experiment_solution_actions') == 0)) {
          $this->database->update('lab_migration_solution')
            ->fields([
              'approval_status' => 1,
              'approver_uid' => $this->currentUser->id(),
            ])
            ->condition('experiment_id', $form_state->getValue('lab_experiment_list'))
            ->condition('approval_status', 0)
            ->execute();

          $this->messenger->addMessage($this->t('Approved Entire Experiment.'), 'status');
        }
        elseif (($form_state->getValue('lab_actions') == 0) && ($form_state->getValue('lab_experiment_actions') == 2) && ($form_state->getValue('lab_experiment_solution_actions') == 0)) {
          $this->database->update('lab_migration_solution')
            ->fields(['approval_status' => 0])
            ->condition('experiment_id', $form_state->getValue('lab_experiment_list'))
            ->execute();

          $this->messenger->addMessage($this->t('Entire Experiment marked as Pending Review.'), 'status');
        }
        elseif (($form_state->getValue('lab_actions') == 0) && ($form_state->getValue('lab_experiment_actions') == 3) && ($form_state->getValue('lab_experiment_solution_actions') == 0)) {
          if (strlen(trim($form_state->getValue('message'))) <= 30) {
            $this->messenger->addError($this->t("Please mention the reason for disapproval. Minimum 30 character required"));
            return;
          }
          if (!$this->currentUser->hasPermission('lab migration bulk delete code')) {
            $this->messenger->addError($this->t('You do not have permission to Bulk Dis-Approved and Deleted Entire Experiment.'));
            return;
          }
          if ($this->labGlobal->lab_migration_delete_experiment($form_state->getValue('lab_experiment_list'))) {
            $this->messenger->addMessage($this->t('Dis-Approved and Deleted Entire Experiment.'), 'status');
          }
          else {
            $this->messenger->addError($this->t('Error Dis-Approving and Deleting Entire Experiment.'));
          }
        }
        elseif (($form_state->getValue('lab_actions') == 0) && ($form_state->getValue('lab_experiment_actions') == 0) && ($form_state->getValue('lab_experiment_solution_actions') == 1)) {
          $this->database->update('lab_migration_solution')
            ->fields([
              'approval_status' => 1,
              'approver_uid' => $this->currentUser->id(),
            ])
            ->condition('id', $form_state->getValue('solution_list'))
            ->execute();

          $this->messenger->addMessage($this->t('Solution approved.'), 'status');
        }
        elseif (($form_state->getValue('lab_actions') == 0) && ($form_state->getValue('lab_experiment_actions') == 0) && ($form_state->getValue('lab_experiment_solution_actions') == 2)) {
          $this->database->update('lab_migration_solution')
            ->fields(['approval_status' => 0])
            ->condition('id', $form_state->getValue('solution_list'))
            ->execute();

          $this->messenger->addMessage($this->t('Solution marked as Pending Review.'), 'status');
        }
        elseif (($form_state->getValue('lab_actions') == 0) && ($form_state->getValue('lab_experiment_actions') == 0) && ($form_state->getValue('lab_experiment_solution_actions') == 3)) {
          if (strlen(trim($form_state->getValue('message'))) <= 30) {
            $this->messenger->addError($this->t("Please mention the reason for disapproval. Minimum 30 character required"));
            return;
          }
          if ($this->labGlobal->lab_migration_delete_solution($form_state->getValue('solution_list'))) {
            $this->messenger->addMessage($this->t('Solution Dis-Approved and Deleted.'), 'status');
          }
          else {
            $this->messenger->addError($this->t('Error Dis-Approving and Deleting Solution.'));
          }
        }
        else {
          $this->messenger->addError($this->t('Please select only one action at a time'));
        }
      }
    }
  }

}