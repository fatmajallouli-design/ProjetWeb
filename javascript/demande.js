const imageBox = document.querySelector(".image-box");
const imageInput = document.getElementById("imageInput");

imageBox.onclick = function() {
  imageInput.click();
};

imageInput.onchange = function() {
  const file = imageInput.files[0];

  if (file) {
    const imageURL = URL.createObjectURL(file);
    imageBox.innerHTML = "<img src='" + imageURL + "'>";
  }
};