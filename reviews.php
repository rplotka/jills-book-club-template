<?php
/* reviews.php - write a review, and read them.
   The form is checked twice: in the browser by resources/jill.js (the Lab 5
   pattern, a convenience) and here in PHP (the real check). The list joins all
   three tables; ?book=3 shows one book's reviews with a prepared statement. */

require 'includes/init.inc.php';

$pageTitle = 'Reviews';
$errors = [];
$memberId = 0;
$bookId = (int) ($_GET['book'] ?? 0);   // which book's reviews to show (0 = all)
$rating = '';
$comment = '';
$members = null;
$books = null;
$reviews = null;
$filterTitle = '';
$message = flashMessage(['added' => 'Thanks! Your review is in.', 'deleted' => 'Review removed.']);

if ($db) {
   try {
      if ($_SERVER['REQUEST_METHOD'] === 'POST') {
         $action = $_POST['action'] ?? '';

         if ($action === 'add') {
            // The dropdowns send ids as text: (int) turns them into numbers.
            $memberId = (int) ($_POST['member_id'] ?? 0);
            $bookId = (int) ($_POST['book_id'] ?? 0);
            $rating = trim($_POST['rating'] ?? '');
            $comment = trim($_POST['comment'] ?? '');

            if ($memberId < 1) {
               $errors['member_id'] = 'Choose who you are.';
            }
            if ($bookId < 1) {
               $errors['book_id'] = 'Choose a book.';
            }
            if (!in_array($rating, ['1', '2', '3', '4', '5'], true)) {
               $errors['rating'] = 'Pick a rating from 1 to 5 stars.';
            }
            if (mb_strlen($comment) > 255) {
               $errors['comment'] = 'Keep the comment to 255 characters.';
            }

            if (count($errors) === 0) {
               $ratingNumber = (int) $rating;
               $commentForDb = ($comment === '') ? null : $comment;
               try {
                  $stmt = $db->prepare('INSERT INTO reviews (member_id, book_id, rating, comment) VALUES (?, ?, ?, ?)');
                  $stmt->bind_param('iiis', $memberId, $bookId, $ratingNumber, $commentForDb);
                  $stmt->execute();
                  redirect('reviews.php?book=' . $bookId . '&msg=added');
               } catch (mysqli_sql_exception $e) {
                  // 1452: the member or book id doesn't exist (someone edited the form)
                  if ($e->getCode() === 1452) {
                     $errors['book_id'] = 'That member or book doesn\'t exist.';
                  } else {
                     throw $e;
                  }
               }
            }
         } elseif ($action === 'delete') {
            $reviewId = (int) ($_POST['review_id'] ?? 0);
            $stmt = $db->prepare('DELETE FROM reviews WHERE review_id = ?');
            $stmt->bind_param('i', $reviewId);
            $stmt->execute();
            redirect('reviews.php?msg=deleted');
         }
      }

      // The dropdowns: fixed SQL, so query() is fine.
      $members = $db->query('SELECT member_id, first_name, last_name FROM members ORDER BY first_name')->fetch_all(MYSQLI_ASSOC);
      $books = $db->query('SELECT book_id, title FROM books ORDER BY title')->fetch_all(MYSQLI_ASSOC);

      // The list. With ?book=N the WHERE uses a ? placeholder: $bookId came from the URL.
      $sql = 'SELECT r.review_id, r.rating, r.comment, b.book_id, b.title, m.first_name, m.last_name
              FROM reviews AS r
              JOIN books AS b ON b.book_id = r.book_id
              JOIN members AS m ON m.member_id = r.member_id';
      if ($bookId > 0) {
         $stmt = $db->prepare($sql . ' WHERE r.book_id = ? ORDER BY r.review_id DESC');
         $stmt->bind_param('i', $bookId);
         $stmt->execute();
         $reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
         foreach ($books as $book) {
            if ((int) $book['book_id'] === $bookId) {
               $filterTitle = $book['title'];
            }
         }
      } else {
         $reviews = $db->query($sql . ' ORDER BY r.review_id DESC')->fetch_all(MYSQLI_ASSOC);
      }
   } catch (mysqli_sql_exception $e) {
      $dbError = 'Database error: ' . $e->getMessage();
   }
}

require 'includes/head.inc.php';
?>
<?php if ($message !== '') { ?>
      <div class="message success" role="status">
         <p><?= h($message) ?></p>
      </div>
<?php } ?>

