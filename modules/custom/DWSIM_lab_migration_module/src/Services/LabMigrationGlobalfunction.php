<?php
 
namespace Drupal\lab_migration\Services;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Database\Database;
use Drupal\Core\DrupalKernel;
use Drupal\user\Entity\User;

class LabMigrationGlobalfunction{

   public function _list_of_labs()
  {
    $lab_titles = array(
        '0' => 'Please select...'
    );
    //$lab_titles_q = $injected_database->query("SELECT * FROM {lab_migration_proposal} WHERE solution_display = 1 ORDER BY lab_title ASC");
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
  public function _lm_list_of_states()
  {
    $states = array(0 => '-Select-');
    $query = \Drupal::database()->select('list_states_of_india');
    $query->fields('list_states_of_india');
    //$query->orderBy('', '');
    $states_list = $query->execute();
    while ($states_list_data = $states_list->fetchObject())
      {
        $states[$states_list_data->state] = $states_list_data->state;
      }
    return $states;
  }
  public function _lab_migration_list_of_states()
{
    $states = array(''=> '- Select -');
    $states_list = \Drupal::database()->query("SELECT state FROM all_india_pincode WHERE country = 'India' ORDER BY state ASC");
    while ($states_list_data = $states_list->fetchObject())
    {
        $states[$states_list_data->state] = $states_list_data->state;
    }
    return $states;
}

public function _lm_list_of_cities()
  {
    $city = array(0 => '-Select-');
    $query = \Drupal::database()->select('list_cities_of_india');
    $query->fields('list_cities_of_india');
    $query->orderBy('city', 'ASC');
    $city_list = $query->execute();
    while ($city_list_data = $city_list->fetchObject())
      {
        $city[$city_list_data->city] = $city_list_data->city;
      }
    return $city;
  }

  public function _lab_migration_list_of_city_pincode($city=Null, $state=NULL, $district=NULL)
{
    $pincode = array();
    if($city){
        $pincode_list = \Drupal::database()->query("SELECT pincode FROM all_india_pincode WHERE city = :city AND state = :state AND district = :district", array(':city' => $city,':state'=> $state, ':district' => $district));
        while ($pincode_list_data = $pincode_list->fetchObject())
        {
            $pincode[$pincode_list_data->pincode] = $pincode_list_data->pincode;
        }
    }
    else{
        $pincode[000000] = '000000';
    }
    return $pincode;
}

  public function _lm_list_of_departments()
  {
    $department = array();
    $query = \Drupal::database()->select('list_of_departments');
    $query->fields('list_of_departments');
    $query->orderBy('id', 'DESC');
    $department_list = $query->execute();
    while ($department_list_data = $department_list->fetchObject())
      {
        $department[$department_list_data->department] = $department_list_data->department;
      }
    return $department;
  }

  public function _lm_list_of_software_version()
  {
    $software_version = array();
    $query = \Drupal::database()->select('dwsim_software_version');
    $query->fields('dwsim_software_version');
    //$query->orderBy('id', 'DESC');
    $software_version_list = $query->execute();
    while ($software_version_list_data = $software_version_list->fetchObject())
      {
        $software_version[$software_version_list_data->dwsim_version] = $software_version_list_data->dwsim_version;
      }
    return $software_version;
  }

  public function _lab_migration_list_of_district($state=Null)
{
    $district = array(''=> '- Select -');
    if($state){
        $district_list = \Drupal::database()->query("SELECT district FROM all_india_pincode WHERE state = :state ORDER BY district ASC", array(':state'=> $state));
        while ($district_list_data = $district_list->fetchObject())
        {
            $district[$district_list_data->district] = $district_list_data->district;
        }
    }
    return $district;
}
public function _lm_dir_name($lab, $name, $university)
  {
    $lab_title = \Drupal::service("lab_migration_global")->lm_ucname($lab);
    $proposar_name = \Drupal::service("lab_migration_global")->lm_ucname($lab);
    $university_name = \Drupal::service("lab_migration_global")->lm_ucname($university);
    $dir_name = $lab_title . " " . "by". " " . $proposar_name . ' ' . $university_name;
    $directory_name = str_replace("__", "_", str_replace(" ", "_", $dir_name));
    return $directory_name;
  }
public function lm_ucname($string)
  {
    $string = ucwords(strtolower($string));
    foreach (array(
        '-',
        '\''
    ) as $delimiter)
      {
        if (strpos($string, $delimiter) !== false)
          {
            $string = implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
          }
      }
    return $string;
    // Example of using lm_ucname in another method of this service.
  
    $name = $this->lm_ucname('example-string');
    // Other logic here...
    return $name;
  
  }
  public function lab_migration_path()
  {
    return $_SERVER['DOCUMENT_ROOT'] . base_path() . 'DWSIM_uploads/lab_migration_uploads/';
  }
  public function _bulk_list_of_labs()
  {
    $lab_titles = array(
        '0' => 'Please select...'
    );
    //$lab_titles_q = db_query("SELECT * FROM {lab_migration_proposal} WHERE solution_display = 1 ORDER BY lab_title ASC");
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('solution_display', 1);
    $query->orderBy('lab_title', 'ASC');
    $lab_titles_q = $query->execute();
    while ($lab_titles_data = $lab_titles_q->fetchObject())
      {
        $lab_titles[$lab_titles_data->id] = $lab_titles_data->lab_title . ' (Proposed by ' . $lab_titles_data->name . ')';
      }
    return $lab_titles;
  }
  
  public function _latex_copy_script_file()
  {
    // exec("cp ./" . drupal_get_path('module', 'lab_migration') . "/latex/* " . \Drupal::service("lab_migration_global")->lab_migration_path() . "latex");
    // exec("chmod u+x ./uploads/latex/*.sh");
    // Get the module path using the updated method for Drupal 10
$module_path = \Drupal::service('module_handler')->getModule('lab_migration')->getPath();

// Get the migration path from the lab_migration_global service
$lab_migration_path = \Drupal::service('lab_migration_global')->lab_migration_path();

// Execute the command to copy files
exec("cp {$module_path}/latex/* {$lab_migration_path}latex");

// Execute the command to change permissions
exec("chmod u+x ./uploads/latex/*.sh");
  }
  
  public function lab_migration_solution_proposal_pending()
  {
    /* get list of solution proposal where the solution_provider_uid is set to some userid except 0 and solution_status is also 1 */
    $pending_rows = array();
    //$pending_q = db_query("SELECT * FROM {lab_migration_proposal} WHERE solution_provider_uid != 0 AND solution_status = 1 ORDER BY id DESC");
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('solution_provider_uid', 0, '!=');
    $query->condition('solution_status', 1);
    $query->orderBy('id', 'DESC');
    $pending_q = $query->execute();
    while ($pending_data = $pending_q->fetchObject())
      {
        $pending_rows[$pending_data->id] = array(
            l($pending_data->name, 'user/' . $pending_data->uid),
            $pending_data->lab_title,
            l('Approve', 'lab-migration/manage-proposal/solution-proposal-approve/' . $pending_data->id)
        );
      }
    /* check if there are any pending proposals */
    if (!$pending_rows)
      {
        \Drupal::messenger()->addMessage(t('There are no pending solution proposals.'), 'status');
        return '';
      }
    $pending_header = array(
        'Proposer Name',
        'Title of the Lab',
        'Action'
    );
    $output = theme('table', array(
        'header' => $pending_header,
        'rows' => $pending_rows
    ));
    return $output;
  }
  public function lab_migration_list_experiments() {
    // $user = \Drupal::currentUser();
$user = $user->get('uid')->value;

    $proposal_data = \Drupal::service("lab_migration_global")->lab_migration_get_proposal();
    if (!$proposal_data) {
      RedirectResponse('');
      return;
    }

    $return_html = '<strong>Title of the Lab:</strong><br />' . $proposal_data->lab_title . '<br /><br />';
    $return_html .= '<strong>Proposer Name:</strong><br />' . $proposal_data->name_title . ' ' . $proposal_data->name . '<br /><br />';
    // $return_html .= Link::fromTextAndUrl('Upload Solution', 'lab-migration/code/upload') . '<br />';
    $return_html .= Link::fromTextAndUrl(
      'Upload Solution', 
      Url::fromUri('internal:/lab-migration/code/upload')
  )->toString() . '<br />';
    /* get experiment list */
    $experiment_rows = [];
    //$experiment_q = \Drupal::database()->query("SELECT * FROM {lab_migration_experiment} WHERE proposal_id = %d ORDER BY number ASC", $proposal_data->id);
    $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('proposal_id', $proposal_data->id);
    $query->orderBy('number', 'ASC');
    $experiment_q = $query->execute();

   

    while ($experiment_data = $experiment_q->fetchObject()) {


      $experiment_rows[] = [
        $experiment_data->number . ')&nbsp;&nbsp;&nbsp;&nbsp;' . $experiment_data->title,
        '',
        '',
        '',
      ];
      /* get solution list */
      //$solution_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution} WHERE experiment_id = %d ORDER BY id ASC", $experiment_data->id);
      $query = \Drupal::database()->select('lab_migration_solution');
      $query->fields('lab_migration_solution');
      $query->condition('experiment_id', $experiment_data->id);
      $query->orderBy('id', 'ASC');
      $solution_q = $query->execute();
      if ($solution_q) {
        while ($solution_data = $solution_q->fetchObject()) {
          $solution_status = '';
          switch ($solution_data->approval_status) {
            case 0:
              $solution_status = "Pending";
              break;
            case 1:
              $solution_status = "Approved";
              break;
            default:
              $solution_status = "Unknown";
              break;
          }
          if ($solution_data->approval_status == 0) {
            $experiment_rows[] = [
              "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . $solution_data->code_number . "   " . $solution_data->caption,
              '',
              $solution_status,
              Link::fromTextAndUrl('Delete', 'lab-migration/code/delete/' . $solution_data->id),
            ];
          }
          else {
            $experiment_rows[] = [
              "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . $solution_data->code_number . "   " . $solution_data->caption,
              '',
              $solution_status,
              '',
            ];
          }
          /* get solution files */
          //$solution_files_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution_files} WHERE solution_id = %d ORDER BY id ASC", $solution_data->id);
          $query = \Drupal::database()->select('lab_migration_solution_files');
          $query->fields('lab_migration_solution_files');
          $query->condition('solution_id', $solution_data->id);
          $query->orderBy('id', 'ASC');
          $solution_files_q = $query->execute();

          if ($solution_files_q) {
            while ($solution_files_data = $solution_files_q->fetchObject()) {
              $code_file_type = '';
              switch ($solution_files_data->filetype) {
                case 'S':
                  $code_file_type = 'Source';
                  break;
                case 'R':
                  $code_file_type = 'Result';
                  break;
                case 'X':
                  $code_file_type = 'Xcox';
                  break;
                case 'U':
                  $code_file_type = 'Unknown';
                  break;
                default:
                  $code_file_type = 'Unknown';
                  break;
              }
              $experiment_rows[] = [
                "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . Link::fromTextAndUrl($solution_files_data->filename, 'lab-migration/download/file/' . $solution_files_data->id),
                $code_file_type,
                '',
                '',
              ];
            }
          }
          /* get dependencies files */
          //$dependency_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution_dependency} WHERE solution_id = %d ORDER BY id ASC", $solution_data->id);
          $query = \Drupal::database()->select('lab_migration_solution_dependency');
          $query->fields('lab_migration_solution_dependency');
          $query->condition('solution_id', $solution_data->id);
          $query->orderBy('id', 'ASC');
          $dependency_q = $query->execute();
          while ($dependency_data = $dependency_q->fetchObject()) {
            //$dependency_files_q = \Drupal::database()->query("SELECT * FROM {lab_migration_dependency_files} WHERE id = %d", $dependency_data->dependency_id);
            $query = \Drupal::database()->select('lab_migration_dependency_files');
            $query->fields('lab_migration_dependency_files');
            $query->condition('id', $dependency_data->dependency_id);
            $dependency_files_q = $query->execute();
            $dependency_files_data = $dependency_files_q->fetchObject();
            $experiment_rows[] = [
              "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . Link::fromTextAndUrl($dependency_files_data->filename, 'lab-migration/download/dependency/' . $dependency_files_data->id),
              'Dependency',
              '',
              '',
            ];
          }
        }
      }
    }

    $experiment_header = [
      'No. Title of the Experiment',
      'Type',
      'Status',
      'Actions',
    ];
    // $return_html .= drupal_render()_table($experiment_header, $experiment_rows);

    // $return_html .= \Drupal::service("renderer")->render('table', [
    //   'header' => $experiment_header,
    //   'rows' => $experiment_rows,
    // ]);
    $return_html = '<strong>Title of the Lab:</strong><br /><br /><br />';
$return_html .= '<strong>Proposer Name:</strong><br /><br /><br />';
$return_html .= '<a href="/test_module_upgradtion/lab-migration/code/upload">Upload Solution</a><br />';
// Add your table or any other HTML content here

return new Response($return_html);
    $table = [
      '#type' => 'table',
      '#header' => $experiment_header,  // The headers for the table
      '#rows' => $experiment_rows,      // The rows for the table
      
    ];
  
    
    $return_html .= \Drupal::service('renderer')->render($table);
    return $return_html;
  }

  public  function verify_lab_migration_certificates($qr_code = 0) {
    
    $route_match = \Drupal::routeMatch();

$qr_code = (int) $route_match->getParameter('qr_code');
    $page_content = "";
    if ($qr_code) {
      $page_content = verify_qrcode_lm_fromdb($qr_code);
    } //$qr_code
    else {
      $verify_certificates_form = \Drupal::formBuilder()->getForm("verify_lab_migration_certificates_form");
      $page_content = \Drupal::service("renderer")->render($verify_certificates_form);
    }
    return $page_content;
  }
  public function _bulk_list_lab_actions()
  {
    $lab_actions = array(
        0 => 'Please select...'
    );
    $lab_actions[1] = 'Approve Entire Lab';
    $lab_actions[2] = 'Pending Review Entire Lab';
    $lab_actions[3] = 'Dis-Approve Entire Lab (This will delete all the solutions in the lab)';
    $lab_actions[4] = 'Delete Entire Lab Including Proposal';
    return $lab_actions;
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
  public function _bulk_list_solution_actions()
  {
    $lab_solution_actions = array(
        0 => 'Please select...'
    );
    $lab_solution_actions[1] = 'Approve Entire Solution';
    $lab_solution_actions[2] = 'Pending Review Entire Solution';
    $lab_solution_actions[3] = 'Dis-approve Solution (This will delete the solution)';
    return $lab_solution_actions;
  }
  public  function lab_migration_delete_lab_pdf() {
    
    $route_match = \Drupal::routeMatch();

$lab_id = (int) $route_match->getParameter('lab_id');
\Drupal::service("lab_migration_global")->lab_migration_del_lab_pdf($lab_id);
    \Drupal::messenger()->addMessage(t('Lab schedule for regeneration.'), 'status');
    RedirectResponse('lab_migration/code_approval/bulk');
    return;
  }

  public  function lab_migration_delete_lab($lab_id)
{
  $status = TRUE;
  $root_path = \Drupal::service("lab_migration_global")->lab_migration_path();
  $query = \Drupal::database()->select('lab_migration_proposal');
              $query->fields('lab_migration_proposal');
              $query->condition('id', $lab_id);
              $proposal_q = $query->execute();
  $proposal_data = $proposal_q->fetchObject();
  if (!$proposal_data)
  {
    \Drupal::messenger()->addError('Invalid Lab.');
    return FALSE;
  }
  /* delete experiments */
  $query = \Drupal::database()->select('lab_migration_experiment');
              $query->fields('lab_migration_experiment');
              $query->condition('proposal_id', $proposal_data->id);
              $experiment_q = $query->execute();
  while ($experiment_data = $experiment_q->fetchObject())
  {
    if (!\Drupal::service("lab_migration_global")->lab_migration_delete_experiment($experiment_data->id))
    {
      $status = FALSE;
    }
  }
  return $status;
}

public  function lab_migration_delete_experiment($experiment_id)
{
    $status = TRUE;
    $root_path = \Drupal::service("lab_migration_global")->lab_migration_path();
    $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('id', $experiment_id);
    $experiment_q = $query->execute();
    $experiment_data = $experiment_q->fetchObject();
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $experiment_data->proposal_id);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    if (!$experiment_data)
    {
        \Drupal::messenger()->addError('Invalid experiment.');
        return FALSE;
    }
  /* deleting solutions */
    $query = \Drupal::database()->select('lab_migration_solution');
    $query->fields('lab_migration_solution');
    $query->condition('experiment_id', $experiment_id);
    $solution_q = $query->execute();
    $delete_exp_folder = FALSE;
    while ($solution_data = $solution_q->fetchObject())
    {
        $delete_exp_folder = TRUE;
        if (!\Drupal::service("lab_migration_global")->lab_migration_delete_solution($solution_data->id))
          $status = FALSE;
    }
    if (!$delete_exp_folder)
    {
        return TRUE;
    }
    if ($status)
    {
        $dir_path = $root_path . $proposal_data->directory_name . '/EXP' . $experiment_data->number;
        if (is_dir($dir_path))
        {
          $res = rmdir($dir_path);
          if (!$res)
          {
            \Drupal::messenger()->addError(t('Error deleting experiment folder !folder', array('!folder' => $dir_path)));
            /* sending email to admins */
          //   $email_to = \Drupal::config('lab_migration.settings')->get('lab_migration_emails');
          //   $from = \Drupal::config('lab_migration.settings')->get('lab_migration_from_email');
          //   $bcc="";
          //   $cc=\Drupal::config('lab_migration.settings')->get('lab_migration_cc_emails');
          //   $param['standard']['subject'] = "[ERROR] Error deleting experiment folder";
          //   $param['standard']['body'] = "Error deleting folder " . $dir_path;
          //   $param['standard']['headers']=array('From'=>$from,'MIME-Version'=> '1.0',
          //           'Content-Type'=> 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
          //           'Content-Transfer-Encoding' => '8Bit',
          //           'X-Mailer'=> 'Drupal','Cc' => $cc, 'Bcc' => $bcc);
          //       if (!drupal_mail('lab_migration', 'standard', $email_to, language_default(), $param,$from, TRUE))
          //     \Drupal::messenger()->addError('Error sending email message.');
          //   return FALSE;
          } 
          else 
          {
            return TRUE;
          }
        } 
        else {
          \Drupal::messenger()->addError(t('Cannot delete experiment folder. !folder does not exists.', array('!folder' => $dir_path)));
          return FALSE;
        }
    }
  return FALSE;
}

  public  function lab_migration_get_proposal()
  {
    global $user;
    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    //var_dump($user->id()        );die;
    //$proposal_q = db_query("SELECT * FROM {lab_migration_proposal} WHERE solution_provider_uid = ".$user->uid." AND solution_status = 2 ORDER BY id DESC LIMIT 1");
    $query = Database::getConnection()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('solution_provider_uid',  $user->id());
    $query->condition('solution_status', 2);
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    //var_dump($proposal_data);die;
//     if (!$proposal_data)
//       {
       
//         // Create the link URL object for the "available" link.
// $link_url = Url::fromRoute('lab_migration.proposal_open');
// $link = Link::fromTextAndUrl('available', $link_url)->toString();


// // Now you can use $link in your output or messages
// \Drupal::messenger()->addMessage("Check out the proposal: " . $link);
// }
    
    switch ($proposal_data->approval_status)
    {
        case 0:
            \Drupal::messenger()->addMessage(t('Proposal is awaiting approval.'), 'status');
            return FALSE;
        case 1:
            return $proposal_data;
        case 2:
          \Drupal::messenger()->addMessage(t('Proposal has been dis-approved.'), 'error');
            return FALSE;
        case 3:
          \Drupal::messenger()->addMessage(t('Proposal has been marked as completed.'), 'status');
            return FALSE;
        default:
        \Drupal::messenger()->addMessage(t('Invalid proposal state. Please contact site administrator for further information.'), 'error');
            return FALSE;
    }
   // return $proposal_data;
  }
  public function lab_migration_upload_code_form($form,$form_state)
{
 
  global $user;

  $proposal_data = lab_migration_get_proposal();
  if (!$proposal_data) {
      drupal_goto('');
      return;
  }

  /* add javascript for dependency selection effects */
  $dep_selection_js = "(public function ($) {
  //alert('ok');
    $('#edit-existing-depfile-dep-lab-title').change(public function() {
      var dep_selected = '';   
 
      /* showing and hiding relevant files */
     $('.form-checkboxes .option').hide();
      $('.form-checkboxes .option').each(public function(index) {
        var activeClass = $('#edit-existing-depfile-dep-lab-title').val();
        consloe.log(activeClass);
        if ($(this).children().hasClass(activeClass)) {
          $(this).show();
        }
        if ($(this).children().attr('checked') == true) {
          dep_selected += $(this).children().next().text() + '<br />';
        }
      });
      /* showing list of already existing dependencies */
      $('#existing_depfile_selected').html(dep_selected);
    });

    $('.form-checkboxes .option').change(public function() {
      $('#edit-existing-depfile-dep-lab-title').trigger('change');
    });
    $('#edit-existing-depfile-dep-lab-title').trigger('change');
  }(jQuery));";
  drupal_add_js($dep_selection_js, 'inline', 'header');

