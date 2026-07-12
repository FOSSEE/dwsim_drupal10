<?php

namespace Drupal\lab_migration\Services;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Provides utility and helper methods for the Lab Migration module.
 */
class LabMigrationGlobalfunction {

  /** @var \Drupal\Core\Database\Connection */
  protected $database;

  /** @var \Drupal\Core\Messenger\MessengerInterface */
  protected $messenger;

  /** @var \Drupal\Core\Session\AccountProxyInterface */
  protected $currentUser;

  /** @var \Drupal\Core\Extension\ModuleHandlerInterface */
  protected $moduleHandler;

  /** @var \Drupal\Core\File\FileSystemInterface */
  protected $fileSystem;

  /** @var \Drupal\lab_migration\Services\MailService */
  protected $mailService;

  /**
   * Constructs a LabMigrationGlobalfunction instance.
   */
  public function __construct(
    Connection $database,
    MessengerInterface $messenger,
    AccountProxyInterface $current_user,
    ModuleHandlerInterface $module_handler,
    FileSystemInterface $file_system,
    MailService $mail_service
  ) {
    $this->database = $database;
    $this->messenger = $messenger;
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
    $this->fileSystem = $file_system;
    $this->mailService = $mail_service;
  }

  /**
   * Returns list of approved labs for select options.
   */
  public function _list_of_labs() {
    $lab_titles = ['0' => 'Please select...'];
    $result = $this->database->select('lab_migration_proposal', 'p')
      ->fields('p')
      ->condition('solution_display', 1)
      ->condition('approval_status', 3)
      ->orderBy('lab_title', 'ASC')
      ->execute();
    foreach ($result as $row) {
      $lab_titles[$row->id] = $row->lab_title . ' (Proposed by ' . $row->name_title . ' ' . $row->name . ')';
    }
    return $lab_titles;
  }

  /**
   * Returns list of Indian states from list_states_of_india table.
   */
  public function _lm_list_of_states() {
    $states = [0 => '-Select-'];
    $result = $this->database->select('list_states_of_india', 's')
      ->fields('s')
      ->execute();
    foreach ($result as $row) {
      $states[$row->state] = $row->state;
    }
    return $states;
  }

  /**
   * Returns list of Indian states from all_india_pincode table.
   */
  public function _lab_migration_list_of_states() {
    $states = ['' => '- Select -'];
    $result = $this->database->query(
      "SELECT DISTINCT state FROM {all_india_pincode} WHERE country = 'India' ORDER BY state ASC"
    );
    foreach ($result as $row) {
      $states[$row->state] = $row->state;
    }
    return $states;
  }

  /**
   * Returns list of Indian cities for select options.
   */
  public function _lm_list_of_cities() {
    $city = [0 => '-Select-'];
    $result = $this->database->select('list_cities_of_india', 'c')
      ->fields('c')
      ->orderBy('city', 'ASC')
      ->execute();
    foreach ($result as $row) {
      $city[$row->city] = $row->city;
    }
    return $city;
  }

  /**
   * Returns list of pincodes for a given city/state/district.
   */
  public function _lab_migration_list_of_city_pincode($city = NULL, $state = NULL, $district = NULL) {
    $pincode = [];
    if ($city) {
      $result = $this->database->query(
        "SELECT pincode FROM {all_india_pincode} WHERE city = :city AND state = :state AND district = :district",
        [':city' => $city, ':state' => $state, ':district' => $district]
      );
      foreach ($result as $row) {
        $pincode[$row->pincode] = $row->pincode;
      }
    }
    else {
      $pincode['000000'] = '000000';
    }
    return $pincode;
  }

  /**
   * Returns list of departments for select options.
   */
  public function _lm_list_of_departments() {
    $department = [];
    $result = $this->database->select('list_of_departments', 'd')
      ->fields('d')
      ->orderBy('id', 'DESC')
      ->execute();
    foreach ($result as $row) {
      $department[$row->department] = $row->department;
    }
    return $department;
  }

