<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CaptchaController extends Controller
{
    public function generate()
    {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < 5; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        Session::put('captcha_code', $code);
        
        // Create complex SVG captcha
        $svg = '<svg width="150" height="50" xmlns="http://www.w3.org/2000/svg">';
        
        // Background with gradient
        $svg .= '<defs><linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">';
        $svg .= '<stop offset="0%" style="stop-color:#e3f2fd;stop-opacity:1" />';
        $svg .= '<stop offset="100%" style="stop-color:#bbdefb;stop-opacity:1" /></linearGradient></defs>';
        $svg .= '<rect width="150" height="50" fill="url(#bg)" stroke="#90caf9" stroke-width="2"/>';
        
        // Add noise lines
        for ($i = 0; $i < 8; $i++) {
            $x1 = rand(0, 150); $y1 = rand(0, 50);
            $x2 = rand(0, 150); $y2 = rand(0, 50);
            $color = sprintf('#%02x%02x%02x', rand(100, 200), rand(100, 200), rand(100, 200));
            $svg .= '<line x1="'.$x1.'" y1="'.$y1.'" x2="'.$x2.'" y2="'.$y2.'" stroke="'.$color.'" stroke-width="1" opacity="0.3"/>';
        }
        
        // Add characters with rotation and different positions
        $colors = ['#1976d2', '#388e3c', '#f57c00', '#d32f2f', '#7b1fa2'];
        for ($i = 0; $i < 5; $i++) {
            $x = 15 + ($i * 25) + rand(-3, 3);
            $y = 32 + rand(-5, 5);
            $rotation = rand(-15, 15);
            $fontSize = rand(16, 20);
            $color = $colors[$i];
            
            $svg .= '<text x="'.$x.'" y="'.$y.'" font-family="Arial Black" font-size="'.$fontSize.'" fill="'.$color.'" ';
            $svg .= 'transform="rotate('.$rotation.' '.$x.' '.$y.')" font-weight="bold">'.$code[$i].'</text>';
        }
        
        // Add noise dots
        for ($i = 0; $i < 30; $i++) {
            $cx = rand(0, 150); $cy = rand(0, 50);
            $color = sprintf('#%02x%02x%02x', rand(150, 255), rand(150, 255), rand(150, 255));
            $svg .= '<circle cx="'.$cx.'" cy="'.$cy.'" r="1" fill="'.$color.'" opacity="0.5"/>';
        }
        
        $svg .= '</svg>';
        
        return response($svg)->header('Content-Type', 'image/svg+xml');
    }
    
    public function verify(Request $request)
    {
        $captcha = strtoupper($request->input('captcha'));
        $sessionCaptcha = Session::get('captcha_code');
        
        return response()->json([
            'valid' => $captcha == $sessionCaptcha
        ]);
    }
}