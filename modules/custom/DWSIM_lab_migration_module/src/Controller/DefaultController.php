<?php /**
 * @file
 * Contains \Drupal\lab_migration\Controller\DefaultController.
 */

namespace Drupal\lab_migration\Controller;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Service;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Render\Markup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use ZipArchive;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;


/**
 * Default controller for the lab_migration module.
 */
class DefaultController extends ControllerBase {
   
 
  

  public function lab_migration_proposal_pending() {
    /* get pending proposals to be approved */
    $pending_rows = [];
    //$pending_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} WHERE approval_status = 0 ORDER BY id DESC");
    $query =\Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('approval_status', 0);
    $query->orderBy('id', 'DESC');
    $pending_q = $query->execute();
    while ($pending_data = $pending_q->fetchObject()) {
      $approval_url = Link::fromTextAndUrl('Approve', Url::fromRoute('lab_migration.proposal_approval_form',['id'=>$pending_data->id]))->toString();
      $edit_url =  Link::fromTextAndUrl('Edit', Url::fromRoute('lab_migration.proposal_edit_form',['id'=>$pending_data->id]))->toString();
      $mainLink = t('@linkApprove | @linkReject', array('@linkApprove' => $approval_url, '@linkReject' => $edit_url));
      $pending_rows[$pending_data->id] = [
        date('d-m-Y', $pending_data->creation_date),
        
       // Create the link with the user's name as the link text.
       Link::fromTextAndUrl($pending_data->name, Url::fromRoute('entity.user.canonical', ['user' => $pending_data->uid])),
      

        // Link::fromTextAndUrl($pending_data->name, 'user/' . $pending_data->uid),
        $pending_data->lab_title,
        $pending_data->department,
        $mainLink 
      
    
        
        // Link::fromTextAndUrl('Approve', Url::fromRoute('lab_migration.manage_proposal_approve', ['id' => $pending_data->id]))
        // ->toString() . ' | ' . 
        // Link::fromTextAndUrl('Edit', Url::fromRoute('lab_migration.proposal_edit_form', ['id' => $pending_data->id]))->toString()
        // Link::fromTextAndUrl('Approve', 'lab_migration_manage_proposal_approve' . $pending_data->id) . ' | ' . Link::fromTextAndUrl('Edit', 'lab-migration/manage-proposal/edit/' . $pending_data->id),
      ];
    }
    /* check if there are any pending proposals */
    // if (!$pending_rows) {
    //   \Drupal::messenger()->addMessage($this->t('There are no pending proposals.'), 'status');
    //   return '';
    // }
    $pending_header = [
      'Date of Submission',
      'Name',
      'Title of the Lab',
      'Department',
      'Action'
    ];
    //$output = drupal_render()_table($pending_header, $pending_rows);
    $output =  [
      '#type' => 'table',
      '#header' => $pending_header,
      '#rows' => $pending_rows,
      //'#empty' => 'no rows found',
    ];
    //var_dump($output);die;
    return $output;
  }
  
      



  public function lab_migration_solution_proposal_pending() {
    
    $pending_rows = [];
    //$pending_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} WHERE approval_status = 0 ORDER BY id DESC");
    $query =\Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    // $query->condition('approval_status', 0);
    $query->condition('solution_provider_uid', 0, '!=');
    $query->condition('solution_status', 1);
    
    $query->orderBy('id', 'DESC');
    $pending_q = $query->execute();
    while ($pending_data = $pending_q->fetchObject()) {
      $approval_url = Link::fromTextAndUrl('Approve', Url::fromRoute('lab_migration.solution_proposal_approval_form',['id'=>$pending_data->id]))->toString();
      $edit_url =  Link::fromTextAndUrl('Edit', Url::fromRoute('lab_migration.proposal_edit_form',['id'=>$pending_data->id]))->toString();
      $mainLink = t('@linkApprove | @linkReject', array('@linkApprove' => $approval_url, '@linkReject' => $edit_url));
      $pending_rows[$pending_data->id] = [
        date('d-m-Y', $pending_data->creation_date),
        
       // Create the link with the user's name as the link text.
       Link::fromTextAndUrl($pending_data->name, Url::fromRoute('entity.user.canonical', ['user' => $pending_data->uid])),


        // Link::fromTextAndUrl($pending_data->name, 'user/' . $pending_data->uid),
        $pending_data->lab_title,
        $pending_data->department,
        $mainLink 
      
    
        
        // Link::fromTextAndUrl('Approve', Url::fromRoute('lab_migration.manage_proposal_approve', ['id' => $pending_data->id]))
        // ->toString() . ' | ' . 
        // Link::fromTextAndUrl('Edit', Url::fromRoute('lab_migration.proposal_edit_form', ['id' => $pending_data->id]))->toString()
        // Link::fromTextAndUrl('Approve', 'lab_migration_manage_proposal_approve' . $pending_data->id) . ' | ' . Link::fromTextAndUrl('Edit', 'lab-migration/manage-proposal/edit/' . $pending_data->id),
      ];
    }
    /* check if there are any pending proposals */
    // if (!$pending_rows) {
    //   \Drupal::messenger()->addMessage($this->t('There are no pending proposals.'), 'status');
    //   return '';
    // }
    $pending_header = [
      'Date of Submission',
      'Name',
      'Title of the Lab',
      'Department',
      'Action',
    ];
    //$output = drupal_render()_table($pending_header, $pending_rows);
    $output =  [
      '#type' => 'table',
      '#header' => $pending_header,
      '#rows' => $pending_rows,
    ];
   
    return $output;
  }

  public function lab_migration_proposal_pending_solution() {
    /* get pending proposals to be approved */
    $pending_rows = [];
    //$pending_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} WHERE approval_status = 1 ORDER BY id DESC");
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('approval_status', 1);
    $query->orderBy('id', 'DESC');
    $pending_q = $query->execute();
    while ($pending_data = $pending_q->fetchObject()) {
      $pending_rows[$pending_data->id] = [
        date('d-m-Y', $pending_data->creation_date),
        date('d-m-Y', $pending_data->approval_date),
        // Link::fromTextAndUrl($pending_data->name, 'user/' . $pending_data->uid),
        $link = Link::fromTextAndUrl($pending_data->name, Url::fromRoute('entity.user.canonical', ['user' => $pending_data->uid])),
        $pending_data->lab_title,
        $pending_data->department,
        $link = Link::fromTextAndUrl('Status', Url::fromRoute('lab_migration.proposal_status_form', ['id' => $pending_data->id])),
        // Link::fromTextAndUrl('Status', 'lab-migration/manage-proposal/status/' . $pending_data->id),
      ];
    }
    /* check if there are any pending proposals */
    // if (!$pending_rows) {
    //   \Drupal::messenger()->addMessage(t('There are no proposals pending for solutions.'), 'status');
    //   return new Response('');
    // }
    $pending_header = [
      'Date of Submission',
      'Date of Approval',
      'Name',
      'Title of the Lab',
      'Department',
      'Action',
    ];
    $output =  [
      '#type' => 'table',
      '#header' => $pending_header,
      '#rows' => $pending_rows,
      '#empty' => 'No rows found'
    ];
    return $output;
  }

  public function lab_migration_proposal(){
    /* get pending proposals to be approved */
    $proposal_rows = '[]';
    //$proposal_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} ORDER BY id DESC");
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->orderBy('id', 'DESC');
    $proposal_q = $query->execute();
    while ($proposal_data = $proposal_q->fetchObject()) {
      $approval_status = '';
      switch ($proposal_data->approval_status) {
        case 0:
          $approval_status = 'Pending';
          break;
        case 1:
          $approval_status = "<span style='color:red;'>Approved</span>";
          break;
        case 2:
          $approval_status = "<span style='color:black;'>Dis-approved</span>";
          break;
        case 3:
          $approval_status = "<span style='color:green;'>Solved</span>";
          break;
        default:
          $approval_status = 'Unknown';
          break;
        
      
    
      }
      $proposal_rows[] = [
        date('d-m-Y', $proposal_data->creation_date),
        Link::fromTextAndUrl($proposal_data->name, 'user/' . $proposal_data->uid),
        $proposal_data->lab_title,
        $proposal_data->department,
        $approval_status,
        Link::fromTextAndUrl('Status', 'lab-migration/manage-proposal/status/' . $proposal_data->id) . ' | ' . Link::fromTextAndUrl('Edit', 'lab-migration/manage-proposal/edit/' . $proposal_data->id),
      ];
    }
    /* check if there are any pending proposals */
    // if (!$proposal_rows) {
    //   \Drupal::messenger()->addMessage(t('There are no proposals.'), 'status');
    //   return '';
    // }
    $proposal_header = [
      'Date of Proposal Submission',
      'Name',
      'Title of the Lab',
      'Department',
      'Status',
      'Action',
    ];
    $output = \Drupal::service("renderer")->render('table', [
      'header' => $proposal_header,
      'rows' => $proposal_rows,
    ]);
    return $output;
  }
  public function lab_migration_proposal_open() {
    $user = \Drupal::currentUser();
  
    // Query to get the proposal count
    $query = \Drupal::database()->select('lab_migration_proposal', 'lmp');
    $query->fields('lmp');
    $query->condition('approval_status', 1);
    $query->condition('solution_provider_uid', 0);
    $proposal_q = $query->execute();
    $proposal_data_array = $proposal_q->fetchAll();
  
    $proposal_rows = [];
  
    // Check if there are any proposals
    if (count($proposal_data_array) > 0) {
        foreach ($proposal_data_array as $proposal_data) {
            // $proposal_link = Link::fromTextAndUrl(
            //     $proposal_data->lab_title, 
            //     Url::fromRoute('/lab-migration/open-proposal', ['id' => $proposal_data->id])
            // );
            $apply_link = Link::fromTextAndUrl(
                'Apply', 
                Url::fromRoute('lab_migration.solution_proposal_form', ['id' => $proposal_data->id])
            );
            $proposal_rows[] = [$proposal_data->lab_title, $apply_link];
        }
      
        // Define table headers
        $proposal_header = [
            'Title of the Lab',
            'Actions',
        ];
      
        // Render table if proposals are available
        $return_html = [
            '#type' => 'table',
            '#header' => $proposal_header,
            '#rows' => $proposal_rows,
            '#empty' => t('No proposals are available'), // Optional message if the table is empty
        ];
    } else {
        // Render a message if no proposals are available
        $return_html = [
            '#markup' => t('No proposals are available'),
        ];
    }
  
    return $return_html;
}

