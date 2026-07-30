<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Controller\DefaultController.
 */

namespace Drupal\textbook_companion\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\textbook_companion\Services\MailService;
use Drupal\textbook_companion\Services\TextbookCompanionGlobalFunction;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Default controller for the textbook_companion module.
 */
class DefaultController extends ControllerBase {

  protected $database;
  protected $messenger;
  protected $currentUser;
  protected $entityTypeManager;
  protected $mailService;
  protected $configFactory;
  protected $formBuilder;
  protected $globalService;
  protected $requestStack;
  protected $loggerFactory;
  protected $fileSystem;

  public function __construct(
    Connection $database,
    MessengerInterface $messenger,
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    MailService $mail_service,
    ConfigFactoryInterface $config_factory,
    FormBuilderInterface $form_builder,
    TextbookCompanionGlobalFunction $global_service,
    RequestStack $request_stack,
    LoggerChannelFactoryInterface $logger_factory,
    FileSystemInterface $file_system
  ) {
    $this->database = $database;
    $this->messenger = $messenger;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->mailService = $mail_service;
    $this->configFactory = $config_factory;
    $this->formBuilder = $form_builder;
    $this->globalService = $global_service;
    $this->requestStack = $request_stack;
    $this->loggerFactory = $logger_factory;
    $this->fileSystem = $file_system;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('messenger'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('textbook_companion.mail_service'),
      $container->get('config.factory'),
      $container->get('form_builder'),
      $container->get('textbook_companion_global'),
      $container->get('request_stack'),
      $container->get('logger.factory'),
      $container->get('file_system')
    );
  }

