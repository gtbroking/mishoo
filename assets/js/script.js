// Main JavaScript file for the shopping website

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initMobileMenu();
    initSlider();
    initWishlist();
    initCart();
    initSearch();
    initTheme();
});

// Mobile Menu Functionality
function initMobileMenu() {
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileNav = document.querySelector('.mobile-nav');
    const closeMobileNav = document.querySelector('.close-mobile-nav');
    
    if (mobileMenuToggle && mobileNav) {
        mobileMenuToggle.addEventListener('click', function() {
            mobileNav.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }
    
    if (closeMobileNav && mobileNav) {
        closeMobileNav.addEventListener('click', function() {
            mobileNav.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
    
    // Close mobile menu when clicking outside
    if (mobileNav) {
        mobileNav.addEventListener('click', function(e) {
            if (e.target === mobileNav) {
                mobileNav.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }
}

// Hero Slider Functionality
function initSlider() {
    const slides = document.querySelectorAll('.slide');
    const prevBtn = document.querySelector('.slider-nav .prev');
    const nextBtn = document.querySelector('.slider-nav .next');
    
    if (slides.length === 0) return;
    
    let currentSlide = 0;
    
    function showSlide(index) {
        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === index);
        });
    }
    
    function nextSlide() {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    }
    
    function prevSlide() {
        currentSlide = (currentSlide - 1 + slides.length) % slides.length;
        showSlide(currentSlide);
    }
    
    // Auto-advance slides
    setInterval(nextSlide, 5000);
    
    // Navigation buttons
    if (nextBtn) nextBtn.addEventListener('click', nextSlide);
    if (prevBtn) prevBtn.addEventListener('click', prevSlide);
    
    // Touch/swipe support for mobile
    let startX = 0;
    let endX = 0;
    
    const sliderContainer = document.querySelector('.slider-container');
    if (sliderContainer) {
        sliderContainer.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
        });
        
        sliderContainer.addEventListener('touchend', function(e) {
            endX = e.changedTouches[0].clientX;
            handleSwipe();
        });
        
        function handleSwipe() {
            const swipeThreshold = 50;
            const diff = startX - endX;
            
            if (Math.abs(diff) > swipeThreshold) {
                if (diff > 0) {
                    nextSlide();
                } else {
                    prevSlide();
                }
            }
        }
    }
}

// Wishlist Functionality
function initWishlist() {
    const wishlistBtns = document.querySelectorAll('.wishlist-btn');
    
    wishlistBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const productId = this.dataset.productId;
            const isActive = this.classList.contains('active');
            
            // Show loading state
            const originalContent = this.innerHTML;
            this.innerHTML = '<div class="loading"></div>';
            this.disabled = true;
            
            // Make AJAX request
            fetch('api/wishlist_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    action: isActive ? 'remove' : 'add'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.classList.toggle('active');
                    showNotification(data.message, 'success');
                    updateWishlistCount();
                } else {
                    showNotification(data.message || 'Error occurred', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Network error occurred', 'error');
            })
            .finally(() => {
                this.innerHTML = originalContent;
                this.disabled = false;
            });
        });
    });
}

// Cart Functionality
function initCart() {
    const addToCartBtns = document.querySelectorAll('.add-to-cart');
    
    addToCartBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const productId = this.dataset.productId;
            const quantity = this.dataset.quantity || 1;
            
            // Show loading state
            const originalContent = this.innerHTML;
            this.innerHTML = '<div class="loading"></div> Adding...';
            this.disabled = true;
            
            // Make AJAX request
            fetch('api/cart_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: parseInt(quantity),
                    action: 'add'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    updateCartCount();
                    
                    // Animate button
                    this.innerHTML = '<i class="fas fa-check"></i> Added!';
                    this.classList.add('btn-success');
                    
                    setTimeout(() => {
                        this.innerHTML = originalContent;
                        this.classList.remove('btn-success');
                    }, 2000);
                } else {
                    showNotification(data.message || 'Error occurred', 'error');
                    this.innerHTML = originalContent;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Network error occurred', 'error');
                this.innerHTML = originalContent;
            })
            .finally(() => {
                this.disabled = false;
            });
        });
    });
}

