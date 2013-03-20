<?php
class Car extends MiniActiveRecord {
  public $validations = 'presence:model; regexp:year:/\d{4}/; presence:year';
  public $has_many = 'accidents';
  public $has_many_through = 'dents:accidents';
  public $has_and_belongs_to_many = 'drivers';
  function description(){
    return implode(' ', array($this->year, $this->color, $this->model));
  }
}
?>