<?php

/**
 * @file
 * Contains \Drupal\dwsim_flowsheet\Form\DwsimFlowsheetProposalStatusForm.
 */

namespace Drupal\dwsim_flowsheet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

class DwsimFlowsheetProposalStatusForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dwsim_flowsheet_proposal_status_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    // $proposal_id = (int) arg(3);
    $proposal_id  = (int) \Drupal::routeMatch()->getParameter('id');
    //$proposal_q = db_query("SELECT * FROM {dwsim_flowsheet_proposal} WHERE id = %d", $proposal_id);
    $query = \Drupal::database()->select('dwsim_flowsheet_proposal');
    $query->fields('dwsim_flowsheet_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        /* everything ok */
      } //$proposal_data = $proposal_q->fetchObject()
      else {
        \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
        $response = new RedirectResponse(Url::fromRoute('dwsim_flowsheet.proposal_all')->toString());
        // Send the redirect response
        $response->send();
            return;
        // drupal_goto('flowsheeting-project/manage-proposal');
        // return;
      }
    } //$proposal_q
    else {
      \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
      $response = new RedirectResponse(Url::fromRoute('dwsim_flowsheet.proposal_all')->toString());
      // Send the redirect response
      $response->send();
          return;
      // drupal_goto('flowsheeting-project/manage-proposal');
      // return;
    }
    // @FIXME
    // l() expects a Url object, created from a route name or external URI.
    // $form['contributor_name'] = array(
    // 		'#type' => 'item',
    // 		'#markup' => l($proposal_data->name_title . ' ' . $proposal_data->contributor_name, 'user/' . $proposal_data->uid),
    // 		'#size' => 250,
    // 		'#title' => t('Student name')
    // 	);
    $url = Url::fromRoute('entity.user.canonical', ['user' => $proposal_data->uid]);
$link = Link::fromTextAndUrl($proposal_data->name_title . ' ' . $proposal_data->contributor_name, $url)->toString();

// Add it to the form.
$form['contributor_name'] = [
  '#type' => 'item',
  '#markup' => $link,
  '#title' => t('Student name'),
];

    $form['student_email_id'] = [
      '#title' => t('Student Email'),
      '#type' => 'item',
      '#markup' => User::load($proposal_data->uid)->getEmail(),
      '#title' => t('Email'),
    ];
    $form['month_year_of_degree'] = [
      '#type' => 'date_popup',
      '#title' => t('Month and year of award of degree'),
      '#date_label_position' => '',
      '#description' => '',
      '#default_value' => $proposal_data->month_year_of_degree,
      '#date_format' => 'M-Y',
      '#date_increment' => 0,
      '#date_year_range' => '1960:+0',
      '#datepicker_options' => [
        'maxDate' => 0
        ],
      '#disabled' => TRUE,
    ];
    $form['university'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->university,
      '#title' => t('University/Institute'),
    ];
    $form['country'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->country,
      '#title' => t('Country'),
    ];
    $form['all_state'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->state,
      '#title' => t('State'),
    ];
    $form['city'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->city,
      '#title' => t('City'),
    ];
    $form['pincode'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->pincode,
      '#title' => t('Pincode/Postal code'),
    ];
    $form['version'] = [
      '#type' => 'item',
      '#title' => t('DWSIM version'),
      '#markup' => $proposal_data->version,
    ];
    $form['project_guide_name'] = [
      '#type' => 'item',
      '#title' => t('Project guide'),
      '#markup' => $proposal_data->project_guide_name,
    ];
    $form['project_guide_email_id'] = [
      '#type' => 'item',
      '#title' => t('Project guide email'),
      '#markup' => $proposal_data->project_guide_email_id,
    ];
    $form['project_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->project_title,
      '#title' => t('Title of the Flowsheet Project'),
    ];
    $form['process_development_compound_name'] = [
      '#type' => 'item',
      '#title' => t('Name of compound for which process development is carried out'),
      '#markup' => $proposal_data->process_development_compound_name,
    ];
    $form['process_development_compound_cas_number'] = [
      '#type' => 'item',
      '#title' => t('CAS Number of compound for which process development is carried out'),
      '#markup' => $proposal_data->process_development_compound_cas_number,
    ];
    $form['dwsim_database_compound_name'] = [
      '#type' => 'item',
      '#title' => t('List of compounds from DWSIM Database used in process flowsheet'),
      '#markup' => $proposal_data->dwsim_database_compound_name,
    ];
    $proposal_status = '';
    switch ($proposal_data->approval_status) {
      case 0:
        $proposal_status = t('Pending');
        break;
      case 1:
        $proposal_status = t('Approved');
        break;
      case 2:
        $proposal_status = t('Dis-approved');
        break;
      case 3:
        $proposal_status = t('Completed');
        break;
      default:
        $proposal_status = t('Unkown');
        break;
    } //$proposal_data->approval_status
    $user_defined_compound_table = _dwsim_flowsheet_list_of_user_defined_compound($proposal_data->id);
    if (is_array($user_defined_compound_table)) {
      $form['user_defined_compounds_used_in_process_flowsheetcompound_name'] = [
        '#type' => 'fieldset',
        '#title' => t('List of user defined compounds used in process flowsheet'),
      ];
      $form['user_defined_compounds_used_in_process_flowsheetcompound_name']['table'] = $user_defined_compound_table;
    } //$proposal_data->user_defined_compounds_used_in_process != "" || $proposal_data->user_defined_compounds_used_in_process != NULL
    else {
      $form['user_defined_compounds_used_in_process_flowsheetcompound_name'] = [
        '#type' => 'item',
        '#title' => t('List of user defined compounds used in process flowsheet'),
        '#markup' => "Not entered",
      ];
    }
    // var_dump(_dwsim_flowsheet_list_of_user_defined_compound($proposal_data->id));die;
    if ($proposal_data->user_defined_compound_filepath != "" && $proposal_data->user_defined_compound_filepath != "NULL") {
      // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $form['user_defined_compound_filepath'] = array(
// 			'#type' => 'item',
// 			'#title' => t('Uploaded the user defined compound '),
// 			'#markup' => l('Download user defined compound list', 'flowsheeting-project/download/user-defined-compound-file/' . $proposal_id) . "<br><br>"
// 		);
$form['user_defined_compound_filepath'] = [
  '#type' => 'item',
  '#title' => t('Uploaded the user-defined compound'),
  '#markup' => Link::fromTextAndUrl(
      t('Download user-defined compound list'),
      Url::fromRoute('dwsim_flowsheet.download_user_defined_compound', ['proposal_id' => $proposal_id])
  )->toString() . '<br><br>',
];
    } //$proposal_data->user_defined_compound_filepath != ""
    else {
      $form['user_defined_compound_filepath'] = [
        '#type' => 'item',
        '#title' => t('Uploaded the user defined compound '),
        '#markup' => "Not uploaded<br><br>",
      ];
    }
    $form['proposal_status'] = [
      '#type' => 'item',
      '#markup' => $proposal_status,
      '#title' => t('Proposal Status'),
    ];
    if ($proposal_data->approval_status == 0) {
      // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $form['approve'] = array(
// 			'#type' => 'item',
// 			'#markup' => l('Click here', 'flowsheeting-project/manage-proposal/approve/' . $proposal_id),
// 			'#title' => t('Approve')
// 		);

    } //$proposal_data->approval_status == 0
    if ($proposal_data->approval_status == 1) {
      $form['completed'] = [
        '#type' => 'checkbox',
        '#title' => t('Completed'),
        '#description' => t('Check if user has provided all the required files and pdfs.'),
      ];
    } //$proposal_data->approval_status == 1
    if ($proposal_data->approval_status == 2) {
      $form['message'] = [
        '#type' => 'item',
        '#markup' => $proposal_data->dissapproval_reason,
        '#title' => t('Reason for disapproval'),
      ];
    } //$proposal_data->approval_status == 2
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    // @FIXME
    // l() expects a Url object, created from a route name or external URI.
    // $form['cancel'] = array(
    // 		'#type' => 'markup',
    // 		'#markup' => l(t('Cancel'), 'flowsheeting-project/manage-proposal/all')
    // 	);