  public function lab_migration_code_approval() {
    /* get a list of unapproved solutions */
    //$pending_solution_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution} WHERE approval_status = 0");
    $query = \Drupal::database()->select('lab_migration_solution');
    $query->fields('lab_migration_solution');
    $query->condition('approval_status', 0);
    $pending_solution_q = $query->execute();
    // if (!$pending_solution_q) {
    //   \Drupal::messenger()->addMessage(t('There are no pending code approvals.'), 'status');
    //   return '';
    // }
    $pending_solution_rows = [];
    while ($pending_solution_data = $pending_solution_q->fetchObject()) {
      /* get experiment data */
      //$experiment_q = \Drupal::database()->query("SELECT * FROM {lab_migration_experiment} WHERE id = %d", $pending_solution_data->experiment_id);
      $query = \Drupal::database()->select('lab_migration_experiment');
      $query->fields('lab_migration_experiment');
      $query->condition('id', $pending_solution_data->experiment_id);
      $experiment_q = $query->execute();
      $experiment_data = $experiment_q->fetchObject();
      /* get proposal data */
      // $proposal_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d", $experiment_data->proposal_id);
      $query = \Drupal::database()->select('lab_migration_proposal');
      $query->fields('lab_migration_proposal');
      $query->condition('id', $experiment_data->proposal_id);
      $proposal_q = $query->execute();
      $proposal_data = $proposal_q->fetchObject();
      /* get solution provider details */
      $solution_provider_user_name = '';
      // $user_data = User::load($proposal_data->solution_provider_uid);
      // //var_dump($user_data);die;
      // if ($user_data) {
      //   $solution_provider_user_name = $user_data->name;
      // }
      // else {
      //   $solution_provider_user_name = '';
      // }
      /* setting table row information */
      $url = Url::fromRoute('lab_migration.code_approval_form', ['solution_id' => $pending_solution_data->id]);
      //     Generate the URL using the route and passing the parameter for solution_id.
// Create the link with Link::fromTextAndUrl and translate the text.
$link = Link::fromTextAndUrl(t('Edit'), $url)->toString();
      $pending_solution_rows[] = [
        $proposal_data->lab_title,
        $experiment_data->title,
        $proposal_data->name,
        $proposal_data->solution_provider_name,
        $link

// // Return or render the link in your form or page.
// $build['edit_link'] = [
//   '#markup' => $link,
// ],
        // Link::fromTextAndUrl('Edit', 'lab-migration/code-approval/approve/' . $pending_solution_data->id),
      ];
    }
    /* check if there are any pending solutions */
    // if (!$pending_solution_rows) {
    //   \Drupal::messenger()->addMessage(t('There are no pending solutions'), 'status');
    //   return '';
    // }
    $header = [
      'Title of the Lab',
      'Experiment',
      'Proposer',
      'Solution Provider',
      'Actions',
    ];
   
    $output =  [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $pending_solution_rows,
    ];
    return $output;
  }


