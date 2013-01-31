<?php
/**
 * Item
 *
 * An Item in a list
 */
namespace Menu\Items;

use \Underscore\Types\Arrays;
use \Menu\Traits\MenuObject;
use \Menu\Helpers;
use \Menu\Traits\Content;
use \Menu\Html;
use \Menu\Menu;

class Item extends MenuObject
{
  /**
   * The list this item is in
   *
   * @var ItemList
   */
  public $list;

  /**
   * The item element
   * @var string
   */
  protected $element = 'li';

  /**
   * The item's content
   * @var Content
   */
  public $content;

  /**
   * The children this item has
   *
   * @var ItemList
   */
  public $children;

  /**
   * The default render options for this item
   *
   * @var array
   */
  public $options = array(
    'activeClass'      => 'active',
    'activeChildClass' => 'active-child'
  );

  /**
   * Create a new item instance
   *
   * @param ItemList $list     The parent
   * @param Content  $content  The content
   * @param array    $children Facultative children ItemLists
   * @param array    $options  Options
   */
  public function __construct(ItemList $list, Content $content, $children = array(), $options = array())
  {
    $this->list     = $list;
    $this->children = $children;
    $this->options  = array_replace_recursive($this->options, $options);

    // Create content
    $this->content = $content->inItem($this);
  }

  /**
   * Render the item
   *
   * @param array
   *
   * @return string
   */
  public function render($options = array())
  {
    $this->options = array_replace_recursive($this->options, $options);

    // Add the active classes
    $this->addActiveClasses($this->attributes);

    // Increment the render depth
    $this->options['renderDepth'] = $this->getOption('renderDepth', 0) + 1;

    // Render main content
    $content = $this->renderTabbed($this->content, $this->options['renderDepth'] + 1);

    // Render children if any
    if ($this->hasChildren()) {
      $content .=
        PHP_EOL.$this->children.
        str_repeat("\t", $this->options['renderDepth']);
    }

    $content .= PHP_EOL.str_repeat("\t", $this->options['renderDepth']);
    $element = $this->element;
    if ($element) $content = HTML::$element($content, $this->attributes);

    return $this->renderTabbed($content, $this->options['renderDepth']);
  }

  ////////////////////////////////////////////////////////////////////
  ///////////////////////// PUBLIC INTERFACE /////////////////////////
  ////////////////////////////////////////////////////////////////////

  /**
   * Check if this item is active
   *
   * @return boolean
   */
  public function isActive()
  {
    return
      $this->getUrl() == $this->getRequest()->fullUrl() or
      $this->getUrl() == $this->getRequest()->url();
  }

  /**
   * Check if this item has children
   *
   * @return boolean
   */
  public function hasChildren()
  {
    return !is_null($this->children) and !empty($this->children);
  }

  /**
   * Check if this item has an active child
   *
   * @return boolean
   */
  public function hasActiveChild()
  {
    if (!$this->hasChildren()) {
      return false;
    }

    foreach ($this->children->getItems() as $child) {
      if ($child->isActive()) {
        return true;
      }

      if ($child->hasChildren()) {
        return $child->hasActiveChild();
      }
    }

    return false;
  }

  /**
   * Get the url of the Item's content
   *
   * @return string
   */
  protected function getUrl()
  {
    return $this->content->isLink() ? $this->content->getEvaluatedUrl() : null;
  }

  /**
   * Get the List this Item belongs to
   *
   * @return List
   */
  public function getList()
  {
    return $this->list;
  }

  ////////////////////////////////////////////////////////////////////
  ////////////////////////////// HELPERS /////////////////////////////
  ////////////////////////////////////////////////////////////////////

  /**
   * Get an option from the options array
   *
   * @param string $option The option key
   *
   * @return string Its content
   */
  private function getOption($option, $fallback = null)
  {
    return Arrays::get($this->options, $option, $fallback);
  }

  /**
   * Add the various active classes to an array of attributes
   *
   * @param  array $attributes
   * @return array
   */
  private function addActiveClasses($attributes)
  {
    if ($this->isActive()) {
      $attributes = Helpers::addClass($attributes, $this->getOption('activeClass'));
    }

    if ($this->hasActiveChild()) {
      $attributes = Helpers::addClass($attributes, $this->getOption('activeChildClass'));
    }

    return $attributes;
  }

  ////////////////////////////////////////////////////////////////////
  //////////////////////////// DEPENDENCIES //////////////////////////
  ////////////////////////////////////////////////////////////////////

  /**
   * Get the Request instance
   *
   * @return Request
   */
  public function getRequest()
  {
    // Defer to Illuminate/Request if possible
    if (class_exists('App')) {
      return App::make('Request');
    }

    return Menu::getContainer('Symfony\Component\HttpFoundation\Request');
  }

}
