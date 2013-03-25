<?php
$GLOBALS['page_title'] = $GLOBALS['headline'] = 'Edit Post';
?>
<form action="?action=update&amp;controller=posts" method="post" accept-charset="utf-8">
  <?php if(isset($params['flash'])) print $params['flash']; ?>
  <p>
    <input type="hidden" name="posts[id]" value="<?= $params['id'] ?>" />
    <label for="posts_title">Name</label>
    <input type="text" name="posts[title]" value="<?= h($params['title']) ?>" id="posts_title"/>
  </p>
  <p>
    <label for="posts_teaser">Teaser</label>
    <textarea name="posts[teaser]" rows="6" cols="40" id="posts_teaser"><?= h($params['teaser']) ?></textarea>
  </p>
  <p>
    <label for="posts_body">Body</label>
    <textarea name="posts[body]" rows="12" cols="40" id="posts_body"><?= h($params['body']) ?></textarea>
  </p>

  <p><input type="submit" value="Save" name="commit" /></p>
</form>