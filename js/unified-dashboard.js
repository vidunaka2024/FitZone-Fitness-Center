// Unified Dashboard JavaScript
class UnifiedDashboard {
    constructor() {
        this.currentUser = null;
        this.currentPage = 'overview';
        this.isAuthenticated = false;
        this.init();
    }

    async init() {
        // Check authentication status
        await this.checkAuth();
        
        if (!this.isAuthenticated) {
            this.redirectToLogin();
            return;
        }

        // Initialize dashboard
        this.initializeNavigation();
        this.loadUserData();
        this.loadDashboardData();
        this.setupEventListeners();
    }

    async checkAuth() {
        try {
            // Simulate auth check - in real app, this would call your PHP auth endpoint
            const sessionData = localStorage.getItem('fitzone_session');
            if (sessionData) {
                const session = JSON.parse(sessionData);
                if (session.expires > Date.now()) {
                    this.isAuthenticated = true;
                    this.currentUser = session.user;
                    return;
                }
            }
            
            // Mock authentication for demo
            this.isAuthenticated = true;
            this.currentUser = {
                id: 1,
                name: 'John Doe',
                email: 'john@example.com',
                role: 'member',
                avatar: 'uploads/profile-pics/default-avatar.jpg',
                membership: 'premium'
            };
            
        } catch (error) {
            console.error('Auth check failed:', error);
            this.isAuthenticated = false;
        }
    }

    redirectToLogin() {
        window.location.href = 'login.html';
    }

    loadUserData() {
        if (!this.currentUser) return;

        document.getElementById('userName').textContent = this.currentUser.name;
        document.getElementById('userRole').textContent = this.formatRole(this.currentUser.role);
        document.getElementById('userAvatar').src = this.currentUser.avatar;

        // Show admin section if user is admin
        if (this.currentUser.role === 'admin' || this.currentUser.role === 'staff') {
            document.getElementById('adminSection').style.display = 'block';
        }
    }

    formatRole(role) {
        const roles = {
            'member': 'Member',
            'trainer': 'Personal Trainer',
            'staff': 'Staff Member',
            'admin': 'Administrator'
        };
        return roles[role] || 'Member';
    }