  /**
   * Returns list of DWSIM software versions for select options.
   */
  public function _lm_list_of_software_version() {
    $versions = [];
    $result = $this->database->select('dwsim_software_version', 'v')
      ->fields('v')
      ->execute();
    foreach ($result as $row) {
      $versions[$row->dwsim_version] = $row->dwsim_version;
    }
    return $versions;
  }

  /**
   * Returns list of districts for a given state.
   */
  public function _lab_migration_list_of_district($state = NULL) {
    $district = ['' => '- Select -'];
    if ($state) {
      $result = $this->database->query(
        "SELECT DISTINCT district FROM {all_india_pincode} WHERE state = :state ORDER BY district ASC",
        [':state' => $state]
      );
      foreach ($result as $row) {
        $district[$row->district] = $row->district;
      }
    }
    return $district;
  }

  /**
   * Builds a filesystem-safe directory name from lab/proposer/university.
   */
  public function _lm_dir_name($lab, $name, $university) {
    $lab_title = $this->lm_ucname($lab);
    $proposer_name = $this->lm_ucname($name);
    $university_name = $this->lm_ucname($university);
    $dir_name = $lab_title . ' by ' . $proposer_name . ' ' . $university_name;
    return str_replace('__', '_', str_replace(' ', '_', $dir_name));
  }

  /**
   * Capitalises a name string, handling hyphens and apostrophes.
   */
  public function lm_ucname($string) {
    $string = ucwords(strtolower($string));
    foreach (['-', "'"] as $delimiter) {
      if (strpos($string, $delimiter) !== FALSE) {
        $string = implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
      }
    }
    return $string;
  }

  /**
   * Returns the absolute filesystem path for lab migration uploads.
   */
  public function lab_migration_path() {
    return $_SERVER['DOCUMENT_ROOT'] . base_path() . 'DWSIM_uploads/lab_migration_uploads/';
  }

  /**
   * Returns labs with solution_display=1 for bulk management selects.
   */
  public function _bulk_list_of_labs() {
    $lab_titles = ['0' => 'Please select...'];
    $result = $this->database->select('lab_migration_proposal', 'p')
      ->fields('p')
      ->condition('solution_display', 1)
      ->orderBy('lab_title', 'ASC')
      ->execute();
    foreach ($result as $row) {
      $lab_titles[$row->id] = $row->lab_title . ' (Proposed by ' . $row->name . ')';
    }
    return $lab_titles;
  }

  /**
   * Returns available bulk lab actions for the bulk management form.
   */
  public function _bulk_list_lab_actions() {
    return [
      0 => 'Please select...',
      1 => 'Approve Entire Lab',
      2 => 'Pending Review Entire Lab',
      3 => 'Dis-Approve Entire Lab (This will delete all the solutions in the lab)',
      4 => 'Delete Entire Lab Including Proposal',
    ];
  }

  /**
   * Returns available bulk experiment actions.
   */
  public function _bulk_list_experiment_actions() {
    return [
      0 => 'Please select...',
      1 => 'Approve Entire Experiment',
      2 => 'Pending Review Entire Experiment',
      3 => 'Dis-Approve Entire Experiment (This will delete all the solutions in the experiment)',
    ];
  }

  /**
   * Returns available bulk solution actions.
   */
  public function _bulk_list_solution_actions() {
    return [
      0 => 'Please select...',
      1 => 'Approve Entire Solution',
      2 => 'Pending Review Entire Solution',
      3 => 'Dis-approve Solution (This will delete the solution)',
    ];
  }

  /**
   * Copies LaTeX script files to the uploads directory.
   */
  public function _latex_copy_script_file() {
    $module_path = $this->moduleHandler->getModule('lab_migration')->getPath();
    $lab_migration_path = $this->lab_migration_path();
    exec("cp {$module_path}/latex/* {$lab_migration_path}latex");
    exec("chmod u+x {$lab_migration_path}latex/*.sh");
  }

