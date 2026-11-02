<?php
/**
 * Zpracování profilové fotky.
 *
 * Postup (zadání): ověřit soubor -> načíst jako obrázek -> změnit šířku na 800 px
 * (výška podle poměru stran) -> uložit jako JPEG s kvalitou 90. PNG, GIF, BMP, WebP
 * a TIFF se tak převedou na JPEG. Nový soubor se vytváří znovu z pixelů, takže se
 * nedostane na server případný škodlivý kód ukrytý v originálním souboru.
 * Soubor dostane náhodné jméno (nikdy se nepoužije jméno od uživatele).
 */

/**
 * @param array $file položka z $_FILES
 * @return array ['filename' => string|null, 'error' => string|null]
 */
function processProfilePhoto(array $file): array
{
    $cfg = config('upload');
    $fail = fn(string $msg): array => ['filename' => null, 'error' => $msg];

    $code = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($code === UPLOAD_ERR_NO_FILE) {
        return $fail('Vyber profilovou fotku.');
    }
    if ($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE) {
        return $fail('Soubor je příliš velký (maximum ' . round($cfg['max_size'] / 1048576) . ' MB).');
    }
    if ($code !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return $fail('Soubor se nepodařilo nahrát, zkus to znovu.');
    }
    if ($file['size'] > $cfg['max_size']) {
        return $fail('Soubor je příliš velký (maximum ' . round($cfg['max_size'] / 1048576) . ' MB).');
    }

    // typ souboru zjišťujeme z obsahu, ne podle přípony ani podle údaje od prohlížeče
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!in_array($mime, $cfg['mime_types'], true)) {
        return $fail('Povolené formáty: JPEG, PNG, GIF, BMP, TIFF, WebP.');
    }

    $data = file_get_contents($file['tmp_name']);

    // GD neumí TIFF; pokud je na serveru Imagick, převedeme ho na PNG
    if ($mime === 'image/tiff') {
        if (!class_exists('Imagick')) {
            return $fail('Formát TIFF tento server nepodporuje, nahraj JPEG nebo PNG.');
        }
        try {
            $im = new Imagick();
            $im->readImageBlob($data);
            $im->setIteratorIndex(0);
            $im->setImageFormat('png');
            $data = $im->getImageBlob();
        } catch (Exception $ex) {
            return $fail('Soubor není platný obrázek.');
        }
    }

    $info = @getimagesizefromstring($data);
    if ($info === false || $info[0] < 1 || $info[1] < 1) {
        return $fail('Soubor není platný obrázek.');
    }
    if ($info[0] * $info[1] > 40000000) {          // ochrana paměti serveru
        return $fail('Obrázek má příliš velké rozlišení.');
    }

    $src = @imagecreatefromstring($data);
    if ($src === false) {
        return $fail('Soubor není platný obrázek.');
    }

    // fotky z mobilu bývají otočené podle EXIF
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($file['tmp_name']);
        $angle = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 1] ?? 0;
        if ($angle !== 0) {
            $rotated = imagerotate($src, $angle, 0);
            if ($rotated !== false) {
                $src = $rotated;
            }
        }
    }

    $w = imagesx($src);
    $h = imagesy($src);
    $newW = (int) $cfg['width'];
    $newH = max(1, (int) round($h * $newW / $w));

    $dst = imagecreatetruecolor($newW, $newH);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));   // průhlednost PNG/GIF -> bílá
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);

    if (!is_dir($cfg['dir']) && !mkdir($cfg['dir'], 0755, true)) {
        return $fail('Na serveru chybí složka pro fotky.');
    }

    $filename = bin2hex(random_bytes(16)) . '.jpg';
    if (!imagejpeg($dst, $cfg['dir'] . $filename, (int) $cfg['quality'])) {
        return $fail('Fotku se nepodařilo uložit.');
    }

    return ['filename' => $filename, 'error' => null];
}

/** Smaže fotku z disku. Povolí jen jména, která generuje processProfilePhoto(). */
function deleteProfilePhoto(?string $filename): void
{
    if ($filename !== null && preg_match('/^[a-f0-9]{32}\.jpg$/', $filename)) {
        $path = config('upload')['dir'] . $filename;
        if (is_file($path)) {
            unlink($path);
        }
    }
}
