<?php
class Dent extends MiniActiveRecord{
  public $has_many = 'accidents';
  public $has_many_through = 'cars:accidents';
}
?>