  function lab_migration_proposal_approval_form($form, $form_state)
  {
    global $user;
    /* get current proposal */
    
    $route_match = \Drupal::routeMatch();

    $proposal_id = (int) $route_match->getParameter('proposal_id');
    
    //$proposal_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d", $proposal_id);
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    if ($proposal_q)
      {
        if ($proposal_data = $proposal_q->fetchObject())
          {
            /* everything ok */
          }
        else
          {
            \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
            RedirectResponse('lab-migration/manage-proposal');
            return;
          }
      }
    else
      {
        \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
        RedirectResponse('lab-migration/manage-proposal');
        return;
      }
    // var_dump($proposal_data->name_title);
    //    die;
    $form['name'] = array(
        '#type' => 'item',
        '#markup' => Link::formTextAndUrl($proposal_data->name_title . ' ' . $proposal_data->name, 'user/' . $proposal_data->uid),
        '#title' => t('Name')
    );
    $form['email_id'] = array(
        '#type' => 'item',
        '#markup' => user_load($proposal_data->uid)->mail,
        '#title' => t('Email')
    );
    $form['contact_ph'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->contact_ph,
        '#title' => t('Contact No.')
    );
    $form['department'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->department,
        '#title' => t('Department/Branch')
    );
    $form['university'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->university,
        '#title' => t('University/Institute')
    );
    $form['country'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->country,
        '#title' => t('Country')
    );
    $form['all_state'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->state,
        '#title' => t('State')
    );
    $form['city'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->city,
        '#title' => t('City')
    );
    $form['pincode'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->pincode,
        '#title' => t('Pincode/Postal code')
    );
    $form['operating_system'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->operating_system,
        '#title' => t('Operating System')
    );
    // $form['version'] = array(
    //     '#type' => 'item',
    //     '#markup' => $proposal_data->version,
    //     '#title' => t('Version')
    // );
    $form['syllabus_link'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->syllabus_link,
        '#title' => t('Syllabus Link'),
    );
    $form['lab_title'] = array(
        '#type' => 'item',
        '#markup' => $proposal_data->lab_title,
        '#title' => t('Title of the Lab')
    );
    /* get experiment details */
    $experiment_list = '<ul>';
    //$experiment_q = \Drupal::database()->query("SELECT * FROM {lab_migration_experiment} WHERE proposal_id = %d ORDER BY id ASC", $proposal_id);
    $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('proposal_id', $proposal_id);
    $query->orderBy('id', 'ASC');
    $experiment_q = $query->execute();
    while ($experiment_data = $experiment_q->fetchObject())
      {
        $experiment_list .= '<li>' . $experiment_data->title . '</li>Description of Experiment : ' . $experiment_data->description . '<br>';
      }
    $experiment_list .= '</ul>';
    $form['experiment'] = array(
        '#type' => 'item',
        '#markup' => $experiment_list,
        '#title' => t('Experiments')
    );
    if ($proposal_data->syllabus_copy_file_path != "None")
    {
        // $form['syllabus_copy_file_path'] = array(
        //     '#type' => 'markup',
        //     '#markup' => Link::fromTextAndUrl('Click here to download uploaded syllabus copy', 'lab_migration.download_syllabus_copy' . $proposal_id) . "<br><br>"
        
        //   );
        $form['syllabus_copy_file_path'] = [
          '#type' => 'markup',
          '#markup' => Link::fromTextAndUrl(
            'Click here to download uploaded syllabus copy', 
            Url::fromRoute('/lab-migration/download/syllabus-copy-file', ['proposal_id' => $proposal_id])
          )->toString() . '<br><br>',
        ];
    } //$row->samplefilepath != "None"
    if ($proposal_data->solution_provider_uid == 0)
      {
        $solution_provider = "User will not provide solution, we will have to provide solution";
      }
    else if ($proposal_data->solution_provider_uid == $proposal_data->uid)
      {
        $solution_provider = "Proposer will provide the solution of the lab";
      }
    else
      {
        $solution_provider_user_data = user_load($proposal_data->solution_provider_uid);
        if ($solution_provider_user_data)
            $solution_provider = "Solution will be provided by user " . Link::fromTextAndUrl($solution_provider_user_data->name, 'user/' . $proposal_data->solution_provider_uid);
        else
            $solution_provider = "User does not exists";
      }
    $form['solution_provider_uid'] = array(
        '#type' => 'item',
        '#title' => t('Do you want to provide the solution'),
        '#markup' => $solution_provider
    );
    /* $form['solution_display'] = array(
    '#type' => 'item',
    '#title' => t('Do you want to display the solution on the www.r.fossee.in website'),
    '#markup' => ($proposal_data->solution_display == 1) ? "Yes" : "No",
    );*/
    $form['approval'] = array(
        '#type' => 'radios',
        '#title' => t('Lab migration proposal'),
        '#options' => array(
            '1' => 'Approve',
            '2' => 'Disapprove'
        ),
        '#required' => TRUE
    );
    $form['message'] = array(
        '#type' => 'textarea',
        '#title' => t('Reason for disapproval'),
        '#attributes' => array(
            'placeholder' => t('Enter reason for disapproval in minimum 30 characters '),
            'cols' => 50,
            'rows' => 4
        ),
        '#states' => array(
            'visible' => array(
                ':input[name="approval"]' => array(
                    'value' => '2'
                )
            )
        )
    );
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit')
    );
    $form['cancel'] = array(
        '#type' => 'item',
        '#markup' => Link::fromTextAndurl(t('Cancel'), 'lab-migration/manage-proposal')
    );
    return $form;
  }

  public function lab_migration_proposal_all()
  {
    /* get pending proposals to be approved */
    $proposal_rows = array();
    //$proposal_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} ORDER BY id DESC");
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->orderBy('id', 'DESC');
    $proposal_q = $query->execute();
  
    while ($proposal_data = $proposal_q->fetchObject())
      {
        $approval_status = '';
        switch ($proposal_data->approval_status)
        {
            case 0:
                $approval_status = 'Pending';
                break;
            case 1:
                $approval_status = "Approved";
                break;
            case 2:
                $approval_status = "Dis-approved";
                break;
            case 3:
                $approval_status = "Solved";
                break;
            default:
                $approval_status = 'Unknown';
                break;
        }
      
      $approval_url =  Link::fromTextAndUrl('Status', Url::fromRoute('lab_migration.proposal_status_form',['id'=>$proposal_data->id]))->toString();
      // var_dump($approval_url);die;
      $edit_url =  Link::fromTextAndUrl('Edit', Url::fromRoute('lab_migration.proposal_edit_form',['id'=>$proposal_data->id]))->toString();
      $mainLink = t('@linkApprove | @linkReject', array('@linkApprove' => $approval_url, '@linkReject' => $edit_url));
      
        $proposal_rows[] = array(
            date('d-m-Y', $proposal_data->creation_date),
            // $uid_url = Url::fromRoute('entity.user.canonical', ['user' => $proposal_data->uid]),
            //  $link = Link::fromTextAndUrl($proposal_data->name, $uid_url)->toString(),
            Link::fromTextAndUrl($proposal_data->name, Url::fromRoute('entity.user.canonical', ['user' => $proposal_data->uid])),
        

            // Link::fromTextAndUrl($pending_data->name, 'user/' . $pending_data->uid),
            $proposal_data->lab_title,
            $proposal_data->department,
            $approval_status,
            $mainLink 
          
            );
          }
        $proposal_header = array(
          'Date of Submission',
          'Name',
          'Title of the Lab',
          'Department',
          'Status',
          'Action',
      );
      
      $output = [
        '#type' => 'table',
        '#header' => $proposal_header,
        '#rows' => $proposal_rows,
    ];
      return $output;   
      }
      public function lab_migration_category_all()
      {
        /* get pending proposals to be approved */
        $proposal_rows = array();
        // $proposal_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} ORDER BY id DESC");
        $query = \Drupal::database()->select('lab_migration_proposal');
        $query->fields('lab_migration_proposal');
        $query->orderBy('id', 'DESC');
        $proposal_q = $query->execute();
        // $approval_url = Link::fromTextAndUrl('Status', Url::fromRoute('lab_migration.proposal_status_form',['id'=>$proposal_data->id]))->toString();
      
      // $mainLink = t('@linkApprove | @linkReject', array('@linkApprove' => $approval_url, '@linkReject' => $edit_url));
     
        while ($proposal_data = $proposal_q->fetchObject())
          {
            $edit_url =  Link::fromTextAndUrl('Edit category', Url::fromRoute('lab_migration.category_edit_form',['id'=>$proposal_data->id]))->toString();
            $proposal_rows[] = array(
                date('d-m-Y', $proposal_data->creation_date),
                // $link = Link::fromTextAndUrl(
                //   $proposal_data->name,
                //   Url::fromUri('internal:/lab-migration/proposal' . $proposal_data->uid)
                // )->toRenderable(),
              // l($proposal_data->name, 'user/' . $proposal_data->uid),
              Link::fromTextAndUrl($proposal_data->name, Url::fromRoute('entity.user.canonical', ['user' => $proposal_data->uid])),

                $proposal_data->lab_title,
                $proposal_data->department,
                $proposal_data->category,
                $edit_url,
//                 $url = Url::fromUri('internal:/lab-migration/manage-proposal/category/edit' . $proposal_data->id),
// $link = Link::fromTextAndUrl('Edit/Category', $url),
                // Link::fromTextAndUrl('Edit Category', '/lab-migration/manage-proposal/category/edit' . $proposal_data->id)
            );
          }
        $proposal_header = array(
            'Date of Submission',
            'Name',
            'Title of the Lab',
            'Department',
            'Category',
            'Action'
        );
        
        $output = [
          '#type' => 'table',
          '#header' => $proposal_header,
          '#rows' => $proposal_rows,
          
      ];
        return $output;
      }
    
    


  // public function lab_migration_upload_code_delete() {
  //   $user = \Drupal::currentUser();
    
  //   $route_match = \Drupal::routeMatch();

  //   $solution_id = (int) $route_match->getParameter('solution_id');
    

  //   /* check solution */
  //   // $solution_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution} WHERE id = %d LIMIT 1", $solution_id);
  //   $query = \Drupal::database()->select('lab_migration_solution');
  //   $query->fields('lab_migration_solution');
  //   $query->condition('id', $solution_id);
  //   $query->range(0, 1);
  //   $solution_q = $query->execute();
  //   $solution_data = $solution_q->fetchObject();
  //   if (!$solution_data) {
  //     \Drupal::messenger()->addMessage('Invalid solution.', 'error');
  //     // RedirectResponse('lab-migration/code');
  //     // return new RedirectResponse('/lab-migration/code/list-experiments');
  //     $response = new RedirectResponse(Url::fromRoute('lab-migration/code/list-experiments')->toString());
  //     // Send the redirect response
  //        $response->send();

  //     return;
  //   }
  //   if ($solution_data->approval_status != 0) {
  //     \Drupal::messenger()->addMessage('You cannnot delete a solution after it has been approved. Please contact site administrator if you want to delete this solution.', 'error');
  //     // RedirectResponse('lab-migration/code');
  //      // RedirectResponse('lab-migration/code-approval');
  //   $response = new RedirectResponse(Url::fromRoute('lab_migration.code_approval')->toString());
  
  //   // Send the redirect response
  //   $response->send();
  //     return;
  //   }

  //   //$experiment_q = \Drupal::database()->query("SELECT * FROM {lab_migration_experiment} WHERE id = %d LIMIT 1", $solution_data->experiment_id);
  //   $query = \Drupal::database()->select('lab_migration_experiment');
  //   $query->fields('lab_migration_experiment');
  //   $query->condition('id', $solution_data->experiment_id);
  //   $query->range(0, 1);
  //   $experiment_q = $query->execute();

  //   $experiment_data = $experiment_q->fetchObject();
  //   if (!$experiment_data) {
  //     // \Drupal::messenger()->addMessage('You do not have permission to delete this solution.', 'error');
  //     RedirectResponse('lab-migration/code');
  //     return;
  //   }

  //   //$proposal_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d AND solution_provider_uid = %d LIMIT 1", $experiment_data->proposal_id, $user->uid);
  //   $query = \Drupal::database()->select('lab_migration_proposal');
  //   $query->fields('lab_migration_proposal');
  //   $query->condition('id', $experiment_data->proposal_id);
  //   $query->condition('solution_provider_uid', $user->uid);
  //   $query->range(0, 1);
  //   $proposal_q = $query->execute();
  //   $proposal_data = $proposal_q->fetchObject();
  //   // if (!$proposal_data) {
  //   //   \Drupal::messenger()->addMessage('You do not have permission to delete this solution.', 'error');

  //   //   RedirectResponse('lab-migration/code');
  //   //   return;
  //   // }

  //   /* deleting solution files */
  //   if (\Drupal::service("lab_migration_global")->lab_migration_delete_solution($solution_data->id)) {
  //     \Drupal::messenger()->addMessage('Solution deleted.', 'status');

  //     /* sending email */
  //     // $email_to = $user->mail;

  //     // $from = $config->get('lab_migration_from_email', '');
  //     // $bcc = $config->get('lab_migration_emails', '');
  //     // $cc = $config->get('lab_migration_cc_emails', '');
  //     // $param['solution_deleted_user']['solution_id'] = $proposal_data->id;
  //     // $param['solution_deleted_user']['lab_title'] = $proposal_data->lab_title;
  //     // $param['solution_deleted_user']['experiment_title'] = $experiment_data->title;
  //     // $param['solution_deleted_user']['solution_number'] = $solution_data->code_number;
  //     // $param['solution_deleted_user']['solution_caption'] = $solution_data->caption;
  //     // $param['solution_deleted_user']['user_id'] = $user->uid;
  //     // $param['solution_deleted_user']['headers'] = [
  //     //   'From' => $from,
  //     //   'MIME-Version' => '1.0',
  //     //   'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
  //     //   'Content-Transfer-Encoding' => '8Bit',
  //     //   'X-Mailer' => 'Drupal',
  //     //   'Cc' => $cc,
  //     //   'Bcc' => $bcc,
  //     // ];

  //     // if (!drupal_mail('lab_migration', 'solution_deleted_user', $email_to, language_default(), $param, $from, TRUE)) {
  //     //   \Drupal::messenger()->addMessage('Error sending email message.', 'error');
  //     // }
  //   }
  //   else {
  //     \Drupal::messenger()->addMessage('Error deleting example.', 'status');
  //   }
  //   $response = new RedirectResponse(Url::fromRoute('lab_migration.list_experiments')->toString());
  
  // // Send the redirect response
  // $response->send();
  //   //RedirectResponse('lab-migration/code');
  //   return;
  // }
 public function lab_migration_upload_code_delete() {
    $user = \Drupal::currentUser();
    
    $route_match = \Drupal::routeMatch();

    $solution_id = (int) $route_match->getParameter('solution_id');
    

    /* check solution */
    // $solution_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution} WHERE id = %d LIMIT 1", $solution_id);
    $query = \Drupal::database()->select('lab_migration_solution');
    $query->fields('lab_migration_solution');
    $query->condition('id', $solution_id);
    $query->range(0, 1);
    $solution_q = $query->execute();
    $solution_data = $solution_q->fetchObject();
    if (!$solution_data) {
      \Drupal::messenger()->addMessage('Invalid solution.', 'error');
      // RedirectResponse('lab-migration/code');
      // return new RedirectResponse('/lab-migration/code/list-experiments');
      // $response = new RedirectResponse(Url::fromRoute('/lab-migration/code/list-experiments')->toString());
      // Send the redirect response
         //$response->send();

      return;
    }
    if ($solution_data->approval_status != 0) {
      \Drupal::messenger()->addMessage('You cannnot delete a solution after it has been approved. Please contact site administrator if you want to delete this solution.', 'error');
      // RedirectResponse('lab-migration/code');
       // RedirectResponse('lab-migration/code-approval');
    $response = new RedirectResponse(Url::fromRoute('lab_migration.code_approval')->toString());
  
    // Send the redirect response
    $response->send();
      return;
    }

    //$experiment_q = \Drupal::database()->query("SELECT * FROM {lab_migration_experiment} WHERE id = %d LIMIT 1", $solution_data->experiment_id);
    $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('id', $solution_data->experiment_id);
    $query->range(0, 1);
    $experiment_q = $query->execute();

    $experiment_data = $experiment_q->fetchObject();

if (!$experiment_data) {
  $url = Url::fromUserInput('/lab-migration/code/list-experiments')->toString();
  return new RedirectResponse($url);
}
    //$proposal_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d AND solution_provider_uid = %d LIMIT 1", $experiment_data->proposal_id, $user->uid);
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $experiment_data->proposal_id);
    $query->condition('solution_provider_uid', $user->uid);
    $query->range(0, 1);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    // if (!$proposal_data) {
    //   \Drupal::messenger()->addMessage('You do not have permission to delete this solution.', 'error');

    //   RedirectResponse('lab-migration/code');
    //   return;
    // }

    /* deleting solution files */
    if (\Drupal::service("lab_migration_global")->lab_migration_delete_solution($solution_data->id)) {
      \Drupal::messenger()->addMessage('Solution deleted.', 'status');

      /* sending email */
      // $email_to = $user->mail;

      // $from = $config->get('lab_migration_from_email', '');
      // $bcc = $config->get('lab_migration_emails', '');
      // $cc = $config->get('lab_migration_cc_emails', '');
      // $param['solution_deleted_user']['solution_id'] = $proposal_data->id;
      // $param['solution_deleted_user']['lab_title'] = $proposal_data->lab_title;
      // $param['solution_deleted_user']['experiment_title'] = $experiment_data->title;
      // $param['solution_deleted_user']['solution_number'] = $solution_data->code_number;
      // $param['solution_deleted_user']['solution_caption'] = $solution_data->caption;
      // $param['solution_deleted_user']['user_id'] = $user->uid;
      // $param['solution_deleted_user']['headers'] = [
      //   'From' => $from,
      //   'MIME-Version' => '1.0',
      //   'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
      //   'Content-Transfer-Encoding' => '8Bit',
      //   'X-Mailer' => 'Drupal',
      //   'Cc' => $cc,
      //   'Bcc' => $bcc,
      // ];

      // if (!drupal_mail('lab_migration', 'solution_deleted_user', $email_to, language_default(), $param, $from, TRUE)) {
      //   \Drupal::messenger()->addMessage('Error sending email message.', 'error');
      // }
    }
    else {
      \Drupal::messenger()->addMessage('Error deleting example.', 'status');
    }
    // $response = new RedirectResponse(Url::fromRoute('lab_migration.list_experiments')->toString());
  
  // Send the redirect response
  // $response->send();
    //RedirectResponse('lab-migration/code');
    return;
  }




