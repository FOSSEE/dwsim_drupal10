<?php

/**
 * @file
 * Contains \Drupal\custom_model\Form\CustomModelUploadAbstractCodeForm.
 */

namespace Drupal\custom_model\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\custom_model\Services\CustomModelGlobalfunction;

class CustomModelUploadAbstractCodeForm extends FormBase {

  protected $connection;
  protected $messenger;
  protected $currentUser;
  protected $routeMatch;
  protected $globalFunction;
  protected $configFactory;

  public function __construct(
    Connection $connection,
    MessengerInterface $messenger,
    AccountInterface $currentUser,
    RouteMatchInterface $routeMatch,
    CustomModelGlobalfunction $globalFunction,
    ConfigFactoryInterface $configFactory
  ) {
    $this->connection     = $connection;
    $this->messenger      = $messenger;
    $this->currentUser    = $currentUser;
    $this->routeMatch     = $routeMatch;
    $this->globalFunction = $globalFunction;
    $this->configFactory  = $configFactory;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('messenger'),
      $container->get('current_user'),
      $container->get('current_route_match'),
      $container->get('custom_model_global'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_model_upload_abstract_code_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = $this->currentUser;
    $form['#attributes'] = ['enctype' => "multipart/form-data"];
    $proposal_data = $this->globalFunction->custom_model_get_proposal();
    if (!$proposal_data) {
      $this->messenger->addError($this->t('Invalid proposal selected. Please try again.'));
      return new RedirectResponse('/custom-model/abstract-code/upload');
    }

    // $uid = $user->uid;
    // $query = \Drupal::database()->select('custom_model_proposal');
    // $query->fields('custom_model_proposal');
    // $query->condition('uid', $uid);
    // $query->condition('approval_status', '1');
    // $proposal_q = $query->execute();
    // // var_dump($proposal_q);die;
    // if ($proposal_q) {
    //   if ($proposal_data = $proposal_q->fetchObject()) {
    //     /* everything ok */
    //   } //$proposal_data = $proposal_q->fetchObject()
    //   else {
    //     \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
    //     // drupal_goto('custom-model/abstract-code');
    // return new RedirectResponse('/custom-model/abstract-code/upload');
    
    //     return;
    //   }
    // } //$proposal_q
    // else {
    //   \Drupal::messenger()->addMessage(t('Invalid proposal selected. Please try again.'), 'error');
    //   // drupal_goto('custom-model/abstract-code');
    // return new RedirectResponse('/custom-model/abstract-code/upload');
    //   return;
    // }
    $abstracts_q = $this->connection->select('custom_model_submitted_abstracts')
      ->fields('custom_model_submitted_abstracts')
      ->condition('proposal_id', $proposal_data->id)
      ->execute()
      ->fetchObject();
    if ($abstracts_q && $abstracts_q->is_submitted == 1) {
      $this->messenger->addError($this->t('You have already submitted your project files. For any query please write to us.'));
      return new RedirectResponse('/custom-model/abstract-code/upload');
    }
    $form['project_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->project_title,
      '#title' => t('Title of the Circuit Simulation Project'),
    ];
    $form['contributor_name'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->contributor_name,
      '#title' => t('Contributer Name'),
    ];
    $form['dwsim_version'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->version,
      '#title' => t('DWSIM version used'),
    ];
    // var_dump($proposal_data);die;
    $existing_uploaded_S_file = $this->globalFunction->default_value_for_uploaded_files('S', $proposal_data->id);
    if (!$existing_uploaded_S_file) {
      $existing_uploaded_S_file = new \stdClass();
      $existing_uploaded_S_file->filename = 'No file uploaded';
    }
    $form['upload_custom_model_simulation_file'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload the Custom Model as DWSIM Simulation File'),
      '#description' => $this->t('<span style="color:red;">Current File :</span> ' . $existing_uploaded_S_file->filename . '<br />Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . $this->t('<span style="color:red;">Allowed file extensions : ') . $this->configFactory->get('custom_model.settings')->get('custom_model_simulation_file', '') . '</span>',
    ];
    $existing_uploaded_P_file = $this->globalFunction->default_value_for_uploaded_files('P', $proposal_data->id);
    if (!$existing_uploaded_P_file) {
      $existing_uploaded_P_file = new \stdClass();
      $existing_uploaded_P_file->filename = 'No file uploaded';
    }
    $form['upload_custom_model_script_file'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload the scilab/ironpython script for the custom model'),
      '#description' => $this->t('<span style="color:red;">Current File :</span> ' . $existing_uploaded_P_file->filename . '<br />Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . $this->t('<span style="color:red;">Allowed file extensions : ') . $this->configFactory->get('custom_model.settings')->get('custom_model_script_file', '') . '</span>',
    ];
    $existing_uploaded_A_file = $this->globalFunction->default_value_for_uploaded_files('A', $proposal_data->id);
    if (!$existing_uploaded_A_file) {
      $existing_uploaded_A_file = new \stdClass();
      $existing_uploaded_A_file->filename = 'No file uploaded';
    }
    $form['upload_an_abstract'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload an abstract of the project.'),
      '#description' => $this->t('<span style="color:red;">Current File :</span> ' . $existing_uploaded_A_file->filename . '<br />Separate filenames with underscore. No spaces or any special characters allowed in filename.<br />' . $this->t('<span style="color:red;">Allowed file extensions : ') . $this->configFactory->get('custom_model.settings')->get('custom_model_abstract_upload_extensions', '') . '</span>'),
    ];

    $form['prop_id'] = [
      '#type' => 'hidden',
      '#value' => $proposal_data->uid,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'item',
      '#markup' => Link::fromTextAndUrl(
        $this->t('Cancel'),
        Url::fromUserInput('/custom-model/abstract-code/circuit simulation-project-list')
      )->toString(),
    ];
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if (isset($_FILES['files'])) {
      /* check if file is uploaded */
      $existing_uploaded_A_file = $this->globalFunction->default_value_for_uploaded_files('A', $form_state->getValue(['prop_id']));
      $existing_uploaded_S_file = $this->globalFunction->default_value_for_uploaded_files('S', $form_state->getValue(['prop_id']));
      $existing_uploaded_P_file = $this->globalFunction->default_value_for_uploaded_files('P', $form_state->getValue(['prop_id']));
      if (!$existing_uploaded_S_file) {
        if (!($_FILES['files']['name']['upload_custom_model_simulation_file'])) {
          $form_state->setErrorByName('upload_custom_model_simulation_file', t('Please upload the file.'));
        }
      } //!$existing_uploaded_S_file
      if (!$existing_uploaded_P_file) {
        if (!($_FILES['files']['name']['upload_custom_model_script_file'])) {
          $form_state->setErrorByName('upload_custom_model_script_file', t('Please upload the file.'));
        }
      } //!$existing_uploaded_S_file
      if (!$existing_uploaded_A_file) {
        if (!($_FILES['files']['name']['upload_an_abstract'])) {
          $form_state->setErrorByName('upload_an_abstract', t('Please upload the file.'));
        }
      } //!$existing_uploaded_A_file
		/* check for valid filename extensions */
      if ($_FILES['files']['name']['upload_custom_model_script_file'] || $_FILES['files']['name']['upload_an_abstract'] || $_FILES['files']['name']['upload_custom_model_simulation_file']) {
        foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
          if ($file_name) {
            /* checking file type */
            if (strstr($file_form_name, 'upload_custom_model_simulation_file')) {
              $file_type = 'S';
            }
            else {
              if (strstr($file_form_name, 'upload_custom_model_script_file')) {
                $file_type = 'P';
              }
              else {
                if (strstr($file_form_name, 'upload_an_abstract')) {
                  $file_type = 'A';
                }
                else {
                  $file_type = 'U';
                }
              }
            }
            $config = $this->config('custom_model.settings');
            $allowed_extensions_str = '';
            switch ($file_type) {
              case 'S':
                $allowed_extensions_str = $config->get('custom_model_simulation_file', '');
                break;
              case 'A':
                $allowed_extensions_str = $config->get('custom_model_abstract_upload_extensions', '');
                break;
              case 'P':
                $allowed_extensions_str = $config->get('custom_model_script_file', '');
                break;
            } //$file_type
            $allowed_extensions = explode(',', $allowed_extensions_str);
            $tmp_ext = explode('.', strtolower($_FILES['files']['name'][$file_form_name]));
            $temp_extension = end($tmp_ext);
            if (!in_array($temp_extension, $allowed_extensions)) {
              $form_state->setErrorByName($file_form_name, t('Only file with ' . $allowed_extensions_str . ' extensions can be uploaded.'));
            }
            if ($_FILES['files']['size'][$file_form_name] <= 0) {
              $form_state->setErrorByName($file_form_name, t('File size cannot be zero.'));
            }
            /* check if valid file name */
            if (!custom_model_check_valid_filename($_FILES['files']['name'][$file_form_name])) {
              $form_state->setErrorByName($file_form_name, t('Invalid file name specified. Only alphabets and numbers are allowed as a valid filename.'));
            }
          } //$file_name
        } //$_FILES['files']['name'] as $file_form_name => $file_name
      } //$_FILES['files']['name'] as $file_form_name => $file_name
    } //isset($_FILES['files'])
    // drupal_add_js('jQuery(document).ready(function () { alert("Hello!"); });', 'inline');
    // drupal_static_reset('drupal_add_js') ;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = $this->currentUser;
    $v = $form_state->getValues();
    $root_path = $this->globalFunction->custom_model_path();
    $proposal_data = $this->globalFunction->custom_model_get_proposal();
    if (!$proposal_data) {
      return;
    }
    $proposal_id = $proposal_data->id;
    $proposal_directory = $proposal_data->directory_name;
    /* create proposal folder if not present */
    //$dest_path = $proposal_directory . '/';
    $dest_path_project_files = $proposal_directory . '/project_files/';
    if (!is_dir($root_path . $dest_path_project_files)) {
      mkdir($root_path . $dest_path_project_files);
    }
    $proposal_id = $proposal_data->id;
    $query_s = "SELECT * FROM {custom_model_submitted_abstracts} WHERE proposal_id = :proposal_id";
    $args_s = [":proposal_id" => $proposal_id];
    $query_s_result = $this->connection->query($query_s, $args_s)->fetchObject();
    if (!$query_s_result) {
      $submitted_abstract_id = $this->connection->query($query, $args);
      $this->connection->query($query1, $args1);
      $this->messenger->addStatus($this->t('Abstract uploaded successfully.'));
    } //!$query_s_result
    else {
      $query = "UPDATE {custom_model_submitted_abstracts} SET 

	
	abstract_upload_date =:abstract_upload_date,
	is_submitted= :is_submitted 
	WHERE proposal_id = :proposal_id
	";
      $args = [
        ":abstract_upload_date" => time(),
        ":is_submitted" => 1,
        ":proposal_id" => $proposal_id,
      ];
      $submitted_abstract_id = $this->connection->query($query, $args);
      $this->connection->query($query1, $args1);
      $this->messenger->addStatus($this->t('Abstract updated successfully.'));
    }
    foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
      if ($file_name) {
        /* checking file type */
        if (strstr($file_form_name, 'upload_custom_model_simulation_file')) {
          $file_type = 'S';
        } //strstr($file_form_name, 'upload_custom_model_simulation_file')
        else {
          if (strstr($file_form_name, 'upload_custom_model_script_file')) {
            $file_type = 'P';
          }
          else {
            if (strstr($file_form_name, 'upload_an_abstract')) {
              $file_type = 'A';
            }
            else {
              $file_type = 'U';
            }
          }
        }
        switch ($file_type) {
          case 'S':
            if (file_exists($root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name])) {
              //unlink($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);
              $this->messenger->addError($this->t('File @filename already exists and has been overwritten.', [
                '@filename' => $_FILES['files']['name'][$file_form_name],
              ]));
            } //file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
					/* uploading file */
            else {
              if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name])) {
                /* for uploaded files making an entry in the database */
                $query_ab_f = "SELECT * FROM custom_model_submitted_abstracts_file WHERE proposal_id = :proposal_id AND filetype = 
				:filetype";
                $args_ab_f = [
                  ":proposal_id" => $proposal_id,
                  ":filetype" => $file_type,
                ];
                $query_ab_f_result = \Drupal::database()->query($query_ab_f, $args_ab_f)->fetchObject();
                if (!$query_ab_f_result) {
                  $query = "INSERT INTO {custom_model_submitted_abstracts_file} (submitted_abstract_id, proposal_id, uid, approvar_uid, filename, filepath, filemime, filesize, filetype, timestamp)
          VALUES (:submitted_abstract_id, :proposal_id, :uid, :approvar_uid, :filename, :filepath, :filemime, :filesize, :filetype, :timestamp)";
                  $args = [
                    ":submitted_abstract_id" => $submitted_abstract_id,
                    ":proposal_id" => $proposal_id,
                    ":uid" => $this->currentUser->id(),
                    ":approvar_uid" => 0,
                    ":filename" => $_FILES['files']['name'][$file_form_name],
                    ":filepath" => $_FILES['files']['name'][$file_form_name],
                    ":filemime" => mime_content_type($root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name]),
                    ":filesize" => $_FILES['files']['size'][$file_form_name],
                    ":filetype" => $file_type,
                    ":timestamp" => time(),
                  ];
                  $this->connection->query($query, $args);
                  $this->messenger->addStatus($file_name . ' uploaded successfully.');
                } //!$query_ab_f_result
                else {
                  unlink($root_path . $dest_path_project_files . $query_ab_f_result->filename);
                  $query = "UPDATE {custom_model_submitted_abstracts_file} SET filename = :filename, filepath=:filepath, filemime=:filemime, filesize=:filesize, timestamp=:timestamp WHERE proposal_id = :proposal_id AND filetype = :filetype";
                  $args = [
                    ":filename" => $_FILES['files']['name'][$file_form_name],
                    ":filepath" => $file_path . $_FILES['files']['name'][$file_form_name],
                    ":filemime" => mime_content_type($root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name]),
                    ":filesize" => $_FILES['files']['size'][$file_form_name],
                    ":timestamp" => time(),
                    ":proposal_id" => $proposal_id,
                    ":filetype" => $file_type,
                  ];
                  $this->connection->query($query, $args);
                  $this->messenger->addStatus($file_name . ' file updated successfully.');
                }
              } //move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
              else {
                $this->messenger->addError($this->t('Error uploading file: @path', ['@path' => $dest_path_project_files . $file_name]));
              }
            }
            break;
          case 'P':
            if (file_exists($root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name])) {
              //unlink($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);
              \Drupal::messenger()->addMessage(t("File !filename already exists hence overwirtten the exisitng file ", [
                '!filename' => $_FILES['files']['name'][$file_form_name]
                ]), 'error');
            } //file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
					/* uploading file */
            else {
              if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name])) {
                /* for uploaded files making an entry in the database */
                $query_ab_f = "SELECT * FROM custom_model_submitted_abstracts_file WHERE proposal_id = :proposal_id AND filetype = 
				:filetype";
                $args_ab_f = [
                  ":proposal_id" => $proposal_id,
                  ":filetype" => $file_type,
                ];
                $query_ab_f_result = \Drupal::database()->query($query_ab_f, $args_ab_f)->fetchObject();
                if (!$query_ab_f_result) {
                  $query = "INSERT INTO {custom_model_submitted_abstracts_file} (submitted_abstract_id, proposal_id, uid, approvar_uid, filename, filepath, filemime, filesize, filetype, timestamp)
          VALUES (:submitted_abstract_id, :proposal_id, :uid, :approvar_uid, :filename, :filepath, :filemime, :filesize, :filetype, :timestamp)";
                  $args = [
                    ":submitted_abstract_id" => $submitted_abstract_id,
                    ":proposal_id" => $proposal_id,
                ":uid" => $this->currentUser->id(),
                    ":approvar_uid" => 0,
                    ":filename" => $_FILES['files']['name'][$file_form_name],
                    ":filepath" => $_FILES['files']['name'][$file_form_name],
                    ":filemime" => mime_content_type($root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name]),
                    ":filesize" => $_FILES['files']['size'][$file_form_name],
                    ":filetype" => $file_type,
                    ":timestamp" => time(),
                  ];
                  $this->connection->query($query, $args);
                  $this->messenger->addStatus($file_name . ' uploaded successfully.');
                } //!$query_ab_f_result
                else {
                  unlink($root_path . $dest_path_project_files . $query_ab_f_result->filename);
                  $query = "UPDATE {custom_model_submitted_abstracts_file} SET filename = :filename, filepath=:filepath, filemime=:filemime, filesize=:filesize, timestamp=:timestamp WHERE proposal_id = :proposal_id AND filetype = :filetype";
                  $args = [
                    ":filename" => $_FILES['files']['name'][$file_form_name],
                    ":filepath" => $file_path . $_FILES['files']['name'][$file_form_name],
                    ":filemime" => mime_content_type($root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name]),
                    ":filesize" => $_FILES['files']['size'][$file_form_name],
                    ":timestamp" => time(),
                    ":proposal_id" => $proposal_id,
                    ":filetype" => $file_type,
                  ];
                  $this->connection->query($query, $args);
                  $this->messenger->addStatus($file_name . ' file updated successfully.');
                }
              } //move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
              else {
                $this->messenger->addError($this->t('Error uploading file: @path', ['@path' => $dest_path_project_files . $file_name]));
              }
            }
            break;
          case 'A':
            if (file_exists($root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name])) {
              //unlink($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);
              \Drupal::messenger()->addMessage(t("File !filename already exists hence overwirtten the exisitng file ", [
                '!filename' => $_FILES['files']['name'][$file_form_name]
                ]), 'error');
            } //file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
					/* uploading file */
            else {
              if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name])) {
                /* for uploaded files making an entry in the database */
                $query_ab_f = "SELECT * FROM custom_model_submitted_abstracts_file WHERE proposal_id = :proposal_id AND filetype = 
				:filetype";
                $args_ab_f = [
                  ":proposal_id" => $proposal_id,
                  ":filetype" => $file_type,
                ];
                $query_ab_f_result = \Drupal::database()->query($query_ab_f, $args_ab_f)->fetchObject();
                if (!$query_ab_f_result) {
                  $query = "INSERT INTO {custom_model_submitted_abstracts_file} (submitted_abstract_id, proposal_id, uid, approvar_uid, filename, filepath, filemime, filesize, filetype, timestamp)
          VALUES (:submitted_abstract_id, :proposal_id, :uid, :approvar_uid, :filename, :filepath, :filemime, :filesize, :filetype, :timestamp)";
                  $args = [
                    ":submitted_abstract_id" => $submitted_abstract_id,
                    ":proposal_id" => $proposal_id,
                ":uid" => $this->currentUser->id(),
                    ":approvar_uid" => 0,
                    ":filename" => $_FILES['files']['name'][$file_form_name],
                    ":filepath" => $_FILES['files']['name'][$file_form_name],
                    ":filemime" => mime_content_type($root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name]),
                    ":filesize" => $_FILES['files']['size'][$file_form_name],
                    ":filetype" => $file_type,
                    ":timestamp" => time(),
                  ];
                  $this->connection->query($query, $args);
                  $this->messenger->addStatus($file_name . ' uploaded successfully.');
                } //!$query_ab_f_result
                else {
                  unlink($root_path . $dest_path_project_files . $query_ab_f_result->filename);
                  $query = "UPDATE {custom_model_submitted_abstracts_file} SET filename = :filename, filepath=:filepath, filemime=:filemime, filesize=:filesize, timestamp=:timestamp WHERE proposal_id = :proposal_id AND filetype = :filetype";
                  $args = [
                    ":filename" => $_FILES['files']['name'][$file_form_name],
                    ":filepath" => $file_path . $_FILES['files']['name'][$file_form_name],
                    ":filemime" => mime_content_type($root_path . $dest_path_project_files . $_FILES['files']['name'][$file_form_name]),
                    ":filesize" => $_FILES['files']['size'][$file_form_name],
                    ":timestamp" => time(),
                    ":proposal_id" => $proposal_id,
                    ":filetype" => $file_type,
                  ];
                  $this->connection->query($query, $args);
                  $this->messenger->addStatus($file_name . ' file updated successfully.');
                }
              } //move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
              else {
                $this->messenger->addError($this->t('Error uploading file: @path', ['@path' => $dest_path_project_files . $file_name]));
              }
            }
            break;
        } //$file_type
      } //$file_name
    } //$_FILES['files']['name'] as $file_form_name => $file_name
	/* sending email */
    // $email_to = $user->mail;
    // $from = variable_get('custom_model_from_email', '');
    // $bcc = variable_get('custom_model_emails', '');
    // $cc = variable_get('custom_model_cc_emails', '');
    // $params['abstract_uploaded']['proposal_id'] = $proposal_id;
    // $params['abstract_uploaded']['submitted_abstract_id'] = $submitted_abstract_id;
    // $params['abstract_uploaded']['user_id'] = $user->uid;
    // $params['abstract_uploaded']['headers'] = [
    //   'From' => $from,
    //   'MIME-Version' => '1.0',
    //   'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
    //   'Content-Transfer-Encoding' => '8Bit',
    //   'X-Mailer' => 'Drupal',
    //   'Cc' => $cc,
    //   'Bcc' => $bcc,
    // ];
    // if (!drupal_mail('custom_model', 'abstract_uploaded', $email_to, language_default(), $params, $from, TRUE)) {
    //   \Drupal::messenger()->addMessage('Error sending email message.', 'error');
    // }
    // drupal_goto('custom-model/abstract-code');
    // return new RedirectResponse('/custom-model/abstract-code/upload');
  }

}

