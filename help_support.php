
<?php
session_start();

if (empty($_SESSION['user_logged_in']) || empty($_SESSION['user_id'])) {
    header('Location: user_login.php?redirect=help_support.php');
    exit;
}

$userName = $_SESSION['user_name'] ?? 'Customer';

if (empty($_SESSION['support_csrf'])) {
    $_SESSION['support_csrf'] = bin2hex(random_bytes(32));
}

function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Help &amp; Support | OBRS</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
:root {
    --navy: #101c36;
    --blue: #1769aa;
    --bright: #2563eb;
    --ink: #172033;
    --muted: #657187;
    --line: #e5eaf2;
    --page: #f4f7fb;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: var(--page);
    color: var(--ink);
    font-family: Arial, Helvetica, sans-serif;
}

.navbar, footer {
    background: var(--navy);
}

.navbar {
    box-shadow: 0 5px 18px rgba(16,28,54,.12);
}

.navbar-brand {
    color: #fff !important;
    font-weight: 800;
    letter-spacing: .3px;
}

.greeting {
    color: #fff;
    font-size: .95rem;
}

.hero {
    position: relative;
    overflow: hidden;
    color: #fff;
    padding: clamp(32px,5vw,60px) 24px;
    border-radius: 24px;
    background: linear-gradient(120deg,#102344,#1769aa 58%,#2879e8);
    box-shadow: 0 18px 38px rgba(23,105,170,.18);
}

.hero:after {
    content: "";
    position: absolute;
    width: 250px;
    height: 250px;
    right: -70px;
    top: -105px;
    border: 34px solid rgba(255,255,255,.09);
    border-radius: 50%;
    pointer-events: none;
}

.hero-content {
    position: relative;
    z-index: 1;
}

.hero-icon {
    display: inline-grid;
    place-items: center;
    width: 64px;
    height: 64px;
    border-radius: 20px;
    background: rgba(255,255,255,.15);
    font-size: 30px;
    margin-bottom: 18px;
}

.eyebrow {
    font-size: .76rem;
    font-weight: 800;
    letter-spacing: 1.7px;
    text-transform: uppercase;
}

.hero p {
    color: rgba(255,255,255,.86);
    max-width: 620px;
    margin-inline: auto;
}

.section-heading {
    font-weight: 800;
    letter-spacing: -.4px;
}

.section-subtitle {
    color: var(--muted);
}

.support-card {
    display: flex;
    flex-direction: column;
    min-height: 100%;
    padding: 24px;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 18px;
    box-shadow: 0 7px 22px rgba(20,37,63,.035);
    transition: transform .2s, box-shadow .2s, border-color .2s;
}

.support-card:hover {
    transform: translateY(-4px);
    border-color: #c7d9f5;
    box-shadow: 0 14px 28px rgba(20,37,63,.08);
}

.support-icon {
    display: inline-grid;
    place-items: center;
    width: 54px;
    height: 54px;
    border-radius: 16px;
    background: #edf5ff;
    font-size: 27px;
}

.support-card h3 {
    font-weight: 750;
}

.support-card p {
    color: var(--muted);
    line-height: 1.6;
}

.support-link {
    display: inline-flex;
    gap: 7px;
    width: fit-content;
    margin-top: auto;
    color: var(--blue);
    text-decoration: none;
    font-weight: 700;
}

.support-link:hover {
    color: #0d4e87;
    text-decoration: underline;
}

.ticket-card {
    background: linear-gradient(135deg,#fff,#f7fbff);
    border-color: #cfe0f7;
}

.ticket-card .support-icon {
    background: #e4efff;
}

.faq-wrap, .contact-wrap {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 20px;
    box-shadow: 0 7px 22px rgba(20,37,63,.035);
}

.faq-wrap {
    padding: clamp(18px,3vw,30px);
}

.faq-search {
    max-width: 420px;
}

.accordion-item {
    border-color: var(--line);
}

.accordion-button {
    font-weight: 700;
    color: var(--ink);
}

.accordion-button:not(.collapsed) {
    background: #edf5ff;
    color: #124f89;
    box-shadow: none;
}

.accordion-button:focus {
    box-shadow: 0 0 0 .2rem rgba(37,99,235,.13);
}

.faq-answer {
    color: var(--muted);
    line-height: 1.7;
}

.contact-wrap {
    padding: clamp(20px,3vw,32px);
}

.form-label {
    font-weight: 700;
}

.form-control, .form-select {
    border-color: #dbe2ec;
    border-radius: 10px;
    padding: 11px 13px;
}

.form-control:focus, .form-select:focus {
    border-color: #82b4ef;
    box-shadow: 0 0 0 .2rem rgba(37,99,235,.12);
}

.btn-primary {
    background: var(--bright);
    border-color: var(--bright);
    border-radius: 10px;
    font-weight: 700;
    padding: 11px 17px;
}

.btn-primary:hover {
    background: #1d4ed8;
    border-color: #1d4ed8;
}

.btn-outline-light {
    border-radius: 9px;
    font-weight: 700;
}

.btn-outline-primary {
    border-radius: 10px;
    font-weight: 700;
}

.contact-note {
    border-radius: 14px;
    padding: 15px;
    background: #edf5ff;
    color: #31567d;
}

footer {
    color: #dbe4f3;
    margin-top: 20px;
}

footer p {
    color: #b8c5d9;
}

@media(max-width:575.98px) {
    .hero {
        border-radius: 18px;
    }

    .greeting {
        font-size: .82rem;
    }

    .support-card {
        padding: 20px;
    }
}

@media(prefers-reduced-motion:reduce) {
    *, *:before, *:after {
        transition-duration: .01ms !important;
    }
}
</style>
</head>

<body>

<nav class="navbar">
    <div class="container py-2">
        <a class="navbar-brand" href="user_dashboard.php">🚍 OBRS</a>

        <div class="ms-auto d-flex flex-wrap gap-2 gap-sm-3 align-items-center justify-content-end">
            <span class="greeting">Hi, <?= h($userName) ?></span>
            <a href="user_dashboard.php" class="btn btn-outline-light btn-sm">
                Dashboard
            </a>
        </div>
    </div>
</nav>

<main class="container py-4 py-md-5">

    <!-- HERO -->
    <section class="hero text-center mb-5">
        <div class="hero-content">
            <div class="hero-icon" aria-hidden="true">🎧</div>
            <div class="eyebrow mb-2">We're here to help</div>
            <h1 class="display-6 fw-bold mb-3">How can we help you?</h1>
            <p class="mb-0">
                Find answers about bookings, payments, refunds, and your account —
                or send our support team a message.
            </p>
        </div>
    </section>

    <!-- SUPPORT CATEGORIES -->
    <section class="mb-5">
        <div class="mb-4">
            <h2 class="h3 section-heading mb-2">
                What do you need help with?
            </h2>
            <p class="section-subtitle mb-0">
                Choose a topic to get started.
            </p>
        </div>

        <div class="row g-3">

            <div class="col-md-6 col-lg-4">
                <div class="support-card">
                    <div class="support-icon">🎫</div>
                    <h3 class="h5 mt-3">Booking Issues</h3>
                    <p>Problems with your bus booking or ticket confirmation.</p>
                    <a class="support-link" href="#contact" data-category="Booking">
                        Get booking help →
                    </a>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="support-card">
                    <div class="support-icon">💳</div>
                    <h3 class="h5 mt-3">Payment Issues</h3>
                    <p>Payment deducted, failed payment, or incorrect payment status.</p>
                    <a class="support-link" href="#contact" data-category="Payment">
                        Get payment help →
                    </a>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="support-card">
                    <div class="support-icon">↩️</div>
                    <h3 class="h5 mt-3">Cancellation &amp; Refund</h3>
                    <p>Questions about cancellation requests or refund progress.</p>
                    <a class="support-link" href="#contact" data-category="Cancellation & Refund">
                        Get refund help →
                    </a>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="support-card">
                    <div class="support-icon">📧</div>
                    <h3 class="h5 mt-3">Login &amp; OTP</h3>
                    <p>Help with signing in, account access, or email verification.</p>
                    <a class="support-link" href="#contact" data-category="Login & OTP">
                        Get account help →
                    </a>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="support-card ticket-card">
                    <div class="support-icon">🧾</div>
                    <h3 class="h5 mt-3">My Complaints</h3>
                    <p>Check submitted complaints, their status, and support replies.</p>
                    <a href="my_tickets.php" class="btn btn-outline-primary mt-auto">
                        View My Complaints →
                    </a>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="support-card">
                    <div class="support-icon">💬</div>
                    <h3 class="h5 mt-3">Contact Support</h3>
                    <p>Can't find an answer? Send us your question or complaint.</p>
                    <a class="support-link" href="#contact">
                        Contact the team →
                    </a>
                </div>
            </div>

        </div>
    </section>

    <!-- FAQ -->
    <section class="mb-5" id="faq">
        <div class="faq-wrap">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
                <div>
                    <h2 class="h3 section-heading mb-2">
                        Frequently Asked Questions
                    </h2>
                    <p class="section-subtitle mb-0">
                        Quick answers to common questions.
                    </p>
                </div>

                <div class="faq-search w-100">
                    <label for="faqSearch" class="form-label">Search FAQs</label>
                    <input type="search" id="faqSearch" class="form-control"
                           placeholder="Try payment or refund">
                </div>
            </div>

            <div class="accordion" id="faqAccordion">

                <div class="accordion-item faq-item">
                    <h3 class="accordion-header" id="faqHeading1">
                        <button class="accordion-button" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq1"
                                aria-expanded="true" aria-controls="faq1">
                            How can I book a bus ticket?
                        </button>
                    </h3>
                    <div id="faq1" class="accordion-collapse collapse show"
                         aria-labelledby="faqHeading1" data-bs-parent="#faqAccordion">
                        <div class="accordion-body faq-answer">
                            Open Bus Schedules, select your journey and preferred bus,
                            then follow the booking steps shown on the website.
                        </div>
                    </div>
                </div>

                <div class="accordion-item faq-item">
                    <h3 class="accordion-header" id="faqHeading2">
                        <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq2"
                                aria-expanded="false" aria-controls="faq2">
                            What if my payment succeeds but my ticket is missing?
                        </button>
                    </h3>
                    <div id="faq2" class="accordion-collapse collapse"
                         aria-labelledby="faqHeading2" data-bs-parent="#faqAccordion">
                        <div class="accordion-body faq-answer">
                            Check your booking and payment status first. If the issue
                            continues, submit a support ticket with your booking reference.
                            Never share your password or OTP.
                        </div>
                    </div>
                </div>

                <div class="accordion-item faq-item">
                    <h3 class="accordion-header" id="faqHeading3">
                        <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq3"
                                aria-expanded="false" aria-controls="faq3">
                            How do I ask for help with a refund?
                        </button>
                    </h3>
                    <div id="faq3" class="accordion-collapse collapse"
                         aria-labelledby="faqHeading3" data-bs-parent="#faqAccordion">
                        <div class="accordion-body faq-answer">
                            Submit a ticket using the Cancellation &amp; Refund category.
                            Refund eligibility and timing depend on the booking and
                            cancellation terms.
                        </div>
                    </div>
                </div>

                <div class="accordion-item faq-item">
                    <h3 class="accordion-header" id="faqHeading4">
                        <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq4"
                                aria-expanded="false" aria-controls="faq4">
                            Where can I check my complaint status?
                        </button>
                    </h3>
                    <div id="faq4" class="accordion-collapse collapse"
                         aria-labelledby="faqHeading4" data-bs-parent="#faqAccordion">
                        <div class="accordion-body faq-answer">
                            Open My Complaints to see submitted tickets, their current
                            status, and any reply from the support team.
                        </div>
                    </div>
                </div>

                <div class="accordion-item faq-item">
                    <h3 class="accordion-header" id="faqHeading5">
                        <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq5"
                                aria-expanded="false" aria-controls="faq5">
                            What information should I include in a support ticket?
                        </button>
                    </h3>
                    <div id="faq5" class="accordion-collapse collapse"
                         aria-labelledby="faqHeading5" data-bs-parent="#faqAccordion">
                        <div class="accordion-body faq-answer">
                            Write a clear subject and explain what happened. Include
                            your booking reference when relevant, but never include
                            your password, OTP, or full payment card details.
                        </div>
                    </div>
                </div>

            </div>

            <p id="faqNoResults" class="text-secondary mt-3 mb-0 d-none">
                No matching FAQs. Please contact support below.
            </p>
        </div>
    </section>

    <!-- CONTACT SUPPORT FORM -->
    <section class="contact-wrap mb-5" id="contact">
        <div class="row g-4">

            <div class="col-lg-4">
                <div class="eyebrow text-primary mb-2">Need more help?</div>
                <h2 class="h3 section-heading mb-3">Contact Support</h2>
                <p class="section-subtitle">
                    Tell us what happened. Your request will be saved as a support
                    ticket so you can track it later.
                </p>

                <div class="contact-note mt-4">
                    <strong>💡 Helpful tip</strong>
                    <div class="small mt-1">
                        Choose the closest issue category and describe the problem
                        clearly. Keep your ticket number for reference.
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <form action="submit_ticket.php" method="POST">
                    <input type="hidden" name="csrf_token"
                           value="<?= h($_SESSION['support_csrf']) ?>">

                    <div class="mb-3">
                        <label for="category" class="form-label">Issue Category</label>
                        <select name="category" id="category" class="form-select" required>
                            <option value="">Select a category</option>
                            <option value="Booking">Booking Issue</option>
                            <option value="Payment">Payment Issue</option>
                            <option value="Cancellation & Refund">Cancellation &amp; Refund</option>
                            <option value="Login & OTP">Login &amp; OTP</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" name="subject" id="subject"
                               class="form-control" maxlength="150"
                               placeholder="Briefly describe your problem" required>
                    </div>

                    <div class="mb-3">
                        <label for="message" class="form-label">Describe your problem</label>
                        <textarea name="message" id="message" class="form-control"
                                  rows="5" maxlength="5000"
                                  placeholder="Tell us what happened..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Submit Complaint →
                    </button>
                </form>
            </div>

        </div>
    </section>

</main>

<footer class="py-4">
    <div class="container text-center">
        <strong>🚍 OBRS</strong>
        <p class="small mb-0 mt-2">
            Online Bus Reservation System · Help &amp; Support
        </p>
        <p class="small mb-0 mt-1">
            Submit a ticket and track the reply from My Complaints.
        </p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
const faqSearch = document.getElementById('faqSearch');
const faqItems = Array.from(document.querySelectorAll('.faq-item'));
const faqNoResults = document.getElementById('faqNoResults');

faqSearch.addEventListener('input', function () {
    const query = this.value.trim().toLowerCase();
    let visibleCount = 0;

    faqItems.forEach(function (item) {
        const matches = item.textContent.toLowerCase().includes(query);
        item.classList.toggle('d-none', !matches);

        if (matches) {
            visibleCount++;
        }
    });

    faqNoResults.classList.toggle('d-none', visibleCount > 0);
});

document.querySelectorAll('[data-category]').forEach(function (link) {
    link.addEventListener('click', function () {
        const category = document.getElementById('category');
        category.value = this.getAttribute('data-category');
    });
});
</script>

</body>
</html>