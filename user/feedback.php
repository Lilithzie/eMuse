<?php
require '../config/database.php';
include 'includes/header.php';

$message = ''; $message_type = '';

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    try {
        $visitor_name       = trim($_POST['visitor_name']);
        $visitor_email      = trim($_POST['visitor_email']);
        $visit_date         = trim($_POST['visit_date']);
        $overall_rating     = (int)$_POST['overall_rating'];
        $exhibition_rating  = (int)$_POST['exhibition_rating'];
        $staff_rating       = (int)$_POST['staff_rating'];
        $facilities_rating  = (int)$_POST['facilities_rating'];
        $comments           = trim($_POST['comments']);
        $category_id        = (int)($_POST['category_id'] ?? 0) ?: null;

        if (empty($visitor_name) || empty($visitor_email) || !$overall_rating) {
            $message = 'Please fill in name, email, and overall rating.'; $message_type = 'error';
        } elseif ($overall_rating < 1 || $overall_rating > 5) {
            $message = 'Rating must be between 1 and 5.'; $message_type = 'error';
        } else {
            $pdo->prepare("
                INSERT INTO visitor_feedback
                    (visitor_name, visitor_email, visit_date, rating, exhibition_rating, staff_rating, facilities_rating, feedback_text, category_id, status)
                VALUES (?,?,?,?,?,?,?,?,?,'pending')
            ")->execute([$visitor_name, $visitor_email, $visit_date ?: null, $overall_rating, $exhibition_rating ?: null, $staff_rating ?: null, $facilities_rating ?: null, $comments ?: '', $category_id]);
            $message = 'Thank you for your feedback, ' . htmlspecialchars($visitor_name) . '! We appreciate your input.';
            $message_type = 'success';
        }
    } catch (Exception $e) {
        $message = 'An error occurred. Please try again.'; $message_type = 'error';
    }
}

// Fetch feedback categories
$categories = $pdo->query("SELECT * FROM feedback_categories ORDER BY name")->fetchAll();

