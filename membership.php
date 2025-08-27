<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Plans - FitZone Fitness Center</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body>
    <?php include 'php/includes/header.php'; ?>
    
    <main>
        <section class="page-header">
            <div class="container">
                <h1>Membership Plans</h1>
                <p>Choose the perfect plan that fits your lifestyle and fitness goals</p>
            </div>
        </section>

        <section class="membership-plans">
            <div class="container">
                <div class="plans-grid">
                    <div class="plan-card basic">
                        <div class="plan-header">
                            <h3>Basic</h3>
                            <div class="price">
                                <span class="currency">$</span>
                                <span class="amount">29</span>
                                <span class="period">/month</span>
                            </div>
                        </div>
                        <div class="plan-features">
                            <ul>
                                <li>Gym access during off-peak hours (9 AM - 5 PM)</li>
                                <li>Use of cardio equipment</li>
                                <li>Use of strength training equipment</li>
                                <li>Locker room access</li>
                                <li>Basic fitness assessment</li>
                                <li>Mobile app access</li>
                            </ul>
                        </div>
                        <div class="plan-footer">
                            <button class="btn btn-secondary">Choose Basic</button>
                        </div>
                    </div>

                    <div class="plan-card premium featured">
                        <div class="plan-badge">Most Popular</div>
                        <div class="plan-header">
                            <h3>Premium</h3>
                            <div class="price">
                                <span class="currency">$</span>
                                <span class="amount">59</span>
                                <span class="period">/month</span>
                            </div>
                        </div>
                        <div class="plan-features">
                            <ul>
                                <li>24/7 gym access</li>
                                <li>All equipment and facilities</li>
                                <li>Unlimited group fitness classes</li>
                                <li>Swimming pool access</li>
                                <li>Sauna and steam room</li>
                                <li>2 guest passes per month</li>
                                <li>Nutritional consultation</li>
                                <li>Priority booking for classes</li>
                            </ul>
                        </div>
                        <div class="plan-footer">
                            <button class="btn btn-primary">Choose Premium</button>
                        </div>
                    </div>

                    <div class="plan-card elite">
                        <div class="plan-header">
                            <h3>Elite</h3>
                            <div class="price">
                                <span class="currency">$</span>
                                <span class="amount">99</span>
                                <span class="period">/month</span>
                            </div>
                        </div>
                        <div class="plan-features">
                            <ul>
                                <li>All Premium features included</li>
                                <li>4 personal training sessions per month</li>
                                <li>Unlimited guest passes</li>
                                <li>Towel service</li>
                                <li>Massage therapy (2 sessions/month)</li>
                                <li>Private locker</li>
                                <li>VIP parking</li>
                                <li>Complimentary supplements</li>
                                <li>Body composition analysis</li>
                            </ul>
                        </div>
                        <div class="plan-footer">
                            <button class="btn btn-secondary">Choose Elite</button>
                        </div>
                    </div>
                </div>

                <div class="plan-comparison">
                    <h2>Compare Plans</h2>
                    <div class="comparison-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Features</th>
                                    <th>Basic</th>
                                    <th>Premium</th>
                                    <th>Elite</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Gym Access</td>
                                    <td>Off-peak hours</td>
                                    <td>24/7</td>
                                    <td>24/7</td>
                                </tr>
                                <tr>
                                    <td>Group Classes</td>
                                    <td>Limited</td>
                                    <td>Unlimited</td>
                                    <td>Unlimited</td>
                                </tr>
                                <tr>
                                    <td>Swimming Pool</td>
                                    <td>❌</td>
                                    <td>✅</td>
                                    <td>✅</td>
                                </tr>
                                <tr>
                                    <td>Personal Training</td>
                                    <td>Additional cost</td>
                                    <td>Additional cost</td>
                                    <td>4 sessions/month</td>
                                </tr>
                                <tr>
                                    <td>Guest Passes</td>
                                    <td>❌</td>
                                    <td>2/month</td>
                                    <td>Unlimited</td>
                                </tr>
                                <tr>
                                    <td>Massage Therapy</td>
                                    <td>❌</td>
                                    <td>Additional cost</td>
                                    <td>2 sessions/month</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <section class="membership-benefits">
            <div class="container">
                <h2>Membership Benefits</h2>
                <div class="benefits-grid">
                    <div class="benefit">
                        <h3>No Commitment</h3>
                        <p>Cancel anytime with 30 days notice</p>
                    </div>
                    <div class="benefit">
                        <h3>Freeze Options</h3>
                        <p>Pause your membership for up to 3 months</p>
                    </div>
                    <div class="benefit">
                        <h3>Nationwide Access</h3>
                        <p>Use any FitZone location across the country</p>
                    </div>
                    <div class="benefit">
                        <h3>Family Discounts</h3>
                        <p>Save 15% when you add family members</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="membership-signup">
            <div class="container">
                <h2>Ready to Start Your Fitness Journey?</h2>
                <p>Join FitZone today and transform your life with our expert guidance and state-of-the-art facilities.</p>
                <div class="signup-options">
                    <a href="register.php" class="btn btn-primary btn-large">Join Online</a>
                    <a href="contact.php" class="btn btn-secondary btn-large">Visit Us Today</a>
                </div>
                <div class="trial-offer">
                    <p><strong>New members:</strong> Get your first week FREE! <a href="register.php">Sign up now</a></p>
                </div>
            </div>
        </section>
    </main>

    <?php include 'php/includes/footer.php'; ?>
    <script src="js/main.js"></script>
</body>
</html>