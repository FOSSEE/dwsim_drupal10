<?php

/**
 * @file
 * Contains \Drupal\lab_migration\Form\LabMigrationProposalApprovalForm.
 */

namespace Drupal\lab_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;

class LabMigrationProposalApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lab_migration_proposal_approval_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    // $proposal_id = (int) arg(3);
    $route_match = \Drupal::routeMatch();

$proposal_id = (int) $route_match->getParameter('id');
// var_dump($proposal_id);die;
    //$proposal_q = $injected_database->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d", $proposal_id);
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        /* everything ok */
      }
      else {
        \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
        // RedirectResponse('lab-migration/manage-proposal');
       $url = Url::fromRoute('lab_migration.proposal_approval_form')->toString();
     \Drupal::service('request_stack')->getCurrentRequest()->query->set('destination', $url); 
        return;
      }
    }
    else {
      \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
      // RedirectResponse('lab-migration.manage-proposal_approve');

      $url = Url::fromRoute('lab_migration.proposal_approval_form')->toString();
     \Drupal::service('request_stack')->getCurrentRequest()->query->set('destination', $url);

      return;
    }
    // var_dump($proposal_data->name_title);
    //    die;
    $form['name'] = [
      '#type' => 'item',
      // '#markup' => Link::fromTextAndUrl($proposal_data->name_title . ' ' . $proposal_data->name, 'user/' . $proposal_data->uid),
      '#markup' => Link::fromTextAndUrl(
  $proposal_data->name_title . ' ' . $proposal_data->name,
  Url::fromUserInput('/user/' . $proposal_data->uid)
)->toString(),
      '#title' => t('Name'),
      
    ];
    $form['email_id'] = [
      '#type' => 'item',
      // '#markup' => loadMultiple($proposal_data->uid)->mail,
      // '#markup' => User::load($proposal_data->uid)->getEmail(),
      '#markup' => \Drupal\user\Entity\User::load($proposal_data->uid)->getEmail(),

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
    // $form['operating_system'] = [
    //   '#type' => 'item',
    //   '#markup' => $proposal_data->operating_system,
    //   '#title' => t('Operating System'),
    // ];
    // $form['version'] = [
    //   '#type' => 'item',
    //   // '#markup' => $proposal_data->version,
    //   '#markup' => isset($proposal_data->version) ? $proposal_data->version : $this->t('Not available'),
    //   '#title' =>$this-> t('Version'),
    // ];
    // $form['syllabus_link'] = [
    //   '#type' => 'item',
    //   '#markup' => $proposal_data->syllabus_link,
    //   '#title' => t('Syllabus Link'),
    // ];
    $form['lab_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->lab_title,
      '#title' => t('Title of the Lab'),
    ];
    /* get experiment details */
    $experiment_list = '<ul>';
    //$experiment_q = $injected_database->query("SELECT * FROM {lab_migration_experiment} WHERE proposal_id = %d ORDER BY id ASC", $proposal_id);
    $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('proposal_id', $proposal_id);
    $query->orderBy('id', 'ASC');
    $experiment_q = $query->execute();
    while ($experiment_data = $experiment_q->fetchObject()) {
      $experiment_list .= '<li>' . $experiment_data->title . '</li>Description of Experiment : ' . $experiment_data->description . '<br>';
    }
    $experiment_list .= '</ul>';
    $form['experiment'] = [
      '#type' => 'item',
      '#markup' => $experiment_list,
      '#title' => t('Experiments'),
    ];
//     if ($proposal_data->syllabus_copy_file_path != "None") {
//       $form['syllabus_copy_file_path'] = [
//         '#type' => 'item',
//         // '#markup' => Link::fromTextAndUrl(t('Click here to download uploaded syllabus copy'), 'lab-migration/download/syllabus-copy-file/' . $proposal_id,$Url) ,
//       '#markup' => Link::fromTextAndUrl(
//   $this->t('Click here to download uploaded syllabus copy'),
//   Url::fromUri('internal:/lab-migration/download/syllabus-copy-file/' . $proposal_id)
// )->toString(),
//         // var_dump( $proposal_id );die;
//       ];
//     } //$row->samplefilepath != "None"
    if ($proposal_data->solution_provider_uid == 0) {
      $solution_provider = "User will not provide solution, we will have to provide solution";
    }
    else {
      if ($proposal_data->solution_provider_uid == $proposal_data->uid) {
        $solution_provider = "Proposer will provide the solution of the lab";
      }
      else {
        $solution_provider_user_data = User::load($proposal_data->solution_provider_uid);
        if ($solution_provider_user_data) {
          $solution_provider = "Solution will be provided by user " . Link::fromTextAndUrl($solution_provider_user_data->name, 'user/' . $proposal_data->solution_provider_uid);
        }
        else {
          $solution_provider = "User does not exists";
        }
      }
    }
    $form['solution_provider_uid'] = [
      '#type' => 'item',
      '#title' => t('Do you want to provide the solution'),
      '#markup' => $solution_provider,
    ];
    /* $form['solution_display'] = array(
    '#type' => 'item',
    '#title' => t('Do you want to display the solution on the www.r.fossee.in website'),
    '#markup' => ($proposal_data->solution_display == 1) ? "Yes" : "No",
    );*/
    $form['approval'] = [
      '#type' => 'radios',
      '#title' => t('Lab migration proposal'),
      '#options' => [
        '1' => 'Approve',
        '2' => 'Disapprove',
      ],
      '#required' => TRUE,
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => t('Reason for disapproval'),
      '#attributes' => [
        'placeholder' => t('Enter reason for disapproval in minimum 30 characters '),
        'cols' => 50,
        'rows' => 4,
      ],
      '#states' => [
        'visible' => [
          ':input[name="approval"]' => [
            'value' => '2'
            ]
          ]
        ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    
    $form['cancel'] = [
      '#type' => 'markup',
      // '#markup' =>Link::fromTextAndUrl(t('Cancel'), 'lab-migration/manage-proposal'),
      '#markup' => Link::fromTextAndUrl(
  $this->t('Cancel'),
  Url::fromUri('internal:/lab-migration/manage-proposal/pending'))->toString(),

    ];
    return $form;
    // var_dump(t('Cancel'), 'lab-migration/manage-proposal');die;

  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if ($form_state->getValue(['approval']) == 2) {
      if ($form_state->getValue(['message']) == '') {
        $form_state->setErrorByName('message', t('Reason for disapproval could not be empty'));
      }
    }
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // $user = \Drupal::currentUser();
    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    /* get current proposal */
    // $proposal_id = (int) arg(3);
    $route_match = \Drupal::routeMatch();

    $proposal_id = (int) $route_match->getParameter('id');
    // $proposal_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d", $proposal_id);
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        /* everything ok */
      }
      else {
        \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
        // RedirectResponse('lab-migration/manage-proposal');
        $url = Url::fromRoute('lab_migration.manage_proposal_approve')->toString();
       
     \Drupal::service('request_stack')->getCurrentRequest()->query->set('destination', $url);

        return;
      }
    }
    else {
      \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
      // RedirectResponse('lab-migration/manage-proposal');
      $url = Url::fromRoute('lab_migration.manage_proposal_approve')->toString();
      \Drupal::service('request_stack')->getCurrentRequest()->query->set('destination', $url);

      return;
    }
    if ($form_state->getValue(['approval']) == 1) {
      
      $query = "UPDATE {lab_migration_proposal} SET approver_uid = :uid, approval_date = :date, approval_status = 1, solution_status = 2 WHERE id = :proposal_id";
      $args = [
       
'uid' => $user->get('uid')->value,

        // ":uid" =>\Drupal::currentUser(),
        ":date" => time(),
        ":proposal_id" => $proposal_id,
      ];
      \Drupal::database()->query($query, $args);
      /* sending email */
$config = \Drupal::config('lab_migration.settings');

    $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
$email_to = $user_data->getEmail();
    $from = \Drupal::config('lab_migration.settings')->get('lab_migration_from_email');
$bcc = \Drupal::config('lab_migration.settings')->get('lab_migration_emails');
$cc = \Drupal::config('lab_migration.settings')->get('lab_migration_cc_emails');

$params['proposal_approved']['proposal_id'] = $proposal_id;
$params['proposal_approved']['user_id']     = $proposal_data->uid;

$params['proposal_approved']['headers'] = [
  'From' => $from,
          'MIME-Version' => '1.0',
          'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
          'Content-Transfer-Encoding' => '8Bit',
          'X-Mailer' => 'Drupal',
          'Cc' => $cc,
          'Bcc' => $bcc,
];

  $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
      $mail_manager = \Drupal::service('plugin.manager.mail');
  if (!\Drupal::service('plugin.manager.mail')->mail('lab_migration', 'proposal_approved', $email_to, 'en', $params, $form, TRUE));
  { \Drupal::messenger()->addMessage('Mail sent successfully.');
  }



      // \Drupal::messenger()->add_message('Lab migration proposal No. ' . $proposal_id . ' approved. User has been notified of the approval.', 'status');
      \Drupal::messenger()->addMessage('Lab migration proposal No. ' . $proposal_id . ' approved. User has been notified of the approval.', 'status');
      // RedirectResponse('lab-migration/manage-proposal');
      $url = Url::fromRoute('lab_migration.proposal_pending')->toString();
      \Drupal::service('request_stack')->getCurrentRequest()->query->set('destination', $url);
      return;
      // var_dump($proposal_id);die;
    }
    else {
      if ($form_state->getValue(['approval']) == 2) {
        $query = "UPDATE {lab_migration_proposal} SET approver_uid = :uid, approval_date = :date, approval_status = 2, message = :message, solution_provider_uid = 0, solution_status = 0 WHERE id = :proposal_id";
        $args = [
          
          ":uid" => $user->get('uid')->value,
          ":date" => time(),
          ":message" => $form_state->getValue(['message']),
          ":proposal_id" => $proposal_id,
        ];
        $result = \Drupal::database()->query($query, $args);
        $config = \Drupal::config('lab_migration.settings');

    $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
$email_to = $user_data->getEmail();
    $from = \Drupal::config('lab_migration.settings')->get('lab_migration_from_email');
$bcc = \Drupal::config('lab_migration.settings')->get('lab_migration_emails');
$cc = \Drupal::config('lab_migration.settings')->get('lab_migration_cc_emails');

$params['proposal_disapproved']['proposal_id'] = $proposal_id;
$params['proposal_disapproved']['user_id']     = $proposal_data->uid;

$params['proposal_disapproved']['headers'] = [
          'From' => $from,
          'MIME-Version' => '1.0',
          'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
          'Content-Transfer-Encoding' => '8Bit',
          'X-Mailer' => 'Drupal',
          'Cc' => $cc,
          'Bcc' => $bcc,
];

  $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
      $mail_manager = \Drupal::service('plugin.manager.mail');
  if (!\Drupal::service('plugin.manager.mail')->mail('lab_migration', 'proposal_disapproved', $email_to, 'en', $params, $form, TRUE));
  { \Drupal::messenger()->addMessage('Mail sent successfully.');
  }


 // }
//       if (!\Drupal::service('plugin.manager.mail')->mail('lab_migration', 'proposal_disapproved', $email_to, 'en', $params, $form, TRUE));
//  { \Drupal::messenger()->addError('Error sending email message.');
// }
        \Drupal::messenger()->addmessage('Lab migration proposal No. ' . $proposal_id . ' dis-approved. User has been notified of the dis-approval.', 'error');
        // RedirectResponse('lab-migration/manage-proposal');
        $url = Url::fromRoute('lab_migration.proposal_pending')->toString();
      \Drupal::service('request_stack')->getCurrentRequest()->query->set('destination', $url);
        return;
      }
    }
  }


}

?>