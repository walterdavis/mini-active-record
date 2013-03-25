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
require_once('../../lib/MiniController.php');
require_once('controllers/PostsController.php');
require_once('controllers/CommentsController.php');

// path to the views
define('VIEW_ROOT', dirname(__FILE__) . '/views/');

/**
 * get and reset the session flash
 *
 * @param object $object to assign the flash to
 * @return void
 * @author Walter Lee Davis
 */
function get_flash($object){
  if(isset($_SESSION['flash'])){
    $object->flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
  }
}

/**
 * make an unordered list out of an array of errors
 *
 * @param array $errors
 * @param string $classname (optional)
 * @author Walter Lee Davis
 */
function format_flash($errors, $classname = null){
  if(!empty($classname)) $classname = ' ' . $classname;
  $error_string = '';
  if(false !== $errors)
    $error_string = "<ul class=\"flash$classname\">\n\t<li>" . implode("</li>\n\t<li>", (array)$errors) . "</li>\n</ul>\n";
  return $error_string;
}

/**
 * helper for redirects
 *
 * @param string $url 
 * @return void
 * @author Walter Lee Davis
 */
function redirect_to($url){
  header('Location: ' . $url);
  exit;
}

/**
 * build a querystring URL for this simple router
 *
 * @param object $object to link to
 * @param string $action method to call
 * @return string URL
 * @author Walter Lee Davis
 */
function url_for($object, $action){
  $url = 'index.php?action=' . $action . '&controller=' . $object->_table;
  if($object->id > 0) $url .= '&id=' . $object->id;
  return $url;
}

/**
 * render a template with an object
 *
 * @param string $view_path path to the template
 * @param object $object 
 * @param array $collection (optional)
 * @return string rendered HTML
 * @author Walter Lee Davis
 */
function render($view_path, $object, $collection = null){
  ob_start();
  $params = get_object_vars($object);
  include(VIEW_ROOT . $view_path . '.php');
  return ob_get_clean();
}

/**
 * this is a rock-simple router/view combination
 *
 * @return string HTML for the template
 * @author Walter Lee Davis
 */
function front_controller(){
  if(isset($_GET['action']) && in_array($_GET['action'], w('new create update show edit destroy index'))){
    $action = ($_GET['action'] == 'new') ? 'create' : $_GET['action'];
    $controller_name = Inflector::pluralize(Inflector::classify($_GET['controller'])) . 'Controller';
    $controller = new $controller_name();
    $out = $controller->$action();
  }else{
    // show the blog index
    $controller = new PostsController();
    $out = $controller->index();
  }
  return $out;
}
// these global variables get overwritten by the various views
$headline = $page_title = 'Demo Blog';
// build the body of the page
$out = front_controller();
// put it in the main template
include(VIEW_ROOT . 'layout.php');

?>