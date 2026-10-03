<?php

namespace App\Http\Middleware;

use App\Support\ResearchToken;
use Closure;
use Illuminate\Http\Request;

class DecodeResearchToken
{
    public function handle(Request $request, Closure $next)
    {
        $id = ResearchToken::decode((string) $request->route('id'));
        abort_if($id === null, 404);

        $request->route()->setParameter('id', $id);

        return $next($request);
    }
}