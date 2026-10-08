<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between"><h2 class="font-semibold text-xl">Users</h2>
            <a href="{{ route('admin.users.create') }}" class="px-3 py-2 bg-brand text-white text-sm">Add user</a></div>
    </x-slot>
    <div class="max-w-7xl mx-auto px-4">
        <table class="min-w-full text-sm bg-white dark:bg-black border">
            <thead class="bg-brand-silver dark:bg-brand-charcoal text-left"><tr>
                <th class="px-3 py-2">Name</th><th class="px-3 py-2">Email</th><th class="px-3 py-2">Role</th><th class="px-3 py-2"></th>
            </tr></thead>
            <tbody>
                @foreach($users as $user)
                    <tr class="border-t border-brand-silver dark:border-brand-charcoal">
                        <td class="px-3 py-2">{{ $user->name }}</td>
                        <td class="px-3 py-2">{{ $user->email }}</td>
                        <td class="px-3 py-2">{{ $user->roles->pluck('name')->join(', ') }}</td>
                        <td class="px-3 py-2"><a class="text-brand" href="{{ route('admin.users.edit', $user) }}">Edit</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">{{ $users->links() }}</div>
    </div>
</x-app-layout>
