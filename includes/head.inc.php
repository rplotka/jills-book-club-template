<?php
/* head.inc.php - the top of every page. Set $pageTitle before including it.
   $extraScripts (optional): more <script> files this page needs. */
?>
<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <title><?= h($pageTitle) ?> - Jill's Book Club</title>
   <link rel="stylesheet" href="resources/jill.css">
   <!-- the RSS feed (Lab 4): browsers and feed readers find it from this tag -->
   <link rel="alternate" type="application/rss+xml" title="Jill's Book Club: latest reviews" href="feed.php">
   <script src="resources/jill.js" defer></script>
<?php foreach ($extraScripts ?? [] as $script) { ?>
   <script src="<?= h($script) ?>" defer></script>
<?php } ?>
</head>

<body>
   <header class="site-header">
      <p class="site-title"><a href="index.php">Jill's Book Club</a></p>
      <nav aria-label="Site">
         <?= buildMenu() ?>

      </nav>
   </header>

   <main id="content">
      <h1><?= h($pageTitle) ?></h1>
<?php if ($dbError !== '') { ?>
      <div class="message error" role="alert">
         <p><?= h($dbError) ?></p>
      </div>
<?php } ?>
