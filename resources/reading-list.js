/*  reading-list.js - Jill's reading list on books.php, in jQuery (Lab 6, Quiz 2).
    Click (or press Enter on) a title to cross it off; "Books read: X of Y"
    keeps count; Hide/Show finished books. Which books are read is saved in
    localStorage, so it survives a reload. It's this browser's list only:
    nothing here is sent to the server.                                      */

$(function () {
   const KEY = "jillReadBooks";   // where the list of read book ids is saved

   // The saved ids, or an empty list the first time (or if storage is blocked).
   function loadRead() {
      try {
         return JSON.parse(localStorage.getItem(KEY)) || [];
      } catch (error) {
         return [];
      }
   }

   function saveRead() {
      const ids = $("#bookList li.read").map(function () {
         return String($(this).data("id"));
      }).get();
      try {
         localStorage.setItem(KEY, JSON.stringify(ids));
      } catch (error) {
         // private browsing can block storage: the list still works, it just won't be remembered
      }
   }

   // "Books read: X of Y". Hidden books still count: they're still <li>s in the list.
   function showCount() {
      const read = $("#bookList li.read").length;
      const total = $("#bookList li").length;
      $("#readCount").text(`Books read: ${read} of ${total}`);
   }

   // Start: cross off the books saved last time.
   const saved = loadRead();
   $("#bookList li").each(function () {
      if (saved.includes(String($(this).data("id")))) {
         $(this).addClass("read");
      }
   });
   showCount();

   // Cross off / un-cross. Delegation: one handler on the list, filtered to "li".
   $("#bookList").on("click", "li", function () {
      $(this).toggleClass("read");
      saveRead();
      showCount();
   });

   // Keyboard users: Enter or Space on a focused title does the same as a click.
   $("#bookList").on("keydown", "li", function (event) {
      if (event.key === "Enter" || event.key === " ") {
         event.preventDefault();
         $(this).trigger("click");
      }
   });

   // Hide the finished books, and bring them back so one can be un-crossed.
   $("#hideRead").on("click", function () {
      $("#bookList li.read").hide();
   });
   $("#showRead").on("click", function () {
      $("#bookList li.read").show();
   });
});
