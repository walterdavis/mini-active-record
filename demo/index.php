<?php
/**
 * I'm doing this all in one file, not because it's a great way to build an app, 
 * but for clarity and conciseness. 
 *
 */
require_once('../config.inc.php');
/**
 * SQL to create example table
 *
 * CREATE TABLE `messages` (
 *   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 *   `name` varchar(255) NOT NULL,
 *   `body` text NOT NULL,
 *   `created_at` datetime NOT NULL,
 *   PRIMARY KEY (`id`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 */
class Message extends MiniActiveRecord{
  // two different ways to call validate_presence: with and without a custom error message
  public $validations = 'presence:name; presence:body:Didn\'t you have anything to say?';
}

// declare some variables for later
$errors = $flash_class = $key = '';

// create a new blank instance of Message to use everywhere
$message = new Message();

// if we are editing, then switch $message to that instance (if it exists)
if(isset($_REQUEST['id'])){
  if($edit = $message->find($_REQUEST['id'])){
    $message = $edit;
    $key = '<input type="hidden" name="id" value="' . $message->id . '" />';
  }
}

// handle a post
if(isset($_POST['commit'])){
  $message->populate($_POST['message']);
  // save, which calls validate()
  $message->save();
  $errors = $message->get_errors();
  // errors will either be false or an array
  if($errors){
    // pretty-print the errors and fall through
    $errors = '<ul><li>' . implode('</li><li>', (array)$errors) . '</li></ul>';
    $flash_class = ' error';
  }else{
    // redirect
    header('Location: index.php');
    exit;
  }
}
// load all existing messages
$messages = $message->find_all(a('order: created_at DESC'));
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>MiniActiveRecord Example</title>
  <link rel="stylesheet" type="text/css" media="screen" href="css/style.css" />
</head>
<body>
  <div id="PageDiv">
    <h1 id="miniactiverecord_example">MiniActiveRecord Example</h1>
    <p>This is a simple example of a form handler made with MiniActiveRecord.</p>
    <ul class="messages">
      <?php
      foreach($messages as $c){
        // create a LI for each message
        print '<li><span class="name">' . h($c->name) . '</span>' . h($c->body) . '<span class="metadata"><span class="date">(' . $c->created_at . ')</span> <a href="?id=' . $c->id . '" class="edit_link">edit</a></span></li>';
      }
      ?>
    </ul>
    <div class="flash<?= $flash_class ?>">
      <?= $errors ?>
    </div>
    <form action="index.php" method="post" accept-charset="utf-8">
      <p><?= $key ?><label for="message_name">Name</label><input type="text" name="message[name]" value="<?= h($message->name) ?>" id="message_name"/></p>
      <p><label for="message_body">Add a Message</label><textarea id="message_body" name="message[body]" rows="8" cols="40"><?= h($message->body) ?></textarea></p>
      <p><input type="submit" name="commit" value="Say it!"/></p>
    </form>
  </div>
</body>
</html>