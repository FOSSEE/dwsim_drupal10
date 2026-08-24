<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\TextbookCompanionSearchForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TextbookCompanionSearchForm extends FormBase {

  protected $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  public function getFormId() {
    return 'textbook_companion_search_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = \Drupal::request();
    $search_term = $request->query->get('search') ?? '';
    $by_title = $request->query->get('by_title') ?? 1;
    $by_author = $request->query->get('by_author') ?? 1;

    $form['search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search'),
      '#size' => 48,
      '#default_value' => $search_term,
    ];
    $form['search_by_title'] = [
      '#type' => 'checkbox',
      '#default_value' => $by_title,
      '#title' => $this->t('Search by Title of the Book'),
    ];
    $form['search_by_author'] = [
      '#type' => 'checkbox',
      '#default_value' => $by_author,
      '#title' => $this->t('Search by Author of the Book'),
    ];
    $form['submit'] = ['#type' => 'submit', '#value' => $this->t('Search')];
    $form['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('<front>'),
      '#attributes' => ['class' => ['button']],
    ];

    if (!empty($search_term)) {
      $query = $this->database->select('textbook_companion_preference', 'pref');
      $query->fields('pref', ['id', 'book', 'author']);
      $query->condition('approval_status', 1);

      if ($by_title && $by_author) {
        $or = $query->orConditionGroup()
          ->condition('book', '%' . $this->database->escapeLike($search_term) . '%', 'LIKE')
          ->condition('author', '%' . $this->database->escapeLike($search_term) . '%', 'LIKE');
        $query->condition($or);
      }
      elseif ($by_title) {
        $query->condition('book', '%' . $this->database->escapeLike($search_term) . '%', 'LIKE');
      }
      elseif ($by_author) {
        $query->condition('author', '%' . $this->database->escapeLike($search_term) . '%', 'LIKE');
      }
      else {
        $this->messenger()->addError($this->t('Please select whether to search by Title and/or Author.'));
      }

      $search_rows = [];
      foreach ($query->execute() as $search_data) {
        $run_url = Url::fromRoute('textbook_companion.run_form', ['book_pref_id' => $search_data->id]);
        $search_rows[] = [
          Link::fromTextAndUrl($search_data->book, $run_url)->toString(),
          $search_data->author,
        ];
      }

      if ($search_rows) {
        $form['search_results'] = [
          '#type' => 'item',
          '#title' => $this->t('Search results for "@term"', ['@term' => $search_term]),
          'table' => [
            '#type' => 'table',
            '#header' => [$this->t('Title of the Book'), $this->t('Author Name')],
            '#rows' => $search_rows,
          ],
        ];
      }
      else {
        $form['search_results'] = [
          '#type' => 'item',
          '#title' => $this->t('Search results for "@term"', ['@term' => $search_term]),
          '#markup' => $this->t('No results found'),
        ];
      }
    }

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('textbook_companion.search_form', [], [
      'query' => [
        'search'    => $form_state->getValue('search'),
        'by_title'  => $form_state->getValue('search_by_title') ? 1 : 0,
        'by_author' => $form_state->getValue('search_by_author') ? 1 : 0,
      ],
    ]);
  }

}
