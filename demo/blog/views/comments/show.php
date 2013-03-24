<?php
$GLOBALS['page_title'] = $GLOBALS['headline'] = 'Comment';
?>
<?php if(isset($params['flash'])) print $params['flash']; ?>
<?php if(!empty($params['url'])){ ?>
  <p><a href="<?= $params['url'] ?>"><?= h($params['name']) ?></a></p>
<?php }else{ ?>
  <p><?= h($params['name']) ?></p>
<?php } ?>
<p><?= nl2br(h($params['message'])) ?></p>