<?php

namespace Drupal\custom_model\Services;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\DataCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Render\RendererInterface;

/**
 * Reusable AJAX helper to reduce repetitive AJAX callback logic.
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
   * Replaces the HTML of a given CSS selector.
   *
   * @param string $selector
   * @param string|array $content
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
   * @param string|array $content
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
   * Returns a DataCommand response.
   *
   * @param string $selector
   * @param string $name
   * @param mixed $value
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function dataCommand($selector, $name, $value) {
    $response = new AjaxResponse();
    $response->addCommand(new DataCommand($selector, $name, $value));
    return $response;
  }

  /**
   * Returns a message command.
   *
   * @param string $message
   * @param string $type  status|warning|error
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function messageCommand($message, $type = 'status') {
    $response = new AjaxResponse();
    $response->addCommand(new MessageCommand($message, NULL, ['type' => $type]));
    return $response;
  }

  /**
   * Builds a response with multiple HTML or Replace commands in one call.
   *
   * @param array $commands
   *   Array keyed by CSS selector with values:
   *   - 'type' => 'html'|'replace'|'data'
   *   - 'content' => string|render array
   *   - 'name' => string (only for 'data' type)
   *   - 'value' => mixed (only for 'data' type)
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function buildMultiCommandResponse(array $commands) {
    $response = new AjaxResponse();
    foreach ($commands as $selector => $data) {
      $rendered = is_array($data['content']) ? $this->renderer->render($data['content']) : $data['content'];
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