    initializeNavigation() {
        const navLinks = document.querySelectorAll('.nav-link[data-page]');
        
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = link.getAttribute('data-page');
                this.showPage(page);
            });
        });
    }

    showPage(pageId) {
        // Update navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        
        const activeLink = document.querySelector(`[data-page="${pageId}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }

        // Hide all pages
        document.querySelectorAll('.page-content').forEach(page => {
            page.classList.add('hidden');
        });

        // Show selected page
        const targetPage = document.getElementById(`page-${pageId}`);
        if (targetPage) {
            targetPage.classList.remove('hidden');
            this.currentPage = pageId;
        }

        // Update page title
        this.updatePageTitle(pageId);

        // Load page-specific data
        this.loadPageData(pageId);

        // Update URL hash
        window.location.hash = pageId;
    }

    updatePageTitle(pageId) {
        const titles = {
            'overview': 'Dashboard Overview',
            'profile': 'My Profile',
            'classes': 'Fitness Classes',
            'trainers': 'Personal Trainers',
            'workouts': 'My Workouts',
            'progress': 'Progress Tracking',
            'membership': 'My Membership',
            'billing': 'Billing & Payments',
            'settings': 'Settings',
            'admin-users': 'User Management',
            'admin-classes': 'Class Management',
            'admin-reports': 'Reports'
        };
        
        document.getElementById('pageTitle').textContent = titles[pageId] || 'Dashboard';
    }

    async loadPageData(pageId) {
        switch (pageId) {
            case 'overview':
                await this.loadOverviewData();
                break;
            case 'classes':
                await this.loadClasses();
                break;
            case 'trainers':
                await this.loadTrainers();
                break;
            case 'workouts':
                await this.loadWorkouts();
                break;
            case 'membership':
                await this.loadMembershipData();
                break;
            default:
                console.log(`Loading ${pageId} data...`);
        }
    }

    async loadDashboardData() {
        await this.loadOverviewData();
    }

    async loadOverviewData() {
        try {
            // Load stats
            const stats = await this.fetchMockData('stats');
            this.updateStats(stats);

            // Load upcoming classes
            const upcomingClasses = await this.fetchMockData('upcoming-classes');
            this.renderUpcomingClasses(upcomingClasses);

        } catch (error) {
            console.error('Failed to load overview data:', error);
        }
    }

    updateStats(stats) {
        document.getElementById('workoutsThisMonth').textContent = stats.workouts || '12';
        document.getElementById('classesBooked').textContent = stats.classes || '8';
        document.getElementById('caloriesBurned').textContent = stats.calories || '2,340';
        document.getElementById('membershipDays').textContent = stats.days || '127';
        document.getElementById('upcomingClasses').textContent = stats.upcoming || '3';
    }

    renderUpcomingClasses(classes) {
        const container = document.getElementById('upcomingClassesList');
        
        if (!classes || classes.length === 0) {
            container.innerHTML = '<p class="text-muted">No upcoming classes booked</p>';
            return;
        }

        const classesHTML = classes.map(cls => `
            <div class="activity-item">
                <div class="activity-icon" style="background: ${this.getClassIconColor(cls.type)};">
                    <i class="${this.getClassIcon(cls.type)}"></i>
                </div>
                <div class="activity-content">
                    <h4 class="activity-title">${cls.name}</h4>
                    <p class="activity-subtitle">${cls.date} at ${cls.time} with ${cls.trainer}</p>
                </div>
                <div class="activity-time">
                    <button class="btn btn-outline btn-sm" onclick="dashboard.cancelClass(${cls.id})">Cancel</button>
                </div>
            </div>
        `).join('');

        container.innerHTML = classesHTML;
    }

    async loadClasses() {
        const container = document.getElementById('classGrid');
        container.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading classes...</div>';

        try {
            const classes = await this.fetchMockData('all-classes');
            this.renderClassGrid(classes);
        } catch (error) {
            container.innerHTML = '<p class="text-danger">Failed to load classes</p>';
        }
    }

    renderClassGrid(classes) {
        const container = document.getElementById('classGrid');
        
        const classesHTML = classes.map(cls => `
            <div class="class-card">
                <img src="${cls.image}" alt="${cls.name}" class="class-image" 
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="class-image" style="display: none; align-items: center; justify-content: center; color: white; font-size: 3rem;">
                    <i class="${this.getClassIcon(cls.type)}"></i>
                </div>
                <div class="class-info">
                    <h3 class="class-name">${cls.name}</h3>
                    <div class="class-details">
                        <span><i class="fas fa-clock"></i> ${cls.duration} min</span>
                        <span><i class="fas fa-users"></i> ${cls.capacity} spots</span>
                        <span><i class="fas fa-signal"></i> ${cls.level}</span>
                    </div>
                    <p style="color: #6c757d; font-size: 0.9rem; margin-bottom: 1rem;">${cls.description}</p>
                    <div class="class-actions">
                        <button class="btn btn-primary" onclick="dashboard.bookClass(${cls.id})">
                            Book Class
                        </button>
                        <button class="btn btn-outline" onclick="dashboard.viewClassDetails(${cls.id})">
                            Details
                        </button>
                    </div>
                </div>
            </div>
        `).join('');

        container.innerHTML = classesHTML;
    }

    async loadTrainers() {
        const container = document.getElementById('trainersContent');
        
        try {
            const trainers = await this.fetchMockData('trainers');
            this.renderTrainers(trainers);
        } catch (error) {
            container.innerHTML = '<p class="text-danger">Failed to load trainers</p>';
        }
    }

    renderTrainers(trainers) {
        const container = document.getElementById('trainersContent');
        
        const trainersHTML = `
            <div class="class-grid">
                ${trainers.map(trainer => `
                    <div class="class-card">
                        <img src="${trainer.image}" alt="${trainer.name}" class="class-image" 
                             onerror="this.src='uploads/profile-pics/default-avatar.jpg'">
                        <div class="class-info">
                            <h3 class="class-name">${trainer.name}</h3>
                            <div class="class-details">
                                <span><i class="fas fa-star"></i> ${trainer.rating}/5</span>
                                <span><i class="fas fa-medal"></i> ${trainer.experience}y exp</span>
                                <span><i class="fas fa-dollar-sign"></i> $${trainer.rate}/hr</span>
                            </div>
                            <p style="color: #6c757d; font-size: 0.9rem; margin-bottom: 1rem;">${trainer.specialization}</p>
                            <div class="class-actions">
                                <button class="btn btn-primary" onclick="dashboard.bookTrainer(${trainer.id})">
                                    Book Session
                                </button>
                                <button class="btn btn-outline" onclick="dashboard.viewTrainerProfile(${trainer.id})">
                                    View Profile
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;

        container.innerHTML = trainersHTML;
    }

    // Utility methods
    getClassIcon(type) {
        const icons = {
            'cardio': 'fas fa-heartbeat',
            'strength': 'fas fa-dumbbell',
            'flexibility': 'fas fa-hand-peace',
            'dance': 'fas fa-music',
            'martial_arts': 'fas fa-fist-raised',
            'water': 'fas fa-swimmer',
            'mind_body': 'fas fa-leaf'
        };
        return icons[type] || 'fas fa-dumbbell';
    }

    getClassIconColor(type) {
        const colors = {
            'cardio': 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
            'strength': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'flexibility': 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
            'dance': 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
            'martial_arts': 'linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%)',
            'water': 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
            'mind_body': 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)'
        };
        return colors[type] || 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
    }

    // Mock data fetching
    async fetchMockData(endpoint) {
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 500));

        const mockData = {
            'stats': {
                workouts: 15,
                classes: 6,
                calories: 2850,
                days: 134,
                upcoming: 3
            },
            'upcoming-classes': [
                {
                    id: 1,
                    name: 'Morning Yoga',
                    date: 'Tomorrow',
                    time: '9:00 AM',
                    trainer: 'Sarah Johnson',
                    type: 'mind_body'
                },
                {
                    id: 2,
                    name: 'HIIT Training',
                    date: 'Friday',
                    time: '6:00 PM',
                    trainer: 'Mike Thompson',
                    type: 'cardio'
                },
                {
                    id: 3,
                    name: 'Strength Training',
                    date: 'Saturday',
                    time: '10:00 AM',
                    trainer: 'David Lee',
                    type: 'strength'
                }
            ],
            'all-classes': [
                {
                    id: 1,
                    name: 'Zumba Dance Fitness',
                    type: 'dance',
                    duration: 45,
                    capacity: 25,
                    level: 'All Levels',
                    description: 'High-energy dance fitness combining Latin rhythms with easy-to-follow moves',
                    image: 'images/classes/zumba.jpg'
                },
                {
                    id: 2,
                    name: 'Hatha Yoga',
                    type: 'mind_body',
                    duration: 60,
                    capacity: 20,
                    level: 'Beginner',
                    description: 'Gentle yoga practice focusing on basic postures and breathing techniques',
                    image: 'images/classes/yoga.jpg'
                },
                {
                    id: 3,
                    name: 'CrossFit WOD',
                    type: 'strength',
                    duration: 50,
                    capacity: 15,
                    level: 'Advanced',
                    description: 'High-intensity functional fitness workouts for serious athletes',
                    image: 'images/classes/crossfit.jpg'
                },
                {
                    id: 4,
                    name: 'Indoor Cycling',
                    type: 'cardio',
                    duration: 45,
                    capacity: 20,
                    level: 'Intermediate',
                    description: 'Energetic indoor cycling class with varied terrain simulation',
                    image: 'images/classes/spinning.jpg'
                },
                {
                    id: 5,
                    name: 'Pilates Core',
                    type: 'flexibility',
                    duration: 55,
                    capacity: 18,
                    level: 'All Levels',
                    description: 'Core-focused exercises improving strength, flexibility, and posture',
                    image: 'images/classes/pilates.jpg'
                },
                {
                    id: 6,
                    name: 'Bootcamp',
                    type: 'mixed',
                    duration: 50,
                    capacity: 20,
                    level: 'Intermediate',
                    description: 'Military-style workout combining cardio and strength training',
                    image: 'images/classes/bootcamp.jpg'
                }
            ],
            'trainers': [
                {
                    id: 1,
                    name: 'Sarah Johnson',
                    specialization: 'Yoga & Flexibility, Mind-Body Connection',
                    experience: 8,
                    rating: 4.9,
                    rate: 75,
                    image: 'images/trainers/trainer-1.jpg'
                },
                {
                    id: 2,
                    name: 'Mike Thompson',
                    specialization: 'Strength Training & HIIT',
                    experience: 6,
                    rating: 4.8,
                    rate: 80,
                    image: 'images/trainers/trainer-2.jpg'
                },
                {
                    id: 3,
                    name: 'Maria Rodriguez',
                    specialization: 'Dance Fitness & Cardio',
                    experience: 5,
                    rating: 4.7,
                    rate: 70,
                    image: 'images/trainers/trainer-3.jpg'
                },
                {
                    id: 4,
                    name: 'David Lee',
                    specialization: 'CrossFit & Functional Training',
                    experience: 10,
                    rating: 4.9,
                    rate: 85,
                    image: 'images/trainers/trainer-4.jpg'
                }
            ]
        };

        return mockData[endpoint] || [];
    }

    // Action methods
    async bookClass(classId) {
        try {
            // Simulate booking API call
            const success = await this.mockApiCall('book-class', { classId });
            
            if (success) {
                this.showNotification('Class booked successfully!', 'success');
                // Refresh data
                this.loadOverviewData();
            } else {
                this.showNotification('Failed to book class. Please try again.', 'error');
            }
        } catch (error) {
            this.showNotification('An error occurred while booking the class.', 'error');
        }
    }

    async cancelClass(classId) {
        if (!confirm('Are you sure you want to cancel this class booking?')) {
            return;
        }

        try {
            const success = await this.mockApiCall('cancel-class', { classId });
            
            if (success) {
                this.showNotification('Class booking cancelled successfully!', 'success');
                this.loadOverviewData();
            } else {
                this.showNotification('Failed to cancel booking. Please try again.', 'error');
            }
        } catch (error) {
            this.showNotification('An error occurred while cancelling the booking.', 'error');
        }
    }

    bookTrainer(trainerId) {
        this.showNotification('Trainer booking feature coming soon!', 'info');
    }

    viewClassDetails(classId) {
        this.showNotification('Class details modal coming soon!', 'info');
    }

    viewTrainerProfile(trainerId) {
        this.showNotification('Trainer profile modal coming soon!', 'info');
    }

    async mockApiCall(endpoint, data) {
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 1000));
        return Math.random() > 0.1; // 90% success rate
    }

    setupEventListeners() {
        // Handle hash changes for direct navigation
        window.addEventListener('hashchange', () => {
            const hash = window.location.hash.substr(1);
            if (hash) {
                this.showPage(hash);
            }
        });

        // Load initial page from hash
        const initialHash = window.location.hash.substr(1);
        if (initialHash) {
            this.showPage(initialHash);
        }
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 9999;
            min-width: 300px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;

        // Set background color based on type
        const colors = {
            success: '#28a745',
            error: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8'
        };
        notification.style.backgroundColor = colors[type] || colors.info;

        notification.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" 
                        style="background: none; border: none; color: white; font-size: 1.2rem; cursor: pointer; margin-left: 1rem;">
                    ×
                </button>
            </div>
        `;

        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
    }
}

// Global functions
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    
    sidebar.classList.toggle('active');
    mainContent.classList.toggle('expanded');
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        localStorage.removeItem('fitzone_session');
        window.location.href = 'login.html';
    }
}

// Initialize dashboard
document.addEventListener('DOMContentLoaded', () => {
    window.dashboard = new UnifiedDashboard();
});

// Handle clicks outside sidebar on mobile
document.addEventListener('click', (e) => {
    const sidebar = document.getElementById('sidebar');
    const mobileToggle = document.querySelector('.mobile-toggle');
    
    if (window.innerWidth <= 768 && 
        sidebar.classList.contains('active') && 
        !sidebar.contains(e.target) && 
        !mobileToggle.contains(e.target)) {
        toggleSidebar();
    }
});