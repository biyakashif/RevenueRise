<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CaptchaController extends Controller
{
    public function generate()
    {
        $code = strtoupper(Str::random(5));
        session(['captcha_code' => strtolower($code)]);

        $width = 120;
        $height = 40;
        $image = imagecreate($width, $height);
        
        // Colors
        $bg = imagecolorallocate($image, 240, 240, 240);
        $textColor = imagecolorallocate($image, 51, 51, 51);
        
        // Add noise
        for ($i = 0; $i < 50; $i++) {
            $noiseColor = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
            imagesetpixel($image, rand(0, $width-1), rand(0, $height-1), $noiseColor);
        }
        
        // Add text
        imagestring($image, 5, 35, 12, $code, $textColor);
        
        // Add lines
        for ($i = 0; $i < 3; $i++) {
            $lineColor = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
            imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $lineColor);
        }

        ob_start();
        imagepng($image);
        $imageData = ob_get_contents();
        ob_end_clean();
        imagedestroy($image);

        return response($imageData)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    public function verify(Request $request)
    {
        $input = strtolower(trim($request->input('captcha')));
        $stored = session('captcha_code');
        
        return response()->json([
            'valid' => $input === $stored
        ]);
    }
}