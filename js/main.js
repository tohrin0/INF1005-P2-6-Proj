document.addEventListener('DOMContentLoaded', function() {
    // Image popup functionality
    const thumbnails = document.getElementsByClassName('img-thumbnail');
    let currentPopup = null;

    function createPopup(imgSrc) {
        removePopup();
        const popupContainer = document.createElement('div');
        popupContainer.setAttribute('class', 'img-popup');
        
        const popupImage = document.createElement('img');
        popupImage.setAttribute('src', imgSrc);
        popupImage.setAttribute('class', 'popup-content');
        
        popupContainer.appendChild(popupImage);
        document.body.appendChild(popupContainer);
        currentPopup = popupContainer;

        popupContainer.addEventListener('click', removePopup);
    }

    function removePopup() {
        if (currentPopup) {
            currentPopup.remove();
            currentPopup = null;
        }
    }

    Array.from(thumbnails).forEach(thumb => {
        thumb.addEventListener('click', function(e) {
            e.preventDefault();
            const largeSrc = this.getAttribute('data-large');
            createPopup(largeSrc);
        });
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && currentPopup) {
            removePopup();
        }
    });

    // Menu activation functionality
    function activateMenu() {
        const navLinks = document.querySelectorAll('nav a');
        navLinks.forEach(link => {
            if (link.href === location.href) {
                link.classList.add('active');
                const parentLi = link.closest('li');
                if (parentLi) {
                    parentLi.classList.add('active');
                }
            }
        });
    }

    // Initialize menu activation
    activateMenu();
});