$form['cancel'] = [
    '#type' => 'markup',
    '#markup' => Link::fromTextAndUrl(
        t('Cancel'),
        Url::fromUri('internal:/flowsheeting-project/manage-proposal/all')
    )->toString(),
];
    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    $proposal_id  = (int) \Drupal::routeMatch()->getParameter('id');
    // $proposal_id = (int) arg(3);
    //$proposal_q = db_query("SELECT * FROM {dwsim_flowsheet_proposal} WHERE id = %d", $proposal_id);
    $query = \Drupal::database()->select('dwsim_flowsheet_proposal');
    $query->fields('dwsim_flowsheet_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        /* everything ok */
      } //$proposal_data = $proposal_q->fetchObject()
      else {
        \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
        // drupal_goto('flowsheeting-project/manage-proposal');
        $response = new RedirectResponse(Url::fromRoute('dwsim_flowsheet.proposal_all')->toString());
        // Send the redirect response
        $response->send();
            return;
      }
    } //$proposal_q
    else {
      \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
      // drupal_goto('flowsheeting-project/manage-proposal');
      $response = new RedirectResponse(Url::fromRoute('dwsim_flowsheet.proposal_all')->toString());
      // Send the redirect response
      $response->send();
          return;
    }
    /* set the book status to completed */
    if ($form_state->getValue(['completed']) == 1) {
      $up_query = "UPDATE dwsim_flowsheet_proposal SET approval_status = :approval_status , actual_completion_date = :expected_completion_date WHERE id = :proposal_id";
      $args = [
        ":approval_status" => '3',
        ":proposal_id" => $proposal_id,
        ":expected_completion_date" => time(),
      ];
      $result = \Drupal::database()->query($up_query, $args);
      CreateReadmeFileDWSIMFlowsheetingProject($proposal_id);
      if (!$result) {
        \Drupal::messenger()->addError('Error in update status');
        return;
      } //!$result
		/* sending email */
      $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
      $email_to = $user_data->mail;
      $from = \Drupal::config('dwsim_flowsheet.settings')->get('dwsim_flowsheet_from_email');
      $bcc = $user->mail . ', ' . \Drupal::config('dwsim_flowsheet.settings')->get('dwsim_flowsheet_emails');
      $cc = \Drupal::config('dwsim_flowsheet.settings')->get('dwsim_flowsheet_cc_emails');
      $params['dwsim_flowsheet_proposal_completed']['proposal_id'] = $proposal_id;
      $params['dwsim_flowsheet_proposal_completed']['user_id'] = $proposal_data->uid;
      $params['dwsim_flowsheet_proposal_completed']['headers'] = [
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
        'dwsim_flowsheet_proposal_completed',
        $email_to,
        $langcode,
        $params,
        $from,
        TRUE
      );
      if (empty($mail_result['result'])) {
        \Drupal::messenger()->addError('Error sending email message.');
      }
      \Drupal::messenger()->addStatus('Congratulations! DWSIM flowsheeting proposal has been marked as completed. User has been notified of the completion.');
    } //$form_state['values']['completed'] == 1
    // drupal_goto('flowsheeting-project/manage-proposal');
    $response = new RedirectResponse(Url::fromRoute('dwsim_flowsheet.proposal_pending')->toString());
    // Send the redirect response
    $response->send();
    return;
  }

}
?>
