<?php

namespace Drupal\textbook_companion\Services;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Reusable mail helper to reduce repetitive email-sending logic.
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
   * Constructs a MailService instance.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
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
   * Sends an email using the module's hook_mail and MailManager.
   *
   * Validates recipient, attaches standard headers, and logs failures.
   *
   * @param string $module_name
   *   The module name (matches hook_mail module parameter).
   * @param string $mail_key
   *   The mail key (matches the switch case in hook_mail).
   * @param string $to
   *   The recipient email address.
   * @param array $params
   *   Parameters passed to hook_mail.
   * @param bool $send
   *   Whether to actually send (defaults TRUE).
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function sendMail($module_name, $mail_key, $to, array $params = [], $send = TRUE) {
    if (empty($to)) {
      $this->loggerFactory->get($module_name)->warning(
        'Email not sent: recipient is empty for mail key: @key',
        ['@key' => $mail_key]
      );
      return FALSE;
    }

    $config = $this->configFactory->get($module_name . '.settings');
    $from = $config->get($module_name . '_from_email')
      ?: $this->configFactory->get('system.site')->get('mail');

    // Set standard headers if not already provided.
    if (!isset($params[$mail_key]['headers'])) {
      $params[$mail_key]['headers'] = [
        'From'                      => $from,
        'MIME-Version'              => '1.0',
        'Content-Type'              => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer'                  => 'Drupal',
      ];
    }
    else {
      $params[$mail_key]['headers']['From'] = $params[$mail_key]['headers']['From'] ?? $from;
    }

    // Attach Cc from config if not already overridden.
    if (!isset($params[$mail_key]['headers']['Cc'])) {
      $cc = $config->get($module_name . '_cc_emails');
      if (!empty($cc)) {
        $params[$mail_key]['headers']['Cc'] = $cc;
      }
    }

    // Attach Bcc from config if not already overridden.
    if (!isset($params[$mail_key]['headers']['Bcc'])) {
      $bcc = $config->get($module_name . '_emails');
      if (!empty($bcc)) {
        $params[$mail_key]['headers']['Bcc'] = $bcc;
      }
    }

    $langcode = $this->languageManager->getCurrentLanguage()->getId();

    $result = $this->mailManager->mail(
      $module_name,
      $mail_key,
      $to,
      $langcode,
      $params,
      $from,
      $send
    );

    if (empty($result['result'])) {
      $this->loggerFactory->get($module_name)->error(
        'Failed to send email for key @key to @to',
        ['@key' => $mail_key, '@to' => $to]
      );
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Sends a proposal approval notification email.
   *
   * @param string $module_name
   * @param int $proposal_id
   * @param int $user_id
   * @param string $to
   * @param array $extra_params
   *
   * @return bool
   */
  public function sendApprovalMail($module_name, $proposal_id, $user_id, $to, array $extra_params = []) {
    $mail_key = $module_name . '_proposal_approved';
    $params = $extra_params;
    $params[$mail_key]['proposal_id'] = $proposal_id;
    $params[$mail_key]['user_id'] = $user_id;
    return $this->sendMail($module_name, $mail_key, $to, $params);
  }

  /**
   * Sends a proposal rejection notification email.
   *
   * @param string $module_name
   * @param int $proposal_id
   * @param int $user_id
   * @param string $to
   * @param string $reason
   * @param array $extra_params
   *
   * @return bool
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
   * Sends a generic notification email with explicit subject and body.
   *
   * @param string $module_name
   * @param string $mail_key
   * @param string $to
   * @param string $subject
   * @param string $body
   * @param array $extra_params
   *
   * @return bool
   */
  public function sendNotification($module_name, $mail_key, $to, $subject, $body, array $extra_params = []) {
    $params = $extra_params;
    $params[$mail_key]['subject'] = $subject;
    $params[$mail_key]['body'] = $body;
    return $this->sendMail($module_name, $mail_key, $to, $params);
  }

}
