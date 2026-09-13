<?php

use App\Actions\OptimizeAvatar;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/*
 * Tests for the adaptive avatar-upload plan (AVATAR-UPLOAD-PLAN.md).
 *
 * Client-side compression (resources/js/avatar-upload.js) handles most of
 * the heavy lifting in the browser, so server-side coverage focuses on:
 *  1. Validation rules (max:5120, image, custom messages).
 *  2. OptimizeAvatar still compresses a JPEG/PNG source correctly.
 *  3. Non-image files are rejected with the correct message.
 *
 * JS compression is verified by Vite's smoke build (it must parse)
 * and by the __avatarCompressor probe exposed for automated checks.
 */

test('valid image at most 5MB passes validation', function () {
    $file = UploadedFile::fake()->image('avatar.jpg', 600, 600)->size(1200);

    $data = ['avatar' => $file];
    $rules = ['avatar' => 'nullable|image|max:5120'];

    $validated = validator($data, $rules)->validate();

    expect($validated)->toHaveKey('avatar')
        ->and($file->isValid())->toBeTrue();
});

test('image over 5MB is rejected with friendly custom message', function () {
    $huge = UploadedFile::fake()->image('huge.jpg', 4000, 3000)->size(6000);

    $data = ['avatar' => $huge];
    $rules = ['avatar' => 'nullable|image|max:5120'];
    $messages = ['avatar.max' => 'That image is still too large after compression (limit 5 MB). Please pick a smaller photo.'];

    $this->expectException(ValidationException::class);

    try {
        validator($data, $rules, $messages)->validate();
    } catch (ValidationException $e) {
        expect($e->validator->errors()->first('avatar'))
            ->toContain('That image is still too large after compression');
        throw $e;
    }
});

test('non-image file is rejected with friendly custom message', function () {
    $txt = UploadedFile::fake()->create('notes.txt', 100, 'text/plain');

    $data = ['avatar' => $txt];
    $rules = ['avatar' => 'nullable|image|max:5120'];
    $messages = ['avatar.image' => 'The avatar must be a JPG, PNG, WebP or GIF image.'];

    $this->expectException(ValidationException::class);

    try {
        validator($data, $rules, $messages)->validate();
    } catch (ValidationException $e) {
        expect($e->validator->errors()->first('avatar'))
            ->toContain('The avatar must be a JPG, PNG, WebP or GIF image.');
        throw $e;
    }
});

test('OptimizeAvatar still produces 256x256 webp from JPEG source', function () {
    Storage::fake('avatars');

    $img = imagecreatetruecolor(1200, 900);
    $white = imagecolorallocate($img, 255, 255, 255);
    imagefill($img, 0, 0, $white);
    ob_start();
    imagejpeg($img, null, 90);
    $jpg = ob_get_clean();
    $tmp = sys_get_temp_dir().'/avatar-jpeg-'.uniqid().'.jpg';
    file_put_contents($tmp, $jpg);

    $path = OptimizeAvatar::run($tmp);

    Storage::disk('avatars')->assertExists($path);

    $stored = Storage::disk('avatars')->path($path);
    [$w, $h, $type] = getimagesize($stored);

    expect($w)->toBe(256)
        ->and($h)->toBe(256)
        ->and(image_type_to_mime_type($type))->toBe('image/webp');

    @unlink($tmp);
});

test('OptimizeAvatar produces 256x256 webp from PNG source', function () {
    Storage::fake('avatars');

    $img = imagecreatetruecolor(300, 300);
    ob_start();
    imagepng($img);
    $png = ob_get_clean();
    $tmp = sys_get_temp_dir().'/avatar-png-'.uniqid().'.png';
    file_put_contents($tmp, $png);

    $path = OptimizeAvatar::run($tmp);

    $stored = Storage::disk('avatars')->path($path);
    [$w, $h] = getimagesize($stored);

    expect($w)->toBe(256)
        ->and($h)->toBe(256)
        ->and(filesize($stored))->toBeLessThan(filesize($tmp));

    @unlink($tmp);
});

test('non-image file content is rejected even when named as jpg', function () {
    $fake = UploadedFile::fake()->create('fake.jpg', 200, 'application/pdf');

    $data = ['avatar' => $fake];
    $rules = ['avatar' => 'nullable|image|max:5120'];

    $v = validator($data, $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('avatar'))->toBeTrue();
});

test('avatar-property nullable allows missing avatar without error', function () {
    $data = [];
    $rules = ['avatar' => 'nullable|image|max:5120'];

    $validated = validator($data, $rules)->validate();

    expect($validated)->not->toHaveKey('avatar');
});
