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

Copy this folder to your VM (Lab 11 gives you the commands), then on the VM:

```bash
sudo bash setup/install.sh git@github.com:YOU/jills-book-club.git   # read it first: it says what each step does
sudo bash setup/check.sh                                            # PASS/FAIL for every piece, with the fix
```

`install.sh` puts the site at `/var/www/html/bookclub`, creates the `bookclub_jill` database and its own
`jill_app` user (the password goes only into `includes/config.inc.php`, which is never committed), connects the
folder to your repo with a read-only deploy key, and writes `deploy-bookclub.php` (Lab 10's webhook, for this
repo). It is safe to run again: it never reloads `setup.sql` over your data.
