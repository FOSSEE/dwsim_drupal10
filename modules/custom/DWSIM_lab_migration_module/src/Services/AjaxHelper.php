<?php

namespace Drupal\lab_migration\Services;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\DataCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Render\RendererInterface;

/**
 * Reusable AJAX helper to reduce repetitive callback logic across forms.
 */
class AjaxHelper {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs an AjaxHelper instance.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * Replaces the HTML of a given CSS selector.
   *
   * @param string $selector
   *   The CSS selector to target.
   * @param string|array $content
   *   The replacement content (string or render array).
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function replaceWrapper($selector, $content) {
    $response = new AjaxResponse();
    $rendered = is_array($content) ? $this->renderer->render($content) : $content;
    $response->addCommand(new ReplaceCommand($selector, $rendered));
    return $response;
  }

  /**
   * Sets the inner HTML of a given CSS selector.
   *
   * @param string $selector
   *   The CSS selector to target.
   * @param string|array $content
   *   The content to inject (string or render array).
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function htmlWrapper($selector, $content) {
    $response = new AjaxResponse();
    $rendered = is_array($content) ? $this->renderer->render($content) : $content;
    $response->addCommand(new HtmlCommand($selector, $rendered));
    return $response;
  }

  /**
   * Generates a DataCommand response for setting jQuery data attributes.
   *
   * @param string $selector
   *   The CSS selector.
   * @param string $name
   *   The data attribute name.
   * @param mixed $value
   *   The data value.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function dataCommand($selector, $name, $value) {
    $response = new AjaxResponse();
    $response->addCommand(new DataCommand($selector, $name, $value));
    return $response;
  }

  /**
   * Returns a status/warning/error message command.
   *
   * @param string $message
   *   The message text.
   * @param string $type
   *   Message type: 'status', 'warning', or 'error'. Defaults to 'status'.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function messageCommand($message, $type = 'status') {
    $response = new AjaxResponse();
    $response->addCommand(new MessageCommand($message, NULL, ['type' => $type]));
    return $response;
  }

  /**
   * Builds a response with multiple HTML/Replace/Data commands at once.
   *
   * @param array $commands
   *   Keyed array of commands. Each entry must have:
   *   - 'type': 'html', 'replace', or 'data'
   *   - 'content': the content (for html/replace)
   *   - 'name' and 'value': for data type
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function buildMultiCommandResponse(array $commands) {
    $response = new AjaxResponse();
    foreach ($commands as $selector => $data) {
      $rendered = isset($data['content']) && is_array($data['content'])
        ? $this->renderer->render($data['content'])
        : ($data['content'] ?? '');

      if ($data['type'] === 'html') {
        $response->addCommand(new HtmlCommand($selector, $rendered));
      }
      elseif ($data['type'] === 'replace') {
        $response->addCommand(new ReplaceCommand($selector, $rendered));
      }
      elseif ($data['type'] === 'data') {
        $response->addCommand(new DataCommand($selector, $data['name'], $data['value']));
      }
    }
    return $response;
  }

}
