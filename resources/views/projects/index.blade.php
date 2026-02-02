<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Projects
        </h2>
    </x-slot>

    <div class="p-6"
         x-data="{
            search: '',
            open: false
         }">

        {{-- Top bar: Search + Create button --}}
        <div class="flex gap-2 items-center mb-4">
            <input type="text"
                   placeholder="Search project..."
                   class="border rounded p-2 w-64"
                   x-model="search">

            <button type="button"
                    class="!bg-blue-600 hover:!bg-blue-700 !text-white font-semibold px-4 py-2 rounded shadow"
                    @click="open = true">
                Create Project
            </button>

            <a href="{{ route('workspaces.index') }}" class="text-sm text-gray-600 underline ml-auto">
                Back to Workspaces
            </a>
        </div>

        {{-- List --}}
        <ul>
            @foreach($projects as $project)
                <li class="border p-2 mb-2"
                    x-show="'{{ strtolower($project->name) }}'.includes(search.toLowerCase())">

                    <a href="{{ route('projects.board', $project->id) }}"
                       class="text-blue-600 hover:underline font-semibold">
                        {{ $project->name }}
                    </a>
                </li>
            @endforeach
        </ul>

        {{-- Modal --}}
        <div x-show="open" x-cloak
             class="fixed inset-0 flex items-center justify-center bg-black/50"
             @keydown.escape.window="open = false"
             @click.self="open = false">

            <div class="bg-white rounded shadow p-6 w-full max-w-md">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Create Project</h3>
                    <button type="button" class="text-gray-500" @click="open = false">✕</button>
                </div>

                <form method="POST" action="{{ route('projects.store', $workspaceId) }}">
                    @csrf

                    <label class="block text-sm text-gray-700 mb-1">Project name</label>
                    <input type="text" name="name"
                           class="border rounded p-2 w-full"
                           required
                           autofocus>

                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button"
                                class="border px-4 py-2 rounded"
                                @click="open = false">
                            Cancel
                        </button>
                        <button type="submit"
                                class="!bg-blue-600 hover:!bg-blue-700 !text-white font-semibold px-4 py-2 rounded shadow">
                            Create
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
