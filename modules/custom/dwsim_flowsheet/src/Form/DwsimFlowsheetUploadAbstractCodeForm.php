<?php

/**
 * @file
 * Contains \Drupal\dwsim_flowsheet\Form\DwsimFlowsheetUploadAbstractCodeForm.
 */

namespace Drupal\dwsim_flowsheet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
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

class DwsimFlowsheetUploadAbstractCodeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dwsim_flowsheet_upload_abstract_code_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $form['#attributes'] = ['enctype' => "multipart/form-data"];
    /* get current approved proposal */
    $proposal_data = dwsim_flowsheet_get_proposal();
    if (!$proposal_data) {
      \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
      $response = new RedirectResponse(Url::fromUri('internal:/flowsheeting-project/abstract-code')->toString());
      $response->send();
      return;
    }
    $query = \Drupal::database()->select('dwsim_flowsheet_submitted_abstracts');
    $query->fields('dwsim_flowsheet_submitted_abstracts');
    $query->condition('proposal_id', $proposal_data->id);
    $abstracts_q = $query->execute()->fetchObject();
    if ($abstracts_q) {
      if ($abstracts_q->is_submitted == 1) {
        \Drupal::messenger()->addError(t('Your abstract is under review, you can not edit exisiting abstract without reviewer permission.'));
        // drupal_goto('flowsheeting-project/abstract-code');
        
        $response = new RedirectResponse(Url::fromRoute('dwsim_flowsheet.upload_abstract_code_form')->toString());
    // Send the redirect response
    $response->send();
        return;
      } //$abstracts_q->is_submitted == 1
    } //$abstracts_q->is_submitted == 1
    $form['project_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->project_title,
      '#title' => t('Title of the Flowsheet Project'),
    ];
    $form['version'] = [
      '#type' => 'item',
      '#title' => t('DWSIM version'),
      '#markup' => $proposal_data->version,
    ];
    if ($abstracts_q == TRUE) {
      if ($abstracts_q->unit_operations_used_in_dwsim) {
        $existing_unit_operations_used_in_dwsim = $this->default_value_for_selections("unit_operations_used_in_dwsim", $proposal_data->id);
        $form['unit_operations_used_in_dwsim'] = [
          '#type' => 'select',
          '#title' => t('Unit Operations used in DWSIM'),
          '#options' => _df_list_of_unit_operations(),
          '#required' => TRUE,
          '#default_value' => $existing_unit_operations_used_in_dwsim,
          '#size' => '20',
          '#multiple' => TRUE,
          '#description' => t('[You can select multiple options by holding ctrl + left key of mouse]'),
        ];
      } //$abstracts_q->unit_operations_used_in_dwsim
    } //$abstracts_q->unit_operations_used_in_dwsim
    else {
      $form['unit_operations_used_in_dwsim'] = [
        '#type' => 'select',
        '#title' => t('Unit Operations used in DWSIM'),
        '#options' => _df_list_of_unit_operations(),
        '#required' => TRUE,
        '#size' => '20',
        '#multiple' => TRUE,
        '#description' => t('[You can select multiple options by holding ctrl + left key of mouse]'),
      ];
    }
    if ($abstracts_q == TRUE) {
      if ($abstracts_q->thermodynamic_packages_used) {
        $existing_thermodynamic_packages_used = $this->default_value_for_selections("thermodynamic_packages_used", $proposal_data->id);
        $form['thermodynamic_packages_used'] = [
          '#type' => 'select',
          '#title' => t('Thermodynamic Packages Used'),
          '#options' => _df_list_of_thermodynamic_packages(),
          '#required' => TRUE,
          '#size' => '20',
          '#default_value' => $existing_thermodynamic_packages_used,
          '#multiple' => TRUE,
          '#description' => t('[You can select multiple options by holding ctrl + left key of mouse]'),
        ];
      } //$abstracts_q->thermodynamic_packages_used
    } //$abstracts_q == TRUE
    else {
      $form['thermodynamic_packages_used'] = [
        '#type' => 'select',
        '#title' => t('Thermodynamic Packages Used'),
        '#options' => _df_list_of_thermodynamic_packages(),
        '#required' => TRUE,
        '#size' => '20',
        '#multiple' => TRUE,
        '#description' => t('[You can select multiple options by holding ctrl + left key of mouse]'),
      ];
    }
    if ($abstracts_q == TRUE) {
      //var_dump($abstracts_q->logical_blocks_used);die;
      if ($abstracts_q->logical_blocks_used != "Not entered") {
        $existing_logical_blocks_used = $this->default_value_for_selections("logical_blocks_used", $proposal_data->id);
        $form['logical_blocks_used'] = [
          '#type' => 'select',
          '#title' => t('Logical Blocks used (If any)'),
          '#options' => _df_list_of_logical_block(),
          '#default_value' => $existing_logical_blocks_used,
          '#multiple' => TRUE,
          '#description' => t('[You can select multiple options by holding ctrl + left key of mouse]'),
        ];
      } //$abstracts_q->logical_blocks_used != "Not entered"
      else {
        $form['logical_blocks_used'] = [
          '#type' => 'select',
          '#title' => t('Logical Blocks used (If any)'),
          '#options' => _df_list_of_logical_block(),
          '#multiple' => TRUE,
          '#description' => t('[You can select multiple options by holding ctrl + left key of mouse]'),
        ];
      }
    } //$abstracts_q == TRUE
    else {
      $form['logical_blocks_used'] = [
        '#type' => 'select',
        '#title' => t('Logical Blocks used (If any)'),
        '#options' => _df_list_of_logical_block(),
        '#multiple' => TRUE,
        '#description' => t('[You can select multiple options by holding ctrl + left key of mouse]'),
      ];
    }
    $headers = [
      "Name of compound for which process development is carried out",
      "CAS No.",
    ];
    $rows = [];
    $item = [
      "{$proposal_data->process_development_compound_name}",
      "{$proposal_data->process_development_compound_cas_number}"
      ,
    ];
    array_push($rows, $item);
    // @FIXME
    // theme() has been renamed to _theme() and should NEVER be called directly.
    // Calling _theme() directly can alter the expected output and potentially
    // introduce security issues (see https://www.drupal.org/node/2195739). You
    // should use renderable arrays instead.
    // 
    // 
    // @see https://www.drupal.org/node/2195739
    // $prodata = theme('table', array(
    // 		'header' => $headers,
    // 		'rows' => $rows
    // 	));

    $page_content = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
    ];

    $form['process_development_compound_name'] = [
      '#type' => 'fieldset',
      '#title' => t('Name of compound for which process development is carried out'),
    ];
    $form['process_development_compound_name']['table'] = $page_content;
    if ($proposal_data->dwsim_database_compound_name) {
      $existing_dwsim_database_compound_name = $this->default_value_for_selections("dwsim_database_compound_name", $proposal_data->id);
      $form['list_of_compounds_from_dwsim_database_used_in_process_flowsheet'] = [
        '#type' => 'select',
        '#title' => t('List of compounds from DWSIM Database used in process flowsheet'),
        '#options' => _df_list_of_dwsim_compound(),
        '#default_value' => $existing_dwsim_database_compound_name,
        '#size' => '20',
        '#multiple' => TRUE,
        '#description' => t('[You can select multiple options by holding ctrl + left key of mouse]'),
      ];
    } //$proposal_data->dwsim_database_compound_name
    else {
      $form['list_of_compounds_from_dwsim_database_used_in_process_flowsheet'] = [
        '#type' => 'select',
        '#title' => t('List of compounds from DWSIM Database used in process flowsheet'),
        '#options' => _df_list_of_dwsim_compound(),
        '#size' => '20',
        '#multiple' => TRUE,
      ];
    }
    /////////////////////////////////////////////////////
    //Edit user defiend compounds
    $query_u = \Drupal::database()->select('dwsim_flowsheet_user_defined_compound');
    $query_u->fields('dwsim_flowsheet_user_defined_compound');
    $query_u->condition('proposal_id', $proposal_data->id);
    $result_u = $query_u->execute();
    $result_u_fetch =  $result_u->fetchAll();
    $num_of_user_defined_compounds_results = count($result_u_fetch);
    $form['user_defined_compound_fieldset'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#prefix' => '<div id="user-defined-compounds-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];
    if ($num_of_user_defined_compounds_results != 0) {
      $form_state->set(['num_user_defined_compounds'], $num_of_user_defined_compounds_results);
      $temp = 0;
      foreach ($result_u_fetch as $i => $row_udc) {
        $temp = $i;
        $form['user_defined_compound_fieldset'][$i]["s_text"] = [
          "#type" => "item",
          "#markup" => "<h4><label>User defined compounds : " . ($temp + 1) . "</label></h4>",
        ];
        $form['user_defined_compound_fieldset'][$i]["udc_id"] = [
          "#type" => "hidden",
          "#default_value" => $row_udc->id,
        ];
        $form['user_defined_compound_fieldset'][$i]["user_defined_compound"] = [
          "#type" => "textfield",
          "#title" => "Name of the user defined compound",
          "#default_value" => $row_udc->user_defined_compound,
        ];
        $form['user_defined_compound_fieldset'][$i]["cas_no"] = [
          "#type" => "textfield",
          "#title" => "CAS No.",
          "#default_value" => $row_udc->cas_no,
        ];
      }
      $form['user_defined_compound_fieldset']["user_defined_compound_count"] = [
        "#type" => "hidden",
        "#value" => $temp,
      ];
      /*$form['user_defined_compound_fieldset']['add_user_defined_compounds'] = array(
			'#type' => 'submit',
			'#value' => t('Add more compounds'),
			'#limit_validation_errors' => array(),
			'#submit' => array(
				'user_defined_compounds_add_more_add_one'
			),
			'#ajax' => array(
				'callback' => 'user_defined_compounds_add_more_callback',
				'wrapper' => 'user-defined-compounds-fieldset-wrapper'
			)
		);*/
      ////////////////////////////
      $existing_uploaded_udc_file = $this->default_value_for_uploaded_files("UDC", $proposal_data->id);
      if (!$existing_uploaded_udc_file) {
        $existing_uploaded_udc_file = new \stdClass();
        $existing_uploaded_udc_file->filename = "No file uploaded";
      } //!$existing_uploaded_udc_file
      if (basename($existing_uploaded_udc_file->user_defined_compound_filepath) == 'NULL' || basename($existing_uploaded_udc_file->user_defined_compound_filepath) == '') {
        $udcfilename = 'No file uploaded';
      } //basename($existing_uploaded_udc_file->user_defined_compound_filepath) == 'NULL' || basename($existing_uploaded_udc_file->user_defined_compound_filepath) == ''
      else {
        $udcfilename = basename($existing_uploaded_udc_file->user_defined_compound_filepath);
      }
      $form['upload_an_udc'] = [
        '#type' => 'file',
        '#title' => t('Upload an user defiend compound.'),
        '#description' => t('<span style="color:red;">Current File :</span> ' . $udcfilename . '<br />Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . t('<span style="color:red;">Allowed file extensions : ') . \Drupal::config('dwsim_flowsheet.settings')->get('dwsim_flowsheet_user_defind_compound_source_extensions') . '</span>',
      ];
      /////////////////////////////
    } //$num_of_user_defined_compounds_results != 0
    else {
      if (!$form_state->get(['num_user_defined_compounds'])) {
        $form_state->set(['num_user_defined_compounds'], 1);
      } //empty($form_state['num_user_defined_compounds'])
      $temp = 0;
      for ($i = 0; $i < $form_state->get(['num_user_defined_compounds']); $i++) {
        $temp = $i;
        $form['user_defined_compound_fieldset'][$i]["s_text"] = [
          "#type" => "item",
          "#markup" => "<h4><label>User defined compounds : " . ($temp + 1) . "</label></h4>",
        ];
        $form['user_defined_compound_fieldset'][$i]["udc_id"] = [
          "#type" => "hidden",
          "#default_value" => "",
        ];
        $form['user_defined_compound_fieldset'][$i]["user_defined_compound"] = [
          "#type" => "textfield",
          "#title" => "Name of the user defined compound",
          "#default_value" => "",
        ];
        $form['user_defined_compound_fieldset'][$i]["cas_no"] = [
          "#type" => "textfield",
          "#title" => "CAS No.",
          "#default_value" => "",
        ];
      } //$i = 0; $i < $form_state['num_user_defined_compounds']; $i++
      $form['user_defined_compound_fieldset']["user_defined_compound_count"] = [
        "#type" => "hidden",
        "#value" => $temp,
      ];
      $form['user_defined_compound_fieldset']['add_user_defined_compounds'] = [
        '#type' => 'submit',
        '#value' => t('Add more compounds'),
        '#limit_validation_errors' => [],
        '#submit' => [
          'user_defined_compounds_add_more_add_one'
          ],
        '#ajax' => [
          'callback' => 'user_defined_compounds_add_more_callback',
          'wrapper' => 'user-defined-compounds-fieldset-wrapper',
        ],
      ];
      if ($form_state->get(['num_user_defined_compounds']) > 1) {
        $form['user_defined_compound_fieldset']['remove_user_defined_compounds'] = [
          '#type' => 'submit',
          '#value' => t('Remove compounds'),
          '#limit_validation_errors' => [],
          '#submit' => [
            'user_defined_compounds_add_more_remove_one'
            ],
          '#ajax' => [
            'callback' => 'user_defined_compounds_add_more_remove_one',
            'wrapper' => 'user-defined-compounds-fieldset-wrapper',
          ],
        ];
      } //$form_state['num_user_defined_compounds'] > 1
      $existing_uploaded_udc_file = $this->default_value_for_uploaded_files("UDC", $proposal_data->id);
      if (!$existing_uploaded_udc_file) {
        $existing_uploaded_udc_file = new \stdClass();
        $existing_uploaded_udc_file->filename = "No file uploaded";
      } //!$existing_uploaded_udc_file
      if (basename($existing_uploaded_udc_file->user_defined_compound_filepath) == 'NULL' || basename($existing_uploaded_udc_file->user_defined_compound_filepath) == '') {
        $udcfilename = 'No file uploaded';
      } //basename($existing_uploaded_udc_file->user_defined_compound_filepath) == 'NULL' || basename($existing_uploaded_udc_file->user_defined_compound_filepath) == ''
      else {
        $udcfilename = basename($existing_uploaded_udc_file->user_defined_compound_filepath);
      }
      $form['upload_an_udc'] = [
        '#type' => 'file',
        '#title' => t('Upload an user defiend compound.'),
        '#description' => t('<span style="color:red;">Current File :</span> ' . $udcfilename . '<br />Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . t('<span style="color:red;">Allowed file extensions : ') . \Drupal::config('dwsim_flowsheet.settings')->get('dwsim_flowsheet_user_defind_compound_source_extensions') . '</span>',
      ];
      if ($no_js_use) {
        if (!empty($form['user_defined_compound_fieldset']['remove_user_defined_compounds']['#ajax'])) {
          unset($form['user_defined_compound_fieldset']['remove_user_defined_compounds']['#ajax']);
        } //!empty($form['user_defined_compound_fieldset']['remove_user_defined_compounds']['#ajax'])
        unset($form['user_defined_compound_fieldset']['add_user_defined_compounds']['#ajax']);
      } //$no_js_use
    }
    //////////////////////////////////////////////////////
    $existing_uploaded_A_file = $this->default_value_for_uploaded_files("A", $proposal_data->id);
    if (!$existing_uploaded_A_file) {
      $existing_uploaded_A_file = new \stdClass();
      $existing_uploaded_A_file->filename = "No file uploaded";
    } //!$existing_uploaded_A_file
    $form['upload_an_abstract'] = [
      '#type' => 'file',
      '#title' => t('Upload an abstract (brief outline) of the project.'),
      '#description' => t('<span style="color:red;">Current File :</span> ' . $existing_uploaded_A_file->filename . '<br />Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . t('<span style="color:red;">Allowed file extensions : ') . \Drupal::config('dwsim_flowsheet.settings')->get('dwsim_flowsheet_abstract_upload_extensions') . '</span>',
    ];
    $existing_uploaded_S_file = $this->default_value_for_uploaded_files("S", $proposal_data->id);
    if (!$existing_uploaded_S_file) {
      $existing_uploaded_S_file = new \stdClass();
      $existing_uploaded_S_file->filename = "No file uploaded";
    } //!$existing_uploaded_S_file
    $form['upload_flowsheet_developed_process'] = [
      '#type' => 'file',
      '#title' => t('Upload the DWSIM flowsheet for the developed process.'),
      '#description' => t('<span style="color:red;">Current File :</span> ' . $existing_uploaded_S_file->filename . '<br />Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . t('<span style="color:red;">Allowed file extensions : ') . \Drupal::config('dwsim_flowsheet.settings')->get('dwsim_flowsheet_flowsheet_developed_process_source_extensions') . '</span>',
    ];
    $form['prop_id'] = [
      '#type' => 'hidden',
      '#value' => $proposal_data->id,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    // @FIXME
    // l() expects a Url object, created from a route name or external URI.
    // $form['cancel'] = array(
    // 		'#type' => 'item',
    // 		'#markup' => l(t('Cancel'), 'flowsheeting-project/manage-proposal')
    // 	);
    // $response->send();
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if ($form_state->getValue(['unit_operations_used_in_dwsim'])) {
      $unit_operations_used_in_dwsim = implode(", ", $_POST['unit_operations_used_in_dwsim']);
      $form_state->setValue(['unit_operations_used_in_dwsim'], $unit_operations_used_in_dwsim);
    } //$form_state['values']['unit_operations_used_in_dwsim']
    else {
      $form_state->setErrorByName('unit_operations_used_in_dwsim', t('Please select.'));
    }
    if ($form_state->getValue(['thermodynamic_packages_used'])) {
      $thermodynamic_packages_used = implode(", ", $_POST['thermodynamic_packages_used']);
      $form_state->setValue(['thermodynamic_packages_used'], $thermodynamic_packages_used);
    } //$form_state['values']['thermodynamic_packages_used']
    else {
      $form_state->setErrorByName('thermodynamic_packages_used', t('Please select.'));
    }
    if ($form_state->getValue(['logical_blocks_used']) != "") {
      $logical_blocks_used_in = $_POST['logical_blocks_used'];
      if ($logical_blocks_used_in != "") {
        if ($logical_blocks_used_in) {
          $logical_blocks_used = implode(", ", $logical_blocks_used_in);
          $form_state->setValue(['logical_blocks_used'], $logical_blocks_used);
        } //$form_state['values']['logical_blocks_used']
      } //$logical_blocks_used_in != ""
      else {
        $form_state->setValue(['logical_blocks_used'], "Not entered");
      }
    } //$form_state['values']['logical_blocks_used']
    else {
      $form_state->setValue(['logical_blocks_used'], "Not entered");
    }
    if ($form_state->getValue([
      'list_of_compounds_from_dwsim_database_used_in_process_flowsheet'
      ])) {
      $list_of_compounds_from_dwsim_database_used_in_process_flowsheet = implode("| ", $_POST['list_of_compounds_from_dwsim_database_used_in_process_flowsheet']);
      $form_state->setValue([
        'list_of_compounds_from_dwsim_database_used_in_process_flowsheet'
        ], $list_of_compounds_from_dwsim_database_used_in_process_flowsheet);
    } //$form_state['values']['list_of_compounds_from_dwsim_database_used_in_process_flowsheet']
    if (isset($_FILES['files'])) {
      /* check if file is uploaded */
      $existing_uploaded_A_file = $this->default_value_for_uploaded_files("A", $form_state->getValue([
        'prop_id'
        ]));
      $existing_uploaded_S_file = $this->default_value_for_uploaded_files("S", $form_state->getValue([
        'prop_id'
        ]));
      $existing_uploaded_udc_file = $this->default_value_for_uploaded_files("UDC", $form_state->getValue([
        'prop_id'
        ]));
      if (!$existing_uploaded_S_file) {
        if (!($_FILES['files']['name']['upload_flowsheet_developed_process'])) {
          $form_state->setErrorByName('upload_flowsheet_developed_process', t('Please upload the file.'));
        }
      } //!$existing_uploaded_S_file
      if (!$existing_uploaded_A_file) {
        if (!($_FILES['files']['name']['upload_an_abstract'])) {
          $form_state->setErrorByName('upload_an_abstract', t('Please upload the file.'));
        }
      } //!$existing_uploaded_A_file
      if (!$existing_uploaded_udc_file) {
        if (!($_FILES['files']['name']['upload_an_udc'])) {
          $form_state->setErrorByName('upload_an_udc', t('Please upload the file.'));
        }
      } //!$existing_uploaded_udc_file
		/* check for valid filename extensions */
      if ($_FILES['files']['name']['upload_an_udc'] || $_FILES['files']['name']['upload_an_abstract'] || $_FILES['files']['name']['upload_flowsheet_developed_process']) {
        foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
          if ($file_name) {
            /* checking file type */
            if (strstr($file_form_name, 'upload_flowsheet_developed_process')) {
              $file_type = 'S';
            }
            else {
              if (strstr($file_form_name, 'upload_an_abstract')) {
                $file_type = 'A';
              }
              else {
                if (strstr($file_form_name, 'upload_an_udc')) {
                  $file_type = 'UDC';
                }
                else {
                  $file_type = 'U';
                }
              }
            }
            $allowed_extensions_str = '';
            switch ($file_type) {
              case 'S':
                $allowed_extensions_str = \Drupal::config('dwsim_flowsheet.settings')->get('dwsim_flowsheet_flowsheet_developed_process_source_extensions');
                break;
              case 'A':
                $allowed_extensions_str = \Drupal::config('dwsim_flowsheet.settings')->get('dwsim_flowsheet_abstract_upload_extensions');
                break;
              case 'UDC':
                $allowed_extensions_str = \Drupal::config('dwsim_flowsheet.settings')->get('dwsim_flowsheet_user_defind_compound_source_extensions');
                break;
            } //$file_type
            $allowed_extensions = explode(',', $allowed_extensions_str);
            $tmp_ext = explode('.', strtolower($_FILES['files']['name'][$file_form_name]));
            $temp_extension = end($tmp_ext);
            if (!in_array($temp_extension, $allowed_extensions)) {
              $form_state->setErrorByName($file_form_name, t('Only file with ' . $allowed_extensions_str . ' extensions can be uploaded.'));
            }
            if ($_FILES['files']['size'][$file_form_name] <= 0) {
              $form_state->setErrorByName($file_form_name, t('File size cannot be zero.'));
            }
            /* check if valid file name */
            if (!dwsim_flowsheet_check_valid_filename($_FILES['files']['name'][$file_form_name])) {
              $form_state->setErrorByName($file_form_name, t('Invalid file name specified. Only alphabets and numbers are allowed as a valid filename.'));
            }
          } //$file_name
        } //$_FILES['files']['name'] as $file_form_name => $file_name
      } //$_FILES['files']['name'] as $file_form_name => $file_name
    } //isset($_FILES['files'])
    // drupal_add_js('jQuery(document).ready(function () { alert("Hello!"); });', 'inline');
    // drupal_static_reset('drupal_add_js') ;
  }

  function default_value_for_selections($operation, $proposal_id) {
    $query = Database::getConnection()->select('dwsim_flowsheet_submitted_abstracts', 'a');
    $query->fields('a');
    $query->condition('proposal_id', $proposal_id);
    $abstracts_q = $query->execute()->fetchObject();

    $selected_package_array = [];

    if ($abstracts_q) {
        if ($operation === "unit_operations_used_in_dwsim" && !empty($abstracts_q->unit_operations_used_in_dwsim)) {
            $selected_package_array = array_map('trim', explode(',', $abstracts_q->unit_operations_used_in_dwsim));
        } elseif ($operation === "thermodynamic_packages_used" && !empty($abstracts_q->thermodynamic_packages_used)) {
            $selected_package_array = array_map('trim', explode(',', $abstracts_q->thermodynamic_packages_used));
        } elseif ($operation === "logical_blocks_used" && !empty($abstracts_q->logical_blocks_used)) {
            $selected_package_array = array_map('trim', explode(',', $abstracts_q->logical_blocks_used));
        } elseif ($operation === "dwsim_database_compound_name") {
            $query = Database::getConnection()->select('dwsim_flowsheet_proposal', 'p');
            $query->fields('p');
            $query->condition('id', $proposal_id);
            $proposal_q = $query->execute()->fetchObject();

            if (!empty($proposal_q->dwsim_database_compound_name)) {
                $selected_package_array = array_map('trim', explode('| ', $proposal_q->dwsim_database_compound_name));
            }
        }
    }

    return $selected_package_array;
}

function default_value_for_uploaded_files($filetype, $proposal_id) {
    $selected_files_array = null;

    if (in_array($filetype, ['A', 'S'])) {
        $query = Database::getConnection()->select('dwsim_flowsheet_submitted_abstracts_file', 'f');
        $query->fields('f');
        $query->condition('proposal_id', $proposal_id);
        $query->condition('filetype', $filetype);
        $selected_files_array = $query->execute()->fetchObject();
    } elseif ($filetype === "UDC") {
        $query = Database::getConnection()->select('dwsim_flowsheet_proposal', 'p');
        $query->fields('p');
        $query->condition('id', $proposal_id);
        $selected_files_array = $query->execute()->fetchObject();
    }

    return $selected_files_array;
}


  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $user_id_int = (int) $user->id();
    // var_dump($user_id_int);die;
    $v = $form_state->getValues();
    $root_path = dwsim_flowsheet_path();
    $proposal_data = dwsim_flowsheet_get_proposal();
    // var_dump($proposal_data->id);die;
    $proposal_id = $proposal_data->id;
    if (!$proposal_data) {
      // drupal_goto('');
      $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
      // Send the redirect response
      $response->send();
      return;
    } //!$proposal_data
    $proposal_id = $proposal_data->id;
    $proposal_directory = $proposal_data->directory_name;
    /* create proposal folder if not present */
    $dest_path = $proposal_directory . '/';
    $dest_path_udc = $proposal_directory . '/user_defined_compound/';
    if (!is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path);
    }
    if ($proposal_data) {
      $query = "UPDATE {dwsim_flowsheet_proposal} SET 
	dwsim_database_compound_name =  :dwsim_database_compound_name
 
	WHERE id = :proposal_id
	";
      $args = [
        ":dwsim_database_compound_name" => $v['list_of_compounds_from_dwsim_database_used_in_process_flowsheet'],
        ":proposal_id" => $proposal_id,
      ];
      $submitted_proposal_id = \Drupal::database()->query($query, $args);
    } //$proposal_data
    $proposal_id = $proposal_data->id;
    $query_s = "SELECT * FROM {dwsim_flowsheet_submitted_abstracts} WHERE proposal_id = :proposal_id";
    $args_s = [":proposal_id" => $proposal_id];
    $query_s_result = \Drupal::database()->query($query_s, $args_s)->fetchObject();
    if (!$query_s_result) {
      /* creating solution database entry */
      $query = "INSERT INTO {dwsim_flowsheet_submitted_abstracts} (
	proposal_id,
	approver_uid,
	abstract_approval_status,
	unit_operations_used_in_dwsim,
	thermodynamic_packages_used,
	logical_blocks_used,
	abstract_upload_date,
	abstract_approval_date,
	is_submitted) VALUES (:proposal_id, :approver_uid, :abstract_approval_status, :unit_operations_used_in_dwsim, 
  :thermodynamic_packages_used, :logical_blocks_used, :abstract_upload_date, :abstract_approval_date, :is_submitted)";
      $args = [
        ":proposal_id" => $proposal_id,
        ":approver_uid" => 0,
        ":abstract_approval_status" => 0,
        ":unit_operations_used_in_dwsim" => $v['unit_operations_used_in_dwsim'],
        ":thermodynamic_packages_used" => $v['thermodynamic_packages_used'],
        ":logical_blocks_used" => $v['logical_blocks_used'],
        ":abstract_upload_date" => time(),
        ":abstract_approval_date" => 0,
        ":is_submitted" => 0,
      ];
     \Drupal::database()->query($query, $args);
     $submitted_abstract_id = \Drupal::database()->lastInsertId();
      \Drupal::messenger()->addStatus('Abstract uploaded successfully.');
    } //!$query_s_result
    else {
      $query = "UPDATE {dwsim_flowsheet_submitted_abstracts} SET 
	unit_operations_used_in_dwsim=  :unit_operations_used_in_dwsim,
	thermodynamic_packages_used= :thermodynamic_packages_used,
	logical_blocks_used=:logical_blocks_used,
	abstract_upload_date =:abstract_upload_date,
	is_submitted= :is_submitted 
	WHERE proposal_id = :proposal_id
	";
      $args = [
        ":unit_operations_used_in_dwsim" => $v['unit_operations_used_in_dwsim'],
        ":thermodynamic_packages_used" => $v['thermodynamic_packages_used'],
        ":logical_blocks_used" => $v['logical_blocks_used'],
        ":abstract_upload_date" => time(),
        ":is_submitted" => 0,
        ":proposal_id" => $proposal_id,
      ];
      \Drupal::database()->query($query, $args);
      // var_dump($submitted_abstract_id);die;
      \Drupal::messenger()->addStatus('Abstract updated successfully.');
    }
    // For editing user defiend compounds
    $user_defined_compoundupload = 0;
    for ($i = 0; $i <= $v['user_defined_compound_fieldset']["user_defined_compound_count"]; $i++) {
      $udc_id = $v['user_defined_compound_fieldset'][$i]["udc_id"];
      if ($udc_id != "") {
        if ($v['user_defined_compound_fieldset'][$i]["user_defined_compound"] != "") {
          $query = \Drupal::database()->update('dwsim_flowsheet_user_defined_compound');
          $query->fields([
            'user_defined_compound' => $v['user_defined_compound_fieldset'][$i]["user_defined_compound"],
            'cas_no' => $v['user_defined_compound_fieldset'][$i]["cas_no"],
          ]);
          $query->condition('id', $v['user_defined_compound_fieldset'][$i]["udc_id"]);
          $result = $query->execute();
          if ($result != 0) {
            $user_defined_compoundupload++;
          } //$result != 0
        } //$v['user_defined_compound_fieldset'][$i]["user_defined_compound"] != ""
      } //$udc_id != ""
      else {
        if ($v['user_defined_compound_fieldset'][$i]["user_defined_compound"] != "") {
          $user_defined_compoundquery = "
	INSERT INTO dwsim_flowsheet_user_defined_compound
	(proposal_id,user_defined_compound,cas_no)
	VALUES
	(:proposal_id,:user_defined_compound,:cas_no)
	";
          $user_defined_compoundargs = [
            ":proposal_id" => $proposal_id,
            ":user_defined_compound" => $v['user_defined_compound_fieldset'][$i]["user_defined_compound"],
            ":cas_no" => $v['user_defined_compound_fieldset'][$i]["cas_no"],
          ];
          /* storing the row id in $result */
          $user_defined_compoundresult = \Drupal::database()->query($user_defined_compoundquery, $user_defined_compoundargs, $user_defined_compoundquery);
          if ($user_defined_compoundresult != 0) {
            $user_defined_compoundupload++;
          } //$user_defined_compoundresult != 0
        } //$v['user_defined_compound_fieldset'][$i]["user_defined_compound"] != ""
      }
    } //$i = 0; $i <= $v["user_defined_compound_count"]; $i++
	/* uploading files */
    foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
      if ($file_name) {
        /* checking file type */
        if (strstr($file_form_name, 'upload_flowsheet_developed_process')) {
          $file_type = 'S';
        } //strstr($file_form_name, 'upload_flowsheet_developed_process')
        else {
          if (strstr($file_form_name, 'upload_an_abstract')) {
            $file_type = 'A';
          } //strstr($file_form_name, 'upload_an_abstract')
          else {
            if (strstr($file_form_name, 'upload_an_udc')) {
              $file_type = 'UDC';
            } //strstr($file_form_name, 'upload_an_udc')
            else {
              $file_type = 'U';
            }
          }
        }
        switch ($file_type) {
          case 'S':
            if (file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
              //unlink($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);
              move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);
              \Drupal::messenger()->addError(t("File !filename already exists hence overwirtten the exisitng file ", [
                '!filename' => $_FILES['files']['name'][$file_form_name]
                ]));
            } //file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
					/* uploading file */
            else {
              if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
                /* for uploaded files making an entry in the database */
                $query_ab_f = "SELECT * FROM dwsim_flowsheet_submitted_abstracts_file WHERE proposal_id = :proposal_id AND filetype = 
				:filetype";
                $args_ab_f = [
                  ":proposal_id" => $proposal_id,
                  ":filetype" => $file_type,
                ];
                $query_ab_f_result = \Drupal::database()->query($query_ab_f, $args_ab_f)->fetchObject();
                if (!$query_ab_f_result) {
                  $query = "INSERT INTO {dwsim_flowsheet_submitted_abstracts_file} (submitted_abstract_id, proposal_id, uid, approvar_uid, filename, filepath, filemime, filesize, filetype, timestamp)
          VALUES (:submitted_abstract_id, :proposal_id, :uid, :approvar_uid, :filename, :filepath, :filemime, :filesize, :filetype, :timestamp)";
                  $args = [
                    ":submitted_abstract_id" => $submitted_abstract_id,
                    ":proposal_id" => $proposal_id,
                    ":uid" => $user_id_int,
                    ":approvar_uid" => 0,
                    ":filename" => $_FILES['files']['name'][$file_form_name],
                    ":filepath" => $_FILES['files']['name'][$file_form_name],
                    ":filemime" => mime_content_type($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]),
                    ":filesize" => $_FILES['files']['size'][$file_form_name],
                    ":filetype" => $file_type,
                    ":timestamp" => time(),
                  ];
                  \Drupal::database()->query($query, $args);
                  \Drupal::messenger()->addStatus($file_name . ' uploaded successfully.');
                } //!$query_ab_f_result
                else {
                  unlink($root_path . $dest_path . $query_ab_f_result->filename);
                  $query = "UPDATE {dwsim_flowsheet_submitted_abstracts_file} SET filename = :filename, filepath=:filepath, filemime=:filemime, filesize=:filesize, timestamp=:timestamp WHERE proposal_id = :proposal_id AND filetype = :filetype";
                  $args = [
                    ":filename" => $_FILES['files']['name'][$file_form_name],
                    ":filepath" => $file_path . $_FILES['files']['name'][$file_form_name],
                    ":filemime" => mime_content_type($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]),
                    ":filesize" => $_FILES['files']['size'][$file_form_name],
                    ":timestamp" => time(),
                    ":proposal_id" => $proposal_id,
                    ":filetype" => $file_type,
                  ];
                  \Drupal::database()->query($query, $args);
                  \Drupal::messenger()->addStatus($file_name . ' file updated successfully.');
                }
              } //move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
              else {
                \Drupal::messenger()->addError('Error uploading file : ' . $dest_path . $file_name);
              }
            }
            break;
          case 'A':
            if (file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
              //unlink($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);		
              move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);

              \Drupal::messenger()->addError(t("File !filename already exists hence overwirtten the exisitng file ", [
                '!filename' => $_FILES['files']['name'][$file_form_name]
                ]));
            } //file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
					/* uploading file */
            else {
              if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
                /* for uploaded files making an entry in the database */
                $query_ab_f = "SELECT * FROM dwsim_flowsheet_submitted_abstracts_file WHERE proposal_id = :proposal_id AND filetype = 
				:filetype";
                $args_ab_f = [
                  ":proposal_id" => $proposal_id,
                  ":filetype" => $file_type,
                ];
                $query_ab_f_result = \Drupal::database()->query($query_ab_f, $args_ab_f)->fetchObject();
           
                if (!$query_ab_f_result) {
                  $query = "INSERT INTO {dwsim_flowsheet_submitted_abstracts_file} (submitted_abstract_id, proposal_id, uid, approvar_uid, filename, filepath, filemime, filesize, filetype, timestamp)
          VALUES (:submitted_abstract_id, :proposal_id, :uid, :approvar_uid, :filename, :filepath, :filemime, :filesize, :filetype, :timestamp)";
                  $args = [
                    ":submitted_abstract_id" => $submitted_abstract_id,
                    ":proposal_id" => $proposal_id,
                    ":uid" => $user_id_int,
                    ":approvar_uid" => 0,
                    ":filename" => $_FILES['files']['name'][$file_form_name],
                    ":filepath" => $_FILES['files']['name'][$file_form_name],
                    ":filemime" => mime_content_type($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]),
                    ":filesize" => $_FILES['files']['size'][$file_form_name],
                    ":filetype" => $file_type,
                    ":timestamp" => time()
                  ];
                 \Drupal::database()->query($query, $args);
                  \Drupal::messenger()->addStatus($file_name . ' uploaded successfully.');
                } //!$query_ab_f_result
                else {
                  unlink($root_path . $dest_path . $query_ab_f_result->filename);
                  $query = "UPDATE {dwsim_flowsheet_submitted_abstracts_file} SET filename = :filename, filepath=:filepath, filemime=:filemime, filesize=:filesize, timestamp=:timestamp WHERE proposal_id = :proposal_id AND filetype = :filetype";
                  $args = [
                    ":filename" => $_FILES['files']['name'][$file_form_name],
                    ":filepath" => $file_path . $_FILES['files']['name'][$file_form_name],
                    ":filemime" => mime_content_type($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]),
                    ":filesize" => $_FILES['files']['size'][$file_form_name],
                    ":timestamp" => time(),
                    ":proposal_id" => $proposal_id,
                    ":filetype" => $file_type,
                  ];
                  \Drupal::database()->query($query, $args);
                  \Drupal::messenger()->addStatus($file_name . ' file updated successfully.');
                }
              } //move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
              else {
                \Drupal::messenger()->addError('Error uploading file : ' . $dest_path . $file_name);
              }
            }
            break;
          case 'UDC':
            if (!is_dir($root_path . $dest_path_udc)) {
              mkdir($root_path . $dest_path_udc);
            }
            if (file_exists($root_path . $dest_path_udc . $_FILES['files']['name'][$file_form_name])) {
              unlink($root_path . $dest_path_udc . $_FILES['files']['name'][$file_form_name]);
              move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);
              \Drupal::messenger()->addError(t("File !filename already exists directory hence overwirtten the exisitng file ", [
                '!filename' => $_FILES['files']['name'][$file_form_name]
                ]));
            } //file_exists($root_path . $dest_path_udc . $_FILES['files']['name'][$file_form_name])
            if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path_udc . $_FILES['files']['name'][$file_form_name])) {
              //////////////////////////////////
						/* for uploaded files making an entry in the database */
              $query_udc_f = "SELECT * FROM dwsim_flowsheet_proposal WHERE id = :proposal_id";
              $args_udc_f = [":proposal_id" => $proposal_id];
              $query_udc_f_result = \Drupal::database()->query($query_udc_f, $args_udc_f)->fetchObject();
              if ($query_udc_f_result) {
                unlink($root_path . $dest_path_udc . $query_ab_f_result->user_defined_compound_filepath);
                $user_defined_compound_filepath = "user_defined_compound/" . $_FILES['files']['name'][$file_form_name];
                $query_udc_f = "UPDATE dwsim_flowsheet_proposal SET user_defined_compound_filepath = :user_defined_compound_filepath WHERE id= :proposal_id";
                $args_udc_f = [
                  ":user_defined_compound_filepath" => $user_defined_compound_filepath,
                  ":proposal_id" => $proposal_id,
                ];
                \Drupal::database()->query($query_udc_f, $args_udc_f);
                \Drupal::messenger()->addStatus($file_name . ' uploaded successfully.');
              } //!$query_ab_f_result
              else {
                \Drupal::messenger()->addError('Invalid proposal');
              }
              //////////////////////////////////
            } //move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path_udc . $_FILES['files']['name'][$file_form_name])
            else {
              \Drupal::messenger()->addMessage($file_name . " unable to move.");
            }
            break;
        } //$file_type
      } //$file_name
    } //$_FILES['files']['name'] as $file_form_name => $file_name
	/* sending email */
    $email_to = $user->mail;
    $from = \Drupal::config('dwsim_flowsheet.settings')->get('dwsim_flowsheet_from_email');
    $bcc = \Drupal::config('dwsim_flowsheet.settings')->get('dwsim_flowsheet_emails');
    $cc = \Drupal::config('dwsim_flowsheet.settings')->get('dwsim_flowsheet_cc_emails');
    $params['abstract_uploaded']['proposal_id'] = $proposal_id;
    $params['abstract_uploaded']['submitted_abstract_id'] = $submitted_abstract_id;
    $params['abstract_uploaded']['user_id'] = $user->id();
    $params['abstract_uploaded']['headers'] = [
      'From' => $from,
      'MIME-Version' => '1.0',
      'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
      'Content-Transfer-Encoding' => '8Bit',
      'X-Mailer' => 'Drupal',
      'Cc' => $cc,
      'Bcc' => $bcc,
    ];
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $mail_result = \Drupal::service('plugin.manager.mail')->mail(
      'dwsim_flowsheet',
      'abstract_uploaded',
      $email_to,
      $langcode,
      $params,
      $from,
      TRUE
    );
    if (empty($mail_result['result'])) {
      \Drupal::messenger()->addError('Error sending email message.');
    }
    // drupal_goto('flowsheeting-project/abstract-code');
    $response = new RedirectResponse(Url::fromRoute('dwsim_flowsheet.upload_abstract_code_form')->toString());
    // Send the redirect response
    $response->send();
  }

}

