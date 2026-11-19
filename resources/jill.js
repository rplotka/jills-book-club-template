/*  jill.js - plain JavaScript for every page of Jill's Book Club.
    1. "Are you sure?" before any delete form is sent.
    2. Home page: "Top rated" fetched as JSON from api/books.php (Lab 8).
    3. Review and member forms: checked in the browser before sending (Lab 5).
       PHP checks everything again: the browser checks are a convenience.  */

document.addEventListener("DOMContentLoaded", function () {
   // ===== 1. Confirm before deleting =====
   // Any form with data-confirm="question" asks first; Cancel stops the submit.
   document.querySelectorAll("form[data-confirm]").forEach(function (form) {
      form.addEventListener("submit", function (event) {
         if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
         }
      });
   });

   // ===== 2. Top rated, with fetch() =====
   const topList = document.getElementById("topRated");
   if (topList) {
      fetch("api/books.php?top=3")
         .then(function (response) {
            if (!response.ok) {
               throw new Error("HTTP " + response.status);
            }
            return response.json();
         })
         .then(function (data) {
            topList.innerHTML = "";
            data.books.forEach(function (book) {
               // textContent, never innerHTML, for data: a title can't become markup
               const item = document.createElement("li");
               const link = document.createElement("a");
               link.href = `reviews.php?book=${book.id}`;
               link.textContent = book.title;
               item.append(link, ` by ${book.author}: ${book.average ?? "no rating"} of 5 (${book.reviews} reviews)`);
               topList.append(item);
            });
         })
         .catch(function (error) {
            topList.innerHTML = "";
            const item = document.createElement("li");
            item.textContent = `Couldn't load the ratings (${error.message}).`;
            topList.append(item);
         });
   }

   // ===== 3. Check forms before they're sent =====
   // Shows the problems in the error box above the form, the same box PHP uses.
   function showProblems(box, problems) {
      const list = box.querySelector("ul");
      list.innerHTML = "";
      problems.forEach(function (problem) {
         const item = document.createElement("li");
         item.textContent = problem;
         list.append(item);
      });
      box.hidden = problems.length === 0;
      if (problems.length > 0) {
         box.scrollIntoView({ block: "nearest" });
      }
   }

   const reviewForm = document.getElementById("reviewForm");
   if (reviewForm) {
      reviewForm.addEventListener("submit", function (event) {
         const problems = [];
         if (reviewForm.member_id.value === "0") {
            problems.push("Choose who you are.");
         }
         if (reviewForm.book_id.value === "0") {
            problems.push("Choose a book.");
         }
         if (!reviewForm.querySelector("input[name=rating]:checked")) {
            problems.push("Pick a rating from 1 to 5 stars.");
         }
         if (reviewForm.comment.value.length > 255) {
            problems.push("Keep the comment to 255 characters.");
         }
         if (problems.length > 0) {
            event.preventDefault();   // don't send it; show what's wrong instead
         }
         showProblems(document.getElementById("reviewErrors"), problems);
      });
   }

   const memberForm = document.getElementById("memberForm");
   if (memberForm) {
      memberForm.addEventListener("submit", function (event) {
         const problems = [];
         if (memberForm.first_name.value.trim() === "") {
            problems.push("First name may not be blank.");
         }
         if (memberForm.last_name.value.trim() === "") {
            problems.push("Last name may not be blank.");
         }
         // a simple shape check: something@something.something
         if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(memberForm.email.value.trim())) {
            problems.push("Enter a real email address, like jill@example.com.");
         }
         if (problems.length > 0) {
            event.preventDefault();
         }
         showProblems(document.getElementById("memberErrors"), problems);
      });
   }
});
