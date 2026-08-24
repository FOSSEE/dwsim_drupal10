<?php

namespace Drupal\dwsim_flowsheet\Services;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\DataCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Render\RendererInterface;

/**
 * Provides a generic AJAX helper to reduce repetitive AJAX callback logic.
 */
class AjaxHelper {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs an AjaxHelper object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * Replaces the HTML of a given selector.
   *
   * @param string $selector
   *   The CSS selector.
   * @param string|array $content
   *   The content to replace with (string or render array).
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function replaceWrapper($selector, $content) {
    $response = new AjaxResponse();
    $rendered_content = is_array($content) ? $this->renderer->render($content) : $content;
    $response->addCommand(new ReplaceCommand($selector, $rendered_content));
    return $response;
  }

  /**
   * Sets the inner HTML of a given selector.
   *
   * @param string $selector
   *   The CSS selector.
   * @param string|array $content
   *   The content to set (string or render array).
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function htmlWrapper($selector, $content) {
    $response = new AjaxResponse();
    $rendered_content = is_array($content) ? $this->renderer->render($content) : $content;
    $response->addCommand(new HtmlCommand($selector, $rendered_content));
    return $response;
  }

  /**
   * Generates a DataCommand response.
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
   * Returns a generic message command.
   *
   * @param string $message
   *   The message to display.
   * @param string $type
   *   The message type (status, warning, error). Defaults to 'status'.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function messageCommand($message, $type = 'status') {
    $response = new AjaxResponse();
    // Using MessageCommand directly inside a specific wrapper or globally
    $response->addCommand(new MessageCommand($message, NULL, ['type' => $type]));
    return $response;
  }

  /**
   * Combine multiple HTML and Replace commands.
   * 
   * @param array $commands
   *   An array where keys are selectors and values are arrays with 'type' ('html' or 'replace') and 'content'.
   * 
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function buildMultiCommandResponse(array $commands) {
    $response = new AjaxResponse();
    foreach ($commands as $selector => $data) {
      $rendered_content = is_array($data['content']) ? $this->renderer->render($data['content']) : $data['content'];
      if ($data['type'] === 'html') {
        $response->addCommand(new HtmlCommand($selector, $rendered_content));
      } elseif ($data['type'] === 'replace') {
        $response->addCommand(new ReplaceCommand($selector, $rendered_content));
      } elseif ($data['type'] === 'data') {
        $response->addCommand(new DataCommand($selector, $data['name'], $data['value']));
      }
    }
    return $response;
  }
}
