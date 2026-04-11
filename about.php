<?php
// about.php — About Us page
require_once 'includes/sessions.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$page_title = 'About Us';

require_once 'templates/header.php';
?>

<!-- ── PAGE BANNER ────────────────────────────────────────────── -->
<div class="page-banner">
    <div class="container">
        <h1>About Us</h1>
        <p>The story behind Omnes MarketPlace — who we are, where we started, and where we're going.</p>
    </div>
</div>

<div class="container">

    <!-- ── OUR STORY ──────────────────────────────────────────── -->
    <section class="about-history">
        <div class="about-history-text">
            <p class="section-label">Our Story</p>
            <h2>From a Student Idea to a<br><em style="color:var(--purple-500);font-style:normal;">Living Marketplace</em></h2>
            <p>
                Omnes MarketPlace was born in early 2024 from a simple frustration: students and
                faculty within the Omnes Education network had no trusted, community-first place
                to buy and sell the things that mattered to them — from everyday study essentials
                to rare collector's pieces accumulated over a lifetime.
            </p>
            <p>
                What began as a whiteboard sketch in a Web Development B1 class quickly grew into
                a fully functional e-commerce platform designed around three core values:
                <strong>transparency</strong>, <strong>fairness</strong>, and <strong>variety</strong>.
                Rather than imposing a single fixed price, we built a system that lets buyers and
                sellers meet in the middle — through instant purchases, open negotiations, or
                competitive auctions.
            </p>
            <p>
                By the end of 2024, the platform had expanded beyond its academic origins.
                External sellers were invited to list premium and antique items, and the
                "Rare" category was introduced to house one-of-a-kind collector's pieces —
                things you simply cannot find anywhere else. The live auction feature followed
                shortly after, giving rare items the stage they deserved.
            </p>
            <p>
                Today, Omnes MarketPlace serves buyers and sellers across France, with branches
                in three cities and a growing catalogue that spans hand-blown Murano glass to
                minimalist ceramic mugs. Our mission has never changed: give everyone in the
                Omnes community — student, faculty, or friend of the school — access to a
                marketplace they can trust.
            </p>
        </div>

        <!-- Timeline -->
        <div class="about-timeline">
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-body">
                    <div class="timeline-year">2024 — Q1</div>
                    <div class="timeline-event">Platform concept developed as a Web Development B1 class project at Omnes Education, Paris.</div>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-body">
                    <div class="timeline-year">2024 — Q2</div>
                    <div class="timeline-event">First working prototype launched internally. Buy It Now and Negotiation modes go live.</div>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-body">
                    <div class="timeline-year">2024 — Q3</div>
                    <div class="timeline-event">External sellers onboarded. Rare & High-End categories introduced. Seller dashboard built.</div>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-body">
                    <div class="timeline-year">2024 — Q4</div>
                    <div class="timeline-event">Live Auction (Best Offer) system launched. Lyon and Bordeaux branches open.</div>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-body">
                    <div class="timeline-year">2025</div>
                    <div class="timeline-event">Mobile-responsive redesign. Notification alerts and user preference system added.</div>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-dot" style="background:var(--gold);"></div>
                <div class="timeline-body">
                    <div class="timeline-year" style="color:var(--gold);">2026 — Today</div>
                    <div class="timeline-event">45+ curated listings, 3 branches, and a community of hundreds of buyers and sellers across France.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ── TEAM ───────────────────────────────────────────────── -->
    <section style="margin-top: 72px;">
        <div class="section-title">
            <div>
                <p class="section-label">The People Behind It</p>
                <h2>Meet the Team</h2>
            </div>
            <div class="section-title-line"></div>
        </div>

        <div class="team-grid">

            <!-- Member 1 -->
            <div class="team-card">
                <div class="team-avatar">
                    <img src="https://placehold.co/200x200/430e78/ffffff?text=FA" alt="AMINU Farouk Abdullahi">
                </div>
                <div class="team-info">
                    <h3 class="team-name">AMINU Farouk Abdullahi</h3>
                    <p class="team-position">Co-Founder &amp; Lead Developer</p>
                    <p class="team-qual">BSc Computer Science — Omnes Education, Paris</p>
                    <p class="team-bio">
                        Farouk architected the back-end infrastructure and database schema powering
                        the platform. His focus on security and clean code set the foundation for
                        every feature built since.
                    </p>
                </div>
            </div>

            <!-- Member 2 -->
            <div class="team-card">
                <div class="team-avatar">
                    <img src="https://placehold.co/200x200/5b189e/ffffff?text=MM" alt="VIRK Muhammad Moeez">
                </div>
                <div class="team-info">
                    <h3 class="team-name">VIRK Muhammad Moeez</h3>
                    <p class="team-position">Co-Founder &amp; Product Manager</p>
                    <p class="team-qual">MSc Digital Business — Omnes Education, Paris</p>
                    <p class="team-bio">
                        Moeez drives the product roadmap and keeps the team aligned with user needs.
                        He designed the three-mode purchasing system and oversees seller onboarding
                        and community growth.
                    </p>
                </div>
            </div>

            <!-- Member 3 -->
            <div class="team-card">
                <div class="team-avatar">
                    <img src="https://placehold.co/200x200/7b2fbe/ffffff?text=MB" alt="BHAGIA Mohit">
                </div>
                <div class="team-info">
                    <h3 class="team-name">BHAGIA Mohit</h3>
                    <p class="team-position">UI/UX Designer &amp; Front-End Developer</p>
                    <p class="team-qual">BA Digital Design — Omnes Education, Paris</p>
                    <p class="team-bio">
                        Mohit is responsible for every pixel you see on screen. He crafted the
                        purple design system, built the responsive CSS, and led the mobile
                        redesign launched in 2025.
                    </p>
                </div>
            </div>

            <!-- Member 4 -->
            <div class="team-card">
                <div class="team-avatar">
                    <img src="https://placehold.co/200x200/9d53d4/ffffff?text=AN" alt="ABDELGHANI Nour">
                </div>
                <div class="team-info">
                    <h3 class="team-name">ABDELGHANI Nour</h3>
                    <p class="team-position">Operations Manager &amp; Branch Coordinator</p>
                    <p class="team-qual">BSc Business Administration — Omnes Education, Lyon</p>
                    <p class="team-bio">
                        Nour manages day-to-day operations and coordinates between all three
                        branches. She built the seller verification process and handles
                        disputes and customer support.
                    </p>
                </div>
            </div>

            <!-- Member 5 -->
            <div class="team-card">
                <div class="team-avatar">
                    <img src="https://placehold.co/200x200/c08de8/1a0533?text=NN" alt="NITIN Nitin">
                </div>
                <div class="team-info">
                    <h3 class="team-name">NITIN Nitin</h3>
                    <p class="team-position">Data Analyst &amp; QA Engineer</p>
                    <p class="team-qual">BSc Data Science — Omnes Education, Bordeaux</p>
                    <p class="team-bio">
                        Nitin keeps the platform running smoothly. He manages testing pipelines,
                        monitors performance, and turns user behaviour data into actionable
                        improvements for the team.
                    </p>
                </div>
            </div>

        </div>
    </section>

    <!-- ── BRANCHES ────────────────────────────────────────────── -->
    <section style="margin-top: 72px; margin-bottom: 72px;">
        <div class="section-title">
            <div>
                <p class="section-label">Where to Find Us</p>
                <h2>Our Branches</h2>
            </div>
            <div class="section-title-line"></div>
        </div>

        <div class="branches-grid">

            <!-- Paris HQ -->
            <div class="branch-card branch-card--hq">
                <div class="branch-icon">🏛️</div>
                <div class="branch-label">Headquarters</div>
                <h3 class="branch-city">Paris</h3>
                <p class="branch-address">12 Rue du Commerce<br>75015 Paris, Île-de-France</p>
                <ul class="branch-details">
                    <li>📞 +33 1 23 45 67 89</li>
                    <li>📧 paris@omnesmp.com</li>
                    <li>🕐 Mon–Sat: 09:00 – 19:00</li>
                    <li>📅 Opened: Q1 2024</li>
                </ul>
                <p class="branch-note">
                    Our founding location and administrative centre. All seller approvals,
                    auction supervision, and platform development happen here.
                </p>
            </div>

            <!-- Lyon -->
            <div class="branch-card">
                <div class="branch-icon">🏙️</div>
                <div class="branch-label">Branch</div>
                <h3 class="branch-city">Lyon</h3>
                <p class="branch-address">8 Place Bellecour<br>69002 Lyon, Auvergne-Rhône-Alpes</p>
                <ul class="branch-details">
                    <li>📞 +33 4 78 12 34 56</li>
                    <li>📧 lyon@omnesmp.com</li>
                    <li>🕐 Mon–Fri: 09:00 – 18:00</li>
                    <li>📅 Opened: Q4 2024</li>
                </ul>
                <p class="branch-note">
                    Our first expansion branch, specialising in High-End and antique items
                    sourced from the Auvergne-Rhône-Alpes region. Managed by Nour Abdelghani.
                </p>
            </div>

            <!-- Bordeaux -->
            <div class="branch-card">
                <div class="branch-icon">🍷</div>
                <div class="branch-label">Branch</div>
                <h3 class="branch-city">Bordeaux</h3>
                <p class="branch-address">5 Allée de Tourny<br>33000 Bordeaux, Nouvelle-Aquitaine</p>
                <ul class="branch-details">
                    <li>📞 +33 5 56 78 90 12</li>
                    <li>📧 bordeaux@omnesmp.com</li>
                    <li>🕐 Tue–Sat: 10:00 – 18:00</li>
                    <li>📅 Opened: Q4 2024</li>
                </ul>
                <p class="branch-note">
                    Our newest branch, with a focus on Regular category items and local
                    artisan sellers. Also home to Nitin's QA and analytics operations.
                </p>
            </div>

        </div>
    </section>

</div><!-- /.container -->

<?php require_once 'templates/footer.php'; ?>