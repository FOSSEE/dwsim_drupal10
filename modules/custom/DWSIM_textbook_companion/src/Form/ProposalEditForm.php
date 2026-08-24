<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\ProposalEditForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Form\EnforcedResponseException;

class ProposalEditForm extends FormBase {

  protected $database;
  protected $currentUser;
  protected $entityTypeManager;

  public function __construct(Connection $database, AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->database = $database;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  public function getFormId() {
    return 'proposal_edit_form';
  }

  /**
   * Helper to load a preference by proposal_id and pref_number.
   */
  protected function loadPreference($proposal_id, $pref_number) {
    return $this->database->select('textbook_companion_preference', 'p')
      ->fields('p')
      ->condition('proposal_id', $proposal_id)
      ->condition('pref_number', $pref_number)
      ->range(0, 1)
      ->execute()
      ->fetchObject();
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $proposal_id = \Drupal::request()->query->get('proposal_id')
      ?? \Drupal::routeMatch()->getParameter('proposal_id');

    if (empty($proposal_id)) {
      $parts = explode('/', trim(\Drupal::request()->getPathInfo(), '/'));
      $last = end($parts);
      if (is_numeric($last)) {
        $proposal_id = (int) $last;
      }
    }

    if (empty($proposal_id)) {
      $this->messenger()->addError($this->t('Invalid proposal selected. Please try again.'));
      throw new EnforcedResponseException(
        new RedirectResponse(Url::fromRoute('textbook_companion._proposal_all')->toString())
      );
    }

    $query = $this->database->select('textbook_companion_proposal', 'p');
    $query->fields('p');
    $query->condition('id', $proposal_id);
    $proposal_data = $query->execute()->fetchObject();

    if (!$proposal_data) {
      $this->messenger()->addError($this->t('Invalid proposal selected. Please try again.'));
      throw new EnforcedResponseException(
        new RedirectResponse(Url::fromRoute('textbook_companion._proposal_all')->toString())
      );
    }

    $user_data = $this->entityTypeManager->getStorage('user')->load($proposal_data->uid);

    $preference1_data = $this->loadPreference($proposal_id, 1);
    $preference2_data = $this->loadPreference($proposal_id, 2);
    $preference3_data = $this->loadPreference($proposal_id, 3);

    $form['full_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
      '#default_value' => $proposal_data->full_name,
    ];
    $form['email_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email'),
      '#size' => 30,
      '#value' => $user_data ? $user_data->getEmail() : '',
      '#disabled' => TRUE,
    ];
    $form['mobile'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mobile No.'),
      '#size' => 30,
      '#maxlength' => 15,
      '#required' => TRUE,
      '#default_value' => $proposal_data->mobile,
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
      '#default_value' => $proposal_data->how_project,
    ];
    $form['course'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Course'),
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
      '#default_value' => $proposal_data->course,
    ];

    $dept_options = function_exists('_list_of_departments') ? _list_of_departments() : [];
    $form['branch'] = [
      '#type' => 'select',
      '#title' => $this->t('Department/Branch'),
      '#options' => $dept_options,
      '#required' => TRUE,
      '#default_value' => $proposal_data->branch,
    ];
    $form['university'] = [
      '#type' => 'textfield',
      '#title' => $this->t('University/ Institute'),
      '#size' => 80,
      '#maxlength' => 200,
      '#required' => TRUE,
      '#attributes' => ['placeholder' => 'Insert full name of your institute/ university....'],
      '#default_value' => $proposal_data->university,
    ];
    $form['country'] = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#options' => ['India' => 'India', 'Others' => 'Others'],
      '#required' => TRUE,
      '#default_value' => $proposal_data->country,
    ];
    $form['other_country'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other than India'),
      '#size' => 100,
      '#default_value' => $proposal_data->country,
      '#states' => ['visible' => [':input[name="country"]' => ['value' => 'Others']]],
    ];
    $form['other_state'] = [
      '#type' => 'textfield',
      '#title' => $this->t('State other than India'),
      '#size' => 100,
      '#default_value' => $proposal_data->state,
      '#states' => ['visible' => [':input[name="country"]' => ['value' => 'Others']]],
    ];
    $form['other_city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City other than India'),
      '#size' => 100,
      '#default_value' => $proposal_data->city,
      '#states' => ['visible' => [':input[name="country"]' => ['value' => 'Others']]],
    ];

    $state_options = function_exists('_list_of_states') ? _list_of_states() : [];
    $form['all_state'] = [
      '#type' => 'select',
      '#title' => $this->t('State'),
      '#options' => $state_options,
      '#default_value' => $proposal_data->state,
      '#states' => ['visible' => [':input[name="country"]' => ['value' => 'India']]],
    ];

    $city_options = function_exists('_list_of_cities') ? _list_of_cities() : [];
    $form['city'] = [
      '#type' => 'select',
      '#title' => $this->t('City'),
      '#options' => $city_options,
      '#default_value' => $proposal_data->city,
      '#states' => ['visible' => [':input[name="country"]' => ['value' => 'India']]],
    ];
    $form['pincode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pincode'),
      '#size' => 30,
      '#maxlength' => 6,
      '#default_value' => $proposal_data->pincode,
    ];
    $form['hr'] = ['#type' => 'item', '#markup' => '<hr>'];
    $form['faculty'] = ['#type' => 'hidden', '#value' => $proposal_data->faculty];
    $form['reviewer'] = ['#type' => 'hidden', '#value' => $proposal_data->reviewer];
    $form['completion_date'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Expected Date of Completion'),
      '#description' => $this->t('Input date format should be DD-MM-YYYY. Eg: 23-03-2011'),
      '#size' => 10,
      '#maxlength' => 10,
      '#default_value' => $proposal_data->completion_date ? date('d-m-Y', $proposal_data->completion_date) : '',
    ];
    $form['version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('DWSIM Version'),
      '#size' => 10,
      '#maxlength' => 20,
      '#default_value' => $proposal_data->dwsim_version,
    ];
    $form['operating_system'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Operating System'),
      '#size' => 30,
      '#maxlength' => 50,
      '#default_value' => $proposal_data->operating_system,
    ];

    // Preference 1 (always shown).
    $form['preference1'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Book Preference 1'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    foreach (['book1' => 'book', 'author1' => 'author', 'isbn1' => 'isbn', 'publisher1' => 'publisher', 'edition1' => 'edition', 'year1' => 'year'] as $field => $col) {
      $form['preference1'][$field] = [
        '#type' => 'textfield',
        '#title' => $this->t(ucfirst(str_replace('1', '', $field))),
        '#required' => TRUE,
        '#default_value' => $preference1_data ? $preference1_data->$col : '',
        '#size' => in_array($field, ['edition1', 'year1']) ? 4 : 30,
        '#maxlength' => in_array($field, ['edition1']) ? 2 : (in_array($field, ['year1']) ? 4 : 100),
      ];
    }

    if ($preference2_data) {
      $form['preference2'] = ['#type' => 'fieldset', '#title' => $this->t('Book Preference 2'), '#collapsible' => TRUE, '#collapsed' => FALSE];
      foreach (['book2' => 'book', 'author2' => 'author', 'isbn2' => 'isbn', 'publisher2' => 'publisher', 'edition2' => 'edition', 'year2' => 'year'] as $field => $col) {
        $form['preference2'][$field] = [
          '#type' => 'textfield',
          '#title' => $this->t(ucfirst(str_replace('2', '', $field))),
          '#required' => TRUE,
          '#default_value' => $preference2_data->$col,
          '#size' => in_array($field, ['edition2', 'year2']) ? 4 : 30,
          '#maxlength' => in_array($field, ['edition2']) ? 2 : (in_array($field, ['year2']) ? 4 : 100),
        ];
      }
    }

    if ($preference3_data) {
      $form['preference3'] = ['#type' => 'fieldset', '#title' => $this->t('Book Preference 3'), '#collapsible' => TRUE, '#collapsed' => FALSE];
      foreach (['book3' => 'book', 'author3' => 'author', 'isbn3' => 'isbn', 'publisher3' => 'publisher', 'edition3' => 'edition', 'year3' => 'year'] as $field => $col) {
        $form['preference3'][$field] = [
          '#type' => 'textfield',
          '#title' => $this->t(ucfirst(str_replace('3', '', $field))),
          '#required' => TRUE,
          '#default_value' => $preference3_data->$col,
          '#size' => in_array($field, ['edition3', 'year3']) ? 4 : 30,
          '#maxlength' => in_array($field, ['edition3']) ? 2 : (in_array($field, ['year3']) ? 4 : 100),
        ];
      }
    }

    $form['hidden_preference_id1'] = ['#type' => 'hidden', '#value' => $preference1_data ? $preference1_data->id : 0];
    $form['hidden_proposal_id'] = ['#type' => 'hidden', '#value' => $proposal_id];
    $form['submit'] = ['#type' => 'submit', '#value' => $this->t('Submit')];
    $form['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('textbook_companion._proposal_all'),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('book1') && $form_state->getValue('author1')) {
      if (function_exists('_dir_name')) {
        $dir = _dir_name(trim($form_state->getValue('book1')), trim($form_state->getValue('author1')), $form_state->getValue('hidden_preference_id1'));
        if ($dir !== NULL) {
          $form_state->setValue('dir_name1', $dir);
        }
      }
    }
    if (!preg_match('/^[0-9\ \+]{0,15}$/', $form_state->getValue('mobile'))) {
      $form_state->setErrorByName('mobile', $this->t('Invalid mobile number'));
    }
    if (!preg_match('/^[0-9]{1,2}-[0-9]{1,2}-[0-9]{4}$/', $form_state->getValue('completion_date'))) {
      $form_state->setErrorByName('completion_date', $this->t('Invalid expected date of completion'));
    }
    else {
      [$d, $m, $y] = explode('-', $form_state->getValue('completion_date'));
      if (!checkdate((int) $m, (int) $d, (int) $y)) {
        $form_state->setErrorByName('completion_date', $this->t('Invalid expected date of completion'));
      }
    }
    if (!preg_match('/^[1-9][0-9]{0,1}$/', $form_state->getValue('edition1'))) {
      $form_state->setErrorByName('edition1', $this->t('Invalid edition for Book Preference 1'));
    }
    if (!preg_match('/^[1-3][0-9][0-9][0-9]$/', $form_state->getValue('year1'))) {
      $form_state->setErrorByName('year1', $this->t('Invalid year of publication for Book Preference 1'));
    }
    elseif ((int) $form_state->getValue('year1') > (int) date('Y')) {
      $form_state->setErrorByName('year1', $this->t('Year of publication should not be in the future for Book Preference 1'));
    }
    if (!preg_match('/^[0-9\-xX]+$/', $form_state->getValue('isbn1'))) {
      $form_state->setErrorByName('isbn1', $this->t('Invalid ISBN for Book Preference 1'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    [$d, $m, $y] = explode('-', $form_state->getValue('completion_date'));
    $completion_ts = mktime(0, 0, 0, $m, $d, $y);
    $proposal_id = $form_state->getValue('hidden_proposal_id');

    if ($form_state->getValue('country') === 'other') {
      $form_state->setValue('country', $form_state->getValue('other_country'));
      $form_state->setValue('all_state', $form_state->getValue('other_state'));
    }

    $this->database->update('textbook_companion_proposal')
      ->fields([
        'full_name' => $form_state->getValue('full_name'),
        'mobile' => $form_state->getValue('mobile'),
        'how_project' => $form_state->getValue('how_project'),
        'course' => $form_state->getValue('course'),
        'branch' => $form_state->getValue('branch'),
        'university' => $form_state->getValue('university'),
        'city' => $form_state->getValue('city'),
        'pincode' => $form_state->getValue('pincode'),
        'state' => $form_state->getValue('all_state'),
        'country' => $form_state->getValue('country'),
        'faculty' => $form_state->getValue('faculty'),
        'reviewer' => $form_state->getValue('reviewer'),
        'completion_date' => $completion_ts,
        'operating_system' => $form_state->getValue('operating_system'),
        'dwsim_version' => $form_state->getValue('version'),
      ])
      ->condition('id', $proposal_id)
      ->execute();

    $preference1_data = $this->loadPreference($proposal_id, 1);
    if ($preference1_data) {
      $preference1_id = $preference1_data->id;
      if (function_exists('del_book_pdf')) {
        del_book_pdf($preference1_id);
      }
      if (function_exists('RenameDir') && $form_state->getValue('dir_name1')) {
        RenameDir($preference1_id, $form_state->getValue('dir_name1'));
      }
      $this->database->update('textbook_companion_preference')
        ->fields([
          'book' => $form_state->getValue('book1'),
          'author' => $form_state->getValue('author1'),
          'isbn' => $form_state->getValue('isbn1'),
          'publisher' => $form_state->getValue('publisher1'),
          'edition' => $form_state->getValue('edition1'),
          'year' => $form_state->getValue('year1'),
          'directory_name' => $form_state->getValue('dir_name1'),
        ])
        ->condition('id', $preference1_id)
        ->execute();
    }

    $this->messenger()->addStatus($this->t('Proposal Updated'));
    $form_state->setRedirect('textbook_companion._proposal_all');
  }

}
