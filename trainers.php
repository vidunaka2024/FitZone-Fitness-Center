<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Trainers - FitZone Fitness Center</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body>
    <?php include 'php/includes/header.php'; ?>
    
    <main>
        <section class="page-header">
            <div class="container">
                <h1>Our Expert Trainers</h1>
                <p>Meet our certified fitness professionals dedicated to helping you reach your goals</p>
            </div>
        </section>

        <section class="trainers-grid">
            <div class="container">
                <div class="trainers-container">
                    <div class="trainer-card">
                        <img src="images/trainers/sarah-johnson.jpg" alt="Sarah Johnson">
                        <div class="trainer-info">
                            <h3>Sarah Johnson</h3>
                            <p class="trainer-title">Yoga & Pilates Specialist</p>
                            <div class="trainer-credentials">
                                <p><strong>Certifications:</strong> RYT-500, PMA-CPT</p>
                                <p><strong>Experience:</strong> 8 years</p>
                                <p><strong>Specialties:</strong> Hatha Yoga, Vinyasa, Pilates Reformer</p>
                            </div>
                            <p class="trainer-bio">Sarah brings peace and strength to every session, helping clients find balance through mindful movement and breathing techniques.</p>
                            <div class="trainer-contact">
                                <button class="btn btn-primary">Book Session</button>
                                <button class="btn btn-secondary">View Schedule</button>
                            </div>
                        </div>
                    </div>

                    <div class="trainer-card">
                        <img src="images/trainers/mike-thompson.jpg" alt="Mike Thompson">
                        <div class="trainer-info">
                            <h3>Mike Thompson</h3>
                            <p class="trainer-title">Strength & Conditioning Coach</p>
                            <div class="trainer-credentials">
                                <p><strong>Certifications:</strong> CSCS, NASM-CPT</p>
                                <p><strong>Experience:</strong> 12 years</p>
                                <p><strong>Specialties:</strong> CrossFit, Powerlifting, Sports Performance</p>
                            </div>
                            <p class="trainer-bio">Former collegiate athlete turned coach, Mike specializes in building functional strength and athletic performance for all levels.</p>
                            <div class="trainer-contact">
                                <button class="btn btn-primary">Book Session</button>
                                <button class="btn btn-secondary">View Schedule</button>
                            </div>
                        </div>
                    </div>

                    <div class="trainer-card">
                        <img src="images/trainers/maria-rodriguez.jpg" alt="Maria Rodriguez">
                        <div class="trainer-info">
                            <h3>Maria Rodriguez</h3>
                            <p class="trainer-title">Dance Fitness Instructor</p>
                            <div class="trainer-credentials">
                                <p><strong>Certifications:</strong> ZIN, AFAA</p>
                                <p><strong>Experience:</strong> 6 years</p>
                                <p><strong>Specialties:</strong> Zumba, Latin Dance, Cardio Dance</p>
                            </div>
                            <p class="trainer-bio">Maria's infectious energy and passion for dance makes every workout feel like a party while delivering serious fitness results.</p>
                            <div class="trainer-contact">
                                <button class="btn btn-primary">Book Session</button>
                                <button class="btn btn-secondary">View Schedule</button>
                            </div>
                        </div>
                    </div>

                    <div class="trainer-card">
                        <img src="images/trainers/david-lee.jpg" alt="David Lee">
                        <div class="trainer-info">
                            <h3>David Lee</h3>
                            <p class="trainer-title">Cardio & Weight Loss Specialist</p>
                            <div class="trainer-credentials">
                                <p><strong>Certifications:</strong> ACE-CPT, Spinning Instructor</p>
                                <p><strong>Experience:</strong> 10 years</p>
                                <p><strong>Specialties:</strong> HIIT, Spinning, Fat Loss, Endurance</p>
                            </div>
                            <p class="trainer-bio">David's evidence-based approach to cardio training has helped hundreds of clients achieve their weight loss and endurance goals.</p>
                            <div class="trainer-contact">
                                <button class="btn btn-primary">Book Session</button>
                                <button class="btn btn-secondary">View Schedule</button>
                            </div>
                        </div>
                    </div>

                    <div class="trainer-card">
                        <img src="images/trainers/emma-wilson.jpg" alt="Emma Wilson">
                        <div class="trainer-info">
                            <h3>Emma Wilson</h3>
                            <p class="trainer-title">Rehabilitation & Mobility Expert</p>
                            <div class="trainer-credentials">
                                <p><strong>Certifications:</strong> DPT, ACSM-CPT</p>
                                <p><strong>Experience:</strong> 15 years</p>
                                <p><strong>Specialties:</strong> Injury Prevention, Corrective Exercise, Senior Fitness</p>
                            </div>
                            <p class="trainer-bio">With a background in physical therapy, Emma specializes in helping clients overcome limitations and build sustainable fitness habits.</p>
                            <div class="trainer-contact">
                                <button class="btn btn-primary">Book Session</button>
                                <button class="btn btn-secondary">View Schedule</button>
                            </div>
                        </div>
                    </div>

                    <div class="trainer-card">
                        <img src="images/trainers/john-martinez.jpg" alt="John Martinez">
                        <div class="trainer-info">
                            <h3>John Martinez</h3>
                            <p class="trainer-title">Functional Training Coach</p>
                            <div class="trainer-credentials">
                                <p><strong>Certifications:</strong> FMS, TRX-STC</p>
                                <p><strong>Experience:</strong> 9 years</p>
                                <p><strong>Specialties:</strong> Functional Movement, TRX, Bootcamp</p>
                            </div>
                            <p class="trainer-bio">John focuses on real-world movement patterns that improve daily life activities while building overall fitness and strength.</p>
                            <div class="trainer-contact">
                                <button class="btn btn-primary">Book Session</button>
                                <button class="btn btn-secondary">View Schedule</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="training-programs">
            <div class="container">
                <h2>Personal Training Programs</h2>
                <div class="programs-grid">
                    <div class="program">
                        <h3>Individual Training</h3>
                        <p>One-on-one sessions tailored to your specific goals</p>
                        <ul>
                            <li>Personalized workout plans</li>
                            <li>Flexible scheduling</li>
                            <li>Progress tracking</li>
                            <li>Nutritional guidance</li>
                        </ul>
                    </div>
                    <div class="program">
                        <h3>Small Group Training</h3>
                        <p>Semi-private sessions with 2-4 participants</p>
                        <ul>
                            <li>Cost-effective option</li>
                            <li>Motivational group dynamic</li>
                            <li>Customized for group needs</li>
                            <li>Build workout partnerships</li>
                        </ul>
                    </div>
                    <div class="program">
                        <h3>Specialized Programs</h3>
                        <p>Targeted training for specific needs</p>
                        <ul>
                            <li>Injury rehabilitation</li>
                            <li>Sports performance</li>
                            <li>Senior fitness</li>
                            <li>Youth training</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'php/includes/footer.php'; ?>
    <script src="js/main.js"></script>
</body>
</html>