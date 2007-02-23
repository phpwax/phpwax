<?php
/*
 * @package PHP-WAX
 *
 *  Wrappers for Scriptaculous Javacript Library  
 */
 
class ScriptaculousHelper extends PrototypeHelper {
  
  # Returns a JavaScript snippet to be used on the AJAX callbacks for starting
  # visual effects.
  #
  # This method requires the inclusion of the script.aculo.us JavaScript library.
  #
  # Example:
  #   link_to_remote("Reload", array("update" => "posts", 
  #         "url" => array(":action" => "reload"), 
  #         "complete" => visual_effect("highlight", "posts", array("duration" => 0.5)))
  #
  # If no element_id is given, it assumes "element" which should be a local
  # variable in the generated JavaScript execution context. This can be used
  # for example with drop_receiving_element:
  #
  #   drop_receving_element (...), "loading" => visual_effect("fade")
  #
  # This would fade the element that was dropped on the drop receiving element.
  #
  # You can change the behaviour with various options, see
  # http://script.aculo.us for more documentation.
  public function visual_effect($name, $element_id = false, $js_options = array()) {
      $element = ($element_id ? "'{$element_id}'" : "element");
      if($js_options['queue']) {
          $js_options['queue'] = "'{$js_options['queue']}'";
      }
      return "new Effect." . camelize($name, true) . "({$element}," . $this->options_for_javascript($js_options) . ")";
  }
  
  # Makes the element with the DOM ID specified by +element_id+ sortable
  # by drag-and-drop and make an AJAX call whenever the sort order has
  # changed. By default, the action called gets the serialized sortable
  # element as parameters.
  #
  # This method requires the inclusion of the script.aculo.us JavaScript library.
  #
  # Example:
  #    sortable_element("my_list", array("url" => array(":action" => "order"))) 
  #
  # In the example, the action gets a "my_list" array parameter 
  # containing the values of the ids of elements the sortable consists 
  # of, in the current order.
  #
  # You can change the behaviour with various options, see
  # http://script.aculo.us for more documentation.
  public function sortable_element($element_id, $options = array()) {
      if(!$options['with']) {
          $options['with'] = "Sortable.serialize('{$element_id}')";
      }
      if(!$options['onUpdate']) {
          $options['onUpdate'] = "function(){" . $this->remote_function($options) . "}";
      }
      $options = $this->remove_ajax_options($options);
      foreach(array('tag', 'overlap', 'constraint', 'handle') as $option) {
          if($options[$option]) {
              $options[$option] = "'{$options[$option]}'";
          }
      }
      
      if($options['containment']) {
          $options['containment'] = $this->array_or_string_for_javascript($options['containment']);
      }
      if($options['only']) {
          $options['only'] = $this->array_or_string_for_javascript($options['only']);
      }
      return $this->javascript_tag("Sortable.create('{$element_id}', " . $this->options_for_javascript($options) . ")");
  }
  
  # Makes the element with the DOM ID specified by $element_id draggable.
  #
  # This method requires the inclusion of the script.aculo.us JavaScript library.
  #
  # Example:
  #    draggable_element("my_image", array("revert" => true))
  # 
  # You can change the behaviour with various options, see
  # http://script.aculo.us for more documentation. 
  public function draggable_element($element_id, $options = array()) {
      return $this->javascript_tag("new Draggable('{$element_id}', " . $this->options_for_javascript($options) . ")");
  }
  
  # Makes the element with the DOM ID specified by $element_id receive
  # dropped draggable elements (created by draggable_element).
  # and make an AJAX call  By default, the action called gets the DOM ID of the
  # element as parameter.
  #
  # This method requires the inclusion of the script.aculo.us JavaScript library.
  #
  # Example:
  #    drop_receiving_element("my_cart", array("url" => array(":controller" => "cart", ":action" => "add"))) 
  #
  # You can change the behaviour with various options, see
  # http://script.aculo.us for more documentation.
  public function drop_receiving_element($element_id, $options = array()) {
      if(!$options['with']) {
          $options['with'] = "'id=' + encodeURIComponent(element.id)";
      }
      if(!$options['onDrop']) {
          $options['onDrop'] = "function(element){" . $this->remote_function($options) . "}";
      }
      $options = $this->remove_ajax_options($options);
      if($options['accept']) {
          $options['accept'] = $this->array_or_string_for_javascript($options['accept']);  
      }  
      if($options['hoverclass']) {
          $options['hoverclass'] = "'{$options['hoverclass']}'";
      }
      return $this->javascript_tag("Droppables.add('{$element_id}', " . $this->options_for_javascript($options) . ")");
  }
  
  /**
   * wrapper for script.aculo.us/prototype Ajax.Autocompleter.
   * @param string name value of input field
   * @param string default value for input field
   * @param array input tag options. (size, autocomplete, etc...)
   * @param array completion options. (use_style, etc...)
   *
   * @return string input field tag, div for completion results, and
   *                 auto complete javascript tags
   */
  public function auto_complete_field($name, $url, $value="", $tag_options = array(), $completion_options = array()) {
    $javascript = text_field_tag($name, $value, $tag_options, array(), false);
    $javascript .= $this->content_tag('div', '' , array('id' => "{$name}_auto_complete", 'class' => 'auto_complete'));
    $javascript .= $this->build_auto_complete_field($name, $url, $completion_options);
    return $javascript;
  }
  
