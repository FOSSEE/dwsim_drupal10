<?php

namespace Drupal\dwsim_flowsheet\Services;

use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Helper service for the DWSIM Flowsheet module.
 */
class DwsimFlowsheetGlobalFunction {

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

  /*************************** VALIDATION FUNCTIONS *****************************/

  public function dwsim_flowsheet_check_valid_filename($file_name) {
    if (!preg_match('/^[0-9a-zA-Z\.\\_]+$/', $file_name))
      return FALSE;
    else if (substr_count($file_name, ".") > 1)
      return FALSE;
    else
      return TRUE;
  }

  public function dwsim_flowsheet_check_name($name = '') {
    if (!preg_match('/^[0-9a-zA-Z\ ]+$/', $name))
      return FALSE;
    else
      return TRUE;
  }

  public function dwsim_flowsheet_check_code_number($number = '') {
    if (!preg_match('/^[0-9]+$/', $number))
      return FALSE;
    else
      return TRUE;
  }

  public function dwsim_flowsheet_path() {
    return $_SERVER['DOCUMENT_ROOT'] . base_path() . 'dwsim_uploads/dwsim_flowsheet_uploads/';
  }

  /************************* USER VERIFICATION FUNCTIONS ************************/

  public function dwsim_flowsheet_get_proposal() {
    $user = $this->currentUser;
    $query = $this->connection->select('dwsim_flowsheet_proposal');
    $query->fields('dwsim_flowsheet_proposal');
    $query->condition('uid', $user->id());
    $query->condition('approval_status', 1);
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $proposal_data = $query->execute()->fetchObject();
    if ($proposal_data) {
      return $proposal_data;
    }

    // Fall back to the latest proposal only for user-facing status messaging.
    $status_query = $this->connection->select('dwsim_flowsheet_proposal');
    $status_query->fields('dwsim_flowsheet_proposal');
    $status_query->condition('uid', $user->id());
    $status_query->orderBy('id', 'DESC');
    $status_query->range(0, 1);
    $latest_proposal = $status_query->execute()->fetchObject();
    if (!$latest_proposal) {
      $this->messenger->addError("You do not have any approved DWSIM Flowsheet proposal. Please propose the flowsheet proposal");
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
        $this->messenger->addError($this->t('You do not have any approved DWSIM Flowsheet proposal. Please propose the flowsheet proposal.'));
        return FALSE;
    }
  }

  /*************************************************************************/
  /***** Convert only first character of string to uppercase ***************/
  /*************************************************************************/

  public function dwsim_flowsheet_ucname($string) {
    $string = ucwords(strtolower($string));
    foreach (['-', "'"] as $delimiter) {
      if (strpos($string, $delimiter) !== false) {
        $string = implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
      }
    }
    return $string;
  }

  public function _df_sentence_case($string) {
    $string = ucwords(strtolower($string));
    foreach (['-', "'"] as $delimiter) {
      if (strpos($string, $delimiter) !== false) {
        $string = implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
      }
    }
    return $string;
  }

  public function _df_list_of_dwsim_compound() {
    $dwsim_compound = [];
    $query = $this->connection->select('dwsim_flowsheet_compounds_from_dwsim');
    $query->fields('dwsim_flowsheet_compounds_from_dwsim');
    $query->orderBy('id', 'ASC');
    $dwsim_compound_list = $query->execute();
    while ($dwsim_compound_list_data = $dwsim_compound_list->fetchObject()) {
      $dwsim_compound[$dwsim_compound_list_data->compound] = $dwsim_compound_list_data->compound;
    }
    return $dwsim_compound;
  }

  public function _df_list_of_unit_operations() {
    $dwsim_unit_operations = [];
    $query = $this->connection->select('dwsim_flowsheet_unit_operations');
    $query->fields('dwsim_flowsheet_unit_operations');
    $query->orderBy('id', 'ASC');
    $dwsim_unit_operations_list = $query->execute();
    while ($dwsim_unit_operations_list_data = $dwsim_unit_operations_list->fetchObject()) {
      $dwsim_unit_operations[$dwsim_unit_operations_list_data->unit_operations] = $dwsim_unit_operations_list_data->unit_operations;
    }
    return $dwsim_unit_operations;
  }

