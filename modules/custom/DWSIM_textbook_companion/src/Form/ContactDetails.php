<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\ContactDetails.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContactDetails extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a ContactDetails form object.
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
    return 'contact_details';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $msg = \Drupal::request()->query->get('msg');
    if ($msg === NULL) {
      $this->messenger()->addWarning($this->t('Caution: Please update Contact Detail carefully as this will be used for future reference during Payment.'));
    }

    $x = $this->currentUser->id();
    if (!$x) {
      $this->messenger()->addError($this->t('You must be logged in to view this page.'));
      return [];
    }

    $query = $this->database->select('textbook_companion_proposal', 'p');
    $query->fields('p');
    $query->condition('uid', $x);
    $data2 = $query->execute()->fetchObject();

    if (!$data2) {
      $proposal_url = Url::fromRoute('textbook_companion.proposal_all')->toString();
      $form['message'] = [
        '#type' => 'item',
        '#markup' => $this->t('Fill Up The <a href=":url">Book Proposal Form</a>', [':url' => $proposal_url]),
      ];
      return $form;
    }

    $query = $this->database->select('textbook_companion_preference', 'pref');
    $query->fields('pref');
    $query->condition('approval_status', 1);
    $query->condition('proposal_id', $data2->id);
    $data3 = $query->execute()->fetchObject();

    if (!$data3) {
      $form['message'] = [
        '#type' => 'item',
        '#markup' => $this->t('Book Proposal Has Not Been Accepted.'),
      ];
      return $form;
    }

    $proposal_id = $data2->id;

    $query = $this->database->select('textbook_companion_cheque', 'c');
    $query->fields('c');
    $query->condition('proposal_id', $proposal_id);
    $commentv = $query->execute()->fetchObject();

    $form16 = $commentv ? $commentv->commentf : '';
    $mob_no = $data2->mobile;
    $full_name = $data2->full_name;

    $query = $this->database->select('textbook_companion_cheque', 'chq');
    $query->fields('chq');
    $query->condition('proposal_id', $proposal_id);
    $result = $query->execute();

    $form1 = 0;
    $form8 = 0;
    $form9 = 0;
    $form10 = 0;
    $form11 = 0;
    $form12 = 0;
    $form13 = 0;
    $form14 = 0;
    $form15 = 0;

    if ($data = $result->fetchObject()) {
      $form1 = $data->address;
      $form8 = $data->alt_mobno;
      $form9 = $data->perm_city;
      $form10 = $data->perm_state;
      $form11 = $data->perm_pincode;
      $form12 = $data->temp_chq_address;
      $form13 = $data->temp_city;
      $form14 = $data->temp_state;
      $form15 = $data->temp_pincode;
    }
    else {
      $this->database->insert('textbook_companion_cheque')
        ->fields(['proposal_id' => $proposal_id])
        ->execute();
    }

    $form['candidate_detail'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Candidate Detail'),
      '#attributes' => [
        'id' => 'candidate_detail',
      ],
    ];
    $form['proposal_id'] = [
      '#type' => 'hidden',
      '#default_value' => $proposal_id,
    ];
    $form['candidate_detail']['fullname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#size' => 48,
      '#default_value' => $full_name,
    ];
    $form['candidate_detail']['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email'),
      '#size' => 48,
      '#value' => $this->currentUser->getEmail(),
      '#disabled' => TRUE,
    ];
    $form['candidate_detail']['mobileno1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mobile No'),
      '#size' => 48,
      '#default_value' => $mob_no,
    ];

    $form['candidate_detail']['mobileno2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Alternate Mobile No'),
      '#size' => 48,
      '#default_value' => $form8,
    ];

    $query = $this->database->select('textbook_companion_paper', 'pap');
    $query->fields('pap');
    $query->condition('proposal_id', $proposal_id);
    $q_data = $query->execute()->fetchObject();

    $form_html = '<ul>';
    if ($q_data && $q_data->internship_form) {
      $form_html .= '<li><strong>' . $this->t('Internship Application') . ' </strong>' . $this->t('Form Submitted') . '</li>';
    }
    else {
      $form_html .= '<li><strong>' . $this->t('Internship Application') . ' </strong>' . $this->t('Form Not Submitted.<br>Please submit it as soon as possible.') . '</li>';
    }
    if ($q_data && $q_data->copyright_form) {
      $form_html .= '<li><strong>' . $this->t('Copyright Application') . ' </strong>' . $this->t('Form Submitted') . '</li>';
    }
    else {
      $form_html .= '<li><strong>' . $this->t('Copyright Application') . ' </strong>' . $this->t('Form Not Submitted.<br>Please submit it as soon as possible.') . '</li>';
    }
    if ($q_data && $q_data->undertaking_form) {
      $form_html .= '<li><strong>' . $this->t('Undertaking Application') . ' </strong>' . $this->t('Form Submitted') . '</li>';
    }
    else {
      $form_html .= '<li><strong>' . $this->t('Undertaking Application') . ' </strong>' . $this->t('Form Not Submitted.<br>Please submit it as soon as possible.') . '</li>';
    }
    $form_html .= '</ul>';

    $form['Application Status'] = [
      '#type' => 'fieldset',
      '#markup' => $form_html,
      '#title' => $this->t('Application Form Status'),
      '#attributes' => [
        'id' => 'app_status',
      ],
    ];

    $form['perm_cheque_address'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Permanent Address'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#attributes' => [
        'id' => 'perm_cheque_address',
      ],
    ];
    $form['temp_cheque_address'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Temporary Address'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#attributes' => [
        'id' => 'temp_cheque_address',
      ],
    ];
    $form['perm_cheque_address']['chq_address'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Address'),
      '#size' => 35,
      '#default_value' => $form1,
    ];
    $form['perm_cheque_address']['perm_city'] = [
      '#type' => 'textfield',
      '#default_value' => $form9,
      '#title' => $this->t('City'),
      '#size' => 35,
    ];
    $form['perm_cheque_address']['perm_state'] = [
      '#type' => 'textfield',
      '#default_value' => $form10,
      '#title' => $this->t('State'),
      '#size' => 35,
    ];
    $form['perm_cheque_address']['perm_pincode'] = [
      '#type' => 'textfield',
      '#default_value' => $form11,
      '#title' => $this->t('Zip code'),
      '#size' => 35,
    ];
    $form['temp_cheque_address']['temp_chq_address'] = [
      '#type' => 'textarea',
      '#default_value' => $form12,
      '#title' => $this->t('Address'),
      '#size' => 35,
    ];
    $form['temp_cheque_address']['temp_city'] = [
      '#type' => 'textfield',
      '#default_value' => $form13,
      '#title' => $this->t('City'),
      '#size' => 35,
    ];
    $form['temp_cheque_address']['temp_state'] = [
      '#type' => 'textfield',
      '#default_value' => $form14,
      '#title' => $this->t('State'),
      '#size' => 35,
    ];
    $form['temp_cheque_address']['temp_pincode'] = [
      '#type' => 'textfield',
      '#default_value' => $form15,
      '#title' => $this->t('Zip code'),
      '#size' => 35,
    ];
    $form['temp_cheque_address']['same_address'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Same As Permanent Address'),
    ];

    if ($commentv && $commentv->commentf) {
      $form['commentu'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Remarks'),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
        '#attributes' => [
          'id' => 'comment_cheque',
        ],
      ];
      $form['commentu']['comment_cheque'] = [
        '#type' => 'textarea',
        '#size' => 35,
        '#default_value' => $form16,
        '#attributes' => [
          'readonly' => 'readonly',
        ],
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
    ];

    $form['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('textbook_companion.proposal_all'),
      '#attributes' => [
        'class' => ['button'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $x = $this->currentUser->id();
    if (!$x) {
      return;
    }

    $query = $this->database->select('textbook_companion_proposal', 'p');
    $query->fields('p', ['id']);
    $query->condition('uid', $x);
    $data2 = $query->execute()->fetchObject();

    if ($data2) {
      $this->database->update('textbook_companion_cheque')
        ->fields([
          'alt_mobno' => $form_state->getValue('mobileno2'),
          'address' => $form_state->getValue('chq_address'),
          'perm_city' => $form_state->getValue('perm_city'),
          'perm_state' => $form_state->getValue('perm_state'),
          'perm_pincode' => $form_state->getValue('perm_pincode'),
          'temp_chq_address' => $form_state->getValue('temp_chq_address'),
          'temp_city' => $form_state->getValue('temp_city'),
          'temp_state' => $form_state->getValue('temp_state'),
          'temp_pincode' => $form_state->getValue('temp_pincode'),
          'address_con' => 'Submitted',
        ])
        ->condition('proposal_id', $data2->id)
        ->execute();

      $this->messenger()->addStatus($this->t('Contact Details Has Been Updated.....!'));
      $form_state->setRedirect('textbook_companion.contact_details', [], [
        'query' => ['msg' => 0],
      ]);
    }
  }

}
