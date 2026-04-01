<?php

/**
 * @file
 * Contains \Drupal\lab_migration\Form\LabMigrationBulkApprovalForm.
 */

namespace Drupal\lab_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Database\Database;
use Drupal\Component\Render\Markup;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Session\AccountInterface;

	
class LabMigrationBulkApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lab_migration_bulk_approval_form';
  }
  public function buildForm(array $form, FormStateInterface $form_state) {
    // $options_first = $this->_bulk_list_of_labs();
    // $options_two = $this->_ajax_bulk_get_experiment_list();
    // $selected = $form_state->getValue('lab', key($options_first));
    // $select_two = $form_state->getValue('lab_experiment_list', key($options_two));
    $options_first = $this->_bulk_list_of_labs() ?? [];
    $options_two = $this->_ajax_bulk_get_experiment_list() ?? [];
    $selected = $form_state->getValue('lab') ?: key($options_first);
    $selected = $form_state->getValue('lab', !empty($options_first) ? key($options_first) : '');
    $select_two = $form_state->getValue('lab_experiment_list', !empty($options_two) ? key($options_two) : '');
    $route_match = \Drupal::routeMatch();
    //$url_lab_id = (int) $route_match->getParameter('url_lab_id');
    // if (!$url_lab_id)
    //   {
    //     // $selected = isset($form_state['values']['lab']) ? $form_state['values']['lab'] : key($options_first);
    //  $selected = $form_state->getValue('lab') ?: key($options_first);
    //   }
    // elseif ($url_lab_id == '')
    //   {
    //     $selected = 0;
    //   }
    // else
    //   {
    //     $selected = $url_lab_id;
    //   }
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
      // '#states' => [
      //     'invisible' => [
      //       ':input[name="lab"]' => ['value' => 0],
      //     ],
      //   ],

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
   
  $form['download_lab_wrapper']['lab_experiment_list'] = array(
      '#type' => 'select',
      '#title' => t('Title of the experiment'),
      '#options' => $this->_ajax_bulk_get_experiment_list($lab_default_value),
      '#default_value' => $form_state->getValue('lab_experiment_list', ''),
      '#ajax' => [
          'callback' => '::ajax_solution_list_callback',
          'wrapper'  => 'ajax_download_experiments'
        ],
      '#prefix' => '<div id="ajax_selected_experiment">',
      '#suffix' => '</div>',
      '#states' => array(
          'invisible' => array(
              ':input[name="lab"]' => [
                  'value' => 0
              ]
          )
      )
  );
  $form['download_experiment_wrapper'] = [
    '#type' => 'container',
    '#attributes' => ['id' => 'ajax_download_experiments'],
  ];
  $form['download_experiment_wrapper']['download_experiment'] = [
    '#type' => 'item',
    '#markup' => Link::fromTextAndUrl('Download Experiment', Url::fromUri('internal:/lab-migration/download/experiment/' . $form_state->getValue('lab_experiment_list')))->toString()
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
          [
            [':input[name="lab"]' => ['value' => 0]]
          ],
        ],
      ],
    ];

  
$form['download_experiment_wrapper']['solution_list'] = [
  '#type' => 'select',
    '#title' => t('Title of the solution'),
    '#options' => $this->_ajax_bulk_get_solution_list($form_state->getValue('lab_experiment_list')),
    '#ajax' => [
        'callback' => '::ajax_solution_file_callback',
        'wrapper'  => 'ajax_download_solution_file'
      ],
];
$form['download_solution_wrapper'] = [
  '#type' => 'container',
  '#attributes' => ['id' => 'ajax_download_solution_file'],
];
$form['download_solution_wrapper']['download_solution'] = [
  '#type' => 'item',
  '#markup' => Link::fromTextAndUrl('Download Solution', Url::fromUri('internal:/lab-migration/download/solution/' . $form_state->getValue('solution_list')))->toString()
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
    // 'visible' => [
    //   [
    //     [
    //       ':input[name="lab_actions"]' => ['value' => 3],
    //     ],
    //     'or',
    //     [
    //       ':input[name="lab_experiment_actions"]' => ['value' => 3],
    //     ],
    //     'or',
    //     [
    //       ':input[name="lab_experiment_solution_actions"]' => ['value' => 3],
    //     ],
    //     'or',
    //     [
    //       ':input[name="lab_actions"]' => ['value' => 4],
    //     ],
    //   ],
    // ],
    'required' => [
      [
        [
          ':input[name="lab_actions"]' => ['value' => 3],
        ],
        'or',
        [
          ':input[name="lab_experiment_actions"]' => ['value' => 3],
        ],
        'or',
        [
          ':input[name="lab_experiment_solution_actions"]' => ['value' => 3],
        ],
        'or',
        [
          ':input[name="lab_actions"]' => ['value' => 4],
        ],
      ],
    ],
  ],
];
//List of solution file
$query = \Drupal::database()->select('lab_migration_solution_files', 's');
    $query->fields('s');
    $query->condition('solution_id', $form_state->getValue('solution_list'));
    $solution_list_q = $query->execute();
    if ($solution_list_q) {
      $solution_files_rows = [];
      while ($solution_list_data = $solution_list_q->fetchObject()) {

//var_dump($solution_list_data);die;
        $solution_file_type = '';
        switch ($solution_list_data->filetype) {
          case 'S':
            $solution_file_type = 'Source or Main file';
            break;
          case 'R':
            $solution_file_type = 'Result file';
            break;
          case 'X':
            $solution_file_type = 'xcos file';
            break;
          default:
            $solution_file_type = 'Unknown';
            break;
        }
      
        // Create file download link
        $items = [
         
           Link::fromTextAndUrl($solution_list_data->filename, Url::fromUri('internal:/lab-migration/download/file/' . $solution_list_data->id))->toString(),
          "{$solution_file_type}"
        ];
      }
    }
    array_push($solution_files_rows, $items);
    //var_dump($solution_rows);die;
      $form['download_solution_wrapper']['solution_files'] = [
        '#type' => 'fieldset',
        '#title' => t('List of solution files'),
      ];
      $solution_files_header = ['Filename', 'Type']; // Table headers

      $table = [
        '#type' => 'table',
        '#header' => $solution_files_header,
        '#rows' => $solution_files_rows,
      
      '#attributes' => [
        'style' => 'width: 100%;',
        
      ],
    ];
          // Add the table to the fieldset