  /**
   * Helper: build a render array table from header and rows.
   */
  protected function buildTable(array $header, array $rows): array {
    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No records found.'),
    ];
  }

  /**
   * Helper: get proposal status label.
   */
  protected function proposalStatusLabel(int $status): string {
    return [
      0 => 'Pending',
      1 => 'Approved',
      2 => 'Dis-approved',
      3 => 'Completed',
      4 => 'External',
      5 => 'Submitted all codes',
    ][$status] ?? 'Unknown';
  }

  /**
   * Helper: load the latest proposal for the current user.
   */
  protected function loadLatestUserProposal() {
    return $this->database->select('textbook_companion_proposal', 'p')
      ->fields('p')
      ->condition('uid', $this->currentUser->id())
      ->orderBy('id', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchObject();
  }

  /**
   * Helper: redirect to front page as a render array with a status message.
   */
  protected function redirectFrontRender(): RedirectResponse {
    return new RedirectResponse(Url::fromRoute('<front>')->toString());
  }

  public function textbook_companion_proposal_all() {
    if (!$this->currentUser->isAuthenticated()) {
      $this->messenger->addError($this->t('It is mandatory to login on this website to access the proposal form.'));
      return $this->redirectFrontRender();
    }
    $proposal_data = $this->loadLatestUserProposal();
    if ($proposal_data) {
      $code_link = Link::fromTextAndUrl($this->t('Code Submission'), Url::fromRoute('textbook_companion.list_chapters'))->toString();
      $proposal_link = Link::fromTextAndUrl($this->t('here'), Url::fromRoute('textbook_companion.proposal_all'))->toString();
      switch ($proposal_data->proposal_status) {
        case 0:
          $this->messenger->addStatus($this->t('We have already received your proposal. We will get back to you soon.'));
          return $this->redirectFrontRender();
        case 1:
          $this->messenger->addStatus($this->t('Your proposal has been approved. Please go to @link to upload your code.', ['@link' => $code_link]));
          return $this->redirectFrontRender();
        case 2:
          $this->messenger->addError($this->t('Your proposal has been dis-approved. Please create another proposal below.'));
          break;
        case 3:
          $this->messenger->addStatus($this->t('Congratulations! You have completed your last book proposal. You can create another proposal below.'));
          break;
        default:
          $this->messenger->addError($this->t('Invalid proposal state. Please contact site administrator.'));
          return $this->redirectFrontRender();
      }
    }
    return $this->formBuilder->getForm('Drupal\textbook_companion\Form\TextbookCompanionProposalForm');
  }

  public function textbook_companion_aicte_proposal_all() {
    if (!$this->currentUser->isAuthenticated()) {
      // Anonymous: show list of available books.
      $result = $this->database->select('textbook_companion_aicte', 'a')
        ->fields('a')->condition('status', 0)->execute();
      $items = [];
      foreach ($result as $row) {
        $edition = $row->edition ? '<i>ed</i>: ' . $row->edition : '';
        $year = $row->year ? ($edition ? ', ' : '') . '<i>pub</i>: ' . $row->year : '';
        $suffix = ($edition || $year) ? " ({$edition}{$year})" : '';
        $items[] = $row->book . ' by ' . $row->author . $suffix;
      }
      return [
        '#theme' => 'item_list',
        '#items' => $items,
        '#prefix' => '<p>' . $this->t('Please <a href="/user">Login</a> to create a proposal.') . '</p>',
      ];
    }
    $proposal_data = $this->loadLatestUserProposal();
    if ($proposal_data) {
      $code_link = Link::fromTextAndUrl($this->t('Code Submission'), Url::fromRoute('textbook_companion.list_chapters'))->toString();
      switch ($proposal_data->proposal_status) {
        case 0:
          $this->messenger->addStatus($this->t('We have already received your proposal. We will get back to you soon.'));
          return $this->redirectFrontRender();
        case 1:
          $this->messenger->addStatus($this->t('Your proposal has been approved. Please go to @link to upload your code.', ['@link' => $code_link]));
          return $this->redirectFrontRender();
        case 2:
          $this->messenger->addError($this->t('Your proposal has been dis-approved. Please create another proposal below.'));
          break;
        case 3:
          $this->messenger->addStatus($this->t('Congratulations! You have completed your last book proposal. You can create another proposal below.'));
          break;
        default:
          $this->messenger->addError($this->t('Invalid proposal state. Please contact site administrator.'));
          return $this->redirectFrontRender();
      }
    }
    return [
      '#markup' => '<h5><b>* Please select any 3 books from the below list.</b></h5>',
    ];
  }

  public function _proposal_pending() {
    $rows = [];
    $pending_q = $this->database->select('textbook_companion_proposal', 'p')
      ->fields('p')->condition('proposal_status', 0)->orderBy('id', 'DESC')->execute();
    while ($d = $pending_q->fetchObject()) {
      $approve_url = Url::fromRoute('textbook_companion.proposal_approval_form', [], ['query' => ['proposal_id' => $d->id]]);
      $edit_url = Url::fromRoute('textbook_companion.proposal_edit_form', [], ['query' => ['proposal_id' => $d->id]]);
      $rows[] = [
        date('d-m-Y', $d->creation_date),
        Link::fromTextAndUrl($d->full_name, Url::fromRoute('entity.user.canonical', ['user' => $d->uid]))->toString(),
        date('d-m-Y', $d->proposed_completion_date),
        Link::fromTextAndUrl($this->t('Approve'), $approve_url)->toString() . ' | ' . Link::fromTextAndUrl($this->t('Edit'), $edit_url)->toString(),
      ];
    }
    if (!$rows) {
      $this->messenger->addStatus($this->t('There are no pending proposals.'));
      return ['#markup' => ''];
    }
    return $this->buildTable(['Date of Submission', 'Contributor Name', 'Proposed Date of Completion', 'Action'], $rows);
  }

  public function _proposal_all() {
    $rows = [];
    $proposal_q = $this->database->select('textbook_companion_proposal', 'p')
      ->fields('p')->orderBy('id', 'DESC')->execute();
    while ($d = $proposal_q->fetchObject()) {
      $pref = $this->database->select('textbook_companion_preference', 'p')
        ->fields('p')->condition('proposal_id', $d->id)->condition('approval_status', 1)->range(0, 1)->execute()->fetchObject();
      if (!$pref) {
        $pref = $this->database->select('textbook_companion_preference', 'p')
          ->fields('p')->condition('proposal_id', $d->id)->condition('pref_number', 1)->range(0, 1)->execute()->fetchObject();
      }
      $status_label = $this->proposalStatusLabel($d->proposal_status);
      $proposed = $d->proposed_completion_date ? date('d-m-Y', $d->proposed_completion_date) : '-----';
      $status_url = Url::fromRoute('textbook_companion.proposal_status_form', [], ['query' => ['proposal_id' => $d->id]]);
      $edit_url   = Url::fromRoute('textbook_companion.proposal_edit_form', [], ['query' => ['proposal_id' => $d->id]]);
      $rows[] = [
        date('d-m-Y', $d->creation_date),
        ($pref ? $pref->book . '<br><em>by ' . $pref->author . '</em>' : ''),
        Link::fromTextAndUrl($d->full_name, Url::fromRoute('entity.user.canonical', ['user' => $d->uid]))->toString(),
        date('d-m-Y', $d->completion_date),
        $proposed,
        $status_label,
        Link::fromTextAndUrl($this->t('Status'), $status_url)->toString() . ' | ' . Link::fromTextAndUrl($this->t('Edit'), $edit_url)->toString(),
      ];
    }
    if (!$rows) {
      $this->messenger->addStatus($this->t('There are no proposals.'));
      return ['#markup' => ''];
    }
    return $this->buildTable(['Date of Submission', 'Title of the Book', 'Contributor Name', 'Actual Date of Completion', 'Proposed Date of Completion', 'Status', 'Action'], $rows);
  }

  public function _failed_all($preference_id = 0, $confirm = '') {
    $page_content = '';
    if ($preference_id && $confirm === 'yes') {
      $row = $this->database->select('textbook_companion_proposal', 'pro')
        ->fields('pro')
        ->leftJoin('textbook_companion_preference', 'pre', 'pre.proposal_id = pro.id');
      // Rebuild as proper query object.
      $q = $this->database->select('textbook_companion_preference', 'pre');
      $q->fields('pre');
      $q->leftJoin('textbook_companion_proposal', 'pro', 'pro.id = pre.proposal_id');
      $q->condition('pre.id', $preference_id);
      $row = $q->execute()->fetchObject();
      if ($row) {
        $this->database->update('textbook_companion_proposal')
          ->expression('failed_reminder', 'failed_reminder + 1')
          ->condition('id', $row->proposal_id ?? $row->id)
          ->execute();
        // Send reminder email via MailService.
        $params['failed_reminder']['preference_id'] = $preference_id;
        $this->mailService->sendMail('textbook_companion', 'failed_reminder', $row->mail ?? '', $params);
        $this->messenger->addStatus($this->t('Reminder sent successfully.'));
      }
      return new RedirectResponse(Url::fromRoute('textbook_companion.failed_all')->toString());
    }
    if ($preference_id) {
      $row = $this->database->select('textbook_companion_preference', 'pre')
        ->fields('pre')
        ->condition('pre.id', $preference_id)
        ->execute()->fetchObject();
      if ($row) {
        $yes_url = Url::fromRoute('textbook_companion.failed_all', ['preference_id' => $preference_id, 'confirm' => 'yes']);
        $cancel_url = Url::fromRoute('textbook_companion.failed_all');
        $page_content .= '<p>' . $this->t('Are you sure you want to notify?') . '</p>';
        $page_content .= '<strong>' . $this->t('Book:') . '</strong> ' . $row->book . '<br>';
        $page_content .= '<strong>' . $this->t('Author:') . '</strong> ' . $row->author . '<br>';
        $page_content .= Link::fromTextAndUrl($this->t('Yes'), $yes_url)->toString() . ' | ' . Link::fromTextAndUrl($this->t('Cancel'), $cancel_url)->toString();
      }
    }
    else {
      $result = $this->database->select('textbook_companion_proposal', 'pro')
        ->fields('pro')
        ->condition('pro.proposal_status', 1)
        ->condition('pro.completion_date', time(), '<')
        ->orderBy('failed_reminder', 'ASC')
        ->execute();
      $rows = [];
      foreach ($result as $row) {
        $remind_url = Url::fromRoute('textbook_companion.failed_all', ['preference_id' => $row->id]);
        $rows[] = [
          date('d-m-Y', $row->creation_date),
          $row->full_name,
          date('d-m-Y', $row->completion_date),
          $row->failed_reminder,
          Link::fromTextAndUrl($this->t('Remind'), $remind_url)->toString(),
        ];
      }
      return $this->buildTable(['Date of Submission', 'Contributor Name', 'Expected Completion Date', 'Reminders', 'Action'], $rows);
    }
    return ['#markup' => $page_content];
  }

  public function code_approval() {
    $pending_q = $this->database->select('textbook_companion_example', 'e')
      ->fields('e', [])
      ->condition('e.approval_status', 0)
      ->execute();
    // We need chapter fields too — use a join.
    $q = $this->database->select('textbook_companion_example', 'e');
    $q->addField('c', 'id', 'c_id');
    $q->addField('c', 'number', 'c_number');
    $q->addField('c', 'name', 'c_name');
    $q->addField('c', 'preference_id', 'c_preference_id');
    $q->innerJoin('textbook_companion_chapter', 'c', 'c.id = e.chapter_id');
    $q->condition('e.approval_status', 0);
    $results = $q->execute();
    $rows = [];
    foreach ($results as $row) {
      $pref = $this->database->select('textbook_companion_preference', 'p')
        ->fields('p')->condition('id', $row->c_preference_id)->execute()->fetchObject();
      $proposal = $pref ? $this->database->select('textbook_companion_proposal', 'pr')
        ->fields('pr')->condition('id', $pref->proposal_id)->execute()->fetchObject() : NULL;
      $edit_url = Url::fromRoute('textbook_companion.code_approval_form', [], ['query' => ['chapter_id' => $row->c_id]]);
      $rows[] = [
        $pref ? $pref->book : '',
        $row->c_number,
        $row->c_name,
        $proposal ? $proposal->full_name : '',
        Link::fromTextAndUrl($this->t('Edit'), $edit_url)->toString(),
      ];
    }
    if (!$rows) {
      $this->messenger->addStatus($this->t('There are no pending code approvals.'));
      return ['#markup' => ''];
    }
    return $this->buildTable(['Title of the Book', 'Chapter Number', 'Title of the Chapter', 'Contributor Name', 'Actions'], $rows);
  }

  public function list_chapters() {
    $proposal_data = $this->loadLatestUserProposal();
    $proposal_link = Link::fromTextAndUrl($this->t('proposal'), Url::fromRoute('textbook_companion.proposal_all'))->toString();
    if (!$proposal_data) {
      $this->messenger->addError($this->t('Please submit a @link.', ['@link' => $proposal_link]));
      return $this->redirectFrontRender();
    }
    if (!in_array($proposal_data->proposal_status, [1, 4])) {
      switch ($proposal_data->proposal_status) {
        case 0:
          $this->messenger->addStatus($this->t('We have already received your proposal. We will get back to you soon.'));
          break;
        case 2:
          $this->messenger->addError($this->t('Your proposal has been dis-approved. Please create another proposal @link.', ['@link' => $proposal_link]));
          break;
        case 3:
          $this->messenger->addStatus($this->t('Congratulations! You have completed your last book proposal. You can create another proposal @link.', ['@link' => $proposal_link]));
          break;
        default:
          $this->messenger->addError($this->t('Invalid proposal state. Please contact site administrator.'));
      }
      return $this->redirectFrontRender();
    }
    $preference_data = $this->database->select('textbook_companion_preference', 'p')
      ->fields('p')->condition('proposal_id', $proposal_data->id)->condition('approval_status', 1)->range(0, 1)->execute()->fetchObject();
    if (!$preference_data) {
      $this->messenger->addError($this->t('Invalid Book Preference status. Please contact site administrator.'));
      return $this->redirectFrontRender();
    }
    if ($preference_data->submited_all_examples_code == 1) {
      $this->messenger->addStatus($this->t('You have already submitted all codes for this book for review. You cannot upload more code.'));
      return $this->redirectFrontRender();
    }
    $upload_url = Url::fromRoute('textbook_companion.upload_examples');
    $build = [
      '#markup' => '<br><strong>' . $this->t('Title of the Book:') . '</strong><br>' . $preference_data->book
        . '<br><br><strong>' . $this->t('Contributor Name:') . '</strong><br>' . $proposal_data->full_name
        . '<br><br>' . Link::fromTextAndUrl($this->t('Upload Example Code'), $upload_url)->toString() . '<br>',
    ];
    $chapter_rows = [];
    $chapter_q = $this->database->select('textbook_companion_chapter', 'c')
      ->fields('c')->condition('preference_id', $preference_data->id)->orderBy('number', 'ASC')->execute();
    while ($chapter_data = $chapter_q->fetchObject()) {
      $example_count = $this->database->select('textbook_companion_example', 'e')
        ->condition('chapter_id', $chapter_data->id)->countQuery()->execute()->fetchField();
      $edit_url = Url::fromRoute('textbook_companion.edit_chapter_title_form', [], ['query' => ['chapter_id' => $chapter_data->id]]);
      $view_url = Url::fromRoute('textbook_companion.list_examples', ['chapter_id' => $chapter_data->id]);
      $chapter_rows[] = [
        $chapter_data->number,
        $chapter_data->name . ' (' . Link::fromTextAndUrl($this->t('Edit'), $edit_url)->toString() . ')',
        $example_count,
        Link::fromTextAndUrl($this->t('View'), $view_url)->toString(),
      ];
    }
    if (!$chapter_rows) {
      $this->messenger->addStatus($this->t('No uploads found.'));
      return $build;
    }
    $build['table'] = $this->buildTable(['Chapter No.', 'Title of the Chapter', 'Uploaded Examples', 'Actions'], $chapter_rows);
    return $build;
  }

  public function upload_examples() {
    return $this->formBuilder->getForm('Drupal\textbook_companion\Form\UploadExamplesForm');
  }


  public function _upload_examples_delete($example_id = NULL) {
    if (!$example_id) {
      $example_id = $this->requestStack->getCurrentRequest()->query->get('example_id');
    }
    $uid = $this->currentUser->id();
    $root_path = $this->globalService->textbook_companion_path();

    $example_data = $this->database->select('textbook_companion_example', 'e')
      ->fields('e')->condition('id', $example_id)->range(0, 1)->execute()->fetchObject();
    if (!$example_data) {
      $this->messenger->addError($this->t('Invalid example.'));
      return $this->redirect('textbook_companion.list_chapters');
    }
    if ($example_data->approval_status != 0) {
      $this->messenger->addError($this->t('You cannot delete an approved example. Please contact site administrator.'));
      return $this->redirect('textbook_companion.list_chapters');
    }
    $chapter_data = $this->database->select('textbook_companion_chapter', 'c')
      ->fields('c')->condition('id', $example_data->chapter_id)->range(0, 1)->execute()->fetchObject();
    $preference_data = $chapter_data ? $this->database->select('textbook_companion_preference', 'p')
      ->fields('p')->condition('id', $chapter_data->preference_id)->range(0, 1)->execute()->fetchObject() : NULL;
    $proposal_data = $preference_data ? $this->database->select('textbook_companion_proposal', 'pr')
      ->fields('pr')->condition('id', $preference_data->proposal_id)->condition('uid', $uid)->range(0, 1)->execute()->fetchObject() : NULL;
    if (!$proposal_data) {
      $this->messenger->addError($this->t('You do not have permission to delete this example.'));
      return $this->redirect('textbook_companion.list_chapters');
    }
    if (function_exists('delete_example') && delete_example($example_data->id)) {
      $this->messenger->addStatus($this->t('Example deleted.'));
      $params['example_deleted_user']['book_title'] = $preference_data->book;
      $params['example_deleted_user']['chapter_title'] = $chapter_data->name;
      $params['example_deleted_user']['example_number'] = $example_data->number;
      $params['example_deleted_user']['user_id'] = $uid;
      $email_to = $this->currentUser->getEmail();
      if (!$this->mailService->sendMail('textbook_companion', 'example_deleted_user', $email_to, $params)) {
        $this->messenger->addError($this->t('Error sending email message.'));
      }
    }
    else {
      $this->messenger->addError($this->t('Error deleting example.'));
    }
    return $this->redirect('textbook_companion.list_chapters');
  }

  public function list_examples($chapter_id = NULL) {
    if (!$chapter_id) {
      $chapter_id = $this->requestStack->getCurrentRequest()->query->get('chapter_id');
    }
    $proposal_data = $this->loadLatestUserProposal();
    $proposal_link = Link::fromTextAndUrl($this->t('proposal'), Url::fromRoute('textbook_companion.proposal_all'))->toString();
    if (!$proposal_data) {
      $this->messenger->addError($this->t('Please submit a @link.', ['@link' => $proposal_link]));
      return $this->redirectFrontRender();
    }
    if (!in_array($proposal_data->proposal_status, [1, 4])) {
      $this->messenger->addError($this->t('Your proposal is not in an approved state.'));
      return $this->redirectFrontRender();
    }
    $preference_data = $this->database->select('textbook_companion_preference', 'p')
      ->fields('p')->condition('proposal_id', $proposal_data->id)->condition('approval_status', 1)->range(0, 1)->execute()->fetchObject();
    if (!$preference_data) {
      $this->messenger->addError($this->t('Invalid Book Preference status. Please contact site administrator.'));
      return $this->redirectFrontRender();
    }
    $chapter_data = $this->database->select('textbook_companion_chapter', 'c')
      ->fields('c')->condition('id', $chapter_id)->condition('preference_id', $preference_data->id)->range(0, 1)->execute()->fetchObject();
    if (!$chapter_data) {
      $this->messenger->addError($this->t('Invalid chapter.'));
      return $this->redirect('textbook_companion.list_chapters');
    }
    $back_url = Url::fromRoute('textbook_companion.list_chapters');
    $build = [
      '#markup' => '<br><strong>' . $this->t('Title of the Book:') . '</strong><br>' . $preference_data->book
        . '<br><br><strong>' . $this->t('Contributor Name:') . '</strong><br>' . $proposal_data->full_name
        . '<br><br><strong>' . $this->t('Chapter Number:') . '</strong><br>' . $chapter_data->number
        . '<br><br><strong>' . $this->t('Title of the Chapter:') . '</strong><br>' . $chapter_data->name
        . '<br><br>' . Link::fromTextAndUrl($this->t('Back to Chapter List'), $back_url)->toString(),
    ];
    $example_rows = [];
    $example_q = $this->database->select('textbook_companion_example', 'e')
      ->fields('e')->condition('chapter_id', $chapter_id)->execute();
    while ($example_data = $example_q->fetchObject()) {
      $status_map = [0 => 'Pending', 1 => 'Approved', 2 => 'Rejected'];
      $approval_label = $status_map[$example_data->approval_status] ?? '';
      $example_files = '';
      $files_q = $this->database->select('textbook_companion_example_files', 'f')
        ->fields('f')->condition('example_id', $example_data->id)->orderBy('filetype', 'ASC')->execute();
      while ($file = $files_q->fetchObject()) {
        $type_map = ['S' => 'Main or Source', 'R' => 'Result', 'X' => 'xcos'];
        $file_type = $type_map[$file->filetype] ?? '';
        $dl_url = Url::fromRoute('textbook_companion.download_example_file', ['example_file_id' => $file->id]);
        $example_files .= Link::fromTextAndUrl($file->filename, $dl_url)->toString() . ' (' . $file_type . ')<br>';
      }
      if ($example_data->approval_status == 0) {
        $edit_url = Url::fromRoute('textbook_companion.upload_examples_admin_edit_form', [], ['query' => ['example_id' => $example_data->id]]);
        $del_url  = Url::fromRoute('textbook_companion.upload_examples_delete', ['example_id' => $example_data->id]);
        $actions  = Link::fromTextAndUrl($this->t('Edit'), $edit_url)->toString() . ' | ' . Link::fromTextAndUrl($this->t('Delete'), $del_url)->toString();
      }
      else {
        $dl_url  = Url::fromRoute('textbook_companion.download_example', ['example_id' => $example_data->id]);
        $actions = Link::fromTextAndUrl($this->t('Download'), $dl_url)->toString();
      }
      $example_rows[] = [
        'data' => [$example_data->number, $example_data->caption, $approval_label, $example_files, $actions],
        'valign' => 'top',
      ];
    }
    $build['table'] = $this->buildTable(['Example No.', 'Caption', 'Status', 'Files', 'Action'], $example_rows);
    return $build;
  }

  public function textbook_companion_browse_book($character = NULL) {
    $query_character = $character;
    $build = [
      'browse_list' => $this->browseList('book'),
      'spacing' => [
        '#markup' => '<br /><br />',
      ],
    ];
    if (!$query_character) {
      $build['message'] = [
        '#markup' => $this->t('Please select the starting character of the title of the book.'),
      ];
      return $build;
    }
    $book_q = $this->database->select('textbook_companion_preference', 'p')
      ->fields('p')
      ->condition('book', $query_character . '%', 'LIKE')
      ->condition('approval_status', 1)
      ->execute();
    $rows = [];
    foreach ($book_q as $d) {
      $url = Url::fromRoute('textbook_companion.run_form', ['book_pref_id' => $d->id]);
      $rows[] = [Link::fromTextAndUrl($d->book, $url)->toString(), $d->author];
    }
    if (!$rows) {
      $build['message'] = [
        '#markup' => $this->t('Sorry, no books are available with that title.'),
      ];
      return $build;
    }
    $build['table'] = $this->buildTable(['Title of the Book', 'Author Name'], $rows);
    return $build;
  }

  public function textbook_companion_browse_author($character = NULL) {
    $query_character = $character;
    
    $build = [
      'browse_list' => $this->browseList('author'),
      'spacing' => [
        '#markup' => '<br /><br />',
      ],
    ];
    
    if (!$query_character) {
      $build['message'] = [
        '#markup' => $this->t("Please select the starting character of the author's name"),
      ];
      return $build;
    }
    
    $book_rows = [];
    $query = $this->database->select('textbook_companion_preference', 'pe');
    $query->fields('pe', ['book', 'author', 'publisher', 'year', 'id']);
    $query->rightJoin('textbook_companion_proposal', 'po', 'pe.proposal_id = po.id');
    $query->condition('po.proposal_status', 3);
    $query->condition('pe.approval_status', 1);
    $book_q = $query->execute();
    
    while ($book_data = $book_q->fetchObject()) {
      preg_match_all("/" . preg_quote($query_character, '/') . "[a-z]+/i", $book_data->author, $matches);
      if (count($matches) > 0) {
        if (count($matches[0]) > 0) {
          foreach ($matches[0] as $key => $value) {
            if (strtolower($value) == "and") {
              unset($matches[$key]);
            } else {
              $matches[0][$key] = "<b>" . $value . "</b>";
              $book_data->author = str_replace($value, $matches[0][$key], $book_data->author);
            }
          }
        }
        if (count($matches[0]) > 0) {
          $book_rows[] = [
            Link::fromTextAndUrl($book_data->book, Url::fromRoute('textbook_companion.run_form', ['book_pref_id' => $book_data->id]))->toString(),
            [
              'data' => [
                '#markup' => $book_data->author,
              ],
            ],
          ];
        }
      }
    }
    
    if (!$book_rows) {
      $build['message'] = [
        '#markup' => $this->t("Sorry no books are available with that author's name"),
      ];
    } else {
      $build['table'] = $this->buildTable(['Title of the Book', 'Author Name'], $book_rows);
    }
    
    return $build;
  }

  public function textbook_companion_browse_student($character = NULL) {
    $query_character = $character;
    
    $build = [
      'browse_list' => $this->browseList('student'),
      'spacing' => [
        '#markup' => '<br /><br />',
      ],
    ];
    
    if (!$query_character) {
      $build['message'] = [
        '#markup' => $this->t("Please select the starting character of the student's name"),
      ];
      return $build;
    }
    
    $book_rows = [];
    $query = $this->database->select('textbook_companion_preference', 'pe');
    $query->fields('po', ['full_name', 'approval_date']);
    $query->fields('pe', ['book', 'author', 'publisher', 'year', 'id']);
    $query->leftJoin('textbook_companion_proposal', 'po', 'pe.proposal_id = po.id');
    $query->condition('po.proposal_status', 3);
    $query->condition('pe.approval_status', 1);
    $query->condition('full_name', $query_character . '%', 'LIKE');
    $student_q = $query->execute();
    
    while ($student_data = $student_q->fetchObject()) {
      $book_rows[] = [
        Link::fromTextAndUrl($student_data->book, Url::fromRoute('textbook_companion.run_form', ['book_pref_id' => $student_data->id]))->toString(),
        $student_data->full_name,
      ];
    }
    
    if (!$book_rows) {
      $build['message'] = [
        '#markup' => $this->t("Sorry no books are available with that student's name"),
      ];
    } else {
      $build['table'] = $this->buildTable(['Title of the Book', 'Student Name'], $book_rows);
    }
    
    return $build;
  }

  protected function browseList(string $type): array {
    $links = [];
    $char_list = range('A', 'Z');
    foreach ($char_list as $char) {
      $url = Url::fromRoute('textbook_companion.browse_' . $type, ['character' => $char]);
      $links[] = Link::fromTextAndUrl($char, $url)->toString();
    }
    return [
      '#markup' => '<div id="filter-links">' . implode(' | ', $links) . '</div>',
    ];
  }

  // public function textbook_companion_download_example_file() {
  //   $example_file_id = arg(3);
  //   $root_path = textbook_companion_path();
  //   $root_temp_path = textbook_companion_temp_path();
  //   /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE id = %d LIMIT 1", $example_file_id);
	// $example_file_data = db_fetch_object($example_files_q);*/
  //   /*$query = db_select('textbook_companion_example_files');
	// $query->fields('textbook_companion_example_files');
	// $query->condition('id', $example_file_id);
	// $query->range(0, 1);
	// $result = $query->execute();*/
  //   $example_files_q = db_query("select * from textbook_companion_preference tcp join textbook_companion_chapter tcc on tcp.id=tcc.preference_id join textbook_companion_example tce ON tcc.id=tce.chapter_id join textbook_companion_example_files tcef on tce.id=tcef.example_id where tcef.id= :example_id LIMIT 1", [
  //     ':example_id' => $example_file_id
  //     ]);
  //   $example_file_data = $example_files_q->fetchObject();
  //   header('Content-Type: ' . $example_file_data->filemime);
  //   header('Content-disposition: attachment; filename="' . $example_file_data->filename . '"');
  //   header('Content-Length: ' . filesize($root_path . $example_file_data->directory_name . '/' . $example_file_data->filepath));
  //   ob_clean();
  //   readfile($root_path . $example_file_data->directory_name . '/' . $example_file_data->filepath);
  // }
  public function textbook_companion_download_example_file($example_file_id) {
  if (empty($example_file_id)) {
    throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
  }

  // Get root path via injected global service.
  $root_path = $this->globalService->textbook_companion_path();

  $query = $this->database->select('textbook_companion_preference', 'tcp');
  $query->join('textbook_companion_chapter', 'tcc', 'tcp.id = tcc.preference_id');
  $query->join('textbook_companion_example', 'tce', 'tcc.id = tce.chapter_id');
  $query->join('textbook_companion_example_files', 'tcef', 'tce.id = tcef.example_id');
  $query->fields('tcef');
  $query->addField('tcp', 'directory_name', 'directory_name');
  $query->condition('tcef.id', $example_file_id);
  $query->range(0, 1);

  $example_file_data = $query->execute()->fetchObject();

  if (!$example_file_data) {
    throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
  }

  $file_path = $root_path . $example_file_data->directory_name . '/' . $example_file_data->filepath;

  if (!file_exists($file_path)) {
    throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('File not found.');
  }

  $response = new BinaryFileResponse($file_path);
  $response->setContentDisposition(
    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
    $example_file_data->filename
  );
  $response->headers->set('Content-Type', $example_file_data->filemime);
  $response->headers->set('Content-Length', filesize($file_path));

  return $response;
}
  // Get root path (assuming this function exists in your module).
  $root_path =\Drupal::service("textbook_companion_global")->textbook_companion_path();

  // Database query using Drupal 10 Database API.
  // $connection = \Drupal::database();
  // $query = $connection->select('textbook_companion_preference', 'tcp')
  //   ->join('textbook_companion_chapter', 'tcc', 'tcp.id = tcc.preference_id')
  //   ->join('textbook_companion_example', 'tce', 'tcc.id = tce.chapter_id')
  //   ->join('textbook_companion_example_files', 'tcef', 'tce.id = tcef.example_id')
  //       ->fields('tcef')

  //   ->condition('tcef.id', $example_file_id)
  //   ->range(0, 1);

  $connection = \Drupal::database();

$query = $connection->select('textbook_companion_preference', 'tcp');
$query->join('textbook_companion_chapter', 'tcc', 'tcp.id = tcc.preference_id');
$query->join('textbook_companion_example', 'tce', 'tcc.id = tce.chapter_id');
$query->join('textbook_companion_example_files', 'tcef', 'tce.id = tcef.example_id');

// Fetch both file info and directory name
$query->fields('tcef');
$query->addField('tcp', 'directory_name', 'directory_name');

$query->condition('tcef.id', $example_file_id);
$query->range(0, 1);


// Execute.

  $example_file_data = $query->execute()->fetchObject();

  if (!$example_file_data) {
    throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
  }

  // Build full file path.
  $file_path = $root_path . $example_file_data->directory_name . '/' . $example_file_data->filepath;

  // var_dump( $example_file_data->directory_name);die;
  if (!file_exists($file_path)) {
    throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('File not found.');
  }

  // Prepare response using Symfony's BinaryFileResponse.
  $response = new BinaryFileResponse($file_path);
  $response->setContentDisposition(
    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
    $example_file_data->filename
  );
  $response->headers->set('Content-Type', $example_file_data->filemime);
  $response->headers->set('Content-Length', filesize($file_path));

  return $response;
}



  public function textbook_companion_download_sample_code($proposal_id = NULL) {
    if (!$proposal_id) {
      $proposal_id = $this->requestStack->getCurrentRequest()->query->get('proposal_id');
    }
    $root_path = $this->globalService->textbook_companion_samplecode_path();
    $example_file_data = $this->database->select('textbook_companion_proposal')
      ->fields('textbook_companion_proposal')
      ->condition('id', $proposal_id)
      ->range(0, 1)
      ->execute()
      ->fetchObject();
    if (!$example_file_data || empty($example_file_data->samplefilepath)) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('Sample code file not found.');
    }
    $file_path = $root_path . $example_file_data->samplefilepath;
    if (!file_exists($file_path)) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('File does not exist.');
    }
    $samplecodename = substr($example_file_data->samplefilepath, strrpos($example_file_data->samplefilepath, '/') + 1);
    $response = new BinaryFileResponse($file_path);
    $response->setContentDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $samplecodename
    );
    return $response;
  }

  // public function textbook_companion_download_example() {
  //   $example_id = arg(3);
  //   $root_path = textbook_companion_path();
  //   $root_temp_path = textbook_companion_temp_path();
  //   /* get example data */
  //   /*$example_q = db_query("SELECT * FROM {textbook_companion_example} WHERE id = %d", $example_id);
	// $example_data = db_fetch_object($example_q);*/
  //   $query = db_select('textbook_companion_example');
  //   $query->fields('textbook_companion_example');
  //   $query->condition('id', $example_id);
  //   $result = $query->execute();
  //   $example_data = $result->fetchObject();
  //   /*$chapter_q = db_query("SELECT * FROM {textbook_companion_chapter} WHERE id = %d", $example_data->chapter_id);
	// $chapter_data = db_fetch_object($chapter_q);*/
  //   $query = db_select('textbook_companion_chapter');
  //   $query->fields('textbook_companion_chapter');
  //   $query->condition('id', $example_data->chapter_id);
  //   $result = $query->execute();
  //   $chapter_data = $result->fetchObject();
  //   /*$example_files_q = db_query("SELECT * FROM {textbook_companion_example_files} WHERE example_id = %d", $example_id);*/
  //   /* $query = db_select('textbook_companion_example_files');
	// $query->fields('textbook_companion_example_files');
	// $query->condition('example_id', $example_id);
	// $example_files_q = $query->execute();*/
  //   $example_files_q = db_query("select * from textbook_companion_preference tcp join textbook_companion_chapter tcc on tcp.id=tcc.preference_id join textbook_companion_example tce ON tcc.id=tce.chapter_id join textbook_companion_example_files tcef on tce.id=tcef.example_id where tcef.example_id= :example_id", [
  //     ':example_id' => $example_id
  //     ]);
  //   $EX_PATH = 'EX' . $example_data->number . '/';
  //   /* zip filename */
  //   if (!is_dir($root_temp_path . 'tbc_download_temp')) {
  //     mkdir($root_temp_path . 'tbc_download_temp');
  //   }
  //   $zip_filename = $root_temp_path . 'tbc_download_temp/' . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
  //   /* creating zip archive on the server */
  //   $zip = new ZipArchive();
  //   $zip->open($zip_filename, ZipArchive::CREATE);
  //   while ($example_files_row = $example_files_q->fetchObject()) {
  //     $zip->addFile($root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath, $EX_PATH . $example_files_row->filename);
  //   } //$example_files_row = $example_files_q->fetchObject()
  //   $zip_file_count = $zip->numFiles;
  //   $zip->close();
  //   if ($zip_file_count > 0) {
  //     /* download zip file */
  //     header('Content-Type: application/octet-stream');
  //     header('Content-disposition: attachment; filename="EX' . $example_data->number . '.zip"');
  //     header('Content-Length: ' . filesize($zip_filename));
  //     ob_clean();
  //     readfile($zip_filename);
  //     unlink($zip_filename);
  //   } //$zip_file_count > 0
  //   else {
  //     drupal_set_message("There are no files in this examples to download", 'error');
  //     drupal_goto('textbook-companion/textbook-run');
  //   }
  // }

