<?php

/**
 * @file
 * Contains \Drupal\lab_migration\Form\LabMigrationProposalEditForm.
 */

namespace Drupal\lab_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Drupal\Core\Link;
class LabMigrationProposalEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lab_migration_proposal_edit_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    // $proposal_id = (int) arg(3);
    $route_match = \Drupal::routeMatch();

$proposal_id = (int) $route_match->getParameter('id');
    //$proposal_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d", $proposal_id);
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        /* everything ok */
      }
      else {
        \Drupal::messenger()->addmessage(t('Invalid proposal selected. Please try again.'), 'error');
        // RedirectResponse('lab-migration/manage-proposal');
        

return new RedirectResponse('/lab-migration/manage-proposal/pending');

        return;
      }
    }
    else {
       \Drupal::messenger()->addmessage(t('Invalid proposal selected. Please try again.'), 'error');
      // RedirectResponse('lab-migration/manage-proposal');
      return new RedirectResponse('/lab-migration/manage-proposal/pending');

      // return;
    }
    $user_data = User::load($proposal_data->uid);
    //var_dump($user_data->getEmail());die;
    $form['name_title'] = [
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
      '#default_value' => $proposal_data->name_title,
    ];
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => t('Name of the Proposer'),
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
      '#default_value' => $proposal_data->name,
    ];
    $form['email_id'] = [
      '#type' => 'item',
      '#title' => t('Email'),
      '#markup' => $user_data->getEmail(),
    ];
    $form['contact_ph'] = [
      '#type' => 'textfield',
      '#title' => t('Contact No.'),
      '#size' => 30,
      '#maxlength' => 15,
      '#required' => TRUE,
      '#default_value' => $proposal_data->contact_ph,
    ];
    $form['department'] = [
      '#type' => 'select',
      '#title' => t('Department/Branch'),
      '#options' =>  \Drupal::service("lab_migration_global")->_lm_list_of_departments(),
      '#required' => TRUE,
      '#default_value' => $proposal_data->department,
    ];
    $form['university'] = [
      '#type' => 'textfield',
      '#title' => t('University/Institute'),
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
      '#default_value' => $proposal_data->university,
    ];
    $form['country'] = [
      '#type' => 'select',
      '#title' => t('Country'),
      '#options' => [
        'India' => 'India',
        'Others' => 'Others',
      ],
      '#default_value' => $proposal_data->country,
      '#required' => TRUE,
      '#tree' => TRUE,
      '#validated' => TRUE,
    ];
    $form['other_country'] = [
      '#type' => 'textfield',
      '#title' => t('Other than India'),
      '#size' => 100,
      '#default_value' => $proposal_data->country,
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
      '#default_value' => $proposal_data->state,
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
      '#default_value' => $proposal_data->city,
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
      '#default_value' => $proposal_data->state,
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
      '#default_value' => $proposal_data->city,
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
      '#default_value' => $proposal_data->pincode,
      '#attributes' => [
        'placeholder' => 'Insert pincode of your city/ village....'
        ],
    ];
    // $form['operating_system'] = [
    //   '#type' => 'textfield',
    //   '#default_value' => $proposal_data->operating_system,
    //   '#title' => t('Operating System'),
    // ];
    // $form['version'] = [
    //   '#type' => 'select',
    //   '#title' => t('R Version'),
    //   '#options' => \Drupal::service("lab_migration_global")->_lm_list_of_software_version(),
    //   '#default_value' => $proposal_data->version,
    // ];
    // $form['syllabus_link'] = [
    //   '#type' => 'item',
    //   '#markup' => $proposal_data->syllabus_link,
    //   '#title' => t('Syllabus Link'),
    // ];
    $form['lab_title'] = [
      '#type' => 'textfield',
      '#title' => t('Title of the Lab'),
      '#size' => 100,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $proposal_data->lab_title,
    ];
    /* get experiment details */
    // $experiment_q = \Drupal::database()->query("SELECT * FROM {lab_migration_experiment} WHERE proposal_id = %d ORDER BY id ASC", $proposal_id);
    $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('proposal_id', $proposal_id);
    $query->orderBy('id', 'ASC');
    $experiment_q = $query->execute();
    /*$form['lab_experiment'] = array(
    '#type' => 'fieldset',
    '#collapsible' => FALSE,
    '#tree' => TRUE,
    );*/
    for ($counter = 1; $counter <= 15; $counter++) {
      $experiment_title = '';
      $experiment_data = $experiment_q->fetchObject();
      if ($experiment_data) {
        $experiment_title = $experiment_data->title;
        $experiment_description = $experiment_data->description;
        /*$form['lab_experiment_']['update'][$experiment_data->id] = array(
            '#type' => 'textfield',
            '#title' => t('Title of the Experiment ') . $counter,
            '#size' => 50,
            '#required' => FALSE,
            
            '#default_value' => $experiment_title,
            );
            $form['lab_experiment']['update1'][$experiment_data->id] = array(
            '#type' => 'textarea',
            
            '#title' => t('Description for Experiment ') . $counter,
            '#default_value' => $experiment_description,
            );*/
        $form['lab_experiment_update' . $experiment_data->id] = [
          '#type' => 'textfield',
          '#title' => t('Title of the Experiment ') . $counter,
          '#size' => 100,
          '#default_value' => $experiment_title,
        ];
        $namefield = "lab_experiment_update" . $experiment_data->id;
        $form['lab_experiment_description_update' . $experiment_data->id] = [
          '#type' => 'textarea',
          '#attributes' => [
            'placeholder' => t('Enter Description for your experiment ' . $counter)
            ],
          '#default_value' => $experiment_description,
          '#title' => t('Description for Experiment ') . $counter,
        ];
      }
      else {
        $form['lab_experiment_insert' . $counter] = [
          '#type' => 'textfield',
          '#title' => t('Title of the Experiment ') . $counter,
          '#size' => 100,
          '#required' => FALSE,
          '#default_value' => $experiment_title,
        ];
        $namefield = "lab_experiment_insert" . $counter;
        $form['lab_experiment_description_insert' . $counter] = [
          '#type' => 'textarea',
          '#attributes' => [
            'placeholder' => t('Enter Description for your experiment ' . $counter)
            ],
          '#title' => t('Description for Experiment ') . $counter,
          '#states' => [
            'invisible' => [
              ':input[name=' . $namefield . ']' => [
                'value' => ""
                ]
              ]
            ],
        ];
      }
    }
    if ($proposal_data->solution_provider_uid == 0) {
      $solution_provider_user = 'Open';
    }
    else {
      if ($proposal_data->solution_provider_uid == $proposal_data->uid) {
        $solution_provider_user = 'Proposer';
      }
      else {
        $user_data = User::load($proposal_data->solution_provider_uid);
        if (!$user_data) {
          $solution_provider_user = 'NA';
          \Drupal::messenger()->addmessage('Solution provider user name is invalid', 'error');
        }
        $solution_provider_user = $proposal_data->soultion_provider_name;
      }
    }
    $form['solution_provider_uid'] = [
      '#type' => 'item',
      '#title' => t('Who will provide the solution'),
      '#markup' => $solution_provider_user,
    ];
    $form['open_solution'] = [
      '#type' => 'checkbox',
      '#title' => t('Open the solution for everyone'),
    ];
    $form['solution_display'] = [
      '#type' => 'hidden',
      '#title' => t('Do you want to display the solution on the www.r.fossee.in website'),
      '#options' => [
        '1' => 'Yes'
        ],
      '#required' => TRUE,
      // '#default_value' => ($proposal_data->solution_display == 1) ? "1" : "2",
        '#default_value' => '1',
    ];
    $form['delete_proposal'] = [
      '#type' => 'checkbox',
      '#title' => t('Delete Proposal'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'item',
      // '#markup' => Link::fromTextAndUrl(t('Cancel'), 'lab-migration/manage-proposal'),
      '#markup' => Link::fromTextAndUrl(t('Cancel'), Url::fromRoute('lab_migration.proposal_pending'))->toString(),
    ];
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // $proposal_id = (int) arg(3);
    $route_match = \Drupal::routeMatch();

$proposal_id = (int) $route_match->getParameter('id');
    /* check before delete proposal */
    if ($form_state->getValue(['delete_proposal']) == 1) {
      //$experiment_q = \Drupal::database()->query("SELECT * FROM {lab_migration_experiment} WHERE proposal_id = %d", $proposal_id);
      $query = \Drupal::database()->select('lab_migration_experiment');
      $query->fields('lab_migration_experiment');
      $query->condition('proposal_id', $proposal_id);
      $experiment_q = $query->execute();
      while ($experiment_data = $experiment_q->fetchObject()) {
        //$solution_q = \Drupal::database()->query("SELECT * FROM {lab_migration_solution} WHERE experiment_id = %d", $experiment_data->id);
        $query = \Drupal::database()->select('lab_migration_solution');
        $query->fields('lab_migration_solution');
        $query->condition('experiment_id', $experiment_data->id);
        $solution_q = $query->execute();
        if ($solution_q->fetchObject()) {
          $form_state->setErrorByName('', t('Cannot delete proposal since there are solutions already uploaded. Use the "Bulk Manage" interface to delete this proposal'));
        }
      }
    }
    return;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    // $proposal_id = (int) arg(3);
    $route_match = \Drupal::routeMatch();

$proposal_id = (int) $route_match->getParameter('proposal_id');
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
         \Drupal::messenger()->addmessage(t('Invalid proposal selected. Please try again.'), 'error');
        // RedirectResponse('lab-migration/manage-proposal');
        $response = new RedirectResponse(Url::fromRoute('lab_migration.proposal_all')->toString());
        // Send the redirect response
           $response->send();
        return;
      }
    }
    else {
       \Drupal::messenger()->addmessage(t('Invalid proposal selected. Please try again.'), 'error');
      // RedirectResponse('lab-migration/manage-proposal/pending');
      $response = new RedirectResponse(Url::fromRoute('lab_migration.proposal_all')->toString());
      // Send the redirect response
         $response->send();
      return;
    }
    /* delete proposal */
    if ($form_state->getValue(['delete_proposal']) == 1) {
      //\Drupal::database()->query("DELETE FROM {lab_migration_proposal} WHERE id = %d", $proposal_id);
      $query = \Drupal::database()->delete('lab_migration_proposal');
      $query->condition('id', $proposal_id);
      $num_deleted = $query->execute();
      //\Drupal::database()->query("DELETE FROM {lab_migration_experiment} WHERE proposal_id = %d", $proposal_id);
      $query = \Drupal::database()->delete('lab_migration_experiment');
      $query->condition('proposal_id', $proposal_id);
      $num_deleted = $query->execute();
      \Drupal::messenger()->addmessage(t('Proposal Delete'), 'status');
      // RedirectResponse('lab-migration/manage-proposal');
      // $url = Url::fromRoute('lab_migration/manage-proposal/pending')->toString();
    // Redirect to the URL.
    $response = new RedirectResponse(Url::fromRoute('lab_migration.proposal_all')->toString());
      // Send the redirect response
         $response->send();
      return;
    }
    if ($form_state->getValue(['open_solution']) == 1) {
      // $query = "UPDATE {lab_migration_proposal} SET solution_provider_uid = :solution_provider_uid, solution_status = :solution_status, solution_provider_name_title = '', solution_provider_name = '', solution_provider_contact_ph = '', solution_provider_department = '', solution_provider_university = '' WHERE id = :proposal_id";
        // $args= array(
        //    ":solution_provider_uid" => 0, 
        //    ":solution_status" => 0,
        //    ":proposal_id" => $proposal_id,
        // );
        // $result = \Drupal::database()->query($query, $args);
      $result = \Drupal::database()->update('lab_migration_proposal')->fields([
        'solution_provider_uid' => 0,
        'solution_status' => 0,
        'solution_provider_name_title' => '',
        'solution_provider_name' => '',
        'solution_provider_contact_ph' => '',
        'solution_provider_department' => '',
        'solution_provider_university' => '',
      ])->condition('id', $proposal_id)->execute();
      if (!$result) {
       \Drupal::messenger()->addmessage(t('Solution already open for everyone.'), 'error');
        return;
      }
    }
    $solution_display = 0;
    if ($form_state->getValue(['solution_display']) == 1) {
      $solution_display = 1;
    }
    else {
      $solution_display = 0;
    }
    /* update proposal */
    $v = $form_state->getValues();
    //$query = "UPDATE {lab_migration_proposal} SET name_title = :name_title, name = :name, contact_ph = :contact_ph, department = :department, university = :unversity, lab_title = :lab_title, solution_display = :solution_display WHERE id = :id";
    // $args= array(    
    //    ":name_title" => $v['name_title'],
    //    ":name" => $v['name'],
    //    "contact_ph" => $v['contact_ph'],
    //    ":department" => $v['department'],
    //    ":university" => $v['university'],
    //    ":lab_title" => $v['lab_title'],
    //    ":solution_display" => $solution_display,
    //    ":id" => $proposal_id,
    //  );

    $lab_title = $v['lab_title'];
    $proposar_name = $v['name_title'] . ' ' . $v['name'];
    $university = $v['university'];
    $directory_names = \Drupal::service("lab_migration_global")->_lm_dir_name($lab_title, $proposar_name, $university);
    if (\Drupal::service("lab_migration_global")->LM_RenameDir($proposal_id, $directory_names)) {
      $directory_name = $directory_names;
    }
    else {
      return;
    }

    $query = \Drupal::database()->update('lab_migration_proposal')->fields([
      'name_title' => $v['name_title'],
      'name' => $v['name'],
      'contact_ph' => $v['contact_ph'],
      'department' => $v['department'],
      'university' => $v['university'],
      'city' => $v['city'],
      'pincode' => $v['pincode'],
      'state' => $v['all_state'],
      'operating_system' => $v['operating_system'],
      'version' => $form_state->getValue(['version']),
      'lab_title' => $v['lab_title'],
      'solution_display' => $solution_display,
      'directory_name' => $directory_name,
    ])->condition('id', $proposal_id);
    $result1 = $query->execute();
    //$result=\Drupal::database()->query($query, $args);
    /* updating existing experiments */
    $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('proposal_id', $proposal_id);
    $query->orderBy('id', 'ASC');
    $experiment_q = $query->execute();
    for ($counter = 1; $counter <= 15; $counter++) {
      $experiment_data = $experiment_q->fetchObject();
      if ($experiment_data) {
        $experiment_field_name = 'lab_experiment_update' . $experiment_data->id;
        $experiment_description = 'lab_experiment_description_update' . $experiment_data->id;
        if (strlen(trim($form_state->getValue([$experiment_field_name]))) >= 1) {
          $query = "UPDATE {lab_migration_experiment} SET title = :title, description= :description WHERE id = :id";
          $args = [
            ":title" => trim($form_state->getValue([$experiment_field_name])),
            ":description" => trim($form_state->getValue([$experiment_description])),
            ":id" => $experiment_data->id,
          ];
          $result2 = \Drupal::database()->query($query, $args);
          if (!$result2) {
            \Drupal::messenger()->addmessage(t('Could not update Title of the Experiment : ') . trim($form_state->getValue([$experiment_field_name])), 'error');
          }
        }
        else {
          $query = "DELETE FROM {lab_migration_experiment} WHERE id = :id LIMIT 1";
          $args = [":id" => $experiment_data->id];
          $result3 = \Drupal::database()->query($query, $args);
        }
      }
    }
    /* foreach ($form_state['values']['lab_experiment']['update'] as $update_id => $update_value) {
    if (strlen(trim($update_value)) >= 1) {
    $description= $form_state['values']['lab_experiment_description']['update']; 
    $query = "UPDATE {lab_migration_experiment} SET title = :title and description=:description WHERE id = :id";
    $args = array(
    ":title"=>  trim($update_value),
    ":description"=>trim($description),
    ":id"=> $update_id,
    );
    $result2 = \Drupal::database()->query($query, $args);
    if (!$result2)
    {
     \Drupal::messenger()->addmessage(t('Could not update Title of the Experiment : ') . trim($update_value), 'error');
    }
    } else {
    $query = "DELETE FROM {lab_migration_experiment} WHERE id = :id LIMIT 1";
    $args = array( 
    ":id" => $update_id
    );
    $result3 = \Drupal::database()->query($query, $args);
    }
    }*/
    /* inserting new experiments */
    $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('proposal_id', $proposal_id);
    $query->orderBy('number', 'DESC');
    $query->range(0, 1);
    $number_q = $query->execute();
    if ($number_data = $number_q->fetchObject()) {
      $number = (int) $number_data->number;
      $number++;
    }
    else {
      $number = 1;
    }
    for ($counter = 1; $counter <= 15; $counter++) {
      $lab_experiment_insert = 'lab_experiment_insert' . $counter;
      $lab_experiment_description_insert = 'lab_experiment_description_insert' . $counter;
      if (strlen(trim(!$form_state->getValue([$lab_experiment_insert]))) >= 1) {
        $query = "INSERT INTO {lab_migration_experiment} (proposal_id, number, title, description) VALUES (:proposal_id, :number, :title, :description)";
        $args = [
          ":proposal_id" => $proposal_id,
          ":number" => $number,
          ":title" => trim($form_state->getValue([$lab_experiment_insert])),
          ":description" => trim($form_state->getValue([$lab_experiment_description_insert])),
        ];
        $result4 = \Drupal::database()->query($query, $args);
        if (!$result4) {
          \Drupal::messenger()->addmessage(t('Could not insert Title of the Experiment : ') . trim($form_state->getValue([$lab_experiment_insert])), 'error');
        }
        else {
          $number++;
        }
      }
    }
    /* $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('proposal_id', $proposal_id);
    $query->orderBy('number', 'DESC');
    $query->range(0, 1);
    $number_q = $query->execute();
    if ($number_data = $number_q->fetchObject()) {
    $number = (int)$number_data->number;
    $number++;
    } else {
    $number = 1;
    }
    $insertvalue = array($insert_id => $insert_value);
    $lab_experimentinsert = $form_state['values']['lab_experiment']['insert'];
    $lab_exp_descriptioninsert=$form_state['values']['lab_experiment_description']['insert'];
    if (is_array($lab_experimentinsert) || is_object($lab_experimentinsert))
    {  
    foreach ($lab_experimentinsert as $insertvalue) {
    //foreach ($form_state['values']['lab_experiment']['insert'] as $insert_id => $insert_value) {
    if (strlen(trim($insert_value)) >= 1) {
    $query = "INSERT INTO {lab_migration_experiment} (proposal_id, number, title, description) VALUES :proposal_id, :number, :title, :description";
    $args = array(
    ":proposal_id" => $proposal_id, 
    ":number" => $number, 
    ":title" => trim($insert_value),
    ":description"=>""
    );
    $result4 = \Drupal::database()->query($query, $args);
    if (!$result4)
    {
     \Drupal::messenger()->addmessage(t('Could not insert Title of the Experiment : ') . trim($insert_value), 'error');
    } else {
    $number++;
    }
    }
    }
    }*/
    \Drupal::messenger()->addmessage(t('Proposal Updated'), 'status');
    $response = new RedirectResponse(Url::fromRoute('lab_migration.proposal_pending')->toString());
    // Send the redirect response
       $response->send();
       return;
  }


}
?>