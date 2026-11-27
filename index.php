<?php
require "includes/init.php";

$loggedIn = isset($_SESSION["user_id"]);
if ($loggedIn && $_SESSION["role"] == "admin") {
    $homeLink = BASE_URL . "/admin/dashboard.php";
} else {
    $homeLink = BASE_URL . "/user/dashboard.php";
}

$latestNews = [];
$newsError = false;
try {
    $stmt = $pdo->prepare("SELECT title, tag, body, created_at
                           FROM announcements
                           WHERE is_published = :published
                           ORDER BY created_at DESC, id DESC
                           LIMIT :limit");
    $stmt->bindValue(":published", 1, PDO::PARAM_INT);
    $stmt->bindValue(":limit", 3, PDO::PARAM_INT);
    $stmt->execute();
    $latestNews = $stmt->fetchAll();
} catch (PDOException $e) {
    $newsError = true;
}

$showSampleNews = count($latestNews) == 0;
if ($showSampleNews) {
    $latestNews = sampleAnnouncements();
}

$pageTitle = $site["university_name"];
include "includes/header.php";
?>

<section class="hero university-hero">
    <div class="container hero-inner">
        <div>
            <p class="eyebrow"><?php echo e($site["university_short_name"]); ?> | <?php echo e($site["university_name"]); ?></p>
            <h1>Learn. Lead.<br>Be heard.</h1>
            <p>Find your direction, grow your skills and help shape what comes next. At Horizon Valley University, learning is connected to the life and needs of our community.</p>
            <div class="btn-row">
                <a class="btn btn-light" href="#faculties">Explore faculties</a>
                <a class="btn btn-outline-light" href="#admissions">Admissions</a>
            </div>
        </div>
        <div class="hero-visual">
            <svg viewBox="0 0 520 360" role="img" aria-labelledby="heroArtTitle heroArtDesc">
                <title id="heroArtTitle">Horizon Valley University campus</title>
                <desc id="heroArtDesc">A campus building and speech panel represent learning, leadership and student voice.</desc>
                <rect x="20" y="18" width="480" height="324" rx="34" fill="#e6f4f3"/>
                <circle cx="425" cy="82" r="34" fill="#5fd0cf"/>
                <path d="M44 287h432v24H44z" fill="#1f8f94"/>
                <path d="m74 132 125-72 125 72v20H74z" fill="#072a30"/>
                <path d="M94 150h210v137H94z" fill="#031417"/>
                <path d="M175 218h48v69h-48z" fill="#5fd0cf"/>
                <path d="M114 173h28v29h-28zm54 0h28v29h-28zm81 0h28v29h-28z" fill="#e6f4f3"/>
                <path d="M342 143h113a15 15 0 0 1 15 15v75a15 15 0 0 1-15 15h-18l-25 23v-23h-70a15 15 0 0 1-15-15v-75a15 15 0 0 1 15-15Z" fill="#1f8f94"/>
                <path d="M362 177h73m-73 20h57m-57 20h66" stroke="#ffffff" stroke-width="8" stroke-linecap="round"/>
                <circle cx="394" cy="95" r="8" fill="#031417"/>
                <circle cx="418" cy="95" r="8" fill="#031417"/>
            </svg>
            <p class="hero-caption">A campus built around possibility</p>
        </div>
    </div>
</section>

<section class="number-band" aria-label="Horizon Valley University at a glance">
    <div class="container">
        <p class="eyebrow">HVU at a glance <span>Illustrative figures</span></p>
        <div class="number-grid">
            <div><strong>12,400+</strong><span>students</span></div>
            <div><strong>76</strong><span>degree programs</span></div>
            <div><strong>6</strong><span>faculties</span></div>
            <div><strong>42</strong><span>student-led societies</span></div>
        </div>
    </div>
</section>

<section class="section section-white" id="about">
    <div class="container about-grid">
        <div class="about-art">
            <svg viewBox="0 0 460 330" role="img" aria-labelledby="aboutArtTitle">
                <title id="aboutArtTitle">A path through the Horizon Valley campus</title>
                <rect x="16" y="20" width="428" height="290" rx="30" fill="#e6f4f3"/>
                <path d="M40 266h380v19H40z" fill="#1f8f94"/>
                <path d="m88 147 91-56 91 56v119H88z" fill="#072a30"/>
                <path d="M120 169h28v32h-28zm50 0h28v32h-28zm50 0h28v32h-28z" fill="#5fd0cf"/>
                <path d="M159 216h40v50h-40z" fill="#ffffff"/>
                <path d="M284 202c20-48 54-66 89-58 27 6 39 27 48 53" fill="none" stroke="#1f8f94" stroke-width="10" stroke-linecap="round"/>
                <circle cx="331" cy="116" r="16" fill="#5fd0cf"/>
                <circle cx="371" cy="132" r="12" fill="#072a30"/>
            </svg>
        </div>
        <div class="about-copy">
            <p class="eyebrow">A university with purpose</p>
            <h2 class="section-title">Curiosity, grounded in community.</h2>
            <p>Horizon Valley University is a made-for-the-future learning community shaped by the people and places around it. Students pair a strong academic foundation with hands-on projects, thoughtful mentorship and the confidence to ask better questions.</p>
            <p>Our campus brings disciplines together, making room for new ideas to move from the classroom into the wider world.</p>
            <a class="text-link" href="#faculties">Meet our faculties <span aria-hidden="true">&rarr;</span></a>
        </div>
    </div>
</section>

<section class="section section-light" id="faculties">
    <div class="container">
        <p class="eyebrow">Find your field</p>
        <h2 class="section-title">Six faculties. Many ways forward.</h2>
        <p class="section-intro">Explore a field that matches your curiosity, or combine perspectives across disciplines.</p>
        <div class="faculty-grid">
            <article class="card faculty-card">
                <span class="feature-icon"><?php echo iconSvg("book"); ?></span>
                <h3>Arts &amp; Humanities</h3>
                <p>Languages, history, media and the ideas that shape societies.</p>
                <a class="text-link" href="#admissions">Explore study <span aria-hidden="true">&rarr;</span></a>
            </article>
            <article class="card faculty-card">
                <span class="feature-icon"><?php echo iconSvg("science"); ?></span>
                <h3>Natural &amp; Applied Sciences</h3>
                <p>Ask big questions about the natural world and build practical answers.</p>
                <a class="text-link" href="#admissions">Explore study <span aria-hidden="true">&rarr;</span></a>
            </article>
            <article class="card faculty-card">
                <span class="feature-icon"><?php echo iconSvg("tools"); ?></span>
                <h3>Engineering &amp; Technology</h3>
                <p>Design, computing and systems for a changing world.</p>
                <a class="text-link" href="#admissions">Explore study <span aria-hidden="true">&rarr;</span></a>
            </article>
            <article class="card faculty-card">
                <span class="feature-icon"><?php echo iconSvg("people"); ?></span>
                <h3>Business &amp; Public Leadership</h3>
                <p>Build organizations and services that create shared value.</p>
                <a class="text-link" href="#admissions">Explore study <span aria-hidden="true">&rarr;</span></a>
            </article>
            <article class="card faculty-card">
                <span class="feature-icon"><?php echo iconSvg("health"); ?></span>
                <h3>Health &amp; Life Sciences</h3>
                <p>Study living systems and the foundations of healthier communities.</p>
                <a class="text-link" href="#admissions">Explore study <span aria-hidden="true">&rarr;</span></a>
            </article>
            <article class="card faculty-card">
                <span class="feature-icon"><?php echo iconSvg("cap"); ?></span>
                <h3>Education &amp; Community Development</h3>
                <p>Support lifelong learning and locally led change.</p>
                <a class="text-link" href="#admissions">Explore study <span aria-hidden="true">&rarr;</span></a>
            </article>
        </div>
    </div>
</section>

<section class="section section-white" id="campus">
    <div class="container">
        <p class="eyebrow">A campus to make your own</p>
        <h2 class="section-title">Room to learn, make and recharge.</h2>
        <p class="section-intro">Find the spaces, support and people that make a university day feel like yours.</p>
        <div class="facility-grid">
            <article class="facility-card">
                <span class="feature-icon"><?php echo iconSvg("book"); ?></span>
                <h3>Learning commons</h3>
                <p>Quiet reading rooms, group tables and peer study support in one welcoming hub.</p>
            </article>
            <article class="facility-card">
                <span class="feature-icon"><?php echo iconSvg("science"); ?></span>
                <h3>Studios &amp; labs</h3>
                <p>Purpose-built spaces for prototypes, experiments, performances and new ideas.</p>
            </article>
            <article class="facility-card">
                <span class="feature-icon"><?php echo iconSvg("people"); ?></span>
                <h3>Residence &amp; dining</h3>
                <p>Comfortable places to live, eat and find your people between classes.</p>
            </article>
            <article class="facility-card">
                <span class="feature-icon"><?php echo iconSvg("progress"); ?></span>
                <h3>Wellbeing &amp; recreation</h3>
                <p>Fitness, wellness and outdoor spaces for a balanced campus routine.</p>
            </article>
        </div>
    </div>
</section>

<section class="admissions-band" id="admissions">
    <div class="container admissions-inner">
        <div>
            <p class="eyebrow">Your next chapter starts here</p>
            <h2>Bring your questions.<br>Leave with a direction.</h2>
            <p>Explore programs, meet our community and get to know the application process.</p>
        </div>
        <a class="btn btn-light" href="#contact">Talk with admissions <?php echo iconSvg("plus"); ?></a>
    </div>
</section>

<section class="section section-light" id="news">
    <div class="container">
        <div class="section-heading-row">
            <div>
                <p class="eyebrow">Campus notebook</p>
                <h2 class="section-title">Latest news</h2>
                <p class="section-intro">Ideas, events and updates from across HVU.</p>
            </div>
            <a class="text-link" href="<?php echo BASE_URL; ?>/news.php">All news <span aria-hidden="true">&rarr;</span></a>
        </div>
        <?php if ($showSampleNews) { ?>
            <p class="sample-note"><?php echo $newsError ? "Sample stories are shown while live announcements are unavailable." : "Sample stories are shown until HVU publishes announcements."; ?></p>
        <?php } ?>
        <div class="news-grid news-preview">
            <?php foreach ($latestNews as $story) { ?>
                <article class="card news-card">
                    <div class="news-meta">
                        <span class="badge"><?php echo e($story["tag"] != "" ? $story["tag"] : "University news"); ?></span>
                        <time datetime="<?php echo e(date("Y-m-d", strtotime($story["created_at"]))); ?>"><?php echo e(date("d M Y", strtotime($story["created_at"]))); ?></time>
                    </div>
                    <h3><?php echo e($story["title"]); ?></h3>
                    <p><?php echo e($story["body"]); ?></p>
                </article>
            <?php } ?>
        </div>
    </div>
</section>

<section class="section section-white" id="voice-desk">
    <div class="container">
        <div class="section-heading-row">
            <div>
                <p class="eyebrow">Your voice belongs here</p>
                <h2 class="section-title">Meet Voice Desk</h2>
                <p class="section-intro">A clear, respectful way to raise a concern and follow what happens next.</p>
            </div>
            <a class="btn btn-dark" href="<?php echo $loggedIn ? e($homeLink) : BASE_URL . "/auth/register.php"; ?>">Visit Voice Desk <?php echo iconSvg("voice"); ?></a>
        </div>
        <div class="review-grid">
            <article class="card review-card">
                <span class="review-mark" aria-hidden="true">&ldquo;</span>
                <blockquote>&ldquo;I knew where my concern stood, and I could see the next step without having to chase someone.&rdquo;</blockquote>
                <p class="review-byline">First-year student <span>Sample feedback</span></p>
            </article>
            <article class="card review-card">
                <span class="review-mark" aria-hidden="true">&ldquo;</span>
                <blockquote>&ldquo;The reply was thoughtful and specific. It felt like someone had really read what I wrote.&rdquo;</blockquote>
                <p class="review-byline">Commuter student <span>Sample feedback</span></p>
            </article>
            <article class="card review-card">
                <span class="review-mark" aria-hidden="true">&ldquo;</span>
                <blockquote>&ldquo;Being able to submit privately made it easier to explain what I needed help with.&rdquo;</blockquote>
                <p class="review-byline">Graduate student <span>Sample feedback</span></p>
            </article>
        </div>
    </div>
</section>

<section class="section section-light" id="faq">
    <div class="container faq-layout">
        <div>
            <p class="eyebrow">Good to know</p>
            <h2 class="section-title">A few questions, answered.</h2>
            <p class="section-intro">Need a hand with something else? Our admissions team is happy to help.</p>
            <a class="text-link" href="#contact">Contact the university <span aria-hidden="true">&rarr;</span></a>
        </div>
        <div class="faq-list">
            <details>
                <summary>When can I apply to HVU?</summary>
                <p>Applications for the next academic year open in October. Program dates and requirements can vary, so contact admissions for the latest details.</p>
            </details>
            <details>
                <summary>Can I visit the campus?</summary>
                <p>Sample campus tours run on selected weekdays and one Saturday each month. Contact the admissions team to arrange a visit.</p>
            </details>
            <details>
                <summary>Where can I ask about student support?</summary>
                <p>Student Services can connect you with academic advising, wellbeing support, financial guidance and Voice Desk.</p>
            </details>
            <details>
                <summary>How do I raise a concern?</summary>
                <p>Use Voice Desk to submit a concern, choose whether to share it anonymously and follow replies from your account.</p>
            </details>
        </div>
    </div>
</section>

<section class="section contact-section" id="contact">
    <div class="container contact-layout">
        <div>
            <p class="eyebrow">Come say hello</p>
            <h2 class="section-title">Your next step starts with a conversation.</h2>
            <p>Ask about programs, campus visits or student support. We will help you find the right place to start.</p>
        </div>
        <div class="contact-details">
            <p><?php echo iconSvg("mail"); ?><span><strong>Email</strong><a href="mailto:<?php echo e($site["contact_email"]); ?>"><?php echo e($site["contact_email"]); ?></a></span></p>
            <p><?php echo iconSvg("phone"); ?><span><strong>Call</strong><a href="tel:<?php echo e($site["contact_phone"]); ?>"><?php echo e($site["contact_phone"]); ?></a></span></p>
            <p><?php echo iconSvg("pin"); ?><span><strong>Visit</strong><span><?php echo e($site["contact_address"]); ?></span></span></p>
        </div>
    </div>
</section>

<?php include "includes/footer.php"; ?>