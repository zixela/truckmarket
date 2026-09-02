<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Blacklist;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlacklistController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $entries = $request->user()->blacklistEntries()
            ->with('blockedUser')
            ->when($search !== '', fn ($query) => $query->whereHas(
                'blockedUser',
                fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")
            ))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('account.blacklist.index', ['entries' => $entries, 'search' => $search]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $target = User::query()
            ->where('email', $data['identifier'])
            ->orWhere('name', $data['identifier'])
            ->first();

        if (! $target) {
            return back()->withErrors(['identifier' => __('account.blacklist_not_found')]);
        }

        if ($target->id === $request->user()->id) {
            return back()->withErrors(['identifier' => __('account.blacklist_self')]);
        }

        Blacklist::query()->updateOrCreate(
            ['user_id' => $request->user()->id, 'blocked_user_id' => $target->id],
            ['reason' => $data['reason'] ?? null]
        );

        return back()->with('status', __('account.blacklist_added'));
    }

    public function destroy(Request $request, Blacklist $blacklist): RedirectResponse
    {
        abort_unless($blacklist->user_id === $request->user()->id, 403);

        $blacklist->delete();

        return back()->with('status', __('account.blacklist_removed'));
    }
}