  $form['#attributes'] = array('enctype' => "multipart/form-data");

  $form['lab_title'] = array(
    '#type' => 'item',
    '#markup' => $proposal_data->lab_title,
    '#title' => t('Title of the Lab'),
  );
  $form['name'] = array(
    '#type' => 'item',
    '#markup' => $proposal_data->name_title . ' ' . $proposal_data->name,
    '#title' => t('Proposer Name'),
  );

  /* get experiment list */
  $experiment_rows = array();
  //$experiment_q = db_query("SELECT * FROM {lab_migration_experiment} WHERE proposal_id = %d ORDER BY id ASC", $proposal_data->id);
  $query = \Drupal::database()->select('lab_migration_experiment');
                $query->fields('lab_migration_experiment');
                $query->condition('proposal_id', $proposal_data->id);
                $query->orderBy('id', 'ASC');
                $experiment_q = $query->execute();
  while ($experiment_data = $experiment_q->fetchObject())
  {
    $experiment_rows[$experiment_data->id] = $experiment_data->number . '. ' . $experiment_data->title;
  }
  $form['experiment'] = array(
    '#type' => 'select',
    '#title' => t('Title of the Experiment'),
    '#options' => $experiment_rows,
    '#multiple' => FALSE,
    '#size' => 1,
    '#required' => TRUE,
  );

