<?php

/**
 * @file
 * Contains \Drupal\dwsim_flowsheet\Form\DwsimFlowsheetRunForm.
 */

namespace Drupal\dwsim_flowsheet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Database\Database;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\user\Entity\User;

class DwsimFlowsheetRunForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dwsim_flowsheet_run_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $options_first = $this->_list_of_flowsheet();
    $url_flowsheet_id = (int) \Drupal::routeMatch()->getParameter('id');
    // $url_flowsheet_id = (int) arg(2);
    $flowsheet_data = $this->_flowsheet_information($url_flowsheet_id);
    if ($flowsheet_data == 'Not found') {
      $url_flowsheet_id = '';
    } //$flowsheet_data == 'Not found'
    if (!$url_flowsheet_id) {
      $selected = !$form_state->getValue(['flowsheet']) ? $form_state->getValue(['flowsheet']) : key($options_first);
    } //!$url_flowsheet_id
    elseif ($url_flowsheet_id == '') {
      $selected = 0;
    } //$url_flowsheet_id == ''
    else {
      $selected = $url_flowsheet_id;
    }
    $form = [];
    $form['flowsheet'] = [
      '#type' => 'select',
      '#title' => t('Title of the flowsheet'),
      '#options' => $this->_list_of_flowsheet(),
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => '::dwsim_flowsheet_project_details_callback'
        ],
    ];
    if (!$url_flowsheet_id) {
      $form['flowsheet_details'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax_flowsheet_details"></div>',
      ];
      $form['selected_flowsheet'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax_selected_flowsheet"></div>',
      ];
    } //!$url_flowsheet_id
    else {
      $flowsheet_default_value = $url_flowsheet_id;
      $form['flowsheet_details'] = [
        '#type' => 'item',
        '#markup' => '<div id="ajax_flowsheet_details">' . $this->_flowsheet_details($flowsheet_default_value) . '</div>',
      ];
      // @FIXME
      // l() expects a Url object, created from a route name or external URI.
      // $form['selected_flowsheet'] = array(
      // 			'#type' => 'item',
      // 			'#markup' => '<div id="ajax_selected_flowsheet">' . l('Download Abstract', 'flowsheeting-project/download/project-file/' . $flowsheet_default_value) . '<br>' . l('Download Flowsheet', 'flowsheeting-project/full-download/project/' . $flowsheet_default_value,array('attributes' => array('title' => 'This is a zip file containing a pdf (abstract) and a dwxml/dwxmz file which is the DWSIM flow sheet which is to be viewed by right clicking on the file and opening with DWSIM.'))) . '</div>'
      // 		);
// Create the URLs for the links.
$abstract_url = Url::fromUserInput('/flowsheeting-project/download/project-file/' . $flowsheet_default_value);
$flowsheet_url = Url::fromUserInput('/flowsheeting-project/full-download/project/' . $flowsheet_default_value, [
    'attributes' => [
        'title' => t('This is a zip file containing a PDF (abstract) and a DWXML/DWMXZ file, which is the DWSIM flowsheet to be viewed by right-clicking on the file and opening with DWSIM.'),
    ],
]);

// Create the links.
$abstract_link = Link::fromTextAndUrl(t('Download Abstract'), $abstract_url)->toString();
$flowsheet_link = Link::fromTextAndUrl(t('Download Flowsheet'), $flowsheet_url)->toString();

// Build the form item with the markup.
$form['selected_flowsheet'] = [
    '#type' => 'item',
    '#markup' => '<div id="ajax_selected_flowsheet">' . $abstract_link . '<br>' . $flowsheet_link . '</div>',
];
    }
    return $form;
  }

 
  
  function dwsim_flowsheet_project_details_callback($form, &$form_state)
  {
      $response = new AjaxResponse();
      $flowsheet_default_value = $form_state->getValue('flowsheet');
  
      if ($flowsheet_default_value != 0) {
          $flowsheet_details_markup = $this->_flowsheet_details($flowsheet_default_value);
          $response->addCommand(new HtmlCommand('#ajax_flowsheet_details', $flowsheet_details_markup));
  
          $flowsheet_details = $this->_flowsheet_information($flowsheet_default_value);
  
          if ($flowsheet_details && $flowsheet_details->uid > 0) {
              $user = User::load($flowsheet_details->uid); // Load user object
  
              $abstract_link = Link::fromTextAndUrl(
                  'Download Abstract',
                  Url::fromUri('internal:/flowsheeting-project/download/project-file/' . $flowsheet_default_value)
              )->toString();
  
              $flowsheet_link = Link::fromTextAndUrl(
                  'Download Flowsheet',
                  Url::fromUri('internal:/flowsheeting-project/full-download/project/' . $flowsheet_default_value, [
                      'attributes' => [
                          'title' => 'This is a zip file containing a PDF (abstract) and a DWSIM flow sheet.'
                      ]
                  ])
              )->toString();
  
              $response->addCommand(new HtmlCommand('#ajax_selected_flowsheet', $abstract_link . '<br>' . $flowsheet_link));
          } else {
              $response->addCommand(new HtmlCommand('#ajax_selected_flowsheet', ''));
              $response->addCommand(new HtmlCommand('#ajax_selected_flowsheet_dwsim', ''));
          }
      } else {
          $response->addCommand(new HtmlCommand('#ajax_flowsheet_details', ''));
          $response->addCommand(new HtmlCommand('#ajax_selected_flowsheet', ''));
          $response->addCommand(new HtmlCommand('#ajax_selected_flowsheet_dwsim', ''));
      }
  
      return $response;
  }
  
  function bootstrap_table_format($headers, $rows)
  {
      $thead = '';
      $tbody = '';
  
      foreach ($headers as $header) {
          $thead .= '<th>' . htmlspecialchars($header) . '</th>';
      }
  
      foreach ($rows as $row) {
          $tbody .= '<tr>';
          foreach ($row as $data) {
              $tbody .= '<td>' . htmlspecialchars($data) . '</td>';
          }
          $tbody .= '</tr>';
      }
  
      $table = "
          <table class='table table-bordered table-hover' style='margin-left:-140px'>
              <thead><tr>{$thead}</tr></thead>
              <tbody>{$tbody}</tbody>
          </table>
      ";
  
      return Markup::create($table);
  }
  

  public function _list_of_flowsheet()
  {
      $flowsheet_titles = [
          '0' => 'Please select...'
      ];
  
      $connection = \Drupal::database();
      $query = $connection->select('dwsim_flowsheet_proposal', 'd')
          ->fields('d')
          ->condition('d.approval_status', 3)
          ->orderBy('d.project_title', 'ASC');
  
      $flowsheet_titles_q = $query->execute();
      while ($flowsheet_titles_data = $flowsheet_titles_q->fetchObject()) {
          $flowsheet_titles[$flowsheet_titles_data->id] = $flowsheet_titles_data->project_title
              . ' (Proposed by ' . $flowsheet_titles_data->name_title . ' ' . $flowsheet_titles_data->contributor_name . ')';
      }
      return $flowsheet_titles;
  }
  public function _flowsheet_information($proposal_id)
  {
      $connection = \Drupal::database();
      $query = $connection->select('dwsim_flowsheet_proposal', 'd')
          ->fields('d')
          ->condition('d.id', $proposal_id)
          ->condition('d.approval_status', 3);
  
      $flowsheet_q = $query->execute();
      $flowsheet_data = $flowsheet_q->fetchObject();
  
      return $flowsheet_data ?: 'Not found';
  }

  
  public function _flowsheet_details($flowsheet_default_value)
  {
      if ($flowsheet_default_value == 0) {
          return '';
      }
  
      $flowsheet_details = $this->_flowsheet_information($flowsheet_default_value);
  
      if (!$flowsheet_details || $flowsheet_details == 'Not found') {
          return 'Flowsheet not found.';
      }
  
      $link = Link::fromTextAndUrl(
          $flowsheet_details->project_title,
          Url::fromUri('internal:/flowsheeting-project/full-download/project/' . $flowsheet_default_value, [
              'attributes' => ['title' => 'This is a zip file containing a pdf and a DWSIM flow sheet.']
          ])
      )->toString();
  
      $details = '<span style="color: rgb(128, 0, 0);"><strong>About the Flowsheet</strong></span><br>'
          . '<ul>'
          . '<li><strong>Proposer Name:</strong> ' . $flowsheet_details->name_title . ' ' . $flowsheet_details->contributor_name . '</li>'
          . '<li><strong>Title of the Flowsheet:</strong> ' . $link . '</li>'
          . '<li><strong>Institution:</strong> ' . $flowsheet_details->university . '</li>'
          . '<li><strong>Version:</strong> ' . $flowsheet_details->version . '</li>'
          . '<li><strong>Reference:</strong> ' . $flowsheet_details->reference . '</li>'
          . '</ul>';
  
      return $details;
  }
      
