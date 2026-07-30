<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\ChequeContctForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ChequeContctForm extends FormBase {

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a ChequeContctForm object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(Connection $database, AccountProxyInterface $current_user) {
    $this->database = $database;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cheque_contct_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $uid = $this->currentUser->id();

    // Query for logged-in user proposal to pre-populate / check.
    $form1 = NULL;
    $data = NULL;
    if ($uid) {
      $query = $this->database->select('textbook_companion_proposal', 'p');
      $query->fields('p', ['id', 'how_project']);
      $query->condition('uid', $uid);
      $data = $query->execute()->fetchObject();
      if ($data) {
        $form1 = $data->id;
      }
    }

    if ($uid) {
      $search_term = \Drupal::request()->query->get('search') ?: '';

      $form['search'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Search'),
        '#size' => 48,
        '#default_value' => $search_term,
      ];

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Search'),
      ];

      $form['cancel'] = [
        '#type' => 'link',
        '#title' => $this->t('Cancel'),
        '#url' => Url::fromRoute('<front>'),
        '#attributes' => [
          'class' => ['button'],
        ],
      ];

      $form['submit2'] = [
        '#type' => 'link',
        '#title' => $this->t('Generate Report'),
        '#url' => Url::fromRoute('textbook_companion.cheque_report_form'),
        '#attributes' => [
          'id' => 'perm_report',
          'class' => ['button'],
        ],
      ];

      // Query submitted cheques.
      $query = $this->database->select('textbook_companion_proposal', 'p');
      $query->join('textbook_companion_cheque', 'c', 'p.id = c.proposal_id');
      $query->fields('p', ['full_name']);
      $query->fields('c', ['proposal_id', 'address_con', 'cheque_no', 'cheque_dispatch_date']);
      $query->condition('c.address_con', 'Submitted');

      if (!empty($search_term)) {
        $query->condition('p.full_name', '%' . $this->database->escapeLike($search_term) . '%', 'LIKE');
      }

      $result = $query->execute();
      $search_rows = [];

      while ($search_data = $result->fetchObject()) {
        $status_url = Url::fromRoute('textbook_companion.cheque_status_form', [
          'proposal_id' => $search_data->proposal_id,
        ]);
        $status_link = Link::fromTextAndUrl($search_data->full_name, $status_url)->toString();

        $search_rows[] = [
          $status_link,
          $search_data->address_con,
          $search_data->cheque_no,
          $search_data->cheque_dispatch_date,
        ];
      }

      if (!empty($search_rows)) {
        $search_header = [
          $this->t('Name Of The Student'),
          $this->t('Application Form Status'),
          $this->t('Cheque No'),
          $this->t('Cheque Clearance Date'),
        ];

        $form['search_results'] = [
          '#type' => 'item',
          '#title' => !empty($search_term) ? $this->t('Search results for "@term"', ['@term' => $search_term]) : '',
          'table' => [
            '#type' => 'table',
            '#header' => $search_header,
            '#rows' => $search_rows,
          ],
        ];
      }
      else {
        $form['search_results'] = [
          '#type' => 'item',
          '#title' => !empty($search_term) ? $this->t('Search results for "@term"', ['@term' => $search_term]) : '',
          '#markup' => $this->t('No results found'),
        ];
      }

      return $form;
    }
    else {
      $form2 = 0;
      $form3 = 0;
      $form4 = 0;
      $form5 = 0;
      $form9 = '';
      $form8 = '';
      $form10 = '';
      $form11 = '';
      $form12 = '';
      $form13 = '';

      if ($form1) {
        $query = $this->database->select('textbook_companion_paper', 'p');
        $query->fields('p');
        $query->condition('proposal_id', $form1);
        $data1 = $query->execute()->fetchObject();

        if ($data1) {
          $form2 = $data1->internship_form;
          $form3 = $data1->copyright_form;
          $form4 = $data1->undertaking_form;
          $form5 = $data1->reciept_form;
        }

        $query = $this->database->select('textbook_companion_proposal', 'pr');
        $query->fields('pr');
        $query->condition('id', $form1);
        $data_chq = $query->execute()->fetchObject();

        if ($data_chq) {
          $form9 = $data_chq->full_name;
          $form8 = $data ? $data->how_project : '';
          $form10 = $data_chq->mobile;
          $form11 = $data_chq->course;
          $form12 = $data_chq->branch;
          $form13 = $data_chq->university;
        }
      }

      if ($form2 && $form3 && $form4 && $form5) {
        $form['full_name'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Full Name'),
          '#size' => 30,
          '#maxlength' => 50,
          '#default_value' => $form9,
        ];
        $form['mobile'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Mobile No.'),
          '#size' => 30,
          '#maxlength' => 15,
          '#default_value' => $form10,
        ];
        $form['how_project'] = [
          '#type' => 'select',
          '#title' => $this->t('How did you come to know about this project'),
          '#options' => [
            'eSim Website' => 'eSim Website',
            'Friend' => 'Friend',
            'Professor/Teacher' => 'Professor/Teacher',
            'Mailing List' => 'Mailing List',
            'Poster in my/other college' => 'Poster in my/other college',
            'Others' => 'Others',
          ],
          '#default_value' => $form8,
        ];
        $form['course'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Course'),
          '#size' => 30,
          '#maxlength' => 50,
          '#default_value' => $form11,
        ];
        $form['branch'] = [
          '#type' => 'select',
          '#title' => $this->t('Department/Branch'),
          '#options' => [
            'Electrical Engineering' => 'Electrical Engineering',
            'Electronics Engineering' => 'Electronics Engineering',
            'Computer Engineering' => 'Computer Engineering',
            'Chemical Engineering' => 'Chemical Engineering',
            'Instrumentation Engineering' => 'Instrumentation Engineering',
            'Mechanical Engineering' => 'Mechanical Engineering',
            'Civil Engineering' => 'Civil Engineering',
            'Physics' => 'Physics',
            'Mathematics' => 'Mathematics',
            'Others' => 'Others',
          ],
          '#default_value' => $form12,
        ];

        $form['university'] = [
          '#type' => 'textfield',
          '#title' => $this->t('University/Institute'),
          '#size' => 30,
          '#maxlength' => 100,
          '#default_value' => $form13,
        ];
        $form['addressforcheque'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Address For Mailing Cheque'),
          '#size' => 30,
          '#maxlength' => 100,
        ];
        $form['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Submit'),
        ];
        $form['cancel'] = [
          '#type' => 'markup',
          '#markup' => $this->t('Cancel'),
        ];
      }

      if (!$form2) {
        $this->messenger()->addError($this->t('Internship Form has not been received.'));
      }
      if (!$form3) {
        $this->messenger()->addError($this->t('Copyright Form has not been received.'));
      }
      if (!$form4) {
        $this->messenger()->addError($this->t('Undertaking Form has not been received.'));
      }

      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uid = $this->currentUser->id();
    if ($uid) {
      $search = $form_state->getValue('search');
      $form_state->setRedirect('textbook_companion.cheque_contct_form', [], [
        'query' => ['search' => $search],
      ]);
    }
  }

}
