<?php
// Handle generic image uploads

if (isset($_POST["uploadImage"]) && $_POST["uploadImage"] == "ok") {

    if (!isset($_FILES['imageFile']) || $_FILES['imageFile']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(["status" => 400, "results" => "No file uploaded or upload error."]);
        exit;
    }

    $uploadDir = dirname(dirname(__DIR__)) . '/uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = strtolower(pathinfo($_FILES['imageFile']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
    
    if (!in_array($ext, $allowed)) {
        http_response_code(400);
        echo json_encode(["status" => 400, "results" => "Invalid file type. Allowed: JPG, PNG, GIF, WEBP, PDF."]);
        exit;
    }

    $fileName = 'img_' . time() . '_' . uniqid() . '.' . $ext;
    $targetPath = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($_FILES['imageFile']['tmp_name'], $targetPath)) {
        http_response_code(500);
        echo json_encode(["status" => 500, "results" => "Failed to save uploaded file."]);
        exit;
    }

    // Determine the base URL dynamically based on current request
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST']; // includes port if present
    $baseUrl = $protocol . "://" . $host;
    
    // Si estamos detrás del proxy de Nuxt, el host podría ser diferente o el puerto.
    // Asumiremos ruta absoluta desde la raíz de la API, ej. /uploads/filename.ext
    // O mejor aún, la API principal expone /uploads directamente.
    $publicUrl = $baseUrl . '/uploads/' . $fileName;

    echo json_encode(["status" => 200, "url" => $publicUrl]);
    exit;
}