public function lab_migration_download_solution() {

  $route_match = \Drupal::routeMatch();
  $solution_id = (int) $route_match->getParameter('solution_id');

  $root_path = \Drupal::service("lab_migration_global")->lab_migration_path();

  /* Get solution data */
  $solution_data = \Drupal::database()->select('lab_migration_solution', 's')
    ->fields('s')
    ->condition('id', $solution_id)
    ->execute()
    ->fetchObject();

  if (!$solution_data) {
    \Drupal::messenger()->addError("Invalid solution.");
    // return $this->redirect('/lab-migration/lab-migration-run');
  }

  /* Experiment information */
  $experiment_data = \Drupal::database()->select('lab_migration_experiment', 'e')
    ->fields('e')
    ->condition('id', $solution_data->experiment_id)
    ->execute()
    ->fetchObject();

  /* File list with directory_name JOIN */
  $solution_files_q = \Drupal::database()->query("
    SELECT lmsf.*, lmp.directory_name
    FROM {lab_migration_solution_files} lmsf
    JOIN {lab_migration_solution} lms ON lms.id = lmsf.solution_id
    JOIN {lab_migration_experiment} lme ON lme.id = lms.experiment_id
    JOIN {lab_migration_proposal} lmp ON lmp.id = lme.proposal_id
    WHERE lmsf.solution_id = :sid
  ", [':sid' => $solution_id]);

  /* Dependency files */
  $solution_dependency_files_q = \Drupal::database()->select('lab_migration_solution_dependency', 'd')
    ->fields('d')
    ->condition('solution_id', $solution_id)
    ->execute();

  $CODE_PATH = 'CODE' . $solution_data->code_number . '/';

  /* ZIP file name */
  $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';

  $zip = new \ZipArchive();
  $zip->open($zip_filename, \ZipArchive::CREATE);

  /* Add main files */
  while ($row = $solution_files_q->fetchObject()) {
    $zip->addFile(
      $root_path . $row->directory_name . '/' . $row->filepath,
      $CODE_PATH . str_replace(' ', '_', $row->filename)
    );
  }

  /* Add dependency files */
  while ($row = $solution_dependency_files_q->fetchObject()) {
    $dep = \Drupal::database()->select('lab_migration_dependency_files', 'df')
      ->fields('df')
      ->condition('id', $row->dependency_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if ($dep) {
      $zip->addFile(
        $root_path . $dep->filepath,
        $CODE_PATH . 'DEPENDENCIES/' . str_replace(' ', '_', $dep->filename)
      );
    }
  }

  $zip_file_count = $zip->numFiles;
  $zip->close();

  if ($zip_file_count < 1) {
    \Drupal::messenger()->addError("There are no files in this solution.");
    // return $this->redirect('/lab-migration/lab-migration-run');
  }

  /* CLEAN output buffers */
  while (ob_get_level()) {
    ob_end_clean();
  }

  /* Use Symfony response to output file */
  $response = new BinaryFileResponse($zip_filename);
  $response->setContentDisposition(
    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
    'CODE' . $solution_data->code_number . '.zip'
  );

  // Delete file after download
  $response->deleteFileAfterSend(true);

  return $response;
}



public function lab_migration_download_solution_file(RouteMatchInterface $route_match) {
  // Get the solution file ID from the route.
  $solution_file_id = (int) $route_match->getParameter('solution_file_id');
  // Fetch solution file data from the database.
  $root_path = \Drupal::service("lab_migration_global")->lab_migration_path();
  $connection = Database::getConnection();
  $query = $connection->select('lab_migration_solution_files', 'lmsf')
    ->fields('lmsf')
    ->condition('lmsf.id', $solution_file_id, '=')
    ->range(0, 1);

  // Join related tables to retrieve directory_name.
  $query->join('lab_migration_solution', 'lms', 'lms.id = lmsf.solution_id');
  $query->join('lab_migration_experiment', 'lme', 'lme.id = lms.experiment_id');
  $query->join('lab_migration_proposal', 'lmp', 'lmp.id = lme.proposal_id');
  $query->addField('lmp', 'directory_name');

  $solution_file_data = $query->execute()->fetchObject();

  if (!$solution_file_data) {
    // Return a 404 response if the file does not exist.
    throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
  }

  // Construct the full file path.
  $file_path = $root_path . $solution_file_data->directory_name . '/' . $solution_file_data->filepath;
  //var_dump($file_path);die;
  if (!file_exists($file_path)) {
    // Return a 404 response if the file is missing.
    throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
  }

  // Use BinaryFileResponse for file downloads.
  $response = new BinaryFileResponse($file_path);
  $response->setContentDisposition(
    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
    str_replace(' ', '_', $solution_file_data->filename)
  );

  return $response;
}

 public function lab_migration_download_experiment() {
    
    $route_match = \Drupal::routeMatch();

$experiment_id = (int) $route_match->getParameter('experiment_id');
//var_dump($experiment_id);die;
$root_path = \Drupal::service("lab_migration_global")->lab_migration_path();
    /* get solution data */
    //$experiment_q = \Drupal::database()->query("SELECT * FROM {lab_migration_experiment} WHERE id = %d", $experiment_id);
    $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('id', $experiment_id);
    $experiment_q = $query->execute();
    $experiment_data = $experiment_q->fetchObject();
    $EXP_PATH = 'EXP' . $experiment_data->number . '/';
    $experiment_number = $experiment_data->number;
    /* zip filename */
     $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
// $temporary_directory = \Drupal::service('file_system')->realpath('temporary://');

// $zip_filename = $temporary_directory . '/zip-' . time() . '-' . rand(0, 999999) . '.zip';

    /* creating zip archive on the server */
    $zip = new \ZipArchive();
    $zip->open($zip_filename,\ZipArchive::CREATE);
    //$solution_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution} WHERE experiment_id = %d AND approval_status = 1", $experiment_id);
    $query = \Drupal::database()->select('lab_migration_solution');
    $query->fields('lab_migration_solution');
    $query->condition('experiment_id', $experiment_id);
    $query->condition('approval_status', 1);
    $solution_q = $query->execute();
    while ($solution_row = $solution_q->fetchObject()) {
      $CODE_PATH = 'CODE' . $solution_row->code_number . '/';
      // $solution_files_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution_files} WHERE solution_id = %d", $solution_row->id);
      $solution_files_q = \Drupal::database()->query("SELECT lmsf.*, lmp.directory_name FROM lab_migration_solution_files lmsf JOIN lab_migration_solution lms JOIN lab_migration_experiment lme JOIN lab_migration_proposal lmp WHERE lms.id = lmsf.solution_id AND lme.id = lms.experiment_id AND lmp.id = lme.proposal_id AND lmsf.solution_id = :solution_id", [
        ':solution_id' => $solution_row->id
        ]);
      //  $query = \Drupal::database()->select('lab_migration_solution_files');
      //   $query->fields('lab_migration_solution_files');
      //   $query->condition('solution_id', $solution_row->id);
        //$solution_files_q = $query_files_q->execute();
      // $solution_dependency_files_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution_dependency} WHERE solution_id = %d", $solution_row->id);        
      while ($solution_files_row = $solution_files_q->fetchObject()) {
        //var_dump($solution_files_row);die;
        $zip->addFile($root_path . $solution_files_row->directory_name . '/' . $solution_files_row->filepath, $EXP_PATH . $CODE_PATH . str_replace(' ', '_', ($solution_files_row->filename)));
        // $zip->addFile($root_path . $LAB_PATH . $solution_files_row->filepath, $LAB_PATH . $EXP_PATH . $CODE_PATH . str_replace(' ', '_', ($solution_files_row->filename)));
// var_dump($root_path);die;
      }
    }
    
    $zip_file_count = $zip->numFiles;
    //var_dump($zip_file_count);die;
    $zip->close();
    if ($zip_file_count > 0) {
      /* download zip file */
      header('Content-Type: application/zip');
      header('Content-disposition: attachment; filename="EXP' . $experiment_data->number . '.zip"');
      header('Content-Length: ' . filesize($zip_filename));
      ob_clean();
      //flush();
      readfile($zip_filename);
      unlink($zip_filename);
    }
    else {
      // \Drupal::messenger()->addMessage("There are no solutions in this experiment to download", 'error');
      // return new Response('');
      // var_dump($zip_filename);die;

      $msg = \Drupal::messenger()->addError('There are no solutions in this experiment to download.');
//return new Response('', Response::HTTP_NO_CONTENT);

      // RedirectResponse('lab-migration/lab-migration-run');
      // $response = new RedirectResponse('/lab-migration/lab-migration-run');
// $response->send();
return $msg;
    }
  }

  public function lab_migration_download_lab() {
    $current_user = \Drupal::currentUser();
    $route_match = \Drupal::routeMatch();
    $lab_id = (int) $route_match->getParameter('lab_id');

    // Get the root path from the custom service.
    $root_path = \Drupal::service('lab_migration_global')->lab_migration_path();

    // Fetch lab details.
    $query = \Drupal::database()->select('lab_migration_proposal', 'lmp')
      ->fields('lmp')
      ->condition('id', $lab_id);
    $lab_data = $query->execute()->fetchObject();

    if (!$lab_data) {
      \Drupal::messenger()->addError($this->t('Lab not found.'));
      return new RedirectResponse(Url::fromRoute('<front>')->toString());
    }

    $LAB_PATH = $lab_data->directory_name . '/';
    $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';

    $zip = new \ZipArchive();
    $zip->open($zip_filename, \ZipArchive::CREATE);

    // Fetch experiments for the lab.
    $experiment_query =\Drupal::database()->select('lab_migration_experiment', 'lme')
      ->fields('lme')
      ->condition('proposal_id', $lab_id);
    $experiment_results = $experiment_query->execute();

    while ($experiment_row = $experiment_results->fetchObject()) {
      $EXP_PATH = 'EXP' . $experiment_row->number . '/';

      // Fetch solutions for the experiment.
      $solution_query =\Drupal::database()->select('lab_migration_solution', 'lms')
        ->fields('lms')
        ->condition('experiment_id', $experiment_row->id)
        ->condition('approval_status', 1);
      $solution_results = $solution_query->execute();

      while ($solution_row = $solution_results->fetchObject()) {
        $CODE_PATH = 'CODE' . $solution_row->code_number . '/';

        // Fetch solution files.
        $files_query =\Drupal::database()->select('lab_migration_solution_files', 'lmsf')
          ->fields('lmsf')
          ->condition('solution_id', $solution_row->id);
        $files_results = $files_query->execute();

        while ($file = $files_results->fetchObject()) {
          $file_path = $root_path . $LAB_PATH . $file->filepath;
          $zip_path = $LAB_PATH . $EXP_PATH . $CODE_PATH . str_replace(' ', '_', $file->filename);

          if (file_exists($file_path)) {
            $zip->addFile($file_path, $zip_path);
          }
        }
      }
    }

    $zip_file_count = $zip->numFiles;
    $zip->close();

    if ($zip_file_count > 0) {
      $response = new BinaryFileResponse($zip_filename);
      $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, str_replace(' ', '_', $lab_data->lab_title) . '.zip');
      $response->deleteFileAfterSend(true);

      return $response;
    }
    else {
      \Drupal::messenger()->addError($this->t('There are no solutions in this Lab to download.'));
      return new RedirectResponse(Url::fromRoute('lab_migration.run_form')->toString());
    }
  }
  public function lab_migration_download_lab1() {
    $user = \Drupal::currentUser();
    
    $route_match = \Drupal::routeMatch();

$lab_id = (int) $route_match->getParameter('lab_id');
$root_path = \Drupal::service("lab_migration_global")->lab_migration_path();
// var_dump($lab_id);die;
// var_dump($lab_id);die;

    /* get solution data */
    //$lab_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d", $lab_id);
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $lab_id);
    $lab_q = $query->execute();
    $lab_data = $lab_q->fetchObject();
    $LAB_PATH = $lab_data->directory_name . '/';
    // var_dump($LAB_PATH);die;
    /* zip filename */
    // $temporary_directory = \Drupal::service('file_system')->realpath('temporary://');

// Create the zip filename.
// $zip_filename = $temporary_directory . '/zip-' . time() . '-' . rand(0, 999999) . '.zip';
// var_dump($root_path);die;
    $zip_filename = $root_path . '/zip-' . time() . '-' . rand(0, 999999) . '.zip';
    // var_dump($zip_filename);die;
    /* creating zip archive on the server */
    $zip = new \ZipArchive();
    $zip->open($zip_filename, \ZipArchive::CREATE);
    //$experiment_q = \Drupal::database()->query("SELECT * FROM {lab_migration_experiment} WHERE proposal_id = %d", $lab_id);
    $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('proposal_id', $lab_id);
    $experiment_q = $query->execute();
    while ($experiment_row = $experiment_q->fetchObject()) {
      $EXP_PATH = 'EXP' . $experiment_row->number . '/';
      //$solution_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution} WHERE experiment_id = %d AND approval_status = 1", $experiment_row->id);
      $query = \Drupal::database()->select('lab_migration_solution');
      $query->fields('lab_migration_solution');
      $query->condition('experiment_id', $experiment_row->id);
      $query->condition('approval_status', 1);
      $solution_q = $query->execute();
      while ($solution_row = $solution_q->fetchObject()) {
        $CODE_PATH = 'CODE' . $solution_row->code_number . '/';
        //$solution_files_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution_files} WHERE solution_id = %d", $solution_row->id);

        $solution_files_q = \Drupal::database()->query("SELECT lmsf.*, lmp.directory_name FROM lab_migration_solution_files lmsf JOIN lab_migration_solution lms JOIN lab_migration_experiment lme JOIN lab_migration_proposal lmp WHERE lms.id = lmsf.solution_id AND lme.id = lms.experiment_id AND lmp.id = lme.proposal_id AND lmsf.id = :solution_id", [
          ':solution_id' => $solution_row->id
          ]);
        $query = \Drupal::database()->select('lab_migration_solution_files');
            $query->fields('lab_migration_solution_files');
            $query->condition('solution_id', $solution_row->id);
            $solution_files_q = $query->execute();
       
        while ($solution_files_row = $solution_files_q->fetchObject()) {
          //  var_dump($root_path);die;
          //  var_dump($LAB_PATH);die;
          // var_dump($root_path . $LAB_PATH . $solution_files_row->filepath, $LAB_PATH . $EXP_PATH . $CODE_PATH . str_replace(' ', '_', ($solution_files_row->filename)));die;
          $zip->addFile($root_path . $LAB_PATH . $solution_files_row->filepath, $LAB_PATH . $EXP_PATH . $CODE_PATH . str_replace(' ', '_', ($solution_files_row->filename)));
          //$zip->addFile($root_path. $LAB_PATH . $EXP_PATH . $CODE_PATH . $solution_files_row->filename);
          // var_dump($zip->numFiles);
        }
        
        // die;
           
      }
    }
    $zip_file_count = $zip->numFiles;
    //  var_dump($zip_file_count);die;
    $zip->close();
// var_dump($user->uid);die;
    if ($zip_file_count > 0) {
      if ($user->uid) {
        
        /* download zip file */
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename="' . str_replace(' ', '_', $lab_data->lab_title) . '.zip"');
        header('Content-Length: ' . filesize($zip_filename));
        ob_clean();
        flush();
        readfile($zip_filename);
        unlink($zip_filename);
        // die;
      }

      else {
        // var_dump("hii");die;
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename="' . str_replace(' ', '_', $lab_data->lab_title) . '.zip"');
        header('Content-Length: ' . filesize($zip_filename));
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        header('Pragma: no-cache');
        ob_end_flush();
        ob_clean();
        flush();
        readfile($zip_filename);
        unlink($zip_filename);
        // die;
      }
    }
    else {
      \Drupal::messenger()->addMessage("There are no solutions in this Lab to download", 'error');
//       // RedirectResponse('lab-migration/lab-migration-run');
      $url = Url::fromRoute('lab_migration.run_form')->toString();

// // Return the RedirectResponse.
return new RedirectResponse($url);

  };}

 public function lab_migration_download_full_experiment() {

  $route_match = \Drupal::routeMatch();
  $experiment_id = (int) $route_match->getParameter('experiment_id');
  $root_path = rtrim(\Drupal::service("lab_migration_global")->lab_migration_path(), '/') . '/';

  // Fetch experiment data
  $experiment_data = \Drupal::database()->select('lab_migration_experiment', 'e')
    ->fields('e')
    ->condition('id', $experiment_id)
    ->execute()
    ->fetchObject();

  if (!$experiment_data) {
    \Drupal::messenger()->addMessage("Invalid experiment ID", 'error');
    return new RedirectResponse('/lab-migration/code-approval/bulk');
  }

  $EXP_PATH = 'EXP' . $experiment_data->number . '/';

  $zip_filename = $root_path . 'zip-' . time() . '-' . rand(1000, 999999) . '.zip';
  $zip = new \ZipArchive();
  $zip->open($zip_filename, \ZipArchive::CREATE);

  // Fetch all solutions (approved + pending)
  $solution_q = \Drupal::database()->select('lab_migration_solution', 's')
    ->fields('s')
    ->condition('experiment_id', $experiment_id)
    ->execute();

  while ($solution = $solution_q->fetchObject()) {

    $CODE_PATH = 'CODE' . $solution->code_number . '/';
    $STATUS_PATH = ($solution->approval_status == 1) ? 'APPROVED/' : 'PENDING/';

    // Fetch file list
    $files_q = \Drupal::database()->select('lab_migration_solution_files', 'f')
      ->fields('f')
      ->condition('solution_id', $solution->id)
      ->execute();

    while ($file = $files_q->fetchObject()) {

      // Fetch directory path from proposal
      $dir = \Drupal::database()->select('lab_migration_solution', 's')
        ->join('lab_migration_experiment', 'e', 'e.id = s.experiment_id')
        ->join('lab_migration_proposal', 'p', 'p.id = e.proposal_id')
        ->fields('p', ['directory_name'])
        ->condition('s.id', $solution->id)
        ->execute()
        ->fetchField();

      $full_source_path = $root_path . $dir . '/' . $file->filepath;
      $zip_inner_path = $STATUS_PATH . $EXP_PATH . $CODE_PATH . str_replace(' ', '_', $file->filename);

      if (file_exists($full_source_path)) {
        $zip->addFile($full_source_path, $zip_inner_path);
      }
    }
  }

  $zip->close();

  if ($zip->numFiles > 0) {
    $response = new \Symfony\Component\HttpFoundation\BinaryFileResponse($zip_filename);
    $response->setContentDisposition(
      \Symfony\Component\HttpFoundation\ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      'EXP' . $experiment_data->number . '.zip'
    );
    return $response;
  }
  else {
    unlink($zip_filename);
    \Drupal::messenger()->addMessage("There are no solutions to download", 'error');
    return new RedirectResponse('/lab-migration/code-approval/bulk');
  }
}



  

