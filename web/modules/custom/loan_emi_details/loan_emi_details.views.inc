<?php

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\query\Sql;

/**
 * Implements hook_views_data().
 */
function loan_emi_details_views_data()
{
  $data = [];

  // Replace with your actual table name
  $data['chitnest_loan_emi_details'] = [];

  $data['chitnest_loan_emi_details']['table']['group'] = t('Loan EMI');

  // Primary field
  $data['chitnest_loan_emi_details']['id'] = [
    'title' => t('ID'),
    'help' => t('The ID of the EMI record.'),
    'field' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
  ];

  $data['chitnest_loan_emi_details']['customer_name'] = [
    'title' => t('Customer Name'),
    'help' => t('Name of the customer.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['chitnest_loan_emi_details']['amount'] = [
    'title' => t('Amount'),
    'help' => t('Loan amount.'),
    'field' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  return $data;
}
