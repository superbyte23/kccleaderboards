<?php

use App\Actions\OptimizeAvatar;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('OptimizeAvatar stores a 256x256 webp and removes the large original size', function () {
    Storage::fake('avatars');

    $img = imagecreatetruecolor(1400, 900);
    $white = imagecolorallocate($img, 255, 255, 255);
    imagefill($img, 0, 0, $white);
    ob_start();
    imagejpeg($img, null, 100);
    $jpg = ob_get_clean();
    $tmp = sys_get_temp_dir().'/avatar-source-'.uniqid().'.jpg';
    file_put_contents($tmp, $jpg);

    $upload = new UploadedFile($tmp, 'avatar.jpg', 'image/jpeg', null, true);

    $path = OptimizeAvatar::run($upload);

    Storage::disk('avatars')->assertExists($path);

    $stored = Storage::disk('avatars')->path($path);
    [$w, $h, $type] = getimagesize($stored);

    expect($w)->toBe(256)
        ->and($h)->toBe(256)
        ->and(image_type_to_mime_type($type))->toBe('image/webp')
        ->and(filesize($stored))->toBeLessThan(strlen($jpg));
});

test('OptimizeAvatar keeps square avatars square', function () {
    Storage::fake('public');

    $img = imagecreatetruecolor(600, 600);
    ob_start();
    imagepng($img);
    $png = ob_get_clean();
    $tmp = sys_get_temp_dir().'/avatar-square-'.uniqid().'.png';
    file_put_contents($tmp, $png);

    $upload = new UploadedFile($tmp, 'avatar.png', 'image/png', null, true);

    $path = OptimizeAvatar::run($upload);

    $stored = Storage::disk('avatars')->path($path);
    [$w, $h] = getimagesize($stored);

    expect($w)->toBe(256)
        ->and($h)->toBe(256);

    unlink($tmp);
});

test('OptimizeAvatar saves directly from a file path', function () {
    Storage::fake('avatars');

    $img = imagecreatetruecolor(300, 200);
    ob_start();
    imagejpeg($img, null, 90);
    $jpg = ob_get_clean();
    $tmp = sys_get_temp_dir().'/avatar-path-'.uniqid().'.jpg';
    file_put_contents($tmp, $jpg);

    $path = OptimizeAvatar::run($tmp);

    Storage::disk('avatars')->assertExists($path);

    [$w, $h] = getimagesize(Storage::disk('avatars')->path($path));

    expect($w)->toBe(256)
        ->and($h)->toBe(256);

    unlink($tmp);
});
