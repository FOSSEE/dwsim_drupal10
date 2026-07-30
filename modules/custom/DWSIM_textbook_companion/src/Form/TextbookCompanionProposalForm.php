<?php

/**
 * @file
 * Book proposal form for Textbook Companion.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\textbook_companion\Services\MailService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Migrated book_proposal_form — same fields, validation, and submit pipeline.
 */
class TextbookCompanionProposalForm extends FormBase {

  protected $database;
  protected $currentUser;
  protected $configFactory;
  protected $mailService;

  public function __construct(
    Connection $database,
    AccountProxyInterface $current_user,
    ConfigFactoryInterface $config_factory,
    MailService $mail_service
  ) {
    $this->database = $database;
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
    $this->mailService = $mail_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('textbook_companion.mail_service')
    );
  }

  public function getFormId() {
    return 'book_proposal_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes'] = ['enctype' => 'multipart/form-data'];

    $form['imp_notice'] = [
      '#type' => 'item',
      '#markup' => '<font color="red"><b>Please fill up this form carefully as the details entered here will be exactly written in the Textbook Companion</b></font>',
    ];
    $form['full_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
    ];
    $form['email_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email'),
      '#size' => 30,
      '#default_value' => $this->currentUser->getEmail(),
      '#disabled' => TRUE,
    ];
    $form['mobile'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mobile No.'),
      '#size' => 30,
      '#maxlength' => 15,
      '#required' => TRUE,
    ];
    $form['gender'] = [
      '#type' => 'radios',
      '#title' => $this->t('Gender'),
      '#options' => ['M' => 'Male', 'F' => 'Female'],
      '#required' => TRUE,
    ];
    $form['how_project'] = [
      '#type' => 'select',
      '#title' => $this->t('How did you come to know about this project'),
      '#options' => [
        'DWSIM Website' => 'DWSIM Website',
        'Friend' => 'Friend',
        'Professor/Teacher' => 'Professor/Teacher',
        'Mailing List' => 'Mailing List',
        'Poster in my/other college' => 'Poster in my/other college',
        'Others' => 'Others',
      ],
      '#required' => TRUE,
    ];
    $form['course'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Course'),
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
    ];
    $form['branch'] = [
      '#type' => 'select',
      '#title' => $this->t('Department/Branch'),
      '#options' => function_exists('_list_of_departments') ? _list_of_departments() : [],
      '#required' => TRUE,
    ];
    $form['university'] = [
      '#type' => 'textfield',
      '#title' => $this->t('University/ Institute'),
      '#size' => 80,
      '#maxlength' => 200,
      '#required' => TRUE,
      '#attributes' => ['placeholder' => 'Insert full name of your institute/ university.... '],
    ];
    $form['country'] = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#options' => ['India' => 'India', 'Others' => 'Others'],
      '#required' => TRUE,
    ];
    $form['other_country'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other than India'),
      '#size' => 100,
      '#attributes' => ['placeholder' => $this->t('Enter your country name')],
      '#states' => ['visible' => [':input[name="country"]' => ['value' => 'Others']]],
    ];
    $form['other_state'] = [
      '#type' => 'textfield',
      '#title' => $this->t('State other than India'),
      '#size' => 100,
      '#attributes' => ['placeholder' => $this->t('Enter your state/region name')],
      '#states' => ['visible' => [':input[name="country"]' => ['value' => 'Others']]],
    ];
    $form['other_city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City other than India'),
      '#size' => 100,
      '#attributes' => ['placeholder' => $this->t('Enter your city name')],
      '#states' => ['visible' => [':input[name="country"]' => ['value' => 'Others']]],
    ];
    $form['all_state'] = [
      '#type' => 'select',
      '#title' => $this->t('State'),
      '#options' => function_exists('_list_of_states') ? _list_of_states() : [],
      '#states' => ['visible' => [':input[name="country"]' => ['value' => 'India']]],
    ];
    $form['city'] = [
      '#type' => 'select',
      '#title' => $this->t('City'),
      '#options' => function_exists('_list_of_cities') ? _list_of_cities() : [],
      '#states' => ['visible' => [':input[name="country"]' => ['value' => 'India']]],
    ];
    $form['pincode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pincode'),
      '#size' => 30,
      '#maxlength' => 6,
      '#attributes' => ['placeholder' => 'Enter pincode....'],
    ];
    $form['hr'] = ['#type' => 'item', '#markup' => '<hr>'];
    $form['faculty'] = ['#type' => 'hidden', '#value' => 'None'];
    $form['faculty_email'] = ['#type' => 'hidden', '#value' => 'None@email.com'];
    $form['reviewer'] = ['#type' => 'hidden', '#value' => 'None'];
    $form['version'] = [
      '#type' => 'select',
      '#title' => $this->t('Version'),
      '#options' => function_exists('_list_of_software_version') ? _list_of_software_version() : [],
      '#required' => TRUE,
    ];
    $form['older'] = [
      '#type' => 'textfield',
      '#size' => 30,
      '#maxlength' => 50,
      '#description' => $this->t('Specify the Older version used'),
      '#states' => ['visible' => [':input[name="version"]' => ['value' => 'olderversion']]],
    ];
    $form['completion_date'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Expected Date of Completion'),
      '#description' => $this->t('Input date format should be DD-MM-YYYY. Eg: 23-03-2011'),
      '#size' => 10,
      '#maxlength' => 10,
    ];
    $form['operating_system'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Operating System'),
      '#required' => TRUE,
      '#size' => 30,
      '#maxlength' => 50,
    ];
    $form['reason'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Reasons'),
      '#options' => [
        'Used in more than one University' => $this->t('Used in more than one University'),
        'The book has multiple editions' => $this->t('The book has multiple editions'),
        'Extremely useful' => $this->t('Extremely useful'),
        'Other reason' => $this->t('Any other reason state below'),
      ],
      '#required' => TRUE,
    ];
    $form['other_reason'] = [
      '#type' => 'textarea',
      '#states' => ['visible' => [':input[name="reason[Other reason]"]' => ['checked' => TRUE]]],
    ];
    $form['proposal_type'] = ['#type' => 'hidden', '#default_value' => '1'];
    $form['reference'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Reference'),
      '#required' => TRUE,
      '#attributes' => ['placeholder' => 'Links of the syllabus must be provided....'],
    ];
    $form['form_type'] = ['#type' => 'hidden', '#value' => 1];

    $form['preference1'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Book Preference'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['preference1']['book1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title of the book'),
      '#size' => 30,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];
    $form['preference1']['author1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Author Name'),
      '#size' => 30,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];
    $form['preference1']['isbn1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ISBN No'),
      '#size' => 30,
      '#maxlength' => 25,
      '#required' => TRUE,
    ];
    $form['preference1']['publisher1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publisher & Place'),
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
    ];
    $form['preference1']['edition1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Edition'),
      '#size' => 4,
      '#maxlength' => 2,
      '#required' => TRUE,
    ];
    $form['preference1']['year1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Year of publication'),
      '#size' => 4,
      '#maxlength' => 4,
      '#required' => TRUE,
    ];

    $ext = $this->configFactory->get('textbook_companion.settings')->get('textbook_companion_source_extensions');
    if (!$ext && function_exists('tc_variable_get')) {
      $ext = tc_variable_get('textbook_companion_source_extensions', '');
    }
    $form['samplefile'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Sample Source Files'),
    ];
    $form['samplefile']['samplefile1'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload sample source file'),
      '#size' => 48,
      '#description' => $this->t('Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br /><span style="color:red;">Allowed file extensions : ' . $ext . '</span>',
    ];
    $form['submit'] = ['#type' => 'submit', '#value' => $this->t('Submit')];
    $form['dir_name'] = ['#type' => 'hidden', '#value' => 'None'];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!preg_match('/^[0-9\ \+]{0,15}$/', $form_state->getValue('mobile'))) {
      $form_state->setErrorByName('mobile', $this->t('Invalid mobile number'));
    }
    if (!preg_match('/^[0-9]{1,2}-[0-9]{1,2}-[0-9]{4}$/', $form_state->getValue('completion_date'))) {
      $form_state->setErrorByName('completion_date', $this->t('Invalid expected date of completion'));
    }
    $date_parts = explode('-', (string) $form_state->getValue('completion_date'));
    if (count($date_parts) === 3) {
      list($d, $m, $y) = $date_parts;
      $d = (int) $d;
      $m = (int) $m;
      $y = (int) $y;
      if (!checkdate($m, $d, $y)) {
        $form_state->setErrorByName('completion_date', $this->t('Invalid expected date of completion'));
      }
      if (mktime(0, 0, 0, $m, $d, $y) <= time()) {
        $form_state->setErrorByName('completion_date', $this->t('Expected date of completion should be in future'));
      }
    }

    $cur_year = date('Y');
    if (!preg_match('/^[A-Za-z]/', $form_state->getValue('book1'))) {
      $form_state->setErrorByName('book1', $this->t('Invalid book name for Book Preference 1'));
    }
    if (!preg_match('/^[0-9\-xX]+$/', $form_state->getValue('isbn1'))) {
      $form_state->setErrorByName('isbn1', $this->t('Invalid ISBN for Book Preference 1'));
    }
    if (!preg_match('/^[1-9][0-9]{0,1}$/', $form_state->getValue('edition1'))) {
      $form_state->setErrorByName('edition1', $this->t('Invalid edition for Book Preference 1'));
    }
    if (!preg_match('/^[1-3][0-9][0-9][0-9]$/', $form_state->getValue('year1'))) {
      $form_state->setErrorByName('year1', $this->t('Invalid year of publication for Book Preference 1'));
    }
    if ((int) $form_state->getValue('year1') > $cur_year) {
      $form_state->setErrorByName('year1', $this->t('Year of publication should not be in the future for Book Preference 1'));
    }

    $bk1 = trim((string) $form_state->getValue('book1'));
    $auth1 = trim((string) $form_state->getValue('author1'));
    if ($bk1 && $auth1 && function_exists('_dir_name')) {
      $dir = _dir_name($bk1, $auth1, NULL, $form_state);
      if ($dir != NULL) {
        $form_state->setValue('dir_name1', $dir);
      }
    }

    if ($form_state->getValue('version') == 'olderversion' && $form_state->getValue('older') == '') {
      $form_state->setErrorByName('older', $this->t('Please provide valid version'));
    }

    $files = $this->getRequest()->files->get('files', []);
    $sample = is_array($files) ? ($files['samplefile1'] ?? NULL) : NULL;
    if (!$sample || (is_object($sample) && !$sample->getClientOriginalName())) {
      // Fall back to $_FILES for legacy upload field name.
      if (empty($_FILES['files']['name']['samplefile1'])) {
        $form_state->setErrorByName('samplefile1', $this->t('Please upload sample code main or source file.'));
      }
    }
    if (!empty($_FILES['files']['name'])) {
      foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
        if ($file_name) {
          $allowed_extensions_str = function_exists('tc_variable_get')
            ? tc_variable_get('textbook_companion_source_extensions', '')
            : (string) $this->configFactory->get('textbook_companion.settings')->get('textbook_companion_source_extensions');
          $allowed_extensions = array_filter(array_map('trim', explode(',', $allowed_extensions_str)));
          $fnames = explode('.', strtolower($file_name));
          $temp_extension = end($fnames);
          if ($allowed_extensions && !in_array($temp_extension, $allowed_extensions)) {
            $form_state->setErrorByName($file_form_name, $this->t('Only files with @ext extensions can be uploaded.', ['@ext' => $allowed_extensions_str]));
          }
          if ($_FILES['files']['size'][$file_form_name] <= 0) {
            $form_state->setErrorByName($file_form_name, $this->t('File size cannot be zero.'));
          }
          if (function_exists('textbook_companion_check_valid_filename') && !textbook_companion_check_valid_filename($file_name)) {
            $form_state->setErrorByName($file_form_name, $this->t('Invalid file name specified. Only alphabets and numbers are allowed as a valid filename.'));
          }
        }
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uid = $this->currentUser->id();
    if (!$uid) {
      $this->messenger()->addError($this->t('It is mandatory to login on this website to access the proposal form'));
      return;
    }

    $root_path = function_exists('textbook_companion_samplecode_path')
      ? textbook_companion_samplecode_path()
      : '';

    $date_parts = explode('-', (string) $form_state->getValue('completion_date'));
    $completion_date_timestamp = time();
    if (count($date_parts) === 3) {
      list($d, $m, $y) = $date_parts;
      $completion_date_timestamp = mktime(0, 0, 0, (int) $m, (int) $d, (int) $y);
    }

    $dwsim_version = $form_state->getValue('version');
    if ($dwsim_version == 'olderversion') {
      $dwsim_version = $form_state->getValue('older');
    }

    $country = $form_state->getValue('country');
    $state = $form_state->getValue('all_state');
    // Preserve legacy comparison exactly.
    if ($country == 'other') {
      $country = trim((string) $form_state->getValue('other_country'));
      $state = trim((string) $form_state->getValue('other_state'));
    }

    $reason_vals = $form_state->getValue('reason') ?: [];
    $selected = array_filter(is_array($reason_vals) ? $reason_vals : []);
    $my_reason = implode(', ', array_keys($selected));
    $other_reason = $form_state->getValue('other_reason');
    if ($other_reason) {
      $my_reason .= '- ' . $other_reason;
    }

    $fields = [
      'uid' => $uid,
      'approver_uid' => 0,
      'full_name' => trim(ucwords(strtolower($form_state->getValue('full_name')))),
      'mobile' => trim($form_state->getValue('mobile')),
      'gender' => $form_state->getValue('gender'),
      'how_project' => $form_state->getValue('how_project'),
      'course' => trim($form_state->getValue('course')),
      'branch' => $form_state->getValue('branch'),
      'university' => trim($form_state->getValue('university')),
      'city' => trim((string) $form_state->getValue('city')),
      'pincode' => $form_state->getValue('pincode'),
      'state' => trim((string) $state),
      'country' => $country,
      'faculty' => ucwords(strtolower($form_state->getValue('faculty') ?: 'None')),
      'reviewer' => 'DWSIM TBC Team',
      'reference' => trim($form_state->getValue('reference')),
      'completion_date' => $completion_date_timestamp,
      'creation_date' => time(),
      'approval_date' => 0,
      'proposal_status' => 0,
      'dwsim_version' => trim($dwsim_version),
      'operating_system' => trim($form_state->getValue('operating_system')),
      'teacher_email' => $form_state->getValue('faculty_email'),
      'reason' => $my_reason,
      'samplefilepath' => '',
      'proposal_type' => 0,
      'proposed_completion_date' => $completion_date_timestamp,
    ];

    $result = $this->database->insert('textbook_companion_proposal')->fields($fields)->execute();

    // Preference row required by approval/upload pipeline (same fields as nonaicte path).
    if ($form_state->getValue('book1')) {
      $pref_fields = [
        'proposal_id' => $result,
        'pref_number' => 1,
        'book' => ucwords(strtolower($form_state->getValue('book1'))),
        'author' => ucwords(strtolower($form_state->getValue('author1'))),
        'isbn' => $form_state->getValue('isbn1'),
        'publisher' => ucwords(strtolower($form_state->getValue('publisher1'))),
        'edition' => $form_state->getValue('edition1'),
        'year' => $form_state->getValue('year1'),
        'category' => 0,
        'approval_status' => 0,
        'directory_name' => $form_state->getValue('dir_name1') ?: '',
        'nonaicte_book' => 0,
      ];
      $this->database->insert('textbook_companion_preference')->fields($pref_fields)->execute();
    }

    $dest_path = $result . '/';
    if ($root_path && !is_dir($root_path . $dest_path)) {
      mkdir($root_path . $dest_path);
    }
    if (!empty($_FILES['files']['name'])) {
      foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
        if ($file_name && $root_path) {
          $dest_file = $root_path . $dest_path . $file_name;
          if (file_exists($dest_file)) {
            unlink($dest_file);
          }
          if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $dest_file)) {
            $this->database->update('textbook_companion_proposal')
              ->fields(['samplefilepath' => $dest_path . $file_name])
              ->condition('id', $result)
              ->execute();
          }
        }
      }
    }

    $params = [];
    $params['proposal_received']['proposal_id'] = $result;
    $params['proposal_received']['user_id'] = $uid;
    $this->mailService->sendMail('textbook_companion', 'proposal_received', $this->currentUser->getEmail(), $params);

    $this->messenger()->addStatus($this->t('We have received your book proposal. We will get back to you soon.'));
    $form_state->setRedirectUrl(Url::fromRoute('<front>'));
  }

}