  $form['code_number'] = array(
    '#type' => 'textfield',
    '#title' => t('Code No'),
    '#size' => 5,
    '#maxlength' => 10,
    '#description' => t(""),
    '#required' => TRUE,
  );
  $form['code_caption'] = array(
    '#type' => 'textfield',
    '#title' => t('Caption'),
    '#size' => 40,
    '#maxlength' => 255,
    '#description' => t(''),
    '#required' => TRUE,
  );
  $form['os_used'] = array(
    '#type' => 'select',
    '#title' => t('Operating System used'),
    '#options' => array(
      'Linux' => 'Linux',
      'Windows' => 'Windows',
      'Mac' => 'Mac'
    ),
    '#required' => TRUE,
  );
  $form['version'] = array(
    '#type' => 'select',
    '#title' => t('DWSIM version used'),
    '#options' => _lm_list_of_software_version(),
    '#required' => TRUE,
  );
  $form['toolbox_used'] = array(
    '#type' => 'hidden',
    '#title' => t('Toolbox used (If any)'),
'#default_value'=>'none',
  );
  $form['code_warning'] = array(
    '#type' => 'item',
    '#title' => t('Upload all the r project files in .zip format'),
    '#prefix' => '<div style="color:red">',
    '#suffix' => '</div>',
  );
  $form['sourcefile'] = array(
    '#type' => 'fieldset',
    '#title' => t('Main or Source Files'),
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
  );
  $form['sourcefile']['sourcefile1'] = array(
      '#type' => 'file',
      '#title' => t('Upload main or source file'),
      '#size' => 48,
      '#description' => t('Only alphabets and numbers are allowed as a valid filename.') . '<br />' .
      t('Allowed file extensions: ') . variable_get('lab_migration_source_extensions', ''),
  );

