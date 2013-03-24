<?php
/**
 * This is a simple blog, with posts and comments. It's required by Internet law
 * that all demo applications either be a blog or a to-do list. We obey.
 *
 * @author Walter Lee Davis
 */
 
 /*
 
 CREATE TABLE `comments` (
   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
   `post_id` int(11) NOT NULL,
   `name` varchar(255) NOT NULL DEFAULT '',
   `email` varchar(255) NOT NULL DEFAULT '',
   `url` varchar(255) DEFAULT NULL,
   `message` text NOT NULL,
   `created_at` datetime NOT NULL,
   PRIMARY KEY (`id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 
 CREATE TABLE `posts` (
   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
   `title` varchar(255) NOT NULL DEFAULT '',
   `slug` varchar(255) NOT NULL DEFAULT '',
   `teaser` text NOT NULL,
   `body` text NOT NULL,
   `created_at` datetime NOT NULL,
   `updated_at` datetime NOT NULL,
   PRIMARY KEY (`id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 
 */
 
session_start();
require_once('../../config.inc.php');
require_once('models/post.php');
require_once('models/comment.php');
define('VIEW_ROOT', dirname(__FILE__) . '/views/');
function format_flash($errors){
  // pretty-print the errors and fall through
  $error_string = '';
  if(false !== $errors)
    $error_string = "<ul>\n\t<li>" . implode("</li>\n\t<li>", (array)$errors) . "</li>\n</ul>\n";
  return $error_string;
}

function redirect_to($url){
  header('Location: ' . $url);
  exit;
}

function url_for($model, $action){
  $url = 'index.php?action=' . $action . '&model=' . $model->_table;
  if($model->id > 0) $url .= '&id=' . $model->id;
  return $url;
}

function render($view_path, $object, $collection = null){
  ob_start();
  $params = get_object_vars($object);
  include(VIEW_ROOT . $view_path . '.php');
  return ob_get_clean();
}

function get_flash($model){
  if(isset($_SESSION['flash'])){
    $model->flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
  }
}

function front_controller(){
  if(isset($_GET['action']) && (function_exists($_GET['action']) || $_GET['action'] == 'new') && isset($_GET['model'])){
    $model = Inflector::classify($_GET['model']);
    if(isset($_GET['action']) && $_GET['action'] == 'update' && isset($_POST[$_GET['model']]['id'])){
      $out = update(new $model(), $_POST[$_GET['model']]);
    }
    if(isset($_GET['action']) && $_GET['action'] == 'create'){
      $out = create(new $model(), $_POST[$_GET['model']]);
    }
    if(isset($_GET['action']) && $_GET['action'] == 'new'){
      $out = create(new $model());
    }
    if(isset($_GET['action']) && $_GET['action'] == 'edit'){
      $out = edit(new $model(), $_GET);
    }
    if(isset($_GET['action']) && $_GET['action'] == 'update'){
      $out = update(new $model(), $_POST);
    }
    if(isset($_GET['action']) && $_GET['action'] == 'show'){
      $out = show(new $model(), $_GET);
    }
    if(isset($_POST['action']) && $_POST['action'] == 'destroy'){
      destroy(new $model(), $_POST);
    }
  }else{
    // show the blog index
    $out = index(new Post());
  }
  return $out;
}

function create($model, $params = null){
  if(is_array($params)){
    $object = $model->build($params);
    if($object->save()){
      $_SESSION['flash'] = format_flash('Created ' . Inflector::singularize($model->_table));
      //a tiny hack for comments' sake
      if(is_object($object->post)){
        redirect_to(url_for($object->post, 'show'));
      }
      redirect_to(url_for($object, 'show'));
    }else{
      $model = $object;
      $model->flash = format_flash($object->get_errors());
    }
  }
  return render($model->_table . '/create', $model);
}

function update($model, $params=array()){
  if($object = $model->find($params['id'])){
    $object->populate($params);
    if($object->save()){
      $_SESSION['flash'] = format_flash('Updated ' . Inflector::singularize($model->_table));
      redirect_to(url_for($object, 'show'));
    }else{
      $object->flash = format_flash($object->get_errors());
      return render($model->_table . '/edit', $object);
    }
  }
}

function destroy($model, $params=array()){
  if($object = $model->find($params['id'])){
    if($object->destroy()){
      $_SESSION['flash'] = format_flash('Deleted ' . Inflector::singularize($model->_table));
      redirect_to('index.php');
    }else{
      $_SESSION['flash'] = $object->get_errors();
      redirect_to(url_for($object, 'show'));
    }
  }
}

function edit($model, $params=array()){
  if($object = $model->find($params['id'])){
    return render($model->_table . '/edit', $object);
  }
}

function show($model, $params=array()){
  if($object = $model->find($params['id'])){
    get_flash($object);
    return render($model->_table . '/show', $object);
  }
}

function index($model){
  $objects = $model->find_all(a('order:created_at DESC'));
  get_flash($model);
  return render($model->_table . '/index', $model, $objects);
}
$headline = $page_title = 'Demo Blog';
$out = front_controller();

include(VIEW_ROOT . 'layout.php');

?>