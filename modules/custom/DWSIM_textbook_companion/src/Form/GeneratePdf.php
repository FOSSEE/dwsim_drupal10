<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\GeneratePdf.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates and downloads the textbook companion participation certificate.
 */
class GeneratePdf extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleList;

  /**
   * Constructs a GeneratePdf form object.
   */
  public function __construct(
    Connection $connection,
    MessengerInterface $messenger,
    AccountInterface $currentUser,
    RouteMatchInterface $routeMatch,
    ModuleExtensionList $moduleList
  ) {
    $this->connection = $connection;
    $this->messenger = $messenger;
    $this->currentUser = $currentUser;
    $this->routeMatch = $routeMatch;
    $this->moduleList = $moduleList;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('messenger'),
      $container->get('current_user'),
      $container->get('current_route_match'),
      $container->get('extension.list.module')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'generate_pdf';
  }

  /**
   * Resolves proposal ID from route, query, or path.
   */
  protected function resolveProposalId($proposal_id = NULL) {
    if (empty($proposal_id)) {
      $proposal_id = \Drupal::request()->query->get('proposal_id')
        ?? $this->routeMatch->getParameter('proposal_id');
    }

    if (empty($proposal_id)) {
      $parts = explode('/', trim(\Drupal::request()->getPathInfo(), '/'));
      $last = end($parts);
      if (is_numeric($last)) {
        $proposal_id = (int) $last;
      }
    }

    return $proposal_id;
  }

  /**
   * Generates a random string for QR code verification.
   */
  private function generateRandomString($length = 5) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $proposal_id = NULL) {
    $mpath = $this->moduleList->getPath('textbook_companion');
    require $mpath . '/pdf/fpdf/fpdf.php';
    require $mpath . '/pdf/phpqrcode/qrlib.php';

    $uid = $this->currentUser->id();
    $proposal_id = $this->resolveProposalId($proposal_id);

    $data2 = $this->connection->select('textbook_companion_preference', 'p')
      ->fields('p')
      ->condition('approval_status', 1)
      ->condition('proposal_id', $proposal_id)
      ->execute()
      ->fetchObject();

    $data3 = $this->connection->select('textbook_companion_proposal', 'p')
      ->fields('p')
      ->condition('id', $proposal_id)
      ->condition('proposal_status', 3)
      ->execute()
      ->fetchObject();

    if ($data3) {
      if ($data3->uid != $uid) {
        $this->messenger->addError($this->t('Certificate is not available'));
        return [];
      }
    }

    $data4 = $this->connection->query(
      "SELECT COUNT(tce.id) AS example_count
       FROM {textbook_companion_example} tce
       LEFT JOIN {textbook_companion_chapter} tcc ON tce.chapter_id = tcc.id
       LEFT JOIN {textbook_companion_preference} tcpe ON tcc.preference_id = tcpe.id
       LEFT JOIN {textbook_companion_proposal} tcpo ON tcpe.proposal_id = tcpo.id
       WHERE tcpo.proposal_status = 3
         AND tce.approval_status = 1
         AND tcpo.id = :prop_id",
      [':prop_id' => $proposal_id]
    )->fetchObject();

    if (!$data4 || $data4->example_count == 0) {
      $this->messenger->addError($this->t('Certificate is not available'));
      return [];
    }

    $number_of_example = $data4->example_count;
    $gender = [
      'salutation' => 'Mr. /Ms.',
      'gender' => 'He/She',
    ];
    if ($data3 && $data3->gender) {
      if ($data3->gender == 'M') {
        $gender = [
          'salutation' => 'Mr.',
          'gender' => 'He',
        ];
      }
      else {
        $gender = [
          'salutation' => 'Ms.',
          'gender' => 'She',
        ];
      }
    }

    $pdf = new \FPDF('L', 'mm', 'Letter');
    $pdf->AddPage();
    $image_bg = $mpath . '/pdf/images/bg.png';
    $pdf->Image($image_bg, 0, 0, $pdf->w, $pdf->h);
    $pdf->SetMargins(18, 1, 18);
    $path = $mpath;
    $pdf->Ln(15);
    $pdf->Ln(20);
    $pdf->SetFont('Arial', 'BI', 25);
    $pdf->SetTextColor(139, 69, 19);
    $pdf->Cell(240, 8, 'Certificate of Participation', '0', 1, 'C');
    $pdf->Ln(4);
    $pdf->SetFont('Arial', 'BI', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(240, 8, 'This is to certify that', '0', '1', 'C');
    $pdf->Ln(4);
    $pdf->SetFont('Arial', 'BI', 25);
    $pdf->SetTextColor(139, 69, 19);
    $pdf->Cell(240, 8, $data3->full_name, '0', '1', 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'I', 12);
    if (strtolower($data3->branch) != 'others') {
      $pdf->SetTextColor(0, 0, 0);
      $pdf->MultiCell(240, 8, 'from ' . $data3->university . ' has successfully', '0', 'C');
      $pdf->Ln(0);
      $pdf->Cell(240, 8, 'completed Internship under DWSIM Textbook Companion', '0', '1', 'C');
      $pdf->Ln(0);
      $pdf->Cell(240, 8, 'He/she has coded ' . $number_of_example . ' solved examples using DWSIM from the', '0', '1', 'C');
      $pdf->Ln(0);
      $pdf->MultiCell(240, 8, 'Book: ' . $data2->book . ', Author: ' . $data2->author . '.', '0', 'C');
      $pdf->Ln(0);
    }
    else {
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(240, 8, 'from ' . $data3->university . ' has successfully', '0', '1', 'C');
      $pdf->Ln(0);
      $pdf->Cell(240, 8, 'completed Internship under DWSIM Textbook Companion', '0', '1', 'C');
      $pdf->Ln(0);
      $pdf->Cell(240, 8, 'He/she has coded ' . $number_of_example . ' solved examples using DWSIM from the', '0', '1', 'C');
      $pdf->Ln(0);
      $pdf->Cell(240, 8, 'Book: ' . $data2->book . ', Author: ' . $data2->author . '.', '0', '1', 'C');
      $pdf->Ln(0);
    }

    $tempDir = $path . '/pdf/temp_prcode/';
    $data = $this->connection->select('textbook_companion_qr_code', 'q')
      ->fields('q')
      ->condition('proposal_id', $proposal_id)
      ->execute()
      ->fetchObject();

    $DBString = $data ? $data->qr_code : '';
    if ($DBString == '' || $DBString == 'null') {
      $UniqueString = $this->generateRandomString();
      $this->connection->insert('textbook_companion_qr_code')
        ->fields([
          'proposal_id' => $proposal_id,
          'qr_code' => $UniqueString,
        ])
        ->execute();
    }
    else {
      $UniqueString = $DBString;
    }

    $codeContents = 'http://dwsim.fossee.in/textbook-companion/certificates/verify/' . $UniqueString;
    $fileName = 'generated_qrcode.png';
    $pngAbsoluteFilePath = $tempDir . $fileName;
    \QRcode::png($codeContents, $pngAbsoluteFilePath);
    $pdf->Cell(240, 4, '', '0', '1', 'C');
    $pdf->SetX(95);
    $pdf->write(0, 'The work done is available at ');
    $pdf->SetFont('', 'U');
    $pdf->SetTextColor(139, 69, 19);
    $pdf->write(0, 'http://dwsim.fossee.in/', 'http://dwsim.fossee.in/');
    $pdf->SetFont('', '');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->write(0, '.', '.');
    $pdf->Ln(5);
    $pdf->SetX(198);
    $pdf->SetFont('', '');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetY(-85);
    $pdf->SetX(200);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('', '');
    $sign = $path . '/pdf/images/sign.png';
    $pdf->Image($sign, $pdf->GetX() - 20, $pdf->GetY(), 75, 0);
    $pdf->SetX(29);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetY(-58);
    $pdf->SetX(28);
    $pdf->Cell(0, 2, $UniqueString, 0, 0, 'C');
    $pdf->SetX(29);
    $pdf->SetY(-50);
    $image4 = $path . '/pdf/images/verify_content.png';
    $pdf->SetY(-50);
    $pdf->SetX(80);
    $image3 = $path . '/pdf/images/mhrd.png';
    $image2 = $path . '/pdf/images/fossee.png';
    $pdf->Image($image2, $pdf->GetX() - 15, $pdf->GetY() + 7, 40, 0);
    $pdf->Image($pngAbsoluteFilePath, $pdf->GetX() + 50, $pdf->GetY() - 5, 30, 0);
    $pdf->Image($image3, $pdf->GetX() + 110, $pdf->GetY() + 3, 40, 0);
    $pdf->Image($image4, $pdf->GetX() - 15, $pdf->GetY() + 28, 150, 0);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetTextColor(0, 0, 0);
    $filename = str_replace(' ', '-', $data3->full_name) . '-DWSIM-Textbook-Certificate.pdf';
    $file = $path . '/pdf/temp_certificate/' . $proposal_id . '_' . $filename;
    $pdf->Output($file, 'F');

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Content-Type: application/download');
    header('Content-Description: File Transfer');
    header('Content-Length: ' . filesize($file));
    flush();
    $fp = fopen($file, 'r');
    while (!feof($fp)) {
      echo fread($fp, 65536);
      flush();
    }
    fclose($fp);
    unlink($file);

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
