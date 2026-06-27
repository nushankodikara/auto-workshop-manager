@extends('layouts.app')

@section('title', 'Outsourcing Partners')

@section('content')
<div class="space-y-6">

    <!-- Header Actions -->
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Outsourcing Partner Companies</h2>
        <button onclick="document.getElementById('create-partner-drawer').classList.remove('hidden')"
                class="px-4 py-2 bg-primary hover:bg-primary-hover text-white font-medium rounded-lg text-sm transition flex items-center gap-1.5 shadow-sm cursor-pointer">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>Register Partner Company</span>
        </button>
    </div>

    <!-- Partners Table -->
    <div class="app-card rounded-2xl overflow-hidden shadow-xs">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-slate-100/60 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                    <th class="py-4 px-6">Company Name</th>
                    <th class="py-4 px-6">Phone</th>
                    <th class="py-4 px-6">Email</th>
                    <th class="py-4 px-6">Address</th>
                    <th class="py-4 px-6 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-850/60">
                @forelse($companies as $company)
                    <tr class="hover:bg-slate-100/40 dark:hover:bg-slate-900/40 transition text-slate-750 dark:text-slate-300">
                        <td class="py-4 px-6 font-semibold text-slate-850 dark:text-slate-200">
                            {{ $company->name }}
                        </td>
                        <td class="py-4 px-6 font-mono text-xs">{{ $company->phone ?? 'N/A' }}</td>
                        <td class="py-4 px-6">{{ $company->email ?? 'N/A' }}</td>
                        <td class="py-4 px-6 text-xs">{{ $company->address ?? 'N/A' }}</td>
                        <td class="py-4 px-6 text-right flex items-center justify-end gap-2">
                            <!-- Edit Trigger -->
                            <button onclick="openEditPartnerDrawer({{ $company->id }}, '{{ addslashes($company->name) }}', '{{ addslashes($company->phone) }}', '{{ addslashes($company->email) }}', '{{ addslashes($company->address) }}')"
                                    class="text-xs font-bold text-primary bg-primary/10 border border-primary/20 px-2.5 py-1 rounded transition hover:bg-primary hover:text-white cursor-pointer">
                                Edit
                            </button>
                            <!-- Delete Trigger -->
                            <form action="{{ route('outsourcing.destroy', $company->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to remove this partner company?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-bold text-red-600 bg-red-500/10 border border-red-500/20 px-2.5 py-1 rounded transition hover:bg-red-650 hover:text-white cursor-pointer">
                                    Remove
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-slate-500">
                            No outsourcing partner companies registered yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

<!-- Sidebar Drawer Modal: Register Partner -->
<div id="create-partner-drawer" class="fixed inset-0 z-50 overflow-hidden hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute inset-0 bg-slate-950/75 transition-opacity" onclick="document.getElementById('create-partner-drawer').classList.add('hidden')"></div>

        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div class="pointer-events-auto w-screen max-w-md">
                <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-xl">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-955/40">
                        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <i data-lucide="handshake" class="w-5 h-5 text-primary"></i>
                            <span>Register Partner Company</span>
                        </h2>
                        <button onclick="document.getElementById('create-partner-drawer').classList.add('hidden')" class="text-slate-500 hover:text-slate-400 font-bold p-2 cursor-pointer">✕</button>
                    </div>

                    <form action="{{ route('outsourcing.store') }}" method="POST" class="flex-1 p-6 space-y-5">
                        @csrf

                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Company Name</label>
                            <input type="text" name="name" id="name" required placeholder="e.g., Colombo Engine Rebores"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Phone Number</label>
                            <input type="text" name="phone" id="phone" placeholder="e.g., +94112345678"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Email Address</label>
                            <input type="email" name="email" id="email" placeholder="e.g., contact@partner.com"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <!-- Address -->
                        <div>
                            <label for="address" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Address</label>
                            <textarea name="address" id="address" rows="4" placeholder="Partner office address details..."
                                      class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm"></textarea>
                        </div>

                        <!-- Buttons -->
                        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex gap-3">
                            <button type="submit"
                                    class="flex-1 py-2.5 px-4 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg transition text-sm cursor-pointer">
                                Register Partner
                            </button>
                            <button type="button" onclick="document.getElementById('create-partner-drawer').classList.add('hidden')"
                                    class="py-2.5 px-4 bg-slate-200 dark:bg-slate-855 hover:bg-slate-300 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-350 font-medium rounded-lg transition text-sm cursor-pointer">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sidebar Drawer Modal: Edit Partner -->
<div id="edit-partner-drawer" class="fixed inset-0 z-50 overflow-hidden hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute inset-0 bg-slate-955/75 transition-opacity" onclick="document.getElementById('edit-partner-drawer').classList.add('hidden')"></div>

        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div class="pointer-events-auto w-screen max-w-md">
                <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-xl">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-955/40">
                        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <i data-lucide="edit" class="w-5 h-5 text-primary"></i>
                            <span>Edit Partner Details</span>
                        </h2>
                        <button onclick="document.getElementById('edit-partner-drawer').classList.add('hidden')" class="text-slate-500 hover:text-slate-400 font-bold p-2 cursor-pointer">✕</button>
                    </div>

                    <form id="edit-partner-form" action="" method="POST" class="flex-1 p-6 space-y-5">
                        @csrf
                        @method('PUT')

                        <!-- Name -->
                        <div>
                            <label for="edit_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Company Name</label>
                            <input type="text" name="name" id="edit_name" required
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <!-- Phone -->
                        <div>
                            <label for="edit_phone" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Phone Number</label>
                            <input type="text" name="phone" id="edit_phone"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="edit_email" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Email Address</label>
                            <input type="email" name="email" id="edit_email"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <!-- Address -->
                        <div>
                            <label for="edit_address" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Address</label>
                            <textarea name="address" id="edit_address" rows="4"
                                      class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm"></textarea>
                        </div>

                        <!-- Buttons -->
                        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex gap-3">
                            <button type="submit"
                                    class="flex-1 py-2.5 px-4 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg transition text-sm cursor-pointer font-sans">
                                Save Details
                            </button>
                            <button type="button" onclick="document.getElementById('edit-partner-drawer').classList.add('hidden')"
                                    class="py-2.5 px-4 bg-slate-200 dark:bg-slate-855 hover:bg-slate-300 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-350 font-medium rounded-lg transition text-sm cursor-pointer">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function openEditPartnerDrawer(id, name, phone, email, address) {
        document.getElementById('edit-partner-form').action = `/outsourcing/${id}`;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_phone').value = phone || '';
        document.getElementById('edit_email').value = email || '';
        document.getElementById('edit_address').value = address || '';
        document.getElementById('edit-partner-drawer').classList.remove('hidden');
    }
</script>
@endsection
