<?php
namespace App\Helpers;

use App\Models\Setting;

class Branding
{
    private static ?array $cache = null;

    public static function settings(): array
    {
        if (self::$cache !== null) return self::$cache;
        $m = new Setting();
        self::$cache = [
            'logo'       => $m->get('store_logo', ''),
            'primary'    => self::sanitizeColor($m->get('brand_color_primary',   '#bf9663')),
            'secondary'  => self::sanitizeColor($m->get('brand_color_secondary', '#FF8D08')),
            'dark'       => self::sanitizeColor($m->get('brand_color_dark',      '#2d2016')),
            'text'       => self::sanitizeColor($m->get('brand_color_text',      '#4a3728')),
            'font'       => self::sanitizeFont($m->get('brand_font', 'Poppins')),
            'store_name' => $m->get('store_name', 'Cookie Bakery'),
        ];
        return self::$cache;
    }

    /**
     * Generar bloque <style> con variables CSS dinámicas + Google Font link.
     */
    public static function injectStyles(): string
    {
        $b = self::settings();
        $p = $b['primary'];
        $s = $b['secondary'];
        $d = $b['dark'];
        $t = $b['text'];
        $f = htmlspecialchars($b['font']);

        // Calcular variantes de color (más oscuro / más claro) con hex manipulation
        $pDark  = self::darken($p, 20);
        $pLight = self::lighten($p, 20);
        $border = self::lighten($p, 50);

        return "
<link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">
<link href=\"https://fonts.googleapis.com/css2?family={$f}:wght@400;500;600;700;800&display=swap\" rel=\"stylesheet\">
<style>
:root {
  --gold:        {$p} !important;
  --gold-dark:   {$pDark} !important;
  --gold-light:  {$pLight} !important;
  --orange:      {$s} !important;
  --orange-dark: " . self::darken($s, 15) . " !important;
  --dark:        {$d} !important;
  --text:        {$t} !important;
  --border:      {$border} !important;
  --cream:       " . self::lighten($p, 65) . " !important;
}
body, input, select, textarea, button { font-family: '{$f}', sans-serif !important; }
</style>";
    }

    public static function logoUrl(): string
    {
        $b = self::settings();
        return $b['logo'] ? UPLOAD_URL . '/' . $b['logo'] : '';
    }

    public static function storeName(): string
    {
        return htmlspecialchars(self::settings()['store_name']);
    }

    // ── Utilidades de color ────────────────────────────────────

    private static function sanitizeColor(string $hex): string
    {
        $hex = preg_replace('/[^0-9a-fA-F#]/', '', $hex);
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) return '#bf9663';
        return $hex;
    }

    private static function sanitizeFont(string $font): string
    {
        $allowed = ['Poppins', 'Lato', 'Montserrat', 'Raleway', 'Nunito', 'Inter', 'Open+Sans', 'Playfair+Display'];
        return in_array(str_replace(' ', '+', $font), array_map(fn($f) => str_replace(' ', '+', $f), $allowed))
            ? str_replace(' ', '+', $font)
            : 'Poppins';
    }

    private static function darken(string $hex, int $amount): string
    {
        [$r, $g, $b] = self::hexToRgb($hex);
        return self::rgbToHex(max(0, $r - $amount), max(0, $g - $amount), max(0, $b - $amount));
    }

    private static function lighten(string $hex, int $amount): string
    {
        [$r, $g, $b] = self::hexToRgb($hex);
        return self::rgbToHex(min(255, $r + $amount), min(255, $g + $amount), min(255, $b + $amount));
    }

    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    private static function rgbToHex(int $r, int $g, int $b): string
    {
        return '#' . sprintf('%02x%02x%02x', $r, $g, $b);
    }
}