 /* $form['dep_files'] = array(
    '#type' => 'item',
    '#title' => t('Dependency Files'),
  );*/
 }

 public  function lab_migration_delete_solution($solution_id)
{
    global $user;
    $root_path = \Drupal::service("lab_migration_global")->lab_migration_path();
    $status = TRUE;
    $query = \Drupal::database()->select('lab_migration_solution');
    $query->fields('lab_migration_solution');
    $query->condition('id', $solution_id);
    $solution_q = $query->execute();
    $solution_data = $solution_q->fetchObject();
    if (!$solution_data)
      {
        \Drupal::messenger()->addMessage(t('Invalid solution.'), 'error');
        return FALSE;
      }
    $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('id', $solution_data->experiment_id);
    $experiment_q = $query->execute();
    $experiment_data = $experiment_q->fetchObject();
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $experiment_data->proposal_id);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    if (!$experiment_data)
    {
      \Drupal::messenegr()->addMessage(t('Invalid experiment.'), 'error');
        return FALSE;
    }
  /* deleting solution files */
    $query = \Drupal::database()->select('lab_migration_solution_files');
    $query->fields('lab_migration_solution_files');
    $query->condition('solution_id', $solution_id);
    $solution_files_q = $query->execute();
    while ($solution_files_data = $solution_files_q->fetchObject())
    {
        $ex_path = $proposal_data->directory_name . '/' . $solution_files_data->filepath;
        $dir_path = $root_path . $ex_path;
        if (!file_exists($dir_path))
        {
          $status = FALSE;
          \Drupal::messenger()->addMessage(t('Error deleting !file. File does not exists.', array('!file' => $dir_path)), 'error');
          continue;
        }
    /* removing solution file */
        if (!unlink($dir_path))
        {
          $status = FALSE;
          \Drupal::messenger()->addMessage(t('Error deleting !file', array('!file' => $dir_path)), 'error');

          /* sending email to admins */
            $email_to = variable_get('lab_migration_emails', '');
            $from = variable_get('lab_migration_from_email', '');
            $bcc="";
            $cc=variable_get('lab_migration_cc_emails', '');
            $param['standard']['subject'] = "[ERROR] Error deleting example file";
            $param['standard']['body'] = "Error deleting solution files by " . $user->uid . " at " . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . " :
                solution id : " . $solution_id . "
                file id : " .  $solution_files_data->id . "
                file path : " . $solution_files_data->filepath."
            PDF path : " . $PdfStatus;
            $param['standard']['headers']=array('From'=>$from,'MIME-Version'=> '1.0',
                        'Content-Type'=> 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
                        'Content-Transfer-Encoding' => '8Bit',
                        'X-Mailer'=> 'Drupal','Cc' => $cc, 'Bcc' => $bcc);
    
            if (!drupal_mail('lab_migration', 'standard', $email_to, language_default(), $param, $from, TRUE))
            \Drupal::messenger()->addMessage('Error sending email message.', 'error');
        } 
        else {
          /* deleting example files database entries */     
          \Drupal::database()->delete('lab_migration_solution_files')->condition('id', $solution_files_data->id)->execute();
        }
    }

    if (!$status)
    return FALSE;
    $query = \Drupal::database()->select('lab_migration_solution');
    $query->fields('lab_migration_solution');
    $query->condition('id', $solution_id);
    $solution_q = $query->execute();
    $solution_data = $solution_q->fetchObject();
    if (!$solution_data)
      {
        \Drupal::messenger()->addMessage(t('Invalid solution.'), 'error');
        return FALSE;
      }
    $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('id', $solution_data->experiment_id);
    $experiment_q = $query->execute();
    $experiment_data = $experiment_q->fetchObject();
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $experiment_data->proposal_id);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    $dir_path = $root_path . $proposal_data->directory_name . '/EXP' . $experiment_data->number . '/CODE' . $solution_data->code_number;
    if (is_dir($dir_path))
    {
        // if (!rmdir($dir_path))
        // {
        //     \Drupal::messenger()->addMessage(t('Error deleting folder !folder', array('!folder' => $dir_path)), 'error');

          /* sending email to admins */
          //   $email_to = variable_get('lab_migration_emails', '');
          //   $from = variable_get('lab_migration_from_email', '');
          //   $bcc="";
          //   $cc=variable_get('lab_migration_cc_emails', '');


          //   $param['standard']['subject'] = "[ERROR] Error deleting folder";
          //   $param['standard']['body'] = "Error deleting folder " . $dir_path . " by " . $user->uid . " at " . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
          //   $param['standard']['headers']=array('From'=>$from,'MIME-Version'=> '1.0',
          //               'Content-Type'=> 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
          //               'Content-Transfer-Encoding' => '8Bit',
          //               'X-Mailer'=> 'Drupal','Cc' => $cc, 'Bcc' => $bcc);
        
          //   if (!drupal_mail('lab_migration', 'standard', $email_to, language_default(), $param, $from, TRUE))
          //       \Drupal::messenger()->addMessage('Error sending email message.', 'error');
          // return FALSE;
        // }
    } 
    else 
    {
        \Drupal::messenger()->addMessage(t('Cannot delete solution folder. !folder does not exists.', array('!folder' => $dir_path)), 'error');
        return FALSE;
    }

      /* deleting solution dependency and solution database entries */  
      \Drupal::database()->delete('lab_migration_solution_dependency')->condition('solution_id', $solution_id)->execute();
      \Drupal::database()->delete('lab_migration_solution')->condition('id', $solution_id)->execute();
    return $status;
}
public function LM_RenameDir($proposal_id, $dir_name)
  {
    $query = \Drupal::database()->query("SELECT directory_name FROM lab_migration_proposal WHERE id = :proposal_id", array(
        ':proposal_id' => $proposal_id
    ));
    $result = $query->fetchObject();
    $new_directory_name = rename(\Drupal::service("lab_migration_global")->lab_migration_path() . $result->directory_name, \Drupal::service("lab_migration_global")->lab_migration_path() . $dir_name) or \Drupal::messenger()->addMessage("Unable to rename folder");
    
    return $new_directory_name;
    
  }
