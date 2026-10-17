<div>
    <x-card class="mt-2 p-4 md:p-6 overflow-visible!">
        <!-- Profile Card Section -->
        <x-card class="overflow-visible!">
            <div class="p-4 flex items-center gap-6">
                <div class="shrink-0">
                    <img
                        src="{{ auth()->user()->avatar }}"
                        alt="{{ auth()->user()->name }}"
                        class="w-16 h-16 rounded-full object-cover border-2 border-slate-200"
                    >
                </div>

                <div class="grow">
                    <h4 class="text-lg font-semibold text-slate-900">{{ auth()->user()->name }}</h4>
                    <div class="flex items-center gap-1">
                        <p class="text-sm text-slate-600">
                            <span>{{ auth()->user()->email }}</span>
                        </p>
                        <x-tooltip
                            text="Your email address is configured via Spotify. If incorrect, please reach out via the support bubble to have it fixed."
                        />
                    </div>
                </div>
            </div>
        </x-card>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-4">
            <!-- Preferences Section -->
            <x-card>
                <x-card.header>
                    <h5 class="font-semibold">Preferences</h5>
                </x-card.header>

                <div class="p-4 space-y-6">
                    <div class="group">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Newsletter Emails</label>
                        <div class="relative">
                            <select
                                class="w-full appearance-none bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-purple-400 focus:border-transparent transition-all duration-200 group-hover:border-slate-300"
                                wire:change="updateSetting('recieve_newsletter_emails', $event.target.value === 'true')"
                            >
                                <option value="true" {{ auth()->user()->preferences->recieve_newsletter_emails ? 'selected' : '' }}>Yes, send me updates</option>
                                <option value="false" {{ !auth()->user()->preferences->recieve_newsletter_emails ? 'selected' : '' }}>No newsletters please</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none">
                                <i class="fa fa-chevron-down text-slate-400 text-sm"></i>
                            </div>
                        </div>
                    </div>

                    <div class="group">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Who Can See Your Catalog</label>
                        <div class="relative">
                            <select
                                class="w-full appearance-none bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-purple-400 focus:border-transparent transition-all duration-200 group-hover:border-slate-300"
                                wire:change="updateSetting('catalog_is_public', $event.target.value === 'true')"
                            >
                                <option value="false" {{ ! auth()->user()->preferences->catalog_is_public ? 'selected' : '' }}>Only me</option>
                                <option value="true" {{ auth()->user()->preferences->catalog_is_public ? 'selected' : '' }}>Anyone, from my profile</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none">
                                <i class="fa fa-chevron-down text-slate-400 text-sm"></i>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">
                            A public catalog opens from your profile. Visitors only see the rankings, tier lists and reviews that are public and finished.
                        </p>
                    </div>
                </div>
            </x-card>

            <!-- Account Actions Section -->
            <x-card class="flex flex-col">
                <x-card.header>
                    <h5 class="font-semibold">Account Actions</h5>
                </x-card.header>

                <div class="p-4 grow">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Email Address</label>
                    <input
                        type="email"
                        value="{{ auth()->user()->email }}"
                        disabled
                        class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm text-slate-600 cursor-not-allowed"
                    >
                </div>

                <x-card.footer class="flex items-center justify-between gap-2">
                    <p class="text-xs text-slate-500">Download your rankings as a backup</p>

                    <button
                        class="btn-primary px-3 py-1 inline-flex items-center gap-2"
                        onclick="window.confirm({
                            title: 'Begin downloading your data?',
                            message: 'Would you like to download all of your rankings? The export will be queued and emailed to {{ auth()->user()->email }} (your spotify email address) when completed. Please make sure you have access to this email address and the inbox is not full or you won\'t be able to access the rankings.',
                            confirmText: 'Go',
                            componentId: '{{ $this->getId() }}',
                            action: 'download'
                        });"
                    >
                        <i class="fa fa-download"></i>
                        <span>Export Your Rankings</span>
                    </button>
                </x-card.footer>
            </x-card>

            <!-- Danger Zone Section -->
            <x-card class="lg:col-span-2">
                <x-card.header>
                    <h5 class="font-semibold text-red-800">
                        <i class="fa fa-exclamation-triangle text-red-500 mr-2"></i>
                        Danger Zone
                    </h5>
                </x-card.header>

                <div class="p-4 text-xs text-red-600">
                    <p>Deleting your account:</p>

                    <ul class="mt-2 ml-4 list-disc space-y-1">
                        <li>Emails you a final export of your rankings</li>
                        <li>Takes your rankings, tier lists and reviews off the site</li>
                        <li>Permanently deletes the crates in your catalog</li>
                        <li>Erases your Spotify tokens and the technical data we hold about you</li>
                        @if (auth()->user()->is_pro)
                            <li>Ends your Pro license, which cannot be transferred or restored</li>
                        @endif
                    </ul>
                </div>

                <x-card.footer class="flex justify-end">
                    <button
                        class="btn-danger px-3 py-1 inline-flex items-center gap-2"
                        onclick="window.confirm({
                            title: 'Delete your account?',
                            message: 'We will email you one last export of your rankings, then remove your rankings, tier lists, reviews and catalog from the site along with your account.{{ auth()->user()->is_pro ? ' Your Pro license ends with it and cannot be restored.' : '' }} Your Spotify tokens and the technical data we hold about you are erased permanently. Are you sure you want to go?',
                            confirmText: 'It\'s time for me to go.',
                            entityId: {{ auth()->id() }},
                            componentId: '{{ $this->getId() }}',
                            action: 'destroy',
                            destructive: true
                        })"
                    >
                        <i class="fa fa-trash-alt"></i>
                        <span>Delete Account</span>
                    </button>
                </x-card.footer>
            </x-card>
        </div>
    </x-card>
</div>
