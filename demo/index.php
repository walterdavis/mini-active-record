<?php
require_once('../config.inc.php');
class Comment extends MiniActiveRecord{
  public $validations = 'presence:name; presence:body:Didn\'t you have anything to say?';
}
$comment = new Comment();
$errors = '';
if(isset($_GET['id'])){
  if($edit = $comment->find($_GET['id'])){
    $comment = $edit;
  }
}
if(isset($_POST['commit'])){
  $comment->populate($_POST['comment']);
  $comment->save();
  $errors = $comment->get_errors();
  if($errors){
    $errors = '<ul><li>' . implode('</li><li>', $errors) . '</li></ul>';
  }else{
    header('Location: index.php');
    exit;
  }
}
$comments = $comment->find_all(a('order: created_at DESC'));
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>MiniActiveRecord Example</title>
  <link rel="stylesheet" type="text/css" media="screen" href="css/style.css" />
  <!--[if IE]>
  <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
  <![endif]-->
</head>
<body>
  <div id="PageDiv">
    <h1 id="miniactiverecord_example">MiniActiveRecord Example</h1>
    <p>This is a simple example of a form handler made with MiniActiveRecord.</p>
    <ul>
      <?php
      foreach($comments as $c){
        print '<li>' . h($c->body) . '<br />' . h($c->name) . ' (' . $c->created_at . ') <a href="?id=' . $c->id . '">edit</a></li>';
      }
      ?>
    </ul>
    <div id="flash">
      <?= $errors ?>
    </div>
    <form action="" method="post" accept-charset="utf-8">
      <p><label for="comment_name">Name</label><input type="text" name="comment[name]" value="<?=$comment->name?>" id="comment_name"/></p>
      <p><label for="comment_body">Add a Comment</label><textarea id="comment_body" name="comment[body]" rows="8" cols="40"><?= htmlspecialchars($comment->body, ENT_COMPAT, 'UTF-8') ?></textarea></p>
      <p><input type="submit" name="commit" value="Say it!"/></p>
    </form>
  </div>
</body>
</html>