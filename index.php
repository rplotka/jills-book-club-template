<?php
/* index.php - the home page.
   Server side (PHP): the five latest reviews, from a JOIN of all three tables.
   Browser side (JavaScript): "Top rated" is fetched from api/books.php as JSON
   and built with template literals, the Lab 8 pattern (see resources/jill.js). */

require 'includes/init.inc.php';

$pageTitle = 'Welcome';
$latest = null;
$counts = null;

if ($db) {
   try {
      // Fixed SQL, no user input: a plain query() is fine.
      $result = $db->query('SELECT r.rating, r.comment, b.book_id, b.title, m.first_name
                            FROM reviews AS r
                            JOIN books AS b ON b.book_id = r.book_id
                            JOIN members AS m ON m.member_id = r.member_id
                            ORDER BY r.review_id DESC
                            LIMIT 5');
      $latest = $result->fetch_all(MYSQLI_ASSOC);

      // Three counts in one row: a subquery per table.
      $result = $db->query('SELECT (SELECT COUNT(*) FROM books) AS books,
                                   (SELECT COUNT(*) FROM members) AS members,
                                   (SELECT COUNT(*) FROM reviews) AS reviews');
      $counts = $result->fetch_assoc();
   } catch (mysqli_sql_exception $e) {
      $dbError = 'Database error: ' . $e->getMessage();
   }
}

require 'includes/head.inc.php';
?>
      <section aria-labelledby="about-heading" class="intro">
         <h2 id="about-heading">What we're about</h2>
         <p>We read one book a month and argue about it on Jill's porch.
<?php if ($counts) { ?>
            So far: <strong><?= h($counts['members']) ?></strong> members,
            <strong><?= h($counts['books']) ?></strong> books and
            <strong><?= h($counts['reviews']) ?></strong> reviews.
<?php } ?>
         </p>
         <p class="next-meeting"><strong>Next meeting:</strong> Thursday at 7, Jill's porch (bring a sweater).</p>
      </section>

      <div class="columns">
         <section aria-labelledby="latest-heading">
            <h2 id="latest-heading">Latest reviews</h2>
<?php if ($latest === null) { ?>
            <p>Reviews can't be shown until the database problem above is fixed.</p>
<?php } elseif (count($latest) === 0) { ?>
            <p>No reviews yet. <a href="reviews.php">Write the first one</a>.</p>
<?php } else { ?>
            <ul class="review-list">
<?php foreach ($latest as $review) { ?>
               <li>
                  <?= stars($review['rating']) ?>
                  <a href="reviews.php?book=<?= h($review['book_id']) ?>"><?= h($review['title']) ?></a>
                  <span class="by">by <?= h($review['first_name']) ?></span>
<?php if ($review['comment'] !== null && $review['comment'] !== '') { ?>
                  <q><?= h($review['comment']) ?></q>
<?php } ?>
               </li>
<?php } ?>
            </ul>
            <p><a href="reviews.php">All reviews, or write one</a></p>
<?php } ?>
         </section>

         <section aria-labelledby="top-heading">
            <h2 id="top-heading">Top rated</h2>
            <!-- resources/jill.js fills this list with fetch() from api/books.php -->
            <ol id="topRated" class="top-list">
               <li class="loading">Loading&hellip;</li>
            </ol>
            <p class="hint">Loaded in the browser with <code>fetch()</code> from <a href="api/books.php">api/books.php</a>.</p>
         </section>
      </div>
<?php require 'includes/foot.inc.php'; ?>
