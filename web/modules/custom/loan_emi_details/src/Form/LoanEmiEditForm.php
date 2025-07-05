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
   * Build form accepts emi_number and loan_id parameters.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $emi_number = NULL, $loan_id = NULL) {
    $connection = Database::getConnection();

    // Fetch record based on emi_number and loan_id
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

    // Calculate days pending
    $now = new \DateTime();
    $emi_due_date = !empty($record->end_date) ? new \DateTime($record->end_date) : NULL;

    $days_pending = 0;
    if ($emi_due_date && $emi_due_date < $now) {
      $interval = $emi_due_date->diff($now);
      $days_pending = $interval->days;
    }

    // Calculate penalty = 10% of emi_amount * days_pending
    $calculated_penalty = 0;
    if (!empty($record->emi_amount) && $days_pending > 0) {
      $calculated_penalty = $record->emi_amount * 0.10 * $days_pending;
      // Round to 2 decimals
      $calculated_penalty = round($calculated_penalty, 2);
    }

    $form['loan_title'] = [
      '#type' => 'markup',
      '#markup' => '<h2>' . $this->t('Loan ID: @loan and EMI Number: @emi', ['@loan' => htmlspecialchars($loan_id), '@emi' => $emi_number]) . '</h2>',
    ];

    // Hidden fields to retain values on submit
    $form['emi_number'] = [
      '#type' => 'value',
      '#value' => $emi_number,
    ];
    $form['loan_id'] = [
      '#type' => 'value',
      '#value' => $loan_id,
    ];

    // 2x2 table for EMI Date, Days Pending, Loan Amount, EMI Amount
    $form['loan_info_table'] = [
  '#type' => 'markup',
  '#markup' => '
    <table style="
      border: 1px solid #ddd; 
      border-collapse: collapse; 
      width: 60%; 
      margin-bottom: 20px; 
      font-family: Arial, sans-serif;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      background: #f9f9f9;
      ">
      <tr style="background: #e2f0d9;">
        <th style="border: 1px solid #ddd; padding: 10px 15px; text-align:left;">EMI Date</th>
        <td style="border: 1px solid #ddd; padding: 10px 15px;">' . (!empty($record->start_date) ? date('d M Y', strtotime($record->start_date)) : 'N/A') . '</td>
      </tr>
      <tr>
        <th style="border: 1px solid #ddd; padding: 10px 15px; text-align:left;">Days Pending</th>
        <td style="border: 1px solid #ddd; padding: 10px 15px; color: ' . ($days_pending > 0 ? 'red' : 'green') . '; font-weight: bold;">' . ($days_pending > 0 ? $days_pending . ' days' : 'Not pending') . '</td>
      </tr>
      <tr style="background: #e2f0d9;">
        <th style="border: 1px solid #ddd; padding: 10px 15px; text-align:left;">Loan Amount</th>
        <td style="border: 1px solid #ddd; padding: 10px 15px; color: green; font-weight: bold;">' . ($record->loan_amount !== NULL ? number_format($record->loan_amount, 2) : 'N/A') . '</td>
      </tr>
      <tr>
        <th style="border: 1px solid #ddd; padding: 10px 15px; text-align:left;">EMI Amount</th>
        <td style="border: 1px solid #ddd; padding: 10px 15px; color: green; font-weight: bold;">' . ($record->emi_amount !== NULL ? number_format($record->emi_amount, 2) : 'N/A') . '</td>
      </tr>
    </table>
  ',
];



    $form['penalty'] = [
      '#type' => 'number',
      '#title' => $this->t('Penalty'),
      '#default_value' => $calculated_penalty !== NULL ? $calculated_penalty : 0,
      '#step' => '0.01',
      '#description' => $this->t('Calculated as 10% of EMI amount times number of days pending.'),
    ];

    $form['collected_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Collected Date'),
      '#default_value' => $record->collected_date ? date('Y-m-d', strtotime($record->collected_date)) : '',
      '#required' => TRUE,
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
    $emi_number = $form_state->getValue('emi_number');
    $loan_id = $form_state->getValue('loan_id');
    $collected_date = $form_state->getValue('collected_date');
    $collected_mode = $form_state->getValue('collected_mode');
    $penalty = $form_state->getValue('penalty');

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