// Recent published/resolved feedback (testimonials)
$testimonials = $pdo->query("
    SELECT visitor_name, rating as overall_rating, feedback_text as comments, visit_date
    FROM visitor_feedback
    WHERE status IN ('reviewed','resolved') AND feedback_text IS NOT NULL AND feedback_text != ''
    ORDER BY created_at DESC LIMIT 6
")->fetchAll();

// Average ratings
$avgStats = $pdo->query("SELECT ROUND(AVG(rating),1) as avg_overall, ROUND(AVG(exhibition_rating),1) as avg_exhibit, ROUND(AVG(staff_rating),1) as avg_staff, ROUND(AVG(facilities_rating),1) as avg_facilities, COUNT(*) as total FROM visitor_feedback")->fetch();
?>

    <div class="container">
        <div style="margin-bottom:2rem;">
            <h1 class="section-title">Share Your Feedback</h1>
            <p class="section-subtitle">Your experience matters to us. Help us improve by sharing what you loved and what we can do better.</p>
        </div>

        <?php if ($message): ?>
        <div style="margin-bottom:1.5rem;padding:1rem;border-radius:4px;background:<?= $message_type=='success'?'#d4edda':'#f8d7da' ?>;border-left:4px solid <?= $message_type=='success'?'#28a745':'#dc3545' ?>;color:<?= $message_type=='success'?'#155724':'#721c24' ?>;">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <!-- Avg ratings strip -->
        <?php if ($avgStats['total'] > 0): ?>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:2rem;background:var(--primary-accent);padding:1.25rem;border-radius:8px;text-align:center;">
            <?php foreach ([['Overall',  $avgStats['avg_overall']],['Exhibitions',$avgStats['avg_exhibit']],['Staff',     $avgStats['avg_staff']],['Facilities',$avgStats['avg_facilities']]] as [$lbl,$val]):?>
            <div>
                <div style="font-size:1.6rem;font-weight:700;color:var(--primary-dark);">
                    <?= $val ?? '—' ?> <span style="font-size:1rem;color:#f9a825;">★</span>
                </div>
                <div style="font-size:.85rem;color:#555;"><?= $lbl ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-bottom:3rem;">
            <!-- Feedback Form -->
            <div style="background:white;padding:2rem;border-radius:8px;border:1px solid var(--border-color);">
                <h2 style="color:var(--primary-dark);margin-bottom:1.5rem;">Leave a Review</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="visitor_name" required placeholder="Your name">
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="visitor_email" required placeholder="your@email.com">
                    </div>
                    <div class="form-group">
                        <label>Visit Date</label>
                        <input type="date" name="visit_date" max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id">
                            <option value="">General Feedback</option>
                            <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Rating inputs using CSS star trick -->
                    <?php
                    $ratingFields = [
                        ['overall_rating',    'Overall Experience *', true],  // maps to 'rating' column via aliased name in form
                        ['exhibition_rating', 'Exhibition Quality',   false],
                        ['staff_rating',      'Staff Helpfulness',    false],
                        ['facilities_rating', 'Facilities & Amenities', false],
                    ];
                    foreach ($ratingFields as [$name,$label,$required]):
                    ?>
                    <div class="form-group">
                        <label><?= $label ?></label>
                        <div style="display:flex;gap:.5rem;">
                            <?php for ($s=1;$s<=5;$s++): ?>
                            <label style="cursor:pointer;font-size:1.5rem;color:#ccc;" id="star-<?= $name ?>-<?= $s ?>">
                                ★
                                <input type="radio" name="<?= $name ?>" value="<?= $s ?>" <?= $required&&$s==5?'required':'' ?> style="display:none;" onchange="updateStars('<?= $name ?>',<?= $s ?>)">
                            </label>
                            <?php endfor; ?>
                            <?php if (!$required): ?><small style="line-height:1.8rem;color:#999;margin-left:.25rem;">(optional)</small><?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="form-group">
                        <label>Comments <small style="color:#888;">(optional)</small></label>
                        <textarea name="comments" rows="4" placeholder="Tell us about your experience…" style="width:100%;padding:.75rem;border:1px solid #ddd;border-radius:4px;resize:vertical;"></textarea>
                    </div>

                    <button type="submit" name="submit_feedback" class="btn btn-primary" style="width:100%;padding:1rem;">Submit Feedback</button>
                </form>
            </div>

            <!-- Testimonials -->
            <div>
                <h2 style="color:var(--primary-dark);margin-bottom:1.5rem;">What Visitors Say</h2>
                <?php if ($testimonials): ?>
                <?php foreach ($testimonials as $t): ?>
                <div style="background:#f9f9f9;padding:1.25rem;border-radius:8px;margin-bottom:1rem;border-left:3px solid var(--primary-light);">
                    <div style="display:flex;justify-content:space-between;margin-bottom:.5rem;">
                        <strong><?= htmlspecialchars($t['visitor_name']) ?></strong>
                        <span style="color:#f9a825;font-size:1.1rem;"><?= str_repeat('★',$t['overall_rating']).'<span style="color:#ddd;">'.str_repeat('★',5-$t['overall_rating']).'</span>' ?></span>
                    </div>
                    <?php if ($t['visit_date']): ?>
                    <div style="font-size:.8rem;color:#999;margin-bottom:.5rem;"><?= date('F j, Y', strtotime($t['visit_date'])) ?></div>
                    <?php endif; ?>
                    <p style="color:#555;font-size:.9rem;margin:0;"><?= htmlspecialchars($t['comments']) ?></p>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div style="padding:2rem;text-align:center;color:#999;">Be the first to leave a review!</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<script>
function updateStars(name, val) {
    for (let s = 1; s <= 5; s++) {
        const el = document.getElementById('star-' + name + '-' + s);
        if (el) el.style.color = s <= val ? '#f9a825' : '#ccc';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
