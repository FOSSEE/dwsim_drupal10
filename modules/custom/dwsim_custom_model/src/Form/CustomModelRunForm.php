<?php

/**
 * @file
 * Contains \Drupal\custom_model\Form\CustomModelRunForm.
 */

namespace Drupal\custom_model\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CustomModelRunForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs the form with injected services.
   */
  public function __construct(Connection $connection, RouteMatchInterface $routeMatch) {
    $this->connection = $connection;
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_model_run_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $url_custom_model_id = (int) $this->routeMatch->getParameter('url_custom_model_id');
    $custom_model_data = $this->_custom_model_information($url_custom_model_id);
    if ($custom_model_data == 'Not found') {
      $url_custom_model_id = 0;
    }

    $selected = $form_state->getValue('custom_model') ?? $url_custom_model_id;

    $form['custom_model'] = [
      '#type' => 'select',
      '#title' => $this->t('Title of the Custom Model'),
      '#options' => $this->_list_of_custom_model(),
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => '::custom_model_project_details_callback',
        'wrapper' => 'custom-model-details-wrapper',
      ],
    ];

    $form['custom_model_details_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'custom-model-details-wrapper'],
    ];

    if ($selected && $selected != 0) {
      $form['custom_model_details_wrapper']['details'] = [
        '#type' => 'markup',
        '#markup' => $this->_custom_model_details($selected),
      ];

      $form['custom_model_details_wrapper']['links'] = [
        '#type' => 'markup',
        '#markup' => '<div id="ajax_selected_custom_model">' .
          Link::fromTextAndUrl(
            $this->t('Download Abstract'),
            Url::fromUri('internal:/custom-model/download/project-file/' . $selected)
          )->toString() .
          '<br>' .
          Link::fromTextAndUrl(
            $this->t('Download Custom Model'),
            Url::fromUri('internal:/custom-model/full-download/project/' . $selected)
          )->toString() .
          '</div>',
      ];
    }

    return $form;
  }

  /**
   * AJAX callback: returns the details wrapper container to be re-rendered.
   */
  public function custom_model_project_details_callback(array &$form, FormStateInterface $form_state) {
    return $form['custom_model_details_wrapper'];
  }

  /**
   * Returns the HTML detail block for a given custom model proposal.
   */
  public function _custom_model_details($custom_model_id) {
    $details = $this->_custom_model_information($custom_model_id);
    if (!$details) {
      return '';
    }

    return '<div><span style="color: #800000;"><strong>About the Custom Model</strong></span><br />' .
      '<ul>' .
      '<li><strong>Proposer Name:</strong> ' . $details->name_title . ' ' . $details->contributor_name . '</li>' .
      '<li><strong>Title of the Custom Model:</strong> ' . $details->project_title . '</li>' .
      '<li><strong>University:</strong> ' . $details->university . '</li>' .
      '</ul></div>';
  }

  /**
   * Returns a single completed proposal record or 'Not found'.
   */
  public function _custom_model_information($proposal_id) {
    if (!$proposal_id) {
      return 'Not found';
    }
    $query = $this->connection->select('custom_model_proposal', 'cmp')
      ->fields('cmp')
      ->condition('id', $proposal_id)
      ->condition('approval_status', 3);
    $result = $query->execute()->fetchObject();
    return $result ?: 'Not found';
  }

  /**
   * Returns all completed custom model proposals as a select list.
   */
  public function _list_of_custom_model() {
    $options = ['0' => $this->t('Please select...')];

    $results = $this->connection->select('custom_model_proposal', 'cmp')
      ->fields('cmp', ['id', 'project_title', 'name_title', 'contributor_name'])
      ->condition('approval_status', 3)
      ->orderBy('project_title', 'ASC')
      ->execute();

    foreach ($results as $row) {
      $options[$row->id] = $row->project_title . ' (' . $this->t('Proposed by @title @name', [
        '@title' => $row->name_title,
        '@name' => $row->contributor_name,
      ]) . ')';
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No submit handling required for this form.
  }

}