  /**
   * Returns a render array of pending solution proposals.
   *
   * Returns an empty array and sets a status message when none exist.
   */
  public function lab_migration_solution_proposal_pending() {
    $rows = [];
    $result = $this->database->select('lab_migration_proposal', 'p')
      ->fields('p')
      ->condition('solution_provider_uid', 0, '!=')
      ->condition('solution_status', 1)
      ->orderBy('id', 'DESC')
      ->execute();

    foreach ($result as $data) {
      $user_link = Link::fromTextAndUrl(
        $data->name,
        Url::fromRoute('entity.user.canonical', ['user' => $data->uid])
      )->toString();

      $approve_link = Link::fromTextAndUrl(
        'Approve',
        Url::fromRoute('lab_migration.solution_proposal_approval_form')
      )->toString();

      $rows[] = [$user_link, $data->lab_title, $approve_link];
    }

    if (empty($rows)) {
      $this->messenger->addStatus(t('There are no pending solution proposals.'));
      return [];
    }

    return [
      '#type'   => 'table',
      '#header' => ['Proposer Name', 'Title of the Lab', 'Action'],
      '#rows'   => $rows,
    ];
  }

  /**
   * Loads the approved solution proposal for the current user.
   *
   * Returns proposal object on success, FALSE otherwise.
   */
  public function lab_migration_get_proposal() {
    $uid = $this->currentUser->id();

    $proposal_data = $this->database->select('lab_migration_proposal', 'p')
      ->fields('p')
      ->condition('solution_provider_uid', $uid)
      ->condition('solution_status', 2)
      ->orderBy('id', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$proposal_data) {
      return FALSE;
    }

    switch ($proposal_data->approval_status) {
      case 0:
        $this->messenger->addStatus(t('Proposal is awaiting approval.'));
        return FALSE;

      case 1:
        return $proposal_data;

      case 2:
        $this->messenger->addError(t('Proposal has been dis-approved.'));
        return FALSE;

      case 3:
        $this->messenger->addStatus(t('Proposal has been marked as completed.'));
        return FALSE;

      default:
        $this->messenger->addError(t('Invalid proposal state. Please contact site administrator.'));
        return FALSE;
    }
  }

  /**
   * Deletes all experiments and solutions belonging to a lab proposal.
   *
   * @param int $lab_id
   *   The proposal ID.
   *
   * @return bool
   *   TRUE if all deletions succeeded, FALSE otherwise.
   */
  public function lab_migration_delete_lab($lab_id) {
    $proposal_data = $this->database->select('lab_migration_proposal', 'p')
      ->fields('p')
      ->condition('id', $lab_id)
      ->execute()
      ->fetchObject();

    if (!$proposal_data) {
      $this->messenger->addError(t('Invalid Lab.'));
      return FALSE;
    }

    $status = TRUE;
    $experiments = $this->database->select('lab_migration_experiment', 'e')
      ->fields('e')
      ->condition('proposal_id', $proposal_data->id)
      ->execute();

    foreach ($experiments as $experiment) {
      if (!$this->lab_migration_delete_experiment($experiment->id)) {
        $status = FALSE;
      }
    }

    return $status;
  }

