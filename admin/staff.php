<?php
require_once '../config/config.php';
checkAuth();

$roleLabels = [
    'admin'            => 'Admin',
    'ticketing_staff'  => 'Ticketing Staff',
    'tour_guide'       => 'Tour Guide',
    'maintenance_staff'=> 'Maintenance Staff',
    'shop_staff'       => 'Shop Staff',
    'manager'          => 'Manager',
];

$error   = '';
$success = '';

// ── Flash messages from redirects ─────────────────────────────────────────
if (isset($_SESSION['staff_flash'])) {
    $success = $_SESSION['staff_flash'];
    unset($_SESSION['staff_flash']);
}

// ── Delete ────────────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $target = $pdo->prepare("SELECT * FROM admin_users WHERE admin_id=?");
    $target->execute([$del_id]); $target = $target->fetch();

    if (!$target) {
        $error = 'Account not found.';
    } elseif ($target['role'] === 'super_admin') {
        $error = 'Super admin accounts cannot be deleted.';
    } elseif ($del_id === (int)$_SESSION['admin_id']) {
        $error = 'You cannot delete your own account.';
    } else {
        // Nullify guide link if tour_guide
        $pdo->prepare("UPDATE tour_guides SET admin_id=NULL WHERE admin_id=?")->execute([$del_id]);
        $pdo->prepare("DELETE FROM admin_users WHERE admin_id=?")->execute([$del_id]);
        $_SESSION['staff_flash'] = 'Staff account deleted.';
        header('Location: staff.php'); exit;
    }
}

// ── Create / Update ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit_id    = (int)($_POST['admin_id'] ?? 0);
    $full_name  = trim($_POST['full_name']  ?? '');
    $username   = trim($_POST['username']   ?? '');
    $email      = trim($_POST['email']      ?? '');
    $role       = $_POST['role']            ?? '';
    $password   = $_POST['password']        ?? '';
    $password2  = $_POST['password2']       ?? '';
    // Tour guide extras
    $phone          = trim($_POST['phone']          ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $bio            = trim($_POST['bio']            ?? '');

    $allowedRoles = array_keys($roleLabels);

    if (empty($full_name) || empty($username) || empty($email) || empty($role)) {
        $error = 'Full name, username, email, and role are required.';
    } elseif (!in_array($role, $allowedRoles)) {
        $error = 'Invalid role.';
    } elseif ($edit_id === 0 && empty($password)) {
        $error = 'Password is required for new accounts.';
    } elseif (!empty($password) && $password !== $password2) {
        $error = 'Passwords do not match.';
    } else {
        // Check username uniqueness
        $uq = $pdo->prepare("SELECT admin_id FROM admin_users WHERE username=? AND admin_id != ?");
        $uq->execute([$username, $edit_id]);
        if ($uq->fetch()) {
            $error = "Username \"$username\" is already taken.";
        }
    }

    if (!$error) {
        if ($edit_id > 0) {
            // ── Edit existing ──────────────────────────────────────────
            $target = $pdo->prepare("SELECT * FROM admin_users WHERE admin_id=?");
            $target->execute([$edit_id]); $target = $target->fetch();

            if ($target && $target['role'] === 'super_admin' && $role !== 'super_admin') {
                $error = 'Cannot change the role of a super admin.';
            } else {
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE admin_users SET full_name=?,username=?,email=?,role=?,password=? WHERE admin_id=?")
                        ->execute([$full_name,$username,$email,$role,$hash,$edit_id]);
                } else {
                    $pdo->prepare("UPDATE admin_users SET full_name=?,username=?,email=?,role=? WHERE admin_id=?")
                        ->execute([$full_name,$username,$email,$role,$edit_id]);
                }

                // Sync tour_guides record
                if ($role === 'tour_guide') {
                    $tg = $pdo->prepare("SELECT guide_id FROM tour_guides WHERE admin_id=?");
                    $tg->execute([$edit_id]); $tg = $tg->fetch();
                    if ($tg) {
                        $pdo->prepare("UPDATE tour_guides SET full_name=?,email=?,phone=?,specialization=?,bio=? WHERE admin_id=?")
                            ->execute([$full_name,$email,$phone,$specialization,$bio,$edit_id]);
                    } else {
                        $pdo->prepare("INSERT INTO tour_guides (admin_id,full_name,email,phone,specialization,bio) VALUES (?,?,?,?,?,?)")
                            ->execute([$edit_id,$full_name,$email,$phone,$specialization,$bio]);
                    }
                } else {
                    // If role changed away from tour_guide, unlink (keep record, just clear admin_id)
                    $pdo->prepare("UPDATE tour_guides SET admin_id=NULL WHERE admin_id=?")->execute([$edit_id]);
                }

                $_SESSION['staff_flash'] = "Account for \"$full_name\" updated successfully.";
                header('Location: staff.php'); exit;
            }
        } else {
            // ── Create new ────────────────────────────────────────────
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO admin_users (username,password,full_name,email,role) VALUES (?,?,?,?,?)")
                ->execute([$username,$hash,$full_name,$email,$role]);
            $new_id = (int)$pdo->lastInsertId();

            if ($role === 'tour_guide') {
                $pdo->prepare("INSERT INTO tour_guides (admin_id,full_name,email,phone,specialization,bio) VALUES (?,?,?,?,?,?)")
                    ->execute([$new_id,$full_name,$email,$phone,$specialization,$bio]);
            }

            $_SESSION['staff_flash'] = "Account for \"$full_name\" created successfully.";
            header('Location: staff.php'); exit;
        }
    }
}