public function lab_migration_with_morefeature($key, &$message, $params)
  {
    if (isset($params['subject']))
      {
        $message['subject'] = $params['subject'];
      }
    if (isset($params['body']))
      {
        $message['body'][] = $params['body'];
      }
    if (isset($params['headers']) && is_array($params['headers']))
      {
        $message['headers'] += $params['headers'];
      }
  }
  
  public function lab_migration_upload_code_delete()
{
  global $user;

  $root_path = lab_migration_path();
  // $solution_id = (int)arg(3);
  $route_match = \Drupal::routeMatch();

  $solution_id = (int) $route_match->getParameter('solution_id');
  

  /* check solution */
 // $solution_q = db_query("SELECT * FROM {lab_migration_solution} WHERE id = %d LIMIT 1", $solution_id);
  $query = \Drupal::database()->select('lab_migration_solution');
              $query->fields('lab_migration_solution');
              $query->condition('id', $solution_id);
              $query->range(0, 1);
              $solution_q = $query->execute();
  $solution_data = $solution_q->fetchObject();
  if (!$solution_data)
  {
    \Drupal::messenger()->addMessage('Invalid solution.', 'error');
    // drupal_goto('lab-migration/code');
    return;
  }
  if ($solution_data->approval_status != 0)
  {
    \Drupal::messenger()->addMessage('You cannnot delete a solution after it has been approved. Please contact site administrator if you want to delete this solution.', 'error');
    // drupal_goto('lab-migration/code');
    return;
  }

  //$experiment_q = db_query("SELECT * FROM {lab_migration_experiment} WHERE id = %d LIMIT 1", $solution_data->experiment_id);
  $query = \Drupal::database()->select('lab_migration_experiment');
            $query->fields('lab_migration_experiment');
            $query->condition('id', $solution_data->experiment_id);
            $query->range(0, 1);
            $experiment_q = $query->execute();

  $experiment_data = $experiment_q->fetchObject();
  if (!$experiment_data)
  {
    \Drupal::messenger()->addMessage('You do not have permission to delete this solution.', 'error');
    // drupal_goto('lab-migration/code');
    return;
  }

  //$proposal_q = db_query("SELECT * FROM {lab_migration_proposal} WHERE id = %d AND solution_provider_uid = %d LIMIT 1", $experiment_data->proposal_id, $user->uid);
  $query = \Drupal::database()->select('lab_migration_proposal');
              $query->fields('lab_migration_proposal');
              $query->condition('id', $experiment_data->proposal_id);
              $query->condition('solution_provider_uid', $user->uid);
              $query->range(0, 1);
              $proposal_q = $query->execute();
  $proposal_data = $proposal_q->fetchObject();
  if (!$proposal_data)
  {
    \Drupal::messenger()->addMessage('You do not have permission to delete this solution.', 'error');
    // drupal_goto('lab-migration/code');
    return;
  }

  /* deleting solution files */
  if (lab_migration_delete_solution($solution_data->id))
  {
    \Drupal::messenger()->addMessage('Solution deleted.', 'status');

    /* sending email */
    $email_to = $user->mail;

    $from = variable_get('lab_migration_from_email', '');
    $bcc= variable_get('lab_migration_emails', '');
    $cc=variable_get('lab_migration_cc_emails', '');  
    $param['solution_deleted_user']['solution_id'] = $proposal_data->id;
    $param['solution_deleted_user']['lab_title'] = $proposal_data->lab_title;
    $param['solution_deleted_user']['experiment_title'] = $experiment_data->title;
    $param['solution_deleted_user']['solution_number'] = $solution_data->code_number;
    $param['solution_deleted_user']['solution_caption'] = $solution_data->caption;
    $param['solution_deleted_user']['user_id'] = $user->uid;
    $param['solution_deleted_user']['headers']=array('From'=>$from,'MIME-Version'=> '1.0',
    			'Content-Type'=> 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
    			'Content-Transfer-Encoding' => '8Bit',
    			'X-Mailer'=> 'Drupal','Cc' => $cc, 'Bcc' => $bcc);

    if (!drupal_mail('lab_migration', 'solution_deleted_user', $email_to, language_default(), $param , $from , TRUE))
      \Drupal::messenger()->addMessage('Error sending email message.', 'error');
  } else {
    \Drupal::messenger()->addMessage('Error deleting example.', 'status');
  }

  // drupal_goto('lab-migration/code');
  return;
}