function default_value_for_selections($operation, $proposal_id) {
  $query = Database::getConnection()->select('dwsim_flowsheet_submitted_abstracts', 'a');
  $query->fields('a');
  $query->condition('proposal_id', $proposal_id);
  $abstracts_q = $query->execute()->fetchObject();

  $selected_package_array = [];

  if ($abstracts_q) {
      if ($operation === "unit_operations_used_in_dwsim" && !empty($abstracts_q->unit_operations_used_in_dwsim)) {
          $selected_package_array = array_map('trim', explode(',', $abstracts_q->unit_operations_used_in_dwsim));
      } elseif ($operation === "thermodynamic_packages_used" && !empty($abstracts_q->thermodynamic_packages_used)) {
          $selected_package_array = array_map('trim', explode(',', $abstracts_q->thermodynamic_packages_used));
      } elseif ($operation === "logical_blocks_used" && !empty($abstracts_q->logical_blocks_used)) {
          $selected_package_array = array_map('trim', explode(',', $abstracts_q->logical_blocks_used));
      } elseif ($operation === "dwsim_database_compound_name") {
          $query = Database::getConnection()->select('dwsim_flowsheet_proposal', 'p');
          $query->fields('p');
          $query->condition('id', $proposal_id);
          $proposal_q = $query->execute()->fetchObject();

          if (!empty($proposal_q->dwsim_database_compound_name)) {
              $selected_package_array = array_map('trim', explode('| ', $proposal_q->dwsim_database_compound_name));
          }
      }
  }

  return $selected_package_array;
}

function default_value_for_uploaded_files($filetype, $proposal_id) {
  $selected_files_array = null;

  if (in_array($filetype, ['A', 'S'])) {
      $query = Database::getConnection()->select('dwsim_flowsheet_submitted_abstracts_file', 'f');
      $query->fields('f');
      $query->condition('proposal_id', $proposal_id);
      $query->condition('filetype', $filetype);
      $selected_files_array = $query->execute()->fetchObject();
  } elseif ($filetype === "UDC") {
      $query = Database::getConnection()->select('dwsim_flowsheet_proposal', 'p');
      $query->fields('p');
      $query->condition('id', $proposal_id);
      $selected_files_array = $query->execute()->fetchObject();
  }

  return $selected_files_array;
}

?>
