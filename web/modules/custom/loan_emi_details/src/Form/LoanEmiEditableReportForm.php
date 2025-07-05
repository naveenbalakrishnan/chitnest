<?php

namespace Drupal\loan_emi_details\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;

class LoanEmiEditableReportForm extends FormBase {

  public function getFormId() {
    return 'loan_emi_editable_report_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $connection = Database::getConnection();

    // Fetch dropdown options
    $customer_options = ['' => '- Any -'];
    $customers = $connection->query("SELECT DISTINCT customer_name FROM chitnest_loan_emi_details ORDER BY customer_name")->fetchCol();
    foreach ($customers as $name) {
      $customer_options[$name] = $name;
    }

    $agent_options = ['' => '- Any -'];
    $agents = $connection->query("SELECT DISTINCT agent_collected FROM chitnest_loan_emi_details ORDER BY agent_collected")->fetchCol();
    foreach ($agents as $agent) {
      if (!empty($agent)) {
        $agent_options[$agent] = $agent;
      }
    }

    // --- Filters UI (inline container) ---
    $form['filters'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['container-inline']],
    ];

    $form['filters']['search_loan_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Loan ID'),
      '#size' => 12,
      '#default_value' => $form_state->getValue('search_loan_id') ?? '',
    ];

    $form['filters']['search_customer'] = [
      '#type' => 'select',
      '#title' => $this->t('Customer'),
      '#options' => $customer_options,
      '#default_value' => $form_state->getValue('search_customer') ?? '',
    ];

    $form['filters']['search_agent'] = [
      '#type' => 'select',
      '#title' => $this->t('Agent'),
      '#options' => $agent_options,
      '#default_value' => $form_state->getValue('search_agent') ?? '',
    ];

    $form['apply'] = [
	  '#type' => 'submit',
	  '#value' => $this->t('Search'),
	  '#submit' => ['::searchSubmit'],
	];

	$form['clear'] = [
	  '#type' => 'submit',
	  '#value' => $this->t('Clear All'),
	  '#submit' => ['::clearFilters'],
	];

    // --- Table header ---
    $header = [
      $this->t('Loan ID'),
      $this->t('EMI No'),
      $this->t('Customer Name'),
      $this->t('Loan Start Date'),
      $this->t('EMI Date'),
      $this->t('Status'),
      $this->t('Penalty'),
      $this->t('Agent'),
      $this->t('Collected Date'),
      $this->t('Collected Mode'),
      $this->t('Edit'),
    ];

    $form['emi_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#empty' => $this->t('No EMI records found.'),
      '#attributes' => ['class' => ['table', 'table-bordered']],
    ];

    // Fetch filtered data with pager
    $query = $connection->select('loan_emi_details', 'l')->fields('l');

    $search_loan_id = trim($form_state->getValue('search_loan_id'));
    $search_customer = trim($form_state->getValue('search_customer'));
    $search_agent = trim($form_state->getValue('search_agent'));

    if (!empty($search_loan_id)) {
      $query->condition('l.loan_id', '%' . $search_loan_id . '%', 'LIKE');
    }
    if (!empty($search_customer)) {
      $query->condition('l.customer_name', $search_customer);
    }
    if (!empty($search_agent)) {
      $query->condition('l.agent_collected', $search_agent);
    }

    // Add pagination
    $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $results = $query->execute()->fetchAll();

    foreach ($results as $row) {
      if (empty($row->emi_number) || !is_numeric($row->emi_number)) {
        continue;
      }

      $key = $row->emi_number;

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

      $form['emi_table'][$key]['loan_id'] = ['#markup' => $row->loan_id ?? '-'];
      $form['emi_table'][$key]['emi_number'] = ['#markup' => $row->emi_number];
      $form['emi_table'][$key]['customer_name'] = ['#markup' => $row->customer_name];
      $form['emi_table'][$key]['start_date'] = ['#markup' => date('d M Y', strtotime($row->start_date))];
      $form['emi_table'][$key]['end_date'] = ['#markup' => date('d M Y', strtotime($row->end_date))];
      $form['emi_table'][$key]['status'] = ['#markup' => $status_label];
      $form['emi_table'][$key]['penalty'] = ['#markup' => $row->penalty ?? '0'];
      $form['emi_table'][$key]['agent_collected'] = ['#markup' => $row->agent_collected];

      $form['emi_table'][$key]['collected_date'] = [
        '#type' => 'date',
        '#default_value' => $row->collected_date ? date('Y-m-d', strtotime($row->collected_date)) : '',
      ];

      $form['emi_table'][$key]['collected_mode'] = [
        '#type' => 'textfield',
        '#default_value' => $row->collected_mode ?? '',
        '#size' => 10,
      ];

		$url = Url::fromRoute('loan_emi_details.edit_form', [
		  'emi_number' => $row->emi_number,
		  'loan_id' => $row->loan_id,
		]);
		$form['emi_table'][$key]['edit'] = [
        '#type' => 'link',
        '#title' => $this->t('Edit'),
        '#url' => $url,
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Changes'),
      '#attributes' => ['class' => ['button', 'button--primary']],
    ];

    // Add pager display
    $form['pager'] = [
      '#type' => 'pager',
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('emi_table');
    $connection = Database::getConnection();

    foreach ($values as $emi_number => $data) {
      if (!is_numeric($emi_number)) {
        continue;
      }

      $connection->update('loan_emi_details')
        ->fields([
          'collected_date' => $data['collected_date'] ?: NULL,
          'collected_mode' => $data['collected_mode'] ?: '',
          'penalty' => isset($data['penalty']) ? $data['penalty'] : 0,
        ])
        ->condition('emi_number', $emi_number)
        ->execute();
    }

    $this->messenger()->addStatus($this->t('Loan EMI updates have been saved successfully.'));
  }

  public function searchSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }
  
  public function clearFilters(array &$form, FormStateInterface $form_state) {
  $form_state->setValue('search_loan_id', '');
  $form_state->setValue('search_customer', '');
  $form_state->setValue('search_agent', '');
  $form_state->setRebuild();
}
}
