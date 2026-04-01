<?php

namespace Drupal\dwsim_flowsheet\Services;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Database\Database;

class DwsimFlowsheetGlobalFunction{

function dwsim_flowsheet_ajax()
{
	$query_type = arg(2);
	if ($query_type == 'chapter_title')
	{
		$chapter_number = arg(3);
		$preference_id = arg(4);
		//$chapter_q = db_query("SELECT * FROM {dwsim_flowsheet_chapter} WHERE number = %d AND preference_id = %d LIMIT 1", $chapter_number, $preference_id);
		$query = \Drupal::database()->select('dwsim_flowsheet_chapter');
		$query->fields('dwsim_flowsheet_chapter');
		$query->condition('number', $chapter_number);
		$query->condition('preference_id', $preference_id);
		$query->range(0, 1);
		$chapter_q = $query->execute();
		if ($chapter_data = $chapter_q->fetchObject())
		{
			echo $chapter_data->name;
			return;
		} //$chapter_data = $chapter_q->fetchObject()
	} //$query_type == 'chapter_title'
	else if ($query_type == 'example_exists')
	{
		$chapter_number = arg(3);
		$preference_id = arg(4);
		$example_number = arg(5);
		$chapter_id = 0;
		$query = \Drupal::database()->select('dwsim_flowsheet_chapter');
		$query->fields('dwsim_flowsheet_chapter');
		$query->condition('number', $chapter_number);
		$query->condition('preference_id', $preference_id);
		$query->range(0, 1);
		$chapter_q = $query->execute();
		if (!$chapter_data = $chapter_q->fetchObject())
		{
			echo '';
			return;
		} //!$chapter_data = $chapter_q->fetchObject()
		else
		{
			$chapter_id = $chapter_data->id;
		}
		$query = \Drupal::database()->select('dwsim_flowsheet_example');
		$query->fields('dwsim_flowsheet_example');
		$query->condition('chapter_id', $chapter_id);
		$query->condition('number', $example_number);
		$query->range(0, 1);
		$example_q = $query->execute();
		if ($example_data = $example_q->fetchObject())
		{
			if ($example_data->approval_status == 1)
				echo 'Warning! Solution already approved. You cannot upload the same solution again.';
			else
				echo 'Warning! Solution already uploaded. Delete the solution and reupload it.';
			return;
		} //$example_data = $example_q->fetchObject()
	} //$query_type == 'example_exists'
	echo '';
}
/*************************** VALIDATION FUNCTIONS *****************************/
function dwsim_flowsheet_check_valid_filename($file_name)
{
	if (!preg_match('/^[0-9a-zA-Z\.\_]+$/', $file_name))
		return FALSE;
	else if (substr_count($file_name, ".") > 1)
		return FALSE;
	else
		return TRUE;
}
function dwsim_flowsheet_check_name($name = '')
{
	if (!preg_match('/^[0-9a-zA-Z\ ]+$/', $name))
		return FALSE;
	else
		return TRUE;
}
function dwsim_flowsheet_check_code_number($number = '')
{
	if (!preg_match('/^[0-9]+$/', $number))
		return FALSE;
	else
		return TRUE;
}
function dwsim_flowsheet_path()
{
	return $_SERVER['DOCUMENT_ROOT'] . base_path() . 'dwsim_uploads/dwsim_flowsheet_uploads/';
}
/************************* USER VERIFICATION FUNCTIONS ************************/
function dwsim_flowsheet_get_proposal()
{
	$user = \Drupal::currentUser();
	$query = \Drupal::database()->select('dwsim_flowsheet_proposal');
	$query->fields('dwsim_flowsheet_proposal');
	$query->condition('uid', $user->id());
	$query->condition('approval_status', 1);
	$query->orderBy('id', 'DESC');
	$query->range(0, 1);
	$proposal_data = $query->execute()->fetchObject();
	if ($proposal_data)
	{
		return $proposal_data;
	}

	$status_query = \Drupal::database()->select('dwsim_flowsheet_proposal');
	$status_query->fields('dwsim_flowsheet_proposal');
	$status_query->condition('uid', $user->id());
	$status_query->orderBy('id', 'DESC');
	$status_query->range(0, 1);
	$latest_proposal = $status_query->execute()->fetchObject();
	if (!$latest_proposal)
	{
		\Drupal::messenger()->addError("You do not have any approved DWSIM Flowsheet proposal. Please propose the flowsheet proposal");
		return FALSE;
	}

	switch ($latest_proposal->approval_status)
	{
		case 0:
			\Drupal::messenger()->addStatus(t('Proposal is awaiting approval.'));
			return FALSE;
		case 2:
			\Drupal::messenger()->addError(t('Proposal has been dis-approved.'));
			return FALSE;
		case 3:
			\Drupal::messenger()->addStatus(t('Proposal has been marked as completed.'));
			return FALSE;
		default:
			\Drupal::messenger()->addError(t('You do not have any approved DWSIM Flowsheet proposal. Please propose the flowsheet proposal.'));
			return FALSE;
	}
}
/*************************************************************************/
/***** Function To convert only first charater of string in uppercase ****/
/*************************************************************************/
function dwsim_flowsheet_ucname($string)
{
	$string = ucwords(strtolower($string));
	foreach (array(
		'-',
		'\''
	) as $delimiter)
	{
		if (strpos($string, $delimiter) !== false)
		{
			$string = implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
		} //strpos($string, $delimiter) !== false
	} //array( '-', '\'' ) as $delimiter
	return $string;
}
function _df_sentence_case($string)
{
	$string = ucwords(strtolower($string));
	foreach (array(
		'-',
		'\''
	) as $delimiter)
	{
		if (strpos($string, $delimiter) !== false)
		{
			$string = implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
		} //strpos($string, $delimiter) !== false
	} //array( '-', '\'' ) as $delimiter
	return $string;
}
function _df_list_of_dwsim_compound()
{
	$dwsim_compound = array();
	$query = \Drupal::database()->select('dwsim_flowsheet_compounds_from_dwsim');
	$query->fields('dwsim_flowsheet_compounds_from_dwsim');
	$query->orderBy('id', 'ASC');
	$dwsim_compound_list = $query->execute();
	while ($dwsim_compound_list_data = $dwsim_compound_list->fetchObject())
	{
		$dwsim_compound[$dwsim_compound_list_data->compound] = $dwsim_compound_list_data->compound;
	} //$dwsim_compound_list_data = $dwsim_compound_list->fetchObject()
	return $dwsim_compound;
}
function _df_list_of_unit_operations()
{
	$dwsim_unit_operations = array();
	$query = \Drupal::database()->select('dwsim_flowsheet_unit_operations');
	$query->fields('dwsim_flowsheet_unit_operations');
	$query->orderBy('id', 'ASC');
	$dwsim_unit_operations_list = $query->execute();
	while ($dwsim_unit_operations_list_data = $dwsim_unit_operations_list->fetchObject())
	{
		$dwsim_unit_operations[$dwsim_unit_operations_list_data->unit_operations] = $dwsim_unit_operations_list_data->unit_operations;
	} //$dwsim_unit_operations_list_data = $dwsim_unit_operations_list->fetchObject()
	return $dwsim_unit_operations;
}
function _df_list_of_thermodynamic_packages()
{
	$dwsim_thermodynamic_packages = array();
	$query = \Drupal::database()->select('dwsim_flowsheet_thermodynamic_packages');
	$query->fields('dwsim_flowsheet_thermodynamic_packages');
	$query->orderBy('thermodynamic_packages', 'ASC');
	$dwsim_thermodynamic_packages_list = $query->execute();
	while ($dwsim_thermodynamic_packages_list_data = $dwsim_thermodynamic_packages_list->fetchObject())
	{
		$dwsim_thermodynamic_packages[$dwsim_thermodynamic_packages_list_data->thermodynamic_packages] = $dwsim_thermodynamic_packages_list_data->thermodynamic_packages;
	} //$dwsim_thermodynamic_packages_list_data = $dwsim_thermodynamic_packages_list->fetchObject()
	return $dwsim_thermodynamic_packages;
}
function _df_list_of_logical_block()
{
	$dwsim_logical_block = array();
	$query = \Drupal::database()->select('dwsim_flowsheet_logical_block');
	$query->fields('dwsim_flowsheet_logical_block');
	$query->orderBy('id', 'ASC');
	$dwsim_logical_block_list = $query->execute();
	while ($dwsim_logical_block_list_data = $dwsim_logical_block_list->fetchObject())
	{
		$dwsim_logical_block[$dwsim_logical_block_list_data->logical_block] = $dwsim_logical_block_list_data->logical_block;
	} //$dwsim_logical_block_list_data = $dwsim_logical_block_list->fetchObject()
	return $dwsim_logical_block;
}
function _df_list_of_states()
{
	$states = array(
		0 => '-Select-'
	);
	$query = \Drupal::database()->select('list_states_of_india');
	$query->fields('list_states_of_india');
	//$query->orderBy('', '');
	$states_list = $query->execute();
	while ($states_list_data = $states_list->fetchObject())
	{
		$states[$states_list_data->state] = $states_list_data->state;
	} //$states_list_data = $states_list->fetchObject()
	return $states;
}
function _df_list_of_cities()
{
	$city = array(
		0 => '-Select-'
	);
	$query = \Drupal::database()->select('list_cities_of_india');
	$query->fields('list_cities_of_india');
	$query->orderBy('city', 'ASC');
	$city_list = $query->execute();
	while ($city_list_data = $city_list->fetchObject())
	{
		$city[$city_list_data->city] = $city_list_data->city;
	} //$city_list_data = $city_list->fetchObject()
	return $city;
}
function _df_list_of_pincodes()
{
	$pincode = array(
		0 => '-Select-'
	);
	$query = \Drupal::database()->select('list_of_all_india_pincode');
	$query->fields('list_of_all_india_pincode');
	$query->orderBy('pincode', 'ASC');
	$pincode_list = $query->execute();
	while ($pincode_list_data = $pincode_list->fetchObject())
	{
		$pincode[$pincode_list_data->pincode] = $pincode_list_data->pincode;
	} //$pincode_list_data = $pincode_list->fetchObject()
	return $pincode;
}
function _df_list_of_departments()
{
	$department = array();
	$query = \Drupal::database()->select('list_of_departments');
	$query->fields('list_of_departments');
	$query->orderBy('id', 'DESC');
	$department_list = $query->execute();
	while ($department_list_data = $department_list->fetchObject())
	{
		$department[$department_list_data->department] = $department_list_data->department;
	} //$department_list_data = $department_list->fetchObject()
	return $department;
}
function _df_list_of_software_version()
{
	$software_version = array();
	$query = \Drupal::database()->select('dwsim_software_version');
	$query->fields('dwsim_software_version');
	$query->orderBy('id', 'ASC');
	$software_version_list = $query->execute();
	while ($software_version_list_data = $software_version_list->fetchObject())
	{
		$software_version[$software_version_list_data->dwsim_version] = $software_version_list_data->dwsim_version;
	} //$software_version_list_data = $software_version_list->fetchObject()
	return $software_version;
}
function _df_dir_name($project, $proposar_name)
{

	$project_title = ucname($project);
	$proposar_name = ucname($proposar_name);
	$dir_name = $project_title . ' By ' . $proposar_name;
	$directory_name = str_replace("__", "_", str_replace(" ", "_", str_replace("/", " ", $dir_name)));
	return $directory_name;
}
function dwsim_flowsheet_document_path()
{
	return $_SERVER['DOCUMENT_ROOT'] . base_path() . 'dwsim_uploads/dwsim_flowsheet_uploads/';
}
function DF_RenameDir($proposal_id, $dir_name)
{
	$proposal_id = $proposal_id;
	$dir_name = $dir_name;
	$query = \Drupal::database()->query("SELECT directory_name,id FROM dwsim_flowsheet_proposal WHERE id = :proposal_id", array(
		':proposal_id' => $proposal_id
	));
	$result = $query->fetchObject();
	if ($result != NULL)
	{
		$files = scandir(dwsim_flowsheet_path());
		$files_id_dir = dwsim_flowsheet_path() . $result->id;
		//var_dump($files);die;
		$file_dir = dwsim_flowsheet_path() . $result->directory_name;
		if (is_dir($file_dir))
		{
			$new_directory_name = rename(dwsim_flowsheet_path() . $result->directory_name, dwsim_flowsheet_path() . $dir_name);
			return $new_directory_name;
		} //is_dir($file_dir)
		else if (is_dir($files_id_dir))
		{
			$new_directory_name = rename(dwsim_flowsheet_path() . $result->id, dwsim_flowsheet_path() . $dir_name);
			return $new_directory_name;
		} //is_dir($files_id_dir)
		else
		{
			\Drupal::messenger()->addMessage('Directory not available for rename.');
			return;
		}
	} //$result != NULL
	else
	{
		\Drupal::messenger()->addMessage('Project directory name not present in databse');
		return;
	}
	//var_dump($files);die;
	/* if ($files != NULL)
	{
	$new_directory_name = rename(dwsim_flowsheet_path() . $result->directory_name, dwsim_flowsheet_path() . $dir_name) or drupal_set_message("Unable to rename folder");
	}
	else
	{
	$new_directory_name = 'Can not rename the directory. Directory not present';
	}*/
	return;
}
function CreateReadmeFileDWSIMFlowsheetingProject($proposal_id)
{
	$result = \Drupal::database()->query("
                        SELECT * from dwsim_flowsheet_proposal WHERE id = :proposal_id", array(
		":proposal_id" => $proposal_id
	));
	$proposal_data = $result->fetchObject();
	$root_path = dwsim_flowsheet_path();
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
function rrmdir_project($prop_id)
{
	$proposal_id = $prop_id;
	$result = \Drupal::database()->query("
					SELECT * from dwsim_flowsheet_proposal WHERE id = :proposal_id", array(
		":proposal_id" => $proposal_id
	));
	$proposal_data = $result->fetchObject();
	$root_path = dwsim_flowsheet_document_path();
	$dir = $root_path . $proposal_data->directory_name;
	if ($proposal_data->id == $prop_id)
	{
		if (is_dir($dir))
		{
			$objects = scandir($dir);
			foreach ($objects as $object)
			{
				if ($object != "." && $object != "..")
				{
					if (filetype($dir . "/" . $object) == "dir")
					{
						rrmdir($dir . "/" . $object);
					} //filetype($dir . "/" . $object) == "dir"
					else
					{
						unlink($dir . "/" . $object);
					}
				} //$object != "." && $object != ".."
			} //$objects as $object
			reset($objects);
			rmdir($dir);
			$msg = \Drupal::messenger()->addMessage("Directory deleted successfully");
			return $msg;
		} //is_dir($dir)
		$msg = \Drupal::messenger()->addMessage("Directory not present");
		return $msg;
	} //$proposal_data->id == $prop_id
	else
	{
		$msg = \Drupal::messenger()->addMessage("Data not found");
		return $msg;
	}
}
function rrmdir($dir)
{
	if (is_dir($dir))
	{
		$objects = scandir($dir);
		foreach ($objects as $object)
		{
			if ($object != "." && $object != "..")
			{
				if (filetype($dir . "/" . $object) == "dir")
					rrmdir($dir . "/" . $object);
				else
					unlink($dir . "/" . $object);
			} //$object != "." && $object != ".."
		} //$objects as $object
		reset($objects);
		rmdir($dir);
	} //is_dir($dir)
}
function _dwsim_flowsheet_list_of_user_defined_compound($proposal_id)
{
	$data = "";
	//$query = db_select('dwsim_flowsheet_user_defined_compound');
	//$query->fields('dwsim_flowsheet_user_defined_compound');
	//$query->condition('proposal_id', $proposal_id, '=');
	//$query->orderBy('user_defined_compound', 'ASC');
	$user_defined_compound_list = \Drupal::database()->query("SELECT * FROM dwsim_flowsheet_user_defined_compound WHERE proposal_id = :proposal_id", array(":proposal_id" => $proposal_id));
	$headers = array(
		"List of user defined compounds used in process flowsheet",
		"CAS No."
	);
	if($user_defined_compound_list){
	$rows = array();
	while ($row = $user_defined_compound_list->fetchObject())
	{
		$item = array(
			"{$row->user_defined_compound}",
			"{$row->cas_no}"
		);
		array_push($rows, $item);
	} //$row = $user_defined_compound_list->fetchObject()
	
	// @FIXME
// theme() has been renamed to _theme() and should NEVER be called directly.
// Calling _theme() directly can alter the expected output and potentially
// introduce security issues (see https://www.drupal.org/node/2195739). You
// should use renderable arrays instead.
// 
// 
// @see https://www.drupal.org/node/2195739
// $data .= theme('table', array(
// 		'header' => $headers,
// 		'rows' => $rows
// 	));

	}else{
		$data .= "Not entered";
	}
	return $data;
}


}
