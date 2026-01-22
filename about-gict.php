<?php
require_once 'includes/session_manager.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About GICT - Government Institute of Computer Technology</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/content.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .about-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
            margin-bottom: 60px;
            margin-top: 192px;
            /* Reduced margin since body has padding-top */
        }

        .about-hero h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .about-hero p {
            font-size: 1.3rem;
            max-width: 800px;
            margin: 0 auto;
            opacity: 0.9;
        }

        .about-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .about-section {
            margin-bottom: 80px;
        }

        .about-section h2 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
        }

        .about-section h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 2px;
        }

        .about-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            margin-top: 50px;
        }

        .about-card {
            background: white;
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #f0f0f0;
        }

        .about-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .about-card i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 20px;
        }

        .about-card h3 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 15px;
        }

        .about-card p {
            color: #666;
            line-height: 1.6;
        }

        .stats-section {
            background: #f8f9fa;
            padding: 80px 0;
            margin: 60px 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            text-align: center;
        }

        .stat-item {
            padding: 20px;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.1rem;
            color: #666;
            font-weight: 500;
        }

        .mission-vision {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 40px;
            margin-top: 50px;
        }

        .mission-card,
        .vision-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #667eea;
        }

        .mission-card h3,
        .vision-card h3 {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .mission-card h3 i {
            color: #667eea;
        }

        .vision-card h3 i {
            color: #764ba2;
        }

        .mission-card p,
        .vision-card p {
            color: #666;
            line-height: 1.7;
            font-size: 1.1rem;
        }

        .timeline {
            position: relative;
            margin-top: 50px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, #667eea, #764ba2);
            transform: translateX(-50%);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 40px;
            width: 45%;
        }

        .timeline-item:nth-child(odd) {
            left: 0;
            text-align: right;
            padding-right: 40px;
        }

        .timeline-item:nth-child(even) {
            left: 55%;
            text-align: left;
            padding-left: 40px;
        }

        .timeline-content {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .timeline-item:nth-child(odd) .timeline-content::after {
            content: '';
            position: absolute;
            right: -15px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-left: 15px solid white;
            border-top: 10px solid transparent;
            border-bottom: 10px solid transparent;
        }

        .timeline-item:nth-child(even) .timeline-content::after {
            content: '';
            position: absolute;
            left: -15px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-right: 15px solid white;
            border-top: 10px solid transparent;
            border-bottom: 10px solid transparent;
        }

        .timeline-year {
            font-size: 1.2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 10px;
        }

        .timeline-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .timeline-desc {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            .about-hero {
                margin-top: 262px;
                /* Reduced margin since body has padding-top */
                padding: 60px 0;
                /* Reduce padding for mobile */
            }

            .about-hero h1 {
                font-size: 2.5rem;
            }

            .about-hero p {
                font-size: 1.1rem;
            }

            .about-content {
                padding: 0 15px;
                /* Reduce side padding on mobile */
            }

            .about-section {
                margin-bottom: 60px;
                /* Reduce spacing on mobile */
            }

            .about-section h2 {
                font-size: 2rem;
                /* Smaller headings on mobile */
            }

            .about-grid {
                grid-template-columns: 1fr;
                /* Single column on mobile */
                gap: 30px;
                margin-top: 30px;
            }

            .about-card {
                padding: 30px 20px;
                /* Reduce padding for mobile */
            }

            .stats-section {
                padding: 60px 0;
                /* Reduce padding for mobile */
                margin: 40px 0;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                /* 2 columns on mobile */
                gap: 30px;
            }

            .mission-vision {
                grid-template-columns: 1fr;
                /* Single column on mobile */
                gap: 30px;
            }

            .mission-card,
            .vision-card {
                padding: 30px 20px;
                /* Reduce padding for mobile */
            }

            .timeline::before {
                left: 20px;
            }

            .timeline-item {
                width: 100%;
                left: 0 !important;
                text-align: left !important;
                padding-left: 50px !important;
                padding-right: 20px !important;
            }

            .timeline-item:nth-child(odd) .timeline-content::after,
            .timeline-item:nth-child(even) .timeline-content::after {
                left: -15px !important;
                right: auto !important;
                border-right: 15px solid white !important;
                border-left: none !important;
            }
        }

        /* Extra small mobile devices */
        @media (max-width: 480px) {
            .about-hero {
                margin-top: 262px;
                /* Minimal margin for very small screens */
                padding: 40px 0;
            }

            .about-hero h1 {
                font-size: 2rem;
            }

            .about-hero p {
                font-size: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                /* Single column on very small screens */
            }

            .about-card,
            .mission-card,
            .vision-card {
                padding: 25px 15px;
            }
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="about-hero">
        <div class="about-content">
            <h1>About GICT</h1>
            <p>Empowering students with cutting-edge computer technology education since 2019</p>
        </div>
    </div>

    <div class="about-content">
        <div class="about-section">
            <h2>Welcome to GICT</h2>
            <p style="text-align: center; font-size: 1.2rem; color: #666; max-width: 800px; margin: 0 auto 40px;">
                The Government Institute of Computer Technology (GICT) is a premier institution dedicated to providing
                high-quality computer technology education. Founded in 2019, we have been at the forefront of
                technological advancement and skill development.
            </p>

            <div class="about-grid">
                <div class="about-card">
                    <i class="fas fa-graduation-cap"></i>
                    <h3>Quality Education</h3>
                    <p>We provide comprehensive computer technology education with a focus on practical skills and
                        industry relevance.</p>
                </div>
                <div class="about-card">
                    <i class="fas fa-users"></i>
                    <h3>Expert Faculty</h3>
                    <p>Our experienced faculty members bring industry expertise and academic excellence to every
                        classroom.</p>
                </div>
                <div class="about-card">
                    <i class="fas fa-laptop-code"></i>
                    <h3>Modern Infrastructure</h3>
                    <p>State-of-the-art computer labs and facilities to support hands-on learning and practical
                        training.</p>
                </div>
            </div>
        </div>

        <div class="stats-section">
            <div class="about-content">
                <h2>Our Achievements</h2>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number">2019</div>
                        <div class="stat-label">Year Founded</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">500+</div>
                        <div class="stat-label">Students Enrolled</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">15+</div>
                        <div class="stat-label">Expert Faculty</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">95%</div>
                        <div class="stat-label">Placement Rate</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="about-section">
            <h2>Mission & Vision</h2>
            <div class="mission-vision">
                <div class="mission-card">
                    <h3><i class="fas fa-bullseye"></i>Our Mission</h3>
                    <p>To provide accessible, high-quality computer technology education that empowers students with the
                        skills,
                        knowledge, and confidence needed to excel in the digital age. We strive to bridge the gap
                        between
                        theoretical knowledge and practical application.</p>
                </div>
                <div class="vision-card">
                    <h3><i class="fas fa-eye"></i>Our Vision</h3>
                    <p>To be a leading institution in computer technology education, recognized for innovation,
                        excellence,
                        and producing industry-ready professionals who contribute significantly to the technological
                        advancement
                        of our nation.</p>
                </div>
            </div>
        </div>

        <div class="about-section">
            <h2>Our Journey</h2>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-content">
                        <div class="timeline-year">2019</div>
                        <div class="timeline-title">Foundation</div>
                        <div class="timeline-desc">GICT was established with a vision to provide quality computer
                            technology education.</div>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-content">
                        <div class="timeline-year">2020</div>
                        <div class="timeline-title">First Batch</div>
                        <div class="timeline-desc">Successfully enrolled our first batch of students in various computer
                            technology courses.</div>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-content">
                        <div class="timeline-year">2021</div>
                        <div class="timeline-title">Infrastructure Development</div>
                        <div class="timeline-desc">Enhanced our facilities with modern computer labs and advanced
                            equipment.</div>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-content">
                        <div class="timeline-year">2022</div>
                        <div class="timeline-title">Industry Partnerships</div>
                        <div class="timeline-desc">Established partnerships with leading technology companies for
                            internships and placements.</div>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-content">
                        <div class="timeline-year">2023</div>
                        <div class="timeline-title">Excellence Recognition</div>
                        <div class="timeline-desc">Received recognition for outstanding performance in computer
                            technology education.</div>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-content">
                        <div class="timeline-year">2024</div>
                        <div class="timeline-title">Future Ready</div>
                        <div class="timeline-desc">Continuing to evolve with cutting-edge technology and innovative
                            teaching methods.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>

</html>