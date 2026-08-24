<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\EditChapterTitleForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Form\EnforcedResponseException;

class EditChapterTitleForm extends FormBase {

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
   * Constructs an EditChapterTitleForm object.
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
    return 'edit_chapter_title_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $chapter_id = NULL) {
    $uid = $this->currentUser->id();

    // Query for user proposal.
    $query = $this->database->select('textbook_companion_proposal', 'p');
    $query->fields('p');
    $query->condition('uid', $uid);
    $query->orderBy('id', 'DESC');
    $query->range(0, 1);
    $proposal_data = $query->execute()->fetchObject();

    if (!$proposal_data) {
      $proposal_url = Url::fromRoute('textbook_companion.proposal_all')->toString();
      $this->messenger()->addError($this->t('Please submit a <a href=":url">proposal</a>.', [':url' => $proposal_url]));
      
      $redirect_url = Url::fromRoute('textbook_companion.list_chapters')->toString();
      $response = new RedirectResponse($redirect_url);
      throw new EnforcedResponseException($response);
    }

    if ($proposal_data->proposal_status != 1 && $proposal_data->proposal_status != 4) {
      $redirect_url = Url::fromRoute('textbook_companion.list_chapters')->toString();
      switch ($proposal_data->proposal_status) {
        case 0:
          $this->messenger()->addStatus($this->t('We have already received your proposal. We will get back to you soon.'));
          break;
        case 2:
          $proposal_url = Url::fromRoute('textbook_companion.proposal_all')->toString();
          $this->messenger()->addError($this->t('Your proposal has been dis-approved. Please create another proposal <a href=":url">here</a>.', [':url' => $proposal_url]));
          break;
        case 3:
          $proposal_url = Url::fromRoute('textbook_companion.proposal_all')->toString();
          $this->messenger()->addStatus($this->t('Congratulations! You have completed your last book proposal. You have to create another proposal <a href=":url">here</a>.', [':url' => $proposal_url]));
          break;
        default:
          $this->messenger()->addError($this->t('Invalid proposal state. Please contact site administrator for further information.'));
          break;
      }
      $response = new RedirectResponse($redirect_url);
      throw new EnforcedResponseException($response);
    }

    // Query preference details.
    $query = $this->database->select('textbook_companion_preference', 'pref');
    $query->fields('pref');
    $query->condition('proposal_id', $proposal_data->id);
    $query->condition('approval_status', 1);
    $query->range(0, 1);
    $preference_data = $query->execute()->fetchObject();

    if (!$preference_data) {
      $this->messenger()->addError($this->t('Invalid Book Preference status. Please contact site administrator for further information.'));
      $redirect_url = Url::fromRoute('textbook_companion.list_chapters')->toString();
      $response = new RedirectResponse($redirect_url);
      throw new EnforcedResponseException($response);
    }

    // Robustly extract chapter_id from parameter, query or path fallback.
    if (!$chapter_id) {
      $chapter_id = \Drupal::routeMatch()->getParameter('chapter_id');
    }
    if (!$chapter_id) {
      $chapter_id = \Drupal::request()->query->get('chapter_id');
    }
    if (!$chapter_id) {
      $path = \Drupal::request()->getPathInfo();
      $parts = explode('/', trim($path, '/'));
      $last_part = end($parts);
      if (is_numeric($last_part)) {
        $chapter_id = $last_part;
      }
    }

    if (!$chapter_id) {
      $this->messenger()->addError($this->t('Invalid chapter.'));
      $redirect_url = Url::fromRoute('textbook_companion.list_chapters')->toString();
      $response = new RedirectResponse($redirect_url);
      throw new EnforcedResponseException($response);
    }

    // Query chapter details.
    $query = $this->database->select('textbook_companion_chapter', 'c');
    $query->fields('c');
    $query->condition('id', $chapter_id);
    $query->condition('preference_id', $preference_data->id);
    $chapter_data = $query->execute()->fetchObject();

    if (!$chapter_data) {
      $this->messenger()->addError($this->t('Invalid chapter.'));
      $redirect_url = Url::fromRoute('textbook_companion.list_chapters')->toString();
      $response = new RedirectResponse($redirect_url);
      throw new EnforcedResponseException($response);
    }

    $form['book_details']['book'] = [
      '#type' => 'item',
      '#markup' => $preference_data->book,
      '#title' => $this->t('Title of the Book'),
    ];
    $form['contributor_name'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->full_name,
      '#title' => $this->t('Contributor Name'),
    ];
    $form['number'] = [
      '#type' => 'item',
      '#title' => $this->t('Chapter No'),
      '#markup' => $chapter_data->number,
    ];
    $form['chapter_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title of the Chapter'),
      '#size' => 40,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $chapter_data->name,
    ];
    
    $form['chapter_id'] = [
      '#type' => 'value',
      '#value' => $chapter_id,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('textbook_companion.list_chapters'),
      '#attributes' => [
        'class' => ['button'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (function_exists('check_name')) {
      if (!check_name($form_state->getValue('chapter_title'))) {
        $form_state->setErrorByName('chapter_title', $this->t('Title of the Chapter can contain only alphabets, numbers and spaces.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $chapter_id = $form_state->getValue('chapter_id');

    $this->database->update('textbook_companion_chapter')
      ->fields(['name' => $form_state->getValue('chapter_title')])
      ->condition('id', $chapter_id)
      ->execute();

    $this->messenger()->addStatus($this->t('Title of the Chapter updated.'));
    $form_state->setRedirect('textbook_companion.list_chapters');
  }

}