// public function _list_of_flowsheet()
// {
// 	$flowsheet_titles = array(
// 		'0' => 'Please select...'
// 	);
// 	//$lab_titles_q = db_query("SELECT * FROM {dwsim_flowsheet_proposal} WHERE solution_display = 1 ORDER BY lab_title ASC");
// 	$connection = \Drupal::database();
// $query = $connection->select('dwsim_flowsheet_proposal', 'd');
// $query->fields('d');
// $query->condition('d.approval_status', 3);
// $query->orderBy('d.project_title', 'ASC');
// 	$flowsheet_titles_q = $query->execute();
// 	while ($flowsheet_titles_data = $flowsheet_titles_q->fetchObject()) {
// 		$flowsheet_titles[$flowsheet_titles_data->id] = $flowsheet_titles_data->project_title . ' (Proposed by ' . $flowsheet_titles_data->name_title . ' ' . $flowsheet_titles_data->contributor_name . ')';
// 	} //$flowsheet_titles_data = $flowsheet_titles_q->fetchObject()
// 	return $flowsheet_titles;
// }
// public function _flowsheet_information($proposal_id)
// {
// 	$query = db_select('dwsim_flowsheet_proposal');
// 	$query->fields('dwsim_flowsheet_proposal');
// 	$query->condition('id', $proposal_id);
// 	$query->condition('approval_status', 3);
// 	$flowhsheet_q = $query->execute();
// 	$flowsheet_data = $flowhsheet_q->fetchObject();
// 	if ($flowsheet_data) {
// 		return $flowsheet_data;
// 	} //$flowsheet_data
// 	else {
// 		return 'Not found';
// 	}
// }
// public function _flowsheet_details($flowsheet_default_value)
// {
// 	$flowsheet_details = _flowsheet_information($flowsheet_default_value);
// 	if ($flowsheet_default_value != 0) {
// 		$form['flowsheet_details']['#markup'] = '<span style="color: rgb(128, 0, 0);"><strong>About the Flowsheet</strong></span></td><td style="width: 35%;"><br />' . '<ul>' . '<li><strong>Proposer Name:</strong> ' . $flowsheet_details->name_title . ' ' . $flowsheet_details->contributor_name . '</li>' . '<li><strong>Title of the Flowhseet:</strong> ' . l($flowsheet_details->project_title,'flowsheeting-project/full-download/project/' . $flowsheet_default_value,array('attributes' => array('title' => 'This is a zip file containing a pdf (abstract) and a dwxml/dwxmz file which is the DWSIM flow sheet which is to be viewed by right clicking on the file and opening with DWSIM.'))) . '</li>' . '<li><strong>Institution:</strong> ' . $flowsheet_details->university . '</li>' . '<li><strong>Version:</strong> ' . $flowsheet_details->version . '</li>' . '<li><strong>Reference:</strong> ' . $flowsheet_details->reference . '</li>' . '</ul>';
// 		$details = $form['flowsheet_details']['#markup'];
// 		return $details;
// 	} //$flowsheet_default_value != 0
// }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state){
  }
}
?>
