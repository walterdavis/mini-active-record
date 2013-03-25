<?php
class CommentsController extends MiniController{
  public function create(){
    if(is_array($this->params['comments'])){
      $object = $this->model->build($this->params['comments']);
      if($object->save()){
        $_SESSION['flash'] = format_flash('Created comment');
        redirect_to(url_for($object->post, 'show'));
      }else{
        $this->model = $object;
        $this->model->flash = format_flash($object->get_errors(), 'error');
      }
    }
    return render('comments/create', $this->model);
  }
}
?>