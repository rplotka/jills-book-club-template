-- Jill's Book Club, the complete site (solution-jill): its own database, bookclub_jill.
-- Same tables and starting data as the Databases deck and the PHP demos.
-- Run once:  sudo mariadb < setup.sql     (or paste into phpMyAdmin's SQL tab)

CREATE DATABASE IF NOT EXISTS bookclub_jill CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE bookclub_jill;

DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS members;

CREATE TABLE members (
  member_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  first_name VARCHAR(60) NOT NULL,
  last_name VARCHAR(60) NOT NULL,
  email VARCHAR(120) UNIQUE,
  joined DATE,
  PRIMARY KEY (member_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

CREATE TABLE books (
  book_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(150) NOT NULL,
  author VARCHAR(100) NOT NULL,
  year SMALLINT UNSIGNED,
  pages SMALLINT UNSIGNED,
  PRIMARY KEY (book_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

CREATE TABLE reviews (
  review_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id INT UNSIGNED NOT NULL,
  book_id INT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment VARCHAR(255),
  PRIMARY KEY (review_id),
  FOREIGN KEY (member_id) REFERENCES members (member_id),
  FOREIGN KEY (book_id) REFERENCES books (book_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT INTO members (first_name, last_name, email, joined) VALUES
('Jill', 'Park', 'jill@example.com', '2023-01-15'),
('Dana', 'Cho', 'dana@example.com', '2024-09-02'),
('Marcus', 'Reed', 'marcus@example.com', '2025-03-10'),
('Priya', 'Nair', 'priya@example.com', '2025-11-20'),
('Tom', 'Alvarez', 'tom@example.com', '2026-08-30');

INSERT INTO books (title, author, year, pages) VALUES
('Harry Potter and the Chamber of Secrets', 'J. K. Rowling', 1999, 341),
('Last Night at the Lobster', 'Stewart O''Nan', 2007, 146),
('The Hobbit', 'J. R. R. Tolkien', 1937, 310),
('The Night Circus', 'Erin Morgenstern', 2011, 387),
('Project Hail Mary', 'Andy Weir', 2021, 476),
('Educated', 'Tara Westover', 2018, 334);

INSERT INTO reviews (member_id, book_id, rating, comment) VALUES
(1, 3, 5, 'Read it every winter.'),
(2, 3, 4, 'Slow start, great finish.'),
(3, 3, 4, NULL),
(1, 2, 4, 'Short and sad and perfect.'),
(4, 2, 3, NULL),
(2, 5, 5, 'Rocky!'),
(3, 5, 5, 'Best of the year.'),
(4, 5, 4, NULL),
(1, 4, 3, 'Beautiful, not much happens.'),
(2, 6, 5, NULL),
(4, 6, 3, 'Hard to read, glad I did.'),
(4, 1, 2, 'Not for me.');
