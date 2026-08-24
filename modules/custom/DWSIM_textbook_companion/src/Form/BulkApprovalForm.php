<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\BulkApprovalForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\textbook_companion\Services\MailService;
use Drupal\textbook_companion\Services\AjaxHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

class BulkApprovalForm extends FormBase {

  /**
   * The database connection.
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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The mail service.
   *
   * @var \Drupal\textbook_companion\Services\MailService
   */
  protected $mailService;

  /**
   * The reusable AJAX helper.
   *
   * @var \Drupal\textbook_companion\Services\AjaxHelper
   */
  protected $ajaxHelper;

  /**
   * Constructs a new BulkApprovalForm object.
   */
  public function __construct(
    Connection $database,
    MessengerInterface $messenger,
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    RendererInterface $renderer,
    MailService $mail_service,
    AjaxHelper $ajax_helper
  ) {
    $this->database = $database;
    $this->messenger = $messenger;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->mailService = $mail_service;
    $this->ajaxHelper = $ajax_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('messenger'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('textbook_companion.mail_service'),
      $container->get('textbook_companion.ajax_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bulk_approval_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options_first = $this->bulkListOfBooks();
    $selected = $form_state->getValue('book') ?? key($options_first);

    $options_two = $this->ajaxBulkGetChapterList($selected);

    $form['book'] = [
      '#type' => 'select',
      '#title' => $this->t('Title of the Book'),
      '#options' => $options_first,
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => '::ajaxBulkChapterListCallback'
      ],
      '#validated' => TRUE,
    ];
    $form['download_book'] = [
      '#type' => 'item',
      '#markup' => '<div id="ajax_selected_book"></div>',
    ];
    $form['notes_book'] = [
      '#type' => 'item',
      '#markup' => '<div id="ajax_selected_book_notes"></div>',
    ];
    $form['book_actions'] = [
      '#type' => 'select',
      '#title' => $this->t('Please select action for selected book'),
      '#options' => $this->bulkListBookActions(),
      '#prefix' => '<div id="ajax_selected_book_action" style="color:red;">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="book"]' => [
            'value' => 0
          ]
        ]
      ],
      '#validated' => TRUE,
    ];
    $form['chapter'] = [
      '#type' => 'select',
      '#title' => $this->t('Title of the Chapter'),
      '#options' => $options_two,
      '#prefix' => '<div id="ajax_select_chapter_list">',
      '#suffix' => '</div>',
      '#validated' => TRUE,
      '#ajax' => [
        'callback' => '::ajaxBulkExampleListCallback'
      ],
      '#states' => [
        'invisible' => [
          ':input[name="book"]' => ['value' => 0]
        ]
      ],
    ];
    $form['download_chapter'] = [
      '#type' => 'item',
      '#markup' => '<div id="ajax_download_chapter"></div>',
    ];
    $form['chapter_actions'] = [
      '#type' => 'select',
      '#title' => $this->t('Please select action for selected chapter'),
      '#options' => $this->bulkListChapterActions(),
      '#prefix' => '<div id="ajax_selected_chapter_action" style="color:red;">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="book"]' => [
            'value' => 0
          ]
        ]
      ],
      '#ajax' => ['callback' => '::ajaxBulkChapterActionsCallback'],
    ];
    $form['example'] = [
      '#type' => 'select',
      '#title' => $this->t('Example No. (Caption)'),
      '#options' => $this->ajaxBulkGetExamples(),
      '#validated' => TRUE,
      '#prefix' => '<div id="ajax_selected_example">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="book"]' => [
            'value' => 0
          ]
        ]
      ],
      '#ajax' => ['callback' => '::ajaxBulkExampleFilesCallback'],
    ];
    $form['download_example'] = [
      '#type' => 'item',
      '#markup' => '<div id="ajax_download_selected_example"></div>',
    ];
    $form['edit_example'] = [
      '#type' => 'item',
      '#markup' => '<div id="ajax_edit_selected_example"></div>',
    ];
    $form['example_files'] = [
      '#type' => 'item',
      '#markup' => '',
      '#prefix' => '<div id="ajax_example_files_list">',
      '#suffix' => '</div>',
    ];
    $form['example_actions'] = [
      '#type' => 'select',
      '#title' => $this->t('Please select action for selected example'),
      '#options' => $this->bulkListExampleActions(),
      '#prefix' => '<div id="ajax_selected_example_action" style="color:red;">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="book"]' => [
            'value' => 0
          ]
        ]
      ],
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('If Dis-Approved please specify reason for Dis-Approval'),
      '#states' => [
        'visible' => [
          [
            [
              ':input[name="book_actions"]' => [
                'value' => 3
              ]
            ],
            'or',
            [':input[name="chapter_actions"]' => ['value' => 3]],
            'or',
            [
              ':input[name="example_actions"]' => [
                'value' => 3
              ]
            ],
            'or',
            [':input[name="book_actions"]' => ['value' => 4]],
          ]
        ],
        'required' => [
          [
            [':input[name="book_actions"]' => ['value' => 3]],
            'or',
            [
              ':input[name="chapter_actions"]' => [
                'value' => 3
              ]
            ],
            'or',
            [':input[name="example_actions"]' => ['value' => 3]],
            'or',
            [
              ':input[name="book_actions"]' => [
                'value' => 4
              ]
            ],
          ]
        ],
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#states' => [
        'invisible' => [
          ':input[name="book"]' => [
            'value' => 0
          ]
        ]
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = $this->currentUser;
    $root_path = textbook_companion_path();
    
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element && $triggering_element['#value'] == $this->t('Submit')) {
      $book_id = $form_state->getValue('book');
      if ($book_id) {
        del_book_pdf($book_id);
      }
      
      if ($this->currentUser->hasPermission('bulk manage code')) {
        $query = $this->database->select('textbook_companion_preference');
        $query->fields('textbook_companion_preference');
        $query->condition('id', $book_id);
        $result = $query->execute();
        $pref_data = $result->fetchObject();
        if (!$pref_data) {
          $this->messenger->addError($this->t('Invalid book selected.'));
          return;
        }
        $prop_id = $pref_data->proposal_id;
        
        $query = $this->database->select('textbook_companion_proposal');
        $query->fields('textbook_companion_proposal');
        $query->condition('id', $prop_id);
        $user_query = $query->execute();
        $user_info = $user_query->fetchObject();
        if (!$user_info) {
          $this->messenger->addError($this->t('Invalid proposal data.'));
          return;
        }
        
        $user_data = $this->entityTypeManager->getStorage('user')->load($user_info->uid);
        if (!$user_data) {
          $this->messenger->addError($this->t('User could not be loaded.'));
          return;
        }
        
        $email_subject = '';
        $email_body = [];
        
        $book_actions = $form_state->getValue('book_actions');
        $chapter_actions = $form_state->getValue('chapter_actions');
        $example_actions = $form_state->getValue('example_actions');
        
        $site_name = \Drupal::config('system.site')->get('name');
        
        if (($book_actions == 1) && ($chapter_actions == 0) && ($example_actions == 0)) {
          /* approving entire book */
          $query = $this->database->select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $book_id);
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $preference_data = $result->fetchObject();
          if (!$preference_data) {
            $this->messenger->addError($this->t('Preference details not found or not approved.'));
            return;
          }
          
          $query = $this->database->select('textbook_companion_chapter');
          $query->fields('textbook_companion_chapter');
          $query->condition('preference_id', $book_id);
          $chapter_q = $query->execute();
          while ($chapter_data = $chapter_q->fetchObject()) {
            $query = $this->database->update('textbook_companion_example');
            $query->fields([
              'approval_status' => 1,
              'approver_uid' => $user->id(),
            ]);
            $query->condition('chapter_id', $chapter_data->id);
            $query->condition('approval_status', 0);
            $query->execute();
          }
          
          $query = $this->database->update('textbook_companion_preference');
          $query->fields(['submited_all_examples_code' => 2]);
          $query->condition('id', $book_id);
          $query->execute();
          
          $this->messenger->addStatus($this->t('Approved Entire Book.'));
          /* email */
          $email_subject = $this->t('[@site_name][Textbook Companion] Your uploaded Textbook Companion examples have been approved', [
            '@site_name' => $site_name,
          ])->render();
          $email_body = [
            0 => $this->t('
Dear @user_name,

Your all the uploaded examples for the book have been approved.

Title of the book : @book
Author name : @author
ISBN No. : @isbn
Publisher and Place : @publisher
Edition : @edition
Year of publication : @year

Best Wishes,

@site_name Team,
FOSSEE,IIT Bombay', [
              '@site_name' => $site_name,
              '@user_name' => $user_data->getDisplayName(),
              '@book' => $preference_data->book,
              '@author' => $preference_data->author,
              '@isbn' => $preference_data->isbn,
              '@publisher' => $preference_data->publisher,
              '@edition' => $preference_data->edition,
              '@year' => $preference_data->year,
            ])->render()
          ];
        }
        elseif (($book_actions == 2) && ($chapter_actions == 0) && ($example_actions == 0)) {
          /* pending entire book */
          $query = $this->database->select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $book_id);
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $preference_data = $result->fetchObject();
          if (!$preference_data) {
            $this->messenger->addError($this->t('Preference details not found or not approved.'));
            return;
          }
          
          $query = $this->database->select('textbook_companion_chapter');
          $query->fields('textbook_companion_chapter');
          $query->condition('preference_id', $book_id);
          $chapter_q = $query->execute();
          while ($chapter_data = $chapter_q->fetchObject()) {
            $query = $this->database->update('textbook_companion_example');
            $query->fields(['approval_status' => 0]);
            $query->condition('chapter_id', $chapter_data->id);
            $query->execute();
          }
          
          $this->messenger->addStatus($this->t('Pending Review Entire Book.'));
          /* email */
          $email_subject = $this->t('[@site_name] Your uploaded Textbook Companion examples have been marked as pending', [
            '@site_name' => $site_name,
          ])->render();
          $email_body = [
            0 => $this->t('
Dear @user_name,

Your all the uploaded examples for the book have been marked as pending to be reviewed. 
You will be able to see the examples after they have been approved by one of our reviewers.

Title of the book : @book
Author name : @author
ISBN No. : @isbn
Publisher and Place : @publisher
Edition : @edition
Year of publication : @year

Best Wishes,

@site_name Team,
FOSSEE,IIT Bombay', [
              '@site_name' => $site_name,
              '@user_name' => $user_data->getDisplayName(),
              '@book' => $preference_data->book,
              '@author' => $preference_data->author,
              '@isbn' => $preference_data->isbn,
              '@publisher' => $preference_data->publisher,
              '@edition' => $preference_data->edition,
              '@year' => $preference_data->year,
            ])->render()
          ];
        }
        elseif (($book_actions == 3) && ($chapter_actions == 0) && ($example_actions == 0)) {
          if (strlen(trim($form_state->getValue('message'))) <= 30) {
            $form_state->setErrorByName('message', $this->t(''));
            $this->messenger->addError($this->t('Please mention the reason for disapproval. Minimum 30 character required.'));
            return;
          }
          $query = $this->database->select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $book_id);
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $preference_data = $result->fetchObject();
          if (!$preference_data) {
            $this->messenger->addError($this->t('Preference details not found or not approved.'));
            return;
          }
          
          if (!$this->currentUser->hasPermission('bulk delete code')) {
            $this->messenger->addError($this->t('You do not have permission to Bulk Dis-Approved and Deleted Entire Book.'));
            return;
          }
          
          if (delete_book($book_id)) {
            $this->messenger->addStatus($this->t('Dis-Approved and Deleted Entire Book.'));
          }
          else {
            $this->messenger->addError($this->t('Error Dis-Approving and Deleting Entire Book.'));
          }
          /* email */
          $email_subject = $this->t('[@site_name] Your uploaded Textbook Companion examples have been marked as dis-approved', [
            '@site_name' => $site_name,
          ])->render();
          $email_body = [
            0 => $this->t('
Dear @user_name,

Your all the uploaded examples for the whole book have been marked as dis-approved.

Title of the book : @book
Author name : @author
ISBN No. : @isbn
Publisher and Place : @publisher
Edition : @edition
Year of publication : @year

Reason for dis-approval: @message

Best Wishes,

@site_name Team,
FOSSEE,IIT Bombay', [
              '@site_name' => $site_name,
              '@user_name' => $user_data->getDisplayName(),
              '@book' => $preference_data->book,
              '@author' => $preference_data->author,
              '@isbn' => $preference_data->isbn,
              '@publisher' => $preference_data->publisher,
              '@edition' => $preference_data->edition,
              '@year' => $preference_data->year,
              '@message' => $form_state->getValue('message'),
            ])->render()
          ];
        }
        elseif (($book_actions == 4) && ($chapter_actions == 0) && ($example_actions == 0)) {
          if (strlen(trim($form_state->getValue('message'))) <= 30) {
            $form_state->setErrorByName('message', $this->t(''));
            $this->messenger->addError($this->t('Please mention the reason for disapproval/deletion. Minimum 30 character required.'));
            return;
          }
          $query = $this->database->select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $book_id);
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $pref_data = $result->fetchObject();
          if (!$pref_data) {
            $this->messenger->addError($this->t('Preference details not found or not approved.'));
            return;
          }
          
          if (!$this->currentUser->hasPermission('bulk delete code')) {
            $this->messenger->addError($this->t('You do not have permission to Bulk Delete Entire Book Including Proposal.'));
            return;
          }
          
          if (delete_book($book_id)) {
            $this->messenger->addStatus($this->t('Dis-Approved and Deleted Entire Book examples.'));
            $dir_path = $root_path . $book_id;
            if (is_dir($dir_path)) {
              $res = rmdir($dir_path);
              if (!$res) {
                $this->messenger->addError($this->t("Cannot delete Book directory : @dir_path. Please contact administrator.", ['@dir_path' => $dir_path]));
                return;
              }
            }
            else {
              $this->messenger->addStatus($this->t("Book directory not present : @dir_path. Skipping deleting book directory.", ['@dir_path' => $dir_path]));
            }
            /* deleting preference and proposal */
            $query = $this->database->select('textbook_companion_preference');
            $query->fields('textbook_companion_preference');
            $query->condition('id', $book_id);
            $result = $query->execute();
            $preference_data = $result->fetchObject();
            $proposal_id = $preference_data->proposal_id;
            
            $query = $this->database->delete('textbook_companion_preference');
            $query->condition('proposal_id', $proposal_id);
            $query->execute();
            
            $query = $this->database->delete('textbook_companion_proposal');
            $query->condition('id', $proposal_id);
            $query->execute();
            
            $this->messenger->addStatus($this->t('Deleted Book Proposal.'));
            /* email */
            $email_subject = $this->t('[@site_name] Your uploaded Textbook Companion examples including the book proposal have been deleted', [
              '@site_name' => $site_name,
            ])->render();
            $email_body = [
              0 => $this->t('
Dear @user_name,

We regret to inform you that all the uploaded examples including the book with following details have been deleted permanently.

Title of the book : @book
Author name : @author
ISBN No. : @isbn
Publisher and Place : @publisher
Edition : @edition
Year of publication : @year

Reason for deletion: @message

Best Wishes,

@site_name Team,
FOSSEE,IIT Bombay', [
                '@site_name' => $site_name,
                '@user_name' => $user_data->getDisplayName(),
                '@book' => $pref_data->book,
                '@author' => $pref_data->author,
                '@isbn' => $pref_data->isbn,
                '@publisher' => $pref_data->publisher,
                '@edition' => $pref_data->edition,
                '@year' => $pref_data->year,
                '@message' => $form_state->getValue('message'),
              ])->render()
            ];
          }
          else {
            $this->messenger->addError($this->t('Error Dis-Approving and Deleting Entire Book.'));
          }
        }
        elseif (($book_actions == 0) && ($chapter_actions == 1) && ($example_actions == 0)) {
          $chapter_id = $form_state->getValue('chapter');
          $query = $this->database->select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $book_id);
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $pref_data = $result->fetchObject();
          
          $query = $this->database->select('textbook_companion_chapter');
          $query->fields('textbook_companion_chapter');
          $query->condition('preference_id', $book_id);
          $query->condition('id', $chapter_id);
          $result = $query->execute();
          $chap_data = $result->fetchObject();
          
          $query = $this->database->update('textbook_companion_example');
          $query->fields([
            'approval_status' => 1,
            'approver_uid' => $user->id(),
          ]);
          $query->condition('chapter_id', $chapter_id);
          $query->condition('approval_status', 0);
          $query->execute();
          
          $this->messenger->addStatus($this->t('Approved Entire Chapter.'));
          /* email */
          $email_subject = $this->t('[@site_name] Your uploaded Textbook Companion examples have been approved', [
            '@site_name' => $site_name,
          ])->render();
          $email_body = [
            0 => $this->t('
Dear @user_name,

Your all the uploaded examples for the chapter have been approved. 

Title of the book : @book
Title of the chapter : @chapter

Best Wishes,

@site_name Team,
FOSSEE,IIT Bombay', [
              '@site_name' => $site_name,
              '@user_name' => $user_data->getDisplayName(),
              '@book' => $pref_data->book,
              '@chapter' => $chap_data->name,
            ])->render()
          ];
        }
        elseif (($book_actions == 0) && ($chapter_actions == 2) && ($example_actions == 0)) {
          $chapter_id = $form_state->getValue('chapter');
          $query = $this->database->select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $book_id);
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $pref_data = $result->fetchObject();
          
          $query = $this->database->select('textbook_companion_chapter');
          $query->fields('textbook_companion_chapter');
          $query->condition('preference_id', $book_id);
          $query->condition('id', $chapter_id);
          $result = $query->execute();
          $chap_data = $result->fetchObject();
          
          $query = $this->database->update('textbook_companion_example');
          $query->fields(['approval_status' => 0]);
          $query->condition('chapter_id', $chapter_id);
          $query->execute();
          
          $this->messenger->addStatus($this->t('Entire Chapter marked as Pending Review.'));
          /* email */
          $email_subject = $this->t('[@site_name] Your uploaded Textbook Companion examples have been marked as pending', [
            '@site_name' => $site_name,
          ])->render();
          $email_body = [
            0 => $this->t('
Dear @user_name,

Your all the uploaded examples for the chapter have been marked as pending to be reviewed.

Title of the book : @book
Title of the chapter : @chapter

Best Wishes,

@site_name Team,
FOSSEE,IIT Bombay', [
              '@site_name' => $site_name,
              '@user_name' => $user_data->getDisplayName(),
              '@book' => $pref_data->book,
              '@chapter' => $chap_data->name,
            ])->render()
          ];
        }
        elseif (($book_actions == 0) && ($chapter_actions == 3) && ($example_actions == 0)) {
          $chapter_id = $form_state->getValue('chapter');
          $query = $this->database->select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $book_id);
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $pref_data = $result->fetchObject();
          
          $query = $this->database->select('textbook_companion_chapter');
          $query->fields('textbook_companion_chapter');
          $query->condition('preference_id', $book_id);
          $query->condition('id', $chapter_id);
          $result = $query->execute();
          $chap_data = $result->fetchObject();
          
          if (strlen(trim($form_state->getValue('message'))) <= 30) {
            $form_state->setErrorByName('message', $this->t(''));
            $this->messenger->addError($this->t('Please mention the reason for disapproval. Minimum 30 character required.'));
            return;
          }
          
          if (!$this->currentUser->hasPermission('bulk delete code')) {
            $this->messenger->addError($this->t('You do not have permission to Bulk Dis-Approved and Deleted Entire Chapter.'));
            return;
          }
          
          if (delete_chapter($chapter_id)) {
            $this->messenger->addStatus($this->t('Dis-Approved and Deleted Entire Chapter.'));
          }
          else {
            $this->messenger->addError($this->t('Error Dis-Approving and Deleting Entire Chapter.'));
          }
          /* email */
          $email_subject = $this->t('[@site_name] Your uploaded Textbook Companion example have been marked as dis-approved', [
            '@site_name' => $site_name,
          ])->render();
          $email_body = [
            0 => $this->t('
Dear @user_name,

Your uploaded example for the entire chapter have been marked as dis-approved.

Title of the book : @book
Title of the chapter : @chapter

Reason for dis-approval: @message

Best Wishes,

@site_name Team,
FOSSEE,IIT Bombay', [
              '@site_name' => $site_name,
              '@user_name' => $user_data->getDisplayName(),
              '@book' => $pref_data->book,
              '@chapter' => $chap_data->name,
              '@message' => $form_state->getValue('message'),
            ])->render()
          ];
        }
        elseif (($book_actions == 0) && ($chapter_actions == 0) && ($example_actions == 1)) {
          $chapter_id = $form_state->getValue('chapter');
          $example_id = $form_state->getValue('example');
          
          $query = $this->database->select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $book_id);
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $pref_data = $result->fetchObject();
          
          $query = $this->database->select('textbook_companion_chapter');
          $query->fields('textbook_companion_chapter');
          $query->condition('preference_id', $book_id);
          $query->condition('id', $chapter_id);
          $result = $query->execute();
          $chap_data = $result->fetchObject();
          
          $query = $this->database->select('textbook_companion_example');
          $query->fields('textbook_companion_example');
          $query->condition('id', $example_id);
          $result = $query->execute();
          $examp_data = $result->fetchObject();
          
          $query = $this->database->update('textbook_companion_example');
          $query->fields([
            'approval_status' => 1,
            'approver_uid' => $user->id(),
          ]);
          $query->condition('id', $example_id);
          $query->execute();
          
          $this->messenger->addStatus($this->t('Example approved.'));
          /* email */
          $email_subject = $this->t('[@site_name] Your uploaded Textbook Companion example have been approved', [
            '@site_name' => $site_name,
          ])->render();
          $email_body = [
            0 => $this->t('
Dear @user_name,

Your example for DWSIM Textbook Companion with the following details is approved.

Title of the book : @book
Title of the chapter : @chapter
Example number : @number
Caption : @caption

Best Wishes,

@site_name Team,
FOSSEE,IIT Bombay', [
              '@site_name' => $site_name,
              '@user_name' => $user_data->getDisplayName(),
              '@book' => $pref_data->book,
              '@chapter' => $chap_data->name,
              '@number' => $examp_data->number,
              '@caption' => $examp_data->caption,
            ])->render()
          ];
        }
        elseif (($book_actions == 0) && ($chapter_actions == 0) && ($example_actions == 2)) {
          $chapter_id = $form_state->getValue('chapter');
          $example_id = $form_state->getValue('example');
          
          $query = $this->database->select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $book_id);
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $pref_data = $result->fetchObject();
          
          $query = $this->database->select('textbook_companion_chapter');
          $query->fields('textbook_companion_chapter');
          $query->condition('preference_id', $book_id);
          $query->condition('id', $chapter_id);
          $result = $query->execute();
          $chap_data = $result->fetchObject();
          
          $query = $this->database->select('textbook_companion_example');
          $query->fields('textbook_companion_example');
          $query->condition('id', $example_id);
          $result = $query->execute();
          $examp_data = $result->fetchObject();
          
          $query = $this->database->update('textbook_companion_example');
          $query->fields(['approval_status' => 0]);
          $query->condition('id', $example_id);
          $query->execute();
          
          $this->messenger->addStatus($this->t('Example marked as Pending Review.'));
          /* email */
          $email_subject = $this->t('[@site_name] Your uploaded Textbook Companion example has been marked as pending', [
            '@site_name' => $site_name,
          ])->render();
          $email_body = [
            0 => $this->t('
Dear @user_name,

Your uploaded example for DWSIM Textbook Companion with the following details has been marked as pending to be reviewed.

Title of the book : @book
Title of the chapter : @chapter
Example number : @number
Caption : @caption

Best Wishes,

@site_name Team,
FOSSEE,IIT Bombay', [
              '@site_name' => $site_name,
              '@user_name' => $user_data->getDisplayName(),
              '@book' => $pref_data->book,
              '@chapter' => $chap_data->name,
              '@number' => $examp_data->number,
              '@caption' => $examp_data->caption,
            ])->render()
          ];
        }
        elseif (($book_actions == 0) && ($chapter_actions == 0) && ($example_actions == 3)) {
          $chapter_id = $form_state->getValue('chapter');
          $example_id = $form_state->getValue('example');
          
          if (strlen(trim($form_state->getValue('message'))) <= 30) {
            $form_state->setErrorByName('message', $this->t(''));
            $this->messenger->addError($this->t('Please mention the reason for disapproval. Minimum 30 character required.'));
            return;
          }
          
          $query = $this->database->select('textbook_companion_preference');
          $query->fields('textbook_companion_preference');
          $query->condition('id', $book_id);
          $query->condition('approval_status', 1);
          $result = $query->execute();
          $pref_data = $result->fetchObject();
          
          $query = $this->database->select('textbook_companion_chapter');
          $query->fields('textbook_companion_chapter');
          $query->condition('preference_id', $book_id);
          $query->condition('id', $chapter_id);
          $result = $query->execute();
          $chap_data = $result->fetchObject();
          
          $query = $this->database->select('textbook_companion_example');
          $query->fields('textbook_companion_example');
          $query->condition('id', $example_id);
          $result = $query->execute();
          $examp_data = $result->fetchObject();
          
          if (delete_example($example_id)) {
            $this->messenger->addStatus($this->t('Example Dis-Approved and Deleted.'));
          }
          else {
            $this->messenger->addError($this->t('Error Dis-Approving and Deleting Example.'));
          }
          /* email */
          $email_subject = $this->t('[@site_name] Your uploaded Textbook Companion example has been marked as dis-approved', [
            '@site_name' => $site_name,
          ])->render();
          $email_body = [
            0 => $this->t('
Dear @user_name,

Your example for DWSIM Textbook Companion has been marked as dis-approved and deleted.

Title of the book : @book
Title of the chapter : @chapter
Example number : @number
Caption : @caption

Reason for dis-approval: @message

Best Wishes,

@site_name Team,
FOSSEE,IIT Bombay', [
              '@site_name' => $site_name,
              '@user_name' => $user_data->getDisplayName(),
              '@book' => $pref_data->book,
              '@chapter' => $chap_data->name,
              '@number' => $examp_data->number,
              '@caption' => $examp_data->caption,
              '@message' => $form_state->getValue('message'),
            ])->render()
          ];
        }
        else {
          $this->messenger->addError($this->t('Please select only one action at a time.'));
          return;
        }
        
        /****** sending email when everything done ******/
        if ($email_subject) {
          $email_to = $user_data->getEmail();
          if (!$this->mailService->sendNotification('textbook_companion', 'standard', $email_to, $email_subject, implode("\n", $email_body))) {
            $this->messenger->addError($this->t('Error sending email message.'));
          }
        }
      }
      else {
        $this->messenger->addError($this->t('You do not have permission to bulk manage code.'));
      }
    }
  }

  /**
   * AJAX callback to refresh chapter and book actions select list when a book is selected.
   */
  public function ajaxBulkChapterListCallback(array &$form, FormStateInterface $form_state) {
    $book_default_value = $form_state->getValue('book');
    if ($book_default_value > 0) {
      $download_uri = Url::fromUri('internal:/textbook-companion/full-download/book/' . $book_default_value);
      $download_link = Link::fromTextAndUrl($this->t('Download'), $download_uri)->toString() . ' ' . $this->t('(Download all the approved and unapproved examples of the entire book)');

      $form['book_actions']['#options'] = $this->bulkListBookActions();
      $form['chapter']['#options'] = $this->ajaxBulkGetChapterList($book_default_value);
      $form['chapter_actions']['#options'] = $this->bulkListChapterActions();
      $form['example_actions']['#options'] = $this->bulkListExampleActions();
      $form['example_files']['#title'] = '';
      $form['example_files']['#markup'] = '';

      // Preserve original final command per selector (replace-then-clear => html '').
      return $this->ajaxHelper->buildMultiCommandResponse([
        '#ajax_selected_book' => ['type' => 'html', 'content' => $download_link],
        '#ajax_selected_book_action' => ['type' => 'replace', 'content' => $form['book_actions']],
        '#ajax_select_chapter_list' => ['type' => 'replace', 'content' => $form['chapter']],
        '#ajax_download_chapter' => ['type' => 'html', 'content' => ''],
        '#ajax_selected_chapter_action' => ['type' => 'html', 'content' => ''],
        '#ajax_selected_example' => ['type' => 'html', 'content' => ''],
        '#ajax_selected_example_action' => ['type' => 'html', 'content' => ''],
        '#ajax_download_selected_example' => ['type' => 'html', 'content' => ''],
        '#ajax_edit_selected_example' => ['type' => 'html', 'content' => ''],
        '#ajax_example_files_list' => ['type' => 'replace', 'content' => $form['example_files']],
      ]);
    }

    $form['chapter']['#options'] = $this->ajaxBulkGetChapterList();
    $form['book_actions']['#options'] = $this->bulkListBookActions();
    $form['chapter_actions']['#options'] = $this->bulkListChapterActions();
    $form['example_actions']['#options'] = $this->bulkListExampleActions();
    $form['example_files']['#title'] = '';
    $form['example_files']['#markup'] = '';

    return $this->ajaxHelper->buildMultiCommandResponse([
      '#ajax_selected_book' => ['type' => 'html', 'content' => ''],
      '#ajax_selected_book_pdf' => ['type' => 'html', 'content' => ''],
      '#ajax_selected_book_regenerate_pdf' => ['type' => 'html', 'content' => ''],
      '#ajax_selected_book_notes' => ['type' => 'html', 'content' => ''],
      '#ajax_select_chapter_list' => ['type' => 'html', 'content' => ''],
      '#ajax_selected_book_action' => ['type' => 'html', 'content' => ''],
      '#ajax_selected_chapter_action' => ['type' => 'html', 'content' => ''],
      '#ajax_download_chapter' => ['type' => 'html', 'content' => ''],
      '#ajax_selected_example' => ['type' => 'html', 'content' => ''],
      '#ajax_selected_example_action' => ['type' => 'html', 'content' => ''],
      '#ajax_download_selected_example' => ['type' => 'html', 'content' => ''],
      '#ajax_edit_selected_example' => ['type' => 'html', 'content' => ''],
      '#ajax_example_files_list' => ['type' => 'replace', 'content' => $form['example_files']],
    ]);
  }

  /**
   * AJAX callback to refresh example list when a chapter is selected.
   */
  public function ajaxBulkExampleListCallback(array &$form, FormStateInterface $form_state) {
    $chapter_default_value = $form_state->getValue('chapter');
    if ($chapter_default_value > 0) {
      $download_uri = Url::fromUri('internal:/textbook-companion/full-download/chapter/' . $chapter_default_value);
      $download_link = Link::fromTextAndUrl($this->t('Download'), $download_uri)->toString() . ' ' . $this->t('(Download all the approved and unapproved examples of the entire chapter)');

      $form['chapter_actions']['#options'] = $this->bulkListChapterActions();
      $form['example']['#options'] = $this->ajaxBulkGetExamples($chapter_default_value);
      $form['example_actions']['#options'] = $this->bulkListExampleActions();
      $form['example_files']['#title'] = '';
      $form['example_files']['#markup'] = '';

      return $this->ajaxHelper->buildMultiCommandResponse([
        '#ajax_download_chapter' => ['type' => 'html', 'content' => $download_link],
        '#ajax_selected_chapter_action' => ['type' => 'replace', 'content' => $form['chapter_actions']],
        '#ajax_selected_example' => ['type' => 'replace', 'content' => $form['example']],
        '#ajax_download_selected_example' => ['type' => 'html', 'content' => ''],
        '#ajax_edit_selected_example' => ['type' => 'html', 'content' => ''],
        '#ajax_selected_example_action' => ['type' => 'html', 'content' => ''],
        '#ajax_example_files_list' => ['type' => 'replace', 'content' => $form['example_files']],
      ]);
    }

    $form['chapter_actions']['#options'] = $this->bulkListChapterActions();
    $form['example']['#options'] = $this->ajaxBulkGetExamples();
    $form['example_files']['#title'] = '';
    $form['example_files']['#markup'] = '';
    $form['example_actions']['#options'] = $this->bulkListExampleActions();

    return $this->ajaxHelper->buildMultiCommandResponse([
      '#ajax_download_chapter' => ['type' => 'html', 'content' => ''],
      '#ajax_selected_chapter_action' => ['type' => 'html', 'content' => ''],
      '#ajax_selected_example' => ['type' => 'html', 'content' => ''],
      '#ajax_download_selected_example' => ['type' => 'html', 'content' => ''],
      '#ajax_edit_selected_example' => ['type' => 'html', 'content' => ''],
      '#ajax_example_files_list' => ['type' => 'replace', 'content' => $form['example_files']],
      '#ajax_selected_example_action' => ['type' => 'html', 'content' => ''],
    ]);
  }

  /**
   * AJAX callback to refresh example files list when an example is selected.
   */
  public function ajaxBulkExampleFilesCallback(array &$form, FormStateInterface $form_state) {
    $example_list_default_value = $form_state->getValue('example');
    if ($example_list_default_value > 0) {
      $query = $this->database->select('textbook_companion_example_files');
      $query->fields('textbook_companion_example_files');
      $query->condition('example_id', $example_list_default_value);
      $example_list_q = $query->execute();
      if ($example_list_q) {
        $example_files_rows = [];
        while ($example_list_data = $example_list_q->fetchObject()) {
          $example_file_type = '';
          switch ($example_list_data->filetype) {
            case 'S':
              $example_file_type = $this->t('Source or Main file');
              break;
            case 'R':
              $example_file_type = $this->t('Result file');
              break;
            case 'X':
              $example_file_type = $this->t('xcos file');
              break;
            default:
              $example_file_type = $this->t('Unknown');
              break;
          }
          $example_files_rows[] = [
            Link::fromTextAndUrl($example_list_data->filename, Url::fromUri('internal:/textbook-companion/download/file/' . $example_list_data->id))->toString(),
            $example_file_type
          ];
        }

        $example_files_header = [$this->t('Filename'), $this->t('Type')];
        $example_files_table = [
          '#type' => 'table',
          '#header' => $example_files_header,
          '#rows' => $example_files_rows,
        ];

        $form['example_files']['#title'] = $this->t('List of example files');
        $form['example_files']['#markup'] = $this->renderer->renderPlain($example_files_table);
        $form['example_actions']['#options'] = $this->bulkListExampleActions();

        $download_link = Link::fromTextAndUrl($this->t('Download Example'), Url::fromUri('internal:/textbook-companion/download/example/' . $example_list_default_value))->toString();
        $edit_link = Link::fromTextAndUrl($this->t('Edit Example'), Url::fromUri('internal:/code_approval/editcode/' . $example_list_default_value))->toString();

        return $this->ajaxHelper->buildMultiCommandResponse([
          '#ajax_example_files_list' => ['type' => 'replace', 'content' => $form['example_files']],
          '#ajax_download_selected_example' => ['type' => 'html', 'content' => $download_link],
          '#ajax_edit_selected_example' => ['type' => 'html', 'content' => $edit_link],
          '#ajax_selected_example_action' => ['type' => 'replace', 'content' => $form['example_actions']],
        ]);
      }

      // Example selected but no files query result — empty response (same as before).
      return $this->ajaxHelper->buildMultiCommandResponse([]);
    }

    $form['example_files']['#title'] = '';
    $form['example_files']['#markup'] = '';
    $form['example_actions']['#options'] = $this->bulkListExampleActions();

    return $this->ajaxHelper->buildMultiCommandResponse([
      '#ajax_download_selected_example' => ['type' => 'html', 'content' => ''],
      '#ajax_edit_selected_example' => ['type' => 'html', 'content' => ''],
      '#ajax_example_files_list' => ['type' => 'replace', 'content' => $form['example_files']],
      '#ajax_selected_example_action' => ['type' => 'html', 'content' => ''],
    ]);
  }

  /**
   * AJAX callback placeholder for chapter actions.
   */
  public function ajaxBulkChapterActionsCallback(array &$form, FormStateInterface $form_state) {
    return $this->ajaxHelper->buildMultiCommandResponse([]);
  }

  /**
   * Get list of books.
   */
  protected function bulkListOfBooks() {
    $book_titles = [
      '0' => $this->t('Please select...'),
    ];
    $query = $this->database->select('textbook_companion_preference', 'pp');
    $query->join('textbook_companion_proposal', 'p', 'pp.proposal_id=p.id');
    $query->join('users_field_data', 'u', 'p.uid=u.uid');
    $query->fields('u', ['name']);
    $query->fields('pp', ['id', 'book', 'author']);
    
    $or = $this->database->condition('or');
    $or->condition('approval_status', 1);
    $or->condition('approval_status', 3);
    $query->condition($or);
    $query->orderBy('book', 'ASC');
    $book_titles_q = $query->execute();
    while ($book_titles_data = $book_titles_q->fetchObject()) {
      $book_titles[$book_titles_data->id] = $book_titles_data->book . ' (Written by ' . $book_titles_data->author . ')' . ' (Proposed by ' . $book_titles_data->name . ')';
    }
    return $book_titles;
  }

  /**
   * Get list of chapters for selected book.
   */
  protected function ajaxBulkGetChapterList($preference_id = 0) {
    $book_chapters = [
      '0' => $this->t('Please select...'),
    ];
    if ($preference_id > 0) {
      $query = $this->database->select('textbook_companion_chapter');
      $query->fields('textbook_companion_chapter');
      $query->condition('preference_id', $preference_id);
      $query->orderBy('number', 'ASC');
      $book_chapters_q = $query->execute();
      while ($book_chapters_data = $book_chapters_q->fetchObject()) {
        $book_chapters[$book_chapters_data->id] = $book_chapters_data->number . '. ' . $book_chapters_data->name;
      }
    }
    return $book_chapters;
  }

  /**
   * Get list of examples for selected chapter.
   */
  protected function ajaxBulkGetExamples($chapter_id = 0) {
    $book_examples = [
      '0' => $this->t('Please select...'),
    ];
    if ($chapter_id > 0) {
      $query = $this->database->select('textbook_companion_example');
      $query->fields('textbook_companion_example');
      $query->condition('chapter_id', $chapter_id);
      $book_examples_q = $query->execute();
      while ($book_examples_data = $book_examples_q->fetchObject()) {
        $book_examples[$book_examples_data->id] = $book_examples_data->number . ' (' . $book_examples_data->caption . ')';
      }
    }
    return $book_examples;
  }

  /**
   * Get book actions.
   */
  protected function bulkListBookActions() {
    return [
      '0' => $this->t('Please select...'),
      '1' => $this->t('Approve Entire Book'),
      '2' => $this->t('Pending Review Entire Book'),
      '3' => $this->t('Dis-Approve Entire Book (This will delete all the examples in the book)'),
      '4' => $this->t('Delete Entire Book Including Proposal'),
    ];
  }

  /**
   * Get chapter actions.
   */
  protected function bulkListChapterActions() {
    return [
      '0' => $this->t('Please select...'),
      '1' => $this->t('Approve Entire Chapter'),
      '2' => $this->t('Pending Review Entire Chapter'),
      '3' => $this->t('Dis-Approve Entire Chapter (This will delete all the examples in the chapter)'),
    ];
  }

  /**
   * Get example actions.
   */
  protected function bulkListExampleActions() {
    return [
      '0' => $this->t('Please select...'),
      '1' => $this->t('Approve Example'),
      '2' => $this->t('Pending Review Example'),
      '3' => $this->t('Dis-approve Example (This will delete the example)'),
    ];
  }

}