$form['download_solution_wrapper']['solution_files']['table'] = $table;
   

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
   // return $form['download_lab_wrapper'];
    $experiments = [
      '0' => 'Please select...',
    ];
  
    // Get the database connection.
    $connection = Database::getConnection();
  
    // Prepare the query.
    $query = $connection->select('lab_migration_experiment', 'lme')
      ->fields('lme', ['id', 'number', 'title'])
      ->condition('proposal_id', $lab_default_value)
      ->orderBy('number', 'ASC');
  
    // Execute the query and fetch results.
    $experiments_q = $query->execute();
  // var_dump($experiment_q);die;
    foreach ($experiments_q as $experiments_data) {
      $experiments[$experiments_data->id] = $experiments_data->number . '. ' . $experiments_data->title;
    }
  
    return $experiments;
  }
  
  public function _bulk_list_lab_actions(): array {
    return [
      0 => 'Please select...',
      1 => 'Approve Entire Lab',
      2 => 'Pending Review Entire Lab',
      3 => 'Dis-Approve Entire Lab (This will delete all the solutions in the lab)',
      4 => 'Delete Entire Lab Including Proposal',
    ];
  }
  

  public function _bulk_list_of_labs(): array {
    $lab_titles = [
      '0' => 'Please select...',
    ];
  
    // Get the database connection.
    $connection = Database::getConnection();
  
    // Prepare the query.
    $query = $connection->select('lab_migration_proposal', 'lmp')
      ->fields('lmp', ['id', 'lab_title', 'name'])
      ->condition('solution_display', 1)
      ->orderBy('lab_title', 'ASC');
  
    // Execute the query and fetch results.
    $results = $query->execute();
  // var_dump($results);die;
    foreach ($results as $lab_titles_data) {
      $lab_titles[$lab_titles_data->id] = $lab_titles_data->lab_title . ' (Proposed by ' . $lab_titles_data->name . ')';
    }
  // var_dump($lab_titles);die;
    return $lab_titles;
  }

  
 public function ajax_bulk_experiment_list_callback(array &$form, FormStateInterface $form_state) {
  return $form['update_exp'];
  }

