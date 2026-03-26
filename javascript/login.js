const photoInput = document.getElementById("photoInput");
      const preview = document.getElementById("preview");
      photoInput.addEventListener("change", () => {
        const file = photoInput.files[0];
        if (file) {
          preview.src = URL.createObjectURL(file);
          preview.style.display = "block";
        }
      });
      function showError(message) {
        const errorAlert = document.getElementById("errorAlert");
        const errorMessage = document.getElementById("errorMessage");

        errorMessage.innerText = message;
        errorAlert.style.display = "block";
      }

      function closeError() {
        document.getElementById("errorAlert").style.display = "none";
      }

      document
        .getElementById("registerForm")
        .addEventListener("submit", function (e) {
          let username = document.querySelector('input[name="username"]').value.trim();
          let password = document
            .querySelector('input[name="password"]')
            .value.trim();
          let confirmPassword = document
            .querySelector('input[name="confirmPassword"]')
            .value.trim();

          if (username === "" || password === "" || confirmPassword === "") {
            e.preventDefault();
            showError("Veuillez remplir tous les champs obligatoires.");
            return;
          }

          if (password !== confirmPassword) {
            e.preventDefault();
            showError("Les mots de passe ne correspondent pas.");
            return;
          }

          if (password.length < 6) {
            e.preventDefault();
            showError("Mot de passe trop court (minimum 6 caractères).");
            return;
          }
          if (photoInput.files.length === 0) {
            e.preventDefault();
            showError("Veuillez ajouter une photo.");
            return;
          }
        });