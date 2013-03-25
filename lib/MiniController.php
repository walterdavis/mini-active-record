<?php
class MiniController{
  public $params = array();
  public $model;
  public $table_name;
  function __construct(){
    $this->params = $_REQUEST;
    $model_name = Inflector::classify(str_replace('Controller', '', get_class($this)));
    $this->model = new $model_name();
  }
}
?>