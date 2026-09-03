@props(['score' => 0])

<span class="text-brand-500" aria-label="{{ $score }}">
    {{ str_repeat('★', (int) round($score)) }}<span class="text-gray-300 dark:text-gray-600">{{ str_repeat('★', max(0, 5 - (int) round($score))) }}</span>
</span>