//   public function textbook_companion_download_chapter() {
//     // $chapter_id = arg(3);
//             $route_match = \Drupal::routeMatch();

// $chapter_id = (int) $route_match->getParameter('chapter_id');

//     $root_path = \Drupal::service("textbook_companion_global")->textbook_companion_path();
//     // var_dump($chapter_id);die;
//     // var_dump($root_path);die;

// // $connection = \Drupal::service('database');
//  $connection = \Drupal::database();
//     $query = \Drupal::database()->select('textbook_companion_preference');
//     $query->fields('textbook_companion_preference');
//     $query->condition('id', $book_id);
//     $result = $query->execute();
//     $book_data = $result->fetchObject();
//     // Load chapter.
//     $query = $connection->select('textbook_companion_chapter', 'tcc')
//       ->fields('tcc')
//       ->condition('number', $chapter_id);
//     $chapter_data = $query->execute()->fetchObject();
//      $chapter_data = db_fetch_object($chapter_q);
    
//    var_dump($chpater_data);die;

//     // if (!$chapter_data) {
//     //   \Drupal::messenger()->addMessage("Invalid chapter.", 'error');
//     //   // return new RedirectResponse('/textbook-companion/textbook-run');
//     //         return $this->redirect('textbook_companion.run_form', ['book_pref_id' => $book_id]);

