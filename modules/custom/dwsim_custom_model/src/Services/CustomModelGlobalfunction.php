<?php

namespace Drupal\custom_model\Services;

use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class CustomModelGlobalfunction {

  use StringTranslationTrait;

  /** @var \Drupal\Core\Database\Connection */
  protected $connection;

  /** @var \Drupal\Core\Messenger\MessengerInterface */
  protected $messenger;

  /** @var \Drupal\Core\Session\AccountInterface */
  protected $currentUser;

  /**
   * Constructor with injected services.
   */
  public function __construct(Connection $connection, MessengerInterface $messenger, AccountInterface $currentUser) {
    $this->connection = $connection;
    $this->messenger = $messenger;
    $this->currentUser = $currentUser;
  }

  public function custom_model_ideas_files_path() {
    return $_SERVER['DOCUMENT_ROOT'] . base_path() . 'dwsim_uploads/custom_model_uploads/ideas_files/';
  }

  public function custom_model_path() {
    return $_SERVER['DOCUMENT_ROOT'] . base_path() . 'dwsim_uploads/custom_model_uploads/';
  }

  public function default_value_for_uploaded_files($filetype, $proposal_id) {
    $query = $this->connection->select('custom_model_submitted_abstracts_file');
    $query->fields('custom_model_submitted_abstracts_file');
    $query->condition('proposal_id', $proposal_id);
    if ($filetype == "A") {
      $query->condition('filetype', $filetype);
      return $query->execute()->fetchObject();
    }
    elseif ($filetype == "P") {
      $query->condition('filetype', $filetype);
      return $query->execute()->fetchObject();
    }
    elseif ($filetype == "S") {
      $query->condition('filetype', $filetype);
      return $query->execute()->fetchObject();
    }
    else {
      return;
    }
    return;
  }

  public function _cm_list_of_departments() {
    $department = [];
    $query = $this->connection->select('custom_model_list_of_departments');
    $query->fields('custom_model_list_of_departments');
    $query->orderBy('id', 'ASC');
    $department_list = $query->execute();
    while ($department_list_data = $department_list->fetchObject()) {
      $department[$department_list_data->department] = $department_list_data->department;
    }
    return $department;
  }

  public function _list_of_software_versions() {
    $software_version = [];
    $query = $this->connection->select('dwsim_software_version');
    $query->fields('dwsim_software_version');
    $query->orderBy('id', 'ASC');
    $software_version_list = $query->execute();
    while ($software_version_list_data = $software_version_list->fetchObject()) {
      $software_version[$software_version_list_data->dwsim_version] = $software_version_list_data->dwsim_version;
    }
    return $software_version;
  }

  public function _cm_list_of_states() {
    $states = [0 => '-Select-'];
    $query = $this->connection->select('list_states_of_india');
    $query->fields('list_states_of_india');
    $states_list = $query->execute();
    while ($states_list_data = $states_list->fetchObject()) {
      $states[$states_list_data->state] = $states_list_data->state;
    }
    return $states;
  }

  public function _cm_list_of_cities() {
    $city = [0 => '-Select-'];
    $query = $this->connection->select('list_cities_of_india');
    $query->fields('list_cities_of_india');
    $query->orderBy('city', 'ASC');
    $city_list = $query->execute();
    while ($city_list_data = $city_list->fetchObject()) {
      $city[$city_list_data->city] = $city_list_data->city;
    }
    return $city;
  }

  public function _cm_list_of_pincodes() {
    $pincode = [0 => '-Select-'];
    $query = $this->connection->select('list_of_all_india_pincode');
    $query->fields('list_of_all_india_pincode');
    $query->orderBy('pincode', 'ASC');
    $pincode_list = $query->execute();
    while ($pincode_list_data = $pincode_list->fetchObject()) {
      $pincode[$pincode_list_data->pincode] = $pincode_list_data->pincode;
    }
    return $pincode;
  }

  public function _cm_dir_name($project, $proposar_name) {
    $project_title = ucwords(strtolower($project));
    $proposar_name = ucwords(strtolower($proposar_name));
    $dir_name = $project_title . ' By ' . $proposar_name;
    $directory_name = str_replace("__", "_", str_replace(" ", "_", str_replace("/", " ", $dir_name)));
    return $directory_name;
  }

  public function cm_RenameDir($proposal_id, $dir_name) {
    $query = $this->connection->query("SELECT directory_name,id FROM custom_model_proposal WHERE id = :proposal_id", [
      ':proposal_id' => $proposal_id
    ]);
    $result = $query->fetchObject();
    if ($result != NULL) {
      $files_id_dir = $this->custom_model_path() . $result->id;
      $file_dir = $this->custom_model_path() . $result->directory_name;
      if (is_dir($file_dir)) {
        return rename($this->custom_model_path() . $result->directory_name, $this->custom_model_path() . $dir_name);
      }
      else if (is_dir($files_id_dir)) {
        return rename($this->custom_model_path() . $result->id, $this->custom_model_path() . $dir_name);
      }
      else {
        $this->messenger->addWarning('Directory not available for rename.');
        return;
      }
    }
    else {
      $this->messenger->addError('Project directory name not present in database');
      return;
    }
  }

  public function CreateReadmeFileCustomModel($proposal_id) {
    $result = $this->connection->query("SELECT * from custom_model_proposal WHERE id = :proposal_id", [
      ":proposal_id" => $proposal_id
    ]);
    $proposal_data = $result->fetchObject();
    $root_path = $this->custom_model_path();
    $readme_file = fopen($root_path . $proposal_data->directory_name . "/README.txt", "w") or die("Unable to open file!");
    $txt = "";
    $txt .= "About the Custom Model\n\n";
    $txt .= "Title Of The Custom Model Project: " . $proposal_data->project_title . "\n";
    $txt .= "Proposar Name: " . $proposal_data->name_title . " " . $proposal_data->contributor_name . "\n";
    $txt .= "University: " . $proposal_data->university . "\n\n";
    $txt .= "OM PSSP Project By FOSSEE, IIT Bombay\n";
    fwrite($readme_file, $txt);
    fclose($readme_file);
    return $txt;
  }

  public function cm_rrmdir_project($prop_id) {
    $result = $this->connection->query("SELECT * from custom_model_proposal WHERE id = :proposal_id", [
      ":proposal_id" => $prop_id
    ]);
    $proposal_data = $result->fetchObject();
    if (!$proposal_data) {
      $this->messenger->addError('Data not found.');
      return FALSE;
    }

    $root_path = $this->custom_model_path();
    $dir = $root_path . $proposal_data->directory_name;

    if (is_dir($dir)) {
      $objects = scandir($dir);
      foreach ($objects as $object) {
        if ($object != "." && $object != "..") {
          if (filetype($dir . "/" . $object) == "dir") {
            $this->cm_rrmdir($dir . "/" . $object);
          }
          else {
            unlink($dir . "/" . $object);
          }
        }
      }
      reset($objects);
      rmdir($dir);
      $this->messenger->addStatus('Directory deleted successfully.');
      return TRUE;
    }
    $this->messenger->addWarning('Directory not present.');
    return FALSE;
  }

  public function cm_rrmdir($dir) {
    if (is_dir($dir)) {
      $objects = scandir($dir);
      foreach ($objects as $object) {
        if ($object != "." && $object != "..") {
          if (filetype($dir . "/" . $object) == "dir")
            $this->cm_rrmdir($dir . "/" . $object);
          else
            unlink($dir . "/" . $object);
        }
      }
      reset($objects);
      rmdir($dir);
    }
  }

  public function custom_model_get_proposal() {
    $user = $this->currentUser;
    $query = $this->connection->select('custom_model_proposal');
    $query->fields('custom_model_proposal');
    $query->condition('uid', $user->id());
    $query->condition('approval_status', 1);
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $proposal_data = $query->execute()->fetchObject();
    if ($proposal_data) {
      return $proposal_data;
    }

    $status_query = $this->connection->select('custom_model_proposal');
    $status_query->fields('custom_model_proposal');
    $status_query->condition('uid', $user->id());
    $status_query->orderBy('id', 'DESC');
    $status_query->range(0, 1);
    $latest_proposal = $status_query->execute()->fetchObject();

    if (!$latest_proposal) {
      $this->messenger->addError($this->t("You do not have any approved custom model proposal. Please check the proposal status."));
      return FALSE;
    }

    switch ($latest_proposal->approval_status) {
      case 0:
        $this->messenger->addStatus($this->t('Proposal is awaiting approval.'));
        return FALSE;
      case 2:
        $this->messenger->addError($this->t('Proposal has been dis-approved.'));
        return FALSE;
      case 3:
        $this->messenger->addStatus($this->t('Proposal has been marked as completed.'));
        return FALSE;
      default:
        $this->messenger->addError($this->t('Invalid proposal state. Please contact site administrator for further information.'));
        return FALSE;
    }
  }

  public function custom_model_ucname($string) {
    $string = ucwords(strtolower($string));
    foreach (['-', "'"] as $delimiter) {
      if (strpos($string, $delimiter) !== false) {
        $string = implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
      }
    }
    return $string;
  }
}