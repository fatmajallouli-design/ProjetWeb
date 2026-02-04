document.getElementById("inscriptionForm").addEventListener("submit", function(e) {
    e.preventDefault();
  
    const username = document.getElementById("username").value;
    const password = document.getElementById("password").value;
    const role = document.querySelector('input[name="role"]:checked').value;
  
    
  
    
    this.reset();
  });
  