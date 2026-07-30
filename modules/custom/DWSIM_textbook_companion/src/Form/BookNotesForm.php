<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\BookNotesForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\EnforcedResponseException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Link;

class BookNotesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'book_notes_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $preference_id = NULL) {
    if (empty($preference_id)) {
      $preference_id = \Drupal::routeMatch()->getParameter('preference_id')
        ?? \Drupal::request()->attributes->get('preference_id')
        ?? \Drupal::request()->query->get('preference_id');
    }
    $preference_id = (int) $preference_id;

    if (empty($preference_id)) {
      $this->messenger()->addError($this->t('Invalid book selected. Please try again.'));
      $url = Url::fromRoute('textbook_companion.bulk_approval_form')->toString();
      throw new EnforcedResponseException(new RedirectResponse($url));
    }

    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $preference_id);
    $result = $query->execute();
    if ($result) {
      if ($row = $result->fetchObject()) {
        /* everything ok */
      }
      else {
        $this->messenger()->addError($this->t('Invalid book selected. Please try again.'));
        $url = Url::fromRoute('textbook_companion.bulk_approval_form')->toString();
        throw new EnforcedResponseException(new RedirectResponse($url));
      }
    }
    else {
      $this->messenger()->addError($this->t('Invalid book selected. Please try again.'));
      $url = Url::fromRoute('textbook_companion.bulk_approval_form')->toString();
      throw new EnforcedResponseException(new RedirectResponse($url));
    }

    $form_state->set('preference_id', $preference_id);

    /* get current notes */
    $notes = '';
    $query = \Drupal::database()->select('textbook_companion_notes');
    $query->fields('textbook_companion_notes');
    $query->condition('preference_id', $preference_id);
    $query->range(0, 1);
    $notes_q = $query->execute();
    if ($notes_q) {
      $notes_data = $notes_q->fetchObject();
      if ($notes_data) {
        $notes = $notes_data->notes;
      }
    }

    $book_details = $this->bookInformation($preference_id);
    if ($book_details) {
      $form['book_details'] = [
        '#type' => 'item',
        '#markup' => '<span style="color: rgb(128, 0, 0);"><strong>About the Book</strong></span><br />' .
          '<strong>Author:</strong> ' . $book_details->author . '<br />' .
          '<strong>Title of the Book:</strong> ' . $book_details->book . '<br />' .
          '<strong>Publisher:</strong> ' . $book_details->publisher . '<br />' .
          '<strong>Year:</strong> ' . $book_details->year . '<br />' .
          '<strong>Edition:</strong> ' . $book_details->edition . '<br /><br />' .
          '<span style="color: rgb(128, 0, 0);"><strong>About the Contributor</strong></span><br />' .
          '<strong>Contributor Name:</strong> ' . $book_details->full_name . ', ' . $book_details->course . ', ' . $book_details->branch . ', ' . $book_details->university . '<br />',
      ];
    }

    $form['notes'] = [
      '#type' => 'textarea',
      '#rows' => 20,
      '#title' => $this->t('Notes for Reviewers'),
      '#default_value' => $notes,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl($this->t('Back'), Url::fromRoute('textbook_companion.bulk_approval_form'))->toString(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $preference_id = $form_state->get('preference_id');

    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('id', $preference_id);
    $result = $query->execute();
    if ($result) {
      if ($row = $result->fetchObject()) {
        /* everything ok */
      }
      else {
        $this->messenger()->addError($this->t('Invalid book selected. Please try again.'));
        $form_state->setRedirect('textbook_companion.bulk_approval_form');
        return;
      }
    }
    else {
      $this->messenger()->addError($this->t('Invalid book selected. Please try again.'));
      $form_state->setRedirect('textbook_companion.bulk_approval_form');
      return;
    }

    /* find existing notes */
    $query = \Drupal::database()->select('textbook_companion_notes');
    $query->fields('textbook_companion_notes');
    $query->condition('preference_id', $preference_id);
    $query->range(0, 1);
    $notes_q = $query->execute();
    $notes_data = $notes_q->fetchObject();

    /* add or update notes in database */
    if ($notes_data) {
      $query = \Drupal::database()->update('textbook_companion_notes');
      $query->fields(['notes' => $form_state->getValue('notes')]);
      $query->condition('id', $notes_data->id);
      $query->execute();
      $this->messenger()->addMessage($this->t('Notes updated successfully.'), 'status');
    }
    else {
      \Drupal::database()->insert('textbook_companion_notes')
        ->fields([
          'preference_id' => $preference_id,
          'notes' => $form_state->getValue('notes'),
        ])
        ->execute();
      $this->messenger()->addMessage($this->t('Notes added successfully.'), 'status');
    }

    $form_state->setRedirect('textbook_companion.bulk_approval_form');
  }

  /**
   * Helper to retrieve book information.
   */
  protected function bookInformation($preference_id) {
    $query = \Drupal::database()->select('textbook_companion_proposal', 'proposal');
    $query->leftJoin('textbook_companion_preference', 'preference', 'proposal.id = preference.proposal_id');
    $query->fields('preference', [
      'book',
      'author',
      'isbn',
      'publisher',
      'edition',
      'year',
    ]);
    $query->fields('proposal', [
      'full_name',
      'faculty',
      'reviewer',
      'course',
      'branch',
      'university',
    ]);
    $query->condition('preference.id', $preference_id);
    return $query->execute()->fetchObject();
  }

}
