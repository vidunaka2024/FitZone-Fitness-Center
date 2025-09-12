// FitZone Fitness Center - Trainer Booking System

(function() {
    'use strict';

    // Global variables
    let currentSelectedTrainer = null;
    let trainers = [];

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Trainer booking system initializing...');
        
        initializeTrainerBooking();
        loadTrainers();
        initializeModal();
        setupFormHandlers();
        initializeFilters();
    });

    function initializeTrainerBooking() {
        // Set minimum date to tomorrow
        const dateInput = document.getElementById('appointment-date');
        if (dateInput) {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            dateInput.min = tomorrow.toISOString().split('T')[0];
        }
    }

    function loadTrainers() {
        const loadingMessage = document.getElementById('loading-message');
        const trainersContainer = document.getElementById('trainers-container');

        if (loadingMessage) {
            loadingMessage.style.display = 'block';
        }

        // Fetch real trainers from API
        fetch('php/trainers/list.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    trainers = data.data.trainers;
                    console.log('Loaded', trainers.length, 'trainers successfully');
                    displayTrainers(trainers);
                } else {
                    console.error('API Error:', data.message);
                    showNotification('Failed to load trainers: ' + data.message, 'error');
                    // Fallback to static trainers
                    loadStaticTrainers();
                }
            })
            .catch(error => {
                console.error('Error loading trainers:', error);
                showNotification('Failed to load trainers. Showing default trainers.', 'error');
                // Fallback to static trainers
                loadStaticTrainers();
            })
            .finally(() => {
                if (loadingMessage) {
                    loadingMessage.style.display = 'none';
                }
            });
    }

    function loadStaticTrainers() {
        // Static trainer data (would come from API in production)
        const staticTrainers = [
            {
                id: 1,
                first_name: 'Sarah',
                last_name: 'Johnson',
                title: 'Yoga & Pilates Specialist',
                specializations: ['Hatha Yoga', 'Vinyasa', 'Pilates Reformer'],
                certifications: ['RYT-500', 'PMA-CPT'],
                experience_years: 8,
                hourly_rate: 75,
                bio: 'Sarah brings peace and strength to every session, helping clients find balance through mindful movement and breathing techniques.',
                profile_picture: 'images/trainers/sarah-johnson.jpg',
                is_accepting_clients: true,
                rating: 4.9
            },
            {
                id: 2,
                first_name: 'Mike',
                last_name: 'Thompson',
                title: 'Strength & Conditioning Coach',
                specializations: ['CrossFit', 'Powerlifting', 'Sports Performance'],
                certifications: ['CSCS', 'NASM-CPT'],
                experience_years: 12,
                hourly_rate: 85,
                bio: 'Former collegiate athlete turned coach, Mike specializes in building functional strength and athletic performance for all levels.',
                profile_picture: 'images/trainers/mike-thompson.jpg',
                is_accepting_clients: true,
                rating: 4.8
            },
            {
                id: 3,
                first_name: 'Maria',
                last_name: 'Rodriguez',
                title: 'Dance Fitness Instructor',
                specializations: ['Zumba', 'Latin Dance', 'Cardio Dance'],
                certifications: ['ZIN', 'AFAA'],
                experience_years: 6,
                hourly_rate: 70,
                bio: 'Maria\'s infectious energy and passion for dance makes every workout feel like a party while delivering serious fitness results.',
                profile_picture: 'images/trainers/maria-rodriguez.jpg',
                is_accepting_clients: true,
                rating: 4.7
            },
            {
                id: 4,
                first_name: 'David',
                last_name: 'Lee',
                title: 'Cardio & Weight Loss Specialist',
                specializations: ['HIIT', 'Spinning', 'Fat Loss', 'Endurance'],
                certifications: ['ACE-CPT', 'Spinning Instructor'],
                experience_years: 10,
                hourly_rate: 80,
                bio: 'David\'s evidence-based approach to cardio training has helped hundreds of clients achieve their weight loss and endurance goals.',
                profile_picture: 'images/trainers/david-lee.jpg',
                is_accepting_clients: true,
                rating: 4.9
            },
            {
                id: 5,
                first_name: 'Emma',
                last_name: 'Wilson',
                title: 'Rehabilitation & Mobility Expert',
                specializations: ['Injury Prevention', 'Corrective Exercise', 'Senior Fitness'],
                certifications: ['DPT', 'ACSM-CPT'],
                experience_years: 15,
                hourly_rate: 90,
                bio: 'With a background in physical therapy, Emma specializes in helping clients overcome limitations and build sustainable fitness habits.',
                profile_picture: 'images/trainers/emma-wilson.jpg',
                is_accepting_clients: true,
                rating: 5.0
            },
            {
                id: 6,
                first_name: 'John',
                last_name: 'Martinez',
                title: 'Functional Training Coach',
                specializations: ['Functional Movement', 'TRX', 'Bootcamp'],
                certifications: ['FMS', 'TRX-STC'],
                experience_years: 9,
                hourly_rate: 75,
                bio: 'John focuses on real-world movement patterns that improve daily life activities while building overall fitness and strength.',
                profile_picture: 'images/trainers/john-martinez.jpg',
                is_accepting_clients: true,
                rating: 4.8
            }
        ];

        trainers = staticTrainers;
        displayTrainers(staticTrainers);
    }

    function displayTrainers(trainersData) {
        const container = document.getElementById('trainers-container');
        if (!container) {
            console.error('Trainers container not found!');
            return;
        }
        container.innerHTML = trainersData.map(trainer => `
            <div class="class-card trainer-card" data-trainer-id="${trainer.id}" data-specialization="${getMainSpecialization(trainer.specializations)}" data-rate="${getRateCategory(trainer.hourly_rate)}">
                <img src="${trainer.profile_picture}" alt="${trainer.first_name} ${trainer.last_name}" onerror="this.src='images/trainers/default.svg'">
                <div class="class-info trainer-info">
                    <h3>${trainer.first_name} ${trainer.last_name}</h3>
                    <p class="class-level trainer-title">${trainer.title}</p>
                    <p class="class-duration trainer-rate">$${trainer.hourly_rate}/hour</p>
                    <div class="class-schedule trainer-schedule">
                        <div class="trainer-rating">
                            <span class="stars">${generateStars(trainer.rating)}</span>
                            <span class="rating-value">${trainer.rating}/5 (${trainer.total_reviews} reviews)</span>
                        </div>
                        <p class="trainer-experience">${trainer.experience_years} years experience</p>
                        <p class="trainer-specialties">${trainer.specializations.slice(0, 3).join(', ')}</p>
                    </div>
                    <div class="class-booking trainer-booking">
                        ${trainer.is_accepting_clients ? 
                            `<button class="btn btn-primary book-trainer" data-trainer-id="${trainer.id}">Book Session</button>` :
                            `<button class="btn btn-secondary" disabled>Not Available</button>`
                        }
                        <div class="class-capacity trainer-availability">
                            <span class="available-spots">${trainer.is_accepting_clients ? 'Available' : 'Fully Booked'}</span>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        // Attach event listeners
        attachTrainerListeners();
        console.log('Displayed', trainersData.length, 'trainers with booking buttons');
    }

    function getMainSpecialization(specializations) {
        if (!specializations || specializations.length === 0) return 'general';
        
        const spec = specializations[0].toLowerCase();
        if (spec.includes('yoga') || spec.includes('pilates')) return 'yoga';
        if (spec.includes('crossfit') || spec.includes('strength') || spec.includes('powerlifting')) return 'strength';
        if (spec.includes('hiit') || spec.includes('cardio') || spec.includes('spinning')) return 'cardio';
        if (spec.includes('zumba') || spec.includes('dance')) return 'dance';
        if (spec.includes('injury') || spec.includes('rehabilitation') || spec.includes('corrective')) return 'rehabilitation';
        return 'general';
    }
    
    function getRateCategory(rate) {
        if (rate < 75) return 'low';
        if (rate < 85) return 'mid';
        return 'high';
    }

    function generateStars(rating) {
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 !== 0;
        let stars = '';

        for (let i = 0; i < fullStars; i++) {
            stars += '★';
        }
        if (hasHalfStar) {
            stars += '☆';
        }
        while (stars.length < 5) {
            stars += '☆';
        }
        return stars;
    }

    function attachTrainerListeners() {
        // Book session buttons
        const bookButtons = document.querySelectorAll('.book-trainer');
        console.log('Attaching listeners to', bookButtons.length, 'book buttons');
        
        bookButtons.forEach(button => {
            button.addEventListener('click', function() {
                const trainerId = parseInt(this.getAttribute('data-trainer-id'));
                const trainer = trainers.find(t => t.id === trainerId);
                console.log('Opening booking modal for trainer:', trainer?.first_name, trainer?.last_name);
                if (trainer && !this.disabled) {
                    // Open booking modal instead of immediate booking
                    openBookingModal(trainer);
                } else {
                    console.error('Trainer not found for ID:', trainerId);
                }
            });
        });

        // View schedule buttons (placeholder functionality)
        const scheduleButtons = document.querySelectorAll('.view-schedule');
        scheduleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const trainerId = parseInt(this.getAttribute('data-trainer-id'));
                const trainer = trainers.find(t => t.id === trainerId);
                if (trainer) {
                    showNotification(`${trainer.first_name}'s schedule will be available soon!`, 'info');
                }
            });
        });
    }

    function openBookingModal(trainer) {
        const modal = document.getElementById('booking-modal');
        const trainerDetails = document.getElementById('trainer-details');
        
        if (!modal || !trainerDetails) {
            console.error('Modal elements not found!');
            return;
        }
        
        console.log('Opening booking modal for', trainer.first_name, trainer.last_name);

        trainerDetails.innerHTML = `
            <div class="booking-trainer-info">
                <img src="${trainer.profile_picture}" alt="${trainer.first_name} ${trainer.last_name}" class="trainer-photo">
                <div class="trainer-summary">
                    <h3>${trainer.first_name} ${trainer.last_name}</h3>
                    <p class="trainer-title">${trainer.title}</p>
                    <div class="trainer-rating">
                        <span class="stars">${generateStars(trainer.rating)}</span>
                        <span class="rating-value">${trainer.rating}/5</span>
                    </div>
                    <p class="hourly-rate">$${trainer.hourly_rate}/hour</p>
                </div>
            </div>
        `;

        // Store the trainer for booking
        currentSelectedTrainer = trainer;
        const bookingForm = document.getElementById('booking-form');
        bookingForm.setAttribute('data-trainer-id', trainer.id);

        // Show modal
        modal.style.display = 'block';
        document.body.classList.add('modal-open');

        // Update pricing when form values change
        updatePricing();
    }

    function initializeModal() {
        const modal = document.getElementById('booking-modal');
        if (!modal) {
            console.error('Booking modal not found!');
            return;
        }

        const closeBtn = modal.querySelector('.close');
        const cancelBtn = modal.querySelector('.cancel-booking');
        const bookingForm = document.getElementById('booking-form');

        if (closeBtn) {
            closeBtn.addEventListener('click', closeBookingModal);
        }
        
        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeBookingModal);
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeBookingModal();
            }
        });

        // Handle form submission
        if (bookingForm) {
            bookingForm.addEventListener('submit', handleBookingSubmission);
        }
    }

    function setupFormHandlers() {
        const startTimeSelect = document.getElementById('start-time');
        const endTimeSelect = document.getElementById('end-time');
        const appointmentTypeSelect = document.getElementById('appointment-type');

        // Update end time options when start time changes
        startTimeSelect.addEventListener('change', function() {
            updateEndTimeOptions();
            updatePricing();
        });

        // Update pricing when end time changes
        endTimeSelect.addEventListener('change', updatePricing);

        // Update pricing when appointment type changes
        appointmentTypeSelect.addEventListener('change', updatePricing);
    }

    function updateEndTimeOptions() {
        const startTimeSelect = document.getElementById('start-time');
        const endTimeSelect = document.getElementById('end-time');
        const startTime = startTimeSelect.value;

        if (!startTime) {
            endTimeSelect.innerHTML = '<option value="">Select end time...</option>';
            return;
        }

        const startHour = parseInt(startTime.split(':')[0]);
        let endOptions = '<option value="">Select end time...</option>';

        // Generate end time options (30 min, 1 hour, 1.5 hour, 2 hour sessions)
        const durations = [0.5, 1, 1.5, 2];
        durations.forEach(duration => {
            const endHour = startHour + duration;
            if (endHour <= 21) { // Don't go past 9 PM
                const endTimeValue = String(Math.floor(endHour)).padStart(2, '0') + ':' + 
                                    (duration % 1 === 0.5 ? '30' : '00') + ':00';
                const endTimeDisplay = formatTime(endTimeValue) + ` (${duration}h session)`;
                endOptions += `<option value="${endTimeValue}">${endTimeDisplay}</option>`;
            }
        });

        endTimeSelect.innerHTML = endOptions;
    }

    function formatTime(timeString) {
        const [hours, minutes] = timeString.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour === 0 ? 12 : hour > 12 ? hour - 12 : hour;
        return `${displayHour}:${minutes} ${ampm}`;
    }

    function updatePricing() {
        const startTime = document.getElementById('start-time').value;
        const endTime = document.getElementById('end-time').value;
        const appointmentType = document.getElementById('appointment-type').value;
        const pricingInfo = document.getElementById('pricing-info');

        if (!startTime || !endTime || !appointmentType || !currentSelectedTrainer) {
            pricingInfo.innerHTML = '';
            return;
        }

        // Calculate duration
        const startHour = parseFloat(startTime.split(':')[0]) + parseFloat(startTime.split(':')[1]) / 60;
        const endHour = parseFloat(endTime.split(':')[0]) + parseFloat(endTime.split(':')[1]) / 60;
        const duration = endHour - startHour;

        // Calculate base price
        let basePrice = currentSelectedTrainer.hourly_rate * duration;

        // Apply appointment type multipliers
        const typeMultipliers = {
            'individual': 1.0,
            'small_group': 0.7,
            'assessment': 1.2,
            'consultation': 0.8
        };

        const finalPrice = basePrice * (typeMultipliers[appointmentType] || 1.0);

        pricingInfo.innerHTML = `
            <div class="price-breakdown">
                <h4>Session Pricing</h4>
                <p><strong>Duration:</strong> ${duration} hour${duration !== 1 ? 's' : ''}</p>
                <p><strong>Base Rate:</strong> $${currentSelectedTrainer.hourly_rate}/hour</p>
                <p><strong>Session Type:</strong> ${appointmentType.replace('_', ' ')}</p>
                <p class="total-price"><strong>Total: $${finalPrice.toFixed(2)}</strong></p>
                <p class="price-note">*Final price may vary based on membership discounts</p>
            </div>
        `;
    }

    function closeBookingModal() {
        const modal = document.getElementById('booking-modal');
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        
        // Reset form and data
        const bookingForm = document.getElementById('booking-form');
        bookingForm.reset();
        document.getElementById('pricing-info').innerHTML = '';
        
        currentSelectedTrainer = null;
    }

    function handleBookingSubmission(event) {
        event.preventDefault();
        
        if (!currentSelectedTrainer) {
            showNotification('Error: No trainer selected', 'error');
            return;
        }

        const form = event.target;
        const formData = new FormData(form);
        const submitButton = form.querySelector('#confirm-booking');

        // Validate required fields
        const requiredFields = ['appointment_date', 'start_time', 'end_time', 'session_focus', 'appointment_type', 'location'];
        for (const field of requiredFields) {
            if (!formData.get(field)) {
                showNotification(`Please fill in all required fields`, 'error');
                return;
            }
        }

        // Disable submit button
        submitButton.disabled = true;
        submitButton.textContent = 'Booking...';

        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Prepare booking data
        const bookingData = new URLSearchParams({
            trainer_id: currentSelectedTrainer.id,
            appointment_date: formData.get('appointment_date'),
            start_time: formData.get('start_time'),
            end_time: formData.get('end_time'),
            session_focus: formData.get('session_focus'),
            appointment_type: formData.get('appointment_type'),
            location: formData.get('location'),
            notes: formData.get('notes') || '',
            csrf_token: csrfToken
        });

        // Submit booking
        fetch('php/trainers/book.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: bookingData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Booking response:', data);
            if (data.success) {
                showNotification('🎉 ' + data.message, 'success');
                closeBookingModal();
                
                // Show booking confirmation details
                showBookingConfirmation(data.data);
            } else {
                showNotification('❌ ' + (data.message || 'Booking failed. Please try again.'), 'error');
                console.error('Booking failed with response:', data);
                console.error('Errors:', data.errors);
                // Show detailed error in alert for debugging
                alert('Booking failed: ' + JSON.stringify(data, null, 2));
            }
        })
        .catch(error => {
            console.error('Booking error:', error);
            showNotification('Booking failed. Please check your connection and try again.', 'error');
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.textContent = 'Book Session';
        });
    }

    function showBookingConfirmation(bookingData) {
        const confirmationModal = document.createElement('div');
        confirmationModal.className = 'modal';
        confirmationModal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Booking Confirmed!</h2>
                    <span class="close" onclick="this.closest('.modal').remove()">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="confirmation-details">
                        <h3>Your Training Session Details</h3>
                        <p><strong>Trainer:</strong> ${bookingData.trainer.name}</p>
                        <p><strong>Date:</strong> ${bookingData.appointment.date}</p>
                        <p><strong>Time:</strong> ${bookingData.appointment.start_time} - ${bookingData.appointment.end_time}</p>
                        <p><strong>Duration:</strong> ${bookingData.appointment.duration_minutes} minutes</p>
                        <p><strong>Focus:</strong> ${bookingData.appointment.session_focus}</p>
                        <p><strong>Location:</strong> ${bookingData.appointment.location.replace('_', ' ')}</p>
                        ${bookingData.pricing.payment_required ? 
                            `<p class="price-info"><strong>Total Cost:</strong> $${bookingData.pricing.price}</p>` : 
                            '<p class="price-info"><strong>Cost:</strong> Included in membership</p>'
                        }
                        <div class="next-steps">
                            <h4>What's Next?</h4>
                            <ul>
                                <li>You'll receive a confirmation email shortly</li>
                                <li>Your trainer will contact you 24 hours before the session</li>
                                <li>Arrive 10 minutes early for your appointment</li>
                                <li>Bring a water bottle and towel</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button class="btn btn-primary" onclick="this.closest('.modal').remove()">Got it!</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(confirmationModal);
        confirmationModal.style.display = 'block';
    }

    function bookTrainerImmediately(trainer, button) {
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Default booking settings - 1 hour session tomorrow at 10 AM
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const appointmentDate = tomorrow.toISOString().split('T')[0];
        
        const bookingData = new URLSearchParams({
            trainer_id: trainer.id,
            appointment_date: appointmentDate,
            start_time: '10:00:00',
            end_time: '11:00:00',
            session_focus: 'General Fitness',
            appointment_type: 'individual',
            location: 'gym_floor',
            notes: 'Quick booking session',
            csrf_token: csrfToken
        });

        // Submit booking
        fetch('php/trainers/book.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: bookingData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Booking response:', data);
            console.log('Response details:', JSON.stringify(data, null, 2));
            if (data.success) {
                showNotification(`🎉 Successfully booked ${trainer.first_name} ${trainer.last_name} for tomorrow at 10:00 AM!`, 'success');
                button.textContent = '✅ Booked';
                button.style.backgroundColor = '#28a745';
                button.style.color = 'white';
            } else {
                const errorMsg = data.message || 'Booking failed';
                console.error('Detailed error:', {
                    message: data.message,
                    errors: data.errors,
                    full_response: data
                });
                
                if (errorMsg.includes('Too many')) {
                    showNotification(`⏳ Please wait a moment before booking again`, 'error');
                } else {
                    showNotification(`❌ Failed to book ${trainer.first_name} ${trainer.last_name}: ${errorMsg}`, 'error');
                }
                
                // Show detailed error in alert for debugging
                alert(`Booking failed for ${trainer.first_name} ${trainer.last_name}:\n\nError: ${errorMsg}\n\nFull response: ${JSON.stringify(data, null, 2)}`);
                
                // Reset button
                button.disabled = false;
                button.classList.remove('booking-in-progress');
                button.textContent = 'Book Session';
                console.error('Booking failed:', data);
            }
        })
        .catch(error => {
            console.error('Booking error:', error);
            showNotification(`❌ Booking failed for ${trainer.first_name} ${trainer.last_name}. Please try again.`, 'error');
            button.disabled = false;
            button.classList.remove('booking-in-progress');
            button.textContent = 'Book Session';
        });
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

    function initializeFilters() {
        const searchInput = document.getElementById('trainer-search');
        const specializationFilter = document.getElementById('specialization-filter');
        const rateFilter = document.getElementById('rate-filter');
        
        if (searchInput) {
            searchInput.addEventListener('input', filterTrainers);
        }
        if (specializationFilter) {
            specializationFilter.addEventListener('change', filterTrainers);
        }
        if (rateFilter) {
            rateFilter.addEventListener('change', filterTrainers);
        }
    }
    
    function filterTrainers() {
        const searchTerm = document.getElementById('trainer-search')?.value.toLowerCase() || '';
        const specializationFilter = document.getElementById('specialization-filter')?.value || '';
        const rateFilter = document.getElementById('rate-filter')?.value || '';
        
        const trainerCards = document.querySelectorAll('.trainer-card');
        
        trainerCards.forEach(card => {
            const trainerName = card.querySelector('h3').textContent.toLowerCase();
            const trainerTitle = card.querySelector('.trainer-title').textContent.toLowerCase();
            const trainerSpecs = card.querySelector('.trainer-specialties').textContent.toLowerCase();
            const cardSpecialization = card.getAttribute('data-specialization');
            const cardRate = card.getAttribute('data-rate');
            
            // Check search term
            const matchesSearch = searchTerm === '' || 
                trainerName.includes(searchTerm) || 
                trainerTitle.includes(searchTerm) || 
                trainerSpecs.includes(searchTerm);
            
            // Check specialization filter
            const matchesSpecialization = specializationFilter === '' || cardSpecialization === specializationFilter;
            
            // Check rate filter
            const matchesRate = rateFilter === '' || cardRate === rateFilter;
            
            // Show/hide card based on all filters
            if (matchesSearch && matchesSpecialization && matchesRate) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    // Global function for search button
    window.searchTrainers = filterTrainers;

    // Add styles for trainer-specific elements
    const style = document.createElement('style');
    style.textContent = `
        .trainer-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .trainer-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .trainer-rating {
            margin: 0.5rem 0;
        }

        .trainer-rating .stars {
            color: #ffd700;
            font-size: 1.2rem;
            margin-right: 0.5rem;
        }

        .rating-value {
            font-size: 0.9rem;
            color: #6c757d;
            display: block;
            margin-top: 2px;
        }
        
        .trainer-experience {
            color: #007bff;
            font-weight: 500;
            margin: 5px 0;
        }
        
        .trainer-specialties {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 5px 0;
        }
        
        .trainer-availability {
            text-align: center;
            margin-top: 10px;
        }
        
        .available-spots {
            font-size: 0.9rem;
            color: #28a745;
            font-weight: 500;
        }
        
        .trainer-card .available-spots {
            color: #28a745;
        }
        
        .trainer-card[data-rate="high"] .trainer-rate {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .trainer-card[data-rate="mid"] .trainer-rate {
            color: #f39c12;
            font-weight: 500;
        }
        
        .trainer-card[data-rate="low"] .trainer-rate {
            color: #27ae60;
        }

        .booking-trainer-info {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .trainer-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
        }

        .trainer-summary h3 {
            margin: 0 0 0.25rem;
            color: #2c3e50;
        }

        .trainer-summary .trainer-title {
            margin: 0 0 0.5rem;
            color: #6c757d;
            font-style: italic;
        }

        .hourly-rate {
            font-weight: bold;
            color: #e74c3c;
            margin: 0.5rem 0 0;
        }

        .price-breakdown {
            background: #e8f5e8;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .price-breakdown h4 {
            margin-top: 0;
            color: #2c3e50;
        }

        .total-price {
            font-size: 1.2rem;
            color: #e74c3c;
            border-top: 1px solid #ddd;
            padding-top: 0.5rem;
            margin-top: 0.5rem;
        }

        .price-note {
            font-size: 0.8rem;
            color: #6c757d;
            font-style: italic;
            margin-bottom: 0;
        }

        .confirmation-details {
            background: #d4edda;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .confirmation-details h3 {
            color: #155724;
            margin-top: 0;
        }

        .price-info {
            font-size: 1.1rem;
            font-weight: bold;
            color: #e74c3c;
        }

        .next-steps {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #c3e6cb;
        }

        .next-steps h4 {
            color: #155724;
            margin-bottom: 0.5rem;
        }

        .next-steps ul {
            margin-left: 1rem;
        }

        .next-steps li {
            margin-bottom: 0.25rem;
            color: #155724;
        }

        .loading-message {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
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

        body.modal-open {
            overflow: hidden;
        }
    `;
    document.head.appendChild(style);

})();