  /**
   * wrapper for script.aculo.us/prototype Ajax.Autocompleter.
   * @param string id value of input field
   * @param string url of module/action to execute for autocompletion
   * @param array completion options
   * @return string javascript tag for Ajax.Autocompleter
   */
  protected function build_auto_complete_field($field_id, $url, $options = array()) {
    $javascript = "var {$field_id}_auto_completer = ";
    $javascript .= "new Ajax.Autocompleter(";  
    $javascript .= "'$field_id', ";
    if (isset($options['update'])) {
      $javascript .= "'".$options['update']."', ";
    } else {
      $javascript .= "'{$field_id}_auto_complete', ";
    }
    $javascript .= "'".url_for($url)."'";
    $js_options = array();
    if (isset ($options['with'])) $js_options['callback'] = "function(element, value) { return".$options['with']."}";
    if (isset($options['indicator'])) $js_options['indicator']  = "'".$options['indicator']."'";
    if (isset($options['on_show'])) $js_options['onShow'] = $options['on_show'];
    if (isset($options['on_hide'])) $js_options['onHide'] = $options['on_hide'];
    if (isset($options['min_chars'])) $js_options['minChars'] = $options['min_chars'];
    if (isset($options['frequency'])) $js_options['frequency'] = $options['frequency'];
    if (isset($options['update_element'])) $js_options['updateElement'] = $options['update_element'];
    if (isset($options['after_update_element'])) $js_options['afterUpdateElement'] = $options['after_update_element'];
    $javascript .= ', '.$this->options_for_javascript($js_options).');';
    return $this->javascript_tag($javascript);
  }
  
  /**
   * wrapper for script.aculo.us/prototype Ajax.Autocompleter.
   * @param string name id of field that can be edited
   * @param string url of module/action to be called when ok is clicked
   * @param array editor tag options. (rows, cols, highlightcolor, highlightendcolor, etc...)
   *
   * @return string javascript to manipulate the id field to allow click and edit functionality
   */
  public function in_place_editor($name, $url, $editor_options = array()) {
    $default_options = array('tag' => 'span', 'id' => '\''.$name.'_in_place_editor', 'class' => 'in_place_editor_field');
  
    return $this->build_in_place_editor($name, $url, array_merge($default_options, $editor_options));
  }
  
  /*
   * Makes an HTML element specified by the DOM ID '$field_id' become an in-place
   * editor of a property.
   *
   * A form is automatically created and displayed when the user clicks the element,
   * something like this:
   * <form id="myElement-in-place-edit-form" target="specified url">
   *   <input name="value" text="The content of myElement"/>
   *   <input type="submit" value="ok"/>
   *   <a onclick="javascript to cancel the editing">cancel</a>
   * </form>
   *
   * The form is serialized and sent to the server using an AJAX call, the action on
   * the server should process the value and return the updated value in the body of
   * the reponse. The element will automatically be updated with the changed value
   * (as returned from the server).
   *
   * Required '$options' are:
   * 'url'                 Specifies the url where the updated value should
   *                       be sent after the user presses "ok".
   *
   * Addtional '$options' are:
   * 'rows'                Number of rows (more than 1 will use a TEXTAREA)
   * 'cancel_text'         The text on the cancel link. (default: "cancel")
   * 'save_text'           The text on the save link. (default: "ok")
   * 'external_control'    The id of an external control used to enter edit mode.
   * 'options'             Pass through options to the AJAX call (see prototype's Ajax.Updater)
   * 'with'                JavaScript snippet that should return what is to be sent
   *                       in the AJAX call, 'form' is an implicit parameter
   */
  protected function build_in_place_editor($field_id, $url, $options = array()) {
    $javascript = "new Ajax.InPlaceEditor(";
    $javascript .= "'$field_id', ";
    $javascript .= "'" . url_for($url) . "'";  
    $js_options = array();
    
    if (isset($options['cancel_text'])) $js_options['cancelText'] = "'".$options['cancel_text']."'";
    if (isset($options['save_text'])) $js_options['okText'] = "'".$options['save_text']."'";
    if (isset($options['cols'])) $js_options['cols'] = $options['cols'];
    if (isset($options['rows'])) $js_options['rows'] = $options['rows'];
    if (isset($options['external_control'])) $js_options['externalControl'] = $options['external_control'];
    if (isset($options['options'])) $js_options['ajaxOptions'] = $options['options'];
    if (isset($options['with'])) $js_options['callback'] = "function(form) { return".$options['with']."}";
    if (isset($options['highlightcolor'])) $js_options['highlightcolor'] = "'".$options['highlightcolor']."'";
    if (isset($options['highlightendcolor'])) $js_options['highlightendcolor'] = "'".$options['highlightendcolor']."'";
    if(isset($options['loadTextURL'])) $js_options['loadTextURL'] =  "'".$options['loadTextURL']."'";
    $javascript .= ', '.$this->options_for_javascript($js_options);
    $javascript .= ');';
    return $this->javascript_tag($javascript);
  }
  
}


?>