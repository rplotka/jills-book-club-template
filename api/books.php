<?php
/* api/books.php - the books as JSON, for fetch() on the home page (Lab 8,
   from the other side: this time we serve the JSON ourselves).
   Optional ?top=3 returns only the best-rated books.

   No h() here: json_encode() does the quoting, and this isn't HTML. The
   escaping happens where the data reaches a page (jill.js uses textContent). */

require __DIR__ . '/../includes/init.inc.php';

header('Content-Type: application/json; charset=utf-8');

if (!$db) {
   http_response_code(500);
   echo json_encode(['error' => 'The database is not available.']);
   exit;
}

$top = (int) ($_GET['top'] ?? 0);   // 0 = every book

try {
   $sql = 'SELECT b.book_id, b.title, b.author, b.year,
                  ROUND(AVG(r.rating), 1) AS average, COUNT(r.review_id) AS reviews
           FROM books AS b
           LEFT JOIN reviews AS r ON r.book_id = b.book_id
           GROUP BY b.book_id
           ORDER BY AVG(r.rating) IS NULL, AVG(r.rating) DESC, COUNT(r.review_id) DESC, b.title';
   if ($top > 0) {
      $stmt = $db->prepare($sql . ' LIMIT ?');
      $stmt->bind_param('i', $top);
      $stmt->execute();
      $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
   } else {
      $rows = $db->query($sql)->fetch_all(MYSQLI_ASSOC);
   }

   // Numbers as numbers in the JSON, not strings ("4.5" -> 4.5).
   $books = array_map(function ($row) {
      return [
         'id' => (int) $row['book_id'],
         'title' => $row['title'],
         'author' => $row['author'],
         'year' => $row['year'] === null ? null : (int) $row['year'],
         'average' => $row['average'] === null ? null : (float) $row['average'],
         'reviews' => (int) $row['reviews'],
      ];
   }, $rows);

   echo json_encode(['books' => $books], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (mysqli_sql_exception $e) {
   http_response_code(500);
   echo json_encode(['error' => $e->getMessage()]);
}