  public function _df_list_of_thermodynamic_packages() {
    $dwsim_thermodynamic_packages = [];
    $query = $this->connection->select('dwsim_flowsheet_thermodynamic_packages');
    $query->fields('dwsim_flowsheet_thermodynamic_packages');
    $query->orderBy('thermodynamic_packages', 'ASC');
    $dwsim_thermodynamic_packages_list = $query->execute();
    while ($dwsim_thermodynamic_packages_list_data = $dwsim_thermodynamic_packages_list->fetchObject()) {
      $dwsim_thermodynamic_packages[$dwsim_thermodynamic_packages_list_data->thermodynamic_packages] = $dwsim_thermodynamic_packages_list_data->thermodynamic_packages;
    }
    return $dwsim_thermodynamic_packages;
  }

  public function _df_list_of_logical_block() {
    $dwsim_logical_block = [];
    $query = $this->connection->select('dwsim_flowsheet_logical_block');
    $query->fields('dwsim_flowsheet_logical_block');
    $query->orderBy('id', 'ASC');
    $dwsim_logical_block_list = $query->execute();
    while ($dwsim_logical_block_list_data = $dwsim_logical_block_list->fetchObject()) {
      $dwsim_logical_block[$dwsim_logical_block_list_data->logical_block] = $dwsim_logical_block_list_data->logical_block;
    }
    return $dwsim_logical_block;
  }

  public function _df_list_of_states() {
    $states = [0 => '-Select-'];
    $query = $this->connection->select('list_states_of_india');
    $query->fields('list_states_of_india');
    $states_list = $query->execute();
    while ($states_list_data = $states_list->fetchObject()) {
      $states[$states_list_data->state] = $states_list_data->state;
    }
    return $states;
  }

