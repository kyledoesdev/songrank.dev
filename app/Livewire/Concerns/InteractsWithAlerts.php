<?php

namespace App\Livewire\Concerns;

/**
 * The bridge to resources/js/alerts.js. Every payload is encoded rather than
 * interpolated, so copy is free to carry apostrophes and newlines without
 * escaping its way out of the statement it sits in.
 */
trait InteractsWithAlerts
{
    protected function flash(
        string $title,
        ?string $message = null,
        string $icon = 'success',
        ?string $submessage = null,
    ): void {
        $this->alert('flash', array_filter([
            'title' => $title,
            'message' => $message,
            'submessage' => $submessage,
            'icon' => $icon,
        ]));
    }

    /**
     * Puts a yes/no in front of an action, calling back into this component
     * when the answer is yes. A destructive one, like a delete, gets a red
     * confirm button rather than the primary purple.
     */
    protected function confirmAction(
        string $action,
        string $title,
        string $message,
        string $confirmText = 'Confirm',
        bool $destructive = false,
    ): void {
        $this->alert('confirm', [
            'title' => $title,
            'message' => $message,
            'confirmText' => $confirmText,
            'componentId' => $this->getId(),
            'action' => $action,
            'destructive' => $destructive,
        ]);
    }

    private function alert(string $handler, array $payload): void
    {
        $payload = json_encode($payload, JSON_THROW_ON_ERROR);

        $this->js("window.{$handler}({$payload});");
    }
}
