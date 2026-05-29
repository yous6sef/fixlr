<?php
// Configuration settings for Flix application

define('UPLOAD_DIR', __DIR__ . '/uploads');

define('AI_API_KEY', getenv('AI_API_KEY') ?: getenv('OPENAI_API_KEY') ?: '');
define('AI_MODEL', getenv('AI_MODEL') ?: 'text-bison-001');

define('ADMIN_DEFAULT_EMAIL', getenv('ADMIN_DEFAULT_EMAIL') ?: 'admin@flix.com');
define('ADMIN_DEFAULT_PHONE', getenv('ADMIN_DEFAULT_PHONE') ?: '01234567890');
define('ADMIN_DEFAULT_PASSWORD', getenv('ADMIN_DEFAULT_PASSWORD') ?: 'Admin@123');

// Cloudinary configuration (server-side)
define('CLOUDINARY_CLOUD_NAME', getenv('CLOUDINARY_CLOUD_NAME') ?: 'desqcpjgs');
define('CLOUDINARY_API_KEY', getenv('CLOUDINARY_API_KEY') ?: '');
define('CLOUDINARY_API_SECRET', getenv('CLOUDINARY_API_SECRET') ?: '');
