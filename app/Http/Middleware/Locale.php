<?php
/**
 * Created by IntelliJ IDEA.
 * User: bekir.eldem
 * Date: 06/11/2017
 * Time: 00:37
 */

namespace App\Http\Middleware;

use Illuminate\Support\Facades\App;
use Closure, Session, Auth;



class Locale
{
    /**
     * The availables languages.
     *
     * @array $languages
     */
    protected $languages = ['en','tr'];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function __construct ($request, Closure $next)
    {
        if(!Session::has('locale'))
        {
            Session::put('locale', $request->getPreferredLanguage($this->languages));
        }

        app()->setLocale(Session::get('locale'));

        return $next($request);
    }


}