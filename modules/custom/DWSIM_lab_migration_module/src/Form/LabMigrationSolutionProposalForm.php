<?php

/**
 * @file
 * Contains \Drupal\lab_migration\Form\LabMigrationSolutionProposalForm.
 */

namespace Drupal\lab_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
class LabMigrationSolutionProposalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lab_migration_solution_proposal_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    // $proposal_id = (int) arg(2);

    $route_match = \Drupal::routeMatch();
    $proposal_id = (int) $route_match->getParameter('id');
   
    //$proposal_q = $injected_database->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d", $proposal_id);
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();

    if (!$proposal_data) {
      \Drupal::messenger()->addmessage("Invalid proposal.", 'error');
      // RedirectResponse('');
//       $url = Url::fromRoute('lab_migration.proposal_pending'); // Replace with your actual route name
// $response = new RedirectResponse($url->toString());

// // Return the response
// return $response;
    }

         $proposer_link = Link::fromTextAndUrl(
      $proposal_data->name_title . ' ' . $proposal_data->name,
      Url::fromUri('internal:/user/' . $proposal_data->uid)
    )->toRenderable();
    
 
    $form['name'] = [
      '#type' => 'item',
      '#title' => $this->t('Proposer Name'),
      'link' => $proposer_link,
    ];
    
    $form['lab_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->lab_title,
      '#title' => t('Title of the Lab'),
    ];
    $experiment_html = '';
    //$experiment_q = $injected_database->query("SELECT * FROM {lab_migration_experiment} WHERE proposal_id = %d", $proposal_id);
    $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('proposal_id', $proposal_id);
    $experiment_q = $query->execute();
    while ($experiment_data = $experiment_q->fetchObject()) {
      $experiment_html .= $experiment_data->title ;
    }
    $form['experiment'] = [
      '#type' => 'item',
      '#markup' => $experiment_html,
      '#title' => $this->t('Experiment List'),
    ];
    $form['solution_provider_name_title'] = [
      '#type' => 'select',
      '#title' => t('Title'),
      '#options' => [
        'Mr' => 'Mr',
        'Ms' => 'Ms',
        'Mrs' => 'Mrs',
        'Dr' => 'Dr',
        'Prof' => 'Prof',
      ],
      '#required' => TRUE,
    ];
    $form['solution_provider_name'] = [
      '#type' => 'textfield',
      '#title' => t('Name of the Solution Provider'),
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
    ];
    $form['solution_provider_email_id'] = [
      '#type' => 'textfield',
      '#title' => t('Email'),
      '#size' => 30,
      '#value' => $user->getEmail(),
      '#disabled' => TRUE,
    ];
    $form['solution_provider_contact_ph'] = [
      '#type' => 'textfield',
      '#title' => t('Contact No.'),
      '#size' => 30,
      '#maxlength' => 15,
      '#required' => TRUE,
    ];
    $form['solution_provider_department'] = [
      '#type' => 'select',
      '#title' => t('Department/Branch'),
      '#options' => \Drupal::service("lab_migration_global")->_lm_list_of_departments(),
      '#required' => TRUE,
    ];
    $form['solution_provider_university'] = [
      '#type' => 'textfield',
      '#title' => t('University/Institute'),
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
    ];
    $form['country'] = [
      '#type' => 'select',
      '#title' => t('Country'),
      '#options' => [
        'India' => 'India',
        'Others' => 'Others',
      ],
      '#required' => TRUE,
      '#tree' => TRUE,
      '#validated' => TRUE,
    ];
    $form['other_country'] = [
      '#type' => 'textfield',
      '#title' => t('Other than India'),
      '#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your country name')
        ],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['other_state'] = [
      '#type' => 'textfield',
      '#title' => t('State other than India'),
      '#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your state/region name')
        ],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['other_city'] = [
      '#type' => 'textfield',
      '#title' => t('City other than India'),
      '#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your city name')
        ],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['all_state'] = [
      '#type' => 'select',
      '#title' => t('State'),
      '#options' => \Drupal::service("lab_migration_global")->_lm_list_of_states(),
      '#validated' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'India'
            ]
          ]
        ],
    ];
    $form['city'] = [
      '#type' => 'select',
      '#title' => t('City'),
      '#options' => \Drupal::service("lab_migration_global")->_lm_list_of_cities(),
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'India'
            ]
          ]
        ],
    ];
    $form['pincode'] = [
      '#type' => 'textfield',
      '#title' => t('Pincode'),
      '#size' => 30,
      '#maxlength' => 6,
      '#required' => FALSE,
      '#attributes' => [
        'placeholder' => 'Enter pincode....'
        ],
    ];
    $form['version'] = [
      '#type' => 'select',
      '#title' => t('Version'),
      '#options' => \Drupal::service("lab_migration_global")->_lm_list_of_software_version(),
      '#required' => TRUE,
    ];
    $form['older'] = [
      '#type' => 'textfield',
      '#size' => 30,
      '#maxlength' => 50,
      //'#required' => TRUE,
        '#description' => t('Specify the Older version used'),
      '#states' => [
        'visible' => [
          ':input[name="version"]' => [
            'value' => 'olderversion'
            ]
          ]
        ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Apply for Solution'),
    ];
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    if ($form_state->getValue(['country']) == 'Others') {
      if ($form_state->getValue(['other_country']) == '') {
        $form_state->setErrorByName('other_country', t('Enter country name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      }
      else {
        $form_state->setValue(['country'], $form_state->getValue([
          'other_country'
          ]));
      }
      if ($form_state->getValue(['other_state']) == '') {
        $form_state->setErrorByName('other_state', t('Enter state name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      }
      else {
        $form_state->setValue(['all_state'], $form_state->getValue([
          'other_state'
          ]));
      }
      if ($form_state->getValue(['other_city']) == '') {
        $form_state->setErrorByName('other_city', t('Enter city name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      }
      else {
        $form_state->setValue(['city'], $form_state->getValue(['other_city']));
      }
    }
    else {
      if ($form_state->getValue(['country']) == '') {
        $form_state->setErrorByName('country', t('Select country name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      }
      if ($form_state->getValue(['all_state']) == '') {
        $form_state->setErrorByName('all_state', t('Select state name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      }
      if ($form_state->getValue(['city']) == '') {
        $form_state->setErrorByName('city', t('Select city name'));
        // $form_state['values']['country'] = $form_state['values']['other_country'];
      }
    }

    if ($form_state->getValue(['version']) == 'olderversion') {
      if ($form_state->getValue(['older']) == '') {
        $form_state->setErrorByName('older', t('Please provide valid version'));
      }
    }
    return;
    //$solution_provider_q = $injected_database->query("SELECT * FROM {lab_migration_proposal} WHERE solution_provider_uid = ".$user->uid." AND approval_status IN (0, 1) AND solution_status IN (0, 1, 2)");
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('solution_provider_uid', $user->uid);
    $query->condition('approval_status', [
      0,
      1,
    ], 'IN');
    $query->condition('solution_status', [
      0,
      1,
      2,
    ], 'IN');
    $solution_provider_q = $query->execute();
    if ($solution_provider_q->fetchObject()) {
      $form_state->setErrorByName('', t("You have already applied for a solution. Please compelete that before applying for another solution."));
      // RedirectResponse('lab-migration/open-proposal');
      $response = new RedirectResponse('/lab-migration/open-proposal');
      $response->send();
    }
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    // $proposal_id = (int) arg(2);
    $route_match = \Drupal::routeMatch();

    $proposal_id = (int) $route_match->getParameter('id');
    if ($form_state->getValue(['version']) == 'olderversion') {
      $form_state->setValue(['version'], $form_state->getValue(['older']));
    }
    //$proposal_q = $injected_database->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d", $proposal_id);
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    if (!$proposal_data) {
      \Drupal::messenger()->addmessage("Invalid proposal.", 'error');
      // RedirectResponse('/lab-migration/open-proposal');
      $response = new RedirectResponse('/lab-migration/open-proposal');
      $response->send();
    }
    if ($proposal_data->solution_provider_uid != 0) {
      \Drupal::messenger()->addmessage("Someone has already applied for solving this Lab.", 'error');
      // RedirectResponse('lab-migration/open-proposal');
      $response = new RedirectResponse('/lab-migration/open-proposal');
      $response->send();
    }
    $query = "UPDATE {lab_migration_proposal} set solution_provider_uid = :uid, solution_status = :solution_status, solution_provider_name_title = :solution_provider_name_title, solution_provider_name = :solution_provider_contact_name, solution_provider_contact_ph = :solution_provider_contact_ph, solution_provider_department = :solution_provider_department, solution_provider_university = :solution_provider_university , version = :dwsim_version WHERE id = :proposal_id";
    $args = [
      'uid' => \Drupal::currentUser()->id(),
      "solution_status" => 1,
      "solution_provider_name_title" => $form_state->getValue(['solution_provider_name_title']),
      "solution_provider_contact_name" => $form_state->getValue(['solution_provider_name']),
      "solution_provider_contact_ph" => $form_state->getValue(['solution_provider_contact_ph']),
      "solution_provider_department" => $form_state->getValue(['solution_provider_department']),
      "solution_provider_university" => $form_state->getValue(['solution_provider_university']),
      "dwsim_version" => $form_state->getValue(['version']),
      "proposal_id" => $proposal_id,
    ];

    $result = \Drupal::database()->query($query, $args);
    \Drupal::messenger()->addmessage("We have received your application. We will get back to you soon.", 'status');
        /* sending email */
    $email_to = $user->getEmail();

/* Config */
$config = \Drupal::config('lab_migration.settings');

$from = $config->get('lab_migration_from_email');
$bcc  = $config->get('lab_migration_emails');
$cc   = $config->get('lab_migration_cc_emails');

/* Mandatory fallback */
if (empty($from)) {
  $from = \Drupal::config('system.site')->get('mail');
}

/* Params */
$params['solution_proposal_received'] = [
  'proposal_id' => $proposal_id,
  'user_id' => $user->id(),
  'headers' => [
    'From' => $from,
    'MIME-Version' => '1.0',
    'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
    'Content-Transfer-Encoding' => '8Bit',
    'X-Mailer' => 'Drupal',
    'Cc' => $cc,
    'Bcc' => $bcc,
  ],
];

/* Language */
$langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();

/* Send mail */
$mailManager = \Drupal::service('plugin.manager.mail');

$result = $mailManager->mail(
  'lab_migration',
  'solution_proposal_received',
  $email_to,
  $langcode,
  $params,
  $from,
  TRUE
);

if (empty($result['result'])) {
  \Drupal::messenger()->addError('Error sending email message.');
}
else {
  \Drupal::messenger()->addStatus('Email sent successfully.');
}

    /* Sending email */

$email_to = $config->get('lab_migration_emails');

$from = $config->get('lab_migration_from_email');

/* Fallback if from is empty */
if (empty($from)) {
  $from = \Drupal::config('system.site')->get('mail');
}

/* Language */
$langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();

/* Send mail */
$mailManager = \Drupal::service('plugin.manager.mail');

$result = $mailManager->mail(
  'lab_migration',
  'solution_proposal_received',
  $email_to,
  $langcode,
  $param,
  $from,
  TRUE
);

if (empty($result['result'])) {
  \Drupal::messenger()->addError('Error sending email message.');
}
// $response = new RedirectResponse('<front>');
    // $response->send();
  }


}
?>