// Search Functionality
function initSearch() {
    const searchForm = document.querySelector('.search-bar form');
    const searchInput = document.querySelector('.search-bar input');
    
    if (searchForm && searchInput) {
        // Add search suggestions (optional enhancement)
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length >= 2) {
                searchTimeout = setTimeout(() => {
                    // Implement search suggestions here if needed
                    console.log('Searching for:', query);
                }, 300);
            }
        });
        
        // Handle form submission
        searchForm.addEventListener('submit', function(e) {
            const query = searchInput.value.trim();
            if (!query) {
                e.preventDefault();
                showNotification('Please enter a search term', 'warning');
            }
        });
    }
}

// Theme Functionality
function initTheme() {
    const themeToggle = document.querySelector('.theme-toggle');
    const currentTheme = localStorage.getItem('theme') || 'light';
    
    // Apply saved theme
    document.body.className = document.body.className.replace(/theme-\w+/, '') + ' theme-' + currentTheme;
    
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const currentTheme = document.body.classList.contains('theme-dark') ? 'dark' : 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.body.classList.remove('theme-' + currentTheme);
            document.body.classList.add('theme-' + newTheme);
            
            localStorage.setItem('theme', newTheme);
            
            // Update theme toggle icon
            const icon = this.querySelector('i');
            if (icon) {
                icon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        });
    }
}

// Utility Functions
function updateCartCount() {
    fetch('api/cart_handler.php?action=count')
        .then(response => response.json())
        .then(data => {
            const cartCount = document.querySelector('.action-btn .count');
            if (cartCount && data.count !== undefined) {
                cartCount.textContent = data.count;
                
                // Animate count update
                cartCount.style.transform = 'scale(1.3)';
                setTimeout(() => {
                    cartCount.style.transform = 'scale(1)';
                }, 200);
            }
        })
        .catch(error => console.error('Error updating cart count:', error));
}