  public function _df_list_of_cities() {
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

  public function _df_list_of_pincodes() {
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

  public function _df_list_of_departments() {
    $department = [];
    $query = $this->connection->select('list_of_departments');
    $query->fields('list_of_departments');
    $query->orderBy('id', 'DESC');
    $department_list = $query->execute();
    while ($department_list_data = $department_list->fetchObject()) {
      $department[$department_list_data->department] = $department_list_data->department;
    }
    return $department;
  }

  public function _df_list_of_software_version() {
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

  /**
   * Build directory name from project title and proposer name.
   * Fixed: replaced missing global ucname() with ucwords().
   */
  public function _df_dir_name($project, $proposar_name) {
    $project_title = ucwords($project);
    $proposar_name = ucwords($proposar_name);
    $dir_name = $project_title . ' By ' . $proposar_name;
    $directory_name = str_replace("__", "_", str_replace(" ", "_", str_replace("/", " ", $dir_name)));
    return $directory_name;
  }

  public function dwsim_flowsheet_document_path() {
    return $_SERVER['DOCUMENT_ROOT'] . base_path() . 'dwsim_uploads/dwsim_flowsheet_uploads/';
  }

  public function DF_RenameDir($proposal_id, $dir_name) {
    $query = $this->connection->query("SELECT directory_name,id FROM dwsim_flowsheet_proposal WHERE id = :proposal_id", [
      ':proposal_id' => $proposal_id,
    ]);
    $result = $query->fetchObject();
    if ($result != NULL) {
      $files_id_dir = $this->dwsim_flowsheet_path() . $result->id;
      $file_dir = $this->dwsim_flowsheet_path() . $result->directory_name;
      if (is_dir($file_dir)) {
        return rename($this->dwsim_flowsheet_path() . $result->directory_name, $this->dwsim_flowsheet_path() . $dir_name);
      }
      else if (is_dir($files_id_dir)) {
        return rename($this->dwsim_flowsheet_path() . $result->id, $this->dwsim_flowsheet_path() . $dir_name);
      }
      else {
        $this->messenger->addMessage('Directory not available for rename.');
        return;
      }
    }
    else {
      $this->messenger->addMessage('Project directory name not present in database');
      return;
    }
  }

  public function CreateReadmeFileDWSIMFlowsheetingProject($proposal_id) {
    $result = $this->connection->query("SELECT * from dwsim_flowsheet_proposal WHERE id = :proposal_id", [
      ':proposal_id' => $proposal_id,
    ]);
    $proposal_data = $result->fetchObject();
    $root_path = $this->dwsim_flowsheet_path();
    $readme_file = fopen($root_path . $proposal_data->directory_name . "/README.txt", "w") or die("Unable to open file!");
    $txt = "";
    $txt .= "About the flowsheet";
    $txt .= "\n" . "\n";
    $txt .= "Title Of The Flowsheet Project: " . $proposal_data->project_title . "\n";
    $txt .= "Proposar Name: " . $proposal_data->name_title . " " . $proposal_data->contributor_name . "\n";
    $txt .= "University: " . $proposal_data->university . "\n";
    $txt .= "\n" . "\n";
    $txt .= "DWSIM Flowsheet Project By FOSSEE, IIT Bombay" . "\n";
    fwrite($readme_file, $txt);
    fclose($readme_file);
    return $txt;
  }

  public function rrmdir_project($prop_id) {
    $proposal_id = $prop_id;
    $result = $this->connection->query("SELECT * from dwsim_flowsheet_proposal WHERE id = :proposal_id", [
      ':proposal_id' => $proposal_id,
    ]);
    $proposal_data = $result->fetchObject();
    $root_path = $this->dwsim_flowsheet_document_path();
    $dir = $root_path . $proposal_data->directory_name;
    if ($proposal_data->id == $prop_id) {
      if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
          if ($object != "." && $object != "..") {
            if (filetype($dir . "/" . $object) == "dir") {
              $this->rrmdir($dir . "/" . $object);
            }
            else {
              unlink($dir . "/" . $object);
            }
          }
        }
        reset($objects);
        rmdir($dir);
        $msg = $this->messenger->addMessage("Directory deleted successfully");
        return $msg;
      }
      $msg = $this->messenger->addMessage("Directory not present");
      return $msg;
    }
    else {
      $msg = $this->messenger->addMessage("Data not found");
      return $msg;
    }
  }

  public function rrmdir($dir) {
    if (is_dir($dir)) {
      $objects = scandir($dir);
      foreach ($objects as $object) {
        if ($object != "." && $object != "..") {
          if (filetype($dir . "/" . $object) == "dir")
            $this->rrmdir($dir . "/" . $object);
          else
            unlink($dir . "/" . $object);
        }
      }
      reset($objects);
      rmdir($dir);
    }
  }

  /**
   * Returns render array of user-defined compounds for a proposal.
   * Fixed: replaced deprecated theme('table') with render array.
   */
  public function _dwsim_flowsheet_list_of_user_defined_compound($proposal_id) {
    $data = "";
    $user_defined_compound_list = $this->connection->query(
      "SELECT * FROM dwsim_flowsheet_user_defined_compound WHERE proposal_id = :proposal_id",
      [':proposal_id' => $proposal_id]
    );
    $headers = [
      "List of user defined compounds used in process flowsheet",
      "CAS No.",
    ];
    if ($user_defined_compound_list) {
      $rows = [];
      while ($row = $user_defined_compound_list->fetchObject()) {
        $rows[] = [
          "{$row->user_defined_compound}",
          "{$row->cas_no}",
        ];
      }
      $data = [
        '#type' => 'table',
        '#header' => $headers,
        '#rows' => $rows,
      ];
    }
    else {
      $data .= "Not entered";
    }
    return $data;
  }

}
