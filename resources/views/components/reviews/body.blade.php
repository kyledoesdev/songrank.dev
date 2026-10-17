@props(['review'])

<article {{ $attributes->class([
    'prose prose-sm sm:prose-base prose-zinc max-w-none',
    'prose-headings:font-semibold prose-headings:text-zinc-800 prose-headings:leading-tight',
    'prose-h1:text-2xl sm:prose-h1:text-3xl prose-h2:text-xl sm:prose-h2:text-2xl prose-h3:text-lg sm:prose-h3:text-xl',
    'prose-h1:border-b prose-h1:border-zinc-200 prose-h1:pb-2 prose-h2:border-b prose-h2:border-zinc-200 prose-h2:pb-1',
    'prose-a:text-purple-600 prose-a:font-semibold prose-a:no-underline prose-a:border-b prose-a:border-dashed prose-a:border-purple-300 hover:prose-a:border-solid',
    'prose-li:my-0.5 prose-li:marker:text-purple-400 [&_li>p]:my-0',
    'prose-blockquote:border-purple-300 prose-blockquote:bg-purple-50 prose-blockquote:py-1 prose-blockquote:not-italic prose-blockquote:font-normal prose-blockquote:text-zinc-700',
    'prose-hr:border-zinc-200 prose-hr:my-8',
    'prose-code:bg-zinc-100 prose-code:px-1 prose-code:py-0.5 prose-code:rounded prose-code:text-sm prose-code:font-normal prose-code:before:content-none prose-code:after:content-none',
    'prose-pre:text-sm prose-pre:overflow-x-auto',
    '[&_mark]:bg-amber-100 [&_mark]:px-0.5 [&_mark]:rounded-sm',
]) }}>
    {!! $review->body !!}
</article>
