<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and username.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <!-- Profile Photo -->
        <div x-data="{ hasPhoto: {{ $user->avatar ? 'true' : 'false' }}, photoPreview: null }">
            <!-- Photo File Input -->
            <input type="file" id="photo" name="avatar" class="hidden"
                        x-ref="photo"
                        x-on:change="
                                hasPhoto = true;
                                const reader = new FileReader();
                                reader.onload = (e) => {
                                    photoPreview = e.target.result;
                                };
                                reader.readAsDataURL($refs.photo.files[0]);
                                $refs.removeAvatarInput.value = 0;
                        " />

            <x-input-label for="photo" :value="__('Foto Profil')" />

            <!-- Current Profile Photo -->
            <div class="mt-2" x-show="hasPhoto && ! photoPreview">
                @if ($user->avatar)
                    <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" class="rounded-full h-20 w-20 object-cover shadow-md border border-gray-100">
                @endif
            </div>

            <!-- New Photo Preview -->
            <div class="mt-2" x-show="photoPreview" style="display: none;">
                <span class="block rounded-full w-20 h-20 bg-cover bg-no-repeat bg-center shadow-md border border-gray-100"
                      x-bind:style="'background-image: url(\'' + photoPreview + '\');'">
                </span>
            </div>

            <!-- Fallback Default Initial Profile -->
            <div class="mt-2" x-show="! hasPhoto && ! photoPreview" style="display: none;">
                <div class="rounded-full h-20 w-20 bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-2xl shadow-inner uppercase border border-gray-100">
                    {{ substr($user->name, 0, 1) }}
                </div>
            </div>

            <input type="hidden" name="remove_avatar" x-ref="removeAvatarInput" value="0">

            <div class="mt-3 flex items-center gap-2">
                <button type="button" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150" 
                        x-on:click.prevent="$refs.photo.click()">
                    {{ __('Pilih Foto Baru') }}
                </button>

                <button type="button" x-show="hasPhoto" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        x-on:click.prevent="hasPhoto = false; photoPreview = null; $refs.photo.value = null; $refs.removeAvatarInput.value = 1;"
                        style="display: none;">
                    {{ __('Hapus Foto') }}
                </button>
            </div>
            
            <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="username" :value="__('Username')" />
            <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" :value="old('username', $user->username)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('username')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
