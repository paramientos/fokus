<?php

namespace App\Http\Controllers;

use App\Services\GmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    private GmailService $gmailService;
    
    public function __construct(GmailService $gmailService)
    {
        $this->gmailService = $gmailService;
    }
    
    public function redirect()
    {
        // Yönlendirme URL'sini kaydet (eğer kullanıcı başka bir sayfadan geldiyse geri dönebilmek için)
        session()->put('google_auth_redirect_back', url()->previous());
        
        return redirect($this->gmailService->getAuthUrl());
    }
    
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            Log::error('Google authentication error: ' . $request->get('error'));
            return redirect()->route('mail.inbox')->with('error', 'Google authentication failed: ' . $request->get('error'));
        }
        
        if (!$request->has('code')) {
            Log::error('Google authentication error: No authorization code received');
            return redirect()->route('mail.inbox')->with('error', 'Google authentication failed: No authorization code received');
        }
        
        try {
            $token = $this->gmailService->handleCallback($request->get('code'));
            
            // Token içeriğini kontrol et
            if (!isset($token['access_token'])) {
                throw new \Exception('Invalid token received from Google');
            }
            
            // Token'ı kullanıcıya özel olarak saklayalım
            Cache::put('google_token_' . auth()->id(), $token, now()->addDays(7));
            
            // Servisin çalıştığından emin olmak için test et
            $this->gmailService->setAccessToken($token);
            
            // Önceki sayfaya yönlendir (varsa)
            $redirectBack = session()->pull('google_auth_redirect_back');
            $targetRoute = $redirectBack ?: route('mail.inbox');
            
            return redirect($targetRoute)->with('success', 'Gmail hesabınız başarıyla bağlandı!');
        } catch (\Exception $e) {
            Log::error('Google authentication error: ' . $e->getMessage());
            return redirect()->route('mail.inbox')->with('error', 'Gmail hesabı bağlanamadı: ' . $e->getMessage());
        }
    }
}
