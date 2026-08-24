<?php

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to edit book details for data entry.
 */
class DataentryEditForm extends FormBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new DataentryEditForm object.
   */
  public function __construct(Connection $database, MessengerInterface $messenger) {
    $this->database = $database;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dataentry_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $preference_data = $this->database->select('textbook_companion_preference', 'tcp')
      ->fields('tcp')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();

    if (!$preference_data) {
      $this->messenger->addError($this->t('Invalid book preference ID.'));
      return $form;
    }

    $form['id'] = [
      '#type' => 'hidden',
      '#value' => $id,
    ];

    $form['book'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title of the book'),
      '#size' => 30,
      '#maxlength' => 100,
      '#required' => TRUE,
      '#default_value' => $preference_data->book,
    ];

    $form['author'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Author Name'),
      '#size' => 30,
      '#maxlength' => 100,
      '#required' => TRUE,
      '#default_value' => $preference_data->author,
    ];

    $form['isbn'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ISBN No'),
      '#size' => 30,
      '#maxlength' => 25,
      '#required' => TRUE,
      '#attributes' => [
        'readonly' => 'readonly',
      ],
      '#default_value' => $preference_data->isbn,
    ];

    $form['publisher'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publisher & Place'),
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
      '#default_value' => $preference_data->publisher,
    ];

    $form['edition'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Edition'),
      '#size' => 4,
      '#maxlength' => 2,
      '#required' => TRUE,
      '#default_value' => $preference_data->edition,
    ];

    $form['year'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Year of publication'),
      '#size' => 4,
      '#maxlength' => 4,
      '#required' => TRUE,
      '#default_value' => $preference_data->year,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->database->update('textbook_companion_preference')
      ->fields([
        'book' => $values['book'],
        'author' => $values['author'],
        'isbn' => $values['isbn'],
        'publisher' => $values['publisher'],
        'edition' => $values['edition'],
        'year' => $values['year'],
      ])
      ->condition('id', $values['id'])
      ->execute();

    $this->messenger->addStatus($this->t('Book details updated successfully'));
    $form_state->setRedirect('textbook_companion._data_entry_proposal_all');
  }

}
