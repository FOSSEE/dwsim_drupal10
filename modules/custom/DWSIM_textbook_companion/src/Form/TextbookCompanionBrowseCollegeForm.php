<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\TextbookCompanionBrowseCollegeForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TextbookCompanionBrowseCollegeForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'textbook_companion_browse_college_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $usage_default_value = $form_state->getValue('college') ?? '0';

    $form['college_info'] = [
      '#type' => 'fieldset',
      '#prefix' => '<div id="college-info-wrapper">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];

    // Build college options from module function if available.
    $college_options = ['0' => $this->t('- Select -')];
    if (function_exists('_list_of_colleges')) {
      $college_options = _list_of_colleges();
    }

    $form['college_info']['college'] = [
      '#type' => 'select',
      '#title' => $this->t('College Name'),
      '#options' => $college_options,
      '#default_value' => $usage_default_value,
      '#ajax' => [
        'callback' => '::ajaxCollegeChanged',
        'wrapper' => 'college-info-wrapper',
        'event' => 'change',
      ],
    ];

    if ($usage_default_value != '0') {
      $books = '';
      if (function_exists('_list_books_by_college')) {
        $books = _list_books_by_college($usage_default_value);
      }
      $form['college_info']['book_details'] = [
        '#type' => 'item',
        '#markup' => $books,
      ];
    }

    return $form;
  }

  /**
   * AJAX callback for college select change.
   */
  public function ajaxCollegeChanged(array &$form, FormStateInterface $form_state) {
    return $form['college_info'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
