<?php

namespace App\Seo;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Head\Facades\Head;

final class FaqPage
{
    /**
     * @param  Collection<int, Faq>  $faqs
     */
    public function handle(Collection $faqs): void
    {
        Head::schema([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faqs->map(fn (Faq $faq) => [
                '@type' => 'Question',
                'name' => $faq->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => strip_tags($faq->text),
                ],
            ])->all(),
        ]);
    }
}
