<?php

namespace App\Models;

use App\Notifications\NewCommentOnRanking;
use App\Notifications\ProPurchaseReceipt;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Notifications\DatabaseNotification;

class Notification extends DatabaseNotification
{
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    protected function userName(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->data['user_name'] ?? null);
    }

    protected function avatar(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->data['avatar'] ?? null);
    }

    protected function message(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->data['message'] ?? null);
    }

    protected function entity(): Attribute
    {
        return Attribute::get(fn (): ?array => $this->data['entity'] ?? null);
    }

    public function isCommentNotification(): bool
    {
        return $this->type === NewCommentOnRanking::class;
    }

    public function description(): string
    {
        return match ($this->type) {
            NewCommentOnRanking::class => ($this->user_name ?? 'Someone').' commented on '.($this->entity['name'] ?? 'your ranking'),
            ProPurchaseReceipt::class => $this->message ?? 'Welcome to Song Rank Pro!',
            default => 'You have a new notification',
        };
    }

    public function actionUrl(): ?string
    {
        return $this->data['url'] ?? null;
    }

    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }
}
