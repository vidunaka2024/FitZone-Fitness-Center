// Admin Dashboard JavaScript
class AdminDashboard {
    constructor() {
        this.currentSection = 'dashboard';
        this.currentPage = 1;
        this.currentFilters = {};
        this.csrfToken = null;
        this.init();
    }

    async init() {
        // Initialize navigation
        this.initNavigation();
        
        // Get CSRF token
        await this.getCSRFToken();
        
        // Load initial dashboard data
        await this.loadDashboard();
        
        // Setup search and filter handlers
        this.initFilters();
    }

    initNavigation() {
        const navLinks = document.querySelectorAll('.nav-link[data-section]');
        
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = link.getAttribute('data-section');
                this.showSection(section);
            });
        });
    }

    async getCSRFToken() {
        try {
            // In a real application, this would be provided by the server
            this.csrfToken = 'demo-csrf-token-' + Math.random().toString(36).substr(2, 9);
            document.getElementById('csrf_token').value = this.csrfToken;
        } catch (error) {
            console.error('Failed to get CSRF token:', error);
        }
    }

    showSection(section) {
        // Update navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        document.querySelector(`[data-section="${section}"]`).classList.add('active');

        // Hide all sections
        document.querySelectorAll('.content-section').forEach(section => {
            section.style.display = 'none';
        });

        // Show selected section
        const sectionElement = document.getElementById(`${section}-section`);
        if (sectionElement) {
            sectionElement.style.display = 'block';
            this.currentSection = section;
        }

        // Load section data
        this.loadSectionData(section);
    }

    async loadSectionData(section) {
        switch (section) {
            case 'dashboard':
                await this.loadDashboard();
                break;
            case 'users':
                await this.loadUsers();
                break;
            case 'memberships':
                await this.loadMemberships();
                break;
            case 'classes':
                await this.loadClasses();
                break;
            default:
                console.log(`Loading ${section} section...`);
        }
    }

    async loadDashboard() {
        try {
            // Load stats
            await this.loadStats();
            
            // Load recent activity
            await this.loadRecentActivity();
        } catch (error) {
            console.error('Failed to load dashboard:', error);
        }
    }

    async loadStats() {
        try {
            // Simulate API call
            const stats = await this.mockApiCall('stats');
            
            document.getElementById('total-users').textContent = stats.total_users || '150';
            document.getElementById('active-members').textContent = stats.active_members || '120';
            document.getElementById('monthly-revenue').textContent = '$' + (stats.monthly_revenue || '12,450');
            document.getElementById('total-classes').textContent = stats.total_classes || '45';
        } catch (error) {
            console.error('Failed to load stats:', error);
        }
    }

    async loadRecentActivity() {
        try {
            const activities = await this.mockApiCall('recent-activity');
            
            const container = document.getElementById('recent-activity');
            
            if (activities.length === 0) {
                container.innerHTML = '<p class="text-center text-muted">No recent activity</p>';
                return;
            }

            const tableHTML = `
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Time</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${activities.map(activity => `
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-details">
                                            <h6>${activity.user_name}</h6>
                                            <p>${activity.user_email}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>${activity.action}</td>
                                <td>${this.formatDateTime(activity.created_at)}</td>
                                <td>${activity.ip_address}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
            
            container.innerHTML = tableHTML;
        } catch (error) {
            console.error('Failed to load recent activity:', error);
            document.getElementById('recent-activity').innerHTML = 
                '<p class="text-center text-danger">Failed to load recent activity</p>';
        }
    }

    async loadUsers() {
        try {
            const filters = this.getCurrentFilters();
            const users = await this.mockApiCall('users', { ...filters, page: this.currentPage });
            
            this.renderUsersTable(users.users || []);
            this.renderPagination(users.pagination || {});
        } catch (error) {
            console.error('Failed to load users:', error);
            this.showError('users-table-body', 'Failed to load users');
        }
    }

    renderUsersTable(users) {
        const tbody = document.getElementById('users-table-body');
        
        if (users.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No users found</td></tr>';
            return;
        }

        tbody.innerHTML = users.map(user => `
            <tr>
                <td>
                    <div class="user-info">
                        <img src="${user.profile_picture || 'uploads/profile-pics/default-avatar.jpg'}" 
                             alt="${user.first_name}" class="user-avatar">
                        <div class="user-details">
                            <h6>${user.first_name} ${user.last_name}</h6>
                            <p>${user.email}</p>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge badge-${this.getRoleBadgeClass(user.role)}">${user.role}</span>
                </td>
                <td>
                    <span class="badge badge-${this.getStatusBadgeClass(user.status)}">${user.status}</span>
                </td>
                <td>${user.membership_plan || '-'}</td>
                <td>${this.formatDate(user.created_at)}</td>
                <td>${user.last_login ? this.formatDateTime(user.last_login) : 'Never'}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-sm btn-edit" onclick="dashboard.editUser(${user.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-sm btn-reset" onclick="dashboard.resetUserPassword(${user.id})" title="Reset Password">
                            <i class="fas fa-key"></i>
                        </button>
                        <button class="btn-sm btn-delete" onclick="dashboard.deleteUser(${user.id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    getRoleBadgeClass(role) {
        const classes = {
            'admin': 'danger',
            'staff': 'warning', 
            'trainer': 'info',
            'member': 'primary'
        };
        return classes[role] || 'secondary';
    }

    getStatusBadgeClass(status) {
        const classes = {
            'active': 'success',
            'inactive': 'secondary',
            'suspended': 'danger',
            'pending': 'warning'
        };
        return classes[status] || 'secondary';
    }

    renderPagination(pagination) {
        const container = document.getElementById('users-pagination');
        
        if (!pagination.total_pages || pagination.total_pages <= 1) {
            container.innerHTML = '';
            return;
        }

        let paginationHTML = '<div class="pagination-wrapper">';
        
        // Previous button
        if (pagination.has_previous) {
            paginationHTML += `<button class="btn-secondary" onclick="dashboard.changePage(${pagination.previous_page})">Previous</button>`;
        }

        // Page numbers
        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);

        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === pagination.current_page ? 'btn-primary' : 'btn-secondary';
            paginationHTML += `<button class="${activeClass}" onclick="dashboard.changePage(${i})">${i}</button>`;
        }

        // Next button
        if (pagination.has_next) {
            paginationHTML += `<button class="btn-secondary" onclick="dashboard.changePage(${pagination.next_page})">Next</button>`;
        }

        paginationHTML += '</div>';
        container.innerHTML = paginationHTML;
    }

    changePage(page) {
        this.currentPage = page;
        this.loadUsers();
    }

    getCurrentFilters() {
        return {
            search: document.getElementById('user-search')?.value || '',
            role: document.getElementById('role-filter')?.value || '',
            status: document.getElementById('status-filter')?.value || ''
        };
    }

    initFilters() {
        const searchInput = document.getElementById('user-search');
        const roleFilter = document.getElementById('role-filter');
        const statusFilter = document.getElementById('status-filter');

        // Debounced search
        let searchTimeout;
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.currentPage = 1;
                    this.loadUsers();
                }, 500);
            });
        }

        // Filter changes
        [roleFilter, statusFilter].forEach(filter => {
            if (filter) {
                filter.addEventListener('change', () => {
                    this.currentPage = 1;
                    this.loadUsers();
                });
            }
        });
    }

    // User Management Methods
    showCreateUserModal() {
        document.getElementById('userModalTitle').textContent = 'Create New User';
        document.getElementById('userForm').reset();
        document.getElementById('userId').value = '';
        document.getElementById('passwordGroup').style.display = 'block';
        document.getElementById('userModal').style.display = 'block';
    }

    closeUserModal() {
        document.getElementById('userModal').style.display = 'none';
    }

    async editUser(userId) {
        try {
            const user = await this.mockApiCall(`user/${userId}`);
            
            document.getElementById('userModalTitle').textContent = 'Edit User';
            document.getElementById('userId').value = user.id;
            document.getElementById('firstName').value = user.first_name;
            document.getElementById('lastName').value = user.last_name;
            document.getElementById('email').value = user.email;
            document.getElementById('phone').value = user.phone || '';
            document.getElementById('role').value = user.role;
            document.getElementById('status').value = user.status;
            document.getElementById('membershipPlan').value = user.membership_plan || 'basic';
            
            // Hide password field for editing
            document.getElementById('passwordGroup').style.display = 'none';
            
            document.getElementById('userModal').style.display = 'block';
        } catch (error) {
            console.error('Failed to load user:', error);
            this.showAlert('Failed to load user details', 'error');
        }
    }

    async saveUser() {
        try {
            const form = document.getElementById('userForm');
            const formData = new FormData(form);
            const userId = formData.get('user_id');
            
            const userData = Object.fromEntries(formData.entries());
            
            if (userId) {
                // Update user
                await this.mockApiCall(`user/${userId}`, userData, 'PUT');
                this.showAlert('User updated successfully', 'success');
            } else {
                // Create user
                await this.mockApiCall('users', userData, 'POST');
                this.showAlert('User created successfully', 'success');
            }
            
            this.closeUserModal();
            this.loadUsers();
        } catch (error) {
            console.error('Failed to save user:', error);
            this.showAlert('Failed to save user', 'error');
        }
    }

    async resetUserPassword(userId) {
        if (!confirm('Are you sure you want to reset this user\'s password?')) {
            return;
        }

        try {
            const result = await this.mockApiCall(`user/${userId}/reset-password`, {}, 'POST');
            this.showAlert('Password reset successfully. New password sent to user.', 'success');
        } catch (error) {
            console.error('Failed to reset password:', error);
            this.showAlert('Failed to reset password', 'error');
        }
    }

    async deleteUser(userId) {
        if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            return;
        }

        try {
            await this.mockApiCall(`user/${userId}`, {}, 'DELETE');
            this.showAlert('User deleted successfully', 'success');
            this.loadUsers();
        } catch (error) {
            console.error('Failed to delete user:', error);
            this.showAlert('Failed to delete user', 'error');
        }
    }

    // Utility Methods
    async mockApiCall(endpoint, data = {}, method = 'GET') {
        // Mock API responses for demonstration
        await new Promise(resolve => setTimeout(resolve, 500)); // Simulate network delay

        switch (endpoint) {
            case 'stats':
                return {
                    total_users: 150,
                    active_members: 120,
                    monthly_revenue: 12450,
                    total_classes: 45
                };

            case 'recent-activity':
                return [
                    {
                        user_name: 'John Doe',
                        user_email: 'john@example.com',
                        action: 'User login',
                        created_at: '2024-01-15 10:30:00',
                        ip_address: '192.168.1.100'
                    },
                    {
                        user_name: 'Jane Smith',
                        user_email: 'jane@example.com',
                        action: 'Class booking',
                        created_at: '2024-01-15 09:15:00',
                        ip_address: '192.168.1.101'
                    }
                ];

            case 'users':
                return {
                    users: [
                        {
                            id: 1,
                            first_name: 'John',
                            last_name: 'Doe',
                            email: 'john@example.com',
                            role: 'member',
                            status: 'active',
                            membership_plan: 'premium',
                            created_at: '2024-01-01',
                            last_login: '2024-01-15 10:30:00',
                            profile_picture: null
                        },
                        {
                            id: 2,
                            first_name: 'Jane',
                            last_name: 'Smith',
                            email: 'jane@example.com',
                            role: 'trainer',
                            status: 'active',
                            membership_plan: null,
                            created_at: '2024-01-02',
                            last_login: '2024-01-15 09:15:00',
                            profile_picture: null
                        },
                        {
                            id: 3,
                            first_name: 'Admin',
                            last_name: 'User',
                            email: 'admin@fitzonecenter.com',
                            role: 'admin',
                            status: 'active',
                            membership_plan: null,
                            created_at: '2024-01-01',
                            last_login: '2024-01-15 11:00:00',
                            profile_picture: null
                        }
                    ],
                    pagination: {
                        current_page: 1,
                        total_pages: 1,
                        has_previous: false,
                        has_next: false
                    }
                };

            default:
                return { success: true };
        }
    }

    formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString();
    }

    formatDateTime(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }

    showAlert(message, type = 'info') {
        // Create alert element
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.style.cssText = `
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
        `;

        // Set background color based on type
        const colors = {
            success: '#28a745',
            error: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8'
        };
        alert.style.backgroundColor = colors[type] || colors.info;

        alert.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" 
                        style="background: none; border: none; color: white; font-size: 1.2rem; cursor: pointer; margin-left: 1rem;">
                    ×
                </button>
            </div>
        `;

        document.body.appendChild(alert);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }

    showError(elementId, message) {
        const element = document.getElementById(elementId);
        if (element) {
            element.innerHTML = `<tr><td colspan="7" class="text-center text-danger">${message}</td></tr>`;
        }
    }

    // Mock methods for other sections
    async loadMemberships() {
        console.log('Loading memberships...');
    }

    async loadClasses() {
        console.log('Loading classes...');
    }
}

// Global logout function
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'php/auth/logout.php';
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.dashboard = new AdminDashboard();
});

// Handle modal clicks outside content
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
});

// Handle escape key for modals
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (modal.style.display === 'block') {
                modal.style.display = 'none';
            }
        });
    }
});