<?php
$GLOBALS['page_title'] = $GLOBALS['headline'] = h($object->title);
?>
<?php if(isset($params['flash'])) print $params['flash']; ?>
<p><?= nl2br(h($object->teaser)) ?></p>
<p><?= nl2br(h($object->body)) ?></p>
<p class="metadata">
  <?= $object->created_at ?>
  (<?= $object->comments_count() ?>
  <?= ($object->comments_count() == 1) ? 'Comment' : 'Comments' ?>)
</p>
<h2>Comments:</h2>
<ul class="messages">
  <?php foreach($object->comments as $comment) { ?>
    <li><span class="commenter"><?= (!empty($comment->url)) ? '<a href="' . $comment->url . '">' . h($comment->name) . '</a>' : h($comment->name) ?></span><?= h($comment->message) ?><span class="metadata"><?= $comment->created_at ?></span></li>  
    <?php } ?>
  </ul>
  <form action="?controller=comments&amp;action=create" method="post" accept-charset="utf-8">
    <fieldset>
      <p>
        <label for="comments_name">Name</label><input type="text" name="comments[name]" value="" id="comments_name"/>
        <input type="hidden" name="comments[post_id]" value="<?= $params['id'] ?>" id="comments_post_id"/>
      </p>
      <p><label for="comments_email">E-mail (will not appear)</label><input type="email" name="comments[email]" value="" id="comments_email"/></p>
      <p><label for="comments_url">URL (optional)</label><input type="text" name="comments[url]" value="" id="comments_url"/></p>
      <p><label for="comments_message">Message</label><textarea name="comments[message]" id="comments_message" rows="6" cols="40"></textarea></p>
      <p><input type="submit" value="Comment" name="commit"/></p>
    </fieldset>
  </form>
  <p>
    <a href="index.php">&larr;Back</a> |
    <a href="<?= url_for($object, 'edit') ?>">Edit</a> |
    <a href="<?= url_for($object, 'destroy') ?>" data-method="delete" data-confirm="Are you sure? There is no undo!">Delete</a>
  </p>
