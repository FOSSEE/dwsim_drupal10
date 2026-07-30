<?php

/**
 * @file
 * Form for browsing and downloading textbook companion examples.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Cascading book/chapter/example download form.
 */
class TextbookCompanionRunForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a TextbookCompanionRunForm instance.
   *
   * @param \Drupal\Core\Database\Connection $database
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   */
  public function __construct(Connection $database, RequestStack $request_stack) {
    $this->database = $database;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'textbook_companion_run_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $url_book_pref_id = $this->requestStack->getCurrentRequest()->attributes->get('book_pref_id') ?? 0;
    $category_default_value = 0;

    if ($url_book_pref_id) {
      $result = $this->database->select('textbook_companion_preference', 't')
        ->fields('t', ['category'])
        ->condition('id', $url_book_pref_id)
        ->execute()->fetchObject();
      $category_default_value = $result ? $result->category : 0;
    }

    // Values from form_state (AJAX) or route attribute defaults.
    $selected_book    = (int) ($form_state->getValue('book') ?: $url_book_pref_id);
    $selected_chapter = (int) $form_state->getValue('chapter');
    $selected_example = (int) ($form_state->getValue('examples') ?? 0);

    // Book select (top-level).
    $form['book'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Title of the book'),
      '#options'       => $this->_list_of_books($category_default_value),
      '#default_value' => $selected_book,
      '#ajax'          => [
        'callback' => '::ajax_book_changed_callback',
        'wrapper'  => 'textbook-book-wrapper',
        'event'    => 'change',
      ],
    ];

    // Book wrapper (contains book info, chapter select & chapter download).
    $form['book_wrapper'] = [
      '#type'       => 'container',
      '#attributes' => ['id' => 'textbook-book-wrapper'],
    ];

    if ($selected_book) {
      $form['book_wrapper']['book_info'] = [
        '#type'   => 'markup',
        '#markup' => $this->_html_book_info($selected_book),
      ];

      $form['book_wrapper']['download_book'] = [
        '#type'   => 'markup',
        '#markup' => Link::fromTextAndUrl(
          $this->t('Download Book'),
          Url::fromRoute('textbook_companion.download_book', ['book_id' => $selected_book])
        )->toString(),
      ];

      // Chapter select.
      $form['book_wrapper']['chapter'] = [
        '#type'          => 'select',
        '#title'         => $this->t('Title of the chapter'),
        '#options'       => $this->_list_of_chapters($selected_book),
        '#default_value' => $selected_chapter,
        '#ajax'          => [
          'callback' => '::ajax_chapter_changed_callback',
          'wrapper'  => 'chapter-download-wrapper',
          'event'    => 'change',
        ],
      ];

      // Chapter-download wrapper (inside book_wrapper).
      $form['chapter_download'] = [
        '#type'       => 'container',
        '#attributes' => ['id' => 'chapter-download-wrapper'],
      ];

      if ($selected_chapter) {
        $form['chapter_download']['link'] = [
          '#type'   => 'markup',
          '#markup' => Link::fromTextAndUrl(
            $this->t('Download Chapter'),
            Url::fromRoute('textbook_companion.download_chapter', ['chapter_id' => $selected_chapter])
          )->toString(),
        ];
      }
    }

    // Example dropdown.
    $form['chapter_download']['examples'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Name of the example'),
      '#options'       => $this->_list_of_examples($selected_chapter, $selected_example),
      '#default_value' => $selected_example,
      '#ajax'          => [
        'callback' => '::ajax_example_changed_callback',
        'wrapper'  => 'download-example-link-wrapper',
        'event'    => 'change',
      ],
    ];

    // Download example + files wrapper.
    $form['download_example_wrapper'] = [
      '#type'       => 'container',
      '#attributes' => ['id' => 'download-example-link-wrapper'],
    ];

    if (!empty($selected_example)) {
      $form['download_example_wrapper']['download_example'] = [
        '#type'   => 'markup',
        '#markup' => Link::fromTextAndUrl(
          $this->t('Download Example (OpenFOAM code)'),
          Url::fromRoute('textbook_companion.download_example', ['example_id' => $selected_example])
        )->toString(),
      ];

      // Example files table.
      $files = $this->database->select('textbook_companion_example_files', 'f')
        ->fields('f')
        ->condition('example_id', $selected_example)
        ->execute()->fetchAll();

      if (!empty($files)) {
        $rows = [];
        foreach ($files as $file) {
          switch ($file->filetype) {
            case 'S': $type = 'Source or Main file'; break;
            case 'R': $type = 'Result file'; break;
            case 'X': $type = 'xcos file'; break;
            default:  $type = 'Unknown'; break;
          }
          $file_id = $file->id ?? NULL;
          $link = Link::fromTextAndUrl(
            $file->filename,
            Url::fromUserInput('/textbook-companion/download/file/' . $file_id)
          )->toRenderable();
          $rows[] = [
            'filename' => ['data' => $link],
            'type'     => ['data' => ['#markup' => $type]],
          ];
        }

        $form['download_example_wrapper']['example_files'] = [
          '#type'       => 'fieldset',
          '#title'      => $this->t('List of Example Files'),
          '#attributes' => ['id' => 'ajax-download-example-files-replace'],
          'table'       => [
            '#type'       => 'table',
            '#header'     => [$this->t('Filename'), $this->t('Type')],
            '#rows'       => $rows,
            '#empty'      => $this->t('No files found for this example.'),
            '#attributes' => ['style' => 'width: 100%;'],
          ],
        ];
      }
    }

    return $form;
  }

  // ---------------------------
  // AJAX CALLBACKS
  // ---------------------------

  /**
   * Returns the book wrapper after book change.
   */
  public function ajax_book_changed_callback(array &$form, FormStateInterface $form_state) {
    return $form['book_wrapper'];
  }

  /**
   * Returns the chapter download wrapper after chapter change.
   */
  public function ajax_chapter_changed_callback(array &$form, FormStateInterface $form_state) {
    return $form['chapter_download'];
  }

  /**
   * Returns the download example wrapper after example change.
   */
  public function ajax_example_changed_callback(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    return $form['download_example_wrapper'];
  }

  // ---------------------------
  // HELPER METHODS
  // ---------------------------

  /**
   * Returns list of books for a given category.
   */
  public function _list_of_books($category_default_value = 0) {
    $book_titles = [0 => $this->t('Please select ...')];

    $subquery = $this->database->select('textbook_companion_proposal', 'tcp');
    $subquery->fields('tcp', ['id']);
    $subquery->condition('proposal_status', 3);

    $query = $this->database->select('textbook_companion_preference', 'tcp');
    $query->fields('tcp', ['id', 'book', 'author']);
    $query->condition('category', $category_default_value);
    $query->condition('approval_status', 1);
    $query->condition('proposal_id', $subquery, 'IN');
    $query->orderBy('book', 'ASC');

    foreach ($query->execute()->fetchAll() as $book) {
      $book_titles[$book->id] = $book->book . ' (Written by ' . $book->author . ')';
    }

    return $book_titles;
  }

  /**
   * Returns book info HTML block for a given preference ID.
   */
  public function _html_book_info($preference_id) {
    $query = $this->database->select('textbook_companion_proposal', 'proposal');
    $query->leftJoin('textbook_companion_preference', 'preference', 'proposal.id = preference.proposal_id');
    $query->addField('preference', 'book',      'preference_book');
    $query->addField('preference', 'author',    'preference_author');
    $query->addField('preference', 'isbn',      'preference_isbn');
    $query->addField('preference', 'publisher', 'preference_publisher');
    $query->addField('preference', 'edition',   'preference_edition');
    $query->addField('preference', 'year',      'preference_year');
    $query->addField('proposal',   'full_name', 'proposal_full_name');
    $query->addField('proposal',   'faculty',   'proposal_faculty');
    $query->addField('proposal',   'reviewer',  'proposal_reviewer');
    $query->addField('proposal',   'course',    'proposal_course');
    $query->addField('proposal',   'branch',    'proposal_branch');
    $query->addField('proposal',   'university','proposal_university');
    $query->condition('preference.id', $preference_id);

    $d = $query->execute()->fetchObject();
    if (!$d) {
      return '';
    }

    $html  = '<table style="width:100%;" border="0"><tr><td style="width:50%;vertical-align:top;">';
    $html .= '<strong>About the Book</strong><ul>';
    $html .= '<li><strong>Author:</strong> '    . $d->preference_author    . '</li>';
    $html .= '<li><strong>Title:</strong> '     . $d->preference_book      . '</li>';
    $html .= '<li><strong>Publisher:</strong> ' . $d->preference_publisher . '</li>';
    $html .= '<li><strong>Year:</strong> '      . $d->preference_year      . '</li>';
    $html .= '<li><strong>Edition:</strong> '   . $d->preference_edition   . '</li>';
    $html .= '</ul></td><td style="width:50%;vertical-align:top;">';
    $html .= '<strong>About the Contributor</strong><ul>';
    $html .= '<li><strong>Name:</strong> '     . $d->proposal_full_name  . '</li>';
    $html .= '<li><strong>Faculty:</strong> '  . $d->proposal_faculty    . '</li>';
    $html .= '<li><strong>Reviewer:</strong> ' . $d->proposal_reviewer   . '</li>';
    $html .= '<li><strong>Course:</strong> '   . $d->proposal_course     . ', ' . $d->proposal_branch . ', ' . $d->proposal_university . '</li>';
    $html .= '</ul></td></tr></table>';

    return $html;
  }

  /**
   * Returns list of chapters for a given preference ID.
   */
  public function _list_of_chapters($preference_id = 0) {
    $book_chapters = [0 => $this->t('Please select...')];
    if (!$preference_id) {
      return $book_chapters;
    }

    $query = $this->database->select('textbook_companion_chapter', 'tcc');
    $query->fields('tcc', ['id', 'name', 'number']);
    $query->condition('preference_id', $preference_id);
    $query->orderBy('number', 'ASC');

    foreach ($query->execute()->fetchAll() as $chapter) {
      $book_chapters[$chapter->id] = $chapter->number . '. ' . $chapter->name;
    }

    return $book_chapters;
  }

  /**
   * Returns list of approved examples for a given chapter ID.
   */
  public function _list_of_examples($chapter_id = 0, $selected_example = 0) {
    $examples = [0 => $this->t('Please select...')];
    if (!$chapter_id) {
      return $examples;
    }

    $query = $this->database->select('textbook_companion_example', 'tce');
    $query->fields('tce', ['id', 'number', 'caption']);
    $query->condition('chapter_id', $chapter_id);
    $query->condition('approval_status', 1);

    foreach ($query->execute()->fetchAll() as $example) {
      $examples[$example->id] = $example->number . '. ' . $example->caption;
    }

    return $examples;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}