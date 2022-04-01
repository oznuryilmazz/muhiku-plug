jQuery(document).ready(function ($) {
  $(".mhk-image-uploader").click(function (e) {
    mhk_uploader = $(this);
    e.preventDefault();
    var image = wp
      .media({
        library: {
          type: ["image"],
        },
        title: mhk_uploader.upload_file,
        // multiple: true if you want to upload multiple files at once
        multiple: false,
      })
      .open()
      .on("select", function (e) {
        // This will return the selected image from the Media Uploader, the result is an object
        var uploaded_image = image.state().get("selection").first();
        // We convert uploaded_image to a JSON object to make accessing it easier
        var image_url = uploaded_image.toJSON().url;
        // Let's assign the url value to the input field
        mhk_uploader.attr("src", image_url);
        if (mhk_uploader.hasClass("mhk-button")) {
          mhk_uploader.prev().removeClass("muhiku-plug-hidden");
          mhk_uploader.prev().attr("src", image_url);
          mhk_uploader.next().val(image_url);
          mhk_uploader.remove();
        } else {
          mhk_uploader.attr("src", image_url);
          mhk_uploader.next().next().val(image_url);
        }
      });
  });
});
