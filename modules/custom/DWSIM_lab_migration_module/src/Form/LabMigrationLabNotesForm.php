<?php

/**
 * @file
 * Contains \Drupal\lab_migration\Form\LabMigrationLabNotesForm.
 */

namespace Drupal\lab_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class LabMigrationLabNotesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lab_migration_lab_notes_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    $proposal_id = (int) arg(3);
    //$proposal_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d LIMIT 1", $proposal_id);
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $proposal_id);
    $query->range(0, 1);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    if (!$proposal_data) {
      \Drupal::messenger()->addmesssage(t('Invalid lab selected. Please try again.'), 'error');
      drupal_goto('lab-migration/code-approval');
      return;
    }
    /* get current notes */
    $notes = '';
    //$notes_q = \Drupal::database()->query("SELECT * FROM {lab_migration_notes} WHERE proposal_id = %d LIMIT 1", $proposal_id);
    $query = \Drupal::database()->select('lab_migration_notes');
    $query->fields('lab_migration_notes');
    $query->condition('proposal_id', $proposal_id);
    $query->range(0, 1);
    $notes_q = $query->execute();
    if ($notes_q) {
      $notes_data = $notes_q->fetchObject();
      $notes = $notes_data->notes;
    }
    $form['lab_details'] = [
      '#type' => 'item',
      '#value' => '<span style="color: rgb(128, 0, 0);"><strong>About the Lab</strong></span><br />' . '<strong>Proposer:</strong> ' . $proposal_data->name . '<br />' . '<strong>Title of the Lab:</strong> ' . $proposal_data->lab_title . '<br />',
    ];
    $form['notes'] = [
      '#type' => 'textarea',
      '#rows' => 20,
      '#title' => t('Notes for Reviewers'),
      '#default_value' => $notes,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'markup',
      '#value' => l(t('Back'), 'lab-migration/code-approval'),
    ];
    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    $proposal_id = (int) arg(3);
    //$proposal_q = \Drupal::database()->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d LIMIT 1", $proposal_id);
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $proposal_id);
    $query->range(0, 1);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    if (!$proposal_data) {
      \Drupal::messenger()->addmesssage(t('Invalid lab selected. Please try again.'), 'error');
      drupal_goto('lab-migration/code-approval');
      return;
    }
    /* find existing notes */
    //$notes_q = \Drupal::database()->query("SELECT * FROM {lab_migration_notes} WHERE proposal_id = %d LIMIT 1", $proposal_id);
    $query = \Drupal::database()->select('lab_migration_notes');
    $query->fields('lab_migration_notes');
    $query->condition('proposal_id', $proposal_id);
    $query->range(0, 1);
    $notes_q = $query->execute();
    $notes_data = $notes_q->fetchObject();
    /* add or update notes in database */
    if ($notes_data) {
      $query = "UPDATE {lab_migration_notes} SET notes = :notes WHERE id = :notes_id";
      $args = [
        ":notes" => $form_state->getValue(['notes']),
        ":notes_id" => $notes_data->id,
      ];
      \Drupal::database()->query($query, $args);
      \Drupal::messenger()->addmesssage('Notes updated successfully.', 'status');
    }
    else {
      $query = "INSERT INTO {lab_migration_notes} (proposal_id, notes) VALUES (:proposal_id, :notes)";
      $args = [
        ":proposal_id" => $proposal_id,
        ":notes" => $form_state->getValue(['notes']),
      ];
      \Drupal::database()->query($query, $args);
      \Drupal::messenger()->addmesssage('Notes added successfully.', 'status');
    }
  }

}
?>
