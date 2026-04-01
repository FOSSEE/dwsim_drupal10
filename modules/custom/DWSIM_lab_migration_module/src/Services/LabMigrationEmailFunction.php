<?php
namespace Drupal\lab_migration\Services;

use Drupal\Core\LabMigration\PluginMail;

class LabMigrationEmailFunction {
public function lab_migration_mail($key, $message, $params)
  {
    //var_dump($key);die;
    global $user;
    //$language = $message['language'];
    // var_dump($message);die;
    //$language = user_preferred_language($user);
    switch ($key)
     {
    
       case 'proposal_received':
            /* initializing data */
            // $proposal_q = $db->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d LIMIT 1", $params['proposal_received']['proposal_id']);
            // $proposal_data = $proposal_q->fetchObject();
            $query = $this::database()->select('lab_migration_proposal');
            $query->fields('lab_migration_proposal');
            $query->condition('id', $params['proposal_received']['proposal_id']);
            $query->range(0, 1);
            $proposal_data = $query->execute()->fetchObject();
            /* $samplecodefilename = "";
            if (strlen($proposal_data->samplefilepath) >= 5)
            {
            $samplecodefilename = substr($proposal_data->samplefilepath, strrpos($proposal_data->samplefilepath, '/') + 1);
            }
            else
            {
            $samplecodefilename = "Not provided";
            }*/
            if ($proposal_data->solution_display == 1)
              {
                $solution_display = 'Yes';
              }
            else
              {
                $solution_display = 'No';
              }
            if ($proposal_data->solution_provider_uid == 0)
              {
                $solution_provider_user = 'Open';
              }
            else if ($proposal_data->solution_provider_uid == $proposal_data->uid)
              {
                $solution_provider_user = 'Proposer';
              }
            else
              {
                $solution_provider_user = 'Unknown';
              }
            // $experiment_q = $db->query("SELECT * FROM {lab_migration_experiment} WHERE proposal_id = %d ORDER BY number",
            //  $params['proposal_received']['proposal_id'], 1);
            $query = $this::database()->select('lab_migration_experiment');
            $query->fields('lab_migration_experiment');
            $query->condition('proposal_id', $params['proposal_received']['proposal_id']);
            $query->orderBy('number', 'ASC');
            $experiment_q = $query->execute();
            $experiment_list = '
      	';
            while ($experiment_data = $experiment_q->fetchObject())
              {
                $experiment_list .= '<p>' . $experiment_data->number . ') ' . $experiment_data->title . '<br> Description: ' . $experiment_data->description . '<br>';
                $experiment_list .= ' ';
                $experiment_list .= '</p>';
              }
            $user_data = User::load($params['proposal_received']['user_id']);
            $message['headers'] = $params['proposal_received']['headers'];
            $message['subject'] = $this->t('[!site_name] Your Lab migration proposal has been received', array(
                '!site_name' => \Drupal::config('site_name', '')
            ), array(
                'language' => 'en'
            ));
            $message['body'] = array(
                'body' => t('
Dear ' . $proposal_data->name . ',

We have received your proposal for lab migration with the following details:

Full Name: ' . $proposal_data->name_title . ' ' . $proposal_data->name . '
Email: ' . $user_data->mail . '
Contact No.: ' . $proposal_data->contact_ph . '
Department/Branch: ' . $proposal_data->department . '
University/Institute: ' . $proposal_data->university . '
City: ' . $proposal_data->city . '
State: ' . $proposal_data->state . '


Solution Provided By: ' . $solution_provider_user . '

List of experiments: ' . $experiment_list . '

The proposal is under review. You will be notified of the decision.  

Best Wishes,

!site_name Team
FOSSEE, IIT Bombay', array(
                    '!site_name' => \Drupal::config('site_name', ''),
                    '!user_name' => $user_data->name
                ), array(
                    'language' => 'en'
                ))
            );
            break;
          }
  }
}