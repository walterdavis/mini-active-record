<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title><?= $page_title ?></title>
  <link rel="stylesheet" type="text/css" media="screen" href="../css/style.css" />
  <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/prototype/1.7/prototype.js"></script>
  <script src="javascripts/methods.js" type="text/javascript" charset="utf-8"></script>
  <!--[if IE]>
  <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
  <![endif]-->
</head>
<body>
  <div id="PageDiv">
    <header>
      <h1><?= $headline ?></h1>
    </header>
    <section>
      <?= $out ?>
    </section>
  </div>
  <footer>
    <p>Copyright 2013 Walter Davis Studio</p>
  </footer>
</body>
</html>