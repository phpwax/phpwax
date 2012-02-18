<?php
namespace Wax\Form;

class RecordsetForm extends BoundForm {
  
  public $recordset;
  
  public function __construct($recordset, $post_data, $options=array()) {
    $this->recordset = $recordset;
    foreach($options as $k=>$v) $this->{$k} = $v;
    if(!$this->prefix) $this->prefix = $recordset->model->table; 
    if(!$post_data &&  $_REQUEST[$this->prefix]) $this->post_data = $_REQUEST[$this->prefix];
    $this->bound_to_model = $recordset->model;
    foreach($recordset as $model) {
      foreach($model->columns as $column=>$options) {
        $element = $model->get_col($column);
        $widget_name = $element->widget;
        $widget = new $widget_name($column, $element);
        $widget->prefix = $this->prefix."[".$model->primval."]";
        $widget->name = $column;
        if($element->editable) $this->elements[] = $widget;
      }
    }
  }

  
  public function save() {
    if(!$this->is_posted()) return false;
    $associations = array();
    foreach($this->elements as $el) {
      if(isset($this->post_data[$el->bound_data->primval][$el->name]) && $el->is_association) $associations[$el->name] = $el;
    }
    foreach($associations as $name=>$el) $this->bound_to_model->{$name} = $el->handle_post($this->post_data[$el->bound_data->primval][$name]);
    foreach($this->elements as $el) {
      if(isset($this->post_data[$el->bound_data->primval])) {
        $res[$el->primval] = $el->bound_data->model->handle_post($this->post_data[$el->primval]);
      }
    }
    $res = new WaxRecordset($this->recordset->model, $res);
    $this->validate();
    if($this->is_valid()) return $res;
    return $this->is_valid();
  }
  
  

  
}