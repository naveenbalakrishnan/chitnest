<?php
namespace Drupal\loan_emi_details\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Core\Link;

class LoanEmiEditableReportForm extends FormBase {
  public function getFormId() {
    return 'loan_emi_editable_report_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $header = [
      $this->t('EMI No'),
      $this->t('Customer Name'),
      $this->t('Start Date'),
      $this->t('End Date'),
      $this->t('Status'),
      $this->t('Penalty'),
      $this->t('Agent'),
      $this->t('Collected Date'),
      $this->t('Collected Mode'),
      $this->t('Actions'),
    ];

    $form['emi_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#empty' => $this->t('No EMI records found.'),
      '#attributes' => ['class' => ['table', 'table-bordered']],
    ];

    $query = Database::getConnection()->select('loan_emi_details', 'l')
      ->fields('l')
      ->orderBy('emi_number', 'ASC');

    $results = $query->execute()->fetchAll();

    foreach ($results as $row) {
      $key = $row->emi_id ?? $row->emi_number ?? uniqid();
      $now = new \DateTime();
      $end_date = new \DateTime($row->end_date);
      $collected = !empty($row->collected_date);

      if ($collected) {
        $status_text = 'Paid';
        $status_color = '#5cb85c';
      } elseif ($end_date < $now) {
        $status_text = 'Pending';
        $status_color = '#d9534f';
      } else {
        $status_text = 'Upcoming';
        $status_color = '#f0ad4e';
      }

      $status_label = Markup::create("<span style=\"color: $status_color; font-weight: bold;\">$status_text</span>");

      $form['emi_table'][$key]['emi_number'] = ['#markup' => $row->emi_number];
      $form['emi_table'][$key]['customer_name'] = ['#markup' => $row->customer_name];
      $form['emi_table'][$key]['start_date'] = ['#markup' => date('d M Y', strtotime($row->start_date))];
      $form['emi_table'][$key]['end_date'] = ['#markup' => date('d M Y', strtotime($row->end_date))];
      $form['emi_table'][$key]['status'] = ['#markup' => $status_label];
      $form['emi_table'][$key]['penalty'] = ['#markup' => $row->penalty ?? '0'];
      $form['emi_table'][$key]['agent_collected'] = ['#markup' => $row->agent_collected];
      $form['emi_table'][$key]['collected_date'] = ['#markup' => $row->collected_date];
      $form['emi_table'][$key]['collected_mode'] = ['#markup' => $row->collected_mode];
      $form['emi_table'][$key]['edit'] = [
        '#type' => 'link',
        '#title' => $this->t('Edit'),
        '#url' => Url::fromRoute('loan_emi_details.edit_form', ['emi_id' => $row->emi_id]),
      ];
    }

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Not needed as this form is now read-only
  }
}