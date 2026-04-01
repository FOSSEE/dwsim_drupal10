<?php

namespace Drupal\lab_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Database\Database;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RendererInterface;
use ZipArchieve;

class LabMigrationRunForm extends FormBase {

  /**
  * {@inheritdoc}
  */
  public function getFormId() {
  return 'lab_migration_run_form';
  }
  
  /**
  * {@inheritdoc}
  */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state)

  {
    $options_first =$this->_list_of_labs();
    $options_two = $this->_ajax_get_experiment_list();
    // $select_two = isset($form_state['values']['lab_experiment_list']) ? $form_state['values']['lab_experiment_list'] : key($options_two);
    $select_two = $form_state->getValue('lab_experiment_list') ?: key($options_two);
    // $url_lab_id = (int) arg(2);
    $route_match = \Drupal::routeMatch();
    $url_lab_id = (int) $route_match->getParameter('url_lab_id');
    if (!$url_lab_id)
      {
        // $selected = isset($form_state['values']['lab']) ? $form_state['values']['lab'] : key($options_first);
     $selected = $form_state->getValue('lab') ?: key($options_first);
      }
    elseif ($url_lab_id == '')
      {
        $selected = 0;
      }
    else
      {
        $selected = $url_lab_id;
      }
    $form = [];
    $form['lab'] = array(
        '#type' => 'select',
        '#title' => t('Title of the lab'),
        '#options' => $this->_list_of_labs(),
        '#default_value' => $selected,
        '#ajax' => [
            'callback' => '::ajax_experiment_list_callback',
            'wrapper' => 'ajax_selected_lab'
        ]
    );
    $form['download_lab_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'ajax_selected_lab'],
    ];
        $lab_default_value = $form_state->getValue('lab') ?: $url_lab_id;
  //var_dump($lab_default_value);die;
            $form['download_lab_wrapper']['selected_lab'] = [
              '#type' => 'markup',
              '#markup' => 
                Link::fromTextAndUrl(
                  $this->t('Download Lab Solutions'), 
                  Url::fromUri('internal:/lab-migration/download/lab/' . $lab_default_value)
                )->toString()
            ];
        $form['download_lab_wrapper']['lab_details'] = array(
            '#type' => 'item',
            '#markup' => $this->_lab_details($lab_default_value)
        );
        $form['download_lab_wrapper']['lab_experiment_list'] = array(
            '#type' => 'select',
            '#title' => t('Title of the experiment'),
            '#options' => $this->_ajax_get_experiment_list($lab_default_value),
            // '#default_value' => isset($form_state['values']['lab_experiment_list']) ? $form_state['values']['lab_experiment_list'] : '',
            '#ajax' => [
                'callback' => '::ajax_solution_list_callback',
                'wrapper'  => 'ajax_download_experiments'
              ],
            '#prefix' => '<div id="ajax_selected_experiment">',
            '#suffix' => '</div>',
            '#states' => array(
                'invisible' => array(
                    ':input[name="lab"]' => array(
                        'value' => 0
                    )
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
        $form['download_experiment_wrapper']['solution_list'] = [
          '#type' => 'select',
            '#title' => t('Title of the solution'),
            '#options' => $this->_ajax_get_solution_list($form_state->getValue('lab_experiment_list')),
            '#ajax' => [
                'callback' => '::ajax_solution_files_callback',
                'wrapper'  => 'ajax_download_solution_file'
              ],
        ];
       //for download solution

        $form['download_solution_wrapper'] = [
          '#type' => 'container',
          '#attributes' => ['id' => 'ajax_download_solution_file'],
        ];
        $form['download_solution_wrapper']['download_solution'] = [
          '#type' => 'item',
          '#markup' => Link::fromTextAndUrl('Download Solution', Url::fromUri('internal:/lab-migration/download/solution/' . $form_state->getValue('solution_list')))->toString()
        ];
     
$query = \Drupal::database()->select('lab_migration_solution_files', 's');
      $query->fields('s');
      $query->condition('solution_id', $form_state->getValue('solution_list'));
      $solution_list_q = $query->execute();
      if ($solution_list_q) {
        $solution_files_rows = [];
        while ($solution_list_data = $solution_list_q->fetchObject()) {
 
//var_dump($solution_list_data);die;
          // $solution_file_type = [];
          // var_dump($solution_list_data->filetype);die;
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
        
        
      
        
        
       
      return $form;
}

  

    
  public function ajax_experiment_list_callback(array &$form, FormStateInterface $form_state) {
    return $form['download_lab_wrapper'];

  }
  public function ajax_solution_list_callback(array &$form, FormStateInterface $form_state) {
    return $form['download_experiment_wrapper'];
    
//     $response = new AjaxResponse();
//     $experiment_list_default_value = $form_state->getValue('lab_experiment_list');
//     // var_dump($experiment_list_default_value);die;
//     if ($experiment_list_default_value != 0) {
//       $exp_download_link = Link::fromTextAndUrl(
//         $this->t('Download Experiment'),
//         Url::fromRoute('lab_migration.download_experiment', ['experiment_id' => $experiment_list_default_value])
//       )->toString();
//       // Update the solution list options
     
      
//       // Add the commands to update the DOM
      
//       $response->addCommand(new HtmlCommand('#ajax_download_experiments', Link::fromTextAndUrl('Download Experiment', Url::fromUri('internal:/lab-migration/download/experiment/' . $experiment_list_default_value))->toString()));
//       $response->addCommand(new HtmlCommand('#ajax_selected_experiment', \Drupal::service('renderer')->render($form['lab_experiment_list'])));
//       //$form['lab_solution_list']['#options'] = $this->_ajax_get_solution_list($experiment_list_default_value);
//       //$response->addCommand(new HtmlCommand('#ajax_download_experiments'), $exp_download_link);
//       //$response->addCommand(new HtmlCommand('#ajax_selected_solution', \Drupal::service('renderer')->render($form['lab_solution_list'])));
//       $form_state->setRebuild(TRUE);
//     }
//     // else {
//     //   // Default options when no experiment is selected
//     //   $form['lab_solution_list']['#options'] = $this->_ajax_get_solution_list();
      
//     //   // Clear the DOM elements
//     //       $commands = [];
//     //       $response->addCommand(new HtmlCommand('#ajax_selected_solution', \Drupal::service('renderer')->render($form['lab_solution_list'])));
//     //   $commands[] = new HtmlCommand('#ajax_download_experiments', '');
//     //   $commands[] = new HtmlCommand('#ajax_selected_solution', '');
//     //   $commands[] = new HtmlCommand('#ajax_solution_files', '');
//     //   $commands[] = new HtmlCommand('#ajax_download_experiment_solution', '');
//     //   $commands[] = new HtmlCommand('#ajax_edit_experiment_solution', '');
//     //   // Uncomment if needed
//     //   // $commands[] = new ReplaceCommand('#ajax_selected_experiment', \Drupal::service('renderer')->render($form['lab_experiment_list']));
//     // }
    
//     // Return the response with commands
//     // $response = new AjaxResponse();
//     // $response->addCommand(new AppendCommand('#element-id', 'Updated content'));
//     //return $response;
  

// return $response;
  }
  
  public function ajax_solution_files_callback(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

return $form['download_solution_wrapper'];
    //     $response = new AjaxResponse();
//    // $commands = [];
//   //  var_dump("hi");die;
    // $solution_list_default_value = $form_state->getValue('lab_solution_list');
  
//     if ($solution_list_default_value != 0) {
//       // Render experiment solution actions
//       $response->addCommand(new HtmlCommand('#ajax_selected_lab_experiment_solution_action', \Drupal::service('renderer')->render($form['lab_experiment_solution_actions'])));
  
//       // Query solution files
//       $query = \Drupal::database()->select('lab_migration_solution_files', 's');
//       $query->fields('s');
//       $query->condition('solution_id', $solution_list_default_value);
//       $solution_list_q = $query->execute();
  
//       if ($solution_list_q) {
//         $solution_files_rows = [];
//         while ($solution_list_data = $solution_list_q->fetchObject()) {
//           $solution_file_type = '';
//           switch ($solution_list_data->filetype) {
//             case 'S':
//               $solution_file_type = 'Source or Main file';
//               break;
//             case 'R':
//               $solution_file_type = 'Result file';
//               break;
//             case 'X':
//               $solution_file_type = 'xcos file';
//               break;
//             default:
//               $solution_file_type = 'Unknown';
//               break;
//           }
//           // Create file download link
//           $solution_files_rows[] = [
//             Link::fromTextAndUrl($solution_list_data->filename, Url::fromUri('internal:/lab-migration/download/file/' . $solution_list_data->id))->toString(),
//             $solution_file_type
//           ];
//         }
  
//         // Query dependencies
        
//         // Build the table of files
//         $solution_files_header = ['Filename', 'Type'];
//         $solution_files = \Drupal::service('renderer')->render([
//           '#theme' => 'table',
//           '#header' => $solution_files_header,
//           '#rows' => $solution_files_rows
//         ]);
//       }
  
     
    
//       // Add the download and edit links
     
// $link = Link::fromTextAndUrl(
//   $this->t('Download Solution'),
//   Url::fromRoute('lab_migration.download_solution', ['solution' => $solution_list_default_value])
// )->toString();
// // Add the AJAX command to update the element with ID `#ajax_download_experiment_solution`.
// $response->addCommand(new HtmlCommand('#ajax_download_experiment_solution', $link));
//       // Uncomment if needed
//       // $commands[] = new HtmlCommand('#ajax_edit_experiment_solution', Link::fromTextAndUrl('Edit Solution', Url::fromUri('internal:/code_approval/editcode/' . $solution_list_default_value))->toString());
  
//       // Add the solution files table to the page
//      $response->addCommand(new HtmlCommand('#ajax_solution_files', \Drupal::service('renderer')->render($form['solution_files'])));
//     } else {
//       // If no solution is selected, clear the areas
//       $commands[] = new HtmlCommand('#ajax_selected_lab_experiment_solution_action', '');
//       $commands[] = new HtmlCommand('#ajax_download_experiment_solution', '');
//       $commands[] = new HtmlCommand('#ajax_edit_experiment_solution', '');
//       $commands[] = new HtmlCommand('#ajax_solution_files', '');
//     }
  
    // Return the AJAX response
    // $response->addCommands($commands);
    // return $response;
  }
  
  public function bootstrap_table_format(array $headers, array $rows) {
    // Define the table header and rows.
    $table_header = [];
    foreach ($headers as $header) {
      $table_header[] = ['data' => $header, 'header' => TRUE];
    }
  
    // Define the table rows.
    $table_rows = [];
    foreach ($rows as $row) {
      $table_row = [];
      foreach ($row as $data) {
        $table_row[] = ['data' => $data];
      }
      $table_rows[] = $table_row;
    }
  
    // Create a table render array with Drupal's table theming.
    $table = [
      '#theme' => 'table',
      '#header' => $table_header,
      '#rows' => $table_rows,
      '#attributes' => ['class' => ['table', 'table-bordered', 'table-hover']],
    ];
  
    // Render the table using Drupal's renderer.
    $renderer = \Drupal::service('renderer');
    return $renderer->render($table);
  }
  
/*****************************************************/
public function _list_of_labs()
  {
    $lab_titles = array(
        '0' => 'Please select...'
    );
    //$lab_titles_q = db_query("SELECT * FROM {lab_migration_proposal} WHERE solution_display = 1 ORDER BY lab_title ASC");
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('solution_display', 1);
    $query->condition('approval_status', 3);
    $query->orderBy('lab_title', 'ASC');
    $lab_titles_q = $query->execute();
    while ($lab_titles_data = $lab_titles_q->fetchObject())
      {
        $lab_titles[$lab_titles_data->id] = $lab_titles_data->lab_title . ' (Proposed by ' . $lab_titles_data->name_title .' '.$lab_titles_data->name . ')';
      }
    return $lab_titles;
  }
public function _ajax_get_experiment_list($lab_default_value = '')
  {
    $experiments = array(
        '0' => 'Please select...'
    );
    //$experiments_q = db_query("SELECT * FROM {lab_migration_experiment} WHERE proposal_id = %d ORDER BY number ASC", $proposal_id);
    $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('proposal_id', $lab_default_value);
    $query->orderBy('number', 'ASC');
    $experiments_q = $query->execute();
    while ($experiments_data = $experiments_q->fetchObject())
      {
        $experiments[$experiments_data->id] = $experiments_data->number . '. ' . $experiments_data->title;
      }
    return $experiments;
  }
  public function _ajax_get_solution_list($lab_experiment_list = '') {
    // var_dump("Hii");
    $solutions = [
      '0' => t('Please select...'),
    ];
  
    if (empty($lab_experiment_list)) {
      return $solutions;
    }
  
    // Query the database to get solutions for the given experiment.
    $connection = Database::getConnection();
    $query = $connection->select('lab_migration_solution', 'lms');
    $query->fields('lms', ['id', 'code_number', 'caption']);
    $query->condition('experiment_id', $lab_experiment_list);
    $results = $query->execute();
  
    // Process the query results and populate the solutions array.
    foreach ($results as $record) {
      $solutions[$record->id] = $record->code_number . ' (' . $record->caption . ')';
    }
  
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
  public function _lab_experiment_information($proposal_id){
    $database = \Drupal::database();
    $query = $database->query ("SELECT title FROM lab_migration_proposal
    INNER JOIN  lab_migration_experiment
    ON lab_migration_proposal.id = lab_migration_experiment.proposal_id");
    $result = $query->fetchAll();$database = \Drupal::database();
    $query = $database->query ("SELECT title
    FROM lab_migration_proposal
    INNER JOIN  lab_migration_experiment
    ON lab_migration_proposal.id = lab_migration_experiment.proposal_id where lab_migration_proposal.id = :proposal_id", [":proposal_id" => $proposal_id]);
    // $result = $query->fetchAll();
    // if ($result) {
    $experiment_details = '<strong>Experiment Details</strong><ul>';
      while ($row = $query->fetchObject()){
        //$row['title'];
        $experiment_details .= '<li><strong>Title of the Experiment: </strong>' . $row->title . '</li>';
    }
   
   return $experiment_details;
    //var_dump($result);die;
  }

public function _lab_details($lab_default_value)
  {
    // $lab_default_value = $form_state['values']['lab'];
    $lab_details = $this->_lab_information($lab_default_value);
    //$experiment_details = $this->_lab_experiment_information($lab_default_value);
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
      
    $form['lab_details']['#markup'] = '<span style="color: rgb(128, 0, 0);"><strong>About the Lab</strong></span></td><td style="width: 35%;"><br />' . '<ul>' . '<li><strong>Proposer Name:</strong> ' . $lab_details->name_title . ' ' . $lab_details->name . '</li>' . '<li><strong>Title of the Lab:</strong> ' . $lab_details->lab_title . '</li>' . '<li><strong>Department:</strong> ' . $lab_details->department . '</li>' . '<li><strong>University:</strong> ' . $lab_details->university . '</li>' . '<li><strong>Version:</strong> ' . $lab_details->version . '</li>' . '<li><strong>Operating System:</strong> ' . $lab_details->operating_system . '</li>' . '</ul>' . $solution_provider;

    $details = $form['lab_details']['#markup'];
    return $details;
    
      }
      
    }
  

  public function ajax_bulk_experiment_list_callback(array &$form,\Drupal\Core\Form\FormStateInterface $form_state) {
    $response = new AjaxResponse();
  
    // Get the selected lab value.
    $lab_default_value = $form_state->getValue('lab');
  
    if ($lab_default_value != 0) {
      // Generate a link for download.
      $download_url = Url::fromUserInput('/lab-migration/full-download/lab/' . $lab_default_value);
      $download_link = Link::fromTextAndUrl(t('Download'), $download_url)->toString();
      $response->addCommand(new HtmlCommand('#ajax_selected_lab', $download_link . ' ' . t('(Download all the approved and unapproved solutions of the entire lab)')));
  // var_dump(hii);die;
      // Update lab actions and experiment list.
      $form['lab_actions']['#options'] = _bulk_list_lab_actions();
      $form['lab_experiment_list']['#options'] = $this->_ajax_bulk_get_experiment_list($lab_default_value);
  
      $renderer = \Drupal::service('renderer');
      $response->addCommand(new ReplaceCommand('#ajax_selected_experiment', $renderer->render($form['lab_experiment_list'])));
      $response->addCommand(new ReplaceCommand('#ajax_selected_lab_action', $renderer->render($form['lab_actions'])));
  
      // Clear other sections.
      $response->addCommand(new HtmlCommand('#ajax_selected_solution', ''));
      $response->addCommand(new HtmlCommand('#ajax_selected_lab_experiment_action', ''));
      $response->addCommand(new HtmlCommand('#ajax_selected_lab_experiment_solution_action', ''));
      $response->addCommand(new HtmlCommand('#ajax_solution_files', ''));
      $response->addCommand(new HtmlCommand('#ajax_download_experiment_solution', ''));
      $response->addCommand(new HtmlCommand('#ajax_edit_experiment_solution', ''));
    } else {
      // Clear all sections if no lab is selected.
      $response->addCommand(new HtmlCommand('#ajax_selected_lab', ''));
      $response->addCommand(new HtmlCommand('#ajax_selected_lab_pdf', ''));
      $response->addCommand(new HtmlCommand('#ajax_selected_experiment', ''));
      $response->addCommand(new HtmlCommand('#ajax_selected_lab_action', ''));
      $response->addCommand(new HtmlCommand('#ajax_selected_lab_experiment_action', ''));
      $response->addCommand(new HtmlCommand('#ajax_selected_lab_experiment_solution_action', ''));
      $response->addCommand(new HtmlCommand('#ajax_solution_files', ''));
      $response->addCommand(new HtmlCommand('#ajax_download_experiment_solution', ''));
      $response->addCommand(new HtmlCommand('#ajax_edit_experiment_solution', ''));
    }
  
    return $response;
  }
  
  public function _ajax_bulk_get_experiment_list($lab_default_value = '') {
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



  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
  }
}