function updateWishlistCount() {
    fetch('api/wishlist_handler.php?action=count')
        .then(response => response.json())
        .then(data => {
            const wishlistCount = document.querySelector('.action-btn .count');
            if (wishlistCount && data.count !== undefined) {
                wishlistCount.textContent = data.count;
                
                // Animate count update
                wishlistCount.style.transform = 'scale(1.3)';
                setTimeout(() => {
                    wishlistCount.style.transform = 'scale(1)';
                }, 200);
            }
        })
        .catch(error => console.error('Error updating wishlist count:', error));
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        </div>
    `;
    
    // Add styles if not already present
    if (!document.querySelector('#notification-styles')) {
        const styles = document.createElement('style');
        styles.id = 'notification-styles';
        styles.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                max-width: 400px;
                padding: 1rem;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                animation: slideInRight 0.3s ease;
                transition: all 0.3s ease;
            }
            .notification-info { background: #d1ecf1; color: #0c5460; border-left: 4px solid #17a2b8; }
            .notification-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
            .notification-warning { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
            .notification-error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
            .notification-content {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            .notification-close {
                background: none;
                border: none;
                font-size: 1.2rem;
                cursor: pointer;
                margin-left: auto;
                opacity: 0.7;
            }
            .notification-close:hover { opacity: 1; }
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(styles);
    }
    
    // Add to page
    document.body.appendChild(notification);
    
    // Close button functionality
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    });
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

function getNotificationIcon(type) {
    const icons = {
        info: 'info-circle',
        success: 'check-circle',
        warning: 'exclamation-triangle',
        error: 'exclamation-circle'
    };
    return icons[type] || 'info-circle';
}

// Product Rating Functionality
function initProductRating() {
    const ratingStars = document.querySelectorAll('.rating-stars .star');
    const ratingInput = document.querySelector('#rating');
    
    ratingStars.forEach((star, index) => {
        star.addEventListener('click', function() {
            const rating = index + 1;
            if (ratingInput) ratingInput.value = rating;
            
            ratingStars.forEach((s, i) => {
                s.classList.toggle('active', i < rating);
            });
        });
        
        star.addEventListener('mouseover', function() {
            const rating = index + 1;
            ratingStars.forEach((s, i) => {
                s.classList.toggle('hover', i < rating);
            });
        });
    });
    
    const ratingContainer = document.querySelector('.rating-stars');
    if (ratingContainer) {
        ratingContainer.addEventListener('mouseleave', function() {
            ratingStars.forEach(s => s.classList.remove('hover'));
        });
    }
}

// Quantity Controls
function initQuantityControls() {
    const quantityControls = document.querySelectorAll('.quantity-control');
    
    quantityControls.forEach(control => {
        const minusBtn = control.querySelector('.quantity-minus');
        const plusBtn = control.querySelector('.quantity-plus');
        const input = control.querySelector('.quantity-input');
        
        if (minusBtn && plusBtn && input) {
            minusBtn.addEventListener('click', function() {
                const currentValue = parseInt(input.value) || 1;
                const minValue = parseInt(input.min) || 1;
                if (currentValue > minValue) {
                    input.value = currentValue - 1;
                    input.dispatchEvent(new Event('change'));
                }
            });
            
            plusBtn.addEventListener('click', function() {
                const currentValue = parseInt(input.value) || 1;
                const maxValue = parseInt(input.max) || 999;
                if (currentValue < maxValue) {
                    input.value = currentValue + 1;
                    input.dispatchEvent(new Event('change'));
                }
            });
            
            input.addEventListener('change', function() {
                const value = parseInt(this.value) || 1;
                const minValue = parseInt(this.min) || 1;
                const maxValue = parseInt(this.max) || 999;
                
                this.value = Math.max(minValue, Math.min(maxValue, value));
            });
        }
    });
}

// Image Gallery (for product details)
function initImageGallery() {
    const thumbnails = document.querySelectorAll('.product-thumbnails img');
    const mainImage = document.querySelector('.product-main-image img');
    
    thumbnails.forEach(thumb => {
        thumb.addEventListener('click', function() {
            if (mainImage) {
                mainImage.src = this.src;
                mainImage.alt = this.alt;
            }
            
            thumbnails.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });
}

// Form Validation
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const isValid = validateForm(this);
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        const value = field.value.trim();
        const fieldName = field.name || field.id;
        
        // Remove previous error styling
        field.classList.remove('error');
        
        if (!value) {
            field.classList.add('error');
            showNotification(`${fieldName} is required`, 'error');
            isValid = false;
        } else if (field.type === 'email' && !isValidEmail(value)) {
            field.classList.add('error');
            showNotification('Please enter a valid email address', 'error');
            isValid = false;
        } else if (field.type === 'tel' && !isValidPhone(value)) {
            field.classList.add('error');
            showNotification('Please enter a valid phone number', 'error');
            isValid = false;
        }
    });
    
    return isValid;
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    const phoneRegex = /^[6-9]\d{9}$/;
    return phoneRegex.test(phone);
}

// Lazy Loading for Images
function initLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback for older browsers
        images.forEach(img => {
            img.src = img.dataset.src;
            img.classList.remove('lazy');
        });
    }
}

// Initialize additional components when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initProductRating();
    initQuantityControls();
    initImageGallery();
    initFormValidation();
    initLazyLoading();
});

// Smooth scrolling for anchor links
document.addEventListener('click', function(e) {
    if (e.target.matches('a[href^="#"]')) {
        e.preventDefault();
        const target = document.querySelector(e.target.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    }
});

// Back to top button
function initBackToTop() {
    const backToTopBtn = document.createElement('button');
    backToTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
    backToTopBtn.className = 'back-to-top';
    backToTopBtn.style.cssText = `
        position: fixed;
        bottom: 80px;
        right: 20px;
        width: 50px;
        height: 50px;
        background: #3498db;
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: none;
        z-index: 1000;
        transition: all 0.3s ease;
    `;
    
    document.body.appendChild(backToTopBtn);
    
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopBtn.style.display = 'block';
        } else {
            backToTopBtn.style.display = 'none';
        }
    });
    
    backToTopBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// Initialize back to top button
initBackToTop();