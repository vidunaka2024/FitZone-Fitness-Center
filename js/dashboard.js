// FitZone Fitness Center - Dashboard JavaScript

(function() {
    'use strict';

    // Initialize dashboard when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initializeDashboard();
    });

    function initializeDashboard() {
        initializeTabNavigation();
        initializeCharts();
        initializeFilters();
        initializeInteractiveElements();
        initializeDataRefresh();
        loadDashboardData();
    }

    // Tab navigation functionality
    function initializeTabNavigation() {
        const navLinks = document.querySelectorAll('.dashboard-nav .nav-link');
        const tabContents = document.querySelectorAll('.tab-content');

        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetTab = this.getAttribute('data-tab');
                
                // Remove active class from all nav links
                navLinks.forEach(nav => nav.classList.remove('active'));
                
                // Add active class to clicked nav
                this.classList.add('active');
                
                // Hide all tab contents
                tabContents.forEach(tab => tab.classList.remove('active'));
                
                // Show target tab content
                const targetContent = document.getElementById(targetTab);
                if (targetContent) {
                    targetContent.classList.add('active');
                    
                    // Trigger specific tab initialization
                    initializeTabContent(targetTab);
                }
            });
        });

        // Handle direct URL navigation to tabs
        const hash = window.location.hash.substring(1);
        if (hash) {
            const targetLink = document.querySelector(`[data-tab="${hash}"]`);
            if (targetLink) {
                targetLink.click();
            }
        }
    }

    // Initialize content for specific tabs
    function initializeTabContent(tabName) {
        switch(tabName) {
            case 'workouts':
                loadWorkoutData();
                break;
            case 'classes':
                loadClassData();
                break;
            case 'progress':
                initializeProgressCharts();
                break;
            case 'nutrition':
                loadNutritionData();
                break;
            default:
                break;
        }
    }

    // Initialize charts
    function initializeCharts() {
        const progressChart = document.getElementById('progressChart');
        if (progressChart) {
            createProgressChart(progressChart);
        }
    }

    function createProgressChart(canvas) {
        const ctx = canvas.getContext('2d');
        
        // Sample progress data
        const progressData = {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6'],
            datasets: [{
                label: 'Weight (lbs)',
                data: [183, 181, 180, 178, 176, 175],
                borderColor: '#e74c3c',
                backgroundColor: 'rgba(231, 76, 60, 0.1)',
                tension: 0.4
            }]
        };

        // Simple chart implementation (replace with Chart.js in production)
        drawLineChart(ctx, progressData, canvas.width, canvas.height);
    }

    function drawLineChart(ctx, data, width, height) {
        ctx.clearRect(0, 0, width, height);
        
        const padding = 40;
        const chartWidth = width - padding * 2;
        const chartHeight = height - padding * 2;
        
        // Draw axes
        ctx.strokeStyle = '#ddd';
        ctx.beginPath();
        ctx.moveTo(padding, padding);
        ctx.lineTo(padding, height - padding);
        ctx.lineTo(width - padding, height - padding);
        ctx.stroke();
        
        // Draw data points and line
        const points = data.datasets[0].data;
        const stepX = chartWidth / (points.length - 1);
        const maxY = Math.max(...points);
        const minY = Math.min(...points);
        const rangeY = maxY - minY || 1;
        
        ctx.strokeStyle = data.datasets[0].borderColor;
        ctx.fillStyle = data.datasets[0].backgroundColor;
        ctx.lineWidth = 2;
        
        ctx.beginPath();
        points.forEach((point, index) => {
            const x = padding + index * stepX;
            const y = height - padding - ((point - minY) / rangeY) * chartHeight;
            
            if (index === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        });
        ctx.stroke();
        
        // Draw data points
        ctx.fillStyle = data.datasets[0].borderColor;
        points.forEach((point, index) => {
            const x = padding + index * stepX;
            const y = height - padding - ((point - minY) / rangeY) * chartHeight;
            
            ctx.beginPath();
            ctx.arc(x, y, 4, 0, 2 * Math.PI);
            ctx.fill();
        });
    }

    // Initialize filters
    function initializeFilters() {
        // Workout filters
        const workoutFilters = document.querySelectorAll('.filter-btn');
        workoutFilters.forEach(filter => {
            filter.addEventListener('click', function() {
                // Remove active class from all filters
                workoutFilters.forEach(f => f.classList.remove('active'));
                
                // Add active class to clicked filter
                this.classList.add('active');
                
                // Filter workouts
                const filterType = this.getAttribute('data-filter');
                filterWorkouts(filterType);
            });
        });

        // Class tabs
        const classTabs = document.querySelectorAll('.tab-btn');
        classTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');
                
                // Remove active class from all tabs
                classTabs.forEach(t => t.classList.remove('active'));
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Show target content
                const classContents = document.querySelectorAll('.class-content');
                classContents.forEach(content => content.classList.remove('active'));
                
                const targetContent = document.getElementById(targetTab);
                if (targetContent) {
                    targetContent.classList.add('active');
                    
                    if (targetTab === 'book-new') {
                        loadAvailableClasses();
                    }
                }
            });
        });
    }

    // Filter workouts by type
    function filterWorkouts(type) {
        const workoutItems = document.querySelectorAll('.workout-item');
        
        workoutItems.forEach(item => {
            const itemType = item.getAttribute('data-type');
            
            if (type === 'all' || itemType === type) {
                item.style.display = 'grid';
                // Add fade in animation
                item.style.opacity = '0';
                setTimeout(() => {
                    item.style.opacity = '1';
                }, 50);
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Interactive elements
    function initializeInteractiveElements() {
        // Quick action buttons
        const quickActions = document.querySelectorAll('.quick-actions .btn');
        quickActions.forEach(btn => {
            btn.addEventListener('click', function() {
                const action = this.textContent.trim().toLowerCase();
                
                if (action.includes('book class')) {
                    navigateToTab('classes');
                    setTimeout(() => {
                        const bookTab = document.querySelector('[data-tab="book-new"]');
                        if (bookTab) bookTab.click();
                    }, 100);
                } else if (action.includes('schedule pt')) {
                    navigateToTab('appointments');
                }
            });
        });

        // Workout action buttons
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn') && e.target.textContent.includes('View Details')) {
                showWorkoutDetails(e.target);
            } else if (e.target.classList.contains('btn') && e.target.textContent.includes('Repeat Workout')) {
                repeatWorkout(e.target);
            }
        });

        // Class action buttons
        document.addEventListener('click', function(e) {
            if (e.target.textContent.includes('Cancel')) {
                cancelClass(e.target);
            } else if (e.target.textContent.includes('Reschedule')) {
                rescheduleClass(e.target);
            }
        });

        // Profile form submission
        const profileForm = document.querySelector('.profile-form');
        if (profileForm) {
            profileForm.addEventListener('submit', function(e) {
                e.preventDefault();
                saveProfile(this);
            });
        }
    }

    // Data refresh functionality
    function initializeDataRefresh() {
        // Refresh data every 5 minutes
        setInterval(() => {
            refreshDashboardData();
        }, 300000);

        // Add manual refresh button
        addRefreshButton();
    }

    function addRefreshButton() {
        const dashboardHeader = document.querySelector('.dashboard-header');
        if (dashboardHeader) {
            const refreshBtn = document.createElement('button');
            refreshBtn.className = 'btn btn-secondary btn-sm refresh-btn';
            refreshBtn.innerHTML = '↻ Refresh';
            refreshBtn.style.marginLeft = 'auto';
            
            refreshBtn.addEventListener('click', function() {
                this.disabled = true;
                this.innerHTML = '↻ Refreshing...';
                
                refreshDashboardData().then(() => {
                    this.disabled = false;
                    this.innerHTML = '↻ Refresh';
                    
                    if (window.FitZone && window.FitZone.showNotification) {
                        window.FitZone.showNotification('Dashboard data refreshed!', 'success');
                    }
                });
            });
            
            dashboardHeader.style.display = 'flex';
            dashboardHeader.style.alignItems = 'center';
            dashboardHeader.appendChild(refreshBtn);
        }
    }

    // Data loading functions
    function loadDashboardData() {
        // Simulate API calls to load initial data
        Promise.all([
            loadUserStats(),
            loadScheduleData(),
            loadProgressData(),
            loadAchievements()
        ]).then(() => {
            console.log('Dashboard data loaded successfully');
        }).catch(error => {
            console.error('Error loading dashboard data:', error);
        });
    }

    function loadUserStats() {
        return new Promise(resolve => {
            // Simulate API call
            setTimeout(() => {
                updateStatsCards({
                    workouts: 12,
                    classes: 8,
                    time: '24h',
                    calories: 2450
                });
                resolve();
            }, 500);
        });
    }

    function loadScheduleData() {
        return new Promise(resolve => {
            setTimeout(() => {
                // Schedule data would be loaded here
                resolve();
            }, 300);
        });
    }

    function loadProgressData() {
        return new Promise(resolve => {
            setTimeout(() => {
                updateProgressChart();
                resolve();
            }, 400);
        });
    }

    function loadAchievements() {
        return new Promise(resolve => {
            setTimeout(() => {
                // Achievements would be loaded here
                resolve();
            }, 200);
        });
    }

    function loadWorkoutData() {
        // Load workout history
        console.log('Loading workout data...');
    }

    function loadClassData() {
        // Load class bookings
        console.log('Loading class data...');
    }

    function loadAvailableClasses() {
        const bookNewContent = document.getElementById('book-new');
        if (bookNewContent && !bookNewContent.hasChildNodes()) {
            bookNewContent.innerHTML = `
                <div class="available-classes">
                    <h3>Available Classes This Week</h3>
                    <div class="class-schedule">
                        <div class="class-time-slot">
                            <div class="time-info">
                                <h4>Today, 6:00 PM</h4>
                                <p>60 minutes</p>
                            </div>
                            <div class="class-details">
                                <h4>HIIT Training</h4>
                                <p>Instructor: Mike Thompson</p>
                                <p>15/20 spots available</p>
                            </div>
                            <button class="btn btn-primary btn-sm">Book Now</button>
                        </div>
                        
                        <div class="class-time-slot">
                            <div class="time-info">
                                <h4>Tomorrow, 8:00 AM</h4>
                                <p>45 minutes</p>
                            </div>
                            <div class="class-details">
                                <h4>Morning Yoga</h4>
                                <p>Instructor: Sarah Johnson</p>
                                <p>8/15 spots available</p>
                            </div>
                            <button class="btn btn-primary btn-sm">Book Now</button>
                        </div>
                        
                        <div class="class-time-slot">
                            <div class="time-info">
                                <h4>Tomorrow, 7:00 PM</h4>
                                <p>50 minutes</p>
                            </div>
                            <div class="class-details">
                                <h4>Zumba Dance</h4>
                                <p>Instructor: Maria Rodriguez</p>
                                <p>12/25 spots available</p>
                            </div>
                            <button class="btn btn-primary btn-sm">Book Now</button>
                        </div>
                    </div>
                </div>
            `;

            // Add booking functionality
            const bookButtons = bookNewContent.querySelectorAll('.btn');
            bookButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const classSlot = this.closest('.class-time-slot');
                    const className = classSlot.querySelector('h4').textContent;
                    bookClass(className, this);
                });
            });
        }
    }

    function loadNutritionData() {
        console.log('Loading nutrition data...');
    }

    // Action handlers
    function showWorkoutDetails(button) {
        const workoutItem = button.closest('.workout-item');
        const workoutName = workoutItem.querySelector('h3').textContent;
        
        // Create modal with workout details
        const modal = createModal('Workout Details', `
            <h3>${workoutName}</h3>
            <div class="workout-detail-content">
                <h4>Exercises Performed:</h4>
                <ul>
                    <li>Bench Press - 3 sets x 10 reps (185 lbs)</li>
                    <li>Pull-ups - 3 sets x 8 reps (Body weight)</li>
                    <li>Shoulder Press - 3 sets x 12 reps (135 lbs)</li>
                    <li>Barbell Rows - 3 sets x 10 reps (155 lbs)</li>
                </ul>
                <h4>Performance Notes:</h4>
                <p>Felt strong today. Increased weight on bench press by 5 lbs from last session. Form was good throughout.</p>
                <div class="workout-stats">
                    <div class="stat">
                        <strong>Total Time:</strong> 45 minutes
                    </div>
                    <div class="stat">
                        <strong>Calories Burned:</strong> 320
                    </div>
                    <div class="stat">
                        <strong>Average Heart Rate:</strong> 142 bpm
                    </div>
                </div>
            </div>
        `);
        
        showModal(modal);
    }

    function repeatWorkout(button) {
        const workoutItem = button.closest('.workout-item');
        const workoutName = workoutItem.querySelector('h3').textContent;
        
        if (window.FitZone && window.FitZone.showNotification) {
            window.FitZone.showNotification(`Starting "${workoutName}" workout...`, 'success');
        }
        
        // In a real application, this would navigate to the workout screen
        setTimeout(() => {
            if (window.FitZone && window.FitZone.showNotification) {
                window.FitZone.showNotification('Workout plan loaded! Ready to begin.', 'info');
            }
        }, 2000);
    }

    function cancelClass(button) {
        const classBooking = button.closest('.class-booking');
        const className = classBooking.querySelector('h3').textContent;
        
        if (confirm(`Are you sure you want to cancel "${className}"?`)) {
            // Animate removal
            classBooking.style.opacity = '0';
            classBooking.style.transform = 'translateX(-100%)';
            
            setTimeout(() => {
                classBooking.remove();
                if (window.FitZone && window.FitZone.showNotification) {
                    window.FitZone.showNotification(`"${className}" has been cancelled.`, 'info');
                }
            }, 300);
        }
    }

    function rescheduleClass(button) {
        const classBooking = button.closest('.class-booking');
        const className = classBooking.querySelector('h3').textContent;
        
        const modal = createModal('Reschedule Class', `
            <h3>Reschedule "${className}"</h3>
            <form class="reschedule-form">
                <div class="form-group">
                    <label>Select New Date & Time:</label>
                    <select required>
                        <option value="">Choose a time slot...</option>
                        <option value="tomorrow-8am">Tomorrow, 8:00 AM</option>
                        <option value="tomorrow-6pm">Tomorrow, 6:00 PM</option>
                        <option value="thursday-10am">Thursday, 10:00 AM</option>
                        <option value="friday-7pm">Friday, 7:00 PM</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Reschedule</button>
                    <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                </div>
            </form>
        `);
        
        const form = modal.querySelector('.reschedule-form');
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const newTime = this.querySelector('select').value;
            if (newTime) {
                if (window.FitZone && window.FitZone.showNotification) {
                    window.FitZone.showNotification(`"${className}" has been rescheduled.`, 'success');
                }
                closeModal(modal);
            }
        });
        
        showModal(modal);
    }

    function bookClass(className, button) {
        button.disabled = true;
        button.textContent = 'Booking...';
        
        // Simulate booking process
        setTimeout(() => {
            button.textContent = 'Booked!';
            button.classList.remove('btn-primary');
            button.classList.add('btn-success');
            
            if (window.FitZone && window.FitZone.showNotification) {
                window.FitZone.showNotification(`Successfully booked "${className}"!`, 'success');
            }
            
            // Update the class count
            const spotsElement = button.parentElement.querySelector('p:last-child');
            if (spotsElement) {
                const currentSpots = parseInt(spotsElement.textContent.match(/\d+/)[0]);
                spotsElement.textContent = spotsElement.textContent.replace(/\d+/, currentSpots - 1);
            }
        }, 1500);
    }

    function saveProfile(form) {
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        if (window.FitZone && window.FitZone.setLoadingState) {
            window.FitZone.setLoadingState(submitBtn, true);
        }
        
        // Simulate save process
        setTimeout(() => {
            if (window.FitZone && window.FitZone.setLoadingState) {
                window.FitZone.setLoadingState(submitBtn, false);
            }
            
            if (window.FitZone && window.FitZone.showNotification) {
                window.FitZone.showNotification('Profile updated successfully!', 'success');
            }
        }, 2000);
    }

    // Utility functions
    function navigateToTab(tabName) {
        const targetLink = document.querySelector(`[data-tab="${tabName}"]`);
        if (targetLink) {
            targetLink.click();
        }
    }

    function updateStatsCards(stats) {
        const statCards = document.querySelectorAll('.stat-card');
        const values = [stats.workouts, stats.classes, stats.time, stats.calories];
        
        statCards.forEach((card, index) => {
            const valueElement = card.querySelector('h3');
            if (valueElement && values[index] !== undefined) {
                animateNumber(valueElement, 0, values[index], 1000);
            }
        });
    }

    function animateNumber(element, start, end, duration) {
        const range = end - start;
        const increment = range / (duration / 16);
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                current = end;
                clearInterval(timer);
            }
            
            if (typeof end === 'string' && end.includes('h')) {
                element.textContent = Math.floor(current) + 'h';
            } else if (typeof end === 'number' && end > 1000) {
                element.textContent = Math.floor(current).toLocaleString();
            } else {
                element.textContent = Math.floor(current);
            }
        }, 16);
    }

    function updateProgressChart() {
        const progressChart = document.getElementById('progressChart');
        if (progressChart) {
            createProgressChart(progressChart);
        }
    }

    function initializeProgressCharts() {
        // Load comprehensive progress charts for the progress tab
        console.log('Initializing progress charts...');
    }

    function refreshDashboardData() {
        return Promise.all([
            loadUserStats(),
            loadScheduleData(),
            loadProgressData()
        ]);
    }

    // Modal functions
    function createModal(title, content) {
        const modal = document.createElement('div');
        modal.className = 'dashboard-modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2>${title}</h2>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    ${content}
                </div>
            </div>
        `;
        
        // Add event listeners
        modal.querySelector('.modal-close').addEventListener('click', () => closeModal(modal));
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal(modal);
        });
        
        const closeButtons = modal.querySelectorAll('.close-modal');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', () => closeModal(modal));
        });
        
        return modal;
    }

    function showModal(modal) {
        document.body.appendChild(modal);
        setTimeout(() => modal.classList.add('active'), 10);
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modal) {
        modal.classList.remove('active');
        setTimeout(() => {
            if (modal.parentNode) {
                modal.parentNode.removeChild(modal);
            }
        }, 300);
        
        // Restore body scroll
        document.body.style.overflow = '';
    }

    // Add modal styles
    const style = document.createElement('style');
    style.textContent = `
        .dashboard-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1004;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .dashboard-modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .dashboard-modal .modal-content {
            background: white;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }
        
        .dashboard-modal.active .modal-content {
            transform: translateY(0);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .modal-header h2 {
            margin: 0;
            color: #2c3e50;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
            padding: 0;
            width: 30px;
            height: 30px;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .workout-detail-content h4 {
            color: #e74c3c;
            margin: 1rem 0 0.5rem;
        }
        
        .workout-detail-content ul {
            margin: 0 0 1rem 1rem;
        }
        
        .workout-stats {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-top: 1rem;
        }
        
        .workout-stats .stat {
            margin-bottom: 0.5rem;
        }
        
        .class-time-slot {
            display: grid;
            grid-template-columns: 150px 1fr auto;
            gap: 1rem;
            align-items: center;
            padding: 1rem;
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 1rem;
            transition: box-shadow 0.3s ease;
        }
        
        .class-time-slot:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .time-info h4 {
            margin: 0 0 0.25rem;
            color: #2c3e50;
            font-size: 1rem;
        }
        
        .time-info p,
        .class-details p {
            margin: 0;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .class-details h4 {
            margin: 0 0 0.25rem;
            color: #e74c3c;
            font-size: 1.1rem;
        }
        
        @media (max-width: 768px) {
            .class-time-slot {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 0.5rem;
            }
        }
    `;
    document.head.appendChild(style);

    // Export functions for external use
    window.Dashboard = {
        navigateToTab,
        refreshDashboardData,
        showModal,
        closeModal
    };

})();