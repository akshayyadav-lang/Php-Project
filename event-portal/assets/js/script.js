// Event Portal - JavaScript

// Form validation helpers
document.addEventListener('DOMContentLoaded', function() {
    // Add basic client-side validation
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            let errorMessage = '';
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#e74c3c';
                } else {
                    field.style.borderColor = '#ddd';
                }
            });
            
            // Name validation (letters and spaces only)
            const nameFields = form.querySelectorAll('input[name="name"]');
            nameFields.forEach(field => {
                if (field.value && !/^[a-zA-Z\s]+$/.test(field.value)) {
                    isValid = false;
                    errorMessage = 'Name should only contain letters and spaces.';
                    field.style.borderColor = '#e74c3c';
                }
            });
            
            // Email validation
            const emailFields = form.querySelectorAll('input[type="email"]');
            emailFields.forEach(field => {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (field.value && !emailRegex.test(field.value)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid email address.';
                    field.style.borderColor = '#e74c3c';
                }
            });
            
            // Phone validation (exactly 10 digits)
            const phoneFields = form.querySelectorAll('input[name="phone"], input[type="tel"]');
            phoneFields.forEach(field => {
                if (field.value && !/^[0-9]{10}$/.test(field.value)) {
                    isValid = false;
                    errorMessage = 'Phone number must be exactly 10 digits.';
                    field.style.borderColor = '#e74c3c';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert(errorMessage || 'Please fill in all required fields correctly.');
            }
        });
        
        // Real-time validation feedback
        const nameFields = form.querySelectorAll('input[name="name"]');
        nameFields.forEach(field => {
            field.addEventListener('input', function() {
                if (this.value && !/^[a-zA-Z\s]*$/.test(this.value)) {
                    this.style.borderColor = '#e74c3c';
                } else {
                    this.style.borderColor = '#ddd';
                }
            });
        });
        
        const phoneFields = form.querySelectorAll('input[name="phone"], input[type="tel"]');
        phoneFields.forEach(field => {
            // Only allow digits
            field.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 10) {
                    this.value = this.value.slice(0, 10);
                }
                if (this.value && this.value.length !== 10) {
                    this.style.borderColor = '#e74c3c';
                } else {
                    this.style.borderColor = '#ddd';
                }
            });
        });
    });
    
    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('.btn-danger[onclick*="delete"]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this?')) {
                e.preventDefault();
            }
        });
    });
    
    // Event Search Functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const eventCards = document.querySelectorAll('.event-card');
            let visibleCount = 0;
            
            eventCards.forEach(card => {
                const title = card.getAttribute('data-title') || '';
                const category = card.getAttribute('data-category') || '';
                const location = card.getAttribute('data-location') || '';
                
                if (searchTerm === '' || 
                    title.includes(searchTerm) || 
                    category.includes(searchTerm) || 
                    location.includes(searchTerm)) {
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                }
            });
            
            // Show message if no results
            let noResultsMsg = document.getElementById('noResultsMessage');
            if (visibleCount === 0 && searchTerm !== '') {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.id = 'noResultsMessage';
                    noResultsMsg.className = 'card text-center no-events-card';
                    noResultsMsg.innerHTML = '<p>No events found matching your search.</p>';
                    const eventsContainer = document.getElementById('eventsContainer');
                    if (eventsContainer && eventsContainer.parentNode) {
                        eventsContainer.parentNode.insertBefore(noResultsMsg, eventsContainer.nextSibling);
                    }
                }
                noResultsMsg.style.display = 'block';
            } else {
                if (noResultsMsg) {
                    noResultsMsg.style.display = 'none';
                }
            }
        });
    }
});

