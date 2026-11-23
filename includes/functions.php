<?php
declare(strict_types=1);

/** Escape output (XSS protection). Use on EVERY value printed into HTML. */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** CSRF helpers (bonus security; used by forms) */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_valid(?string $token): bool
{
    return isset($_SESSION['csrf']) && is_string($token)
        && hash_equals($_SESSION['csrf'], $token);
}

/** Validate a Y-m-d date string */
function valid_date(string $d): bool
{
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

/**
 * Validate an uploaded avatar and create a 150x150 JPEG thumbnail.
 * Returns [relativePath|null, errorMessage|null].
 */
function process_avatar(array $file, string $uploadDir): array
{
    // 1. Error code + really an HTTP upload
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [null, 'Upload failed (error code ' . ($file['error'] ?? '?') . ').'];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return [null, 'Invalid upload.'];
    }

    // 2. Size limit: 2 MB
    if ($file['size'] > 2097152) {
        return [null, 'File is larger than 2 MB.'];
    }

    // 3. Real MIME type from the file's bytes (not from the client)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if ($mime === 'image/jpeg') {
        $src = @imagecreatefromjpeg($file['tmp_name']);
    } elseif ($mime === 'image/png') {
        $src = @imagecreatefrompng($file['tmp_name']);
    } else {
        return [null, 'Only JPEG and PNG images are allowed.'];
    }
    if (!$src) {
        return [null, 'Image is corrupted.'];
    }

    // 4. GD: center-crop to a square, then resample to 150x150
    $w = imagesx($src);
    $h = imagesy($src);
    $side = min($w, $h);
    $srcX = intdiv($w - $side, 2);
    $srcY = intdiv($h - $side, 2);

    $thumb = imagecreatetruecolor(150, 150);
    $white = imagecolorallocate($thumb, 255, 255, 255); // PNG transparency -> white
    imagefill($thumb, 0, 0, $white);
    imagecopyresampled($thumb, $src, 0, 0, $srcX, $srcY, 150, 150, $side, $side);

    // 5. Random filename; the original name is discarded
    $filename = bin2hex(random_bytes(16)) . '.jpg';
    $ok = imagejpeg($thumb, rtrim($uploadDir, '/\\') . '/' . $filename, 85);

    imagedestroy($src);
    imagedestroy($thumb);

    if (!$ok) {
        return [null, 'Could not save thumbnail (check folder permissions).'];
    }
    return ['uploads/' . $filename, null];
}