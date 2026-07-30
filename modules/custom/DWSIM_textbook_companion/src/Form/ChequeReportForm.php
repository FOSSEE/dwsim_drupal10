<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\ChequeReportForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\EnforcedResponseException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Form that streams a CSV report of submitted cheque details.
 */
class ChequeReportForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a ChequeReportForm object.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cheque_report_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $query = $this->database->select('textbook_companion_proposal', 'p');
    $query->join('textbook_companion_cheque', 'c', 'p.id = c.proposal_id');
    $query->fields('p', ['full_name']);
    $query->fields('c', ['address_con', 'cheque_no', 'cheque_dispatch_date']);
    $query->condition('c.address_con', 'Submitted');
    $result = $query->execute();

    $rows = [];
    while ($search_data = $result->fetchObject()) {
      $rows[] = [
        $search_data->full_name,
        $search_data->address_con,
        $search_data->cheque_no,
        $search_data->cheque_dispatch_date,
      ];
    }

    if (empty($rows)) {
      throw new EnforcedResponseException(
        new StreamedResponse(function () {
          echo "Couldn't fetch records";
        }, 200, ['Content-Type' => 'text/plain'])
      );
    }

    $headers = [
      'Name Of The Student',
      'Application Form Status',
      'Cheque No',
      'Cheque Clearance Date',
    ];

    $response = new StreamedResponse(function () use ($headers, $rows) {
      $handle = fopen('php://output', 'w');
      fputcsv($handle, $headers);
      foreach ($rows as $row) {
        fputcsv($handle, $row);
      }
      fclose($handle);
    });

    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="Report.csv"');
    $response->headers->set('Pragma', 'no-cache');
    $response->headers->set('Expires', '0');

    throw new EnforcedResponseException($response);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
