const tabButtons = document.querySelectorAll(".tab-btn");
const productCards = document.querySelectorAll(".product-card");

tabButtons.forEach((button) => {
  button.addEventListener("click", () => {
    tabButtons.forEach((btn) => btn.classList.remove("active"));
    button.classList.add("active");

    const category = button.dataset.category;

    productCards.forEach((card) => {
      if (category === "all" || card.dataset.category === category) {
        card.classList.remove("hidden");
      } else {
        card.classList.add("hidden");
      }
    });
  });
});
