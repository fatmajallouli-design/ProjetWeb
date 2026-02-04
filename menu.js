const menuBtn = document.getElementById("menuBtn");
const sideMenu = document.getElementById("sideMenu");
const closeMenu = document.getElementById("closeMenu");
const overlay = document.getElementById("overlay");

menuBtn.addEventListener("click", () => {
  sideMenu.classList.add("active");
  overlay.style.display = "block";
});

closeMenu.addEventListener("click", closeAll);
overlay.addEventListener("click", closeAll);

function closeAll() {
  sideMenu.classList.remove("active");
  overlay.style.display = "none";
}
