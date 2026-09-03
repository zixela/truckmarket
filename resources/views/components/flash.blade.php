@if (session('status'))
    <div class="mb-4 rounded-md border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950/50 px-4 py-3 text-sm text-green-800 dark:text-green-200">
        {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 rounded-md border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/50 px-4 py-3 text-sm text-red-800 dark:text-red-200">
        <ul class="list-inside list-disc space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
