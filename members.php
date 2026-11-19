<?php
/* members.php - who's in the club, and a form to join.
   The email column is UNIQUE, so a second sign-up with the same email is
   refused by MariaDB (error 1062); we catch that and show a friendly message. */

require 'includes/init.inc.php';

$pageTitle = 'Members';
$errors = [];
$firstName = '';
$lastName = '';
$email = '';
$members = null;
$message = flashMessage(['added' => 'Welcome to the club!']);

if ($db) {
   try {
      if ($_SERVER['REQUEST_METHOD'] === 'POST') {
         $firstName = trim($_POST['first_name'] ?? '');
         $lastName = trim($_POST['last_name'] ?? '');
         $email = trim($_POST['email'] ?? '');

         if ($firstName === '') {
            $errors['first_name'] = 'First name may not be blank.';
         }
         if ($lastName === '') {
            $errors['last_name'] = 'Last name may not be blank.';
         }
         if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a real email address, like jill@example.com.';
         }

         if (count($errors) === 0) {
            try {
               // joined = today, filled in by MariaDB
               $stmt = $db->prepare('INSERT INTO members (first_name, last_name, email, joined) VALUES (?, ?, ?, CURDATE())');
               $stmt->bind_param('sss', $firstName, $lastName, $email);
               $stmt->execute();
               redirect('members.php?msg=added');
            } catch (mysqli_sql_exception $e) {
               if ($e->getCode() === 1062) {   // duplicate key: the email is taken
                  $errors['email'] = 'Someone already joined with that email.';
               } else {
                  throw $e;
               }
            }
         }
      }

      // Each member and how many reviews they've written (LEFT JOIN keeps members with none).
      $result = $db->query('SELECT m.first_name, m.last_name, m.joined, COUNT(r.review_id) AS reviews
                            FROM members AS m
                            LEFT JOIN reviews AS r ON r.member_id = m.member_id
                            GROUP BY m.member_id
                            ORDER BY m.joined, m.last_name');
      $members = $result->fetch_all(MYSQLI_ASSOC);
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

      <section aria-labelledby="list-heading">
         <h2 id="list-heading">Who's in the club</h2>
<?php if ($members === null) { ?>
         <p>The list can't be shown until the database problem above is fixed.</p>
<?php } else { ?>
         <table class="data">
            <thead>
               <tr>
                  <th scope="col">Member</th>
                  <th scope="col">Joined</th>
                  <th scope="col">Reviews</th>
               </tr>
            </thead>
            <tbody>
<?php foreach ($members as $member) { ?>
               <tr>
                  <td><?= h($member['first_name'] . ' ' . $member['last_name']) ?></td>
                  <td><?= h($member['joined'] ?? '') ?></td>
                  <td><?= h($member['reviews']) ?></td>
               </tr>
<?php } ?>
            </tbody>
         </table>
         <p class="hint">Email addresses stay private: they're in the database, but this page never shows them.</p>
<?php } ?>
      </section>

      <section aria-labelledby="join-heading">
         <h2 id="join-heading">Join the club</h2>
         <div id="memberErrors" class="message error" role="alert"<?= count($errors) > 0 ? '' : ' hidden' ?>>
            <p>Please correct the following:</p>
            <ul>
<?php foreach ($errors as $error) { ?>
               <li><?= h($error) ?></li>
<?php } ?>
            </ul>
         </div>
         <form method="post" action="members.php" id="memberForm" class="stack-form" novalidate>
            <div class="field">
               <label for="first_name">First name</label>
               <input type="text" id="first_name" name="first_name" maxlength="60" required value="<?= h($firstName) ?>"<?= isset($errors['first_name']) ? ' aria-invalid="true"' : '' ?>>
            </div>
            <div class="field">
               <label for="last_name">Last name</label>
               <input type="text" id="last_name" name="last_name" maxlength="60" required value="<?= h($lastName) ?>"<?= isset($errors['last_name']) ? ' aria-invalid="true"' : '' ?>>
            </div>
            <div class="field">
               <label for="email">Email</label>
               <input type="email" id="email" name="email" maxlength="120" required value="<?= h($email) ?>"<?= isset($errors['email']) ? ' aria-invalid="true"' : '' ?>>
            </div>
            <button type="submit">Join</button>
         </form>
      </section>
<?php require 'includes/foot.inc.php'; ?>
