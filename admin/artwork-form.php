<?php
require_once '../config/config.php';
checkAuth();

$artwork = null;
$editMode = false;

if (isset($_GET['id'])) {
    $editMode = true;
    $stmt = $pdo->prepare("SELECT * FROM artworks WHERE artwork_id = ?");
    $stmt->execute([$_GET['id']]);
    $artwork = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $artist = sanitize($_POST['artist']);
    $type = $_POST['type'];
    $description = sanitize($_POST['description']);
    $year_created = sanitize($_POST['year_created']);
    $exhibit_id = $_POST['exhibit_id'] ? (int)$_POST['exhibit_id'] : null;
    $location_id = $_POST['location_id'] ? (int)$_POST['location_id'] : null;
    $acquisition_date = $_POST['acquisition_date'];
    $condition_status = $_POST['condition_status'];
    
    if ($editMode) {
        $stmt = $pdo->prepare("UPDATE artworks SET title = ?, artist = ?, type = ?, description = ?, year_created = ?, exhibit_id = ?, location_id = ?, acquisition_date = ?, condition_status = ? WHERE artwork_id = ?");
        $stmt->execute([$title, $artist, $type, $description, $year_created, $exhibit_id, $location_id, $acquisition_date, $condition_status, $_GET['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO artworks (title, artist, type, description, year_created, exhibit_id, location_id, acquisition_date, condition_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $artist, $type, $description, $year_created, $exhibit_id, $location_id, $acquisition_date, $condition_status]);
    }
    
    header('Location: artworks.php?success=saved');
    exit();
}

$exhibits = $pdo->query("SELECT * FROM exhibits WHERE status = 'active' ORDER BY title")->fetchAll();
$locations = $pdo->query("SELECT * FROM locations ORDER BY name")->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <h1><?php echo $editMode ? 'Edit Artwork' : 'Add New Artwork'; ?></h1>
    <a href="artworks.php" class="btn btn-secondary">Back to Artworks</a>
</div>

<div class="card">
    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label for="title">Artwork Title *</label>
                <input type="text" id="title" name="title" required value="<?php echo $artwork['title'] ?? ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="artist">Artist/Creator *</label>
                <input type="text" id="artist" name="artist" required value="<?php echo $artwork['artist'] ?? ''; ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="type">Type *</label>
                <select id="type" name="type" required>
                    <option value="painting" <?php echo ($artwork['type'] ?? '') == 'painting' ? 'selected' : ''; ?>>Painting</option>
                    <option value="sculpture" <?php echo ($artwork['type'] ?? '') == 'sculpture' ? 'selected' : ''; ?>>Sculpture</option>
                    <option value="artifact" <?php echo ($artwork['type'] ?? '') == 'artifact' ? 'selected' : ''; ?>>Artifact</option>
                    <option value="photograph" <?php echo ($artwork['type'] ?? '') == 'photograph' ? 'selected' : ''; ?>>Photograph</option>
                    <option value="other" <?php echo ($artwork['type'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="year_created">Year Created</label>
                <input type="text" id="year_created" name="year_created" value="<?php echo $artwork['year_created'] ?? ''; ?>" placeholder="e.g. 1889">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="exhibit_id">Current Exhibit</label>
                <select id="exhibit_id" name="exhibit_id">
                    <option value="">Not assigned to exhibit</option>
                    <?php foreach ($exhibits as $exhibit): ?>
                        <option value="<?php echo $exhibit['exhibit_id']; ?>" 
                            <?php echo ($artwork['exhibit_id'] ?? '') == $exhibit['exhibit_id'] ? 'selected' : ''; ?>>
                            <?php echo $exhibit['title']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="location_id">Location *</label>
                <select id="location_id" name="location_id" required>
                    <option value="">Select Location</option>
                    <?php foreach ($locations as $location): ?>
                        <option value="<?php echo $location['location_id']; ?>" 
                            <?php echo ($artwork['location_id'] ?? '') == $location['location_id'] ? 'selected' : ''; ?>>
                            <?php echo $location['name']; ?> (<?php echo $location['floor']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="acquisition_date">Acquisition Date</label>
                <input type="date" id="acquisition_date" name="acquisition_date" value="<?php echo $artwork['acquisition_date'] ?? ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="condition_status">Condition Status *</label>
                <select id="condition_status" name="condition_status" required>
                    <option value="excellent" <?php echo ($artwork['condition_status'] ?? '') == 'excellent' ? 'selected' : ''; ?>>Excellent</option>
                    <option value="good" <?php echo ($artwork['condition_status'] ?? '') == 'good' ? 'selected' : ''; ?>>Good</option>
                    <option value="fair" <?php echo ($artwork['condition_status'] ?? '') == 'fair' ? 'selected' : ''; ?>>Fair</option>
                    <option value="poor" <?php echo ($artwork['condition_status'] ?? '') == 'poor' ? 'selected' : ''; ?>>Poor</option>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group full-width">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="5"><?php echo $artwork['description'] ?? ''; ?></textarea>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Artwork</button>
            <a href="artworks.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
