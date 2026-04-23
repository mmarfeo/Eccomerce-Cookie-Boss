<?php
namespace App\Helpers;

class Upload
{
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    private const MAX_BYTES     = 5 * 1024 * 1024; // 5 MB
    private const MAX_DIMENSION = 1200; // px

    public static function image(array $file, string $prefix = 'prod'): array
    {
        // Resultado base
        $result = ['success' => false, 'filename' => null, 'error' => null];

        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            $result['error'] = 'Error al subir el archivo.';
            return $result;
        }

        if ($file['size'] > self::MAX_BYTES) {
            $result['error'] = 'El archivo supera el tamaño máximo de 5 MB.';
            return $result;
        }

        // Verificar tipo real con finfo
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, self::ALLOWED_TYPES)) {
            $result['error'] = 'Tipo de archivo no permitido. Solo JPG, PNG o WebP.';
            return $result;
        }

        // Nombre único seguro
        $ext      = match($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        };
        $filename = $prefix . '_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
        $destPath = UPLOAD_PATH . '/' . $filename;

        if (!is_dir(UPLOAD_PATH)) {
            mkdir(UPLOAD_PATH, 0755, true);
        }

        // Procesar y redimensionar con GD
        $image = self::createFromMime($file['tmp_name'], $mimeType);
        if (!$image) {
            $result['error'] = 'No se pudo procesar la imagen.';
            return $result;
        }

        $image = self::resize($image, self::MAX_DIMENSION);

        // Guardar como JPEG (calidad 90) para consistencia
        $saved = false;
        if ($mimeType === 'image/png') {
            $saved = imagepng($image, $destPath, 9);
        } elseif ($mimeType === 'image/webp') {
            $saved = imagewebp($image, $destPath, 90);
        } else {
            $saved = imagejpeg($image, $destPath, 90);
        }
        imagedestroy($image);

        if (!$saved) {
            $result['error'] = 'No se pudo guardar la imagen.';
            return $result;
        }

        $result['success']  = true;
        $result['filename'] = $filename;
        return $result;
    }

    public static function delete(string $filename): void
    {
        $path = UPLOAD_PATH . '/' . $filename;
        if (file_exists($path)) {
            @unlink($path);
        }
    }

    private static function createFromMime(string $path, string $mime): ?\GdImage
    {
        return match($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png'  => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
            default      => null,
        };
    }

    private static function resize(\GdImage $src, int $maxDim): \GdImage
    {
        $origW = imagesx($src);
        $origH = imagesy($src);

        if ($origW <= $maxDim && $origH <= $maxDim) {
            return $src;
        }

        $ratio  = min($maxDim / $origW, $maxDim / $origH);
        $newW   = (int)round($origW * $ratio);
        $newH   = (int)round($origH * $ratio);

        $canvas = imagecreatetruecolor($newW, $newH);

        // Preservar transparencia para PNG
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
        imagefilledrectangle($canvas, 0, 0, $newW, $newH, $transparent);

        imagecopyresampled($canvas, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($src);

        return $canvas;
    }
}
