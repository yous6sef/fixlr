<?php
/**
 * FLIX MARKETPLACE - CLOUDINARY CONFIGURATION
 * Secure upload handler for documents and images
 */

class CloudinaryUploadHandler {
    private $cloudName;
    private $apiKey;
    private $apiSecret;
    private $uploadPreset;

    public function __construct() {
        // Load from .env file
        $this->cloudName = getenv('CLOUDINARY_CLOUD_NAME') ?: $_ENV['CLOUDINARY_CLOUD_NAME'] ?? null;
        $this->apiKey = getenv('CLOUDINARY_API_KEY') ?: $_ENV['CLOUDINARY_API_KEY'] ?? null;
        $this->apiSecret = getenv('CLOUDINARY_API_SECRET') ?: $_ENV['CLOUDINARY_API_SECRET'] ?? null;
        $this->uploadPreset = getenv('CLOUDINARY_UPLOAD_PRESET') ?: $_ENV['CLOUDINARY_UPLOAD_PRESET'] ?? null;

        if (!$this->cloudName || !$this->uploadPreset) {
            throw new Exception('Cloudinary configuration missing');
        }
    }

    /**
     * Generate a signed upload signature for secure uploads
     */
    public function getUploadSignature($options = []) {
        $timestamp = time();
        $params = array_merge([
            'timestamp' => $timestamp,
            'upload_preset' => $this->uploadPreset,
        ], $options);

        $signature = $this->generateSignature($params);

        return [
            'signature' => $signature,
            'timestamp' => $timestamp,
            'upload_preset' => $this->uploadPreset,
            'cloud_name' => $this->cloudName,
            'api_key' => $this->apiKey,
        ];
    }

    /**
     * Generate SHA-1 signature for secure uploads
     */
    private function generateSignature($params) {
        $params = array_filter($params, function($v) {
            return $v !== null;
        });
        ksort($params);

        $query_string = http_build_query($params);
        $signature = hash_hmac('sha1', $query_string, $this->apiSecret, true);
        $signature = base64_encode($signature);

        return $signature;
    }

    /**
     * Validate and process uploaded file
     */
    public static function validateUpload($file, $fileType) {
        $maxSize = 10 * 1024 * 1024; // 10MB
        
        // Allowed MIME types
        $allowedTypes = [
            'idCardFront' => ['image/jpeg', 'image/png'],
            'idCardBack' => ['image/jpeg', 'image/png'],
            'criminalRecord' => ['image/jpeg', 'image/png', 'application/pdf'],
            'resume' => ['application/pdf', 'application/msword'],
            'profileImage' => ['image/jpeg', 'image/png'],
        ];

        // Check file size
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'File size exceeds 10MB limit'];
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes[$fileType] ?? [])) {
            return ['success' => false, 'error' => 'Invalid file type for ' . $fileType];
        }

        // Additional validation
        if ($fileType !== 'resume' && $fileType !== 'profileImage') {
            // Verify image dimensions for ID cards
            if (strpos($mimeType, 'image') === 0) {
                $imageInfo = getimagesize($file['tmp_name']);
                if (!$imageInfo) {
                    return ['success' => false, 'error' => 'Invalid image file'];
                }
                // Basic dimension check
                if ($imageInfo[0] < 200 || $imageInfo[1] < 200) {
                    return ['success' => false, 'error' => 'Image too small (minimum 200x200px)'];
                }
            }
        }

        return ['success' => true];
    }

    /**
     * Process multipart form data for Cloudinary upload
     */
    public static function buildFormData($file, $publicId = null) {
        $formData = [
            'file' => new CURLFile($file['tmp_name'], $file['type'], $file['name']),
            'upload_preset' => $_ENV['CLOUDINARY_UPLOAD_PRESET'],
        ];

        if ($publicId) {
            $formData['public_id'] = $publicId;
        }

        return $formData;
    }

    /**
     * Upload file directly to Cloudinary via CURL
     */
    public function uploadToCloudinary($file, $folder = 'flix/documents', $fileType = 'idCardFront') {
        // Validate first
        $validation = self::validateUpload($file, $fileType);
        if (!$validation['success']) {
            return $validation;
        }

        $ch = curl_init();
        $uploadUrl = "https://api.cloudinary.com/v1_1/{$this->cloudName}/auto/upload";

        $postFields = [
            'file' => curl_file_create($file['tmp_name'], $file['type'], $file['name']),
            'upload_preset' => $this->uploadPreset,
            'folder' => $folder,
            'resource_type' => 'auto',
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $uploadUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['success' => false, 'error' => 'Cloudinary upload failed'];
        }

        $result = json_decode($response, true);

        if (!$result || !isset($result['secure_url'])) {
            return ['success' => false, 'error' => 'Invalid Cloudinary response'];
        }

        return [
            'success' => true,
            'url' => $result['secure_url'],
            'publicId' => $result['public_id'],
        ];
    }
}

/**
 * Helper function to get Cloudinary upload widget token
 */
function getCloudinaryToken() {
    $handler = new CloudinaryUploadHandler();
    return $handler->getUploadSignature();
}
?>
