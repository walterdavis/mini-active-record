<?php
class Comment extends MiniActiveRecord{
  public $belongs_to = 'post';
  public $validations = 'presence:name; email:email; presence:message:Didn\'t you have anything to say?';
}
?>