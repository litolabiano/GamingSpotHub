// ============================================
// GSPOT GAMING HUB - MAIN JAVASCRIPT
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize AOS (Animate On Scroll)
    AOS.init({
        duration: 1000,
        once: true,
        offset: 100
    });
    
    // Navbar Scroll Effect
    const navbar = document.getElementById('mainNav');
    const navbarHeight = navbar.offsetHeight;
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 100) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
    
    // Smooth Scrolling for Anchor Links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            
            if (target) {
                const offsetTop = target.offsetTop - navbarHeight;
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
                
                // Close mobile menu if open
                const navbarCollapse = document.querySelector('.navbar-collapse');
                if (navbarCollapse.classList.contains('show')) {
                    navbarCollapse.classList.remove('show');
                }
            }
        });
    });
    
    // Active Navigation Link
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    
    window.addEventListener('scroll', function() {
        let current = '';
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            
            if (window.scrollY >= (sectionTop - navbarHeight - 200)) {
                current = section.getAttribute('id');
            }
        });
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${current}`) {
                link.classList.add('active');
            }
        });
    });
    
    // Back to Top Button
    const backToTop = document.getElementById('backToTop');
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) {
            backToTop.classList.add('show');
        } else {
            backToTop.classList.remove('show');
        }
    });
    
    // Particles.js Configuration
    if (typeof particlesJS !== 'undefined') {
        particlesJS('particles-js', {
            particles: {
                number: {
                    value: 80,
                    density: {
                        enable: true,
                        value_area: 800
                    }
                },
                color: {
                    value: ['#4ECDC4', '#B67EC9', '#FF6B6B', '#5DADE2']
                },
                shape: {
                    type: 'circle'
                },
                opacity: {
                    value: 0.5,
                    random: true,
                    anim: {
                        enable: true,
                        speed: 1,
                        opacity_min: 0.1,
                        sync: false
                    }
                },
                size: {
                    value: 3,
                    random: true,
                    anim: {
                        enable: true,
                        speed: 2,
                        size_min: 0.1,
                        sync: false
                    }
                },
                line_linked: {
                    enable: true,
                    distance: 150,
                    color: '#4ECDC4',
                    opacity: 0.2,
                    width: 1
                },
                move: {
                    enable: true,
                    speed: 2,
                    direction: 'none',
                    random: false,
                    straight: false,
                    out_mode: 'out',
                    bounce: false
                }
            },
            interactivity: {
                detect_on: 'canvas',
                events: {
                    onhover: {
                        enable: true,
                        mode: 'repulse'
                    },
                    onclick: {
                        enable: true,
                        mode: 'push'
                    },
                    resize: true
                },
                modes: {
                    repulse: {
                        distance: 100,
                        duration: 0.4
                    },
                    push: {
                        particles_nb: 4
                    }
                }
            },
            retina_detect: true
        });
    }
    
    // Contact Form Submission
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(this);
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const phone = document.getElementById('phone').value;
            const message = document.getElementById('message').value;
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Sending...';
            submitBtn.disabled = true;
            
            // Simulate form submission (replace with actual AJAX call)
            setTimeout(() => {
                // Reset form
                contactForm.reset();
                
                // Show success message
                submitBtn.innerHTML = '<i class="fas fa-check me-2"></i> Message Sent!';
                submitBtn.classList.add('btn-success');
                
                // Reset button after 3 seconds
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('btn-success');
                }, 3000);
                
                // You can add actual AJAX call here
                console.log('Form submitted:', { name, email, phone, message });
            }, 2000);
        });
    }
    
    // Animated Counter for Stats
    const animateCounter = (element, target, duration = 2000) => {
        let start = 0;
        const increment = target / (duration / 16);
        
        const timer = setInterval(() => {
            start += increment;
            if (start >= target) {
                element.textContent = target;
                clearInterval(timer);
            } else {
                element.textContent = Math.floor(start);
            }
        }, 16);
    };
    
    // Trigger counter animation when stats section is visible
    const statsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const statNumbers = document.querySelectorAll('.stat-number');
                statNumbers.forEach(stat => {
                    const target = stat.textContent;
                    if (target.includes('K')) {
                        const value = parseFloat(target) * 1000;
                        animateCounter(stat, value, 2000);
                        stat.dataset.suffix = 'K';
                    } else if (target.includes('+')) {
                        const value = parseInt(target);
                        animateCounter(stat, value, 2000);
                        stat.dataset.suffix = '+';
                    } else if (target.includes('/')) {
                        // Don't animate 24/7
                        return;
                    }
                });
                statsObserver.disconnect();
            }
        });
    }, { threshold: 0.5 });
    
    const heroStats = document.querySelector('.hero-stats');
    if (heroStats) {
        statsObserver.observe(heroStats);
    }
    
    // Gallery Lightbox Effect
    const galleryItems = document.querySelectorAll('.gallery-item');
    galleryItems.forEach(item => {
        item.addEventListener('click', function() {
            const img = this.querySelector('img');
            if (img) {
                // Create lightbox (you can use a library like GLightbox or Fancybox)
                console.log('Gallery item clicked:', img.src);
                // Add your lightbox code here
            }
        });
    });
    
    // Preloader (optional)
    window.addEventListener('load', function() {
        document.body.classList.add('loaded');
    });
    
    // Mobile Menu Close on Click Outside
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    document.addEventListener('click', function(event) {
        const isClickInsideNav = navbar.contains(event.target);
        
        if (!isClickInsideNav && navbarCollapse.classList.contains('show')) {
            navbarToggler.click();
        }
    });
    
    // Hover Effects for Service Cards
    const serviceCards = document.querySelectorAll('.service-card');
    serviceCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    console.log('Gspot Gaming Hub - Website loaded successfully! 🎮');
});

// Update counter with suffix
setInterval(() => {
    document.querySelectorAll('.stat-number').forEach(stat => {
        if (stat.dataset.suffix && !stat.textContent.includes(stat.dataset.suffix)) {
            const currentValue = stat.textContent;
            if (stat.dataset.suffix === 'K') {
                stat.textContent = (parseFloat(currentValue) / 1000).toFixed(1) + 'K';
            } else {
                stat.textContent = currentValue + stat.dataset.suffix;
            }
        }
    });
}, 100);
