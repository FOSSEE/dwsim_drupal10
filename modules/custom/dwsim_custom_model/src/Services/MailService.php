<?php

namespace Drupal\custom_model\Services;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Reusable mail helper to reduce repetitive email sending logic.
 */
class MailService {

  use StringTranslationTrait;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a MailService object.
   */
  public function __construct(
    MailManagerInterface $mail_manager,
    LoggerChannelFactoryInterface $logger_factory,
    ConfigFactoryInterface $config_factory,
    LanguageManagerInterface $language_manager
  ) {
    $this->mailManager = $mail_manager;
    $this->loggerFactory = $logger_factory;
    $this->configFactory = $config_factory;
    $this->languageManager = $language_manager;
  }

  /**
   * Sends a module email with standard headers.
   *
   * @param string $module_name
   *   The module name used for hook_mail and config lookup.
   * @param string $mail_key
   *   The mail key defined in hook_mail.
   * @param string $to
   *   Recipient email address.
   * @param array $params
   *   Mail parameters (body, subject, etc).
   * @param bool $send
   *   Whether to actually send (defaults TRUE).
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function sendMail($module_name, $mail_key, $to, array $params = [], $send = TRUE) {
    if (empty($to)) {
      $this->loggerFactory->get($module_name)->warning(
        'Email not sent: recipient address is empty for mail key: @key',
        ['@key' => $mail_key]
      );
      \Drupal::messenger()->addWarning($this->t('Email could not be sent because the recipient email address is empty.'));
      return FALSE;
    }

    $config = $this->configFactory->get($module_name . '.settings');
    $from = $config->get($module_name . '_from_email') ?: $this->configFactory->get('system.site')->get('mail');

    // Set standard mail headers.
    if (!isset($params[$mail_key]['headers'])) {
      $params[$mail_key]['headers'] = [
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
      ];
    }
    else {
      $params[$mail_key]['headers']['From'] = $params[$mail_key]['headers']['From'] ?? $from;
    }

    // Attach Cc if configured and not manually set.
    if (!isset($params[$mail_key]['headers']['Cc'])) {
      $cc = $config->get($module_name . '_cc_emails');
      if (!empty($cc)) {
        $params[$mail_key]['headers']['Cc'] = $cc;
      }
    }

    // Attach Bcc if configured and not manually set.
    if (!isset($params[$mail_key]['headers']['Bcc'])) {
      $bcc = $config->get($module_name . '_emails');
      if (!empty($bcc)) {
        $params[$mail_key]['headers']['Bcc'] = $bcc;
      }
    }

    $langcode = $this->languageManager->getCurrentLanguage()->getId();

    $result = $this->mailManager->mail($module_name, $mail_key, $to, $langcode, $params, $from, $send);

    if (empty($result['result'])) {
      $this->loggerFactory->get($module_name)->error(
        'Error sending email for key: @key to @to',
        ['@key' => $mail_key, '@to' => $to]
      );
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Sends a proposal approval email.
   */
  public function sendApprovalMail($module_name, $proposal_id, $user_id, $to, array $extra_params = []) {
    $mail_key = $module_name . '_proposal_approved';
    $params = $extra_params;
    $params[$mail_key]['proposal_id'] = $proposal_id;
    $params[$mail_key]['user_id'] = $user_id;
    return $this->sendMail($module_name, $mail_key, $to, $params);
  }

  /**
   * Sends a proposal rejection email.
   */
  public function sendRejectionMail($module_name, $proposal_id, $user_id, $to, $reason, array $extra_params = []) {
    $mail_key = $module_name . '_proposal_disapproved';
    $params = $extra_params;
    $params[$mail_key]['proposal_id'] = $proposal_id;
    $params[$mail_key]['user_id'] = $user_id;
    $params[$mail_key]['reason'] = $reason;
    return $this->sendMail($module_name, $mail_key, $to, $params);
  }

  /**
   * Sends a generic notification email.
   */
  public function sendNotification($module_name, $mail_key, $to, $subject, $body, array $extra_params = []) {
    $params = $extra_params;
    $params[$mail_key]['subject'] = $subject;
    $params[$mail_key]['body'] = $body;
    return $this->sendMail($module_name, $mail_key, $to, $params);
  }

}
