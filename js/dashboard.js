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
                    } else if (targetTab === 'past') {
                        loadPastAppointments();
                    }
                }
            });
        });

        // Appointment filters
        const appointmentFilters = document.querySelectorAll('.appointment-filters .filter-btn');
        appointmentFilters.forEach(filter => {
            filter.addEventListener('click', function() {
                // Remove active class from all filters
                appointmentFilters.forEach(f => f.classList.remove('active'));
                
                // Add active class to clicked filter
                this.classList.add('active');
                
                // Filter appointments
                const filterType = this.getAttribute('data-filter');
                filterAppointments(filterType);
            });
        });

        // Class filters
        const classStatusFilter = document.getElementById('class-status-filter');
        const classTypeFilter = document.getElementById('class-type-filter');
        const clearFiltersBtn = document.getElementById('clear-class-filters');

        if (classStatusFilter) {
            classStatusFilter.addEventListener('change', function() {
                filterClasses();
            });
        }

        if (classTypeFilter) {
            classTypeFilter.addEventListener('change', function() {
                filterClasses();
            });
        }

        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function() {
                if (classStatusFilter) classStatusFilter.value = 'all';
                if (classTypeFilter) classTypeFilter.value = 'all';
                filterClasses();
            });
        }
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

    // Filter appointments by type
    function filterAppointments(type) {
        const appointmentItems = document.querySelectorAll('.appointment-item');
        
        appointmentItems.forEach(item => {
            const itemType = item.getAttribute('data-type');
            const itemStatus = item.getAttribute('data-status');
            let show = false;
            
            switch(type) {
                case 'all':
                    show = true;
                    break;
                case 'classes':
                    show = itemType === 'class';
                    break;
                case 'training':
                    show = itemType === 'training';
                    break;
                case 'upcoming':
                    show = itemStatus === 'upcoming';
                    break;
                case 'past':
                    show = itemStatus === 'past';
                    break;
            }
            
            if (show) {
                item.style.display = 'block';
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

    // Filter classes by status and type
    function filterClasses() {
        const classCards = document.querySelectorAll('.class-booking-card');
        const statusFilter = document.getElementById('class-status-filter');
        const typeFilter = document.getElementById('class-type-filter');
        
        const selectedStatus = statusFilter ? statusFilter.value : 'all';
        const selectedType = typeFilter ? typeFilter.value : 'all';
        
        let visibleCount = 0;
        
        classCards.forEach(card => {
            let show = true;
            
            // Filter by status
            if (selectedStatus !== 'all') {
                const statusElement = card.querySelector('.status-indicator');
                if (statusElement) {
                    const cardStatus = statusElement.classList.contains('status-confirmed') ? 'confirmed' :
                                     statusElement.classList.contains('status-wait_listed') ? 'wait_listed' :
                                     statusElement.classList.contains('status-cancelled') ? 'cancelled' : '';
                    
                    if (cardStatus !== selectedStatus) {
                        show = false;
                    }
                }
            }
            
            // Filter by type (this would require adding data-type to the cards)
            if (selectedType !== 'all' && show) {
                const classTitle = card.querySelector('.class-title').textContent.toLowerCase();
                let classType = '';
                
                if (classTitle.includes('yoga') || classTitle.includes('pilates') || classTitle.includes('stretch')) {
                    classType = 'flexibility';
                } else if (classTitle.includes('zumba') || classTitle.includes('dance')) {
                    classType = 'dance';
                } else if (classTitle.includes('strength') || classTitle.includes('weight') || classTitle.includes('muscle')) {
                    classType = 'strength';
                } else if (classTitle.includes('cardio') || classTitle.includes('cycling') || classTitle.includes('running')) {
                    classType = 'cardio';
                }
                
                if (selectedType !== classType && classType) {
                    show = false;
                }
            }
            
            if (show) {
                card.style.display = 'block';
                card.style.opacity = '0';
                setTimeout(() => {
                    card.style.opacity = '1';
                }, 50);
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Show/hide no results message
        updateNoResultsMessage(visibleCount);
    }

    function updateNoResultsMessage(visibleCount) {
        const container = document.getElementById('upcoming-appointments');
        let noResultsMsg = container.querySelector('.no-results-message');
        
        if (visibleCount === 0) {
            if (!noResultsMsg) {
                noResultsMsg = document.createElement('div');
                noResultsMsg.className = 'no-results-message';
                noResultsMsg.innerHTML = `
                    <div class="no-results-content">
                        <div class="no-results-icon">🔍</div>
                        <h3>No Classes Found</h3>
                        <p>No classes match your current filters.</p>
                        <button class="btn btn-secondary" id="reset-filters">Clear Filters</button>
                    </div>
                `;
                container.appendChild(noResultsMsg);
                
                // Add event listener for reset button
                const resetBtn = noResultsMsg.querySelector('#reset-filters');
                resetBtn.addEventListener('click', function() {
                    const statusFilter = document.getElementById('class-status-filter');
                    const typeFilter = document.getElementById('class-type-filter');
                    if (statusFilter) statusFilter.value = 'all';
                    if (typeFilter) typeFilter.value = 'all';
                    filterClasses();
                });
            }
            noResultsMsg.style.display = 'block';
        } else if (noResultsMsg) {
            noResultsMsg.style.display = 'none';
        }
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
            if (e.target.classList.contains('cancel-appointment')) {
                cancelAppointment(e.target);
            } else if (e.target.classList.contains('reschedule-appointment')) {
                rescheduleAppointment(e.target);
            } else if (e.target.textContent.includes('Cancel')) {
                cancelClass(e.target);
            } else if (e.target.textContent.includes('Reschedule')) {
                rescheduleClass(e.target);
            } else if (e.target.classList.contains('book-new-tab')) {
                const tab = e.target.getAttribute('data-tab');
                if (tab) {
                    const tabButton = document.querySelector(`[data-tab="${tab}"]`);
                    if (tabButton) {
                        tabButton.click();
                    }
                }
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
        // Load user's class bookings and appointments
        fetchWithAuth('php/user/appointments.php?type=all')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateClassBookings(data.data.appointments);
                    updateClassStats(data.data.statistics.classes);
                } else {
                    console.error('Failed to load class data:', data.message);
                }
            })
            .catch(error => {
                console.error('Error loading class data:', error);
            });
    }

    function loadAvailableClasses() {
        const bookNewContent = document.querySelector('#book-new .available-classes-container');
        if (bookNewContent) {
            bookNewContent.innerHTML = '<div class="loading">Loading available classes...</div>';
            
            // Load available classes from API
            fetchWithAuth('php/classes/list.php?available_only=true&limit=10')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.classes.length > 0) {
                        displayAvailableClasses(data.data.classes, bookNewContent);
                    } else {
                        bookNewContent.innerHTML = '<div class="no-classes">No classes available at this time.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading classes:', error);
                    bookNewContent.innerHTML = '<div class="error">Failed to load classes. Please try again.</div>';
                });
        }
    }

    function loadPastAppointments() {
        const pastContent = document.getElementById('past-appointments');
        if (pastContent) {
            pastContent.innerHTML = '<div class="loading">Loading past appointments...</div>';
            
            // Load past appointments from API
            fetchWithAuth('php/user/appointments.php?type=past&limit=20')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.appointments && data.data.appointments.length > 0) {
                        displayPastAppointments(data.data.appointments, pastContent);
                    } else {
                        pastContent.innerHTML = '<div class="no-appointments">You have no past appointments.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading past appointments:', error);
                    pastContent.innerHTML = '<div class="error">Failed to load past appointments. Please try again.</div>';
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
        const bookingId = button.getAttribute('data-booking-id');
        
        if (!bookingId) {
            console.error('Booking ID not found');
            return;
        }
        
        if (confirm(`Are you sure you want to cancel "${className}"?`)) {
            button.disabled = true;
            button.textContent = 'Cancelling...';
            
            // Get CSRF token
            const csrfToken = getCSRFToken();
            
            // Cancel via API
            fetch('php/classes/cancel.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    booking_id: bookingId,
                    csrf_token: csrfToken,
                    reason: 'User cancelled'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Animate removal
                    classBooking.style.opacity = '0';
                    classBooking.style.transform = 'translateX(-100%)';
                    
                    setTimeout(() => {
                        classBooking.remove();
                        if (window.FitZone && window.FitZone.showNotification) {
                            window.FitZone.showNotification(data.message || 'Class cancelled successfully.', 'info');
                        }
                    }, 300);
                } else {
                    button.disabled = false;
                    button.textContent = 'Cancel';
                    if (window.FitZone && window.FitZone.showNotification) {
                        window.FitZone.showNotification(data.message || 'Failed to cancel class.', 'error');
                    }
                }
            })
            .catch(error => {
                console.error('Error cancelling class:', error);
                button.disabled = false;
                button.textContent = 'Cancel';
                if (window.FitZone && window.FitZone.showNotification) {
                    window.FitZone.showNotification('Failed to cancel class. Please try again.', 'error');
                }
            });
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

    function bookClass(scheduleId, className, button) {
        button.disabled = true;
        button.textContent = 'Booking...';
        
        // Get CSRF token
        const csrfToken = getCSRFToken();
        
        console.log('Booking class:', {
            scheduleId: scheduleId,
            className: className,
            csrfToken: csrfToken ? 'Present' : 'Missing'
        });
        
        // Book class via API
        fetch('php/classes/book.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                schedule_id: scheduleId,
                booking_type: 'regular',
                csrf_token: csrfToken
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                button.textContent = data.data && data.data.wait_listed ? 'Wait Listed!' : 'Booked!';
                button.classList.remove('btn-primary');
                button.classList.add(data.data && data.data.wait_listed ? 'btn-warning' : 'btn-success');
                
                showDashboardNotification(data.message || 'Class booked successfully!', 'success');
                
                // Update the class count if not wait listed
                if (data.data && !data.data.wait_listed) {
                    const spotsElement = button.parentElement.querySelector('p:last-child');
                    if (spotsElement) {
                        const currentSpots = parseInt(spotsElement.textContent.match(/\d+/)[0]);
                        spotsElement.textContent = spotsElement.textContent.replace(/\d+/, Math.max(0, currentSpots - 1));
                    }
                }
            } else {
                button.disabled = false;
                button.textContent = 'Book Now';
                showDashboardNotification(data.message || 'Failed to book class.', 'error');
            }
        })
        .catch(error => {
            console.error('Error booking class:', error);
            button.disabled = false;
            button.textContent = 'Book Now';
            showDashboardNotification('Booking failed. Please check your connection and try again.', 'error');
        });
    }

    function saveProfile(form) {
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Set loading state
        submitBtn.disabled = true;
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Saving...';
        
        // Add CSRF token to form data
        formData.append('csrf_token', csrfToken);
        
        fetch('php/user/update-profile.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showDashboardNotification('Profile updated successfully!', 'success');
                
                // Update the displayed user name if it changed
                const firstNameInput = form.querySelector('input[name="first_name"]');
                const lastNameInput = form.querySelector('input[name="last_name"]');
                const userNameDisplay = document.querySelector('.user-name');
                const dashboardHeader = document.querySelector('.dashboard-header h1');
                
                if (firstNameInput && lastNameInput && userNameDisplay) {
                    const newName = `${firstNameInput.value} ${lastNameInput.value}`;
                    userNameDisplay.textContent = newName;
                    
                    if (dashboardHeader) {
                        dashboardHeader.textContent = `Welcome Back, ${firstNameInput.value}!`;
                    }
                }
            } else {
                showDashboardNotification(data.message || 'Failed to update profile.', 'error');
                
                if (data.errors && data.errors.length > 0) {
                    const errorList = data.errors.join('\\n• ');
                    showDashboardNotification(`Validation errors:\\n• ${errorList}`, 'error');
                }
            }
        })
        .catch(error => {
            console.error('Error updating profile:', error);
            showDashboardNotification('Failed to update profile. Please try again.', 'error');
        })
        .finally(() => {
            // Restore button state
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
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

        .dashboard-notification {
            position: fixed;
            top: 80px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: white;
            font-weight: bold;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            z-index: 1001;
            max-width: 400px;
        }

        .dashboard-notification.show {
            opacity: 1;
            transform: translateX(0);
        }

        .dashboard-notification-success {
            background: #28a745;
        }

        .dashboard-notification-error {
            background: #dc3545;
        }

        .dashboard-notification-info {
            background: #17a2b8;
        }

        .no-appointments {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }

        .schedule-empty {
            text-align: center;
            padding: 1rem;
            color: #6c757d;
        }

        .schedule-empty a {
            color: #007bff;
            text-decoration: none;
        }

        .booking-status {
            font-size: 0.9rem;
            font-weight: bold;
        }

        .status-confirmed {
            color: #28a745;
        }

        .status-wait_listed {
            color: #ffc107;
        }

        .status-scheduled {
            color: #17a2b8;
        }

        .appointment-item {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .appointment-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            transform: translateY(-1px);
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .appointment-header h3 {
            margin: 0;
            color: #2c3e50;
        }

        .appointment-type-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .appointment-type-badge.class {
            background: #e3f2fd;
            color: #1976d2;
        }

        .appointment-type-badge.training {
            background: #fff3e0;
            color: #f57c00;
        }

        .appointment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .appointment-status {
            margin-bottom: 1rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .appointment-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .appointment-filters {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .past-appointment-item {
            border: 1px solid #f0f0f0;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #fafafa;
        }

        .rating-stars {
            display: flex;
            gap: 0.25rem;
            margin: 1rem 0;
            justify-content: center;
        }

        .star {
            font-size: 2rem;
            cursor: pointer;
            color: #dee2e6;
            transition: color 0.2s ease;
        }

        .star:hover {
            color: #ffc107;
        }

        .rating-form textarea {
            width: 100%;
            margin: 1rem 0;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
        }

        .loading-placeholder {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }

        @media (max-width: 768px) {
            .appointment-details {
                grid-template-columns: 1fr;
            }
            
            .appointment-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .appointment-actions {
                flex-direction: column;
            }
            
            .appointment-filters {
                justify-content: center;
            }
        }

        /* Class Booking Cards Styles */
        .upcoming-classes-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }

        .upcoming-classes-header h3 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.5rem;
        }

        .class-summary {
            display: flex;
            gap: 1rem;
        }

        .summary-item {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            background: #e3f2fd;
            color: #1565c0;
            font-size: 0.9rem;
        }

        .summary-item.waitlisted {
            background: #fff8e1;
            color: #f57c00;
        }

        .class-booking-card {
            border: 1px solid #e0e6ed;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .class-booking-card:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }

        .class-card-header {
            display: grid;
            grid-template-columns: 80px 1fr auto;
            gap: 1rem;
            padding: 1.5rem;
            align-items: center;
        }

        .class-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            background: #f8f9fa;
        }

        .class-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .class-image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .class-icon {
            font-size: 2rem;
            filter: grayscale(0);
        }

        .class-main-info {
            min-width: 0;
        }

        .class-title {
            margin: 0 0 0.5rem 0;
            color: #2c3e50;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .class-schedule {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .class-date,
        .class-time {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .class-status-badge {
            text-align: right;
        }

        .status-indicator {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-indicator.status-confirmed {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-indicator.status-wait_listed {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .status-indicator.status-scheduled {
            background: #cce5ff;
            color: #004085;
            border: 1px solid #99d3ff;
        }

        .class-card-details {
            padding: 0 1.5rem 1rem;
            border-top: 1px solid #f1f3f4;
            background: #fafbfc;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eef0f1;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 500;
            color: #495057;
            font-size: 0.9rem;
        }

        .detail-value {
            color: #2c3e50;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .time-until {
            color: #e74c3c;
            font-weight: 600;
        }

        .class-card-actions {
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }

        .no-classes-booked {
            text-align: center;
            padding: 3rem 2rem;
            background: #f8f9fa;
            border-radius: 12px;
            margin: 2rem 0;
        }

        .no-classes-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.6;
        }

        .no-classes-booked h3 {
            color: #6c757d;
            margin-bottom: 0.5rem;
        }

        .no-classes-booked p {
            color: #6c757d;
            margin-bottom: 2rem;
        }

        .no-classes-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        @media (max-width: 768px) {
            .class-card-header {
                grid-template-columns: 60px 1fr;
                gap: 0.75rem;
            }

            .class-status-badge {
                grid-column: 1 / -1;
                text-align: left;
                margin-top: 1rem;
            }

            .class-image {
                width: 60px;
                height: 60px;
            }

            .class-card-actions {
                flex-direction: column;
            }

            .upcoming-classes-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .class-summary {
                width: 100%;
                justify-content: center;
            }

            .no-classes-actions {
                flex-direction: column;
                align-items: center;
            }
        }

        /* Enhanced Classes Section Styles */
        .classes-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }

        .classes-header h2 {
            margin: 0;
            color: #2c3e50;
        }

        .classes-actions {
            display: flex;
            gap: 0.5rem;
        }

        .classes-filters {
            display: flex;
            gap: 1rem;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .filter-group label {
            font-size: 0.85rem;
            font-weight: 500;
            color: #495057;
        }

        .filter-group select {
            padding: 0.5rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            background: white;
            font-size: 0.9rem;
        }

        .book-new-section {
            padding: 2rem;
            text-align: center;
        }

        .book-new-header h3 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .book-new-header p {
            color: #6c757d;
            margin-bottom: 2rem;
        }

        .book-new-actions {
            margin-bottom: 3rem;
        }

        .btn-large {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 2rem;
            display: inline-block;
            text-decoration: none;
            border-radius: 8px;
        }

        .quick-links {
            text-align: center;
        }

        .quick-links h4 {
            color: #495057;
            margin-bottom: 1rem;
        }

        .quick-link {
            display: inline-block;
            padding: 0.5rem 1rem;
            margin: 0.25rem;
            background: #e9ecef;
            color: #495057;
            text-decoration: none;
            border-radius: 20px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .quick-link:hover {
            background: #007bff;
            color: white;
            transform: translateY(-1px);
        }

        .class-recommendations {
            text-align: left;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .class-recommendations h4 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .classes-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .classes-filters {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group {
                flex-direction: row;
                align-items: center;
                gap: 0.5rem;
            }

            .filter-group label {
                min-width: 100px;
            }

            .quick-links {
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .quick-link {
                margin: 0.25rem 0;
            }
        }

        /* No Results Message Styles */
        .no-results-message {
            padding: 3rem 2rem;
            text-align: center;
            background: #f8f9fa;
            border-radius: 12px;
            margin: 2rem 0;
        }

        .no-results-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .no-results-icon {
            font-size: 4rem;
            opacity: 0.6;
        }

        .no-results-message h3 {
            color: #6c757d;
            margin: 0;
        }

        .no-results-message p {
            color: #6c757d;
            margin: 0;
        }
    `;
    document.head.appendChild(style);

    // Utility functions for API calls
    function fetchWithAuth(url, options = {}) {
        // Add authentication headers if needed
        return fetch(url, {
            credentials: 'same-origin',
            ...options
        });
    }
    
    function getCSRFToken() {
        // Get CSRF token from meta tag or cookie
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute('content') : '';
    }
    
    function displayAvailableClasses(classes, container) {
        container.innerHTML = `
            <div class="available-classes">
                <h3>Available Classes</h3>
                <div class="class-schedule">
                    ${classes.map(classData => `
                        <div class="class-time-slot" data-schedule-id="${classData.schedule_id}">
                            <div class="time-info">
                                <h4>${classData.formatted_date}, ${classData.start_time}</h4>
                                <p>${classData.duration_minutes} minutes</p>
                            </div>
                            <div class="class-details">
                                <h4>${classData.title}</h4>
                                <p>Instructor: ${classData.instructor_name}</p>
                                <p>${classData.spots_available}/${classData.max_capacity} spots available</p>
                            </div>
                            <button class="btn btn-primary btn-sm book-class-btn" 
                                    data-schedule-id="${classData.schedule_id}"
                                    ${classData.spots_available <= 0 ? 'disabled' : ''}>
                                ${classData.spots_available <= 0 ? 'Full' : 'Book Now'}
                            </button>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
        
        // Add booking functionality
        const bookButtons = container.querySelectorAll('.book-class-btn');
        bookButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const scheduleId = this.getAttribute('data-schedule-id');
                const classSlot = this.closest('.class-time-slot');
                const className = classSlot.querySelector('.class-details h4').textContent;
                bookClass(scheduleId, className, this);
            });
        });
    }
    
    function updateClassBookings(appointments) {
        // Update the existing bookings display
        const bookingsContainer = document.querySelector('#upcoming-bookings, #my-classes');
        if (bookingsContainer && appointments.length > 0) {
            bookingsContainer.innerHTML = appointments.map(appointment => `
                <div class="class-booking">
                    <div class="booking-info">
                        <h3>${appointment.title}</h3>
                        <p><strong>Date:</strong> ${appointment.formatted_date}</p>
                        <p><strong>Time:</strong> ${appointment.start_time} - ${appointment.end_time}</p>
                        <p><strong>Instructor:</strong> ${appointment.instructor_name}</p>
                        <p><strong>Room:</strong> ${appointment.room}</p>
                        <p><strong>Status:</strong> <span class="status ${appointment.booking_status}">${appointment.booking_status}</span></p>
                    </div>
                    <div class="booking-actions">
                        ${appointment.can_cancel ? 
                            `<button class="btn btn-sm btn-outline-danger cancel-btn" data-booking-id="${appointment.booking_id}">Cancel</button>` : 
                            ''
                        }
                    </div>
                </div>
            `).join('');
        }
    }
    
    function updateClassStats(stats) {
        // Update statistics display
        const statsContainer = document.querySelector('.class-stats');
        if (statsContainer) {
            statsContainer.innerHTML = `
                <div class="stat-item">
                    <h4>${stats.upcoming}</h4>
                    <p>Upcoming Classes</p>
                </div>
                <div class="stat-item">
                    <h4>${stats.completed}</h4>
                    <p>Classes Completed</p>
                </div>
                <div class="stat-item">
                    <h4>${stats.total}</h4>
                    <p>Total Bookings</p>
                </div>
            `;
        }
    }

    function displayPastAppointments(appointments, container) {
        container.innerHTML = `
            <div class="past-appointments">
                <h3>Past Appointments</h3>
                <div class="appointment-history">
                    ${appointments.map(appointment => `
                        <div class="past-appointment-item" data-type="${appointment.type}">
                            <div class="appointment-summary">
                                <div class="appointment-header">
                                    <h4>${appointment.title}</h4>
                                    <span class="appointment-date">${appointment.formatted_date}</span>
                                </div>
                                <div class="appointment-details">
                                    <p><strong>Time:</strong> ${appointment.start_time_formatted} - ${appointment.end_time_formatted}</p>
                                    <p><strong>Instructor:</strong> ${appointment.instructor_name}</p>
                                    <p><strong>Location:</strong> ${appointment.room}</p>
                                    <p><strong>Status:</strong> <span class="status-${appointment.booking_status.toLowerCase()}">${appointment.booking_status.replace('_', ' ')}</span></p>
                                </div>
                            </div>
                            <div class="appointment-actions">
                                ${appointment.booking_status === 'completed' ? 
                                    `<button class="btn btn-sm btn-outline-primary rate-session" 
                                             data-booking-id="${appointment.booking_id}" 
                                             data-type="${appointment.type}">
                                        Rate Session
                                    </button>` : ''
                                }
                                <button class="btn btn-sm btn-outline-secondary book-again" 
                                        data-type="${appointment.type}" 
                                        data-title="${appointment.title}">
                                    Book Again
                                </button>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;

        // Add event listeners for past appointment actions
        const rateButtons = container.querySelectorAll('.rate-session');
        rateButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const bookingId = this.getAttribute('data-booking-id');
                const type = this.getAttribute('data-type');
                rateSession(bookingId, type);
            });
        });

        const bookAgainButtons = container.querySelectorAll('.book-again');
        bookAgainButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const type = this.getAttribute('data-type');
                const title = this.getAttribute('data-title');
                bookAgain(type, title);
            });
        });
    }

    function cancelAppointment(button) {
        const bookingId = button.getAttribute('data-booking-id');
        const appointmentType = button.getAttribute('data-type');
        const appointmentElement = button.closest('.class-booking-card, .class-booking, .appointment-item');
        const appointmentName = appointmentElement.querySelector('h3, .class-title').textContent;
        
        if (!confirm(`Are you sure you want to cancel "${appointmentName}"?`)) {
            return;
        }
        
        button.disabled = true;
        button.textContent = 'Cancelling...';
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const apiEndpoint = appointmentType === 'class' ? 'php/classes/cancel.php' : 'php/trainers/cancel.php';
        
        fetch(apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                booking_id: bookingId,
                csrf_token: csrfToken,
                reason: 'User cancelled from dashboard'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Animate removal
                appointmentElement.style.opacity = '0';
                appointmentElement.style.transform = 'translateX(-100%)';
                
                setTimeout(() => {
                    appointmentElement.remove();
                    showDashboardNotification(data.message || 'Appointment cancelled successfully.', 'success');
                    
                    // Refresh dashboard data
                    loadClassData();
                }, 300);
            } else {
                button.disabled = false;
                button.textContent = 'Cancel';
                showDashboardNotification(data.message || 'Failed to cancel appointment.', 'error');
            }
        })
        .catch(error => {
            console.error('Error cancelling appointment:', error);
            button.disabled = false;
            button.textContent = 'Cancel';
            showDashboardNotification('Failed to cancel appointment. Please try again.', 'error');
        });
    }

    function rescheduleAppointment(button) {
        const bookingId = button.getAttribute('data-booking-id');
        const appointmentType = button.getAttribute('data-type');
        const appointmentName = button.closest('.class-booking').querySelector('h3').textContent;
        
        showDashboardNotification(`Rescheduling for ${appointmentType}s is coming soon! Please cancel and book a new time for now.`, 'info');
    }

    function showDashboardNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `dashboard-notification dashboard-notification-${type}`;
        notification.textContent = message;

        // Add to dashboard
        const dashboardMain = document.querySelector('.dashboard-main');
        if (dashboardMain) {
            dashboardMain.appendChild(notification);
        } else {
            document.body.appendChild(notification);
        }

        // Show with animation
        setTimeout(() => notification.classList.add('show'), 10);

        // Remove after delay
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 5000);
    }

    function rateSession(bookingId, type) {
        const modal = createModal('Rate Your Session', `
            <div class="rating-form">
                <h3>How was your session?</h3>
                <div class="rating-stars">
                    <span class="star" data-rating="1">⭐</span>
                    <span class="star" data-rating="2">⭐</span>
                    <span class="star" data-rating="3">⭐</span>
                    <span class="star" data-rating="4">⭐</span>
                    <span class="star" data-rating="5">⭐</span>
                </div>
                <textarea placeholder="Leave a comment about your experience..." rows="4"></textarea>
                <div class="form-actions">
                    <button type="button" class="btn btn-primary submit-rating">Submit Rating</button>
                    <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                </div>
            </div>
        `);

        let selectedRating = 0;
        
        // Handle star rating
        const stars = modal.querySelectorAll('.star');
        stars.forEach(star => {
            star.addEventListener('click', function() {
                selectedRating = parseInt(this.getAttribute('data-rating'));
                
                // Update visual state
                stars.forEach((s, index) => {
                    if (index < selectedRating) {
                        s.style.color = '#ffc107';
                    } else {
                        s.style.color = '#dee2e6';
                    }
                });
            });
        });

        // Handle form submission
        modal.querySelector('.submit-rating').addEventListener('click', function() {
            if (selectedRating === 0) {
                showDashboardNotification('Please select a star rating', 'error');
                return;
            }

            const comment = modal.querySelector('textarea').value;
            
            // Here you would submit the rating to the API
            showDashboardNotification('Thank you for your feedback!', 'success');
            closeModal(modal);
        });

        showModal(modal);
    }

    function bookAgain(type, title) {
        if (type === 'class') {
            // Navigate to classes page or show booking modal
            navigateToTab('classes');
            setTimeout(() => {
                const bookTab = document.querySelector('[data-tab="book-new"]');
                if (bookTab) bookTab.click();
            }, 100);
            showDashboardNotification(`Looking for more "${title}" classes...`, 'info');
        } else {
            // Navigate to trainers page
            window.location.href = 'trainers.php';
        }
    }

    // Export functions for external use
    window.Dashboard = {
        navigateToTab,
        refreshDashboardData,
        showModal,
        closeModal,
        loadAvailableClasses,
        loadClassData
    };

})();