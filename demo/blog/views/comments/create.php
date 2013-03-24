<?php
$GLOBALS['page_title'] = $GLOBALS['headline'] = 'New Comment';
?>
<form action="?action=create&amp;model=comments" method="post" accept-charset="utf-8">
  <?php if(isset($params['flash'])) print $params['flash']; ?>
  <p>
    <label for="comments_name">Name</label>
    <input type="text" name="comments[name]" value="<?= h($params['name']) ?>" id="comments_name"/>
    <input type="hidden" name="comments[post_id]" value="<?= $params['post_id'] ?>" id="comments_post_id"/>
  </p>
  <p>
    <label for="comments_email">E-mail (will not appear)</label>
    <input type="text" name="comments[email]" value="<?= h($params['email']) ?>" id="comments_email"/>
  </p>
  <p>
    <label for="comments_url">Url (optional)</label>
    <input type="text" name="comments[url]" value="<?= h($params['url']) ?>" id="comments_url"/>
  </p>
  <p>
    <label for="comments_url">Message</label>
    <textarea name="comments[message]" rows="6" cols="40" id="comments_message"><?= h($params['message']) ?></textarea>
  </p>

  <p><input type="submit" value="Save" name="commit" /></p>
</form>