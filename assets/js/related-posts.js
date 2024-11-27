jQuery(document).ready(function ($) {
  function applyFadeInAnimation() {
    $(".joker-related-post-item").each(function (index) {
      var element = $(this);
      setTimeout(function () {
        element.addClass("fade-in");
      }, index * 75); // Add a slight delay for a staggered effect
    });
  }

  // Apply fade-in on initial page load
  applyFadeInAnimation();

  // Handle Load More button click
  $("#joker-load-more-posts").on("click", function () {
    var button = $(this);
    var page = button.data("page");
    var maxPage = button.data("max-page");
    var postsPerPage = button.data("posts-per-page");
    var category = button.data("category");

    if (page <= maxPage) {
      $.ajax({
        url: joker_ajax_object.ajax_url,
        type: "POST",
        data: {
          action: "joker_load_more_posts",
          page: page,
          posts_per_page: postsPerPage,
          category: category,
        },
        success: function (response) {
          var newPosts = $(response).hide(); // Temporarily hide new posts
          $(".joker-related-posts-grid").append(newPosts);
          newPosts.show(); // Show new posts
          applyFadeInAnimation(); // Reapply fade-in animation for new posts

          button.data("page", page + 1);
          if (page + 1 > maxPage) {
            button.remove();
          }
        },
      });
    }
  });
});