//     // }

//     // $CH_PATH = 'CH' . $chapter_data->number . '/';
//     $zip_filename = $root_path . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';

//     $zip = new \ZipArchive();
//     $zip->open($zip_filename, \ZipArchive::CREATE);

//     // Fetch approved examples.
//     $query = \Drupal::database()->select('textbook_companion_example');
//     $query->fields('textbook_companion_example');
//     $query->condition('chapter_id', $chapter_id);
//     $query->condition('approval_status', 1);
//     $example_q = $query->execute();
//     // $example_q_data = $example_q->fetchObject();
//     foreach ($example_q as $example_row) {
//       $EX_PATH = 'EX' . $example_row->number . '/';

//       // Fetch files for this example.
//       $example_files_q = $connection->query(
//         "SELECT tcef.* , tcp.directory_name
//          FROM {textbook_companion_preference} tcp
//          JOIN {textbook_companion_chapter} tcc ON tcp.id = tcc.preference_id 
//          JOIN {textbook_companion_example} tce ON tcc.id = tce.chapter_id 
//          JOIN {textbook_companion_example_files} tcef ON tce.id = tcef.example_id 
//          WHERE tcef.example_id = :example_id",
//         [':example_id' => $example_row->id]
//       );
  
//  $example_files_q_data =   $example_files_q->fetchAll();
//       foreach ($example_files_q_data as $example_files_row) {
//         $zip->addFile(
//           $root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath,
//           $CH_PATH . $EX_PATH . $example_files_row->filename
//         );
//       }
//       // while ($example_files_row = $example_files_q->fetchObject())
//       //     {
//       //       $zip->addFile($root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath, $CH_PATH . $EX_PATH . $example_files_row->filename);
//       //     }
//       }
//         // var_dump($example_file_q);die;
// // var_dump($example_file_q);die;
//     $zip_file_count = $zip->numFiles;
//     $zip->close();