public function lab_migration_download_full_lab() {

  $route_match = \Drupal::routeMatch();
  $lab_id = (int) $route_match->getParameter('lab_id');

  // Get root upload path.
  $root_path = \Drupal::service("lab_migration_global")->lab_migration_path();

  $APPROVE_PATH = 'APPROVED/';
  $PENDING_PATH = 'PENDING/';

  /* -----------------------------
     Load proposal (lab) data
  ------------------------------ */
  $lab_data = \Drupal::database()->select('lab_migration_proposal', 'lmp')
    ->fields('lmp')
    ->condition('id', $lab_id)
    ->execute()
    ->fetchObject();

  if (!$lab_data) {
    \Drupal::messenger()->addError("Lab not found.");
    return new RedirectResponse('/lab-migration/code-approval/bulk');
  }

  $LAB_PATH = $lab_data->lab_title . '/';

  /* -----------------------------
     Create ZIP file
  ------------------------------ */
  $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';

  $zip = new \ZipArchive();
  $zip->open($zip_filename, \ZipArchive::CREATE);

  /* -----------------------------
     Get all experiments of this lab
  ------------------------------ */
  $experiment_q = \Drupal::database()->select('lab_migration_experiment', 'lme')
    ->fields('lme')
    ->condition('proposal_id', $lab_id)
    ->execute();

  foreach ($experiment_q as $experiment_row) {

    $EXP_PATH = 'EXP' . $experiment_row->number . '/';

    /* -----------------------------
       Approved solutions
    ------------------------------ */
    $approved_solutions = \Drupal::database()->select('lab_migration_solution', 'lms')
      ->fields('lms')
      ->condition('experiment_id', $experiment_row->id)
      ->condition('approval_status', 1)
      ->execute();

    foreach ($approved_solutions as $solution_row) {

      $CODE_PATH = 'CODE' . $solution_row->code_number . '/';

      // JOIN query
      $solution_files_q = \Drupal::database()->query(
        "SELECT lmsf.*, lmp.directory_name
         FROM lab_migration_solution_files lmsf
         JOIN lab_migration_solution lms ON lms.id = lmsf.solution_id
         JOIN lab_migration_experiment lme ON lme.id = lms.experiment_id
         JOIN lab_migration_proposal lmp ON lmp.id = lme.proposal_id
         WHERE lmsf.solution_id = :solution_id",
        [':solution_id' => $solution_row->id]
      );

      foreach ($solution_files_q as $file_row) {
        $file_path = $root_path . $file_row->directory_name . '/' . $file_row->filepath;

        if (file_exists($file_path)) {
          $zip->addFile(
            $file_path,
            $LAB_PATH . $APPROVE_PATH . $EXP_PATH . $CODE_PATH . $file_row->filename
          );
        }
      }
    }

    /* -----------------------------
       Pending solutions
    ------------------------------ */
    $pending_solutions = \Drupal::database()->select('lab_migration_solution', 'lms')
      ->fields('lms')
      ->condition('experiment_id', $experiment_row->id)
      ->condition('approval_status', 0)
      ->execute();

    foreach ($pending_solutions as $solution_row) {

      $CODE_PATH = 'CODE' . $solution_row->code_number . '/';

      // JOIN query
      $solution_files_q = \Drupal::database()->query(
        "SELECT lmsf.*, lmp.directory_name
         FROM lab_migration_solution_files lmsf
         JOIN lab_migration_solution lms ON lms.id = lmsf.solution_id
         JOIN lab_migration_experiment lme ON lme.id = lms.experiment_id
         JOIN lab_migration_proposal lmp ON lmp.id = lme.proposal_id
         WHERE lmsf.solution_id = :solution_id",
        [':solution_id' => $solution_row->id]
      );

      foreach ($solution_files_q as $file_row) {
        $file_path = $root_path . $file_row->directory_name . '/' . $file_row->filepath;

        if (file_exists($file_path)) {
          $zip->addFile(
            $file_path,
            $LAB_PATH . $PENDING_PATH . $EXP_PATH . $CODE_PATH . $file_row->filename
          );
        }
      }
    }
  }

  $zip_file_count = $zip->numFiles;
  $zip->close();

  /* -----------------------------
     If empty ZIP → message + redirect
  ------------------------------ */
  if ($zip_file_count == 0) {
    \Drupal::messenger()->addError("There are no solutions in this lab to download");
    // return new RedirectResponse('/lab-migration/code-approval/bulk');
  }

  /* -----------------------------
     Return ZIP as Response
  ------------------------------ */
  $response = new Response();
  $response->headers->set('Content-Type', 'application/zip');
  $response->headers->set('Content-Disposition', 'attachment; filename="' . $lab_data->lab_title . '.zip"');
  $response->headers->set('Content-Length', filesize($zip_filename));
  $response->setContent(file_get_contents($zip_filename));

  register_shutdown_function(function() use ($zip_filename) {
    @unlink($zip_filename);
  });

  return $response;
}

  public function lab_migration_labs_progress_all() {
    $page_content = [];
  
    // Perform the database query
    $query = \Drupal::database()->select('lab_migration_proposal', 'lmp');
    $query->fields('lmp');  // Fetch all fields (or specify specific fields here)
    $query->condition('approval_status', 1);
    $query->condition('solution_status', 2);
    $result = $query->execute();
    
    // Fetch all rows as an array of objects
    $results = $result->fetchAll();
  
    // Check if there are results
    if (empty($results)) {
      // If no results, return a message
      $page_content['#markup'] = "We are in the process of updating the lab migration data.";
    } else {
      // If there are results, create an ordered list
      $list_items = [];
      foreach ($results as $row) {
        // Create a list item for each row
        $list_items[] = '<li>' . $row->university . ' (' . $row->lab_title . ')</li>';
      }
  
      // Join list items and add the ordered list HTML around them
      $page_content['#markup'] = '<ol >' . implode('', $list_items) . '</ol>';
    }
  
    // Return the render array (Drupal will render it properly)
    return $page_content;
  }
    
  public function lab_migration_labs_progress_proposal() {
    $page_content = "";
    //$query = "SELECT * FROM {lab_migration_proposal} WHERE approval_status = 1 and solution_status = 2";
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('approval_status', 1);
    $query->condition('solution_status', 2);
    $result = $query->execute();
    if ($result->rowCount() == 0) {
      $page_content .= "We are in the process of updating the lab migration data. ";
    }
    else {
      //$result = \Drupal::database()->query($query);
      $page_content .= "<ol reversed>";
      while ($row = $result->fetchObject()) {
        $page_content .= "<li>";
        $page_content .= $row->university . " ({$row->lab_title})";
        $page_content .= "</li>";
      }
      $page_content .= "</ol>";
    }
    return $page_content;
  }

  public function lab_migration_download_lab_pdf() {
    // Get the current route parameters
    $route_match = \Drupal::routeMatch();
    
    // Retrieve lab_id from the URL parameters
    $lab_id = (int) $route_match->getParameter('lab_id');
    
    // Retrieve full_lab flag from the URL parameters
    $full_lab = (int) $route_match->getParameter('full_lab');
    
    // Ensure the lab ID is valid
    if ($lab_id <= 0) {
      // Handle the invalid lab_id (you can return an error or redirect)
      \Drupal::messenger()->addMessage(t('Invalid lab ID provided.'), 'error');
      return;
    }

    // Use the lab_migration_global service to copy script files
    \Drupal::service("lab_migration_global")->_latex_copy_script_file();
    
    // Generate PDF files based on the full_lab flag
    if ($full_lab == 1) {
      \Drupal::service("lab_migration_global")->_latex_generate_files($lab_id, TRUE);
    } else {
      \Drupal::service("lab_migration_global")->_latex_generate_files($lab_id, FALSE);
    }

    // Return a response (you can modify this to return a PDF download response)
    $url = Url::fromRoute('lab_migration.lab_migration_download_lab_pdf', ['lab_id' => $lab_id]);
    return new Response(t('PDF generated successfully for lab %lab_id. You can download it from %url', [
      '%lab_id' => $lab_id,
      '%url' => $url->toString()
    ]));
  }


  public function lab_migration_delete_lab_pdf() {
    
    $route_match = \Drupal::routeMatch();

$lab_id = (int) $route_match->getParameter('lab_id');
// \Drupal::service("lab_migration_global")->lab_migration_del_lab_pdf($lab_id);
    \Drupal::messenger()->addMessage(t('Lab schedule for regeneration.'), 'status');
    // RedirectResponse('lab_migration/code_approval/bulk');
    $response = new RedirectResponse(Url::fromRoute('lab_migration.bulk_approval_form')->toString());
$response->send();
    return;
  }


