<?php
class Post extends MiniActiveRecord{
  public $has_many = 'comments';
  public $validations = 'presence:title; presence:teaser; presence:body';
  public $attr_accessible = 'title body teaser';
  public function after_save(){
    //just an example of how to create a callback, this demo app doesn't use a slug-based router...
    $this->_dirty = true;
    $this->slug = $this->id . '-' . preg_replace('/\s+/', '-', strtolower(preg_replace('/[^a-z0-9]+/i',' ', $this->title)));
    return $this->save_without_callbacks();
  }
  public function comments_count(){
    return count($this->comments);
  }
}
?>