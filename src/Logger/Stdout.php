<?php

namespace Drupal\skpr_logs\Logger;

use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * This class allows logging to stdout and stderr.
 */
class Stdout implements LoggerInterface {

  use RfcLoggerTrait;

  const SKPR_LOGS_COMPONENT = 'drupal';

  /**
   * Constructs a Stdout object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory object.
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    protected LogMessageParserInterface $parser,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []): void {
    $output = fopen('php://stderr', 'w');

    $severity = strtoupper(RfcLogLevel::getLevels()[$level]);

    // Populate the message placeholders and then replace them in the message.
    $variables = $this->parser->parseMessagePlaceholders($message, $context);
    $message = empty($variables) ? $message : strtr($message, $variables);

    $event = json_encode([
      // Allows development teams to filter by this log type.
      'skpr_component' => self::SKPR_LOGS_COMPONENT,
      // Allows development teams to trace interactions.
      'request_id'     => $_SERVER['HTTP_X_REQUEST_ID'] ?? '',
      'timestamp'      => $context['timestamp'],
      'severity'       => $severity,
      'message'        => strip_tags($message),
    ]);

    fwrite($output, $event . "\n");
    fclose($output);
  }

}
