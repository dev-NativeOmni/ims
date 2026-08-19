@extends('layouts.app')

@section('content')
<div x-data="badgeManager()" class="p-4" x-init="init()">
  <h1 class="text-2xl font-bold mb-4">Manajemen Badge</h1>
  <button @click="openCreate()" class="bg-blue-500 text-white px-4 py-2 rounded mb-4">Tambah Badge</button>

  <template x-if="badges.length === 0">
    <p>Loading...</p>
  </template>

  <table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
      <tr>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Key</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Target</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Active</th>
        <th class="px-6 py-3"></th>
      </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
      <template x-for="badge in badges" :key="badge.id">
        <tr>
          <td class="px-6 py-4 whitespace-nowrap" x-text="badge.key"></td>
          <td class="px-6 py-4 whitespace-nowrap" x-text="badge.title"></td>
          <td class="px-6 py-4 whitespace-nowrap" x-text="typeLabels[badge.type] || badge.type"></td>
          <td class="px-6 py-4 whitespace-nowrap" x-text="badge.type === 'completed_juz' ? badge.target_juz : badge.target_value"></td>
          <td class="px-6 py-4 whitespace-nowrap">
            <button @click="toggleActive(badge)" :class="badge.is_active ? 'bg-green-500' : 'bg-gray-400'" class="text-white px-2 py-1 rounded">
              <span x-text="badge.is_active ? 'Aktif' : 'Nonaktif'"></span>
            </button>
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
            <button @click="openEdit(badge)" class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</button>
            <button @click="destroy(badge)" class="text-red-600 hover:text-red-900">Delete</button>
          </td>
        </tr>
      </template>
    </tbody>
  </table>

  <!-- Modal for Create / Edit -->
  <div x-show="showModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white p-6 rounded w-1/2">
      <h2 class="text-xl font-semibold mb-4" x-text="editMode ? 'Edit Badge' : 'Tambah Badge'"></h2>
      <form @submit.prevent="submitForm">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Key</label>
            <input type="text" x-model="form.key" class="mt-1 block w-full border-gray-300 rounded-md" :readonly="editMode" required>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Title</label>
            <input type="text" x-model="form.title" class="mt-1 block w-full border-gray-300 rounded-md" required>
          </div>
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700">Description</label>
            <textarea x-model="form.description" class="mt-1 block w-full border-gray-300 rounded-md"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Icon</label>
            <input type="text" x-model="form.icon" class="mt-1 block w-full border-gray-300 rounded-md" required>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Type</label>
            <select x-model="form.type" class="mt-1 block w-full border-gray-300 rounded-md" required>
              <template x-for="(label, key) in typeLabels" :key="key">
                <option :value="key" x-text="label"></option>
              </template>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Target Value</label>
            <input type="number" step="0.01" x-model="form.target_value" class="mt-1 block w-full border-gray-300 rounded-md" required>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Target Juz</label>
            <input type="number" x-model="form.target_juz" class="mt-1 block w-full border-gray-300 rounded-md">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Sort Order</label>
            <input type="number" x-model="form.sort_order" class="mt-1 block w-full border-gray-300 rounded-md" required>
          </div>
        </div>
        <div class="mt-4 flex justify-end">
          <button type="button" @click="closeModal()" class="mr-2 px-4 py-2 bg-gray-300 rounded">Cancel</button>
          <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded" x-text="editMode ? 'Update' : 'Create'"></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function badgeManager() {
  return {
    badges: [],
    typeLabels: @json($typeLabels),
    showModal: false,
    editMode: false,
    form: {},
    fetchBadges() {
      fetch('{{ route('badges.index') }}', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => { this.badges = data.badges; });
    },
    openCreate() {
      this.editMode = false;
      this.form = { key:'', title:'', description:'', icon:'', type:'count_hafalan', target_value:0, target_juz:null, sort_order:0 };
      this.showModal = true;
    },
    openEdit(badge) {
      this.editMode = true;
      this.form = { ...badge };
      this.showModal = true;
    },
    closeModal() { this.showModal = false; },
    submitForm() {
      const url = this.editMode ? `{{ route('badges.update', '') }}/${this.form.id}` : '{{ route('badges.store') }}';
      const method = this.editMode ? 'PUT' : 'POST';
      fetch(url, {
        method,
        headers: {
          'X‑CSRF‑TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(this.form),
      }).then(() => { this.closeModal(); this.fetchBadges(); });
    },
    toggleActive(badge) {
      fetch(`{{ route('badges.toggleActive', '') }}/${badge.id}`, {
        method: 'POST',
        headers: { 'X‑CSRF‑TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
      }).then(() => this.fetchBadges());
    },
    destroy(badge) {
      fetch(`{{ route('badges.destroy', '') }}/${badge.id}`, {
        method: 'DELETE',
        headers: { 'X‑CSRF‑TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
      }).then(() => this.fetchBadges());
    },
    init() { this.fetchBadges(); }
  }
}
</script>
@endsection
