@php
    $current = app()->getLocale();
    $segments = request()->segments();
@endphp

<div class="flex items-center gap-1 rounded-md border border-gray-200 p-1">
    @foreach (\App\Http\Middleware\SetLocale::SUPPORTED as $locale)
        @php
            $target = $segments;
            $target[0] = $locale;
            $url = url(implode('/', $target)).(request()->getQueryString() ? '?'.request()->getQueryString() : '');
        @endphp
        <a href="{{ $url }}"
           class="rounded px-2 py-1 text-xs font-medium uppercase {{ $current === $locale ? 'bg-brand-500 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
            {{ $locale }}
        </a>
    @endforeach
</div>
