<?php

namespace Drupal\dwsim_flowsheet\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * DWSIM Flowsheet Proposal Form (Drupal 10).
 */
class DwsimFlowsheetProposalForm extends FormBase {

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->currentUser = $container->get('current_user');
    $instance->messenger = $container->get('messenger');
    return $instance;
  }

  public function getFormId() {
    return 'dwsim_flowsheet_proposal_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $no_js_use = FALSE) {
    // Require login.
    if ($this->currentUser->isAnonymous()) {
      $login_link = Link::fromTextAndUrl($this->t('login'), Url::fromRoute('user.login', [], ['query' => \Drupal::destination()->getAsArray()]))->toString();
      $this->messenger->addError($this->t('It is mandatory to @login on this website to access the flowsheet proposal form. If you are a new user please create a new account first.', ['@login' => $login_link]));
      return new RedirectResponse(Url::fromRoute('user.login', [], ['query' => \Drupal::destination()->getAsArray()])->toString());
    }

    // If a previous proposal exists and is Pending(0) or Approved(1), block re-submit.
    $conn = Database::getConnection();
    $proposal_q = $conn->select('dwsim_flowsheet_proposal', 'p')
      ->fields('p')
      ->condition('uid', $this->currentUser->id())
      ->orderBy('id', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if ($proposal_q && in_array((int) $proposal_q->approval_status, [0, 1], TRUE)) {
      $this->messenger->addStatus($this->t('We have already received your proposal.'));
      return new RedirectResponse(Url::fromRoute('<front>')->toString());
    }

    $imp = Markup::create('<span style="color: red;">*This is a mandatory field</span>');
    $form['#attributes']['enctype'] = 'multipart/form-data';

    // Basic fields.
    $form['name_title'] = [
      '#type' => 'select',
      '#title' => $this->t('Title'),
      '#options' => ['Dr' => 'Dr', 'Prof' => 'Prof', 'Mr' => 'Mr', 'Mrs' => 'Mrs', 'Ms' => 'Ms'],
      '#required' => TRUE,
    ];
    $form['contributor_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name of the contributor'),
      //'#size' => 250,
      '#attributes' => ['placeholder' => $this->t('Enter your full name.....')],
      '#maxlength' => 250,
      '#required' => TRUE,
    ];
    $form['contributor_contact_no'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Contact No.'),
      //'#size' => 10,
      '#attributes' => ['placeholder' => $this->t('Enter your contact number')],
      '#maxlength' => 250,
    ];
    $form['gender'] = [
      '#type' => 'select',
      '#title' => $this->t('Gender'),
      '#options' => ['Male' => 'Male', 'Female' => 'Female', 'Other' => 'Other'],
      '#required' => TRUE,
    ];

    $form['month_year_of_degree'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Month and year of award of degree'),
      '#required' => TRUE,
      '#date_date_element' => 'date',
      '#date_time_element' => 'none',
      '#date_year_range' => '1960:+22',
    ];

    $form['contributor_email_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email'),
      //'#size' => 30,
      '#default_value' => $this->currentUser->getEmail(),
      '#disabled' => TRUE,
    ];
    $form['university'] = [
      '#type' => 'textfield',
      '#title' => $this->t('University/ Institute'),
      //'#size' => 80,
      '#maxlength' => 200,
      '#required' => TRUE,
      '#attributes' => ['placeholder' => $this->t('Insert full name of your institute/ university.... ')],
    ];
    $form['project_guide_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Project guide'),
      //'#size' => 250,
      '#attributes' => ['placeholder' => $this->t('Enter full name of project guide')],
      '#maxlength' => 250,
    ];
    $form['project_guide_email_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Project guide email'),
      //'#size' => 30,
    ];
    $form['project_guide_university'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Project Guide University/ Institute'),
      //'#size' => 80,
      '#maxlength' => 200,
      '#attributes' => ['placeholder' => $this->t('Insert full name of the institute/ university of your project guide.... ')],
    ];

    $form['country'] = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#options' => ['India' => 'India', 'Others' => 'Others'],
      '#required' => TRUE,
      '#tree' => TRUE,
      '#validated' => TRUE,
    ];
    $form['other_country'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other than India'),
      //'#size' => 100,
      '#attributes' => ['placeholder' => $this->t('Enter your country name')],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => ['value' => 'Others'],
        ],
      ],
      '#description' => $imp,
    ];
    $form['other_state'] = [
      '#type' => 'textfield',
      '#title' => $this->t('State other than India'),
      //'#size' => 100,
      '#attributes' => ['placeholder' => $this->t('Enter your state/region name')],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => ['value' => 'Others'],
        ],
      ],
      '#description' => $imp,
    ];
    $form['other_city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City other than India'),
      //'#size' => 100,
      '#attributes' => ['placeholder' => $this->t('Enter your city name')],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => ['value' => 'Others'],
        ],
      ],
      '#description' => $imp,
    ];
    $form['all_state'] = [
      '#type' => 'select',
      '#title' => $this->t('State'),
      '#options' => _df_list_of_states(),
      '#validated' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => ['value' => 'India'],
        ],
      ],
      '#description' => $imp,
    ];
    $form['city'] = [
      '#type' => 'select',
      '#title' => $this->t('City'),
      '#options' => _df_list_of_cities(),
      '#validated' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => ['value' => 'India'],
        ],
      ],
      '#description' => $imp,
    ];
    $form['pincode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pincode'),
      //'#size' => 30,
      '#maxlength' => 6,
      '#required' => TRUE,
      '#attributes' => ['placeholder' => $this->t('Enter pincode....')],
    ];

    $form['hr'] = [
      '#type' => 'item',
      '#markup' => '<hr>',
    ];
    $form['project_title'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Project Title'),
      //'#size' => 250,
      '#description' => $this->t('Maximum character limit is 250'),
      '#required' => TRUE,
    ];
    $form['reference'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reference'),
      '#maxlength' => 250,
      '#required' => TRUE,
      '#attributes' => ['placeholder' => $this->t('Enter reference')],
    ];
    $form['version'] = [
      '#type' => 'select',
      '#title' => $this->t('Version'),
      '#options' => _df_list_of_software_version(),
      '#required' => TRUE,
    ];
    $form['older'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other Version'),
      //'#size' => 30,
      '#maxlength' => 50,
      '#description' => $this->t('Specify the Older version used as format "DWSIM v2.0"'),
      '#states' => [
        'visible' => [
          ':input[name="version"]' => ['value' => 'Old version'],
        ],
      ],
    ];
    $form['process_development_compound_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name of compound for which process development is carried out'),
      //'#size' => 50,
      '#description' => $this->t("Mention the compound name as shown:\nEx: Ethanol"),
      '#required' => TRUE,
    ];
    $form['process_development_compound_cas_no'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CAS number for compound which process development is carried out'),
      //'#size' => 50,
      '#description' => $this->t("Mention the compound CAS No as shown:\nEx: 64-17-5"),
      '#required' => TRUE,
    ];
    $form['dwsim_database_compound_name'] = [
      '#type' => 'select',
      '#title' => $this->t('List of compounds from DWSIM Database used in process flowsheet'),
      '#multiple' => TRUE,
      //'#size' => 20,
      '#description' => $this->t('Select all the compounds used in flowsheet which are available in above DWSIM compound list [You can select multiple options by holding ctrl + left key of mouse]'),
      '#options' => _df_list_of_dwsim_compound(),
      '#required' => TRUE,
    ];
    $form['ucompound'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Is user defined compound used?'),
    ];

    // User-defined compound group with dynamic rows and file upload.
    // Initialize counter in form_state.
    if ($form_state->get('user_defined_compound_num') === NULL) {
      $form_state->set('user_defined_compound_num', 1);
    }

    $form['upload_u_compound'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Upload user defined compound'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="ucompound"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['upload_u_compound']['udc_field1_fieldset'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#prefix' => '<div id="udc-field1-fieldset-wrapper">',
      '#suffix' => '</div>',
      '#states' => [
        'visible' => [
          ':input[name="ucompound"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $count = (int) $form_state->get('user_defined_compound_num');
    $last_index = 0;
    for ($i = 0; $i < $count; $i++) {
      $last_index = $i;
      $form['upload_u_compound']['udc_field1_fieldset'][$i]['compound'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Name of User defined compound @n', ['@n' => $i + 1]),
        '#default_value' => '',
      ];
      $form['upload_u_compound']['udc_field1_fieldset'][$i]['cas_no'] = [
        '#type' => 'textfield',
        '#title' => $this->t('CAS Number of User defined compound @n', ['@n' => $i + 1]),
        '#default_value' => '',
      ];
    }

    $form['upload_u_compound']['udc_field1_fieldset']['udc_compound_count'] = [
      '#type' => 'hidden',
      '#value' => $last_index,
    ];

    $form['upload_u_compound']['udc_field1_fieldset']['add_compound'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add more compound'),
      '#limit_validation_errors' => [],
      '#submit' => ['::addCompoundRow'],
      '#ajax' => [
        'callback' => '::udcCompoundAddMoreCallback',
        'wrapper' => 'udc-field1-fieldset-wrapper',
      ],
    ];

    if ($count > 1) {
      $form['upload_u_compound']['udc_field1_fieldset']['remove_compound'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove compound'),
        '#limit_validation_errors' => [],
        '#submit' => ['::removeCompoundRow'],
        '#ajax' => [
          'callback' => '::udcCompoundAddMoreCallback',
          'wrapper' => 'udc-field1-fieldset-wrapper',
        ],
      ];
    }

    // Managed file for user compound file(s).
    // Use config replacement for variable_get: dwsim_flowsheet.settings:dwsim_flowsheet_user_defind_compound_source_extensions
    $allowed_ext = \Drupal::config('dwsim_flowsheet.settings')->get('dwsim_flowsheet_user_defind_compound_source_extensions') ?? '';
    $validators = [];
    if (!empty($allowed_ext)) {
      $validators['file_validate_extensions'] = [str_replace(',', ' ', $allowed_ext)];
    }

    $form['upload_u_compound']['upload_user_compound'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload user defined compound'),
      '#description' => $this->t('Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' .
        '<span style="color:red;">' . $this->t('Allowed file extensions: @ext', ['@ext' => $allowed_ext]) . '</span>',
      '#states' => [
        'visible' => [
          ':input[name="ucompound"]' => ['checked' => TRUE],
        ],
      ],
      '#upload_location' => 'temporary://dwsim_ucompound',
      '#upload_validators' => $validators,
      '#multiple' => TRUE,
    ];

    $form['term_condition'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Terms And Conditions'),
      '#options' => [
        'status' => $this->t('<a href="/term-and-conditions" target="_blank">I agree to the Terms and Conditions</a>'),
      ],
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    // Non-JS fallback: remove #ajax if requested.
    if ($no_js_use) {
      unset($form['upload_u_compound']['udc_field1_fieldset']['add_compound']['#ajax']);
      if (isset($form['upload_u_compound']['udc_field1_fieldset']['remove_compound']['#ajax'])) {
        unset($form['upload_u_compound']['udc_field1_fieldset']['remove_compound']['#ajax']);
      }
    }

    return $form;
  }

  /**
   * AJAX callback to re-render the compound fieldset.
   */
  public function udcCompoundAddMoreCallback(array &$form, FormStateInterface $form_state) {
    return $form['upload_u_compound']['udc_field1_fieldset'];
  }

  /**
   * Add one compound row.
   */
  public function addCompoundRow(array &$form, FormStateInterface $form_state) {
    $n = (int) $form_state->get('user_defined_compound_num');
    $form_state->set('user_defined_compound_num', $n + 1);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Remove one compound row.
   */
  public function removeCompoundRow(array &$form, FormStateInterface $form_state) {
    $n = (int) $form_state->get('user_defined_compound_num');
    if ($n > 1) {
      $form_state->set('user_defined_compound_num', $n - 1);
    }
    $form_state->setRebuild(TRUE);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $conn = Database::getConnection();

    // Duplicate project title check for approval_status IN (1=Approved, 3=Completed).
    $exists = $conn->select('dwsim_flowsheet_proposal', 'p')
      ->fields('p', ['id'])
      ->condition('project_title', $values['project_title'])
      ->condition('approval_status', [1, 3], 'IN')
      ->range(0, 1)
      ->execute()
      ->fetchField();
    if ($exists) {
      $form_state->setErrorByName('project_title', $this->t('Project title name already exists'));
      return;
    }

    // Terms.
    if (empty($values['term_condition']['status'])) {
      $form_state->setErrorByName('term_condition', $this->t('Please check the terms and conditions'));
    }

    // Country / state / city logic.
    if ($values['country'] === 'Others') {
      if (empty(trim($values['other_country']))) {
        $form_state->setErrorByName('other_country', $this->t('Enter country name'));
      }
      else {
        $form_state->setValue('country', trim($values['other_country']));
      }
      if (empty(trim($values['other_state']))) {
        $form_state->setErrorByName('other_state', $this->t('Enter state name'));
      }
      else {
        $form_state->setValue('all_state', trim($values['other_state']));
      }
      if (empty(trim($values['other_city']))) {
        $form_state->setErrorByName('other_city', $this->t('Enter city name'));
      }
      else {
        $form_state->setValue('city', trim($values['other_city']));
      }
    }
    else {
      if ($values['country'] === '0') {
        $form_state->setErrorByName('country', $this->t('Select country name'));
      }
      if ($values['all_state'] === '0') {
        $form_state->setErrorByName('all_state', $this->t('Select state name'));
      }
      if ($values['city'] === '0') {
        $form_state->setErrorByName('city', $this->t('Select city name'));
      }
    }

    // Project title.
    $title = trim((string) $values['project_title']);
    $form_state->setValue('project_title', $title);
    if ($title === '') {
      $form_state->setErrorByName('project_title', $this->t('Project title should not be empty'));
    }
    elseif (strlen($title) > 250) {
      $form_state->setErrorByName('project_title', $this->t('Maximum character limit is 250 characters only, please check the length of the project title'));
    }
    elseif (strlen($title) < 10) {
      $form_state->setErrorByName('project_title', $this->t('Minimum character limit is 10 characters, please check the length of the project title'));
    }
    elseif (preg_match('/[\^£$%&*()}{@#~?><>.:;`|=_+¬]/', $title)) {
      $form_state->setErrorByName('project_title', $this->t('Special characters are not allowed for project title'));
    }

    // Compound name.
    $pdn = trim((string) $values['process_development_compound_name']);
    $form_state->setValue('process_development_compound_name', $pdn);
    if ($pdn === '') {
      $form_state->setErrorByName('process_development_compound_name', $this->t('Field should not be empty'));
    }
    elseif (strlen($pdn) >= 50) {
      $form_state->setErrorByName('process_development_compound_name', $this->t('Maximum character limit is 50 characters only, please check the length'));
    }

    // CAS number (basic presence check, keep as in D7).
    $cas = trim((string) $values['process_development_compound_cas_no']);
    $form_state->setValue('process_development_compound_cas_no', $cas);
    if ($cas === '') {
      $form_state->setErrorByName('process_development_compound_cas_no', $this->t('CAS number field should not be empty'));
    }

    // Version older => required.
    if ($values['version'] === 'Old version' && empty(trim((string) $values['older']))) {
      $form_state->setErrorByName('older', $this->t('Please provide valid version'));
    }

    // Flatten selected compounds like D7 did.
    if (!empty($values['dwsim_database_compound_name']) && is_array($values['dwsim_database_compound_name'])) {
      $flat = implode('| ', array_values($values['dwsim_database_compound_name']));
      $form_state->setValue('dwsim_database_compound_name', trim($flat));
    }

    // If user-defined compound is on, ensure file present & validate filenames.
    if (!empty($values['ucompound'])) {
      $fid_list = (array) ($values['upload_user_compound'] ?? []);
      if (count($fid_list) === 0) {
        $form_state->setErrorByName('upload_user_compound', $this->t('Please upload a file.'));
      }
      else {
        /** @var \Drupal\file\FileInterface[] $files */
        $files = File::loadMultiple($fid_list);
        foreach ($files as $file) {
          // Size > 0 (managed_file ensures this, but keep parity with D7).
          if ((int) $file->getSize() <= 0) {
            $form_state->setErrorByName('upload_user_compound', $this->t('File size cannot be zero.'));
            break;
          }
          // Filename rule from your helper (kept minimal).
          if (function_exists('textbook_companion_check_valid_filename') && !textbook_companion_check_valid_filename($file->getFilename())) {
            $form_state->setErrorByName('upload_user_compound', $this->t('Invalid file name specified. Only alphabets and numbers are allowed as a valid filename.'));
            break;
          }
        }
      }
    }

    $month_year = $form_state->getValue('month_year_of_degree');
    if (!$month_year instanceof DrupalDateTime) {
      $form_state->setErrorByName('month_year_of_degree', $this->t('Please select a valid date.'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = $this->currentUser;
    if ($user->isAnonymous()) {
      $this->messenger->addError($this->t('It is mandatory to login on this website to access the proposal form'));
      return;
    }

    $v = $form_state->getValues();

    // Normalize version field.
    if ($v['version'] === 'Old version') {
      $v['version'] = trim((string) $v['older']);
    }

    $project_title = trim((string) $v['project_title']);
    $proposer_name = $v['name_title'] . ' ' . $v['contributor_name'];
    $university = $v['university'];
    $month_year_of_degree = $this->normalizeMonthYearForStorage($v['month_year_of_degree']) ?? trim((string) $v['month_year_of_degree']);
    $directory_name = _df_dir_name($project_title, $proposer_name);

    // Insert proposal (kept very close to your original).
    $conn = Database::getConnection();
    $result_sql = "
      INSERT INTO {dwsim_flowsheet_proposal}
      (uid, approver_uid, name_title, contributor_name, contact_no, gender, month_year_of_degree, university,
       city, pincode, state, country, version, project_guide_name, project_guide_email_id, project_guide_university,
       project_title, process_development_compound_name, process_development_compound_cas_number,
       dwsim_database_compound_name, approval_status, is_completed, dissapproval_reason, creation_date, approval_date,
       directory_name, user_defined_compound_filepath, reference)
      VALUES
      (:uid, :approver_uid, :name_title, :contributor_name, :contact_no, :gender, :month_year_of_degree, :university,
       :city, :pincode, :state, :country, :version, :project_guide_name, :project_guide_email_id, :project_guide_university,
       :project_title, :process_development_compound_name, :process_development_compound_cas_number,
       :dwsim_database_compound_name, :approval_status, :is_completed, :dissapproval_reason, :creation_date, :approval_date,
       :directory_name, :user_defined_compound_filepath, :reference)
    ";
    $args = [
      ':uid' => (int) $user->id(),
      ':approver_uid' => 0,
      ':name_title' => $v['name_title'],
      ':contributor_name' => _df_sentence_case(trim($v['contributor_name'])),
      ':contact_no' => $v['contributor_contact_no'],
      ':gender' => $v['gender'],
      ':month_year_of_degree' => $month_year_of_degree,
      ':university' => _df_sentence_case($university),
      ':city' => $v['city'],
      ':pincode' => $v['pincode'],
      ':state' => $v['all_state'],
      ':country' => $v['country'],
      ':version' => $v['version'],
      ':project_guide_name' => _df_sentence_case($v['project_guide_name']),
      ':project_guide_email_id' => trim((string) $v['project_guide_email_id']),
      ':project_guide_university' => trim((string) $v['project_guide_university']),
      ':project_title' => $v['project_title'],
      ':process_development_compound_name' => _df_sentence_case($v['process_development_compound_name']),
      ':process_development_compound_cas_number' => $v['process_development_compound_cas_no'],
      ':dwsim_database_compound_name' => trim((string) $v['dwsim_database_compound_name']),
      ':approval_status' => 0,
      ':is_completed' => 0,
      ':dissapproval_reason' => "NULL",
      ':creation_date' => \Drupal::time()->getRequestTime(),
      ':approval_date' => 0,
      ':directory_name' => $directory_name,
      ':user_defined_compound_filepath' => "NULL",
      ':reference' => $v['reference'],
    ];

    $proposal_id = $conn->query($result_sql, $args, ['target' => NULL])->fetchField();
    // On some drivers RETURN_INSERT_ID is not available via query(); use lastInsertId as fallback:
    if (!$proposal_id) {
      $proposal_id = $conn->lastInsertId();
    }

    // Handle user-defined compound rows + file(s).
    if (!empty($v['ucompound'])) {
      $compounds = 0;
      $max = (int) ($v['udc_field1_fieldset']['udc_compound_count'] ?? -1);
      for ($i = 0; $i <= $max; $i++) {
        $compound = trim((string) ($v['udc_field1_fieldset'][$i]['compound'] ?? ''));
        $cas = trim((string) ($v['udc_field1_fieldset'][$i]['cas_no'] ?? ''));
        if ($compound !== '' && $cas !== '') {
          $conn->insert('dwsim_flowsheet_user_defined_compound')
            ->fields([
              'proposal_id' => $proposal_id,
              'user_defined_compound' => $compound,
              'cas_no' => $cas,
              'compound_type' => 'U',
            ])->execute();
          $compounds++;
        }
      }

      // Move uploaded file(s) to your legacy directory layout.
      $root_path = dwsim_flowsheet_document_path();
      $dest_path1 = $directory_name . '/';
      $dest_path2 = 'user_defined_compound/';
      $dest_path = $dest_path1 . $dest_path2;
      $proposal_directory = $root_path . $dest_path1;
      $user_defined_compound_directory = $root_path . $dest_path;

      // Ensure directories exist.
      \Drupal::service('file_system')->prepareDirectory($proposal_directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);
      \Drupal::service('file_system')->prepareDirectory($user_defined_compound_directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);

      $fid_list = (array) ($v['upload_user_compound'] ?? []);
      if ($fid_list) {
        /** @var \Drupal\file\FileInterface[] $files */
        $files = File::loadMultiple($fid_list);
        foreach ($files as $file) {
          // Move/copy file from temp to target folder (legacy FS).
          $source = $file->getFileUri();
          $target = $root_path . $dest_path . $file->getFilename();

          // If exists, unlink like D7.
          if (file_exists($target)) {
            @unlink($target);
          }

          $real_source = \Drupal::service('file_system')->realpath($source);
          if (@copy($real_source, $target)) {
            // Update proposal record with the relative path (kept single value like D7).
            $conn->update('dwsim_flowsheet_proposal')
              ->fields(['user_defined_compound_filepath' => $dest_path2 . $file->getFilename()])
              ->condition('id', $proposal_id)
              ->execute();

            $this->messenger->addStatus($this->t('@name uploaded successfully.', ['@name' => $file->getFilename()]));
          }
          else {
            $this->messenger->addError($this->t('Error uploading file: @name', ['@name' => $file->getFilename()]));
          }

          // Mark file permanent in Drupal.
          $file->setPermanent();
          $file->save();
        }
      }
    }

    if (!$proposal_id) {
      $this->messenger->addError($this->t('Error receiving your proposal. Please try again.'));
      return;
    }

    // Email sending (kept structure; use config instead of variable_get).
    $email_to = $user->getEmail();
    $from = \Drupal::config('dwsim_flowsheet.settings')->get('dwsim_flowsheet_from_email') ?? '';
    $bcc = \Drupal::config('dwsim_flowsheet.settings')->get('dwsim_flowsheet_emails') ?? '';
    $cc  = \Drupal::config('dwsim_flowsheet.settings')->get('dwsim_flowsheet_cc_emails') ?? '';

    $params = [];
    $params['dwsim_flowsheet_proposal_received']['proposal_id'] = $proposal_id;
    $params['dwsim_flowsheet_proposal_received']['user_id'] = (int) $user->id();
    $params['dwsim_flowsheet_proposal_received']['headers'] = [
      'From' => $from,
      'MIME-Version' => '1.0',
      'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
      'Content-Transfer-Encoding' => '8Bit',
      'X-Mailer' => 'Drupal',
      'Cc' => $cc,
      'Bcc' => $bcc,
    ];

    // Use Drupal mail manager.
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $result = \Drupal::service('plugin.manager.mail')->mail(
      'dwsim_flowsheet',
      'dwsim_flowsheet_proposal_received',
      $email_to,
      $langcode,
      $params,
      $from,
      TRUE
    );

    if (empty($result['result'])) {
      $this->messenger->addError($this->t('Error sending email message.'));
    }

    $this->messenger->addStatus($this->t('We have received your DWSIM Flowsheeting proposal. We will get back to you soon.'));
    $form_state->setRedirect('<front>');
  }

  private function normalizeMonthYearForStorage($value) {
    if ($value instanceof DrupalDateTime) {
      return $value->format('Y-m');
    }

    $value = trim((string) $value);
    if ($value === '') {
      return NULL;
    }

    foreach (['!M-Y', '!F-Y', '!Y-m', '!Y-m-d'] as $format) {
      $date = \DateTimeImmutable::createFromFormat($format, $value);
      $errors = \DateTimeImmutable::getLastErrors();
      $has_errors = is_array($errors) && (!empty($errors['warning_count']) || !empty($errors['error_count']));
      if (!$date || $has_errors) {
        continue;
      }

      if ($format === '!M-Y' && strcasecmp($date->format('M-Y'), $value) !== 0) {
        continue;
      }
      if ($format === '!F-Y' && strcasecmp($date->format('F-Y'), $value) !== 0) {
        continue;
      }
      if ($format === '!Y-m' && $date->format('Y-m') !== $value) {
        continue;
      }
      if ($format === '!Y-m-d' && $date->format('Y-m-d') !== $value) {
        continue;
      }

      return $date->format('Y-m');
    }

    return NULL;
  }

}
