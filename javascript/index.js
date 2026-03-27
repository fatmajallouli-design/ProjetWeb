
        const menuBtn = document.getElementById('menuBtn');
        const sideMenu = document.getElementById('sideMenu');
        const closeMenu = document.getElementById('closeMenu');
        const overlay = document.getElementById('overlay');
        const sidebarPanierTrigger = document.getElementById('sidebarPanierTrigger');
        const floatingSidebarPanierTrigger = document.getElementById('floatingSidebarPanierTrigger');
        const demandeTrigger = document.getElementById('demandeTrigger');
        const notificationTrigger = document.getElementById('notificationTrigger');
        const sidebarCartButtons = document.querySelectorAll('.open-index-sidebar');
        const homeSearchInput = document.getElementById('homeProductSearch');
        const homeProductCards = document.querySelectorAll('.searchable-product');
        const cartToast = document.getElementById('cartToast');

        function openMenu() {
            sideMenu.classList.add('active');
            overlay.style.display = 'block';
        }

        function closeAll() {
            sideMenu.classList.remove('active');
            overlay.style.display = 'none';
        }

        if (menuBtn && closeMenu && overlay) {
            menuBtn.addEventListener('click', openMenu);
            closeMenu.addEventListener('click', closeAll);
            overlay.addEventListener('click', closeAll);
        }

        if (sidebarPanierTrigger) {
            sidebarPanierTrigger.addEventListener('click', function (event) {
                event.preventDefault();
                openMenu();
                showCartToast('Connectez-vous pour continuer.');
            });
        }

        if (floatingSidebarPanierTrigger) {
            floatingSidebarPanierTrigger.addEventListener('click', function (event) {
                event.preventDefault();
                openMenu();
                showCartToast('Connectez-vous pour continuer.');
            });
        }

        if (demandeTrigger) {
            demandeTrigger.addEventListener('click', function (event) {
                event.preventDefault();
                showCartToast('Connectez-vous pour continuer.');
            });
        }

        if (notificationTrigger) {
            notificationTrigger.addEventListener('click', function (event) {
                event.preventDefault();
                showCartToast('Connectez-vous pour continuer.');
            });
        }

        sidebarCartButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                openMenu();
            });
        });

        if (homeSearchInput) {
            homeSearchInput.addEventListener('input', function () {
                const query = this.value.toLowerCase().trim();

                homeProductCards.forEach(function (card) {
                    const text = card.textContent.toLowerCase();
                    card.style.display = text.includes(query) ? '' : 'none';
                });
            });
        }

        function showCartToast(message) {
            if (!cartToast) return;
            cartToast.textContent = message;
            cartToast.classList.add('visible');
            window.clearTimeout(showCartToast.timeoutId);
            showCartToast.timeoutId = window.setTimeout(function () {
                cartToast.classList.remove('visible');
            }, 2200);
        }

        sidebarCartButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                showCartToast('Connectez-vous pour continuer.');
            });
        });
  