// ── Fetch all staff (exclude super_admin) ─────────────────────────────────
$staff = $pdo->query("
    SELECT a.*, tg.guide_id, tg.phone, tg.specialization, tg.bio
    FROM admin_users a
    LEFT JOIN tour_guides tg ON a.admin_id = tg.admin_id
    WHERE a.role != 'super_admin'
    ORDER BY a.role, a.full_name
")->fetchAll();

// Group counts
$counts = [];
foreach ($staff as $s) $counts[$s['role']] = ($counts[$s['role']] ?? 0) + 1;

include 'includes/header.php';
?>

<style>
.role-badge {
    display:inline-block;padding:.2rem .65rem;border-radius:12px;
    font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#fff;
}
.role-admin            { background:#6a1b9a; }
.role-ticketing_staff  { background:#1565c0; }
.role-tour_guide       { background:#2e7d32; }
.role-maintenance_staff{ background:#e65100; }
.role-shop_staff       { background:#c4a000; color:#333; }
.role-manager          { background:#37474f; }

.staff-filters { display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem; }
.filter-chip {
    padding:.3rem .85rem;border-radius:20px;border:1.5px solid #ddd;
    background:#fff;cursor:pointer;font-size:.82rem;font-weight:600;transition:.15s;
}
.filter-chip.active,.filter-chip:hover { background:var(--primary-dark,#2A3520);color:#fff;border-color:var(--primary-dark,#2A3520); }
.guide-fields { display:none; }
.guide-fields.visible { display:contents; }
</style>

<div class="page-header">
    <h1>Staff Account Management</h1>
    <button onclick="showCreateModal()" class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Add New Staff
    </button>
</div>

<?php if ($success): ?>
<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Role filter chips -->
<div class="staff-filters">
    <span class="filter-chip active" onclick="filterRole(this,'all')">All (<?= count($staff) ?>)</span>
    <?php foreach ($roleLabels as $rk => $rl): if (!isset($counts[$rk])) continue; ?>
    <span class="filter-chip" onclick="filterRole(this,'<?= $rk ?>')"><?= $rl ?> (<?= $counts[$rk] ?>)</span>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="data-table" id="staffTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Details</th>
                    <th>Since</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($staff as $s): ?>
            <tr data-role="<?= $s['role'] ?>">
                <td><strong><?= htmlspecialchars($s['full_name']) ?></strong></td>
                <td><code style="font-size:.82rem;"><?= htmlspecialchars($s['username']) ?></code></td>
                <td style="font-size:.85rem;"><?= htmlspecialchars($s['email']) ?></td>
                <td><span class="role-badge role-<?= $s['role'] ?>"><?= $roleLabels[$s['role']] ?? $s['role'] ?></span></td>
                <td style="font-size:.82rem;color:#666;">
                    <?php if ($s['role'] === 'tour_guide' && $s['specialization']): ?>
                        <?= htmlspecialchars($s['specialization']) ?>
                        <?php if ($s['phone']): ?> &middot; <?= htmlspecialchars($s['phone']) ?><?php endif; ?>
                    <?php else: ?>
                        <span style="color:#ccc;">—</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:.8rem;color:#888;"><?= date('M d, Y', strtotime($s['created_at'])) ?></td>
                <td>
                    <div class="action-buttons">
                        <button onclick='openEditModal(<?= json_encode($s) ?>)' class="btn-icon" title="Edit">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </button>
                        <?php if ((int)$s['admin_id'] !== (int)$_SESSION['admin_id']): ?>
                        <a href="?delete=<?= $s['admin_id'] ?>" class="btn-icon btn-danger"
                           onclick="return confirm('Delete account for <?= addslashes($s['full_name']) ?>? This cannot be undone.')"
                           title="Delete">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            </svg>
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── Modal ─────────────────────────────────────────────────────────────── -->
<div id="staffModal" class="modal">
    <div class="modal-content" style="max-width:560px;">
        <div class="modal-header">
            <h2 id="modalTitle">Add New Staff</h2>
            <button onclick="closeModal()" class="close-btn">&times;</button>
        </div>
        <form method="POST" action="" id="staffForm">
            <input type="hidden" id="admin_id" name="admin_id" value="0">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group" style="grid-column:1/-1">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" id="f_full_name" required>
                </div>
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" id="f_username" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" id="f_email" required>
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label>Role *</label>
                    <select name="role" id="f_role" required onchange="toggleGuideFields()">
                        <option value="">— Select role —</option>
                        <option value="admin">Admin</option>
                        <option value="manager">Manager</option>
                        <option value="ticketing_staff">Ticketing Staff</option>
                        <option value="tour_guide">Tour Guide</option>
                        <option value="maintenance_staff">Maintenance Staff</option>
                        <option value="shop_staff">Shop Staff</option>
                    </select>
                </div>

                <!-- Tour Guide extra fields -->
                <div class="form-group guide-fields" id="guideFields">
                    <div style="grid-column:1/-1;display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="tel" name="phone" id="f_phone">
                        </div>
                        <div class="form-group">
                            <label>Specialization</label>
                            <input type="text" name="specialization" id="f_specialization" placeholder="e.g. Modern Art, History">
                        </div>
                        <div class="form-group" style="grid-column:1/-1">
                            <label>Bio</label>
                            <textarea name="bio" id="f_bio" rows="2" style="width:100%;padding:.5rem;border:1px solid #ddd;border-radius:4px;resize:vertical;"></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label id="pwLabel">Password *</label>
                    <input type="password" name="password" id="f_password" autocomplete="new-password">
                    <small id="pwHint" style="color:#888;font-size:.75rem;"></small>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="password2" id="f_password2" autocomplete="new-password">
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save Account</button>
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleGuideFields() {
    var role = document.getElementById('f_role').value;
    var gf   = document.getElementById('guideFields');
    gf.classList.toggle('visible', role === 'tour_guide');
}

function showCreateModal() {
    document.getElementById('modalTitle').textContent = 'Add New Staff Account';
    document.getElementById('staffForm').reset();
    document.getElementById('admin_id').value = '0';
    document.getElementById('pwLabel').textContent = 'Password *';
    document.getElementById('pwHint').textContent  = '';
    document.getElementById('f_password').required = true;
    toggleGuideFields();
    document.getElementById('staffModal').style.display = 'flex';
}

function openEditModal(s) {
    document.getElementById('modalTitle').textContent = 'Edit: ' + s.full_name;
    document.getElementById('admin_id').value        = s.admin_id;
    document.getElementById('f_full_name').value     = s.full_name  || '';
    document.getElementById('f_username').value      = s.username   || '';
    document.getElementById('f_email').value         = s.email      || '';
    document.getElementById('f_role').value          = s.role       || '';
    document.getElementById('f_phone').value         = s.phone      || '';
    document.getElementById('f_specialization').value = s.specialization || '';
    document.getElementById('f_bio').value           = s.bio        || '';
    document.getElementById('f_password').value      = '';
    document.getElementById('f_password2').value     = '';
    document.getElementById('f_password').required   = false;
    document.getElementById('pwLabel').textContent   = 'New Password';
    document.getElementById('pwHint').textContent    = 'Leave blank to keep current password.';
    toggleGuideFields();
    document.getElementById('staffModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('staffModal').style.display = 'none';
}

// Role filter
function filterRole(chip, role) {
    document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
    chip.classList.add('active');
    document.querySelectorAll('#staffTable tbody tr').forEach(tr => {
        tr.style.display = (role === 'all' || tr.dataset.role === role) ? '' : 'none';
    });
}

window.onclick = function(e) {
    if (e.target === document.getElementById('staffModal')) closeModal();
};
</script>

<?php include 'includes/footer.php'; ?>