public function _ajax_bulk_get_solution_list($lab_experiment_list = ''): array {
  // return $form['download_solution_wrapper'];

  $solutions = [
    0 => 'Please select...',
  ];

  if (empty($lab_experiment_list)) {
    return $solutions;
  }
// var_dump($lab_experiment_list);die;
  // Get the database connection.
  $connection = Database::getConnection();

  // Prepare the query.
  $query = $connection->select('lab_migration_solution', 'lms')
    ->fields('lms', ['id', 'code_number', 'caption'])
    ->condition('experiment_id', $lab_experiment_list);

  // Add custom ordering logic for `code_number`.
  $query->addExpression("CAST(SUBSTRING_INDEX(code_number, '.', 1) AS BINARY)", 'part1');
  $query->addExpression("CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(code_number, '.', 2), '.', -1) AS UNSIGNED)", 'part2');
  $query->addExpression("CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(code_number, '.', -1), '.', 1) AS UNSIGNED)", 'part3');
  $query->orderBy('part1', 'ASC');
  $query->orderBy('part2', 'ASC');
  $query->orderBy('part3', 'ASC');

  // Execute the query and fetch results.
  $results = $query->execute();
// var_dump($results);die;
  foreach ($results as $solution) {
    $solutions[$solution->id] = $solution->code_number . ' (' . $solution->caption . ')';
  }
// var_dump($solutions);die;
  return $solutions;
}
public function _lab_information($proposal_id)
  {
      //var_dump($proposal_id);die;
    //$lab_q = db_query("SELECT * FROM {lab_migration_proposal} WHERE id = %d", $proposal_id);
    $query = \Drupal::database()->select('lab_migration_proposal', 'l')
    ->fields('l')
    // ->fields('e',[])
    ->condition('l.id', $proposal_id)
    ->condition('l.approval_status', 3);

$lab_q = $query->execute();
$lab_data = $lab_q->fetchObject();
//var_dump($lab_data);die;
// Get the database connection.
if ($lab_data) {
    return $lab_data;
} else {
    return;
}
}
public function _lab_details($lab_default_value)
  {
    // $lab_default_value = $form_state['values']['lab'];
    $lab_details = $this->_lab_information($lab_default_value);
    // $experiment_details = $this->_lab_experiment_information($lab_default_value);
    //var_dump($experiment_details);die;
    if ($lab_default_value != 0)
      {
        if ($lab_details){
        if ($lab_details->solution_provider_uid > 0)
          {
            $user_solution_provider = User::load($lab_details->solution_provider_uid);
            if ($user_solution_provider)
              {
                $solution_provider = '<span style="color: rgb(128, 0, 0);"><strong>Solution Provider</strong></span></td><td style="width: 35%;"><br />' . '<ul>' . '<li><strong>Solution Provider Name:</strong> ' . $lab_details->solution_provider_name_title . ' ' . $lab_details->solution_provider_name . '</li>' . '<li><strong>Department:</strong> ' . $lab_details->solution_provider_department . '</li>' . '<li><strong>University:</strong> ' . $lab_details->solution_provider_university . '</li>' . '</ul>';
              }
            else
              {
                $solution_provider = '<span style="color: rgb(128, 0, 0);"><strong>Solution Provider</strong></span></td><td style="width: 35%;"><br />' . '<ul>' . '<li><strong>Solution Provider: </strong> (Open) </li>' . '</ul>';
              }
          }
        else
          {
            $solution_provider = '<span style="color: rgb(128, 0, 0);"><strong>Solution Provider</strong></span></td><td style="width: 35%;"><br />' . '<ul>' . '<li><strong>Solution Provider: </strong> (Open) </li>' . '</ul>';
          }}
          else{
          // drupal_goto('lab-migration/lab-migration-run');
          $url = Url::fromRoute('lab_migration.run_form');

// Create a RedirectResponse and send it.
$response = new RedirectResponse($url->toString());
$response->send();
          
      }
      
    $form['lab_details']['#markup'] = '<span style="color: rgb(128, 0, 0);"><strong>About the Lab</strong></span></td><td style="width: 35%;"><br />' . '<ul>' . '<li><strong>Proposer Name:</strong> ' . $lab_details->name_title . ' ' . $lab_details->name . '</li>' . '<li><strong>Title of the Lab:</strong> ' . $lab_details->lab_title . '</li>' . '<li><strong>Department:</strong> ' . $lab_details->department . '</li>' . '<li><strong>University:</strong> ' . $lab_details->university . '</li>'  . '<li><strong>Operating System:</strong> ' . $lab_details->operating_system . '</li>' . '</ul>' . $solution_provider;

    $details = $form['lab_details']['#markup'];
    return $details;
    
      }
      
    }

