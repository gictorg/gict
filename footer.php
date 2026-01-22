<?php
// Footer template
?>
<footer class="footer">
    <!-- Certification Section -->
    <section class="certification">
        <div class="container">
            <h2>Important Certifications</h2>
            <div class="certification-logos">
                <img src="assets/images/iso.jpg" alt="ISO Certification">
                <img src="assets/images/nailet.png" alt="NIELIT">
                <img src="assets/images/nsdc.png" alt="NSDC">
                <img src="assets/images/skill.png" alt="Skill India">
                <img src="assets/images/MINISTRY.png" alt="Ministry of Education">
            </div>
        </div>
    </section>

    <!-- Connect Section -->
    <section class="connect-section">
        <div class="container">
            <div class="connect-container">
                <div class="connect-left">
                    <h3>Connect with us on :</h3>
                    <div class="social-icons">
                        <a href="#" target="_blank" rel="noopener noreferrer" class="youtube">
                            <?php include 'assets/images/social/youtube.svg'; ?>
                        </a>
                        <a href="#" target="_blank" rel="noopener noreferrer" class="facebook">
                            <?php include 'assets/images/social/facebook.svg'; ?>
                        </a>
                        <a href="#" target="_blank" rel="noopener noreferrer" class="instagram">
                            <?php include 'assets/images/social/instagram.svg'; ?>
                        </a>
                        <a href="#" target="_blank" rel="noopener noreferrer" class="twitter">
                            <?php include 'assets/images/social/twitter.svg'; ?>
                        </a>
                        <a href="#" target="_blank" rel="noopener noreferrer" class="website">
                            <?php include 'assets/images/social/globe.svg'; ?>
                        </a>
                    </div>
                </div>
                <div class="connect-right">
                    <h3>SEND YOUR COMMENTS/FEEDBACK</h3>
                    <a href="mailto:connect@gict.org.in" class="website">connect@gict.org.in</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Navigation -->
    <section class="footer-nav">
        <div class="container">
            <div class="footer-container">
                <div class="footer-section">
                    <h3>TERMS & POLICIES</h3>
                    <ul class="footer-links">
                        <li><a href="#">Disclaimer</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Copyright Policy</a></li>
                        <li><a href="#">Terms & Conditions</a></li>
                        <li><a href="#">Hyperlinking Policy</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>MEDIA</h3>
                    <ul class="footer-links">
                        <li><a href="#">Photographs</a></li>
                        <li><a href="#">Videos</a></li>
                        <li><a href="#">Right to Information</a></li>
                        <li><a href="#">Notices and Circulars</a></li>
                        <li><a href="#">Tenders</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>DOWNLOAD MOBILE APP</h3>
                    <div class="app-downloads">
                        <a href="#" target="_blank" rel="noopener noreferrer" class="play-store-link">
                            <img src="assets/images/app-store/andoid-badge.png" alt="ANDROID APP ON Google play">
                        </a>
                        <a href="#" target="_blank" rel="noopener noreferrer" class="app-store-link">
                            <img src="assets/images/app-store/apple-badge.png" alt="Download on the App Store">
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Bottom -->
    <section class="footer-bottom">
        <div class="container">
            <div class="footer-bottom-container">
                <div class="update-info">Page Last Updated on : 23 January 2026</div>
                <div class="visitor-count">Number of Visitors : 42</div>
            </div>
        </div>
    </section>
</footer>

<!-- Global Enquiry Modal -->
<div id="enquiryModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-question-circle"></i> Course Inquiry</h2>
            <span class="close" onclick="closeEnquiryModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div id="enquiryMessage"
                style="display: none; margin-bottom: 20px; padding: 15px; border-radius: 8px; font-weight: 500;"></div>

            <form id="enquiryForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_name">Full Name *</label>
                        <input type="text" id="modal_name" name="name" required placeholder="Enter your full name">
                    </div>
                    <div class="form-group">
                        <label for="modal_mobile">Mobile Number *</label>
                        <input type="tel" id="modal_mobile" name="mobile" required
                            placeholder="Enter 10-digit mobile number" pattern="[0-9]{10}" maxlength="10">
                    </div>
                </div>

                <div class="form-group">
                    <label for="modal_email">Email Address</label>
                    <input type="email" id="modal_email" name="email" placeholder="Enter your email address (optional)">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_course_id">Course Category</label>
                        <select id="modal_course_id" name="course_id" onchange="updateModalSubCourses()">
                            <option value="">Select Course Category</option>
                            <option value="1">Technology</option>
                            <option value="2">Marketing</option>
                            <option value="3">Fashion</option>
                            <option value="4">Wellness</option>
                            <option value="5">Skills</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="modal_sub_course_id">Specific Course</label>
                        <select id="modal_sub_course_id" name="sub_course_id">
                            <option value="">Select Specific Course</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="modal_message">Message</label>
                    <textarea id="modal_message" name="message" rows="3"
                        placeholder="Tell us about your interest in the course or any specific questions you have..."></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEnquiryModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-paper-plane"></i> Submit Inquiry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
<script src="assets/js/enquiry-modal.js"></script>
</body>

</html>