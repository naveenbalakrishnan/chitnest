<?php

namespace Drupal\loan_emi_details\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

class LoanEmiEditForm extends FormBase {
  protected $requestStack;

  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  public function getFormId() {
    return 'loan_emi_edit_form';
  }

  /**
   * Build form now accepts both emi_number and loan_id as parameters.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $emi_number = NULL, $loan_id = NULL) {
    $connection = Database::getConnection();

    // You can now use both emi_number and loan_id in your query if needed.
    // Here I just fetch by emi_number as primary key.
    $record = $connection->select('loan_emi_details', 'l')
      ->fields('l')
      ->condition('emi_number', $emi_number)
      ->condition('loan_id', $loan_id)
      ->execute()
      ->fetchObject();

	  if (!$record) {
      $this->messenger()->addError($this->t('EMI record not found.'));
      return [];
    }

    // Store emi_number and loan_id as hidden values in the form
    $form['emi_number'] = [
      '#type' => 'value',
      '#value' => $emi_number,
    ];
    $form['loan_id'] = [
      '#type' => 'value',
      '#value' => $loan_id,
    ];

    $form['penalty'] = [
      '#type' => 'number',
      '#title' => $this->t('Penalty'),
      '#default_value' => $record->penalty,
      '#step' => '0.01',
    ];

    $form['collected_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Collected Date'),
      '#default_value' => $record->collected_date ? date('Y-m-d', strtotime($record->collected_date)) : '',
    ];

    $form['collected_mode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Collected Mode'),
      '#default_value' => $record->collected_mode,
      '#size' => 20,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Back to List'),
      '#url' => Url::fromRoute('loan_emi_details.report'),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get emi_number and loan_id from submitted form values
    $emi_number = $form_state->getValue('emi_number');
    $loan_id = $form_state->getValue('loan_id');
    $collected_date = $form_state->getValue('collected_date');
    $collected_mode = $form_state->getValue('collected_mode');
    $penalty = $form_state->getValue('penalty');

    // Update the record with both conditions just in case
    Database::getConnection()->update('loan_emi_details')
      ->fields([
        'collected_date' => $collected_date ?: NULL,
        'collected_mode' => $collected_mode,
        'penalty' => $penalty,
        'status' => 'paid',
      ])
      ->condition('emi_number', $emi_number)
      ->condition('loan_id', $loan_id)
      ->execute();

    $this->messenger()->addStatus($this->t('Loan EMI updated successfully.'));
    $form_state->setRedirect('loan_emi_details.report');
  }
}
