<?php
require 'lib/markdown.php';
$out = file_get_contents('README.markdown');
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>MiniActiveRecord</title>
  <link rel="stylesheet" type="text/css" media="screen" href="demo/css/style.css" />
  <!--[if IE]>
  <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
  <![endif]-->
</head>
<body>
  <a href="https://github.com/walterdavis/mini-active-record">
    <img style="position: absolute; top: 0; right: 0; border: 0;" src="https://s3.amazonaws.com/github/ribbons/forkme_right_red_aa0000.png" alt="Fork me on GitHub">
  </a>
  <div id="PageDiv">
    <section>
      <?= Markdown($out) ?>
    </section>
  </div>
  <footer>
    <p>Copyright 2013 Walter Davis Studio</p>
  </footer>
</body>
</html>