  /**
   * Deletes all solutions for an experiment and removes its directory.
   *
   * @param int $experiment_id
   *
   * @return bool
   */
  public function lab_migration_delete_experiment($experiment_id) {
    $experiment_data = $this->database->select('lab_migration_experiment', 'e')
      ->fields('e')
      ->condition('id', $experiment_id)
      ->execute()
      ->fetchObject();

    if (!$experiment_data) {
      $this->messenger->addError(t('Invalid experiment.'));
      return FALSE;
    }

    $proposal_data = $this->database->select('lab_migration_proposal', 'p')
      ->fields('p')
      ->condition('id', $experiment_data->proposal_id)
      ->execute()
      ->fetchObject();

    $status = TRUE;
    $delete_exp_folder = FALSE;

    $solutions = $this->database->select('lab_migration_solution', 's')
      ->fields('s')
      ->condition('experiment_id', $experiment_id)
      ->execute();

    foreach ($solutions as $solution) {
      $delete_exp_folder = TRUE;
      if (!$this->lab_migration_delete_solution($solution->id)) {
        $status = FALSE;
      }
    }

    if (!$delete_exp_folder) {
      return TRUE;
    }

    if ($status && $proposal_data) {
      $root_path = $this->lab_migration_path();
      $dir_path = $root_path . $proposal_data->directory_name . '/EXP' . $experiment_data->number;
      if (is_dir($dir_path)) {
        if (!rmdir($dir_path)) {
          $this->messenger->addError(t('Error deleting experiment folder @folder', ['@folder' => $dir_path]));
          return FALSE;
        }
        return TRUE;
      }
      else {
        $this->messenger->addError(t('Cannot delete experiment folder. @folder does not exist.', ['@folder' => $dir_path]));
        return FALSE;
      }
    }

    return FALSE;
  }

  /**
   * Deletes a solution's files from disk and removes DB records.
   *
   * Sends an admin error email via MailService if file deletion fails.
   *
   * @param int $solution_id
   *
   * @return bool
   */
  public function lab_migration_delete_solution($solution_id) {
    $root_path = $this->lab_migration_path();
    $status = TRUE;

    $solution_data = $this->database->select('lab_migration_solution', 's')
      ->fields('s')
      ->condition('id', $solution_id)
      ->execute()
      ->fetchObject();

    if (!$solution_data) {
      $this->messenger->addError(t('Invalid solution.'));
      return FALSE;
    }

    $experiment_data = $this->database->select('lab_migration_experiment', 'e')
      ->fields('e')
      ->condition('id', $solution_data->experiment_id)
      ->execute()
      ->fetchObject();

    if (!$experiment_data) {
      $this->messenger->addError(t('Invalid experiment.'));
      return FALSE;
    }

    $proposal_data = $this->database->select('lab_migration_proposal', 'p')
      ->fields('p')
      ->condition('id', $experiment_data->proposal_id)
      ->execute()
      ->fetchObject();

    // Delete associated solution files from disk and DB.
    $solution_files = $this->database->select('lab_migration_solution_files', 'sf')
      ->fields('sf')
      ->condition('solution_id', $solution_id)
      ->execute();

    foreach ($solution_files as $file) {
      $dir_path = $root_path . $proposal_data->directory_name . '/' . $file->filepath;
      if (!file_exists($dir_path)) {
        $status = FALSE;
        $this->messenger->addError(t('Error deleting @file. File does not exist.', ['@file' => $dir_path]));
        continue;
      }

      if (!unlink($dir_path)) {
        $status = FALSE;
        $this->messenger->addError(t('Error deleting @file.', ['@file' => $dir_path]));

        // Notify admins via MailService.
        $email_to = \Drupal::config('lab_migration.settings')->get('lab_migration_emails');
        if (!empty($email_to)) {
          $subject = '[ERROR] Error deleting solution file';
          $body = 'Error deleting solution file. Solution ID: ' . $solution_id . ', File: ' . $dir_path;
          $this->mailService->sendNotification('lab_migration', 'standard', $email_to, $subject, $body);
        }
      }
      else {
        $this->database->delete('lab_migration_solution_files')
          ->condition('id', $file->id)
          ->execute();
      }
    }

    if (!$status) {
      return FALSE;
    }

    // Remove the solution code directory if it exists.
    $dir_path = $root_path . $proposal_data->directory_name
      . '/EXP' . $experiment_data->number
      . '/CODE' . $solution_data->code_number;

    if (!is_dir($dir_path)) {
      $this->messenger->addError(t('Cannot delete solution folder. @folder does not exist.', ['@folder' => $dir_path]));
      return FALSE;
    }

    // Remove solution dependency and solution records from DB.
    $this->database->delete('lab_migration_solution_dependency')
      ->condition('solution_id', $solution_id)
      ->execute();

    $this->database->delete('lab_migration_solution')
      ->condition('id', $solution_id)
      ->execute();

    return TRUE;
  }

