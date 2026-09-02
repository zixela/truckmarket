@extends('layouts.app')

@section('title', __('auth.choose_role_title'))

@section('content')
<div class="mx-auto max-w-md rounded-lg border border-gray-200 bg-white p-6">
    <h1 class="mb-2 text-xl font-bold">{{ __('auth.choose_role_title') }}</h1>
    <p class="mb-4 text-sm text-gray-600">{{ __('auth.choose_role_intro') }}</p>

    <form method="POST" action="{{ route('auth.role.store') }}" class="space-y-4">
        @csrf
        <div class="grid grid-cols-2 gap-2">
            @foreach ($roles as $role)
                <label class="cursor-pointer rounded-md border border-gray-300 px-3 py-2 text-sm has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                    <input type="radio" name="role" value="{{ $role->value }}" class="sr-only" @checked($loop->first)>
                    {{ $role->label() }}
                </label>
            @endforeach
        </div>

        <button type="submit" class="w-full rounded-md bg-brand-500 px-4 py-2 font-medium text-white hover:bg-brand-600">
            {{ __('common.save') }}
        </button>
    </form>
</div>
@endsection
