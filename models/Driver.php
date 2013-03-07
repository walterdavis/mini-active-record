<?php
class Driver extends MiniActiveRecord {
  public $has_and_belongs_to_many = 'cars';
  public $validations = 'presence:name';
}
?>