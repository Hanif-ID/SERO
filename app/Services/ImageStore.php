<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Pengelola gambar unggahan. File disimpan di public/uploads/<folder>/<nama-acak>.<ext>
 * dan di database hanya disimpan path relatifnya (mis. "products/abc123.jpg").
 */
class ImageStore
{
    /** Aturan validasi standar: hanya JPG/PNG/WEBP (SVG ditolak karena berisiko XSS), maks 2 MB. */
    public const RULES = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];

    public static function put(UploadedFile $file, string $dir): string
    {
        return $file->store($dir, 'uploads'); // nama acak, ekstensi sesuai isi file
    }

    public static function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('uploads')->delete($path);
        }
    }

    /**
     * Tentukan nilai kolom gambar setelah form disubmit:
     *  - ada file baru        -> simpan file baru, hapus yang lama
     *  - centang "hapus"      -> hapus file lama, kolom jadi null
     *  - selain itu           -> pertahankan yang lama
     */
    public static function resolve(Request $request, string $field, string $dir, ?string $current): ?string
    {
        if ($request->hasFile($field)) {
            self::delete($current);

            return self::put($request->file($field), $dir);
        }

        if ($request->boolean("remove_{$field}")) {
            self::delete($current);

            return null;
        }

        return $current;
    }

    /** URL publik gambar, atau gambar cadangan jika kosong. */
    public static function url(?string $path, ?string $fallback = null): ?string
    {
        if ($path) {
            return asset('uploads/'.$path);
        }

        return $fallback ? asset($fallback) : null;
    }
}
