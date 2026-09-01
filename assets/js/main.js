// Fashion Platform Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
        });
    }
    
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });
    
    // Image modal functionality
    window.openImageModal = function(imageName) {
        const modal = document.createElement('div');
        modal.className = 'image-modal';
        modal.innerHTML = '<div class="modal-content"><img src="../uploads/references/' + imageName + '" alt="Reference"><span class="close" onclick="this.parentElement.parentElement.remove()">&times;</span></div>';
        document.body.appendChild(modal);
    };
});