<?php

namespace Drupal\view_custom_table\Plugin\views\field;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("mysql_date")
 */
class MysqlDate extends FieldPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The date format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateFormatStorage;

  /**
   * Constructs a new Date object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $date_format_storage
   *   The date format storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DateFormatterInterface $date_formatter, EntityStorageInterface $date_format_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->dateFormatter = $date_formatter;
    $this->dateFormatStorage = $date_format_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('date.formatter'),
      $container->get('entity_type.manager')->getStorage('date_format')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['date_format'] = ['default' => 'small'];
    $options['custom_date_format'] = ['default' => ''];
    $options['timezone'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $date_formats = [];
    foreach ($this->dateFormatStorage->loadMultiple() as $machine_name => $value) {
      $date_formats[$machine_name] = $this->t('@name format: @date', [
        '@name' => $value->label(),
        '@date' => $this->dateFormatter->format(\Drupal::time()->getRequestTime(), $machine_name),
      ]);
    }

    $form['date_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Date format'),
      '#options' => $date_formats + [
        'custom' => $this->t('Custom'),
        'raw time ago' => $this->t('Time ago'),
        'time ago' => $this->t('Time ago (with "ago" appended)'),
        'raw time hence' => $this->t('Time hence'),
        'time hence' => $this->t('Time hence (with "hence" appended)'),
        'raw time span' => $this->t('Time span (future dates have "-" prepended)'),
        'inverse time span' => $this->t('Time span (past dates have "-" prepended)'),
        'time span' => $this->t('Time span (with "ago/hence" appended)'),
      ],
      '#default_value' => $this->options['date_format'] ?? 'small',
    ];
    $form['custom_date_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom date format'),
      '#description' => $this->t('If "Custom", see <a href="http://us.php.net/manual/en/function.date.php" target="_blank">the PHP docs</a> for date formats. Otherwise, enter the number of different time units to display, which defaults to 2.'),
      '#default_value' => $this->options['custom_date_format'] ?? '',
    ];
    $customDateFormat = [
      'custom',
      'raw time ago',
      'time ago',
      'raw time hence',
      'time hence',
      'raw time span',
      'time span',
      'raw time span',
      'inverse time span',
      'time span',
    ];
    foreach ($customDateFormat as $custom_date_possible) {
      $form['custom_date_format']['#states']['visible'][] = [
        ':input[name="options[date_format]"]' => ['value' => $custom_date_possible],
      ];
    }
    $form['timezone'] = [
      '#type' => 'select',
      '#title' => $this->t('Timezone'),
      '#description' => $this->t('Timezone to be used for date output.'),
      '#options' => ['' => $this->t('- Default site/user timezone -')] + \Drupal\Component\Utility\DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '10.1.0', fn() => \Drupal\Core\Datetime\TimeZoneFormHelper::getOptionsListByRegion(), fn() => system_time_zones(FALSE, TRUE)),
      '#default_value' => $this->options['timezone'],
    ];
    foreach (array_merge(['custom'], array_keys($date_formats)) as $timezone_date_formats) {
      $form['timezone']['#states']['visible'][] = [
        ':input[name="options[date_format]"]' => ['value' => $timezone_date_formats],
      ];
    }

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $date_value = $this->getValue($values);
    if ($date_value != NULL) {
      $value = strtotime($date_value);
      $format = $this->options['date_format'];
      $customDateFormat = [
        'custom',
        'raw time ago',
        'time ago',
        'raw time hence',
        'time hence',
        'raw time span',
        'time span',
        'raw time span',
        'inverse time span',
        'time span',
      ];
      if (in_array($format, $customDateFormat)) {
        $custom_format = $this->options['custom_date_format'];
      }
    }

    if (isset($value) && $value != NULL) {
      $timezone = !empty($this->options['timezone']) ? $this->options['timezone'] : NULL;
      // Will be positive for a datetime in the past (ago), and negative for a
      // datetime in the future (hence).
      $time_diff = \Drupal::time()->getRequestTime() - $value;
      switch ($format) {
        case 'raw time ago':
          return $this->dateFormatter->formatTimeDiffSince($value, ['granularity' => is_numeric($custom_format) ? $custom_format : 2]);

        case 'time ago':
          return $this->t('%time ago', ['%time' => $this->dateFormatter->formatTimeDiffSince($value, ['granularity' => is_numeric($custom_format) ? $custom_format : 2])]);

        case 'raw time hence':
          return $this->dateFormatter->formatTimeDiffUntil($value, ['granularity' => is_numeric($custom_format) ? $custom_format : 2]);

        case 'time hence':
          return $this->t('%time hence', ['%time' => $this->dateFormatter->formatTimeDiffUntil($value, ['granularity' => is_numeric($custom_format) ? $custom_format : 2])]);

        case 'raw time span':
          return ($time_diff < 0 ? '-' : '') . $this->dateFormatter
            ->formatTimeDiffSince($value, [
              'strict' => FALSE,
              'granularity' => is_numeric($custom_format) ? $custom_format : 2,
            ]);

        case 'inverse time span':
          return ($time_diff > 0 ? '-' : '') . $this->dateFormatter
            ->formatTimeDiffSince($value, [
              'strict' => FALSE,
              'granularity' => is_numeric($custom_format) ? $custom_format : 2,
            ]);

        case 'time span':
          $time = $this->dateFormatter
            ->formatTimeDiffSince($value, [
              'strict' => FALSE,
              'granularity' => is_numeric($custom_format) ? $custom_format : 2,
            ]);
          return ($time_diff < 0) ? $this->t('%time hence', ['%time' => $time]) : $this->t('%time ago', ['%time' => $time]);

        case 'custom':
          if ($custom_format === 'r') {
            return $this->dateFormatter->format($value, $format, $custom_format, $timezone, 'en');
          }
          return $this->dateFormatter->format($value, $format, $custom_format, $timezone);

        default:
          return $this->dateFormatter->format($value, $format, '', $timezone);
      }
    }
  }

}
