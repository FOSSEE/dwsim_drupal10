<?php

/**
 * @file
 * Contains \Drupal\lab_migration\Form\LabMigrationSolutionProposalApprovalForm.
 */

namespace Drupal\lab_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Drupal\user\Entity\User;
use Drupal\Core\Link;
use Drupal\Core\Config\ConfigFactoryInterface;

class LabMigrationSolutionProposalApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lab_migration_solution_proposal_approval_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    // $proposal_id = (int) arg(3);
    $route_match = \Drupal::routeMatch();

$proposal_id = (int) $route_match->getParameter('proposal_id');
    // $proposal_q = $injected_database->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d", $proposal_id);
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        /* everything ok */
      }
      else {
        \Drupal::messenger()->addmessage($this->t('Invalid proposal selected. Please try again.'), 'error');
        // RedirectResponse('lab-migration/manage-proposal/pending-solution-proposal');
    //     $url = Url::fromRoute('lab_migration.proposal_pending_solution');
    // $response = new RedirectResponse($url->toString());
    $form_state->setRedirect('lab_migration.manage_proposal.pending_solution_proposal');
    // Send the response back to the client
    
        return;
      }
    }
    else {
      \Drupal::messenger()->addMessage($this->t('Invalid proposal selected. Please try again.'), 'error');
      $form_state->setRedirect('lab_migration.manage_proposal.pending_solution_proposal');      return;
    }
    $form['name'] = [
      '#type' => 'item',
      '#markup' => Link::fromTextAndUrl($proposal_data->solution_provider_name_title . ' ' . $proposal_data->solution_provider_name, 'user/' . $proposal_data->solution_provider_uid),
      '#title' => t('Solution Provider Name'),
    ];
    $form['email_id'] = [
      '#type' => 'item',
      '#markup' => loadMultiple($proposal_data->solution_provider_uid)->mail,
      '#title' => t('Solution Provider Email'),
    ];
    $form['contact_ph'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->solution_provider_contact_ph,
      '#title' => t('Solution Provider Contact No.'),
    ];
    $form['department'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->solution_provider_department,
      '#title' => t('Department/Branch'),
    ];
    $form['university'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->solution_provider_university,
      '#title' => t('University/Institute'),
    ];

    $form['country'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->solution_provider_country,
      '#title' => t('Country'),
    ];
    $form['all_state'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->solution_provider_state,
      '#title' => t('State'),
    ];
    $form['city'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->solution_provider_city,
      '#title' => t('City'),
    ];
    $form['pincode'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->solution_provider_pincode,
      '#title' => t('Pincode/Postal code'),
    ];


    $form['dwsim_version'] = [
      '#type' => 'item',
      '#title' => t('dwsim version used'),
      '#markup' => $proposal_data->dwsim_version,
    ];


    $form['lab_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->lab_title,
      '#title' => t('Title of the Lab'),
    ];
    /* get experiment details */
    $experiment_list = '<ul>';
    //$experiment_q = $injected_database->query("SELECT * FROM {lab_migration_experiment} WHERE proposal_id = %d ORDER BY id ASC", $proposal_id);
    $query = $injected_database->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('proposal_id', $proposal_id);
    $query->orderBy('id', 'ASC');
    $experiment_q = $query->execute();
    while ($experiment_data = $experiment_q->fetchObject()) {
      $experiment_list .= '<li>' . $experiment_data->title . '</li>Description of Experiment : ' . $experiment_data->description . '<br>';
      ;
    }
    $experiment_list .= '</ul>';
    $form['experiment'] = [
      '#type' => 'item',
      '#markup' => $experiment_list,
      '#title' => t('Experiments'),
    ];
    $form['solution_display'] = [
      '#type' => 'item',
      '#title' => t('Display the solution on the www.dwsim.fossee.in website'),
      '#markup' => ($proposal_data->solution_display == 1) ? "Yes" : "No",
    ];
    /*if ($proposal_data->solution_provider_uid == 0)
      {
        $solution_provider = "Open";
      }
    else if ($proposal_data->solution_provider_uid == $proposal_data->uid)
      {
        $solution_provider = "Proposer will provide the solution of the lab";
      }
    else
      {
        $solution_provider_user_data = loadMultiple($proposal_data->solution_provider_uid);
        if ($solution_provider_user_data)
          {
            $solution_provider .= '<ul>' . '<li><strong>Solution Provider:</strong> ' . l($solution_provider_user_data->name, 'user/' . $proposal_data->solution_provider_uid) . '</li>' . '<li><strong>Solution Provider Name:</strong> ' . $proposal_data->solution_provider_name_title . ' ' . $proposal_data->solution_provider_name . '</li>' . '<li><strong>Department:</strong> ' . $proposal_data->solution_provider_department . '</li>' . '<li><strong>University:</strong> ' . $proposal_data->solution_provider_university . '</li>' . '</ul>';
          }
        else
          {
            $solution_provider = "User does not exists";
          }
      }*/

    $proposer .= '<ul>' . '<li><strong>Proposer:</strong> ' . Link::fromTextAndUrl($proposal_data->name, 'user/' . $proposal_data->uid) . '</li>' . '<li><strong>Proposer Name:</strong> ' . $proposal_data->name_title . ' ' . $proposal_data->name . '</li>' . '<li><strong>Contact No:</strong> ' . $proposal_data->contact_ph . '</li>' . '<li><strong>Email:</strong> ' . loadMultiple($proposal_data->uid)->mail . '</li>' . '<li><strong>Department:</strong> ' . $proposal_data->department . '</li>' . '<li><strong>University:</strong> ' . $proposal_data->university . '</li>' . '<li><strong>Country:</strong> ' . $proposal_data->country . '</li>' . '<li><strong>State:</strong> ' . $proposal_data->state . '</li>' . '<li><strong>City:</strong> ' . $proposal_data->city . '</li>' . '<li><strong>Pincode:</strong> ' . $proposal_data->pincode . '</li>' . '</ul>';
    $form['proposer_details'] = [
      '#type' => 'item',
      '#title' => t('Proposer of Lab :'),
      '#markup' => $proposer,
    ];
    /*$form['solution_provider_uid'] = array(
        '#type' => 'item',
        '#title' => t('Solution Provider'),
        '#markup' => $solution_provider
    );*/
    $form['approval'] = [
      '#type' => 'radios',
      '#title' => t('Solution Provider'),
      '#options' => [
        '1' => 'Approve',
        '2' => 'Disapprove',
      ],
      '#required' => TRUE,
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => t('Reason for disapproval'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'markup',
      '#value' => Link::fromTextAndUrl(t('Cancel'), 'lab-migration/manage-proposal/pending-solution-proposal'),
    ];
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // $proposal_id = (int) arg(3);
    $route_match = \Drupal::routeMatch();

$proposal_id = (int) $route_match->getParameter('proposal_id');

    // $solution_provider_q = $injected_database->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d", $proposal_id);
    $query = $injected_database->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $proposal_id);
    $solution_provider_q = $query->execute();
    $solution_provider_data = $solution_provider_q->fetchObject();
    //	$solution_provider_present_q = $injected_database->query("SELECT * FROM {lab_migration_proposal} WHERE solution_provider_uid = %d AND approval_status IN (0, 1) AND id != %d", $solution_provider_data->uid, $proposal_id);
    $query = $injected_database->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('solution_provider_uid', $solution_provider_data->uid);
    $query->condition('approval_status', [
      0,
      1,
    ], 'IN');
    $query->condition('id', $proposal_id, '<>');
    $solution_provider_present_q = $query->execute();
    if ($x = $solution_provider_present_q->fetchObject()) {
      add_message($proposal_id);
      $form_state->setErrorByName('', t('Solution provider has already one proposal active'));
    }
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    // $proposal_id = (int) arg(3);
    $route_match = \Drupal::routeMatch();

