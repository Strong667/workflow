<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AvatarUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'file' => ['required', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            // Файл крупнее upload_max_filesize PHP до правил не доходит —
            // Laravel сообщает об этом отдельным ключом uploaded.
            'file.uploaded'   => 'Не удалось загрузить файл: он больше лимита PHP ('.ini_get('upload_max_filesize').')',
            'file.required'   => 'Файл не выбран',
            'file.image'      => 'Файл должен быть изображением',
            'file.mimes'      => 'Допустимые форматы: JPG, PNG, WEBP, GIF',
            'file.max'        => 'Размер файла не должен превышать 2 МБ',
            'file.dimensions' => 'Максимальный размер изображения — 4000×4000 пикселей',
        ];
    }
}