  /**
   * Renames a lab directory on disk when the proposal directory name changes.
   *
   * @param int $proposal_id
   * @param string $dir_name
   *   The new directory name.
   *
   * @return bool
   */
  public function LM_RenameDir($proposal_id, $dir_name) {
    $result = $this->database->select('lab_migration_proposal', 'p')
      ->fields('p', ['directory_name'])
      ->condition('id', $proposal_id)
      ->execute()
      ->fetchObject();

    if (!$result) {
      $this->messenger->addError(t('Proposal not found for directory rename.'));
      return FALSE;
    }

    $old_path = $this->lab_migration_path() . $result->directory_name;
    $new_path = $this->lab_migration_path() . $dir_name;

    if (!rename($old_path, $new_path)) {
      $this->messenger->addError(t('Unable to rename folder.'));
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Creates a README.txt file inside a lab's upload directory.
   *
   * @param int $proposal_id
   *
   * @return string
   *   The README file contents.
   */
  public function CreateReadmeFileLabMigration($proposal_id) {
    $proposal_data = $this->database->select('lab_migration_proposal', 'p')
      ->fields('p')
      ->condition('id', $proposal_id)
      ->execute()
      ->fetchObject();

    if (!$proposal_data) {
      return '';
    }

    $root_path = $this->lab_migration_path();
    $readme_path = $root_path . $proposal_data->directory_name . '/README.txt';
    $readme_file = fopen($readme_path, 'w');
    if (!$readme_file) {
      $this->messenger->addError(t('Unable to open README file for writing.'));
      return '';
    }

    $txt  = "About the lab\n\n";
    $txt .= 'Title Of The Lab: ' . $proposal_data->lab_title . "\n";
    $txt .= 'Proposer Name: ' . $proposal_data->name_title . ' ' . $proposal_data->name . "\n";
    $txt .= 'Department: ' . $proposal_data->department . "\n";
    $txt .= 'University: ' . $proposal_data->university . "\n";
    $txt .= 'Category: ' . $proposal_data->department . "\n\n";
    $txt .= "\nSolution provider\n\n";
    $txt .= 'Solution Provider Name: ' . $proposal_data->solution_provider_name_title . ' ' . $proposal_data->solution_provider_name . "\n";
    $txt .= 'Solution Provider University: ' . $proposal_data->solution_provider_university . "\n\n";
    $txt .= "Lab Migration Project By FOSSEE, IIT Bombay\n";

    fwrite($readme_file, $txt);
    fclose($readme_file);

    return $txt;
  }

  /**
   * Generates LaTeX data files for a lab's solutions.
   *
   * @param int $lab_id
   * @param bool $full_lab
   *   If TRUE, includes all solutions; otherwise only approved ones.
   */
  public function _latex_generate_files($lab_id, $full_lab = FALSE) {
    $root_path = $this->lab_migration_path();
    $dir_path  = $root_path . 'latex/';

    $proposal_data = $this->database->select('lab_migration_proposal', 'p')
      ->fields('p')
      ->condition('id', $lab_id)
      ->execute()
      ->fetchObject();

    if (!$proposal_data) {
      $this->messenger->addError(t('Invalid lab specified.'));
      return;
    }

    if ($proposal_data->approval_status == 0) {
      $this->messenger->addError(t('Lab proposal is still in pending review.'));
      return;
    }

    $category_map = [
      0  => 'Not Selected',
      1  => 'Fluid Mechanics',
      2  => 'Control Theory & Control Systems',
      3  => 'Chemical Engineering',
      4  => 'Thermodynamics',
      5  => 'Mechanical Engineering',
      6  => 'Signal Processing',
      7  => 'Digital Communications',
      8  => 'Electrical Technology',
      9  => 'Mathematics & Pure Science',
      10 => 'Analog Electronics',
      11 => 'Digital Electronics',
      12 => 'Computer Programming',
      13 => 'Others',
    ];
    $category_data = $category_map[$proposal_data->category] ?? 'Unknown';

    $sep = '#';
    $eol = "\n";

    $lab_filedata = implode($sep, [
      $proposal_data->lab_title,
      $proposal_data->name_title,
      $proposal_data->name,
      $proposal_data->department,
      $proposal_data->university,
      $category_data,
    ]) . $eol;

    // If PDF already exists, stream it directly.
    $pdf_path = $dir_path . 'lab_' . $proposal_data->id . '.pdf';
    if (file_exists($pdf_path)) {
      header('Content-Type: application/pdf');
      header('Content-Disposition: attachment; filename="' . $proposal_data->lab_title . '.pdf"');
      header('Content-Length: ' . filesize($pdf_path));
      readfile($pdf_path);
      return;
    }

    $solution_provider_filedata = implode($sep, [
      $proposal_data->solution_provider_name_title,
      $proposal_data->solution_provider_name,
      $proposal_data->solution_provider_department,
      $proposal_data->solution_provider_university,
    ]) . $eol;

    $latex_filedata     = '';
    $latex_dep_filedata = '';
    $dependency_list    = [];

    $experiment_query = $this->database->select('lab_migration_experiment', 'e')
      ->fields('e')
      ->condition('proposal_id', $proposal_data->id)
      ->orderBy('number', 'DESC');
    $experiments = $experiment_query->execute();

    foreach ($experiments as $experiment_data) {
      $solution_query = $this->database->select('lab_migration_solution', 's')
        ->fields('s')
        ->condition('experiment_id', $experiment_data->id)
        ->orderBy('code_number', 'DESC');

      if (!$full_lab) {
        $solution_query->condition('approval_status', 1);
      }

      $solutions = $solution_query->execute();

      foreach ($solutions as $solution_data) {
        // Gather solution files.
        $solution_files = $this->database->select('lab_migration_solution_files', 'sf')
          ->fields('sf')
          ->condition('solution_id', $solution_data->id)
          ->execute();

        foreach ($solution_files as $sf) {
          $latex_filedata .= implode($sep, [
            $experiment_data->number,
            $experiment_data->title,
            $solution_data->code_number,
            $solution_data->caption,
            $sf->filename,
            $sf->filepath,
            $sf->filetype,
            '',
            $sf->id,
          ]) . $eol;
        }

        // Gather dependency files.
        $dep_refs = $this->database->select('lab_migration_solution_dependency', 'sd')
          ->fields('sd')
          ->condition('solution_id', $solution_data->id)
          ->execute();

        foreach ($dep_refs as $dep_ref) {
          $dep = $this->database->select('lab_migration_dependency_files', 'df')
            ->fields('df')
            ->condition('id', $dep_ref->dependency_id)
            ->range(0, 1)
            ->execute()
            ->fetchObject();

          if ($dep && substr($dep->filename, -3) !== 'wav') {
            $latex_filedata .= implode($sep, [
              $experiment_data->number,
              $experiment_data->title,
              $solution_data->code_number,
              $solution_data->caption,
              $dep->filename,
              $dep->filepath,
              'D',
              $dep->caption,
              $dep->id,
            ]) . $eol;
            $dependency_list[$dep->id] = 'D';
          }
        }
      }
    }

    // Build dependency file listing.
    foreach ($dependency_list as $dep_id => $type) {
      $dep = $this->database->select('lab_migration_dependency_files', 'df')
        ->fields('df')
        ->condition('id', $dep_id)
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      if ($dep) {
        $latex_dep_filedata .= implode($sep, [
          $dep->filename,
          $dep->filepath,
          $dep->caption,
          $dep->id,
        ]) . $eol;
      }
    }
  }

}