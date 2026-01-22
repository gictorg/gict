// Enquiry Modal Functions
function openEnquiryModal() {
    console.log('Opening Enquiry Modal');
    const modal = document.getElementById('enquiryModal');
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        // Hide any previous messages
        const msg = document.getElementById('enquiryMessage');
        if (msg) msg.style.display = 'none';
    } else {
        console.error('Enquiry Modal not found');
    }
}

function closeEnquiryModal() {
    const modal = document.getElementById('enquiryModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        // Reset form
        const form = document.getElementById('enquiryForm');
        if (form) form.reset();
        // Hide any messages
        const msg = document.getElementById('enquiryMessage');
        if (msg) msg.style.display = 'none';
    }
}

function showEnquiryMessage(message, isSuccess = true) {
    const messageDiv = document.getElementById('enquiryMessage');
    if (messageDiv) {
        messageDiv.textContent = message;
        messageDiv.style.display = 'block';
        messageDiv.style.backgroundColor = isSuccess ? '#d4edda' : '#f8d7da';
        messageDiv.style.color = isSuccess ? '#155724' : '#721c24';
        messageDiv.style.border = isSuccess ? '1px solid #c3e6cb' : '1px solid #f5c6cb';
    }
}

function updateModalSubCourses() {
    const courseId = document.getElementById('modal_course_id').value;
    const subCourseSelect = document.getElementById('modal_sub_course_id');

    if (!subCourseSelect) return;

    // Reset sub-course dropdown
    subCourseSelect.innerHTML = '<option value="">Select Specific Course</option>';

    // Course options based on selected category (matches database IDs)
    const courseOptions = {
        '1': [ // Technology
            { value: '1', text: 'CCC (Course on Computer Concepts)' },
            { value: '2', text: 'ADCA (Advanced Diploma in Computer Applications)' },
            { value: '3', text: 'PGDCA (Post Graduate Diploma in Computer Applications)' },
            { value: '4', text: 'DCA (Diploma in Computer Applications)' },
            { value: '5', text: 'Tally ERP 9' }
        ],
        '2': [ // Marketing
            { value: '6', text: 'SEO (Search Engine Optimization)' },
            { value: '7', text: 'SEM (Search Engine Marketing)' },
            { value: '8', text: 'Social Media Marketing' },
            { value: '9', text: 'Content Marketing' }
        ],
        '3': [ // Fashion
            { value: '10', text: 'Basic Stitching' },
            { value: '11', text: 'Pants Sewing' },
            { value: '12', text: 'Blouse Sewing' },
            { value: '13', text: 'Kurta Sewing' },
            { value: '14', text: 'Dress Making' }
        ],
        '4': [ // Wellness
            { value: '15', text: 'Yoga Certificate' },
            { value: '16', text: 'Health & Wellness' },
            { value: '17', text: 'Meditation Course' }
        ],
        '5': [ // Skills
            { value: '18', text: 'Beautician Certificate' },
            { value: '19', text: 'Vocational Course' },
            { value: '20', text: 'Skill Development' }
        ]
    };

    if (courseId && courseOptions[courseId]) {
        courseOptions[courseId].forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option.value;
            optionElement.textContent = option.text;
            subCourseSelect.appendChild(optionElement);
        });
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const enquiryForm = document.getElementById('enquiryForm');
    if (enquiryForm) {
        enquiryForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;

            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            submitBtn.disabled = true;

            // Get form data
            const formData = new FormData(this);

            // Submit via AJAX
            fetch('inquiry.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showEnquiryMessage(data.message, true);
                        // Reset form after successful submission
                        setTimeout(() => {
                            closeEnquiryModal();
                        }, 2000);
                    } else {
                        showEnquiryMessage(data.message, false);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showEnquiryMessage('Failed to submit inquiry. Please try again.', false);
                })
                .finally(() => {
                    // Reset button state
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
        });
    }

    // Close modal when clicking outside
    window.addEventListener('click', function (event) {
        const modal = document.getElementById('enquiryModal');
        if (event.target === modal) {
            closeEnquiryModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeEnquiryModal();
        }
    });
});
