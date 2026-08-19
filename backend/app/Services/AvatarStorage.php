<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AvatarStorage
{
    private const DISK      = 'public';
    private const DIRECTORY = 'avatars';

    /** Сохраняет загруженный файл и возвращает публичный URL. */
    public function store(UploadedFile $file): string
    {
        $name = Str::uuid()->toString().'.'.strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $path = $file->storeAs(self::DIRECTORY, $name, self::DISK);

        return Storage::disk(self::DISK)->url($path);
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
