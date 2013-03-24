<?php
class Post extends MiniActiveRecord{
  public $has_many = 'comments';
  public $validations = 'presence:title; presence:teaser; presence:body';
}
?>