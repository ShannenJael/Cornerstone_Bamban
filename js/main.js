// Cornerstone Baptist Church - Bamban
// Main JavaScript File

// ====================================
// Mobile Menu Toggle
// ====================================
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            
            // Animate hamburger menu
            this.classList.toggle('active');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const isClickInsideNav = navMenu.contains(event.target);
            const isClickOnToggle = mobileMenuToggle.contains(event.target);
            
            if (!isClickInsideNav && !isClickOnToggle && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
                mobileMenuToggle.classList.remove('active');
            }
        });
        
        // Close menu when clicking on a link
        const navLinks = navMenu.querySelectorAll('a:not(.dropdown > a)');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                navMenu.classList.remove('active');
                mobileMenuToggle.classList.remove('active');
            });
        });
    }
});

// ====================================
// Sticky Navigation
// ====================================
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    
    if (window.scrollY > 100) {
        navbar.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    } else {
        navbar.style.boxShadow = '0 4px 8px rgba(0,0,0,0.15)';
    }
});

// ====================================
// Smooth Scrolling
// ====================================
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// ====================================
// Hero Scroll Button
// ====================================
const heroScroll = document.querySelector('.hero-scroll');
if (heroScroll) {
    heroScroll.addEventListener('click', function() {
        const nextSection = document.querySelector('.service-times');
        if (nextSection) {
            nextSection.scrollIntoView({ behavior: 'smooth' });
        }
    });
}

// ====================================
// Animation on Scroll
// ====================================
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate-in');
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

// Observe elements for animation
document.querySelectorAll('.service-times-card, .info-card, .event-card, .ministry-card').forEach(el => {
    observer.observe(el);
});

// ====================================
// Form Validation (for contact forms)
// ====================================
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            showError(field, 'This field is required');
        } else {
            clearError(field);
        }
        
        // Email validation
        if (field.type === 'email' && field.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(field.value)) {
                isValid = false;
                showError(field, 'Please enter a valid email address');
            }
        }
        
        // Phone validation
        if (field.type === 'tel' && field.value) {
            const phoneRegex = /^\(?([0-9]{3})\)?[-. ]?([0-9]{3})[-. ]?([0-9]{4})$/;
            if (!phoneRegex.test(field.value)) {
                isValid = false;
                showError(field, 'Please enter a valid phone number');
            }
        }
    });
    
    return isValid;
}

function showError(field, message) {
    // Remove existing error
    clearError(field);
    
    // Add error styling
    field.classList.add('error');
    
    // Create error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    field.parentNode.insertBefore(errorDiv, field.nextSibling);
}

function clearError(field) {
    field.classList.remove('error');
    const errorMessage = field.parentNode.querySelector('.error-message');
    if (errorMessage) {
        errorMessage.remove();
    }
}

// ====================================
// Newsletter Signup
// ====================================
const newsletterForm = document.querySelector('#newsletter-form');
if (newsletterForm) {
    newsletterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateForm(this)) {
            const formData = new FormData(this);
            
            // Here you would typically send the data to your server
            // For now, we'll just show a success message
            
            showSuccessMessage('Thank you for subscribing to our newsletter!');
            this.reset();
        }
    });
}

