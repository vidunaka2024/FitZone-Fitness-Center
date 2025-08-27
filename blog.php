<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitness Blog - FitZone Fitness Center</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body>
    <?php include 'php/includes/header.php'; ?>
    
    <main>
        <section class="page-header">
            <div class="container">
                <h1>Fitness Blog</h1>
                <p>Expert tips, workout guides, and wellness advice from our team</p>
            </div>
        </section>

        <section class="blog-content">
            <div class="container">
                <div class="blog-layout">
                    <div class="blog-posts">
                        <article class="blog-post featured">
                            <img src="images/blog/featured-post.jpg" alt="Summer Fitness Challenge">
                            <div class="post-content">
                                <div class="post-meta">
                                    <span class="post-category">Fitness Tips</span>
                                    <span class="post-date">August 15, 2024</span>
                                    <span class="post-author">By Sarah Johnson</span>
                                </div>
                                <h2>Summer Fitness Challenge: 30 Days to Your Best Self</h2>
                                <p>Transform your summer with our comprehensive 30-day fitness challenge designed to boost your energy, strength, and confidence. This program combines cardio, strength training, and flexibility exercises...</p>
                                <a href="#" class="read-more">Read More</a>
                            </div>
                        </article>

                        <div class="blog-grid">
                            <article class="blog-post">
                                <img src="images/blog/nutrition-guide.jpg" alt="Nutrition Guide">
                                <div class="post-content">
                                    <div class="post-meta">
                                        <span class="post-category">Nutrition</span>
                                        <span class="post-date">August 10, 2024</span>
                                    </div>
                                    <h3>Pre and Post-Workout Nutrition: Fuel Your Success</h3>
                                    <p>Discover the best foods to eat before and after your workouts to maximize performance and recovery...</p>
                                    <a href="#" class="read-more">Read More</a>
                                </div>
                            </article>

                            <article class="blog-post">
                                <img src="images/blog/home-workout.jpg" alt="Home Workout">
                                <div class="post-content">
                                    <div class="post-meta">
                                        <span class="post-category">Workouts</span>
                                        <span class="post-date">August 5, 2024</span>
                                    </div>
                                    <h3>Effective Home Workouts: No Equipment Needed</h3>
                                    <p>Stay fit at home with these bodyweight exercises that require no equipment but deliver amazing results...</p>
                                    <a href="#" class="read-more">Read More</a>
                                </div>
                            </article>

                            <article class="blog-post">
                                <img src="images/blog/mental-health.jpg" alt="Mental Health and Fitness">
                                <div class="post-content">
                                    <div class="post-meta">
                                        <span class="post-category">Wellness</span>
                                        <span class="post-date">July 30, 2024</span>
                                    </div>
                                    <h3>The Mental Health Benefits of Regular Exercise</h3>
                                    <p>Explore how regular physical activity can improve your mood, reduce stress, and boost mental wellbeing...</p>
                                    <a href="#" class="read-more">Read More</a>
                                </div>
                            </article>

                            <article class="blog-post">
                                <img src="images/blog/beginner-guide.jpg" alt="Beginner's Guide">
                                <div class="post-content">
                                    <div class="post-meta">
                                        <span class="post-category">Beginners</span>
                                        <span class="post-date">July 25, 2024</span>
                                    </div>
                                    <h3>Beginner's Guide to Gym Etiquette</h3>
                                    <p>New to the gym? Learn the unspoken rules and etiquette that will help you feel confident and comfortable...</p>
                                    <a href="#" class="read-more">Read More</a>
                                </div>
                            </article>

                            <article class="blog-post">
                                <img src="images/blog/strength-training.jpg" alt="Strength Training for Women">
                                <div class="post-content">
                                    <div class="post-meta">
                                        <span class="post-category">Strength Training</span>
                                        <span class="post-date">July 20, 2024</span>
                                    </div>
                                    <h3>Strength Training for Women: Breaking the Myths</h3>
                                    <p>Debunking common misconceptions about women and weightlifting, plus a beginner-friendly strength program...</p>
                                    <a href="#" class="read-more">Read More</a>
                                </div>
                            </article>

                            <article class="blog-post">
                                <img src="images/blog/recovery-tips.jpg" alt="Recovery Tips">
                                <div class="post-content">
                                    <div class="post-meta">
                                        <span class="post-category">Recovery</span>
                                        <span class="post-date">July 15, 2024</span>
                                    </div>
                                    <h3>Recovery 101: Why Rest Days Are Crucial</h3>
                                    <p>Learn why recovery is just as important as your workouts and how to optimize your rest days for better results...</p>
                                    <a href="#" class="read-more">Read More</a>
                                </div>
                            </article>
                        </div>

                        <div class="pagination">
                            <a href="#" class="page-link active">1</a>
                            <a href="#" class="page-link">2</a>
                            <a href="#" class="page-link">3</a>
                            <a href="#" class="page-link">Next</a>
                        </div>
                    </div>

                    <aside class="blog-sidebar">
                        <div class="widget">
                            <h3>Search Posts</h3>
                            <form class="search-form">
                                <input type="text" placeholder="Search blog posts...">
                                <button type="submit">Search</button>
                            </form>
                        </div>

                        <div class="widget">
                            <h3>Categories</h3>
                            <ul class="category-list">
                                <li><a href="#">Fitness Tips <span>(12)</span></a></li>
                                <li><a href="#">Nutrition <span>(8)</span></a></li>
                                <li><a href="#">Workouts <span>(15)</span></a></li>
                                <li><a href="#">Wellness <span>(6)</span></a></li>
                                <li><a href="#">Beginners <span>(9)</span></a></li>
                                <li><a href="#">Strength Training <span>(11)</span></a></li>
                                <li><a href="#">Recovery <span>(5)</span></a></li>
                            </ul>
                        </div>

                        <div class="widget">
                            <h3>Recent Posts</h3>
                            <div class="recent-posts">
                                <div class="recent-post">
                                    <img src="images/blog/thumb1.jpg" alt="Recent Post">
                                    <div class="recent-post-content">
                                        <h4><a href="#">5 Morning Exercises to Start Your Day</a></h4>
                                        <span class="recent-date">August 12, 2024</span>
                                    </div>
                                </div>
                                <div class="recent-post">
                                    <img src="images/blog/thumb2.jpg" alt="Recent Post">
                                    <div class="recent-post-content">
                                        <h4><a href="#">Hydration Tips for Athletes</a></h4>
                                        <span class="recent-date">August 8, 2024</span>
                                    </div>
                                </div>
                                <div class="recent-post">
                                    <img src="images/blog/thumb3.jpg" alt="Recent Post">
                                    <div class="recent-post-content">
                                        <h4><a href="#">Building Core Strength at Home</a></h4>
                                        <span class="recent-date">August 3, 2024</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="widget">
                            <h3>Newsletter</h3>
                            <p>Subscribe to get the latest fitness tips and updates directly to your inbox.</p>
                            <form class="newsletter-form">
                                <input type="email" placeholder="Your email address">
                                <button type="submit" class="btn btn-primary">Subscribe</button>
                            </form>
                        </div>
                    </aside>
                </div>
            </div>
        </section>
    </main>

    <?php include 'php/includes/footer.php'; ?>
    <script src="js/main.js"></script>
</body>
</html>