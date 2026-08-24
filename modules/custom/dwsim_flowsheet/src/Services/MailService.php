<?php

namespace Drupal\dwsim_flowsheet\Services;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a generic mail helper to reduce repetitive email sending logic.
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
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   */
  public function __construct(MailManagerInterface $mail_manager, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager) {
    $this->mailManager = $mail_manager;
    $this->loggerFactory = $logger_factory;
    $this->configFactory = $config_factory;
    $this->languageManager = $language_manager;
  }

  /**
   * Generically sends an email handling common module configurations.
   *
   * @param string $module_name
   *   The name of the module, used for retrieving configs and keys.
   * @param string $mail_key
   *   The specific mail key for hook_mail.
   * @param string $to
   *   The recipient email address.
   * @param array $params
   *   The params array containing body, subject, proposal_id, etc.
   * @param bool $send
   *   Whether to actually send the email (defaults to TRUE).
   *
   * @return bool
   *   TRUE if mail was sent successfully, FALSE otherwise.
   */
  public function sendMail($module_name, $mail_key, $to, array $params = [], $send = TRUE) {
    if (empty($to)) {
      $this->loggerFactory->get($module_name)->warning('Email not sent: Recipient email address is empty for mail key: @key', ['@key' => $mail_key]);
      \Drupal::messenger()->addWarning($this->t('Email could not be sent because the recipient email address is empty.'));
      return FALSE;
    }

    $config = $this->configFactory->get($module_name . '.settings');
    $from = $config->get($module_name . '_from_email') ?: $this->configFactory->get('system.site')->get('mail');
    
    // Setup basic headers
    if (!isset($params[$mail_key]['headers'])) {
      $params[$mail_key]['headers'] = [
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
      ];
    } else {
      $params[$mail_key]['headers']['From'] = $params[$mail_key]['headers']['From'] ?? $from;
    }

    // Safely attach Cc and Bcc if they exist in config and aren't manually overridden
    if (!isset($params[$mail_key]['headers']['Cc'])) {
      $cc = $config->get($module_name . '_cc_emails');
      if (!empty($cc)) {
        $params[$mail_key]['headers']['Cc'] = $cc;
      }
    }

    if (!isset($params[$mail_key]['headers']['Bcc'])) {
      $bcc = $config->get($module_name . '_emails');
      if (!empty($bcc)) {
        $params[$mail_key]['headers']['Bcc'] = $bcc;
      }
    }

    $langcode = $this->languageManager->getCurrentLanguage()->getId();

    $mail_result = $this->mailManager->mail(
      $module_name,
      $mail_key,
      $to,
      $langcode,
      $params,
      $from,
      $send
    );

    if (empty($mail_result['result'])) {
      $this->loggerFactory->get($module_name)->error('Error sending email for key: @key to @to', ['@key' => $mail_key, '@to' => $to]);
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Helper method to send approval emails.
   */
  public function sendApprovalMail($module_name, $proposal_id, $user_id, $to, array $extra_params = []) {
    $mail_key = $module_name . '_proposal_approved';
    $params = $extra_params;
    $params[$mail_key]['proposal_id'] = $proposal_id;
    $params[$mail_key]['user_id'] = $user_id;

    return $this->sendMail($module_name, $mail_key, $to, $params);
  }

  /**
   * Helper method to send rejection emails.
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
   * Helper method for generic notifications.
   */
  public function sendNotification($module_name, $mail_key, $to, $subject, $body, array $extra_params = []) {
    $params = $extra_params;
    $params[$mail_key]['subject'] = $subject;
    $params[$mail_key]['body'] = $body;
    
    return $this->sendMail($module_name, $mail_key, $to, $params);
  }
}
