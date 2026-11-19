# Jill's Book Club

The Book Club site from ITWS 1100: the page we built in the JavaScript, jQuery and AJAX classes,
now a PHP + MariaDB app with its own database.

| Page | What it does |
|---|---|
| `index.php` | welcome, counts, the next meeting, the five latest reviews, Top rated (fetched from the API) |
| `books.php` | every book with its average rating; add a book; remove one; Jill's reading list |
| `reviews.php` | write a review; all reviews, or one book's with `?book=3` |
| `members.php` | members and their review counts; join the club |
| `api/books.php` | the books as JSON; `?top=3` for the best three |
| `feed.php` | the latest ten reviews as RSS |

## Make it yours

1. On GitHub, click **Use this template → Create a new repository**. Name it `jills-book-club`,
   put it in **your own account**, and make it **private**.
2. You now have your own copy to change. The class shows how to connect it to Codex and to your
   Azure server.

## Run it on your server

```bash
sudo mariadb < setup.sql                      # creates the bookclub_jill database
sudo mariadb -e "CREATE USER 'jill_app'@'localhost' IDENTIFIED BY 'a-long-password';
  GRANT SELECT, INSERT, UPDATE, DELETE ON bookclub_jill.* TO 'jill_app'@'localhost';"
cp includes/config.example.inc.php includes/config.inc.php   # then put that password in it
```

`includes/config.inc.php` holds the password. It is in `.gitignore`: never commit it.

## For Codex (and any other AI)

`AGENTS.md` lists the rules this code follows. Codex reads it before every task, so the code it
writes matches the code that is already here.
