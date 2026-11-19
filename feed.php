<?php
/* feed.php - the latest reviews as an RSS 2.0 feed (Lab 4), built from the
   database every time it's requested. Same XML you wrote by hand in Lab 4,
   but PHP writes it.

   XML has its own escaping rules: ENT_XML1 makes & < > " ' safe in XML. */

require 'includes/init.inc.php';

function x($text) {
   return htmlspecialchars((string) $text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

// The address of this site, so every <link> in the feed is a full URL.
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base = $scheme . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';

$items = [];
if ($db) {
   try {
      $result = $db->query('SELECT r.review_id, r.rating, r.comment, b.book_id, b.title, m.first_name
                            FROM reviews AS r
                            JOIN books AS b ON b.book_id = r.book_id
                            JOIN members AS m ON m.member_id = r.member_id
                            ORDER BY r.review_id DESC
                            LIMIT 10');
      $items = $result->fetch_all(MYSQLI_ASSOC);
   } catch (mysqli_sql_exception $e) {
      $items = [];
   }
}

header('Content-Type: application/rss+xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0">
   <channel>
      <title>Jill's Book Club: latest reviews</title>
      <link><?= x($base) ?></link>
      <description>What the club thought of what we read.</description>
<?php foreach ($items as $item) { ?>
      <item>
         <title><?= x($item['first_name'] . ' gave ' . $item['title'] . ' ' . $item['rating'] . ' out of 5') ?></title>
         <link><?= x($base . 'reviews.php?book=' . $item['book_id']) ?></link>
         <guid isPermaLink="false">jill-review-<?= (int) $item['review_id'] ?></guid>
         <description><?= x($item['comment'] ?? 'No comment.') ?></description>
      </item>
<?php } ?>
   </channel>
</rss>
