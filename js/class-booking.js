// FitZone Fitness Center - Class Booking System

(function() {
    'use strict';

    // Global variables
    let currentFilters = {
        search: '',
        level: '',
        type: ''
    };
    let currentSelectedClass = null;

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initializeClassBooking();
        loadClasses();
        initializeFilters();
        initializeModal();
    });

    function initializeClassBooking() {
        // Check if user is logged in
        checkAuthStatus();
    }

    function checkAuthStatus() {
        // This would typically check session or make an API call
        // For now, we'll assume user authentication is handled by PHP sessions
        console.log('Authentication check completed');
    }

    function loadClasses() {
        console.log('loadClasses() called');
        const loadingMessage = document.getElementById('loading-message');
        const classesContainer = document.getElementById('classes-container');

        console.log('Loading message element:', loadingMessage);
        console.log('Classes container element:', classesContainer);

        if (loadingMessage) {
            loadingMessage.style.display = 'block';
        }

        // Build query parameters
        const params = new URLSearchParams();
        if (currentFilters.type) params.append('type', currentFilters.type);
        if (currentFilters.level) params.append('level', currentFilters.level);
        params.append('available_only', 'true');
        params.append('limit', '20');

        const apiUrl = `php/classes/list.php?${params.toString()}`;
        console.log('Making API call to:', apiUrl);

        fetch(apiUrl)
            .then(response => {
                console.log('Response received:', response.status, response.statusText);
                return response.json();
            })
            .then(data => {
                console.log('API response data:', data);
                if (loadingMessage) {
                    loadingMessage.style.display = 'none';
                }

                if (data.success && data.data.classes) {
                    console.log('Found classes:', data.data.classes.length);
                    displayClasses(data.data.classes);
                    updatePagination(data.data.pagination);
                } else {
                    console.log('No classes found or API error');
                    displayError('Failed to load classes. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error loading classes:', error);
                if (loadingMessage) {
                    loadingMessage.style.display = 'none';
                }
                displayError('Failed to load classes. Please check your connection.');
            });
    }

    function displayClasses(classes) {
        const container = document.getElementById('classes-container');
        if (!container) return;

        if (classes.length === 0) {
            container.innerHTML = '<div class="no-classes"><p>No classes found matching your criteria.</p></div>';
            return;
        }

        container.innerHTML = classes.map(classData => `
            <div class="class-card" data-schedule-id="${classData.schedule_id}" data-level="${classData.difficulty_level}" data-type="${classData.type}">
                <img src="${classData.image_url || 'images/classes/default.svg'}" alt="${classData.name}" onerror="this.src='images/classes/default.svg'">
                <div class="class-info">
                    <h3>${classData.name}</h3>
                    <p class="class-level">${capitalizeFirst(classData.difficulty_level)}</p>
                    <p class="class-duration">${classData.duration_minutes} minutes</p>
                    <p class="class-description">${classData.description || 'Join this exciting fitness class!'}</p>
                    <div class="class-schedule">
                        <p><strong>Date:</strong> ${classData.formatted_date}</p>
                        <p><strong>Time:</strong> ${classData.start_time} - ${classData.end_time}</p>
                        <p><strong>Instructor:</strong> ${classData.trainer_name}</p>
                        <p><strong>Room:</strong> ${classData.room}</p>
                        <p><strong>Available:</strong> ${classData.spots_available}/${classData.max_capacity} spots</p>
                        ${classData.price > 0 ? `<p class="class-price"><strong>Price:</strong> $${classData.price}</p>` : ''}
                    </div>
                    <div class="booking-status">
                        ${getBookingStatusBadge(classData)}
                    </div>
                    ${getBookingButton(classData)}
                </div>
            </div>
        `).join('');

        // Attach event listeners to booking buttons
        attachBookingListeners();
    }

    function getBookingStatusBadge(classData) {
        if (classData.user_booking_status === 'confirmed') {
            return '<span class="status-badge booked">Already Booked</span>';
        } else if (classData.user_booking_status === 'wait_listed') {
            return '<span class="status-badge waitlisted">Wait Listed</span>';
        } else if (classData.availability_status === 'full') {
            return '<span class="status-badge full">Class Full</span>';
        } else if (classData.availability_status === 'almost_full') {
            return '<span class="status-badge almost-full">Almost Full</span>';
        }
        return '';
    }

    function getBookingButton(classData) {
        if (classData.user_booking_status === 'not_logged_in') {
            return '<a href="login.html" class="btn btn-primary">Login to Book</a>';
        } else if (classData.user_booking_status === 'confirmed') {
            return '<button class="btn btn-secondary" disabled>Already Booked</button>';
        } else if (classData.user_booking_status === 'wait_listed') {
            return '<button class="btn btn-warning" disabled>Wait Listed</button>';
        } else if (classData.spots_available <= 0) {
            return '<button class="btn btn-primary book-class" data-schedule-id="' + classData.schedule_id + '">Join Wait List</button>';
        } else {
            return '<button class="btn btn-primary book-class" data-schedule-id="' + classData.schedule_id + '">Book Class</button>';
        }
    }

    function attachBookingListeners() {
        const bookButtons = document.querySelectorAll('.book-class');
        bookButtons.forEach(button => {
            button.addEventListener('click', function() {
                const scheduleId = this.getAttribute('data-schedule-id');
                const classCard = this.closest('.class-card');
                openBookingModal(scheduleId, classCard);
            });
        });
    }

    function openBookingModal(scheduleId, classCard) {
        const modal = document.getElementById('booking-modal');
        const classDetails = document.getElementById('class-details');
        
        if (!modal || !classDetails) return;

        // Extract class information from the card
        const className = classCard.querySelector('h3').textContent;
        const classLevel = classCard.querySelector('.class-level').textContent;
        const classDuration = classCard.querySelector('.class-duration').textContent;
        const scheduleInfo = classCard.querySelector('.class-schedule').innerHTML;

        classDetails.innerHTML = `
            <div class="booking-class-info">
                <h3>${className}</h3>
                <p class="level-duration">${classLevel} • ${classDuration}</p>
                <div class="schedule-details">${scheduleInfo}</div>
            </div>
        `;

        // Store the schedule ID for booking
        const bookingForm = document.getElementById('booking-form');
        bookingForm.setAttribute('data-schedule-id', scheduleId);

        // Show modal
        modal.style.display = 'block';
        document.body.classList.add('modal-open');

        currentSelectedClass = {
            scheduleId: scheduleId,
            name: className,
            element: classCard
        };
    }

    function initializeModal() {
        const modal = document.getElementById('booking-modal');
        const closeBtn = modal.querySelector('.close');
        const cancelBtn = modal.querySelector('.cancel-booking');
        const bookingForm = document.getElementById('booking-form');

        // Close modal events
        closeBtn.addEventListener('click', closeBookingModal);
        cancelBtn.addEventListener('click', closeBookingModal);

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeBookingModal();
            }
        });

        // Handle form submission
        bookingForm.addEventListener('submit', handleBookingSubmission);
    }

    function closeBookingModal() {
        const modal = document.getElementById('booking-modal');
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        
        // Reset form
        const bookingForm = document.getElementById('booking-form');
        bookingForm.reset();
        
        currentSelectedClass = null;
    }

    function handleBookingSubmission(event) {
        event.preventDefault();
        
        if (!currentSelectedClass) {
            showNotification('Error: No class selected', 'error');
            return;
        }

        const form = event.target;
        const formData = new FormData(form);
        const scheduleId = form.getAttribute('data-schedule-id');
        const submitButton = form.querySelector('#confirm-booking');

        // Disable submit button
        submitButton.disabled = true;
        submitButton.textContent = 'Booking...';

        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Prepare booking data
        const bookingData = new URLSearchParams({
            schedule_id: scheduleId,
            booking_type: formData.get('booking_type'),
            notes: formData.get('notes') || '',
            csrf_token: csrfToken
        });

        // Submit booking
        fetch('php/classes/book.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: bookingData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                closeBookingModal();
                
                // Update the class card to reflect booking status
                updateClassCardAfterBooking(currentSelectedClass.element, data.data);
                
                // Optionally reload classes to get updated availability
                setTimeout(() => loadClasses(), 1000);
            } else {
                showNotification(data.message || 'Booking failed. Please try again.', 'error');
            }
        })
        .catch(error => {
            console.error('Booking error:', error);
            showNotification('Booking failed. Please check your connection and try again.', 'error');
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.textContent = 'Confirm Booking';
        });
    }

    function updateClassCardAfterBooking(classCard, bookingData) {
        const button = classCard.querySelector('.btn');
        const statusContainer = classCard.querySelector('.booking-status');

        if (bookingData.wait_listed) {
            button.textContent = 'Wait Listed';
            button.className = 'btn btn-warning';
            button.disabled = true;
            statusContainer.innerHTML = '<span class="status-badge waitlisted">Wait Listed</span>';
        } else {
            button.textContent = 'Booked';
            button.className = 'btn btn-success';
            button.disabled = true;
            statusContainer.innerHTML = '<span class="status-badge booked">Booked</span>';
            
            // Update spots available
            const spotsElement = classCard.querySelector('.class-schedule p:last-child');
            if (spotsElement) {
                const currentSpots = parseInt(spotsElement.textContent.match(/\d+/)[0]);
                spotsElement.innerHTML = `<strong>Available:</strong> ${Math.max(0, currentSpots - 1)}/${spotsElement.textContent.split('/')[1]}`;
            }
        }
    }

    function initializeFilters() {
        const searchInput = document.getElementById('class-search');
        const levelFilter = document.getElementById('level-filter');
        const typeFilter = document.getElementById('type-filter');
        const searchButton = document.querySelector('.search-box button');

        // Search functionality
        if (searchButton) {
            searchButton.addEventListener('click', handleSearch);
        }

        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    handleSearch();
                }
            });
        }

        // Filter functionality
        if (levelFilter) {
            levelFilter.addEventListener('change', function() {
                currentFilters.level = this.value;
                loadClasses();
            });
        }

        if (typeFilter) {
            typeFilter.addEventListener('change', function() {
                currentFilters.type = this.value;
                loadClasses();
            });
        }
    }

    function handleSearch() {
        const searchInput = document.getElementById('class-search');
        if (searchInput) {
            currentFilters.search = searchInput.value.trim();
            loadClasses();
        }
    }

    function displayError(message) {
        const container = document.getElementById('classes-container');
        if (container) {
            container.innerHTML = `<div class="error-message"><p>${message}</p></div>`;
        }
    }

    function updatePagination(paginationData) {
        const container = document.getElementById('pagination-container');
        if (!container || !paginationData.has_more) return;

        // Simple pagination - could be enhanced
        container.innerHTML = `
            <div class="pagination">
                <p>Showing ${paginationData.limit} of ${paginationData.total} classes</p>
                ${paginationData.has_more ? '<button class="btn btn-secondary" onclick="loadMoreClasses()">Load More</button>' : ''}
            </div>
        `;
    }

    function showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;

        // Add to page
        document.body.appendChild(notification);

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

    function capitalizeFirst(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1).replace('_', ' ');
    }

    // Global search function for backward compatibility
    window.searchClasses = handleSearch;

    // Add notification styles
    const style = document.createElement('style');
    style.textContent = `
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
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

        .close {
            background: none;
            border: none;
            font-size: 2rem;
            cursor: pointer;
            color: #999;
            padding: 0;
            width: 30px;
            height: 30px;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .booking-class-info h3 {
            color: #e74c3c;
            margin-bottom: 0.5rem;
        }

        .level-duration {
            color: #6c757d;
            margin-bottom: 1rem;
        }

        .schedule-details {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .form-group textarea {
            height: 80px;
            resize: vertical;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-badge.booked {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.waitlisted {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.full {
            background: #f8d7da;
            color: #721c24;
        }

        .status-badge.almost-full {
            background: #ffeaa7;
            color: #d68910;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            z-index: 1001;
        }

        .notification.show {
            opacity: 1;
            transform: translateX(0);
        }

        .notification-success {
            background: #28a745;
        }

        .notification-error {
            background: #dc3545;
        }

        .notification-info {
            background: #17a2b8;
        }

        .loading-message {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }

        .error-message {
            text-align: center;
            padding: 2rem;
            color: #dc3545;
        }

        .no-classes {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        body.modal-open {
            overflow: hidden;
        }
    `;
    document.head.appendChild(style);

})();