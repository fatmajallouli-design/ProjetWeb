const photoInput = document.getElementById("photoInput");
const preview = document.getElementById("preview");
const form = document.getElementById("registerForm");


photoInput.addEventListener("change", () => {
  const file = photoInput.files[0];
  if (file) {
    preview.src = URL.createObjectURL(file);
    preview.style.display = "block";
  }
});

const password = document.getElementById("password");
const confirmPassword = document.getElementById("confirmPassword");
const errorMsg = document.getElementById("errorMsg");

form.addEventListener("submit", function (e) {
  
  e.preventDefault();

  
  if (password.value.trim() !== confirmPassword.value.trim()) {
    errorMsg.textContent = "Les mots de passe ne se correspondent pas";
    errorMsg.style.color = "red";
    return; 
  }

  
  errorMsg.textContent = "";

 

 
  window.location.href = "index.html";
});