public function _bulk_list_solution_actions(): array {
  return [
    0 => 'Please select...',
    1 => 'Approve Entire Solution',
    2 => 'Pending Review Entire Solution',
    3 => 'Dis-approve Solution (This will delete the solution)',
  ];
}
public function _bulk_list_experiment_actions()
  {
    $lab_experiment_actions = array(
        0 => 'Please select...'
    );
    $lab_experiment_actions[1] = 'Approve Entire Experiment';
    $lab_experiment_actions[2] = 'Pending Review Entire Experiment';
    $lab_experiment_actions[3] = 'Dis-Approve Entire Experiment (This will delete all the solutions in the experiment)';
    return $lab_experiment_actions;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    //var_dump($user->id());die;
    $root_path = \Drupal::service("lab_migration_global")->lab_migration_path();
    // var_dump($form_state->get(['clicked_button', '#value']));die;
    // if ($form_state->get(['clicked_button', '#value']) == 'Submit') {
      if ($form_state->getValue(['lab']))
        //lab_migration_del_lab_pdf($form_state['values']['lab']);
 {
  //var_dump($form_state->getValue(['lab_actions']));die;
        if ($user->hasPermission('lab migration bulk manage code')) {
          $query = \Drupal::database()->select('lab_migration_proposal');
          $query->fields('lab_migration_proposal');
          $query->condition('id', $form_state->getValue(['lab']));
          $user_query = $query->execute();
          $user_info = $user_query->fetchObject();
          $user_data = User::load($user_info->uid);
          if (($form_state->getValue(['lab_actions']) == 1) && ($form_state->getValue(['lab_experiment_actions']) == 0) && ($form_state->getValue(['lab_experiment_solution_actions']) == 0)) {
           // var_dump("hi");die;
            /* approving entire lab */
            //   $experiment_q = \Drupal::database()->query("SELECT * FROM {lab_migration_experiment} WHERE proposal_id = %d", $form_state['values']['lab']);
            $query = \Drupal::database()->select('lab_migration_experiment');
            $query->fields('lab_migration_experiment');
            $query->condition('proposal_id', $form_state->getValue(['lab']));
            $query->orderBy('number', 'ASC');
            $experiment_q = $query->execute();
            $experiment_list = '';
            while ($experiment_data = $experiment_q->fetchObject()) {
              //  \Drupal::database()->query("UPDATE {lab_migration_solution} SET approval_status = 1, approver_uid = %d WHERE experiment_id = %d AND approval_status = 0", $user->uid, $experiment_data->id);
              \Drupal::database()->query("UPDATE {lab_migration_solution} SET approval_status = 1, approver_uid = :approver_uid WHERE experiment_id = :experiment_id AND approval_status = 0", [
                ':approver_uid' => $user->id(),
                ':experiment_id' => $experiment_data->id,
              ]);
              $experiment_list .= '<p>' . $experiment_data->number . ') ' . $experiment_data->title . '<br> Description :  ' . $experiment_data->description . '<br>';
              $experiment_list .= ' ';
              $experiment_list .= '</p>';
            }
            $msg = \Drupal::messenger()->addmessage(t('Approved Entire Lab. Click on the checkbox below to mark this lab completed'), 'status');
            // fromUri('internal:/lab-migration/manage-proposal/status/' . $form_state->getValue(['lab']))
            /* email */
//             $email_subject = t('[!site_name] Your uploaded Lab Migration solutions have been approved', [
//               '!site_name' => $config->get('site_name', '')
//               ]);
//             $email_body = [
//               0 => t('

// Dear !user_name,

// Your all the uploaded solutions for the Lab with the below detail has been approved:

// Title of Lab:' . $user_info->lab_title . '

// List of experiments: ' . $experiment_list . '

// Best Wishes,

// !site_name Team,
// FOSSEE,IIT Bombay', [
//                 '@site_name' => $config->get('site_name', ''),
//                 '@user_name' => $user_data->name,
//               ])
//               ];
          }
          elseif (($form_state->getValue(['lab_actions']) == 2) && ($form_state->getValue(['lab_experiment_actions']) == 0) && ($form_state->getValue(['lab_experiment_solution_actions']) == 0)) {
            /* pending review entire lab */
            //$experiment_q = \Drupal::database()->query("SELECT * FROM {lab_migration_experiment} WHERE proposal_id = %d", $form_state['values']['lab']);
            $experiment_q = \Drupal::database()->query("SELECT * FROM {lab_migration_experiment} WHERE proposal_id = :proposal_id", [
              ':proposal_id' => $form_state->getValue(['lab'])
              ]);
            while ($experiment_data = $experiment_q->fetchObject()) {
              //\Drupal::database()->query("UPDATE {lab_migration_solution} SET approval_status = 0 WHERE experiment_id = %d", $experiment_data->id);
              \Drupal::database()->query("UPDATE {lab_migration_solution} SET approval_status = 0 WHERE experiment_id = :experiment_id", [
                ":experiment_id" => $experiment_data->id
                ]);
            }
            \Drupal::messenger()->addmessage(t('Pending Review Entire Lab.'), 'status');
            /* email */
//             $email_subject = t('[!site_name] Your uploaded Lab Migration solutions have been marked as pending', [
//               '!site_name' => $config->get('site_name', '')
//               ]);
//             $email_body = [
//               0 => t('

// Dear !user_name,

// Your all the uploaded solutions for the Lab with Title: ' . $user_info->lab_title . ' have been marked as pending to be reviewed.
 
// You will be able to see the solutions after they have been approved by one of our reviewers.

// Best Wishes,

// !site_name Team,
// FOSSEE,IIT Bombay', [
//                 '!site_name' => $config->get('site_name', ''),
//                 '!user_name' => $user_data->name,
//               ])
//               ];
            /* email */
            /* $email_subject = t('Your uploaded Lab Migration solutions have been marked as pending');
                $email_body = array(0 => t('Your all the uploaded solutions for the Lab have been marked as pending to be review. You will be able to see the solutions after they have been approved by one of our reviewers.'));*/
          }
          elseif (($form_state->getValue(['lab_actions']) == 3) && ($form_state->getValue(['lab_experiment_actions']) == 0) && ($form_state->getValue(['lab_experiment_solution_actions']) == 0)) {

            if (strlen(trim($form_state->getValue(['message']))) <= 30) {
              //$form_state->setErrorByName('message', t(''));
              \Drupal::messenger()->addmessage("Please mention the reason for disapproval. Minimum 30 character required", 'error');
              return;
            }
            if (!$user->hasPermission('lab migration bulk delete code')) {
              \Drupal::messenger()->addmessage(t('You do not have permission to Bulk Dis-Approved and Deleted Entire Lab.'), 'error');
              return;
            }
            if (lab_migration_delete_lab($form_state->getValue(['lab']))) {
              \Drupal::messenger()->addmessage(t('Dis-Approved and Deleted Entire Lab.'), 'status');
            }
            else {
              \Drupal::messenger()->addmessage(t('Error Dis-Approving and Deleting Entire Lab.'), 'error');
            }
            /* email */
//             $email_subject = t('[!site_name] Your uploaded Lab Migration solutions have been marked as dis-approved', [
//               '!site_name' => $config->get('site_name', '')
//               ]);
//             $email_body = [
//               0 => t('

// Dear !user_name,

// Your all the uploaded solutions for the whole Lab with Title: ' . $user_info->lab_title . ' have been marked as dis-approved.

// Reason for dis-approval: ' . $form_state->getValue(['message']) . '

// Best Wishes,

// !site_name Team,
// FOSSEE,IIT Bombay', [
//                 '!site_name' => $config->get('site_name', ''),
//                 '!user_name' => $user_data->name,
//               ])
//               ];
//             /* email */
            /* $email_subject = t('Your uploaded Lab Migration solutions have been marked as dis-approved');
                $email_body = array(0 =>t('Your all the uploaded solutions for the whole Lab have been marked as dis-approved.
                
                Reason for dis-approval:
                
                ' . $form_state['values']['message']));*/
          }
          elseif (($form_state->getValue(['lab_actions']) == 4) && ($form_state->getValue(['lab_experiment_actions']) == 0) && ($form_state->getValue(['lab_experiment_solution_actions']) == 0)) {
            if (strlen(trim($form_state->getValue(['message']))) <= 30) {
              //$form_state->setErrorByName('message', t(''));
              \Drupal::messenger()->addmessage("Please mention the reason for disapproval/deletion. Minimum 30 character required", 'error');
              return;
            }
            $query = \Drupal::database()->select('lab_migration_experiment');
            $query->fields('lab_migration_experiment');
            $query->condition('proposal_id', $form_state->getValue(['lab']));
            $query->orderBy('number', 'ASC');
            $experiment_q = $query->execute();
            $experiment_list = '';
            while ($experiment_data = $experiment_q->fetchObject()) {
              $experiment_list .= '<p>' . $experiment_data->number . ') ' . $experiment_data->title . '<br> Description:  ' . $experiment_data->description . '<br>';
              $experiment_list .= ' ';
              $experiment_list .= '</p>';
            }
            if (!$user->hasPermission('lab migration bulk delete code')) {
              \Drupal::messenger()->addmessage(t('You do not have permission to Bulk Delete Entire Lab Including Proposal.'), 'error');
              return;
            }
            /* check if dependency files are present */
            $dep_q = \Drupal::database()->query("SELECT * FROM {lab_migration_dependency_files} WHERE proposal_id = :proposal_id", [
              ":proposal_id" => $form_state->getValue(['lab'])
              ]);
            if ($dep_data = $dep_q->fetchObject()) {
              \Drupal::messenger()->addmessage(t("Cannot delete lab since it has dependency files that can be used by others. First delete the dependency files before deleting the lab."), 'error');
              return;
            }
            if (\Drupal::service("lab_migration_global")->lab_migration_delete_lab($form_state->getValue(['lab']))) {
              \Drupal::messenger()->addmessage(t('Dis-Approved and Deleted Entire Lab solutions.'), 'status');
              $query = \Drupal::database()->select('lab_migration_proposal');
              $query->fields('lab_migration_proposal');
              $query->condition('id', $form_state->getValue(['lab']));
              $proposal_q = $query->execute()->fetchObject();
              $query = \Drupal::database()->select('lab_migration_experiment');
              $query->fields('lab_migration_experiment');
              $query->condition('proposal_id', $form_state->getValue(['lab']));
              $experiment_q = $query->execute();
              $experiment_data = $experiment_q->fetchObject();
              $exp_path = $root_path . $proposal_q->directory_name . '/EXP' . $experiment_data->number;
              $dir_path = $root_path . $proposal_q->directory_name;
              if (is_dir($dir_path)) {
                rmdir($exp_path);
                $res = rmdir($dir_path);
                if (!$res) {
                  \Drupal::messenger()->addmessage(t("Cannot delete Lab directory: " . $dir_path . ". Please contact administrator."), 'error');
                  return;
                }
              }
              else {
                \Drupal::messenger()->addmessage(t("Lab directory not present: " . $dir_path . ". Skipping deleting lab directory."), 'status');
              }
              /* deleting full proposal */
              //$proposal_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d", $form_state['values']['lab']);
              $proposal_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} WHERE id = :id", [
                ":id" => $form_state->getValue(['lab'])
                ]);
              $proposal_data = $proposal_q->fetchObject();
              $proposal_id = $proposal_data->id;
              \Drupal::database()->query("DELETE FROM {lab_migration_experiment} WHERE proposal_id = :proposal_id", [
                ":proposal_id" => $proposal_id
                ]);
              \Drupal::database()->query("DELETE FROM {lab_migration_proposal} WHERE id = :id", [
                ":id" => $proposal_id
                ]);
              \Drupal::messenger()->addmessage(t('Deleted Lab Proposal.'), 'status');
              /* email */
//               $email_subject = t('[!site_name] Your uploaded Lab Migration solutions including the Lab proposal have been deleted', [
//                 '!site_name' => $config->get('site_name', '')
//                 ]);
//               $email_body = [
//                 0 => t('

// Dear ' . $proposal_data->name . ',

// We regret to inform you that all the uploaded Experiments of your Lab with following details have been deleted permanently.

// Title of Lab:' . $user_info->lab_title . '

// List of experiments: ' . $experiment_list . '

// Reason for dis-approval: ' . $form_state->getValue(['message']) . '

// Best Wishes,

// !site_name Team
// FOSSEE, IIT Bombay', [
//                   '!site_name' => $config->get('site_name', ''),
//                   '!user_name' => $user_data->name,
//                 ])
//                 ];
              /* email */
              /*  $email_subject = t('Your uploaded Lab Migration solutions including the Lab proposal have been deleted');
                    $email_body = array(0 =>t('Your all the uploaded solutions including the Lab proposal have been deleted permanently.'));*/
            }
            else {
              \Drupal::messenger()->addmessage(t('Error Dis-Approving and Deleting Entire Lab.'), 'error');
            }
          }
          elseif (($form_state->getValue(['lab_actions']) == 0) && ($form_state->getValue(['lab_experiment_actions']) == 1) && ($form_state->getValue(['lab_experiment_solution_actions']) == 0)) {
            \Drupal::database()->query("UPDATE {lab_migration_solution} SET approval_status = 1, approver_uid = :approver_uid WHERE experiment_id = :experiment_id AND approval_status = 0", [
              ":approver_uid" => $user->id(),
              ":experiment_id" => $form_state->getValue(['lab_experiment_list']),
            ]);
            $query = \Drupal::database()->select('lab_migration_experiment');
            $query->fields('lab_migration_experiment');
            $query->condition('id', $form_state->getValue(['lab_experiment_list']));
            $query->orderBy('number', 'ASC');
            $experiment_q = $query->execute();
            $experiment_value = $experiment_q->fetchObject();
            $query = \Drupal::database()->select('lab_migration_solution');
            $query->fields('lab_migration_solution');
            $query->condition('experiment_id', $form_state->getValue(['lab_experiment_list']));
            $query->orderBy('code_number', 'ASC');
            $solution_q = $query->execute();
            $solution_value = $solution_q->fetchObject();
            \Drupal::messenger()->addmessage(t('Approved Entire Experiment.'), 'status');
            /* email */
//             $email_subject = t('[!site_name] Your uploaded Lab Migration solution have been approved', [
//               '!site_name' => $config->get('site_name', '')
//               ]);
//             $email_body = [
//               0 => t('

// Dear !user_name,

// Your Experiment for R Lab Migration with the following details is approved.

// Experiment name: ' . $experiment_value->title . '
// Caption: ' . $solution_value->caption . '

// Best Wishes,

// !site_name Team,
// FOSSEE,IIT Bombay', [
//                 '!site_name' => $config->get('site_name', ''),
//                 '!user_name' => $user_data->name,
//               ])
//               ];
            /* email */
            /* $email_subject = t('Your uploaded Lab Migration solutions have been approved');
                $email_body = array(0 =>t('Your all the uploaded solutions for the experiment have been approved.'));*/
          }
          elseif (($form_state->getValue(['lab_actions']) == 0) && ($form_state->getValue(['lab_experiment_actions']) == 2) && ($form_state->getValue(['lab_experiment_solution_actions']) == 0)) {
            \Drupal::database()->query("UPDATE {lab_migration_solution} SET approval_status = 0 WHERE experiment_id = :experiment_id", [
              ":experiment_id" => $form_state->getValue(['lab_experiment_list'])
              ]);
            \Drupal::messenger()->addmessage(t('Entire Experiment marked as Pending Review.'), 'status');
            $query = \Drupal::database()->select('lab_migration_experiment');
            $query->fields('lab_migration_experiment');
            $query->condition('id', $form_state->getValue(['lab_experiment_list']));
            $query->orderBy('number', 'ASC');
            $experiment_q = $query->execute();
            $experiment_value = $experiment_q->fetchObject();
            $query = \Drupal::database()->select('lab_migration_solution');
            $query->fields('lab_migration_solution');
            $query->condition('experiment_id', $form_state->getValue(['lab_experiment_list']));
            $query->orderBy('code_number', 'ASC');
            $solution_q = $query->execute();
            $solution_value = $solution_q->fetchObject();
            /* email */
//             $email_subject = t('[!site_name] Your uploaded Lab Migration solution have been marked as pending', [
//               '!site_name' => $config->get('site_name', '')
//               ]);
//             $email_body = [
//               0 => t('

// Dear !user_name,

// Your all the uploaded solutions for the experiment have been marked as pending to be reviewed.

// Experiment name: ' . $experiment_value->title . '
// Caption: ' . $solution_value->caption . '

// Best Wishes,

// !site_name Team,
// FOSSEE,IIT Bombay', [
//                 '!site_name' => $config->get('site_name', ''),
//                 '!user_name' => $user_data->name,
//               ])
//               ];
            /* email */
            /*$email_subject = t('Your uploaded Lab Migration solutions have been marked as pending');
                $email_body = array(0 =>t('Your all the uploaded solutions for the experiment have been marked as pending to be review.'));*/
          }
          elseif (($form_state->getValue(['lab_actions']) == 0) && ($form_state->getValue(['lab_experiment_actions']) == 3) && ($form_state->getValue(['lab_experiment_solution_actions']) == 0)) {
            if (strlen(trim($form_state->getValue(['message']))) <= 30) {
              //$form_state->setErrorByName('message', t(''));
              \Drupal::messenger()->addmessage("Please mention the reason for disapproval. Minimum 30 character required", 'error');
              return;
            }
            if (!$user->hasPermission('lab migration bulk delete code')) {
              \Drupal::messenger()->addmessage(t('You do not have permission to Bulk Dis-Approved and Deleted Entire Experiment.'), 'error');
              return;
            }
            $query = \Drupal::database()->select('lab_migration_experiment');
            $query->fields('lab_migration_experiment');
            $query->condition('id', $form_state->getValue(['lab_experiment_list']));
            $query->orderBy('number', 'ASC');
            $experiment_q = $query->execute();
            $experiment_value = $experiment_q->fetchObject();
            $query = \Drupal::database()->select('lab_migration_solution');
            $query->fields('lab_migration_solution');
            $query->condition('experiment_id', $form_state->getValue(['lab_experiment_list']));
            $query->orderBy('code_number', 'ASC');
            $solution_q = $query->execute();
            $solution_value = $solution_q->fetchObject();
            if (\Drupal::service("lab_migration_global")->lab_migration_delete_experiment($form_state->getValue(['lab_experiment_list']))) {
              \Drupal::messenger()->addmessage(t('Dis-Approved and Deleted Entire Experiment.'), 'status');
            }
            else {
              \Drupal::messenger()->addmessage(t('Error Dis-Approving and Deleting Entire Experiment.'), 'error');
            }
            /* email */
//             $email_subject = t('[!site_name] Your uploaded Lab Migration solutions have been marked as dis-approved', [
//               '!site_name' => $config->get('site_name', '')
//               ]);
//             $email_body = [
//               0 => t('

// Dear !user_name,

// We regret to inform you that your experiment with the following details under R Lab Migration is disapproved and has been deleted.

// Experiment name: ' . $experiment_value->title . '
// Caption: ' . $solution_value->caption . '

// Reason for dis-approval: ' . $form_state->getValue(['message']) . '

// Please resubmit the modified solution.

// Best Wishes,

// !site_name Team,
// FOSSEE,IIT Bombay', [
//                 '!site_name' => $config->get('site_name', ''),
//                 '!user_name' => $user_data->name,
//               ])
//               ];
            /* email */
            /*$email_subject = t('Your uploaded Lab Migration solutions have been marked as dis-approved');
                $email_body = array(0 => t('Your uploaded solutions for the entire experiment have been marked as dis-approved.
                
                Reason for dis-approval:
                
                ' . $form_state['values']['message']));*/
          }
          elseif (($form_state->getValue(['lab_actions']) == 0) && ($form_state->getValue(['lab_experiment_actions']) == 0) && ($form_state->getValue(['lab_experiment_solution_actions']) == 1)) {
            $query = \Drupal::database()->select('lab_migration_solution');
            $query->fields('lab_migration_solution');
            $query->condition('id', $form_state->getValue(['solution_list']));
            $query->orderBy('code_number', 'ASC');
            $solution_q = $query->execute();
            $solution_value = $solution_q->fetchObject();
            $query = \Drupal::database()->select('lab_migration_experiment');
            $query->fields('lab_migration_experiment');
            $query->condition('id', $solution_value->experiment_id);
            $query->orderBy('number', 'ASC');
            $experiment_q = $query->execute();
            $experiment_value = $experiment_q->fetchObject();
            \Drupal::database()->query("UPDATE {lab_migration_solution} SET approval_status = 1, approver_uid = :approver_uid WHERE id = :id", [
              ":approver_uid" => $user->uid,
              ":id" => $form_state->getValue(['solution_list']),
            ]);
            \Drupal::messenger()->addmessage(t('Solution approved.'), 'status');
            /* email */
//             $email_subject = t('[!site_name] Your uploaded Lab Migration solution has been approved', [
//               '!site_name' => $config->get('site_name', '')
//               ]);
//             $email_body = [
//               0 => t('

// Dear !user_name,

// Your Experiment for R Lab Migration with the following details is approved.

// Experiment name: ' . $experiment_value->title . '
// Caption: ' . $solution_value->caption . '

// Best Wishes,

// !site_name Team,
// FOSSEE,IIT Bombay', [
//                 '!site_name' => $config->get('site_name', ''),
//                 '!user_name' => $user_data->name,
//               ])
//               ];
            /* email */
            /* $email_subject = t('Your uploaded Lab Migration solution has been approved');
                $email_body = array(0 =>t('Your uploaded solution has been approved.'));*/
          }
          elseif (($form_state->getValue(['lab_actions']) == 0) && ($form_state->getValue(['lab_experiment_actions']) == 0) && ($form_state->getValue(['lab_experiment_solution_actions']) == 2)) {
            $query = \Drupal::database()->select('lab_migration_solution');
            $query->fields('lab_migration_solution');
            $query->condition('id', $form_state->getValue(['solution_list']));
            $query->orderBy('code_number', 'ASC');
            $solution_q = $query->execute();
            $solution_value = $solution_q->fetchObject();
            $query = \Drupal::database()->select('lab_migration_experiment');
            $query->fields('lab_migration_experiment');
            $query->condition('id', $solution_value->experiment_id);
            $query->orderBy('number', 'ASC');
            $experiment_q = $query->execute();
            $experiment_value = $experiment_q->fetchObject();
            \Drupal::database()->query("UPDATE {lab_migration_solution} SET approval_status = 0 WHERE id = :id", [
              ":id" => $form_state->getValue(['solution_list'])
              ]);
            \Drupal::messenger()->addmessage(t('Solution marked as Pending Review.'), 'status');
            /* email */
//             $email_subject = t('[!site_name] Your uploaded Lab Migration solution has been marked as pending', [
//               '!site_name' => $config->get('site_name', '')
//               ]);
//             $email_body = [
//               0 => t('

// Dear !user_name,

// Your all the uploaded solutions for the experiment have been marked as pending to be reviewed.

// Experiment name: ' . $experiment_value->title . '
// Caption: ' . $solution_value->caption . '

// Best Wishes,

// !site_name Team,
// FOSSEE,IIT Bombay', [
//                 '!site_name' => $config->get('site_name', ''),
//                 '!user_name' => $user_data->name,
//               ])
//               ];
            /* email */
            /*$email_subject = t('Your uploaded Lab Migration solution has been marked as pending');
                $email_body = array(0 =>t('Your uploaded solution has been marked as pending to be review.'));*/
          }
          elseif (($form_state->getValue(['lab_actions']) == 0) && ($form_state->getValue(['lab_experiment_actions']) == 0) && ($form_state->getValue(['lab_experiment_solution_actions']) == 3)) {
            $query = \Drupal::database()->select('lab_migration_solution');
            $query->fields('lab_migration_solution');
            $query->condition('id', $form_state->getValue(['solution_list']));
            $query->orderBy('code_number', 'ASC');
            $solution_q = $query->execute();
            $solution_value = $solution_q->fetchObject();
            $query = \Drupal::database()->select('lab_migration_experiment');
            $query->fields('lab_migration_experiment');
            $query->condition('id', $solution_value->experiment_id);
            $query->orderBy('number', 'ASC');
            $experiment_q = $query->execute();
            $experiment_value = $experiment_q->fetchObject();
            if (strlen(trim($form_state->getValue(['message']))) <= 30) {
              //$form_state->setErrorByName('message', t(''));
              \Drupal::messenger()->addmessage("Please mention the reason for disapproval. Minimum 30 character required", 'error');
              return;
            }
            if (\Drupal::service("lab_migration_global")->lab_migration_delete_solution($form_state->getValue(['solution_list']))) {
              \Drupal::messenger()->addmessage(t('Solution Dis-Approved and Deleted.'), 'status');
            }
            else {
              \Drupal::messenger()->addmessage(t('Error Dis-Approving and Deleting Solution.'), 'error');
            }
            /* email */
//             $email_subject = t('[!site_name] Your uploaded Lab Migration solution has been marked as dis-approved', [
//               '!site_name' => $config->get('site_name', '')
//               ]);
//             $email_body = [
//               0 => t('

// Dear !user_name,

// We regret to inform you that your experiment with the following details under R Lab Migration is disapproved and has been deleted.

// Experiment name: ' . $experiment_value->title . '
// Caption: ' . $solution_value->caption . '

// Reason for dis-approval: ' . $form_state->getValue(['message']) . '

// Please resubmit the modified solution.

// Best Wishes,

// !site_name Team,
// FOSSEE,IIT Bombay', [
//                 '!site_name' => $config->get('site_name', ''),
//                 '!user_name' => $user_data->name,
//               ])
//               ];
            /* email */
            /* $email_subject = t('Your uploaded Lab Migration solution has been marked as dis-approved');
                $email_body = array(0 =>t('Your uploaded solution has been marked as dis-approved.
                
                Reason for dis-approval:
                
                ' . $form_state['values']['message']));*/
          }
          else {
            \Drupal::messenger()->addmessage(t('Please select only one action at a time'), 'error');
            return;
          }
          /** sending email when everything done **/
        // if ($email_subject) {
        //     $email_to = $user_data->mail;
        //     $from = $config->get('lab_migration_from_email', '');
        //     $bcc = $config->get('lab_migration_emails', '');
        //     $cc = $config->get('lab_migration_cc_emails', '');
        //     $param['standard']['subject'] = $email_subject;
        //     $param['standard']['body'] = $email_body;
        //     $param['standard']['headers'] = [
        //       'From' => $from,
        //       'MIME-Version' => '1.0',
        //       'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        //       'Content-Transfer-Encoding' => '8Bit',
        //       'X-Mailer' => 'Drupal',
        //       'Cc' => $cc,
        //       'Bcc' => $bcc,
        //     ];
        //     if (!drupal_mail('lab_migration', 'standard', $email_to, language_default(), $param, $from, TRUE)) {
        //       \Drupal::messenger()->addmessage('Error sending email message.', 'error');
        //     }
        //   }
        }
        // else {
        //   \Drupal::messenger()->addmessage(t('You do not have permission to bulk manage code.'), 'error');
        // }
      }
    //}
    return $msg;
  }
 }
?>