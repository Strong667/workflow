<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AvatarStorage
{
    private const DISK      = 'public';
    private const DIRECTORY = 'avatars';

    /** Сторона итогового изображения: аватар нигде не показывается крупнее. */
    private const SIZE = 512;

    /**
     * Приводит изображение к квадрату и сохраняет, возвращая публичный URL.
     *
     * Обрезка нужна и на сервере: область выбирает клиент, но через API
     * могут прислать что угодно, а растянутый прямоугольник в круглом
     * аватаре выглядит сжатым.
     */
    public function store(UploadedFile $file): string
    {
        [$binary, $extension] = $this->normalize($file);

        $path = self::DIRECTORY.'/'.Str::uuid()->toString().'.'.$extension;
        Storage::disk(self::DISK)->put($path, $binary);

        return Storage::disk(self::DISK)->url($path);
    }

    /**
     * Центральная обрезка до квадрата и уменьшение до SIZE.
     *
     * @return array{0: string, 1: string} бинарное содержимое и расширение
     */
    private function normalize(UploadedFile $file): array
    {
        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($source === false) {
            // Формат не по зубам GD — сохраняем как есть, валидация файл уже проверила.
            return [(string) file_get_contents($file->getRealPath()), strtolower($file->getClientOriginalExtension() ?: 'png')];
        }

        $width  = imagesx($source);
        $height = imagesy($source);
        $side   = min($width, $height);
        $size   = min(self::SIZE, $side);

        $canvas = imagecreatetruecolor($size, $size);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));

        imagecopyresampled(
            $canvas,
            $source,
            0, 0,
            (int) (($width - $side) / 2),
            (int) (($height - $side) / 2),
            $size, $size,
            $side, $side
        );

        ob_start();
        $extension = strtolower($file->getClientOriginalExtension() ?: 'png');

        match ($extension) {
            'jpg', 'jpeg' => imagejpeg($canvas, null, 88),
            'webp'        => imagewebp($canvas, null, 88),
            default       => imagepng($canvas, null, 8),
        };

        $binary = (string) ob_get_clean();

        imagedestroy($canvas);
        imagedestroy($source);

        return [$binary, in_array($extension, ['jpg', 'jpeg', 'webp'], true) ? $extension : 'png'];
    }

    /**
     * Удаляет файл, если ссылка ведёт в наше хранилище.
     * Внешние URL (их можно задать через API) не трогаем.
     */
    public function delete(?string $url): void
    {
        $path = $this->pathFromUrl($url);

        if ($path !== null && Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    /** Заменяет аватар: сохраняет новый и подчищает предыдущий. */
    public function replace(?string $previousUrl, ?string $nextUrl): void
    {
        if ($previousUrl !== null && $previousUrl !== $nextUrl) {
            $this->delete($previousUrl);
        }
    }

    private function pathFromUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $base = rtrim(Storage::disk(self::DISK)->url(self::DIRECTORY), '/').'/';

        if (! str_starts_with($url, $base)) {
            return null;
        }

        $name = basename(parse_url($url, PHP_URL_PATH) ?: '');

        return $name === '' ? null : self::DIRECTORY.'/'.$name;
    }
}