public function verify_lab_migration_certificates($qr_code = 0) {
  $route_match = \Drupal::routeMatch();
  $qr_code = (int) $route_match->getParameter('qr_code');
  $page_content = '';

  if ($qr_code) {
    // Call the function to verify the QR code from the database.
    $page_content = verify_qrcode_lm_fromdb($qr_code);
  } else {
    // Get the form for verifying certificates.
    $verify_certificates_form = \Drupal::service('form_builder')->getForm('\Drupal\lab_migration\Form\LabMigrationCertificateForm');
    $page_content = \Drupal::service('renderer')->renderRoot($verify_certificates_form);
  }

  // Return the page content as a Response object.
  return new Response($page_content);
}



public function lab_migration_download_syllabus_copy() {
  // Get the proposal ID from the route.
  $route_match = \Drupal::routeMatch();
  $proposal_id = (int) $route_match->getParameter('proposal_id');
//var_dump($proposal_id);die;
  // Validate the proposal ID.
  if (!$proposal_id) {
    \Drupal::messenger()->addError(t('Invalid proposal ID.'));
    return new RedirectResponse(Url::fromRoute('<front>')->toString());
  }

  // Retrieve the root path and file details.
  $root_path = \Drupal::service('lab_migration_global')->lab_migration_path();
  $connection = \Drupal::database();
  $query = $connection->select('lab_migration_proposal', 'p')
    ->fields('p', ['syllabus_copy_file_path'])
    ->condition('id', $proposal_id);
  $syllabus_copy_file_path = $query->execute()->fetchField();

  // Check if the file path exists in the database.
  if (!$syllabus_copy_file_path) {
    \Drupal::messenger()->addError(t('The syllabus copy file could not be found.'));
    return new RedirectResponse(Url::fromRoute('<front>')->toString());
  }

  // Construct the full file path.
  $full_path = $root_path . DIRECTORY_SEPARATOR . $syllabus_copy_file_path;

  // Validate that the file exists and is readable.
  if (!file_exists($full_path) || !is_readable($full_path)) {
    \Drupal::messenger()->addError(t('The requested file does not exist or is not accessible.'));
    return new RedirectResponse(Url::fromRoute('<front>')->toString());
  }

  // Extract the file name from the path.
  $syllabus_copy_file_name = basename($syllabus_copy_file_path);
//var_dump($syllabus_copy_file_name);die;
  // Create the response for file download.
  $response = new Response();
  $response->setContent(file_get_contents($full_path));
  $response->headers->set('Content-Type', 'application/octet-stream');
  $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
    $syllabus_copy_file_name
  ));
  $response->headers->set('Content-Length', filesize($full_path));

  return $response;
}

  function lab_migration_upload_code_form($form,$form_state)
  {
   
    global $user;
  
    $proposal_data = lab_migration_get_proposal();
    if (!$proposal_data) {
        drupal_goto('');
        return;
    }
  
    /* add javascript for dependency selection effects */
    $dep_selection_js = "(function ($) {
    //alert('ok');
      $('#edit-existing-depfile-dep-lab-title').change(function() {
        var dep_selected = '';   
   
        /* showing and hiding relevant files */
       $('.form-checkboxes .option').hide();
        $('.form-checkboxes .option').each(function(index) {
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
  
      $('.form-checkboxes .option').change(function() {
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
    $query = db_select('lab_migration_experiment');
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
    $form['dwsim_version'] = [
      '#type' => 'select',
      '#title' => t('R version used'),
      '#options' => _lm_list_of_software_version(),
      '#required' => TRUE,
    ];
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



public function lab_migration_list_experiments() {
  // Get proposal data.
  $proposal_data = \Drupal::service("lab_migration_global")->lab_migration_get_proposal();
  if (!$proposal_data) {
    return new RedirectResponse(Url::fromRoute('<front>')->toString());
  }

  // Prepare return HTML with lab and proposer information.
  $return_html = [
    '#markup' => '<strong>Title of the Lab:</strong><br />' . $proposal_data->lab_title . '<br /><br />' .
                 '<strong>Proposer Name:</strong><br />' . $proposal_data->name_title . ' ' . $proposal_data->name . '<br /><br />'
  ];

  // Link to 'Upload Solution' page.
  $upload_solution_url = Url::fromRoute('lab_migration.upload_code_form');
  $return_html['#markup'] .= Link::fromTextAndUrl('Upload Solution', $upload_solution_url)->toString() . '<br />';

  // Prepare experiment table header.
  $experiment_header = ['No. Title of the Experiment', 'Type', 'Status', 'Actions'];
  $experiment_rows = [];

  // Get experiment list.
  $query = \Drupal::database()->select('lab_migration_experiment', 'lme');
  $query->fields('lme');
  $query->condition('proposal_id', $proposal_data->id);
  $query->orderBy('number', 'ASC');
  $experiment_q = $query->execute();

  while ($experiment_data = $experiment_q->fetchObject()) {
    $experiment_rows[] = [
      $experiment_data->number . ') ' . $experiment_data->title,
      '', '', ''
    ];
    //var_dump($experiment_data);die;
    // Get solutions related to each experiment.
    $query = \Drupal::database()->select('lab_migration_solution', 'lms');
    $query->fields('lms');
    $query->condition('experiment_id', $experiment_data->id);
    $query->orderBy('id', 'ASC');
    $solution_q = $query->execute();

    if ($solution_q) {
      while ($solution_data = $solution_q->fetchObject()) {
        //var_dump($solution_data);die;
        $solution_status = ($solution_data->approval_status == 0) ? "Pending" : (($solution_data->approval_status == 1) ? "Approved" : "Unknown");

        // Action link for 'Delete' if approval status is pending.
        $action_link = '';
        if ($solution_data->approval_status == 0) {
          $delete_url = Url::fromUri('internal:/lab-migration/code/delete/' . $solution_data->id);
          //Url::fromRoute('lab_migration.upload_code_delete', ['id' => $solution_data->id]);
          $action_link = Link::fromTextAndUrl('Delete', $delete_url)->toString();
        }

        $experiment_rows[] = [
          // "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . 
          $solution_data->code_number . "   " . $solution_data->caption, 
          '', 
          $solution_status, 
          $action_link
        ];

        // Get solution files related to each solution.
        $query = \Drupal::database()->select('lab_migration_solution_files', 'lmsf');
        $query->fields('lmsf');
        $query->condition('solution_id', $solution_data->id);
        $query->orderBy('id', 'ASC');
        $solution_files_q = $query->execute();

        if ($solution_files_q) {
          while ($solution_files_data = $solution_files_q->fetchObject()) {
            //var_dump($solution_files_data);die;
            $filetype_map = ['S' => 'Source', 'R' => 'Result', 'X' => 'Xcox', 'U' => 'Unknown'];
            $code_file_type = $filetype_map[$solution_files_data->filetype] ?? 'Unknown';

            $download_url = Url::fromUri('internal:/lab-migration/download/file/' . $solution_files_data->id);
            $experiment_rows[] = [
             
              Link::fromTextAndUrl($solution_files_data->filename, $download_url)->toString(),
              $code_file_type,
              '',
              ''
            ];
          }
        }
      
        
      }
    }
  }
//var_dump($experiment_rows);die;
  // Build the table render array.
  $return_html[] = [
    '#theme' => 'table',
    '#header' => $experiment_header,
    '#rows' => $experiment_rows,
  ];

  return $return_html;
}


public function lab_migration_completed_labs_all() {
  $output = [];

  // Prepare the database query to fetch approved lab migration proposals.
  $query = Database::getConnection()->select('lab_migration_proposal', 'lmp');
  $query->fields('lmp');
  $query->condition('approval_status', 3);
  $query->orderBy('approval_date', 'DESC');
  $result = $query->execute();

  // Fetch all rows into an array for easy counting and iteration.
  $rows = $result->fetchAll();
// var_dump($rows);die;
  if (empty($rows)) {
    $output['content'] = [
      '#markup' => 'We are in the process of updating the lab migration data.',
    ];
  } else {
    $preference_rows = [];
    $i = count($rows);
    foreach ($rows as $row) {
      $approval_date = date("Y", $row->approval_date);

      // Create a URL for the lab title link.
      $url = Url::fromUri('internal:/lab-migration/lab-migration-run/' . $row->id);
      $link = Link::fromTextAndUrl($row->lab_title, $url)->toString();

      $preference_rows[] = [
        $i,
        $row->university,
        Markup::create($link),
        $approval_date,
      ];
      $i--;
    }

    // Define table headers.
    $preference_header = [
      'No',
      'Institute',
      'Lab',
      'Year',
    ];

    // Define the table render array.
    $output['table'] = [
      '#type' => 'table',
      '#header' => $preference_header,
      '#rows' => $preference_rows,
    ];
  }
  return $output;

  // Ensure the output is rendered and returned as a Response object.
  // $rendered_output = \Drupal::service('renderer')->renderRoot($output);
  // return new Response($rendered_output);
}
public function _list_all_lm_certificates() {
  $query = Database::getConnection()->query("SELECT * FROM {lab_migration_certificate}");
  $search_rows = [];

  $details_list = $query->fetchAll();
  foreach ($details_list as $details) {
      $download_url = Url::fromUri('internal:/lab-migration/certificate/generate-pdf/' . $details->proposal_id . '/' . $details->id);
      $edit_url = Url::fromUri('internal:/lab-migration/certificate/' . ($details->type == "Proposer" ? 'lm-proposer' : 'lm-participation') . '/form/edit/' . $details->proposal_id . '/' . $details->id);
      
      $search_rows[] = [
          $details->lab_name,
          $details->institute_name,
          $details->name,
          $details->type,
          Link::fromTextAndUrl('Download Certificate', $download_url),
          Link::fromTextAndUrl('Edit Certificate', $edit_url),
      ];
  }

  $search_header = [
      'Lab Name',
      'Institute Name',
      'Name',
      'Type',
      'Download Certificates',
      'Edit Certificates',
  ];

  return [
      '#type' => 'table',
      '#header' => $search_header,
      '#rows' => $search_rows,
      '#empty' => t('No certificates found.'),
  ];
}
function ajax_get_lm_city_list_callback($form, $form_state)
{
    $state_default_value = $form_state['values']['all_state'];
    $district_default_value = $form_state['values']['district'];
    if ($district_default_value != '0')
    {
        $form['city']['#options'] = _lab_migration_list_of_cities($state_default_value, $district_default_value);
        $commands[] = ajax_command_replace("#ajax-city-list-replace", drupal_render($form['city']));
        $form['pincode']['#options'] =  array('0' => '- Select -');
        $commands[] = ajax_command_replace("#ajax-pincode-list-replace", drupal_render($form['pincode']));
    }else{
        $form['city']['#options'] = array('0' => '- Select -');
        $commands[] = ajax_command_replace("#ajax-city-list-replace", drupal_render($form['city']));
    }
    return array(
        '#type' => 'ajax',
        '#commands' => $commands
    );
}
function ajax_get_lm_district_list_callback($form, $form_state)
{
    $state_default_value = $form_state['values']['all_state'];
    if ($state_default_value != '0')
    {
        $form['district']['#options'] = _lab_migration_list_of_district($state_default_value);
        $commands[] = ajax_command_replace("#ajax-district-list-replace", drupal_render($form['district']));
        $form['pincode']['#options'] =  array('0' =>'- Select -');
        $commands[] = ajax_command_replace("#ajax-pincode-list-replace", drupal_render($form['pincode']));
        $form['city']['#options'] = array('0' => '- Select -');
        $commands[] = ajax_command_replace("#ajax-city-list-replace", drupal_render($form['city']));
    }else{
        $form['district']['#options'] = array('0' => '- Select -');
        $commands[] = ajax_command_replace("#ajax-district-list-replace", drupal_render($form['district']));
        $form['pincode']['#options'] =  array('0' =>'- Select -');
        $commands[] = ajax_command_replace("#ajax-pincode-list-replace", drupal_render($form['pincode']));
        $form['city']['#options'] = array('0' => '- Select -');
        $commands[] = ajax_command_replace("#ajax-city-list-replace", drupal_render($form['city']));
    }
    return array(
        '#type' => 'ajax',
        '#commands' => $commands
    );
}
function ajax_get_lm_city_pincode_list_callback($form, $form_state)
{
    $city_default_value = $form_state['values']['city'];
    $state_default_value = $form_state['values']['all_state'];
    $district_default_value = $form_state['values']['district'];
    if ($city_default_value != '0')
    {
        $form['pincode']['#options'] = _lab_migration_list_of_city_pincode($city_default_value,$state_default_value,$district_default_value);
        $commands[] = ajax_command_replace("#ajax-pincode-list-replace", drupal_render($form['pincode']));
    }else{
        $form['pincode']['#options'] =  array('0' => '- Select -');
        $commands[] = ajax_command_replace("#ajax-pincode-list-replace", drupal_render($form['pincode']));
    }
    return array(
        '#type' => 'ajax',
        '#commands' => $commands
    );
}

function lab_migration_category_edit_form(array $form, FormStateInterface $form_state) {
  // Retrieve the current proposal ID from the route.
  $route_match = \Drupal::routeMatch();
  $proposal_id = (int) $route_match->getParameter('id');

  // Fetch the proposal data.
  $query = \Drupal::database()->select('lab_migration_proposal', 'p');
  $query->fields('p');
  $query->condition('id', $proposal_id);
  $proposal_data = $query->execute()->fetchObject();

  if (!$proposal_data) {
    // Handle invalid proposals with an error message and redirect.
    \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
    $url = Url::fromRoute('lab-migration.manage_proposal')->toString();
    return new RedirectResponse($url);
  }

  // Build the form.
  $user = User::load($proposal_data->uid);

  $form['name'] = [
    '#type' => 'item',
    '#markup' => \Drupal::l(
      $proposal_data->name_title . ' ' . $proposal_data->name,
      Url::fromRoute('entity.user.canonical', ['user' => $proposal_data->uid])
    ),
    '#title' => t('Name'),
  ];

  $form['email_id'] = [
    '#type' => 'item',
    '#markup' => $user ? $user->getEmail() : t('Unknown'),
    '#title' => t('Email'),
  ];

  $form['contact_ph'] = [
    '#type' => 'item',
    '#markup' => $proposal_data->contact_ph,
    '#title' => t('Contact No.'),
  ];

  $form['department'] = [
    '#type' => 'item',
    '#markup' => $proposal_data->department,
    '#title' => t('Department/Branch'),
  ];

  $form['university'] = [
    '#type' => 'item',
    '#markup' => $proposal_data->university,
    '#title' => t('University/Institute'),
  ];

  $form['lab_title'] = [
    '#type' => 'item',
    '#markup' => $proposal_data->lab_title,
    '#title' => t('Title of the Lab'),
  ];

  $form['category'] = [
    '#type' => 'select',
    '#title' => t('Category'),
    '#options' => _lm_list_of_departments(), // Replace with the correct department list function.
    '#required' => TRUE,
    '#default_value' => $proposal_data->category,
  ];

  $form['actions']['submit'] = [
    '#type' => 'submit',
    '#value' => t('Submit'),
  ];

  $form['actions']['cancel'] = [
    '#type' => 'link',
    '#title' => t('Cancel'),
    '#url' => Url::fromRoute('lab-migration.manage_proposal_category'),
    '#attributes' => ['class' => ['button']],
  ];

  return $form;
}




}
?>