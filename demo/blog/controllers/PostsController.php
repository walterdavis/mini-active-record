<?php
class PostsController extends MiniController{
  public function create(){
    if(is_array($this->params['posts'])){
      $object = $this->model->build($this->params['posts']);
      if($object->save()){
        $_SESSION['flash'] = format_flash('Created post');
        redirect_to(url_for($object, 'show'));
      }else{
        $this->model = $object;
        $this->model->flash = format_flash($object->get_errors(), 'error');
      }
    }
    return render('posts/create', $this->model);
  }

  public function update(){
    if($object = $this->model->find($this->params['id'])){
      $object->populate($this->params['posts']);
      if($object->save()){
        $_SESSION['flash'] = format_flash('Updated post');
        redirect_to(url_for($object, 'show'));
      }else{
        $object->flash = format_flash($object->get_errors(), 'error');
        return render('posts/edit', $object);
      }
    }
  }

  public function destroy(){
    if($object = $this->model->find($this->params['id'])){
      if($object->destroy()){
        $_SESSION['flash'] = format_flash('Deleted post');
        redirect_to('index.php');
      }else{
        $_SESSION['flash'] = $object->get_errors();
        redirect_to(url_for($object, 'show'));
      }
    }
  }

  public function edit(){
    if($object = $this->model->find($this->params['id'])){
      return render('posts/edit', $object);
    }
  }

  public function show(){
    if($object = $this->model->find($this->params['id'])){
      get_flash($object);
      return render('posts/show', $object);
    }
  }

  public function index(){
    $objects = $this->model->find_all(a('order:created_at DESC'));
    get_flash($this->model);
    return render('posts/index', $this->model, $objects);
  }
}
?>