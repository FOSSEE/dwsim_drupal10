<?php

/**
 * @file
 * Contains \Drupal\custom_model\Form\CustomModelViewIdeaProposalForm.
 */

namespace Drupal\custom_model\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CustomModelViewIdeaProposalForm extends FormBase {

  protected $connection;
  protected $messenger;
  protected $currentUser;
  protected $routeMatch;

  public function __construct(
    Connection $connection,
    MessengerInterface $messenger,
    AccountInterface $currentUser,
    RouteMatchInterface $routeMatch
  ) {
    $this->connection  = $connection;
    $this->messenger   = $messenger;
    $this->currentUser = $currentUser;
    $this->routeMatch  = $routeMatch;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('messenger'),
      $container->get('current_user'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_model_view_idea_proposal_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $proposal_id = (int) $this->routeMatch->getParameter('id');
    $proposal_data = $this->connection->select('custom_model_idea_proposal')
      ->fields('custom_model_idea_proposal')
      ->condition('id', $proposal_id)
      ->execute()
      ->fetchObject();
    if (!$proposal_data) {
      $this->messenger->addError($this->t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirectUrl(Url::fromUri('internal:/custom-model/manage-proposal/view-ideas/'));
      return [];
    }
    if ($proposal_data->reference_link) {
      $reference_link = $proposal_data->reference_link;
    }
    else {
      $reference_link = 'None';
    }
    if ($proposal_data->reference_file) {
      // $reference_file = l($proposal_data->reference_file, 'custom-model/download/idea-reference-file/' . $proposal_data->id);
      $reference_file = Link::fromTextAndUrl(
        $proposal_data->reference_file,
        Url::fromUri('internal:/custom-model/download/idea-reference-file/' . $proposal_data->id)
      )->toString();

    }
    else {
      $reference_file = 'None';
    }
    // $form['contributor_name'] = [
    //   '#type' => 'item',
    //   '#markup' => l($proposal_data->name_title . ' ' . $proposal_data->idea_proposar_name, 'user/' . $proposal_data->uid),
    //   '#title' => t('Student name'),
    // ];
    $form['contributor_name'] = [
      '#type' => 'item',
      '#markup' => Link::fromTextAndUrl(
        $proposal_data->name_title . ' ' . $proposal_data->idea_proposar_name,
        Url::fromUri('internal:/user/' . $proposal_data->uid)
      )->toString(),
      '#title' => $this->t('Student name'),
    ];
    $form['student_email_id'] = [
      '#title' => t('Student Email'),
      '#type' => 'item',
      // '#markup' => User::load($proposal_data->uid)->getEmail(),
      '#title' => t('Email'),
    ];
    $form['student_email_id'] = [
      '#title' => $this->t('Student Email'),
      '#type' => 'item',
      // '#markup' => User::load($proposal_data->uid)->getEmail(),
    ];
    $form['university'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->university,
      '#title' => t('University/Institute'),
    ];
    $form['country'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->country,
      '#title' => t('Country'),
    ];
    $form['all_state'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->state,
      '#title' => t('State'),
    ];
    $form['city'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->city,
      '#title' => t('City'),
    ];
    $form['pincode'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->pincode,
      '#title' => t('Pincode/Postal code'),
    ];
    $form['project_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->project_title,
      '#title' => t('Title of the Custom Model'),
    ];

    $form['reference_link'] = [
      '#type' => 'item',
      '#markup' => $reference_link,
      '#title' => t('Any Reference Web Link'),
    ];
    $form['reference_file'] = [
      '#type' => 'item',
      '#markup' => $reference_file,
      '#title' => t('Any Reference File'),
    ];
   
    $form['cancel'] = [
      '#type' => 'markup',
      // '#markup' =>Link::fromTextAndUrl(t('Cancel'), 'lab-migration/manage-proposal'),
      '#markup' => Link::fromTextAndUrl(
  $this->t('Cancel'),
  Url::fromUri('internal:/custom-model/manage-proposal/idea-proposals'))->toString(),

    ];
    return $form;
  }
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

}
}

