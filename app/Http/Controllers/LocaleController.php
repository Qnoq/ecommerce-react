<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LocaleController extends Controller
{
    /**
     * Change the application locale
     */
    public function change(Request $request)
    {
        $locale = $request->get('locale', 'fr');
        
        if (in_array($locale, available_locales())) {
            Session::put('locale', $locale);
        }
        
        return redirect()->back();
    }
}