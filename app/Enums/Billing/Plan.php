<?php

namespace App\Enums\Billing;

use Laravel\Cashier\Cashier;

enum Plan: string
{
    case FREE = 'free';
    case PRO = 'pro';

    public function label(): string
    {
        return match ($this) {
            self::FREE => 'Free',
            self::PRO => config('app.name').' Pro',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::FREE => 'fa-music',
            self::PRO => 'fa-star',
        };
    }

    public function filamentColor(): string
    {
        return match ($this) {
            self::FREE => 'gray',
            self::PRO => 'primary',
        };
    }

    public function tagline(): string
    {
        return match ($this) {
            self::FREE => 'Everything you need to start ranking.',
            self::PRO => 'No limits, and a little love for an indie developer.',
        };
    }

    public function price(): string
    {
        return match ($this) {
            self::FREE => Cashier::formatAmount(0, config('billing.pro.currency')),
            self::PRO => Cashier::formatAmount(config('billing.pro.amount'), config('billing.pro.currency')),
        };
    }

    public function billingPeriod(): string
    {
        return match ($this) {
            self::FREE => 'forever',
            self::PRO => 'once, no subscription',
        };
    }

    /**
     * The values a pricing feature's text may reference, read from
     * `config/billing.php` so a changed limit reaches every card.
     *
     * @return array<string, string>
     */
    public function placeholders(): array
    {
        $tierlists = collect(config("billing.tierlist_limits.{$this->value}"));

        return [
            '{rankings}' => $this->formatLimit(config("billing.ranking_limits.{$this->value}")),
            '{tierlists}' => $tierlists->unique()->count() === 1
                ? $this->formatLimit($tierlists->first())
                : 'Up to '.$this->formatLimit($tierlists->contains(null) ? null : $tierlists->max()),
            '{tierlists.artist}' => $this->formatLimit($tierlists->get('artist')),
            '{tierlists.album}' => $this->formatLimit($tierlists->get('album')),
            '{tierlists.track}' => $this->formatLimit($tierlists->get('track')),
            '{tierlist_entries}' => $this->formatLimit(config("billing.tierlist_item_limits.{$this->value}")),
            '{reviews}' => $this->formatLimit(config("billing.review_limits.{$this->value}")),
            '{catalog_folders}' => $this->formatLimit(config("billing.catalog_limits.{$this->value}.folders")),
            '{subfolder_levels}' => number_format(config("billing.catalog_limits.{$this->value}.depth") - 1),
            '{experience}' => match ($multiplier = config("billing.experience_multipliers.{$this->value}")) {
                1 => 'Standard',
                2 => 'Double',
                3 => 'Triple',
                default => "{$multiplier}x",
            },
        ];
    }

    public function fillPlaceholders(string $text): string
    {
        return strtr($text, $this->placeholders());
    }

    private function formatLimit(?int $limit): string
    {
        return is_null($limit) ? 'Unlimited' : number_format($limit);
    }
}
