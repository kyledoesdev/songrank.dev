<div class="mt-4 space-y-4">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Notification</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider hidden md:table-cell">Sent At</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider hidden lg:table-cell">Read At</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($notifications as $notification)
                    <tr class="odd:bg-white even:bg-gray-100">
                        <td class="px-4 py-3">
                            {{ $notification->description() }}
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell">
                            <span title="{{ $notification->created_at->toDayDateTimeString() }}">
                                {{ $notification->created_at->diffForHumans() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell">
                            <span title="{{ $notification->read_at?->toDayDateTimeString() }}">
                                {{ $notification->read_at?->diffForHumans() }}
                            </span>
                        </td>
                        <td class="px-2 py-3 text-right">
                            <div class="flex items-center justify-center gap-1">
                                <button
                                    wire:click="markAsRead('{{ $notification->id }}')"
                                    class="btn-primary text-xs"
                                    {{ ! $notification->isUnread() ? 'disabled' : '' }}
                                >
                                    <i class="fa fa-check"></i>
                                </button>
                                <a
                                    href="{{ $notification->actionUrl() }}"
                                    class="btn-secondary text-xs"
                                    target="_blank"
                                    aria-label="Open"
                                    {{ is_null($notification->actionUrl()) ? 'disabled' : '' }}
                                >
                                    <i class="fa fa-eye"></i>
                                </a>
                                <button
                                    wire:click="$dispatch('show-notification-details', { notificationId: '{{ $notification->id }}' })"
                                    class="btn-helper text-xs"
                                    aria-label="Details"
                                >
                                    <i class="fa fa-list"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center">
                            <i class="fa fa-bell-slash text-zinc-300 text-3xl mb-3"></i>
                            <p class="text-sm text-zinc-400">No notifications yet</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $notifications->links() }}
    </div>

    <livewire:notifications.details />
</div>
