<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test Upload Picture</title>
</head>
<body>
    <h2>Test Upload Picture</h2>
    <?php
    $uploadDir = __DIR__ . '/../../public/assets/img/students/';
    $message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
            $filename = basename($_FILES['picture']['name']);
            $targetPath = $uploadDir . $filename;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            if (move_uploaded_file($_FILES['picture']['tmp_name'], $targetPath)) {
                $message = 'Upload successful!';
            } else {
                $message = 'Upload failed!';
            }
        } else {
            $message = 'No file uploaded or upload error.';
        }
    }
    ?>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="picture" accept="image/*" required>
        <button type="submit">Upload</button>
    </form>
    <p><?php echo htmlspecialchars($message); ?></p>
    <?php
    // Show uploaded images
    if (is_dir($uploadDir)) {
        $files = array_diff(scandir($uploadDir), array('.', '..'));
        foreach ($files as $file) {
            $imgPath = 'assets/img/students/' . $file;
            echo '<div><img src="' . $imgPath . '" style="max-width:200px;"><br>' . htmlspecialchars($file) . '</div>';
        }
    }
    ?>
</body>
</html>