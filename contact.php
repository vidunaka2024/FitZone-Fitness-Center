<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - FitZone Fitness Center</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body>
    <?php include 'php/includes/header.php'; ?>
    
    <main>
        <section class="page-header">
            <div class="container">
                <h1>Contact Us</h1>
                <p>Get in touch with our team - we're here to help with all your fitness needs</p>
            </div>
        </section>

        <section class="contact-content">
            <div class="container">
                <div class="contact-layout">
                    <div class="contact-info">
                        <div class="info-card">
                            <h3>Visit Our Gym</h3>
                            <div class="info-item">
                                <strong>Address:</strong>
                                <p>123 Fitness Street<br>
                                Downtown District<br>
                                Cityville, ST 12345</p>
                            </div>
                            <div class="info-item">
                                <strong>Phone:</strong>
                                <p>(555) 123-FITZ<br>(555) 123-3489</p>
                            </div>
                            <div class="info-item">
                                <strong>Email:</strong>
                                <p>info@fitzonecenter.com<br>membership@fitzonecenter.com</p>
                            </div>
                        </div>

                        <div class="info-card">
                            <h3>Operating Hours</h3>
                            <div class="hours-grid">
                                <div class="hours-item">
                                    <strong>Monday - Friday:</strong>
                                    <span>5:00 AM - 11:00 PM</span>
                                </div>
                                <div class="hours-item">
                                    <strong>Saturday:</strong>
                                    <span>6:00 AM - 10:00 PM</span>
                                </div>
                                <div class="hours-item">
                                    <strong>Sunday:</strong>
                                    <span>7:00 AM - 9:00 PM</span>
                                </div>
                                <div class="hours-item">
                                    <strong>Holidays:</strong>
                                    <span>8:00 AM - 6:00 PM</span>
                                </div>
                            </div>
                            <p class="hours-note">*Premium and Elite members enjoy 24/7 access</p>
                        </div>

                        <div class="info-card">
                            <h3>Department Contacts</h3>
                            <div class="department-list">
                                <div class="department">
                                    <strong>Membership Services:</strong>
                                    <span>(555) 123-3489 ext. 1</span>
                                </div>
                                <div class="department">
                                    <strong>Personal Training:</strong>
                                    <span>(555) 123-3489 ext. 2</span>
                                </div>
                                <div class="department">
                                    <strong>Group Classes:</strong>
                                    <span>(555) 123-3489 ext. 3</span>
                                </div>
                                <div class="department">
                                    <strong>General Inquiries:</strong>
                                    <span>(555) 123-3489 ext. 0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="contact-form-section">
                        <h2>Send Us a Message</h2>
                        <form class="contact-form" id="contact-form" action="php/ajax/contact-form.php" method="POST">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first-name">First Name *</label>
                                    <input type="text" id="first-name" name="first_name" required>
                                    <span class="error-message" id="first-name-error"></span>
                                </div>
                                <div class="form-group">
                                    <label for="last-name">Last Name *</label>
                                    <input type="text" id="last-name" name="last_name" required>
                                    <span class="error-message" id="last-name-error"></span>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email">Email Address *</label>
                                    <input type="email" id="email" name="email" required>
                                    <span class="error-message" id="email-error"></span>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="tel" id="phone" name="phone">
                                    <span class="error-message" id="phone-error"></span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="subject">Subject *</label>
                                <select id="subject" name="subject" required>
                                    <option value="">Select a subject</option>
                                    <option value="membership">Membership Inquiry</option>
                                    <option value="personal-training">Personal Training</option>
                                    <option value="classes">Group Classes</option>
                                    <option value="facilities">Facilities Question</option>
                                    <option value="complaint">Complaint</option>
                                    <option value="compliment">Compliment</option>
                                    <option value="other">Other</option>
                                </select>
                                <span class="error-message" id="subject-error"></span>
                            </div>

                            <div class="form-group">
                                <label for="message">Message *</label>
                                <textarea id="message" name="message" rows="6" placeholder="Please provide details about your inquiry..." required></textarea>
                                <span class="error-message" id="message-error"></span>
                            </div>

                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="newsletter" name="newsletter" value="1">
                                    <span class="checkmark"></span>
                                    Subscribe to our newsletter for fitness tips and updates
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="terms" name="terms" required>
                                    <span class="checkmark"></span>
                                    I agree to the <a href="#" target="_blank">Terms of Service</a> and <a href="#" target="_blank">Privacy Policy</a> *
                                </label>
                                <span class="error-message" id="terms-error"></span>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary" id="submit-btn">
                                    <span class="btn-text">Send Message</span>
                                    <span class="btn-loading" style="display: none;">Sending...</span>
                                </button>
                                <button type="reset" class="btn btn-secondary">Clear Form</button>
                            </div>
                        </form>

                        <div class="form-result" id="form-result" style="display: none;">
                            <div class="success-message" id="success-message">
                                <h3>Thank You!</h3>
                                <p>Your message has been sent successfully. We'll get back to you within 24 hours.</p>
                            </div>
                            <div class="error-message" id="error-message">
                                <h3>Oops!</h3>
                                <p>There was an error sending your message. Please try again or contact us directly.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="map-section">
            <div class="container">
                <h2>Find Us</h2>
                <div class="map-container">
                    <div class="map-placeholder">
                        <p>Interactive Map</p>
                        <small>123 Fitness Street, Downtown District, Cityville, ST 12345</small>
                    </div>
                    <div class="map-info">
                        <h3>Getting Here</h3>
                        <div class="directions">
                            <div class="direction-item">
                                <strong>By Car:</strong>
                                <p>Free parking available. Enter from Fitness Street or use our underground garage on Wellness Avenue.</p>
                            </div>
                            <div class="direction-item">
                                <strong>Public Transit:</strong>
                                <p>Bus routes 15, 22, and 45 stop directly in front. Metro station "Downtown Fitness" is 2 blocks away.</p>
                            </div>
                            <div class="direction-item">
                                <strong>Walking/Biking:</strong>
                                <p>Bike racks available at main entrance. Walking paths from Central Park and Business District.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="faq-section">
            <div class="container">
                <h2>Frequently Asked Questions</h2>
                <div class="faq-grid">
                    <div class="faq-item">
                        <h3>What should I bring for my first visit?</h3>
                        <p>Bring a valid ID, comfortable workout clothes, athletic shoes, a water bottle, and a towel. We provide basic amenities but you're welcome to bring your own.</p>
                    </div>
                    <div class="faq-item">
                        <h3>Do you offer trial memberships?</h3>
                        <p>Yes! New members can try FitZone for one week completely free. This includes access to all basic facilities and one group class.</p>
                    </div>
                    <div class="faq-item">
                        <h3>Can I freeze my membership?</h3>
                        <p>Yes, you can freeze your membership for up to 3 months per year for medical reasons, travel, or other circumstances. A small administrative fee applies.</p>
                    </div>
                    <div class="faq-item">
                        <h3>Do you have childcare services?</h3>
                        <p>We offer supervised childcare for children ages 6 months to 12 years during peak hours. This service is included with Premium and Elite memberships.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'php/includes/footer.php'; ?>
    <script src="js/main.js"></script>
    <script src="js/form-validation.js"></script>
</body>
</html>