//        if ($zip_file_count > 0)
//       {
//         /* download zip file */
//         header('Content-Type: application/zip');
//         header('Content-disposition: attachment; filename="CH' . $chapter_data->number . '.zip"');
//         header('Content-Length: ' . filesize($zip_filename));
//         ob_clean();
//         readfile($zip_filename);
//         unlink($zip_filename);
//       }
   
      
    
//     else {

 

// // Show error message
// $this->messenger()->addMessage("There are no examples in this chapter to download", 'error');

// // Redirect to a route
// $url = Url::fromRoute('textbook_companion.run_form'); // Use your route name
// return new RedirectResponse($url->toString());

//       // \Drupal::messenger()->addMessage("There are no examples in this chapter to download", 'error');
//       // return new RedirectResponse('/textbook-companion/textbook-run');
//       // return $this->redirect('textbook_companion.run_form', ['book_pref_id' => $book_id]);
// // $url = Url::fromRoute('textbook_companion.run_form');
// // return new RedirectResponse($url->toString());
// // return \Drupal::messenger()->addMessage("There are no examples in this chapter to download", 'error');;
    
//   }



//   }

public function textbook_companion_download_chapter() {

  // Get chapter id from route.
  $chapter_id = (int) $this->requestStack->getCurrentRequest()->attributes->get('chapter_id');

  // Get root path via injected global service.
  $root_path = rtrim($this->globalService->textbook_companion_path(), '/') . '/';

  $chapter_data = $this->database->select('textbook_companion_chapter', 'tcc')
    ->fields('tcc')
    ->condition('id', $chapter_id)
    ->execute()->fetchObject();

  if (!$chapter_data) {
    $this->messenger->addError($this->t('Invalid chapter.'));
    return $this->redirect('textbook_companion.run_form');
  }

  $CH_PATH = 'CH' . $chapter_data->number . '/';

  // Create temporary zip location
  $temp_dir = $root_path . 'tbc_download_temp/';
  if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0777, TRUE);
  }
  $zip_filename = $temp_dir . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';

  // Create zip
  $zip = new \ZipArchive();
  if ($zip->open($zip_filename, \ZipArchive::CREATE) !== TRUE) {
    \Drupal::messenger()->addError("Unable to create zip file.");
    return $this->redirect('textbook_companion.run_form');
  }

  // Fetch approved examples
  $db = $this->database;
  $example_q = $db->select('textbook_companion_example', 'tce')
    ->fields('tce')
    ->condition('chapter_id', $chapter_id)
    ->condition('approval_status', 1)
    ->execute();

  while ($example_row = $example_q->fetchObject()) {
    $EX_PATH = 'EX' . $example_row->number . '/';

    // Get example files with joined preference directory
    $example_files_q = $db->query("
        SELECT tcef.filename, tcef.filepath, tcp.directory_name
        FROM textbook_companion_preference tcp
        JOIN textbook_companion_chapter tcc ON tcp.id = tcc.preference_id
        JOIN textbook_companion_example tce ON tcc.id = tce.chapter_id
        JOIN textbook_companion_example_files tcef ON tce.id = tcef.example_id
        WHERE tcef.example_id = :example_id",
      [':example_id' => $example_row->id]
    );

    while ($example_files_row = $example_files_q->fetchObject()) {

      $file_full_path = $root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath;

      if (file_exists($file_full_path)) {
        $zip->addFile($file_full_path, $CH_PATH . $EX_PATH . $example_files_row->filename);
      }
      else {
        $this->loggerFactory->get('textbook_companion')->warning('Missing file: ' . $file_full_path);
      }
    }
  }

  $zip_file_count = $zip->numFiles;
  $zip->close();

  if ($zip_file_count < 1) {
    unlink($zip_filename);
    $this->messenger->addError($this->t('There are no examples in this chapter to download.'));
    return $this->redirect('textbook_companion.run_form');
  }

  // Return as downloadable response
  $response = new BinaryFileResponse($zip_filename);
  $response->setContentDisposition(
    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
    'CH' . $chapter_data->number . '.zip'
  );

  // Delete after sending to user
  $response->deleteFileAfterSend(true);

  return $response;
}


  public function textbook_companion_download_book($book_id = NULL) {
    if (!$book_id) {
      $book_id = (int) $this->requestStack->getCurrentRequest()->attributes->get('book_id');
    }
    $root_path = rtrim($this->globalService->textbook_companion_path(), '/') . '/';
    
    $book_data = $this->database->select('textbook_companion_preference', 'tcp')
      ->fields('tcp')
      ->condition('id', $book_id)
      ->execute()
      ->fetchObject();
      
    if (!$book_data) {
      $this->messenger->addError($this->t('Invalid book preference ID.'));
      return $this->redirect('textbook_companion.run_form');
    }
    
    $zipname = str_replace(' ', '_', $book_data->book);
    $directory_name = $book_data->directory_name;
    $BK_PATH = $zipname . '/';
    
    $temp_dir = $root_path . 'tbc_download_temp/';
    if (!is_dir($temp_dir)) {
      mkdir($temp_dir, 0777, TRUE);
    }
    $zip_filename = $temp_dir . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    
    $zip = new \ZipArchive();
    if ($zip->open($zip_filename, \ZipArchive::CREATE) !== TRUE) {
      $this->messenger->addError($this->t('Unable to create zip file.'));
      return $this->redirect('textbook_companion.run_form', ['book_pref_id' => $book_id]);
    }
    
    $chapter_q = $this->database->select('textbook_companion_chapter', 'tcc')
      ->fields('tcc')
      ->condition('preference_id', $book_id)
      ->execute();
      
    $fs = $this->fileSystem;
    
    while ($chapter_row = $chapter_q->fetchObject()) {
      $CH_PATH = 'CH' . $chapter_row->number . '/';
      
      $example_q = $this->database->select('textbook_companion_example', 'tce')
        ->fields('tce')
        ->condition('chapter_id', $chapter_row->id)
        ->condition('approval_status', 1)
        ->execute();
        
      while ($example_row = $example_q->fetchObject()) {
        $EX_PATH = 'EX' . $example_row->number . '/';
        
        $example_files_q = $this->database->select('textbook_companion_example_files', 'tcef')
          ->fields('tcef')
          ->condition('example_id', $example_row->id)
          ->execute();
          
        while ($example_files_row = $example_files_q->fetchObject()) {
          $full_path = $root_path . $directory_name . '/' . $example_files_row->filepath;
          $real_path = $fs->realpath($full_path);
          
          if ($real_path && file_exists($real_path)) {
            $zip->addFile($real_path, $BK_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
          } else {
            $this->loggerFactory->get('textbook_companion')->error('Missing file: ' . $full_path);
          }
        }
      }
    }
    
    $zip_file_count = $zip->numFiles;
    $zip->close();
    
    if ($zip_file_count > 0) {
      $response = new BinaryFileResponse($zip_filename);
      $response->setContentDisposition(
        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        str_replace(' ', '_', $book_data->book) . '.zip'
      );
      $response->deleteFileAfterSend(TRUE);
      return $response;
    } else {
      if (file_exists($zip_filename)) {
        unlink($zip_filename);
      }
      $this->messenger->addError($this->t('There are no examples in this book to download.'));
      return $this->redirect('textbook_companion.run_form', ['book_pref_id' => $book_id]);
    }
  }

 
  
  public function textbook_companion_download_full_chapter($chapter_id = NULL) {
    if (!$chapter_id) {
      $chapter_id = $this->requestStack->getCurrentRequest()->query->get('chapter_id');
    }
    $root_path = rtrim($this->globalService->textbook_companion_path(), '/') . '/';
    $APPROVE_PATH = 'APPROVED/';
    $PENDING_PATH = 'PENDING/';
    
    $chapter_data = $this->database->select('textbook_companion_chapter', 'tcc')
      ->fields('tcc')
      ->condition('id', $chapter_id)
      ->execute()
      ->fetchObject();
      
    if (!$chapter_data) {
      $this->messenger->addError($this->t('Invalid chapter ID.'));
      return $this->redirect('textbook_companion.bulk_approval_form');
    }
    
    $CH_PATH = 'CH' . $chapter_data->number . '/';
    
    $temp_dir = $root_path . 'tbc_download_temp/';
    if (!is_dir($temp_dir)) {
      mkdir($temp_dir, 0777, TRUE);
    }
    $zip_filename = $temp_dir . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    
    $zip = new \ZipArchive();
    if ($zip->open($zip_filename, \ZipArchive::CREATE) !== TRUE) {
      $this->messenger->addError($this->t('Unable to create zip file.'));
      return $this->redirect('textbook_companion.bulk_approval_form');
    }
    
    // Approved examples
    $example_q = $this->database->select('textbook_companion_example', 'tce')
      ->fields('tce')
      ->condition('chapter_id', $chapter_id)
      ->condition('approval_status', 1)
      ->execute();
      
    while ($example_row = $example_q->fetchObject()) {
      $EX_PATH = 'EX' . $example_row->number . '/';
      
      $example_files_q = $this->database->select('textbook_companion_example_files', 'tcef')
        ->fields('tcef')
        ->condition('example_id', $example_row->id)
        ->execute();
        
      while ($example_files_row = $example_files_q->fetchObject()) {
        $file_path = $root_path . $example_files_row->filepath;
        if (file_exists($file_path)) {
          $zip->addFile($file_path, $APPROVE_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
        }
      }
    }
    
    // Unapproved examples
    $example_q = $this->database->select('textbook_companion_example', 'tce')
      ->fields('tce')
      ->condition('chapter_id', $chapter_id)
      ->condition('approval_status', 0)
      ->execute();
      
    while ($example_row = $example_q->fetchObject()) {
      $EX_PATH = 'EX' . $example_row->number . '/';
      
      $example_files_q = $this->database->query("
        SELECT tcef.*, tcp.directory_name 
        FROM textbook_companion_preference tcp 
        JOIN textbook_companion_chapter tcc ON tcp.id = tcc.preference_id 
        JOIN textbook_companion_example tce ON tcc.id = tce.chapter_id 
        JOIN textbook_companion_example_files tcef ON tce.id = tcef.example_id 
        WHERE tcef.example_id = :example_id
      ", [':example_id' => $example_row->id]);
      
      while ($example_files_row = $example_files_q->fetchObject()) {
        $file_path = $root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath;
        if (file_exists($file_path)) {
          $zip->addFile($file_path, $PENDING_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
        }
      }
    }
    
    $zip_file_count = $zip->numFiles;
    $zip->close();
    
    if ($zip_file_count > 0) {
      $response = new BinaryFileResponse($zip_filename);
      $response->setContentDisposition(
        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        'CH' . $chapter_data->number . '.zip'
      );
      $response->deleteFileAfterSend(TRUE);
      return $response;
    } else {
      if (file_exists($zip_filename)) {
        unlink($zip_filename);
      }
      $this->messenger->addError($this->t('There are no examples in this chapter to download.'));
      return $this->redirect('textbook_companion.bulk_approval_form');
    }
  }

  public function textbook_companion_download_full_book($book_id = NULL) {
    if (!$book_id) {
      $book_id = $this->requestStack->getCurrentRequest()->query->get('book_id');
    }
    $root_path = rtrim($this->globalService->textbook_companion_path(), '/') . '/';
    $APPROVE_PATH = 'APPROVED/';
    $PENDING_PATH = 'PENDING/';
    
    $book_data = $this->database->select('textbook_companion_preference', 'tcp')
      ->fields('tcp')
      ->condition('id', $book_id)
      ->execute()
      ->fetchObject();
      
    if (!$book_data) {
      $this->messenger->addError($this->t('Invalid book preference ID.'));
      return $this->redirect('textbook_companion.bulk_approval_form');
    }
    
    $BK_PATH = $book_data->book . '/';
    
    $temp_dir = $root_path . 'tbc_download_temp/';
    if (!is_dir($temp_dir)) {
      mkdir($temp_dir, 0777, TRUE);
    }
    $zip_filename = $temp_dir . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';
    
    $zip = new \ZipArchive();
    if ($zip->open($zip_filename, \ZipArchive::CREATE) !== TRUE) {
      $this->messenger->addError($this->t('Unable to create zip file.'));
      return $this->redirect('textbook_companion.bulk_approval_form');
    }
    
    $chapter_q = $this->database->select('textbook_companion_chapter', 'tcc')
      ->fields('tcc')
      ->condition('preference_id', $book_id)
      ->execute();
      
    while ($chapter_row = $chapter_q->fetchObject()) {
      $CH_PATH = 'CH' . $chapter_row->number . '/';
      
      // Approved examples
      $example_q = $this->database->select('textbook_companion_example', 'tce')
        ->fields('tce')
        ->condition('chapter_id', $chapter_row->id)
        ->condition('approval_status', 1)
        ->execute();
        
      while ($example_row = $example_q->fetchObject()) {
        $EX_PATH = 'EX' . $example_row->number . '/';
        
        $example_files_q = $this->database->query("
          SELECT tcef.*, tcp.directory_name 
          FROM textbook_companion_preference tcp 
          JOIN textbook_companion_chapter tcc ON tcp.id = tcc.preference_id 
          JOIN textbook_companion_example tce ON tcc.id = tce.chapter_id 
          JOIN textbook_companion_example_files tcef ON tce.id = tcef.example_id 
          WHERE tcef.example_id = :example_id
        ", [':example_id' => $example_row->id]);
        
        while ($example_files_row = $example_files_q->fetchObject()) {
          $file_path = $root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath;
          if (file_exists($file_path)) {
            $zip->addFile($file_path, $BK_PATH . $APPROVE_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
          }
        }
      }
      
      // Unapproved examples
      $example_q = $this->database->select('textbook_companion_example', 'tce')
        ->fields('tce')
        ->condition('chapter_id', $chapter_row->id)
        ->condition('approval_status', 0)
        ->execute();
        
      while ($example_row = $example_q->fetchObject()) {
        $EX_PATH = 'EX' . $example_row->number . '/';
        
        $example_files_q = $this->database->query("
          SELECT tcef.*, tcp.directory_name 
          FROM textbook_companion_preference tcp 
          JOIN textbook_companion_chapter tcc ON tcp.id = tcc.preference_id 
          JOIN textbook_companion_example tce ON tcc.id = tce.chapter_id 
          JOIN textbook_companion_example_files tcef ON tce.id = tcef.example_id 
          WHERE tcef.example_id = :example_id
        ", [':example_id' => $example_row->id]);
        
        while ($example_files_row = $example_files_q->fetchObject()) {
          $file_path = $root_path . $example_files_row->directory_name . '/' . $example_files_row->filepath;
          if (file_exists($file_path)) {
            $zip->addFile($file_path, $BK_PATH . $PENDING_PATH . $CH_PATH . $EX_PATH . $example_files_row->filename);
          }
        }
      }
    }
    
    $zip_file_count = $zip->numFiles;
    $zip->close();
    
    if ($zip_file_count > 0) {
      $response = new BinaryFileResponse($zip_filename);
      $response->setContentDisposition(
        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        str_replace(' ', '_', $book_data->book) . '.zip'
      );
      $response->deleteFileAfterSend(TRUE);
      return $response;
    } else {
      if (file_exists($zip_filename)) {
        unlink($zip_filename);
      }
      $this->messenger->addError($this->t('There are no examples in this book to download.'));
      return $this->redirect('textbook_companion.bulk_approval_form');
    }
  }

  public function textbook_companion_delete_book($book_id = NULL) {
    if (!$book_id) {
      $book_id = $this->requestStack->getCurrentRequest()->attributes->get('book_id');
    }
    if (function_exists('del_book_pdf')) {
      del_book_pdf($book_id);
    }
    $this->messenger->addStatus($this->t('Book scheduled for regeneration.'));
    return $this->redirect('textbook_companion.bulk_approval_form');
  }

  public function textbook_companion_ajax() {
    $request = $this->requestStack->getCurrentRequest();
    $path_info = $request->getPathInfo();
    $path_parts = explode('/', trim($path_info, '/'));
    
    // textbook-companion/ajax/<query_type>/<arg3>/<arg4>/<arg5>
    $query_type = isset($path_parts[2]) ? $path_parts[2] : '';
    
    if ($query_type == 'chapter_title') {
      $chapter_number = isset($path_parts[3]) ? $path_parts[3] : '';
      $preference_id = isset($path_parts[4]) ? $path_parts[4] : '';
      
      $chapter_data = $this->database->select('textbook_companion_chapter', 'tcc')
        ->fields('tcc')
        ->condition('number', $chapter_number)
        ->condition('preference_id', $preference_id)
        ->range(0, 1)
        ->execute()
        ->fetchObject();
        
      if ($chapter_data) {
        return new Response($chapter_data->name);
      }
    }
    elseif ($query_type == 'example_exists') {
      $chapter_number = isset($path_parts[3]) ? $path_parts[3] : '';
      $preference_id = isset($path_parts[4]) ? $path_parts[4] : '';
      $example_number = isset($path_parts[5]) ? $path_parts[5] : '';
      
      $chapter_data = $this->database->select('textbook_companion_chapter', 'tcc')
        ->fields('tcc')
        ->condition('number', $chapter_number)
        ->condition('preference_id', $preference_id)
        ->range(0, 1)
        ->execute()
        ->fetchObject();
        
      if (!$chapter_data) {
        return new Response('');
      }
      
      $example_data = $this->database->select('textbook_companion_example', 'tce')
        ->fields('tce')
        ->condition('chapter_id', $chapter_data->id)
        ->condition('number', $example_number)
        ->range(0, 1)
        ->execute()
        ->fetchObject();
        
      if ($example_data) {
        if ($example_data->approval_status == 1) {
          return new Response('Warning! Example already approved. You cannot upload the same example again.');
        }
        else {
          return new Response('Warning! Example already uploaded. Delete the example and reupload it.');
        }
      }
    }
    
    return new Response('');
  }

  public function _data_entry_proposal_all() {
    $proposal_rows = [];
    $preference_q = $this->database->select('textbook_companion_preference', 'tcp')
      ->fields('tcp')
      ->condition('approval_status', 1)
      ->orderBy('book', 'ASC')
      ->execute();
      
    $sno = 1;
    while ($preference_data = $preference_q->fetchObject()) {
      $url = Url::fromRoute('textbook_companion.dataentry_edit', ['id' => $preference_data->id]);
      $proposal_rows[] = [
        $sno++,
        $preference_data->book,
        $preference_data->author,
        $preference_data->isbn,
        Link::fromTextAndUrl($this->t('Edit'), $url)->toString(),
      ];
    }
    
    if (!$proposal_rows) {
      $this->messenger->addStatus($this->t('There are no proposals.'));
      return [
        '#markup' => $this->t('There are no proposals.'),
      ];
    }
    
    $proposal_header = [
      $this->t('SNO'),
      $this->t('Title of the Book'),
      $this->t('Author'),
      $this->t('ISBN'),
      '',
    ];
    
    return [
      '#type' => 'table',
      '#header' => $proposal_header,
      '#rows' => $proposal_rows,
    ];
  }

  public function dataentry_edit($id = NULL) {
    if ($id) {
      return $this->formBuilder->getForm('\Drupal\textbook_companion\Form\DataentryEditForm', $id);
    }
    return [
      '#markup' => $this->t('Access denied'),
    ];
  }

  public function cheque_proposal_all() {
    $count = 20;
    $proposal_rows = [];

    $query = $this->database->select('textbook_companion_proposal', 'tcp')
      ->fields('tcp')
      ->orderBy('id', 'DESC');

    $pager_select = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($count);
    $result = $pager_select->execute();

    while ($proposal_data = $result->fetchObject()) {
      $contributor_url = Url::fromUri('internal:/user/' . $proposal_data->uid);
      $submission_url = Url::fromRoute('textbook_companion.paper_submission_form', ['proposal_id' => $proposal_data->id]);
      $cheque_url = Url::fromRoute('textbook_companion.cheque_status_form', ['proposal_id' => $proposal_data->id]);

      $status_links = Link::fromTextAndUrl($this->t('Form Submission'), $submission_url)->toString() . ' | ' .
                      Link::fromTextAndUrl($this->t('Cheque Details'), $cheque_url)->toString();

      $proposal_rows[] = [
        date('d-m-Y', $proposal_data->creation_date),
        Link::fromTextAndUrl($proposal_data->full_name, $contributor_url)->toString(),
        date('d-m-Y', $proposal_data->completion_date),
        [
          'data' => [
            '#markup' => $status_links,
          ],
        ],
      ];
    }

    if (!$proposal_rows) {
      $this->messenger->addStatus($this->t('There are no proposals.'));
      return [
        '#markup' => $this->t('There are no proposals.'),
      ];
    }

    $proposal_header = [
      $this->t('Date of Submission'),
      $this->t('Contributor Name'),
      $this->t('Expected Date of Completion'),
      $this->t('Status'),
    ];

    return [
      'table' => [
        '#type' => 'table',
        '#header' => $proposal_header,
        '#rows' => $proposal_rows,
      ],
      'pager' => [
        '#type' => 'pager',
      ],
    ];
  }

  public function textbook_companion_completed_books() {
    $query = $this->database->select('textbook_companion_preference', 'pe');
    $query->fields('pe', ['book', 'author', 'publisher', 'year', 'id']);
    $query->leftJoin('textbook_companion_proposal', 'po', 'pe.proposal_id = po.id');
    $query->fields('po', ['full_name', 'university', 'completion_date']);
    $query->condition('po.proposal_status', 3);
    $query->condition('pe.approval_status', 1);
    $query->orderBy('po.completion_date');

    $results = $query->execute()->fetchAll();

    if (empty($results)) {
      return [
        '#markup' => $this->t('Work has been completed on the following books under the Textbook Companion Project.') .
          '<br><span style="color:red;">' . 
          $this->t('The list below is not the books as named but only are the solved example for DWSIM') .
          '</span>',
      ];
    }

    $rows = [];
    $i = count($results);
    foreach ($results as $row) {
      $completion_year = date("Y", $row->completion_date);

      $link = Link::fromTextAndUrl(
        $row->book . ' by ' . $row->author . ', ' . $row->publisher . ', ' . $row->year,
        Url::fromUserInput('/textbook-companion/textbook-run/' . $row->id)
      )->toString();

      $rows[] = [
        $i,
        [
          'data' => [
            '#markup' => $link,
          ],
        ],
        $row->full_name,
        $row->university,
        $completion_year,
      ];
      $i--;
    }

    $header = [
      $this->t('No'),
      $this->t('Title of the Book'),
      $this->t('Contributor Name'),
      $this->t('University / Institute'),
      $this->t('Year of Completion')
    ];

    return [
      'intro' => [
        '#markup' => $this->t('Work has been completed on the following books under the Textbook Companion Project.') .
          '<br><span style="color:red;">' .
          $this->t('The list below is not the books as named but only are the solved example for DWSIM.') .
          '</span>',
      ],
      'table' => [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      ],
    ];
  }

  public function textbook_companion_download_example($example_id) {
    $root_path = rtrim($this->globalService->textbook_companion_path(), '/') . '/';

    $example_data = $this->database->select('textbook_companion_example', 'tce')
      ->fields('tce')
      ->condition('id', $example_id)
      ->execute()
      ->fetchObject();

    if (!$example_data) {
      $this->messenger->addError($this->t('Example not found.'));
      return $this->redirect('textbook_companion.run_form');
    }

    $example_files = $this->database->query("
      SELECT tcp.directory_name, tcef.filepath, tcef.filename 
      FROM textbook_companion_preference tcp
      JOIN textbook_companion_chapter tcc ON tcp.id = tcc.preference_id
      JOIN textbook_companion_example tce ON tcc.id = tce.chapter_id
      JOIN textbook_companion_example_files tcef ON tce.id = tcef.example_id
      WHERE tcef.example_id = :example_id
    ", [':example_id' => $example_id]);

    $EX_PATH = 'EX' . $example_data->number . '/';
    
    $temp_dir = $root_path . 'tbc_download_temp/';
    if (!is_dir($temp_dir)) {
      mkdir($temp_dir, 0777, TRUE);
    }
    $zip_filename = $temp_dir . 'zip-' . time() . '-' . rand(0, 999999) . '.zip';

    $zip = new \ZipArchive();
    if ($zip->open($zip_filename, \ZipArchive::CREATE) !== TRUE) {
      $this->messenger->addError($this->t('Unable to create zip file.'));
      return $this->redirect('textbook_companion.run_form');
    }

    foreach ($example_files as $file_row) {
      $file_path = $root_path . $file_row->directory_name . '/' . $file_row->filepath;
      if (file_exists($file_path)) {
        $zip->addFile($file_path, $EX_PATH . $file_row->filename);
      }
    }
    
    $zip_file_count = $zip->numFiles;
    $zip->close();

    if ($zip_file_count > 0) {
      $response = new BinaryFileResponse($zip_filename);
      $response->setContentDisposition(
        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        'EX' . $example_data->number . '.zip'
      );
      $response->deleteFileAfterSend(TRUE);
      return $response;
    } else {
      if (file_exists($zip_filename)) {
        unlink($zip_filename);
      }
      $this->messenger->addError($this->t('There are no files in this example to download.'));
      return $this->redirect('textbook_companion.run_form');
    }
  }
}
