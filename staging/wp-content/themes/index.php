<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php bloginfo('name'); ?> - <?php bloginfo('description'); ?></title>
    <meta name="description" content="TVETA-accredited technical training in Eldoret. Computer Applications, Web Development, Graphic Design, Digital Marketing & more. Affordable fees, modern labs.">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="<?php echo home_url('/'); ?>" class="logo">M.T.T.I</a>
            <button class="mobile-toggle" onclick="toggleMenu()">☰</button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#courses">Courses</a></li>
                <li><a href="#testimonials">Testimonials</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <h1>Masomotele Technical Training Institute</h1>
            <div class="tagline">Start Learning, Start Earning</div>
            <p>Transform your future with hands-on technical training in our modern computer labs. TVETA-accredited courses designed to get you working fast.</p>
            <div class="cta-buttons">
                <a href="#courses" class="btn btn-primary">Explore Courses</a>
                <a href="#contact" class="btn btn-secondary">Enroll Today</a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="stats-container">
            <div class="stat-box fade-in">
                <div class="stat-number">500+</div>
                <div class="stat-label">Students Trained</div>
            </div>
            <div class="stat-box fade-in">
                <div class="stat-number">15+</div>
                <div class="stat-label">Expert Instructors</div>
            </div>
            <div class="stat-box fade-in">
                <div class="stat-number">10+</div>
                <div class="stat-label">Technical Courses</div>
            </div>
            <div class="stat-box fade-in">
                <div class="stat-number">85%</div>
                <div class="stat-label">Employment Rate</div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about" id="about">
        <div class="about-container">
            <h2 class="section-title">Why Choose M.T.T.I?</h2>
            <p class="section-subtitle">We're committed to providing quality technical education that transforms lives and creates opportunities</p>
            
            <div class="features-grid">
                <div class="feature-card fade-in">
                    <div class="feature-icon">🖥️</div>
                    <h3>Modern Computer Labs</h3>
                    <p>Train on industry-standard equipment in our fully-equipped facilities</p>
                </div>
                
                <div class="feature-card fade-in">
                    <div class="feature-icon">⚡</div>
                    <h3>Fast Track Learning</h3>
                    <p>Complete courses in 1-2 months and start earning immediately</p>
                </div>
                
                <div class="feature-card fade-in">
                    <div class="feature-icon">🏅</div>
                    <h3>TVETA Accredited</h3>
                    <p>Government-recognized certificates trusted by employers nationwide</p>
                </div>
                
                <div class="feature-card fade-in">
                    <div class="feature-icon">💰</div>
                    <h3>Affordable Fees</h3>
                    <p>Quality education at prices everyone can afford with flexible payment plans</p>
                </div>
                
                <div class="feature-card fade-in">
                    <div class="feature-icon">👨‍🏫</div>
                    <h3>Expert Instructors</h3>
                    <p>Learn from experienced professionals with real industry knowledge</p>
                </div>
                
                <div class="feature-card fade-in">
                    <div class="feature-icon">🎯</div>
                    <h3>Hands-On Training</h3>
                    <p>Practical projects and real-world applications, not just theory</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Courses Section -->
    <section class="courses" id="courses">
        <div class="courses-container">
            <h2 class="section-title">Our Popular Courses</h2>
            <p class="section-subtitle">Choose from our range of practical, market-driven technical courses</p>
            
            <div class="courses-grid">
                <!-- Computer Applications -->
                <div class="course-card fade-in">
                    <div class="course-header">
                        <h3>Computer Applications</h3>
                        <div class="course-price">KES 8,000</div>
                        <div class="course-duration">Duration: 1-2 months</div>
                    </div>
                    <div class="course-body">
                        <ul class="course-features">
                            <li>MS Office Suite</li>
                            <li>Internet & Email</li>
                            <li>Typing Skills</li>
                            <li>File Management</li>
                        </ul>
                        <a href="#contact" class="btn btn-primary course-btn">Enroll Now</a>
                    </div>
                </div>

                <!-- Web Development -->
                <div class="course-card fade-in">
                    <div class="course-header">
                        <h3>Web Design & Development</h3>
                        <div class="course-price">KES 15,000</div>
                        <div class="course-duration">Duration: 2 months</div>
                    </div>
                    <div class="course-body">
                        <ul class="course-features">
                            <li>HTML, CSS, JavaScript</li>
                            <li>Responsive Design</li>
                            <li>WordPress Development</li>
                            <li>Portfolio Projects</li>
                        </ul>
                        <a href="#contact" class="btn btn-primary course-btn">Enroll Now</a>
                    </div>
                </div>

                <!-- Graphic Design -->
                <div class="course-card fade-in">
                    <div class="course-header">
                        <h3>Graphic Design</h3>
                        <div class="course-price">KES 12,000</div>
                        <div class="course-duration">Duration: 2 months</div>
                    </div>
                    <div class="course-body">
                        <ul class="course-features">
                            <li>Adobe Photoshop</li>
                            <li>Adobe Illustrator</li>
                            <li>Branding & Identity</li>
                            <li>Portfolio Building</li>
                        </ul>
                        <a href="#contact" class="btn btn-primary course-btn">Enroll Now</a>
                    </div>
                </div>

                <!-- Digital Marketing -->
                <div class="course-card fade-in">
                    <div class="course-header">
                        <h3>Digital Marketing</h3>
                        <div class="course-price">KES 10,000</div>
                        <div class="course-duration">Duration: 1-2 months</div>
                    </div>
                    <div class="course-body">
                        <ul class="course-features">
                            <li>Social Media Strategy</li>
                            <li>Content Creation</li>
                            <li>Facebook & Instagram Ads</li>
                            <li>Analytics & ROI</li>
                        </ul>
                        <a href="#contact" class="btn btn-primary course-btn">Enroll Now</a>
                    </div>
                </div>

                <!-- CCTV Installation -->
                <div class="course-card fade-in">
                    <div class="course-header">
                        <h3>CCTV Installation</h3>
                        <div class="course-price">KES 18,000</div>
                        <div class="course-duration">Duration: 2 months</div>
                    </div>
                    <div class="course-body">
                        <ul class="course-features">
                            <li>Camera Types & Setup</li>
                            <li>Network Configuration</li>
                            <li>DVR/NVR Systems</li>
                            <li>Practical Installation</li>
                        </ul>
                        <a href="#contact" class="btn btn-primary course-btn">Enroll Now</a>
                    </div>
                </div>

                <!-- Computer Repair -->
                <div class="course-card fade-in">
                    <div class="course-header">
                        <h3>Computer & Mobile Repair</h3>
                        <div class="course-price">KES 12,000</div>
                        <div class="course-duration">Duration: 2 months</div>
                    </div>
                    <div class="course-body">
                        <ul class="course-features">
                            <li>Hardware Diagnostics</li>
                            <li>Component Replacement</li>
                            <li>Software Troubleshooting</li>
                            <li>Mobile Phone Repair</li>
                        </ul>
                        <a href="#contact" class="btn btn-primary course-btn">Enroll Now</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials" id="testimonials">
        <div class="testimonials-container">
            <h2 class="section-title">What Our Students Say</h2>
            <p class="section-subtitle">Real success stories from our graduates</p>
            
            <div class="testimonials-grid">
                <div class="testimonial-card fade-in">
                    <div class="testimonial-text">
                        "The web development course changed my life. Within 3 months of completing, I got my first freelance client. The instructors were patient and the curriculum was practical."
                    </div>
                    <div class="testimonial-author">Sarah Wanjiku</div>
                    <div class="testimonial-course">Web Design & Development Graduate</div>
                </div>
                
                <div class="testimonial-card fade-in">
                    <div class="testimonial-text">
                        "I came in knowing nothing about computers. The Computer Applications course started from the basics and now I'm confidently working as a data entry clerk. Thank you M.T.T.I!"
                    </div>
                    <div class="testimonial-author">John Kamau</div>
                    <div class="testimonial-course">Computer Applications Graduate</div>
                </div>
                
                <div class="testimonial-card fade-in">
                    <div class="testimonial-text">
                        "The CCTV installation training was hands-on and comprehensive. I now run my own security systems installation business. Best investment I ever made!"
                    </div>
                    <div class="testimonial-author">David Omondi</div>
                    <div class="testimonial-course">CCTV Installation Graduate</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <h2>Ready to Transform Your Career?</h2>
        <p>Join hundreds of successful graduates who changed their lives with practical technical skills</p>
        <a href="#contact" class="btn">Get Started Today</a>
    </section>

    <!-- Contact Section -->
    <section class="contact" id="contact">
        <div class="contact-container">
            <h2 class="section-title">Get In Touch</h2>
            <p class="section-subtitle">Visit us or reach out - we're here to help you start your journey</p>
            
            <div class="contact-grid">
                <div class="contact-card fade-in">
                    <div class="contact-icon">📍</div>
                    <h3>Location</h3>
                    <p>Sagaas Center, Fourth Floor<br>Eldoret, Kenya</p>
                </div>
                
                <div class="contact-card fade-in">
                    <div class="contact-icon">📞</div>
                    <h3>Phone</h3>
                    <p><a href="tel:+254712464936">+254 712 464 936</a></p>
                </div>
                
                <div class="contact-card fade-in">
                    <div class="contact-icon">📧</div>
                    <h3>Email</h3>
                    <p><a href="mailto:info@masomotele.ac.ke">info@masomotele.ac.ke</a></p>
                </div>
                
                <div class="contact-card fade-in">
                    <div class="contact-icon">🕒</div>
                    <h3>Hours</h3>
                    <p>Monday - Friday: 8:00 AM - 6:00 PM<br>Saturday: 9:00 AM - 3:00 PM</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-section">
                    <h3>About M.T.T.I</h3>
                    <p>Masomotele Technical Training Institute is a TVETA-accredited institution dedicated to providing quality technical education in Eldoret, Kenya.</p>
                    <div class="social-links">
                        <a href="#" title="Facebook">📘</a>
                        <a href="#" title="Instagram">📷</a>
                        <a href="#" title="Twitter">🐦</a>
                        <a href="#" title="LinkedIn">💼</a>
                        <a href="https://wa.me/254712464936" title="WhatsApp">💬</a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="#about">About Us</a></li>
                        <li><a href="#courses">Our Courses</a></li>
                        <li><a href="#testimonials">Testimonials</a></li>
                        <li><a href="#contact">Contact Us</a></li>
                        <li><a href="#">Verify Certificate</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Popular Courses</h3>
                    <ul>
                        <li><a href="#courses">Computer Applications</a></li>
                        <li><a href="#courses">Web Development</a></li>
                        <li><a href="#courses">Graphic Design</a></li>
                        <li><a href="#courses">Digital Marketing</a></li>
                        <li><a href="#courses">CCTV Installation</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <p>📍 Sagaas Center, Fourth Floor<br>Eldoret, Kenya</p>
                    <p>📞 +254 712 464 936</p>
                    <p>📧 info@masomotele.ac.ke</p>
                    <p style="margin-top: 20px; color: #FF9800; font-weight: bold;">"Start Learning, Start Earning"</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Masomotele Technical Training Institute. All rights reserved. | TVETA Accredited</p>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button class="scroll-top" id="scrollTop" onclick="scrollToTop()">↑</button>

    <script>
        // Mobile menu toggle
        function toggleMenu() {
            const navMenu = document.getElementById('navMenu');
            navMenu.classList.toggle('active');
        }

        // Close mobile menu when clicking a link
        document.querySelectorAll('.nav-menu a').forEach(link => {
            link.addEventListener('click', () => {
                document.getElementById('navMenu').classList.remove('active');
            });
        });

        // Smooth scrolling
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

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            const scrollTop = document.getElementById('scrollTop');
            
            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
                scrollTop.classList.add('visible');
            } else {
                navbar.classList.remove('scrolled');
                scrollTop.classList.remove('visible');
            }
        });

        // Scroll to top function
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Intersection Observer for fade-in animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        // Observe all fade-in elements
        document.querySelectorAll('.fade-in').forEach(el => {
            observer.observe(el);
        });

        // Parallax effect for hero
        window.addEventListener('scroll', function() {
            const hero = document.querySelector('.hero');
            const scrolled = window.pageYOffset;
            hero.style.backgroundPositionY = scrolled * 0.5 + 'px';
        });
    </script>

<?php wp_footer(); ?>
</body>
</html>
