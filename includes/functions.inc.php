<?php
/* functions.inc.php - small helpers shared by every page. */

/* Make any text safe to put in HTML (< becomes &lt;, quotes are escaped).
   Use it on everything you echo that came from a user or the database. */
function h($text) {
   return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

/* POST -> redirect -> GET: after a form changes the database, send the
   browser to a fresh GET, so Reload can't submit the form twice. */
function redirect($url) {
   header('Location: ' . $url);
   exit;
}

/* A rating as stars, e.g. stars(4) gives ★★★★☆. The number is in the title
   and in hidden text, so screen readers say "4 out of 5". */
function stars($rating) {
   $rating = (int) round((float) $rating);
   return '<span class="stars" title="' . $rating . ' out of 5">'
      . str_repeat('★', $rating) . str_repeat('☆', 5 - $rating)
      . '<span class="visually-hidden"> ' . $rating . ' out of 5</span></span>';
}

/* The site menu. The link for the page we're on gets aria-current="page". */
function buildMenu() {
   $menu = [
      'index.php' => 'Home',
      'books.php' => 'Books',
      'reviews.php' => 'Reviews',
      'members.php' => 'Members',
   ];
   $current = basename($_SERVER['SCRIPT_NAME']);

   $out = '<ul class="menu">';
   foreach ($menu as $file => $label) {
      $currentAttr = ($file === $current) ? ' aria-current="page"' : '';
      $out .= '<li><a href="' . h($file) . '"' . $currentAttr . '>' . h($label) . '</a></li>';
   }
   $out .= '</ul>';
   return $out;
}

/* Messages shown after a redirect, picked by a key in the URL (?msg=added).
   We only ever show text WE wrote, never the raw $_GET value. */
function flashMessage($messages) {
   $key = $_GET['msg'] ?? '';
   return $messages[$key] ?? '';
}
