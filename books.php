<?php
/* books.php - the club's books.
   PHP + MariaDB: list (LEFT JOIN + AVG + GROUP BY), add (validate, prepared
   INSERT, redirect), delete (POST form, prepared DELETE, redirect).
   jQuery: "Jill's reading list" at the bottom (resources/reading-list.js),
   the Quiz 2 book list: cross off, "Books read: X of Y", hide/show finished. */

require 'includes/init.inc.php';

$pageTitle = 'Books';
$extraScripts = ['resources/jquery-4.0.0.min.js', 'resources/reading-list.js'];
$errors = [];
$title = '';
$author = '';
$year = '';
$pages = '';
$books = null;
$message = flashMessage(['added' => 'Book added.', 'deleted' => 'Book removed.']);

if ($db) {
   try {
      if ($_SERVER['REQUEST_METHOD'] === 'POST') {
         $action = $_POST['action'] ?? '';

         if ($action === 'add') {
            $title = trim($_POST['title'] ?? '');
            $author = trim($_POST['author'] ?? '');
            $year = trim($_POST['year'] ?? '');
            $pages = trim($_POST['pages'] ?? '');

            // Never trust the browser: these checks are the real ones.
            if ($title === '') {
               $errors['title'] = 'Title may not be blank.';
            } elseif (mb_strlen($title) > 150) {
               $errors['title'] = 'Title may be at most 150 characters.';
            }
            if ($author === '') {
               $errors['author'] = 'Author may not be blank.';
            }
            if ($year !== '' && (!preg_match('/^\d{4}$/', $year) || (int) $year > (int) date('Y'))) {
               $errors['year'] = 'Year must be 4 digits and not in the future (or leave it blank).';
            }
            if ($pages !== '' && (!ctype_digit($pages) || (int) $pages < 1 || (int) $pages > 5000)) {
               $errors['pages'] = 'Pages must be a whole number from 1 to 5000 (or leave it blank).';
            }

            if (count($errors) === 0) {
               // Blank optional fields are stored as NULL.
               $yearForDb = ($year === '') ? null : (int) $year;
               $pagesForDb = ($pages === '') ? null : (int) $pages;
               $stmt = $db->prepare('INSERT INTO books (title, author, year, pages) VALUES (?, ?, ?, ?)');
               $stmt->bind_param('ssii', $title, $author, $yearForDb, $pagesForDb);
               $stmt->execute();
               redirect('books.php?msg=added');
            }
         } elseif ($action === 'delete') {
            $bookId = (int) ($_POST['book_id'] ?? 0);
            try {
               $stmt = $db->prepare('DELETE FROM books WHERE book_id = ?');
               $stmt->bind_param('i', $bookId);
               $stmt->execute();
               redirect('books.php?msg=deleted');
            } catch (mysqli_sql_exception $e) {
               // 1451: a review still points at this book (the foreign key says no)
               if ($e->getCode() === 1451) {
                  $errors['delete'] = 'That book has reviews, so it can\'t be removed. Delete its reviews first.';
               } else {
                  throw $e;
               }
            }
         }
      }

      // Every book, with its average rating and how many reviews it has.
      // LEFT JOIN keeps books nobody has reviewed yet (their average is NULL).
      $result = $db->query('SELECT b.book_id, b.title, b.author, b.year, b.pages,
                                   ROUND(AVG(r.rating), 1) AS average, COUNT(r.review_id) AS reviews
                            FROM books AS b
                            LEFT JOIN reviews AS r ON r.book_id = b.book_id
                            GROUP BY b.book_id
                            ORDER BY b.title');
      $books = $result->fetch_all(MYSQLI_ASSOC);
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
<?php if (isset($errors['delete'])) { ?>
      <div class="message error" role="alert">
         <p><?= h($errors['delete']) ?></p>
      </div>
<?php } ?>

      <section aria-labelledby="list-heading">
         <h2 id="list-heading">Our books</h2>
<?php if ($books === null) { ?>
         <p>The list can't be shown until the database problem above is fixed.</p>
<?php } elseif (count($books) === 0) { ?>
         <p>No books yet. Add the first one below.</p>
<?php } else { ?>
         <table class="data">
            <thead>
               <tr>
                  <th scope="col">Title</th>
                  <th scope="col">Author</th>
                  <th scope="col">Year</th>
                  <th scope="col">Rating</th>
                  <th scope="col"><span class="visually-hidden">Actions</span></th>
               </tr>
            </thead>
            <tbody>
<?php foreach ($books as $book) { ?>
               <tr>
                  <td><a href="reviews.php?book=<?= h($book['book_id']) ?>"><?= h($book['title']) ?></a></td>
                  <td><?= h($book['author']) ?></td>
                  <td><?= h($book['year'] ?? '') ?></td>
                  <td>
<?php if ($book['average'] === null) { ?>
                     <span class="hint">no reviews yet</span>
<?php } else { ?>
                     <?= stars($book['average']) ?> <span class="hint"><?= h($book['average']) ?> (<?= h($book['reviews']) ?>)</span>
<?php } ?>
                  </td>
                  <td>
                     <!-- a delete is a small POST form, never a link -->
                     <form method="post" action="books.php" data-confirm="Remove &ldquo;<?= h($book['title']) ?>&rdquo; from the club list?">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="book_id" value="<?= h($book['book_id']) ?>">
                        <button type="submit" class="delete">Remove<span class="visually-hidden"> <?= h($book['title']) ?></span></button>
                     </form>
                  </td>
               </tr>
<?php } ?>
            </tbody>
         </table>
<?php } ?>
      </section>

      <section aria-labelledby="add-heading">
         <h2 id="add-heading">Add a book</h2>
<?php if (count(array_diff_key($errors, ['delete' => ''])) > 0) { ?>
         <div class="message error" role="alert">
            <p>Please correct the following:</p>
            <ul>
<?php foreach (array_diff_key($errors, ['delete' => '']) as $error) { ?>
               <li><?= h($error) ?></li>
<?php } ?>
            </ul>
         </div>
<?php } ?>
         <form method="post" action="books.php" class="stack-form">
            <input type="hidden" name="action" value="add">
            <div class="field">
               <label for="title">Title</label>
               <input type="text" id="title" name="title" maxlength="150" required value="<?= h($title) ?>"<?= isset($errors['title']) ? ' aria-invalid="true"' : '' ?>>
            </div>
            <div class="field">
               <label for="author">Author</label>
               <input type="text" id="author" name="author" maxlength="100" required value="<?= h($author) ?>"<?= isset($errors['author']) ? ' aria-invalid="true"' : '' ?>>
            </div>
            <div class="field">
               <label for="year">Year</label>
               <input type="text" id="year" name="year" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" value="<?= h($year) ?>"<?= isset($errors['year']) ? ' aria-invalid="true"' : '' ?>>
               <span class="hint">optional</span>
            </div>
            <div class="field">
               <label for="pages">Pages</label>
               <input type="number" id="pages" name="pages" min="1" max="5000" value="<?= h($pages) ?>"<?= isset($errors['pages']) ? ' aria-invalid="true"' : '' ?>>
               <span class="hint">optional</span>
            </div>
            <button type="submit">Add book</button>
         </form>
      </section>

<?php if ($books) { ?>
      <section aria-labelledby="reading-heading">
         <h2 id="reading-heading">Jill's reading list</h2>
         <p class="hint">Click a title once you've finished it. This list lives in your browser (jQuery and localStorage); the server never sees it.</p>
         <p id="readCount" aria-live="polite"></p>
         <p>
            <button type="button" id="hideRead">Hide finished books</button>
            <button type="button" id="showRead">Show finished books</button>
         </p>
         <!-- each book's id is in data-id, so the browser can remember which ones were read -->
         <ul id="bookList" class="reading-list">
<?php foreach ($books as $book) { ?>
            <li data-id="<?= h($book['book_id']) ?>" tabindex="0"><?= h($book['title']) ?></li>
<?php } ?>
         </ul>
      </section>
<?php } ?>
<?php require 'includes/foot.inc.php'; ?>