function showSuccessMessage(message) {
    const successDiv = document.createElement('div');
    successDiv.className = 'success-message';
    successDiv.textContent = message;
    successDiv.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background-color: #28a745;
        color: white;
        padding: 15px 25px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        z-index: 9999;
        animation: slideIn 0.3s ease-out;
    `;
    
    document.body.appendChild(successDiv);
    
    setTimeout(() => {
        successDiv.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => successDiv.remove(), 300);
    }, 3000);
}

// ====================================
// Current Year in Footer
// ====================================
const currentYearElements = document.querySelectorAll('.current-year');
currentYearElements.forEach(el => {
    el.textContent = new Date().getFullYear();
});

// ====================================
// Loading Animation
// ====================================
window.addEventListener('load', function() {
    document.body.classList.add('loaded');
});

// ====================================
// Lazy Loading Images
// ====================================
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
    
    const lazyImages = document.querySelectorAll('img.lazy');
    lazyImages.forEach(img => imageObserver.observe(img));
}

// ====================================
// Dropdown Menu for Mobile
// ====================================
const dropdownToggles = document.querySelectorAll('.dropdown > a');
dropdownToggles.forEach(toggle => {
    toggle.addEventListener('click', function(e) {
        // Only prevent default on mobile
        if (window.innerWidth <= 768) {
            e.preventDefault();
            e.stopPropagation(); // Prevent click from bubbling to parent dropdowns

            const dropdown = this.parentElement;
            const isSubDropdown = dropdown.classList.contains('sub-dropdown');
            const isOpen = dropdown.classList.contains('active');
            const parentMenu = this.closest('ul');

            // For sub-dropdowns, just toggle without closing siblings
            if (isSubDropdown) {
                dropdown.classList.toggle('active');
                this.setAttribute('aria-expanded', !isOpen);
                return;
            }

            // Close sibling dropdowns within the same menu level
            if (parentMenu) {
                const siblingToggles = parentMenu.querySelectorAll(':scope > .dropdown > a');
                siblingToggles.forEach(otherToggle => {
                    if (otherToggle !== this) {
                        otherToggle.parentElement.classList.remove('active');
                        otherToggle.setAttribute('aria-expanded', 'false');
                        // Also close any sub-dropdowns inside
                        const subDropdowns = otherToggle.parentElement.querySelectorAll('.sub-dropdown');
                        subDropdowns.forEach(sub => sub.classList.remove('active'));
                    }
                });
            }

            if (isOpen) {
                dropdown.classList.remove('active');
                this.setAttribute('aria-expanded', 'false');
                // Close any sub-dropdowns inside
                const subDropdowns = dropdown.querySelectorAll('.sub-dropdown');
                subDropdowns.forEach(sub => sub.classList.remove('active'));
            } else {
                dropdown.classList.add('active');
                this.setAttribute('aria-expanded', 'true');
            }
        }
    });
});

// ====================================
// Video Player Modal (if applicable)
// ====================================
const videoTriggers = document.querySelectorAll('[data-video]');
videoTriggers.forEach(trigger => {
    trigger.addEventListener('click', function(e) {
        e.preventDefault();
        const videoUrl = this.dataset.video;
        openVideoModal(videoUrl);
    });
});

function openVideoModal(videoUrl) {
    const modal = document.createElement('div');
    modal.className = 'video-modal';
    modal.innerHTML = `
        <div class="video-modal-overlay"></div>
        <div class="video-modal-content">
            <button class="video-modal-close">&times;</button>
            <iframe src="${videoUrl}" frameborder="0" allowfullscreen></iframe>
        </div>
    `;
    
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
    
    // Close modal
    const closeBtn = modal.querySelector('.video-modal-close');
    const overlay = modal.querySelector('.video-modal-overlay');
    
    [closeBtn, overlay].forEach(el => {
        el.addEventListener('click', () => {
            modal.remove();
            document.body.style.overflow = '';
        });
    });
    
    // Close on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.querySelector('.video-modal')) {
            document.querySelector('.video-modal').remove();
            document.body.style.overflow = '';
        }
    });
}

// ====================================
// Print Bulletin Function
// ====================================
function printBulletin() {
    window.print();
}

// ====================================
// Share Functions
// ====================================
function shareOnFacebook(url) {
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, '_blank');
}

function shareOnTwitter(url, text) {
    window.open(`https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(text)}`, '_blank');
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showSuccessMessage('Link copied to clipboard!');
    });
}

// ====================================
// Mini Calendar
// ====================================
document.addEventListener('DOMContentLoaded', function() {
    const calendarDays = document.getElementById('calendarDays');
    const currentMonthEl = document.getElementById('currentMonth');
    const prevMonthBtn = document.getElementById('prevMonth');
    const nextMonthBtn = document.getElementById('nextMonth');

    if (!calendarDays) return;

    let currentDate = new Date();
    let currentMonth = currentDate.getMonth();
    let currentYear = currentDate.getFullYear();

    // Events with dates (month is 0-indexed)
    const events = [
        { day: 15, month: 0, year: 2026 }, // Revival Services
        { day: 16, month: 0, year: 2026 },
        { day: 17, month: 0, year: 2026 },
        { day: 19, month: 0, year: 2026 }, // Business Meeting
        { day: 22, month: 1, year: 2026 }, // Youth Conference
        { day: 23, month: 1, year: 2026 },
        { day: 24, month: 1, year: 2026 },
        { day: 15, month: 2, year: 2026 }  // Ladies Conference
    ];

    function hasEvent(day, month, year) {
        return events.some(e => e.day === day && e.month === month && e.year === year);
    }

    function renderCalendar() {
        const months = ['January', 'February', 'March', 'April', 'May', 'June',
                        'July', 'August', 'September', 'October', 'November', 'December'];

        currentMonthEl.textContent = `${months[currentMonth]} ${currentYear}`;

        // Get first day of month and total days
        const firstDay = new Date(currentYear, currentMonth, 1).getDay();
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
        const daysInPrevMonth = new Date(currentYear, currentMonth, 0).getDate();

        let html = '';

        // Previous month days
        for (let i = firstDay - 1; i >= 0; i--) {
            html += `<div class="calendar-day other-month">${daysInPrevMonth - i}</div>`;
        }

        // Current month days
        const today = new Date();
        for (let day = 1; day <= daysInMonth; day++) {
            let classes = 'calendar-day';

            if (day === today.getDate() && currentMonth === today.getMonth() && currentYear === today.getFullYear()) {
                classes += ' today';
            }

            if (hasEvent(day, currentMonth, currentYear)) {
                classes += ' has-event';
            }

            html += `<div class="${classes}">${day}</div>`;
        }

        // Next month days
        const totalCells = firstDay + daysInMonth;
        const remainingCells = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);
        for (let i = 1; i <= remainingCells; i++) {
            html += `<div class="calendar-day other-month">${i}</div>`;
        }

        calendarDays.innerHTML = html;
    }

    if (prevMonthBtn) {
        prevMonthBtn.addEventListener('click', function() {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            renderCalendar();
        });
    }

    if (nextMonthBtn) {
        nextMonthBtn.addEventListener('click', function() {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            renderCalendar();
        });
    }

    renderCalendar();
});

// ====================================
// Console Message
// ====================================
console.log('%cCornerstone Baptist Church', 'font-size: 20px; font-weight: bold; color: #003366;');
console.log('%cSitio Magurul-gurol, Anupul, Bamban, Tarlac', 'font-size: 14px; color: #c8102e;');
console.log('Website built with care for God\'s glory');




