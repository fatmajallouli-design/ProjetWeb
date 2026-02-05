const container = document.getElementById('container');
const registerBtn = document.getElementById('creer');
const loginBtn = document.getElementById('connecter');

registerBtn.addEventListener('click', () => {
    container.classList.add("active");
});

loginBtn.addEventListener('click', () => {
    container.classList.remove("active");
});
const photoInput = document.getElementById("photoInput");
const preview = document.getElementById("preview");
const loginForm = document.getElementById("inscriptionForm");
const signupForm = document.getElementById("signup");

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

signupForm.addEventListener("submit", function (e) {
  
  e.preventDefault();

  
  if (password.value.trim() !== confirmPassword.value.trim()) {
    errorMsg.textContent = "Les mots de passe ne se correspondent pas";
    errorMsg.style.color = "red";
    return; 
  }

  
  errorMsg.textContent = "";

 

 
  window.location.href = "index.html";
});
loginForm.addEventListener("submit", function(e) {
    e.preventDefault();
  
    const username = document.getElementById("username").value;
    const loginpassword = document.getElementById("loginpassword").value;
    const rolelogin = document.querySelector('input[name="role-login"]:checked').value;
  
    
  
    
    this.reset();
  });

const params = new URLSearchParams(window.location.search);
const mode = params.get("mode");

if (mode === "signup") {
  container.classList.add("active");    
} else {
  container.classList.remove("active"); 
}

  