$proposal_id = (int) $route_match->getParameter('proposal_id');

    //$proposal_q = $injected_database->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d", $proposal_id);
    $query = $injected_database->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        /* everything ok */
      }
      else {
        add_message(t('Invalid proposal selected. Please try again.'), 'error');
        RedirectResponse('lab-migration/manage-proposal/pending-solution-proposal');
        return;
      }
    }
    else {
      add_message(t('Invalid proposal selected. Please try again.'), 'error');
      RedirectResponse('lab-migration/manage-proposal/pending-solution-proposal');
      return;
    }
    $user_data = loadMultiple($proposal_data->solution_provider_uid);
    if ($form_state->getValue(['approval']) == 1) {
      $query = "UPDATE {lab_migration_proposal} SET solution_status = 2 WHERE id =:proposal_id";
      $args = [":proposal_id" => $proposal_id];
      $injected_database->query($query, $args);
      /* sending email */
      $email_to = $user_data->mail;
      $from = $config->get('lab_migration_from_email', '');
      $bcc = $user->mail . ', ' . $config->get('lab_migration_emails', '');
      $cc = $config->get('lab_migration_cc_emails', '');
      $param['solution_proposal_approved']['proposal_id'] = $proposal_id;
      $param['solution_proposal_approved']['user_id'] = $proposal_data->solution_provider_uid;
      $param['solution_proposal_approved']['headers'] = [
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        'Cc' => $cc,
        'Bcc' => $bcc,
      ];
      if (!drupal_mail('lab_migration', 'solution_proposal_approved', $email_to, language_default(), $param, $from, TRUE)) {
        add_message('Error sending email message.', 'error');
      }
      /*$email_to = $user->mail . ', ' . $config->get('lab_migration_emails', '');
        if (!drupal_mail('lab_migration', 'solution_proposal_approved', $email_to , language_default(), $param, $config->get('lab_migration_from_email', NULL), TRUE))
        add_message('Error sending email message.', 'error');*/
      add_message('Lab migration solution proposal approved. User has been notified of the approval.', 'status');
      RedirectResponse('lab-migration/manage-proposal/pending-solution_proposal');
      return;
    }
    else {
      if ($form_state->getValue(['approval']) == 2) {
        $query = "UPDATE {lab_migration_proposal} SET solution_provider_uid = :solution_provider_uid, solution_status = :solution_status, solution_provider_name_title = '', solution_provider_name = '', solution_provider_contact_ph = '', solution_provider_department = '', solution_provider_university = '' WHERE id = :proposal_id";
        $args = [
          ":solution_provider_uid" => 0,
          ":solution_status" => 0,
          ":proposal_id" => $proposal_id,
        ];
        $injected_database->query($query, $args);
        /* sending email */
        $email_to = $user_data->mail;
        $from = $config->get('lab_migration_from_email', '');
        $bcc = $user->mail . ', ' . $config->get('lab_migration_emails', '');
        $cc = $config->get('lab_migration_cc_emails', '');
        $param['solution_proposal_disapproved']['proposal_id'] = $proposal_id;
        $param['solution_proposal_disapproved']['user_id'] = $proposal_data->solution_provider_uid;
        $param['solution_proposal_disapproved']['message'] = $form_state->getValue(['message']);
        $param['solution_proposal_disapproved']['headers'] = [
          'From' => $from,
          'MIME-Version' => '1.0',
          'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
          'Content-Transfer-Encoding' => '8Bit',
          'X-Mailer' => 'Drupal',
          'Cc' => $cc,
          'Bcc' => $bcc,
        ];
        if (!drupal_mail('lab_migration', 'solution_proposal_disapproved', $email_to, language_default(), $param, $from, TRUE)) {
          add_message('Error sending email message.', 'error');
        }
        add_message('Lab migration solution proposal dis-approved. User has been notified of the dis-approval.', 'status');
        RedirectResponse('lab-migration/manage-proposal/pending-solution-proposal');
        return;
      }
    }
  }

}
?>