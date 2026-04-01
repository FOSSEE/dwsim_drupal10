<?php

/**
 * @file
 * Contains \Drupal\dwsim_flowsheet\Form\DwsimFlowsheetCompletedTabForm.
 */

namespace Drupal\dwsim_flowsheet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\DataCommand;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Database\Database;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Mail\MailManager;

class DwsimFlowsheetCompletedTabForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dwsim_flowsheet_completed_tab_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
     $flowsheet_output = $this->_flowsheet_details('2023');

    // You can render the result as part of the form
    $form['flowsheet_output'] = [
        '#markup' => $flowsheet_output,
    ];
    $options_first = $this->_flowsheet_details_year_wise();
    $selected = !$form_state->getValue(['howmany_select']) ? $form_state->getValue(['howmany_select']) : key($options_first);
    $form = [];
    $form['howmany_select'] = [
      '#title' => t('Sorting projects according to year:'),
      '#type' => 'select',
      '#options' => $this->_flowsheet_details_year_wise(),
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => '::ajax_example_autocheckboxes_callback'
        ],
      '#suffix' => '<div id="ajax-selected-flowsheet"></div>',
    ];
   
    // var_dump( _flowsheet_details(2024));die;
    return $form;
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handle form submission logic here.
    \Drupal::messenger()->addMessage($this->t('Form submitted successfully!'));
  }

  
  public function ajax_example_autocheckboxes_callback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $flowsheet_project_default_value = $form_state->getValue('howmany_select'); // Correct form state access
  
    if ($flowsheet_project_default_value != 0) {
      // Update the form options dynamically
      $form['howmany_select']['#options'] = ['Please select...' => 'Please select...'];
      
      // Example dynamic content
      $dynamic_content = $this->_flowsheet_details($flowsheet_project_default_value);
      
      // Replace content dynamically
      $response->addCommand(new HtmlCommand('#ajax-selected-flowsheet', $dynamic_content));
      $response->addCommand(new ReplaceCommand('#ajax_selected_flowsheet_action', \Drupal::service('renderer')->render($form['howmany_select'])));
    } 
    else {
      // Reset the form state value to "Please select..."
      $form['howmany_select']['#options'] = ['Please select...' => 'Please select...'];
      
      // Use DataCommand for JavaScript context
      $response->addCommand(new DataCommand('#ajax_selected_flowsheet', 'form_state_value_select', $form_state->getValue('howmany_select')));
    }
  
    return $response;
  }
  
  // public function ajax_example_autocheckboxes_callback(array &$form, FormStateInterface $form_state)
  // {
	//   $commands = array();
	//   $flowsheet_project_default_value = $form_state['values']['howmany_select'];
	//   if ($flowsheet_project_default_value != 0)
	//   {
	// 	  $form['howmany_select']['#options'] =  array('Please select...' => 'Please select...');
	// 	  $commands[] = ajax_command_html('#ajax-selected-flowsheet', $this->_flowsheet_details($flowsheet_project_default_value));
	// 	  $commands[] = ajax_command_replace('#ajax_selected_flowsheet_action', drupal_render($form['howmany_select']));
		  
	//   } 
	//   else
	//   {
	// 	  $form['howmany_select']['#options'] =  array('Please select...' => 'Please select...');
	// 	  $commands[] = ajax_command_data('#ajax_selected_flowsheet', 'form_state_value_select', $form_state['values']['howmany_select']);
	//   }
	//   return array(
	// 	  '#type' => 'ajax',
	// 	  '#commands' => $commands
	//   );
  // }


  public function _flowsheet_details_year_wise()
{
  $flowsheet_years = array(
  '0' => 'Please select...'
);
  $result = \Drupal::database()->query("SELECT from_unixtime(actual_completion_date, '%Y ') as Year from dwsim_flowsheet_proposal WHERE approval_status = 3 ORDER BY Year ASC");
  
  while ($year_wise_list_data = $result->fetchObject())
    {
      $flowsheet_years[$year_wise_list_data->Year] = $year_wise_list_data->Year;
    }
  return $flowsheet_years;
}


public function _flowsheet_details($flowsheet_proposal_id) {
  $output = "";
 
// $actual_completion_date = 'FROM_UNIXTIME(actual_completion_date, \'%Y\')';
  // Define the database connection.
  $connection = Database::getConnection();

  // Define and execute the query.
  $result = \Drupal::database()->query('SELECT * FROM `dwsim_flowsheet_proposal` 
WHERE `approval_status` = 3 
  AND YEAR(FROM_UNIXTIME(`actual_completion_date`)) = :Year',array(':Year' => $flowsheet_proposal_id));

  $records = $result->fetchAll();
  if (count($records) == 0) {
      $output .= "Work has been completed for the following flow sheets.";
  } 
  else {
      $output .= "Work has been completed for the following flow sheets: " . count($records) . "<hr>";
      $preference_rows = [];
      $i = 1;
      // 
     
      foreach ($records as $row) {
       
          $completion_date = date("d-M-Y", $row->actual_completion_date);
          $project_url = Link::fromTextAndUrl($row->project_title, Url::fromRoute('dwsim_flowsheet.run_form'))->toString();
         
         
          $preference_rows[$row->id] = [
              $i,
              $project_url ,
              $row->contributor_name,
              $row->university,
              $completion_date
          ];
          $i++;
      }

      $preference_header = [
          'No',
          'Flowsheet Project',
          'Contributor Name',
          'University / Institute',
          'Date of Completion'
      ];

      // Render the table with the theme function.
      $output =  [
        '#type' => 'table',
        '#header' => $preference_header,
        '#rows' => $preference_rows,
        
      ];
  }
 
  return $output;
}

}
?>
