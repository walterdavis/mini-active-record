<?php if(isset($params['flash'])) print $params['flash']; ?>
<?php
foreach((array)$collection as $object){
  ?>
  <article>
    <h2><a href="<?= url_for($object, 'show') ?>"><?= h($object->title) ?></a></h2>
    <p><?= nl2br(h($object->teaser)) ?></p>
    <p><a href="<?= url_for($object, 'show') ?>">Read...</a></p>
    <p class="metadata">
    <?= $object->created_at ?>
    (<?= $object->comments_count() ?>
    <?= ($object->comments_count() == 1) ? 'Comment' : 'Comments' ?>)
    </p>
  </article>
  <?php
}
?>
<p><a href="<?= url_for($object, 'new') ?>">New Post...</a></p>