public function CreateReadmeFileLabMigration($proposal_id)
  {
    $result =\Drupal::database()->select("
                        SELECT * from lab_migration_proposal WHERE id = :proposal_id", array(
        ":proposal_id" => $proposal_id
    ));
    $proposal_data = $result->fetchObject();
    $root_path = \Drupal::service("lab_migration_global")->lab_migration_path();
    $readme_file = fopen($root_path . $proposal_data->directory_name . "/README.txt", "w") or die("Unable to open file!");
    $txt = "";
    $txt .= "About the lab";
    $txt .= "\n" . "\n";
    $txt .= "Title Of The Lab: " . $proposal_data->lab_title . "\n";
    $txt .= "Proposar Name: " . $proposal_data->name_title . " " . $proposal_data->name . "\n";
    $txt .= "Department: " . $proposal_data->department . "\n";
    $txt .= "University: " . $proposal_data->university . "\n";
    $txt .= "Category: " . $proposal_data->department . "\n\n";
    $txt .= "\n" . "\n";
    $txt .= "Solution provider";
    $txt .= "\n" . "\n";
    $txt .= "Solution Provider Name: " . $proposal_data->solution_provider_name_title . " " . $proposal_data->solution_provider_name . "\n";
    $txt .= "Solution Provider University: " . $proposal_data->solution_provider_university . "\n";
    $txt .= "\n" . "\n";
    $txt .= "Lab Migration Project By FOSSEE, IIT Bombay" . "\n";
    fwrite($readme_file, $txt);
    fclose($readme_file);
    return $txt;
  }
  
  public function _latex_generate_files($lab_id, $full_lab = FALSE)
  {
    $root_path = \Drupal::service("lab_migration_global")->lab_migration_path();
    $dir_path = $root_path . "latex/";
    $lab_filedata = "";
    $solution_provider_filedata = "";
    $latex_filedata = "";
    $latex_dep_filedata = "";
    $depedency_list = array();
    $eol = "\n";
    $sep = "#";
    //$proposal_q = db_query("SELECT * FROM {lab_migration_proposal} WHERE id = %d", $lab_id);
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $lab_id);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    if (!$proposal_data)
      {
        \Drupal::messenger()->addMessage('Invalid lab specified.', 'error');
        // drupal_goto('');
      }
    if ($proposal_data->approval_status == 0)
      {
        \Drupal::messenger()->addMessage('Lab proposal is still in pending review.', 'error');
        // drupal_goto('');
      }
    $category_data = '';
    switch ($proposal_data->category)
    {
        case 0:
            $category_data = 'Not Selected';
            break;
        case 1:
            $category_data = 'Fluid Mechanics';
            break;
        case 2:
            $category_data = 'Control Theory & Control Systems';
            break;
        case 3:
            $category_data = 'Chemical Engineering';
            break;
        case 4:
            $category_data = 'Thermodynamics';
            break;
        case 5:
            $category_data = 'Mechanical Engineering';
            break;
        case 6:
            $category_data = 'Signal Processing';
            break;
        case 7:
            $category_data = 'Digital Communications';
            break;
        case 8:
            $category_data = 'Electrical Technology';
            break;
        case 9:
            $category_data = 'Mathematics & Pure Science';
            break;
        case 10:
            $category_data = 'Analog Electronics';
            break;
        case 11:
            $category_data = 'Digital Electronics';
            break;
        case 12:
            $category_data = 'Computer Programming';
            break;
        case 13:
            $category_data = 'Others';
            break;
        default:
            $category_data = 'Unknown';
            break;
    }
    $lab_filedata = $proposal_data->lab_title . $sep . $proposal_data->name_title . $sep . $proposal_data->name . $sep . $proposal_data->department . $sep . $proposal_data->university . $sep . $category_data . $eol;
    /* regenerate lab if full lab selected */
    if ($full_lab)
      {
        lab_migration_del_lab_pdf($proposal_data->id);
      }
    /* check if lab already generated */
    if (file_exists($dir_path . "lab_" . $proposal_data->id . ".pdf"))
      {
        /* download zip file */
        header('Content-Type: application/pdf');
        header('Content-disposition: attachment; filename="' . $proposal_data->lab_title . '.pdf"');
        header('Content-Length: ' . filesize($dir_path . "lab_" . $proposal_data->id . ".pdf"));
        readfile($dir_path . "lab_" . $proposal_data->id . ".pdf");
        return;
      }
    $solution_provider_user = User::load($proposal_data->solution_provider_uid);
    if (!$solution_provider_user)
      {
        \Drupal::messenger()->addMessage('Could not fetch solution provider information for the lab specified.', 'error');
      }
    $solution_provider_filedata .= $proposal_data->solution_provider_name_title . $sep . $proposal_data->solution_provider_name . $sep . $proposal_data->solution_provider_department . $sep . $proposal_data->solution_provider_university . $eol;
    //$experiment_q = db_query("SELECT * FROM {lab_migration_experiment} WHERE proposal_id = %d ORDER BY number DESC", $proposal_data->id);
    $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('proposal_id', $proposal_data->id);
    $query->orderBy('number', 'DESC');
    $experiment_q = $query->execute();
    while ($experiment_data = $experiment_q->fetchObject())
      {
        if ($full_lab)
          {
            //$solution_q = db_query("SELECT * FROM {lab_migration_solution} WHERE experiment_id = %d ORDER BY code_number DESC", $experiment_data->id);
            $query = \Drupal::database()->select('lab_migration_solution');
            $query->fields('lab_migration_solution');
            $query->condition('experiment_id', $experiment_data->id);
            $query->orderBy('code_number', 'DESC');
            $solution_q = $query->execute();
          }
        else
          {
            //$solution_q = db_query("SELECT * FROM {lab_migration_solution} WHERE experiment_id = %d AND approval_status = 1 ORDER BY code_number DESC", $experiment_data->id);
            $query = \Drupal::database()->select('lab_migration_solution');
            $query->fields('lab_migration_solution');
            $query->condition('experiment_id', $experiment_data->id);
            $query->condition('approval_status', 1);
            $query->orderBy('code_number', 'DESC');
            $solution_q = $query->execute();
          }
        while ($solution_data = $solution_q->fetchObject())
          {
            //$solution_files_q = db_query("SELECT * FROM {lab_migration_solution_files} WHERE solution_id = %d", $solution_data->id);
            $query = \Drupal::database()->select('lab_migration_solution_files');
            $query->fields('lab_migration_solution_files');
            $query->condition('solution_id', $solution_data->id);
            $solution_files_q = $query->execute();
            while ($solution_files_data = $solution_files_q->fetchObject())
              {
                $latex_filedata .= $experiment_data->number . $sep;
                $latex_filedata .= $experiment_data->title . $sep;
                $latex_filedata .= $solution_data->code_number . $sep;
                $latex_filedata .= $solution_data->caption . $sep;
                $latex_filedata .= $solution_files_data->filename . $sep;
                $latex_filedata .= $solution_files_data->filepath . $sep;
                $latex_filedata .= $solution_files_data->filetype . $sep;
                $latex_filedata .= $sep;
                $latex_filedata .= $solution_files_data->id;
                $latex_filedata .= $eol;
              }
            //$dependency_files_q = db_query("SELECT * FROM {lab_migration_solution_dependency} WHERE solution_id = %d", $solution_data->id);
            $query = \Drupal::database()->select('lab_migration_solution_dependency');
            $query->fields('lab_migration_solution_dependency');
            $query->condition('solution_id', $solution_data->id);
            $dependency_files_q = $query->execute();
            while ($dependency_files_data = $dependency_files_q->fetchObject())
              {
                //$dependency_q = db_query("SELECT * FROM {lab_migration_dependency_files} WHERE id = %d LIMIT 1", $dependency_files_data->dependency_id);
                $query = \Drupal::database()->select('lab_migration_dependency_files');
                $query->fields('lab_migration_dependency_files');
                $query->condition('id', $dependency_files_data->dependency_id);
                $query->range(0, 1);
                $dependency_q = $query->execute();
                if ($dependency_data = $dependency_q->fetchObject())
                  {
                    if (substr($dependency_data->filename, -3) != "wav")
                      {
                        $latex_filedata .= $experiment_data->number . $sep;
                        $latex_filedata .= $experiment_data->title . $sep;
                        $latex_filedata .= $solution_data->code_number . $sep;
                        $latex_filedata .= $solution_data->caption . $sep;
                        $latex_filedata .= $dependency_data->filename . $sep;
                        $latex_filedata .= $dependency_data->filepath . $sep;
                        $latex_filedata .= 'D' . $sep;
                        $latex_filedata .= $dependency_data->caption . $sep;
                        $latex_filedata .= $dependency_data->id;
                        $latex_filedata .= $eol;
                        $depedency_list[$dependency_data->id] = "D";
                      }
                  }
              }
          }
      }
    foreach ($depedency_list as $row => $data)
      {
        //$dependency_q = db_query("SELECT * FROM {lab_migration_dependency_files} WHERE id = %d LIMIT 1", $row);
        $query = \Drupal::database()->select('lab_migration_dependency_files');
        $query->fields('lab_migration_dependency_files');
        $query->condition('id', $row);
        $query->range(0, 1);
        $dependency_q = $query->execute();
        if ($dependency_data = $dependency_q->fetchObject())
          {
            $latex_dep_filedata .= $dependency_data->filename . $sep;
            $latex_dep_filedata .= $dependency_data->filepath . $sep;
            $latex_dep_filedata .= $dependency_data->caption . $sep;
            $latex_dep_filedata .= $dependency_data->id;
            $latex_dep_filedata .= $eol;
          }
      }
}
}
 