<?php if ($members !== null && $books !== null) { ?>
      <section aria-labelledby="write-heading">
         <h2 id="write-heading">Write a review</h2>
         <!-- jill.js fills this box when the browser finds a problem before sending -->
         <div id="reviewErrors" class="message error" role="alert"<?= count($errors) > 0 ? '' : ' hidden' ?>>
            <p>Please correct the following:</p>
            <ul>
<?php foreach ($errors as $error) { ?>
               <li><?= h($error) ?></li>
<?php } ?>
            </ul>
         </div>
         <form method="post" action="reviews.php" id="reviewForm" class="stack-form" novalidate>
            <input type="hidden" name="action" value="add">
            <div class="field">
               <label for="member_id">Who are you?</label>
               <select id="member_id" name="member_id"<?= isset($errors['member_id']) ? ' aria-invalid="true"' : '' ?>>
                  <option value="0">Choose&hellip;</option>
<?php foreach ($members as $member) { ?>
                  <option value="<?= h($member['member_id']) ?>"<?= (int) $member['member_id'] === $memberId ? ' selected' : '' ?>><?= h($member['first_name'] . ' ' . $member['last_name']) ?></option>
<?php } ?>
               </select>
            </div>
            <div class="field">
               <label for="book_id">Book</label>
               <select id="book_id" name="book_id"<?= isset($errors['book_id']) ? ' aria-invalid="true"' : '' ?>>
                  <option value="0">Choose&hellip;</option>
<?php foreach ($books as $book) { ?>
                  <option value="<?= h($book['book_id']) ?>"<?= (int) $book['book_id'] === $bookId ? ' selected' : '' ?>><?= h($book['title']) ?></option>
<?php } ?>
               </select>
            </div>
            <fieldset class="field rating"<?= isset($errors['rating']) ? ' aria-invalid="true"' : '' ?>>
               <legend>Rating</legend>
<?php for ($i = 1; $i <= 5; $i++) { ?>
               <label><input type="radio" name="rating" value="<?= $i ?>"<?= $rating === (string) $i ? ' checked' : '' ?>> <?= str_repeat('★', $i) ?><span class="visually-hidden"> <?= $i ?> out of 5</span></label>
<?php } ?>
            </fieldset>
            <div class="field">
               <label for="comment">Comment</label>
               <textarea id="comment" name="comment" rows="2" maxlength="255"<?= isset($errors['comment']) ? ' aria-invalid="true"' : '' ?>><?= h($comment) ?></textarea>
               <span class="hint">optional, up to 255 characters</span>
            </div>
            <button type="submit">Post review</button>
         </form>
      </section>
<?php } ?>

      <section aria-labelledby="all-heading">
         <h2 id="all-heading"><?= $filterTitle !== '' ? 'Reviews of ' . h($filterTitle) : 'All reviews' ?></h2>
<?php if ($filterTitle !== '') { ?>
         <p><a href="reviews.php">Show reviews of every book</a></p>
<?php } ?>
<?php if ($reviews === null) { ?>
         <p>Reviews can't be shown until the database problem above is fixed.</p>
<?php } elseif (count($reviews) === 0) { ?>
         <p>No reviews yet.</p>
<?php } else { ?>
         <table class="data">
            <thead>
               <tr>
                  <th scope="col">Book</th>
                  <th scope="col">Member</th>
                  <th scope="col">Rating</th>
                  <th scope="col">Comment</th>
                  <th scope="col"><span class="visually-hidden">Actions</span></th>
               </tr>
            </thead>
            <tbody>
<?php foreach ($reviews as $review) { ?>
               <tr>
                  <td><a href="reviews.php?book=<?= h($review['book_id']) ?>"><?= h($review['title']) ?></a></td>
                  <td><?= h($review['first_name'] . ' ' . $review['last_name']) ?></td>
                  <td><?= stars($review['rating']) ?></td>
                  <td><?= h($review['comment'] ?? '') ?></td>
                  <td>
                     <form method="post" action="reviews.php" data-confirm="Delete this review?">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="review_id" value="<?= h($review['review_id']) ?>">
                        <button type="submit" class="delete">Delete<span class="visually-hidden"> this review</span></button>
                     </form>
                  </td>
               </tr>
<?php } ?>
            </tbody>
         </table>
<?php } ?>
      </section>
<?php require 'includes/foot.inc.php'; ?>
