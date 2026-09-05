<div>
    <div class="mt-2 bg-white shadow-lg p-4 md:p-6 rounded-xl">        
        <!-- Profile Card Section -->
        <div class="bg-white rounded-xl p-6 shadow-md border border-slate-100">
            <div class="flex items-center space-x-6">
                <div class="flex-shrink-0">
                    <img
                        src="{{ auth()->user()->avatar }}"
                        alt="{{ auth()->user()->name }}"
                        class="w-16 h-16 rounded-full object-cover border-2 border-slate-200"
                    >
                </div>
                
                <div class="flex-grow">
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
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-4">
            <!-- Email Preferences Section -->
            <div class="bg-white rounded-xl p-6 shadow-md transition-all duration-300">
                <div class="flex items-center mb-6">
                    <h5 class="font-semibold">Email Preferences</h5>
                </div>
                
                <div class="space-y-6">
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
                </div>
            </div>
            
            <!-- Account Actions Section -->
            <div class="bg-white rounded-xl p-6 shadow-md border border-slate-100 hover:shadow-lg transition-all duration-300">
                <div class="flex items-center mb-6">
                    <h5 class="font-semibold">Account Actions</h5>
                </div>
                
                <div class="space-y-4">
                    <div class="group">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Email Address</label>
                        <input
                            type="email"
                            value="{{ auth()->user()->email }}"
                            disabled
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm text-slate-600 cursor-not-allowed"
                        >
                    </div>
                </div>
                
                <div class="space-y-4 mt-6">
                    <div class="group">
                        <button
                            class="btn-primary w-full text-sm py-4 px-6 flex items-center justify-center space-x-2"
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
                        <p class="text-xs text-slate-500 mt-2 text-center">Download all your data as a backup</p>
                    </div>
                    
                    <div class="pt-4 border-t border-slate-100">
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                            <div class="flex items-center mb-2">
                                <i class="fa fa-exclamation-triangle text-red-500 mr-2"></i>
                                <span class="text-sm font-medium text-red-800">Danger Zone</span>
                            </div>
                            <p class="text-xs text-red-600">
                                Your rankings will be removed from the site and your Spotify tokens and technical
                                data erased. We keep your account record so your rankings can be restored if you
                                sign back in &mdash; see our
                                <a href="{{ route('privacy') }}" class="underline hover:text-red-800">Privacy Policy</a>.
                            </p>
                        </div>
                        
                        <button
                            class="btn-danger w-full text-sm py-4 px-6 flex items-center justify-center space-x-2"
                            onclick="window.confirm({
                                title: 'Delete your account?',
                                message: 'We will email you one last export of your rankings, then remove them from the site along with your account. Your Spotify tokens and the technical data we hold about you are erased permanently. Are you sure you want to go?',
                                confirmText: 'It\'s time for me to go.',
                                entityId: {{ auth()->id() }},
                                componentId: '{{ $this->getId() }}',
                                action: 'destroy'
                            })"
                        >
                            <i class="fa fa-trash-alt"></i>
                            <span>Delete Account</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>