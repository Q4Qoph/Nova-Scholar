<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Models\SchoolMembership;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolContext
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $school = $request->route('school');
        if (! $school instanceof School || $school->status !== 'active') {
            abort(404);
        }

        $membership = $request->user()?->schoolMemberships()
            ->active()
            ->where('school_id', $school->id)
            ->with('roles')
            ->first();
        if (! $membership instanceof SchoolMembership) {
            abort(404);
        }

        $request->attributes->set('school_membership', $membership);

        return $next($request);
    }
}
